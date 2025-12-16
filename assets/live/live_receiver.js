// assets/live/live_receiver.js

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
const btnUnmute = $("btnUnmute");

let pc = null;
let room = null;
let viewerId = null;
let sinceId = 0;
let running = false;

function setStatus(text) { if (statusEl) statusEl.textContent = text; }
function setWatchStatus(text) { if (watchStatusEl) watchStatusEl.textContent = text; }

async function apiGet(action) {
  const r = await fetch(`${CFG.apiUrl}?action=${encodeURIComponent(action)}`, { credentials: "same-origin" });
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

async function refreshStreams() {
  setStatus("Chargement des flux...");
  const res = await apiGet("list_streams");
  if (!res.ok) {
    setStatus(res.error ? `Erreur: ${res.error}` : "Erreur list_streams");
    return;
  }
  renderList(res.streams || []);
  setStatus(`Flux en ligne: ${(res.streams || []).length}`);
}

function renderList(streams) {
  if (!listEl) return;
  if (!streams.length) {
    listEl.innerHTML = `<div class="live-stream-item">Aucun flux en ligne.</div>`;
    return;
  }

  listEl.innerHTML = streams.map(s => `
    <div class="live-stream-item">
      <div class="title">${escapeHtml(s.label || s.streamKey)}</div>
      <div class="meta">Room: <code>${escapeHtml(s.streamKey || "")}</code></div>
      <button data-room="${escapeHtml(s.streamKey || "")}">Regarder</button>
    </div>
  `).join("");

  listEl.querySelectorAll("button[data-room]").forEach(btn => {
    btn.addEventListener("click", () => {
      const r = btn.getAttribute("data-room");
      if (r) watch(r);
    });
  });
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[c]));
}

function enableUnmuteIfAudioPresent(stream) {
  if (!btnUnmute) return;

  const hasAudio = stream && stream.getAudioTracks && stream.getAudioTracks().length > 0;
  btnUnmute.disabled = !hasAudio;
  btnUnmute.style.display = hasAudio ? "inline-block" : "none";
}

async function tryUnmuteAndPlay() {
  if (!videoEl) return;

  videoEl.muted = false;
  videoEl.volume = 1;

  try {
    await videoEl.play();
    if (btnUnmute) {
      btnUnmute.disabled = true;
      btnUnmute.style.display = "none";
    }
  } catch {
    // Autoplay policy: le bouton reste pour un geste utilisateur explicite
  }
}

function makePeer() {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.ontrack = async (ev) => {
    const stream = ev.streams[0];
    if (!stream || !videoEl) return;

    videoEl.srcObject = stream;

    // Démarre la vidéo de façon permissive (muted)
    videoEl.muted = true;
    videoEl.volume = 1;
    videoEl.play().catch(() => {});

    // Si audio présent => bouton disponible
    enableUnmuteIfAudioPresent(stream);

    // Tentative automatique d’unmute (peut échouer, d’où le bouton)
    await tryUnmuteAndPlay();
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

  if (res.ok && Array.isArray(res.messages)) {
    for (const m of res.messages) {
      sinceId = Math.max(sinceId, m.id || 0);
      const t = m.msgType || m.msg_type;

      if (t === "answer") {
        const answer = m?.payload?.answer;
        if (answer) {
          await pc.setRemoteDescription(answer);
          setWatchStatus("Lecture en cours.");
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

async function watch(r) {
  try {
    await leave();

    room = String(r);
    viewerId = "v_" + Math.random().toString(16).slice(2);
    sinceId = 0;
    running = true;

    setWatchStatus(`Connexion au flux ${room}...`);

    if (btnUnmute) {
      btnUnmute.style.display = "inline-block";
      btnUnmute.disabled = true;
    }

    pc = makePeer();

    const offer = await pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
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

    if (!sendRes.ok) {
      setWatchStatus(`Erreur offer: ${sendRes.error || "unknown"}`);
      running = false;
      return;
    }

    setWatchStatus("Offer envoyé, attente answer...");
    if (btnLeave) btnLeave.disabled = false;

    poll();
  } catch (e) {
    console.error(e);
    setWatchStatus("Erreur lors de la lecture (voir console).");
  }
}

async function leave() {
  running = false;
  room = null;
  viewerId = null;
  sinceId = 0;

  if (pc) {
    try { pc.close(); } catch {}
    pc = null;
  }

  if (videoEl) {
    videoEl.srcObject = null;
    videoEl.muted = true;
  }

  if (btnUnmute) {
    btnUnmute.disabled = true;
    btnUnmute.style.display = "none";
  }

  if (btnLeave) btnLeave.disabled = true;
  setWatchStatus("Déconnecté.");
}

if (btnUnmute) {
  btnUnmute.addEventListener("click", () => {
    // Geste utilisateur explicite => débloque l’audio
    tryUnmuteAndPlay();
  });
}

if (btnRefresh) btnRefresh.addEventListener("click", refreshStreams);
if (btnLeave) btnLeave.addEventListener("click", leave);

refreshStreams();
