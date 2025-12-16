// assets/live/live_receiver.js
// Robust click handling + audio unlock + debug

(function () {
  const CFG = window.__LIVE_CONFIG__ || { apiUrl: "/live_api.php", signalUrl: "/live_signal.php" };

  const statusEl = document.getElementById("streamsStatus");
  const watchStatusEl = document.getElementById("watchStatus");
  const btnRefresh = document.getElementById("btnRefreshStreams");
  const btnLeave = document.getElementById("btnLeave");
  const videoEl = document.getElementById("receiverVideo");
  const listEl = document.getElementById("streamsList"); // optionnel

  function setStatus(t) { if (statusEl) statusEl.textContent = t || ""; }
  function setWatchStatus(t) { if (watchStatusEl) watchStatusEl.textContent = t || ""; }

  console.log("[LiveReceiver] JS chargé OK", { apiUrl: CFG.apiUrl, signalUrl: CFG.signalUrl });

  let pc = null;
  let room = null;
  let viewerId = null;
  let sinceId = 0;
  let running = false;

  // Audio unlock (WebAudio + <audio> caché)
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

  async function unlockAudio() {
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state !== "running") await audioCtx.resume();

      const buffer = audioCtx.createBuffer(1, 1, 22050);
      const src = audioCtx.createBufferSource();
      src.buffer = buffer;
      src.connect(audioCtx.destination);
      src.start(0);
    } catch (e) {
      console.warn("[LiveReceiver] WebAudio unlock failed", e);
    }

    try {
      ensureAudioEl();
      await audioEl.play();
    } catch (e) {
      // parfois bloqué, mais le clic “Regarder” suffit généralement à débloquer
    }
  }

  function attachAudio(stream) {
    try {
      const aTracks = stream.getAudioTracks ? stream.getAudioTracks() : [];
      console.log("[LiveReceiver] tracks => audio:", aTracks.length, "video:", stream.getVideoTracks?.().length || 0);

      if (!aTracks || aTracks.length === 0) {
        setWatchStatus("Lecture en cours (AUDIO: 0 piste reçue).");
        return;
      }

      ensureAudioEl();
      audioEl.srcObject = new MediaStream(aTracks);
      audioEl.muted = false;
      audioEl.volume = 1;
      audioEl.play().catch(() => {});
    } catch (e) {
      console.warn("[LiveReceiver] attachAudio failed", e);
    }
  }

  async function apiGet(action) {
    const url = CFG.apiUrl + "?action=" + encodeURIComponent(action);
    const r = await fetch(url, { credentials: "same-origin" });
    return r.json();
  }

  async function signalPost(body) {
    const r = await fetch(CFG.signalUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(body),
    });
    return r.json();
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

      attachAudio(stream);
    };

    peer.onicecandidate = async (ev) => {
      if (!ev.candidate || !room) return;
      await signalPost({
        action: "send",
        room,
        fromRole: "viewer",
        direction: "viewer_to_sender",
        msgType: "ice",
        payload: { candidate: ev.candidate },
        viewerId
      });
    };

    return peer;
  }

  async function poll() {
    if (!running || !room) return;

    const res = await signalPost({
      action: "receive",
      role: "viewer",
      room,
      viewerId,
      sinceId,
      timeout: 15000
    });

    if (res && res.ok === true && Array.isArray(res.messages)) {
      for (const m of res.messages) {
        sinceId = Math.max(sinceId, m.id || 0);
        const t = m.msgType || m.msg_type;

        if (t === "answer") {
          const answer = m?.payload?.answer;
          if (answer) {
            await pc.setRemoteDescription(answer);
            setWatchStatus("Lecture en cours.");
            try { audioEl?.play?.(); } catch {}
          }
        } else if (t === "ice") {
          const cand = m?.payload?.candidate;
          if (cand) {
            try { await pc.addIceCandidate(cand); } catch {}
          }
        }
      }
    }

    setTimeout(poll, 800);
  }

  async function leave() {
    running = false;
    room = null;
    viewerId = null;
    sinceId = 0;

    try { pc?.close(); } catch {}
    pc = null;

    if (videoEl) videoEl.srcObject = null;
    try { if (audioEl) audioEl.srcObject = null; } catch {}

    if (btnLeave) btnLeave.disabled = true;
    setWatchStatus("Déconnecté.");
  }

  async function watch(roomKey) {
    console.log("[LiveReceiver] watch()", roomKey);
    await leave();

    // clic utilisateur => unlock audio ici
    await unlockAudio();

    room = String(roomKey);
    viewerId = (crypto && crypto.randomUUID) ? crypto.randomUUID() : ("v_" + Math.random().toString(16).slice(2));
    sinceId = 0;
    running = true;

    if (btnLeave) btnLeave.disabled = false;
    setWatchStatus("Connexion...");

    pc = makePeer();

    // force réception audio/vidéo (plus stable)
    try {
      pc.addTransceiver("audio", { direction: "recvonly" });
      pc.addTransceiver("video", { direction: "recvonly" });
    } catch {}

    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    const sendRes = await signalPost({
      action: "send",
      room,
      fromRole: "viewer",
      direction: "viewer_to_sender",
      msgType: "offer",
      payload: { offer },
      viewerId
    });

    if (!sendRes || sendRes.ok !== true) {
      setWatchStatus("Erreur: impossible d'envoyer l'offre.");
      running = false;
      return;
    }

    setWatchStatus("Offer envoyée, attente answer...");
    poll();
  }

  // Expose pour test manuel dans la console
  window.liveReceiverWatch = watch;
  window.liveReceiverLeave = leave;

  // Refresh list (si ton UI repose dessus)
  async function refreshStreams() {
    const res = await apiGet("list_streams");
    if (!res || res.ok !== true) {
      setStatus("Erreur liste");
      return;
    }

    const streams = res.streams || [];
    setStatus(`${streams.length} flux en ligne`);

    if (!listEl) return; // si ton UI est server-rendered, on ne touche pas

    if (!streams.length) {
      listEl.innerHTML = `<div class="live-muted">Aucun flux en ligne.</div>`;
      return;
    }

    listEl.innerHTML = streams.map(s => `
      <div class="live-stream-item">
        <div class="title">${String(s.label || s.streamKey)}</div>
        <div class="meta">Room: <code>${String(s.streamKey || "")}</code></div>
        <button type="button" data-room="${String(s.streamKey || "")}">Regarder</button>
      </div>
    `).join("");
  }

  // Click handler global (ne dépend PAS de #streamsList)
  document.addEventListener("click", (e) => {
    const btn = e.target && e.target.closest ? e.target.closest("button") : null;
    if (!btn) return;

    // 1) bouton avec data-room
    const r1 = btn.getAttribute("data-room");
    if (r1) {
      e.preventDefault();
      e.stopPropagation();
      watch(r1);
      return;
    }

    // 2) bouton dont le texte est "Regarder" (fallback)
    const label = (btn.textContent || "").trim().toLowerCase();
    if (label === "regarder") {
      const card = btn.closest(".live-stream-item") || btn.parentElement;
      const code = card ? card.querySelector("code") : null;
      const r2 = code ? (code.textContent || "").trim() : "";
      if (r2) {
        e.preventDefault();
        e.stopPropagation();
        watch(r2);
      }
    }
  }, true);

  if (btnRefresh) btnRefresh.addEventListener("click", (e) => { e.preventDefault(); refreshStreams(); });
  if (btnLeave) btnLeave.addEventListener("click", (e) => { e.preventDefault(); leave(); });

  // init
  refreshStreams().catch(() => {});
})();
