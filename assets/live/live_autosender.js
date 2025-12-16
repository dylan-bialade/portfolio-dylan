// assets/live/live_autosender.js
(() => {
  const CFG = window.__LIVE_CONFIG__ || {};
  const API_URL = CFG.apiUrl || "/live_api.php";
  const SIGNAL_URL = CFG.signalUrl || "/live_signal.php";

  // Désactiver le sender sur certaines pages si besoin
  if (window.__LIVE_DISABLE_AUTOSENDER__ === true) return;

  // Supprime l’UI du sender si elle existe dans le HTML
  // (ça ne change rien au consentement navigateur caméra/micro)
  const w = document.getElementById("liveWidget");
  if (w) w.remove();

  const LS_KEY = "live_sender_enabled";
  const DEBUG = !!window.__LIVE_DEBUG__;

  let pc = null;
  let localStream = null;
  let room = null;
  let sinceId = 0;
  let polling = false;
  let wakeLock = null;
  let blackTrack = null;

  const log = (...args) => DEBUG && console.log("[LiveSender]", ...args);

  async function apiGet(action) {
    const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, { credentials: "same-origin" });
    return r.json();
  }

  async function signalPost(url, body) {
    const r = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(body),
    });
    return r.json();
  }

  function hasUserEnabledSender() {
    return localStorage.getItem(LS_KEY) === "1";
  }

  async function requestWakeLock() {
    try {
      if (!("wakeLock" in navigator)) return;
      wakeLock = await navigator.wakeLock.request("screen");
      wakeLock.addEventListener("release", () => log("WakeLock released"));
      log("WakeLock acquired");
    } catch (e) {
      log("WakeLock refused", e?.name);
    }
  }

  async function ensureWakeLock() {
    await requestWakeLock();
    document.addEventListener("visibilitychange", async () => {
      if (document.visibilityState === "visible" && pc) {
        await requestWakeLock();
      }
    });
  }

  function stopWakeLock() {
    try { wakeLock?.release(); } catch {}
    wakeLock = null;
  }

  function createBlackVideoTrack(width = 640, height = 480) {
    const c = document.createElement("canvas");
    c.width = width;
    c.height = height;
    const ctx = c.getContext("2d");
    ctx.fillStyle = "#000";
    ctx.fillRect(0, 0, c.width, c.height);
    const stream = c.captureStream(1);
    return stream.getVideoTracks()[0];
  }

  function makePeerConnection() {
    const peer = new RTCPeerConnection({
      iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
    });

    peer.onconnectionstatechange = () => log("pc state:", peer.connectionState);
    peer.oniceconnectionstatechange = () => log("ice state:", peer.iceConnectionState);

    return peer;
  }

  async function getLocalStream() {
    return navigator.mediaDevices.getUserMedia({
      audio: true,
      video: {
        facingMode: "user",
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    });
  }

  async function heartbeat() {
    try { await apiGet("heartbeat"); } catch {}
  }

  async function pollOffersLoop() {
    if (polling) return;
    polling = true;

    while (pc && room) {
      const res = await signalPost(`${SIGNAL_URL}?action=receive`, {
        role: "sender",
        room,
        sinceId,
        timeout: 12000
      });

      if (res?.ok && Array.isArray(res.messages)) {
        for (const m of res.messages) {
          sinceId = Math.max(sinceId, m.id || 0);
          const t = m.msgType || m.msg_type;

          if (t === "offer") {
            await handleOffer(m);
          } else if (t === "ice") {
            const cand = m?.payload?.candidate;
            if (cand) {
              try { await pc.addIceCandidate(cand); } catch {}
            }
          }
        }
      }

      await new Promise(r => setTimeout(r, 600));
    }

    polling = false;
  }

  async function handleOffer(m) {
    const viewerId = m.viewerId;
    const offer = m?.payload?.offer;

    if (!viewerId || !offer) {
      log("Offer mal formée", m);
      return;
    }

    log("Offer reçue de", viewerId);

    pc.onicecandidate = async (ev) => {
      if (!ev.candidate) return;
      await signalPost(`${SIGNAL_URL}?action=send`, {
        room,
        fromRole: "sender",
        direction: "sender_to_viewer",
        msgType: "ice",
        payload: { candidate: ev.candidate },
        viewerId
      });
    };

    await pc.setRemoteDescription(offer);
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);

    await signalPost(`${SIGNAL_URL}?action=send`, {
      room,
      fromRole: "sender",
      direction: "sender_to_viewer",
      msgType: "answer",
      payload: { answer },
      viewerId
    });

    log("Answer envoyée à", viewerId);
  }

  async function startInternal() {
    const boot = await apiGet("bootstrap_sender");
    if (!boot?.ok || !boot?.canStream || !boot?.streamKey) {
      log("bootstrap_sender refuse", boot);
      return;
    }
    room = boot.streamKey;

    localStream = await getLocalStream();

    const vTrack = localStream.getVideoTracks()[0];
    if (vTrack) {
      vTrack.addEventListener("ended", () => {
        try {
          if (!blackTrack) blackTrack = createBlackVideoTrack();
          const sender = pc?.getSenders()?.find(s => s.track && s.track.kind === "video");
          sender?.replaceTrack(blackTrack);
        } catch {}
      });
    }

    pc = makePeerConnection();
    localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

    await ensureWakeLock();

    sinceId = 0;
    pollOffersLoop();

    heartbeat();
    setInterval(() => pc && heartbeat(), 10000);
  }

  async function startFromUserGesture() {
    localStorage.setItem(LS_KEY, "1");
    try {
      await startInternal();
      return true;
    } catch (e) {
      log("startFromUserGesture error", e?.name || e);
      return false;
    }
  }

  async function autoStartIfEnabled() {
    if (!hasUserEnabledSender()) return;
    try {
      await startInternal();
    } catch (e) {
      log("autoStart refused", e?.name || e);
      stop();
    }
  }

  function stop() {
    try { pc?.close(); } catch {}
    pc = null;

    try { localStream?.getTracks()?.forEach(t => t.stop()); } catch {}
    localStream = null;

    blackTrack = null;
    room = null;
    sinceId = 0;
    stopWakeLock();
  }

  window.LiveSender = { startFromUserGesture, stop };

  autoStartIfEnabled();
})();
