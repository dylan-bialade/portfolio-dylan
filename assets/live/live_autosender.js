// assets/live/live_autosender.js
// Sender headless (pas d’UI visible) + polling DB (live_signal.php)
// - Démarrage via window.LiveSender.startFromUserGesture()
// - Autostart si localStorage flag présent
// - Heartbeat immédiat + intervalle
// - Logs en cas d’échec d’autostart

(() => {
  const CFG = window.__LIVE_CONFIG__ || {
    apiUrl: "/live_api.php",
    signalUrl: "/live_signal.php",
  };

  const FLAG_KEY = "bialadev_live_sender_enabled";
  const DEBUG = !!window.__LIVE_DEBUG__;
  const log = (...a) => { if (DEBUG) console.log("[LiveSender]", ...a); };
  const warn = (...a) => console.warn("[LiveSender]", ...a);

  // Cache l’UI si elle existe dans le footer
  const widget = document.getElementById("liveWidget");
  if (widget) widget.style.display = "none";

  let running = false;
  let room = null;
  let sinceId = 0;

  let localStream = null;
  let wakeLock = null;
  let heartbeatTimer = null;
  let pollAbort = null;

  const peers = new Map(); // viewerId -> RTCPeerConnection

  const sleep = (ms) => new Promise(r => setTimeout(r, ms));

  async function api(action, opts = {}) {
    const url = new URL(CFG.apiUrl, window.location.origin);
    url.searchParams.set("action", action);

    const fetchOpts = { credentials: "same-origin", ...opts };

    if (fetchOpts.method && fetchOpts.method.toUpperCase() === "POST" && fetchOpts.body && typeof fetchOpts.body === "object") {
      fetchOpts.headers = { ...(fetchOpts.headers || {}), "Content-Type": "application/json" };
      fetchOpts.body = JSON.stringify(fetchOpts.body);
    }

    const res = await fetch(url.toString(), fetchOpts);
    return res.json();
  }

  async function signalSend({ room, msgType, payload, viewerId }) {
    const res = await fetch(`${CFG.signalUrl}?action=send`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        room,
        fromRole: "sender",
        direction: "to_viewer",
        msgType,
        payload,
        viewerId
      })
    });
    return res.json();
  }

  async function signalReceive({ room, sinceId, timeout = 15000 }) {
    const url = new URL(CFG.signalUrl, window.location.origin);
    url.searchParams.set("action", "receive");
    url.searchParams.set("room", room);
    url.searchParams.set("role", "sender");
    url.searchParams.set("since_id", String(sinceId || 0));
    url.searchParams.set("timeout", String(timeout));

    const res = await fetch(url.toString(), { credentials: "same-origin", signal: pollAbort?.signal });
    return res.json();
  }

  async function requestWakeLock() {
    try {
      if ("wakeLock" in navigator && !wakeLock) {
        wakeLock = await navigator.wakeLock.request("screen");
        wakeLock.addEventListener("release", () => { wakeLock = null; });
        log("wakeLock acquired");
      }
    } catch (e) {
      log("wakeLock unavailable", e?.name || e);
    }
  }

  async function releaseWakeLock() {
    try { await wakeLock?.release?.(); } catch {}
    wakeLock = null;
  }

  function replaceEndedVideoTrack(stream) {
    const v = stream.getVideoTracks()[0];
    if (!v) return;

    v.addEventListener("ended", () => {
      warn("video track ended -> replacing with black track");
      try {
        const canvas = document.createElement("canvas");
        canvas.width = 640; canvas.height = 480;
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#000";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const blackStream = canvas.captureStream(5);
        const blackTrack = blackStream.getVideoTracks()[0];

        stream.removeTrack(v);
        stream.addTrack(blackTrack);

        for (const pc of peers.values()) {
          const sender = pc.getSenders().find(s => s.track && s.track.kind === "video");
          sender?.replaceTrack(blackTrack);
        }
      } catch (e) {
        warn("replace track failed", e?.name || e);
      }
    });
  }

  async function getLocalStream() {
    if (localStream) return localStream;

    const constraints = {
      audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
      video: {
        facingMode: "user",
        width: { ideal: 640 },
        height: { ideal: 480 },
        frameRate: { ideal: 20, max: 30 }
      }
    };

    localStream = await navigator.mediaDevices.getUserMedia(constraints);
    replaceEndedVideoTrack(localStream);
    return localStream;
  }

  function makePeer(viewerId) {
    const pc = new RTCPeerConnection({
      iceServers: [{ urls: "stun:stun.l.google.com:19302" }],
    });

    pc.onicecandidate = async (ev) => {
      if (!ev.candidate) return;
      await signalSend({ room, msgType: "ice", payload: { candidate: ev.candidate }, viewerId });
    };

    pc.onconnectionstatechange = () => {
      const st = pc.connectionState;
      log("pc state", viewerId, st);
      if (st === "failed" || st === "disconnected" || st === "closed") cleanupPeer(viewerId);
    };

    return pc;
  }

  async function handleOffer(msg) {
    const viewerId = msg.viewerId || msg.from || msg?.payload?.viewerId;
    const offer = msg?.payload?.offer;
    if (!viewerId || !offer) return;

    cleanupPeer(viewerId);

    const pc = makePeer(viewerId);
    peers.set(viewerId, pc);

    const stream = await getLocalStream();
    stream.getTracks().forEach(t => pc.addTrack(t, stream));

    await pc.setRemoteDescription(offer);
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);

    const res = await signalSend({ room, msgType: "answer", payload: { answer }, viewerId });
    if (!res || res.ok !== true) warn("answer send failed", res);
  }

  async function handleIce(msg) {
    const viewerId = msg.viewerId || msg.from || msg?.payload?.viewerId;
    const candidate = msg?.payload?.candidate;
    if (!viewerId || !candidate) return;

    const pc = peers.get(viewerId);
    if (!pc) return;

    try { await pc.addIceCandidate(candidate); } catch {}
  }

  function cleanupPeer(viewerId) {
    const pc = peers.get(viewerId);
    if (!pc) return;
    try { pc.close(); } catch {}
    peers.delete(viewerId);
  }

  function cleanupAllPeers() {
    for (const id of [...peers.keys()]) cleanupPeer(id);
  }

  async function pollLoop() {
    while (running) {
      let res;
      try {
        res = await signalReceive({ room, sinceId, timeout: 15000 });
      } catch (e) {
        if (!running) return;
        await sleep(800);
        continue;
      }

      if (!running) return;

      if (!res || res.ok !== true) {
        await sleep(800);
        continue;
      }

      sinceId = res.lastId || sinceId;
      const msgs = res.messages || [];

      for (const m of msgs) {
        if (m.msgType === "offer") await handleOffer(m);
        if (m.msgType === "ice") await handleIce(m);
      }
    }
  }

  async function startInternal() {
    if (running) return true;

    const boot = await api("bootstrap_sender").catch(() => null);
    if (!boot || boot.ok !== true) return false;
    if (!boot.canStream || !boot.streamKey) return false;

    room = boot.streamKey;
    sinceId = 0;

    pollAbort = new AbortController();

    await getLocalStream();
    await requestWakeLock();

    // IMPORTANT: heartbeat immédiat (sinon tu n'apparais pas rapidement)
    try { await api("heartbeat_sender"); } catch {}

    heartbeatTimer = setInterval(() => {
      api("heartbeat_sender").catch(() => {});
    }, 10000);

    running = true;
    pollLoop().catch((e) => warn("pollLoop fatal", e));

    return true;
  }

  async function stopInternal() {
    running = false;

    try { pollAbort?.abort(); } catch {}
    pollAbort = null;

    if (heartbeatTimer) {
      clearInterval(heartbeatTimer);
      heartbeatTimer = null;
    }

    cleanupAllPeers();

    try { localStream?.getTracks()?.forEach(t => t.stop()); } catch {}
    localStream = null;

    await releaseWakeLock();

    room = null;
    sinceId = 0;
  }

  window.LiveSender = {
    async startFromUserGesture() {
      try {
        const ok = await startInternal();
        if (ok) localStorage.setItem(FLAG_KEY, "1");
        return ok;
      } catch (e) {
        warn("startFromUserGesture", e?.name || e);
        return false;
      }
    },
    async stop() {
      localStorage.removeItem(FLAG_KEY);
      await stopInternal();
    },
    isRunning() { return running; }
  };

  // Autostart : si flag présent, on tente. Si ça échoue, on LOG au lieu de silencieux.
  (async () => {
    const enabled = localStorage.getItem(FLAG_KEY) === "1";
    if (!enabled) return;

    try {
      const ok = await startInternal();
      if (!ok) warn("autoStart: refusé (droits ou boot)");
    } catch (e) {
      warn("autoStart failed (souvent dû à getUserMedia sans geste utilisateur)", e?.name || e);
    }
  })();

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible" && running) requestWakeLock();
  });
})();
