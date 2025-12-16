// assets/live/live_receiver.js
// Receiver (page réservée aux comptes autorisés) — FIX: bouton Regarder + audio

(function () {
  const CFG = window.__LIVE_CONFIG__ || {
    apiUrl: "/live_api.php",
    signalUrl: "/live_signal.php",
  };

  function $(id) { return document.getElementById(id); }

  const listEl = $("streamsList");
  const statusEl = $("streamsStatus");
  const watchStatusEl = $("watchStatus");
  const btnRefresh = $("btnRefreshStreams");
  const btnLeave = $("btnLeave");
  const videoEl = $("receiverVideo");

  function setStatus(text) { if (statusEl) statusEl.textContent = text || ""; }
  function setWatchStatus(text) { if (watchStatusEl) watchStatusEl.textContent = text || ""; }

  console.log("[LiveReceiver] loaded", CFG);

  let pc = null;
  let room = null;
  let viewerId = null;
  let sinceId = 0;
  let running = false;

  // ---- Audio unlock (sans UI) ----
  let audioCtx = null;
  let audioEl = null;

  function ensureAudioEl() {
    if (!audioEl) {
      audioEl = document.createElement("audio");
      audioEl.autoplay = true;
      audioEl.playsInline = true;
      audioEl.muted = false;
      audioEl.volume = 1;
      audioEl.style.display = "none";
      document.body.appendChild(audioEl);
    }
  }

  async function unlockAudioOnGesture() {
    // Appelé depuis le clic "Regarder"
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state !== "running") await audioCtx.resume();

      // tick (certains mobiles aiment bien)
      const buffer = audioCtx.createBuffer(1, 1, 22050);
      const src = audioCtx.createBufferSource();
      src.buffer = buffer;
      src.connect(audioCtx.destination);
      src.start(0);
    } catch (e) {
      console.warn("[LiveReceiver] AudioContext unlock failed:", e);
    }

    try {
      ensureAudioEl();
      await audioEl.play();
    } catch {
      // si bloqué, on retentera après answer
    }
  }

  function attachAudioFromStream(stream) {
    ensureAudioEl();
    const aTracks = stream.getAudioTracks ? stream.getAudioTracks() : [];
    console.log("[LiveReceiver] tracks audio:", aTracks.length, "video:", (stream.getVideoTracks?.().length || 0));

    if (!aTracks || aTracks.length === 0) {
      setWatchStatus("Lecture en cours (AUDIO: 0 piste reçue).");
      return;
    }

    const audioOnly = new MediaStream(aTracks);
    audioEl.srcObject = audioOnly;
    audioEl.muted = false;
    audioEl.volume = 1;
    audioEl.play().catch(() => {});
  }

  async function api(action) {
    const url = new URL(CFG.apiUrl, window.location.origin);
    url.searchParams.set("action", action);
    const res = await fetch(url.toString(), { credentials: "same-origin" });
    return res.json();
  }

  async function signalSend({ room, msgType, payload }) {
    const res = await fetch(`${CFG.signalUrl}?action=send`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        room,
        fromRole: "viewer",
        direction: "to_sender",
        msgType,
        payload,
        viewerId
      })
    });
    return res.json();
  }

  async function signalReceive(timeout = 15000) {
    const url = new URL(CFG.signalUrl, window.location.origin);
    url.searchParams.set("action", "receive");
    url.searchParams.set("room", room);
    url.searchParams.set("role", "viewer");
    url.searchParams.set("viewerId", viewerId);
    url.searchParams.set("since_id", String(sinceId));
    url.searchParams.set("timeout", String(timeout));
    const res = await fetch(url.toString(), { credentials: "same-origin" });
    return res.json();
  }

  // FIX ICI : fonction valide (ton fichier avait une erreur """: )
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    }[c]));
  }

  function makePeer() {
    const peer = new RTCPeerConnection({
      iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
    });

    peer.ontrack = (ev) => {
      const stream = ev.streams[0];
      if (!stream) return;

      if (videoEl) {
        videoEl.srcObject = stream;
        videoEl.muted = false;
        videoEl.volume = 1;
        videoEl.play().catch(() => {});
      }

      attachAudioFromStream(stream);
    };

    peer.onicecandidate = async (ev) => {
      if (!ev.candidate) return;
      await signalSend({ room, msgType: "ice", payload: { candidate: ev.candidate } });
    };

    return peer;
  }

  async function handleAnswer(msg) {
    const answer = msg?.payload?.answer;
    if (!answer) return;

    await pc.setRemoteDescription(answer);

    // retente play audio après answer
    try { await audioEl?.play?.(); } catch {}

    setWatchStatus("Lecture en cours.");
  }

  async function handleIce(msg) {
    const c = msg?.payload?.candidate;
    if (!c) return;
    try { await pc.addIceCandidate(c); } catch {}
  }

  async function pollLoop() {
    while (running) {
      const res = await signalReceive(15000);
      if (!running) return;

      if (!res || res.ok !== true) {
        setWatchStatus("Signalisation indisponible.");
        await new Promise(r => setTimeout(r, 1200));
        continue;
      }

      sinceId = res.lastId || sinceId;
      const msgs = res.messages || [];
      for (const m of msgs) {
        if (m.msgType === "answer") await handleAnswer(m);
        if (m.msgType === "ice") await handleIce(m);
      }
    }
  }

  async function leave() {
    running = false;
    sinceId = 0;
    viewerId = null;
    room = null;

    try { pc?.close(); } catch {}
    pc = null;

    if (videoEl) videoEl.srcObject = null;
    try { if (audioEl) audioEl.srcObject = null; } catch {}

    if (btnLeave) btnLeave.disabled = true;
    setWatchStatus("");
  }

  async function watch(roomKey) {
    console.log("[LiveReceiver] watch", roomKey);

    await leave();

    // IMPORTANT: clic utilisateur => unlock audio ici
    await unlockAudioOnGesture();

    room = roomKey;
    viewerId = (crypto?.randomUUID ? crypto.randomUUID() : ("v-" + Math.random().toString(16).slice(2)));
    sinceId = 0;

    ensureAudioEl();

    pc = makePeer();
    running = true;

    // Force réception audio + vidéo (plus fiable)
    try {
      pc.addTransceiver("audio", { direction: "recvonly" });
      pc.addTransceiver("video", { direction: "recvonly" });
    } catch {}

    setWatchStatus(`Connexion au flux ${room}…`);
    if (btnLeave) btnLeave.disabled = false;

    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    const sent = await signalSend({ room, msgType: "offer", payload: { offer } });
    if (!sent || sent.ok !== true) {
      setWatchStatus("Impossible d'envoyer l'offre (signalisation).");
      running = false;
      return;
    }

    setWatchStatus("Offer envoyée, attente answer...");
    pollLoop().catch((e) => {
      console.error(e);
      setWatchStatus("Erreur (poll).");
    });
  }

  async function refreshStreams() {
    setStatus("Chargement…");
    const res = await api("list_streams");
    if (!res || res.ok !== true) {
      setStatus("Impossible de charger la liste.");
      if (listEl) listEl.innerHTML = `<div class="live-muted">Erreur.</div>`;
      return;
    }

    setStatus(`${res.streams.length} flux en ligne`);

    if (!listEl) return;

    if (!res.streams.length) {
      listEl.innerHTML = `<div class="live-muted">Aucun flux en ligne (heartbeat < 30s).</div>`;
      return;
    }

    listEl.innerHTML = res.streams.map(s => `
      <div class="live-stream-item">
        <div class="title">${escapeHtml(s.label || s.streamKey)}</div>
        <div class="meta">Room: <code>${escapeHtml(s.streamKey || "")}</code></div>
        <button type="button" data-room="${escapeHtml(s.streamKey || "")}">Regarder</button>
      </div>
    `).join("");
  }

  // Delegation : garantit que "Regarder" marche même après re-render
  if (listEl) {
    listEl.addEventListener("click", (e) => {
      const btn = e.target?.closest?.("button[data-room]");
      if (!btn) return;
      e.preventDefault();
      const r = btn.getAttribute("data-room");
      if (r) watch(r);
    });
  }

  if (btnRefresh) btnRefresh.addEventListener("click", (e) => { e.preventDefault(); refreshStreams(); });
  if (btnLeave) btnLeave.addEventListener("click", (e) => { e.preventDefault(); leave(); });

  // Expose debug
  window.liveReceiverWatch = watch;
  window.liveReceiverLeave = leave;

  // init
  document.addEventListener("DOMContentLoaded", () => {
    refreshStreams().catch(console.error);
  });
})();
