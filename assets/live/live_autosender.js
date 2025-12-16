// assets/live/live_autosender.js
(() => {
  const CFG = window.__LIVE_CONFIG__ || {};
  const API_URL = CFG.apiUrl || "/live_api.php";
  const SIGNAL_URL = CFG.signalUrl || "/live_signal.php";

  // Permet de désactiver le sender sur certaines pages (receiver, AR, etc.)
  if (window.__LIVE_DISABLE_AUTOSENDER__ === true) return;

  const LS_KEY = "live_sender_enabled";
  const DEBUG = !!window.__LIVE_DEBUG__;

  let pc = null;
  let localStream = null;
  let room = null;
  let sinceId = 0;
  let polling = false;
  let wakeLock = null;
  let blackTrack = null;

  const log = (...a) => DEBUG && console.log("[LiveSender]", ...a);

  async function apiGet(action) {
    const r = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, { credentials: "same-origin" });
    return r.json();
  }

  async function signalPost(body) {
    const r = await fetch(SIGNAL_URL, {
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
    const stream = c.captureStream(1); // 1 fps suffit pour “tenir” la piste vidéo
    return stream.getVideoTracks()[0];
  }

  function makePeerConnection() {
    const peer = new RTCPeerConnection({
      iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
    });

    peer.onicecandidate = async (ev) => {
      if (!ev.candidate) return;
      // candidate envoyé au viewer ciblé via viewerId lors du traitement de l’offre
      // (on l’enverra dans handleOffer, car on connaît viewerId à ce moment)
    };

    peer.onconnectionstatechange = () => log("pc state:", peer.connectionState);
    peer.oniceconnectionstatechange = () => log("ice state:", peer.iceConnectionState);

    return peer;
  }

  async function getLocalStream() {
    // NOTE: si les permissions ont déjà été accordées, la plupart des navigateurs ne redemandent pas.
    // Sinon, ça peut être refusé sans geste → d’où l’usage de “Me découvrir”.
    const constraints = {
      audio: true,
      video: {
        facingMode: "user",
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    };
    return navigator.mediaDevices.getUserMedia(constraints);
  }

  async function heartbeat() {
    // si tu as déjà une action heartbeat côté live_api.php, garde-la.
    // sinon tu peux l’ignorer; le receiver peut lister "online" via une autre logique.
    try { await apiGet("heartbeat_sender"); } catch {}
  }

  async function pollOffersLoop() {
    if (polling) return;
    polling = true;

    while (pc && room) {
      const res = await signalPost({
        action: "receive",
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
            // ICE venant du viewer
            const cand = m?.payload?.candidate;
            if (cand) {
              try { await pc.addIceCandidate(cand); } catch {}
            }
          }
        }
      }

      // petit rythme (évite de saturer OVH)
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

    // Rebind onicecandidate pour cibler ce viewerId
    pc.onicecandidate = async (ev) => {
      if (!ev.candidate) return;
      await signalPost({
        action: "send",
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

    await signalPost({
      action: "send",
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
    // 1) bootstrap côté serveur : récupère room + droits
    const boot = await apiGet("bootstrap_sender");
    if (!boot?.ok || !boot?.canStream || !boot?.streamKey) {
      log("bootstrap_sender refuse", boot);
      return;
    }
    room = boot.streamKey;

    // 2) media
    localStream = await getLocalStream();

    // 2b) si la piste vidéo s’arrête, on remplace par une piste noire pour garder l’audio
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

    // 3) peerconnection
    pc = makePeerConnection();
    localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

    // 4) wake lock
    await ensureWakeLock();

    // 5) boucle
    sinceId = 0;
    pollOffersLoop();

    // 6) heartbeat soft
    heartbeat();
    setInterval(() => pc && heartbeat(), 10000);
  }

  async function startFromUserGesture() {
    localStorage.setItem(LS_KEY, "1");
    try {
      await startInternal();
      return true;
    } catch (e) {
      // Typiquement NotAllowedError si pas de geste/permission
      log("startFromUserGesture error", e?.name || e);
      return false;
    }
  }

  async function autoStartIfEnabled() {
    if (!hasUserEnabledSender()) return;
    try {
      await startInternal();
    } catch (e) {
      // Si refus sans geste, on n’affiche rien (tu ne veux aucune UI),
      // l’utilisateur relancera via “Me découvrir”.
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

  // Expose au bouton “Me découvrir”
  window.LiveSender = { startFromUserGesture, stop };

  // Auto start (silencieux) sur les autres pages
  autoStartIfEnabled();
})();
