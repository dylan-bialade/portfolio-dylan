// /assets/live/live_receiver_fix.js
// Receiver (UI inchangée) + FIX parsing + FIX "Regarder" + audio via <audio> caché

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

  // --- Audio (sans UI visible) ---
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
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state !== "running") await audioCtx.resume();

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
    } catch {}
  }

  function attachAudioFromStream(stream) {
    ensureAudioEl();
    const aTracks = stream?.getAudioTracks ? stream.getAudioTracks() : [];
    console.log("[LiveReceiver] audio tracks:", aTracks.length);

    if (!aTracks.length) return;

    audioEl.srcObject = new MediaStream(aTracks);
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

  // IMPORTANT: FIX SYNTAXE (ton bug était ici)
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

        // Autoplay plus stable : vidéo mutée, audio séparé
        videoEl.muted = true;
        videoEl.playsInline = true;
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
        await new Promise(r => setTimeout(r, 800));
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
    await unlockAudioOnGesture();

    room = roomKey;
    viewerId = (crypto?.randomUUID ? crypto.randomUUID() : ("v-" + Math.random().toString(16).slice(2)));
    sinceId = 0;

    ensureAudioEl();

    pc = makePeer();
    running = true;

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
    pollLoop().catch(console.error);
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
      listEl.innerHTML = `<div class="live-muted">Aucun flux en ligne.</div>`;
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

  // Delegation : "Regarder" marche toujours
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

  // Debug
  window.liveReceiverWatch = watch;

  document.addEventListener("DOMContentLoaded", () => {
    refreshStreams().catch(console.error);
  });
})();
