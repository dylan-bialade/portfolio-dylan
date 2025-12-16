// assets/live/live_receiver.js
// - UI inchangée (utilise tes IDs existants)
// - "Regarder" fiable (event delegation + preventDefault)
// - Son via <audio> caché + audio unlock au clic "Regarder"

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

let pc = null;
let room = null;
let viewerId = null;
let sinceId = 0;
let running = false;

// Audio unlock
let audioCtx = null;
let audioEl = null;

function setStatus(text) { if (statusEl) statusEl.textContent = text || ""; }
function setWatchStatus(text) { if (watchStatusEl) watchStatusEl.textContent = text || ""; }

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

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));
}

function ensureAudioElements() {
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
  // Doit être appelé suite à un geste utilisateur (clic "Regarder")
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state !== "running") await audioCtx.resume();

    // micro "tick" (certains mobiles en ont besoin)
    const buffer = audioCtx.createBuffer(1, 1, 22050);
    const src = audioCtx.createBufferSource();
    src.buffer = buffer;
    src.connect(audioCtx.destination);
    src.start(0);
  } catch {}

  try {
    ensureAudioElements();
    await audioEl.play();
  } catch {}
}

function attachAudioStream(stream) {
  try {
    ensureAudioElements();
    const aTracks = stream.getAudioTracks ? stream.getAudioTracks() : [];
    if (!aTracks || aTracks.length === 0) {
      setWatchStatus("Lecture en cours (AUDIO: 0 piste reçue).");
      return;
    }
    const audioOnly = new MediaStream(aTracks);
    audioEl.srcObject = audioOnly;
    audioEl.muted = false;
    audioEl.volume = 1;
    audioEl.play().catch(() => {});
  } catch (e) {
    console.warn("[Receiver] attachAudioStream error", e);
  }
}

function detachAudio() {
  try { if (audioEl) audioEl.srcObject = null; } catch {}
}

async function refreshStreams() {
  setStatus("Chargement...");
  const res = await apiGet("list_streams");

  if (!res || res.ok !== true) {
    setStatus("Impossible de charger la liste.");
    if (listEl) listEl.innerHTML = `<div class="live-muted">Erreur.</div>`;
    return;
  }

  const streams = res.streams || [];
  setStatus(`${streams.length} flux en ligne`);

  if (!listEl) return;

  if (streams.length === 0) {
    listEl.innerHTML = `<div class="live-muted">Aucun flux en ligne.</div>`;
    return;
  }

  // IMPORTANT: type="button" évite les submits si ton UI est dans un <form>
  listEl.innerHTML = streams.map(s => `
    <div class="live-stream-item">
      <div class="title">${escapeHtml(s.label || s.streamKey)}</div>
      <div class="meta">Room: <code>${escapeHtml(s.streamKey || "")}</code></div>
      <button type="button" data-room="${escapeHtml(s.streamKey || "")}">Regarder</button>
    </div>
  `).join("");
}

function makePeer() {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.ontrack = (ev) => {
    const stream = ev.streams[0];
    if (!stream) return;

    // Debug (laisse, ça aide)
    try {
      const a = stream.getAudioTracks().length;
      const v = stream.getVideoTracks().length;
      console.log("[Receiver] tracks audio:", a, "video:", v);
    } catch {}

    if (videoEl) {
      videoEl.srcObject = stream;
      videoEl.muted = false;
      videoEl.volume = 1;
      videoEl.play().catch(() => {});
    }

    attachAudioStream(stream);
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

async function watch(roomKey) {
  await leave();

  // GESTE UTILISATEUR => unlock audio ici
  await unlockAudio();

  room = String(roomKey);
  viewerId = "v_" + Math.random().toString(16).slice(2);
  sinceId = 0;
  running = true;

  setWatchStatus(`Connexion au flux ${room}...`);
  if (btnLeave) btnLeave.disabled = false;

  pc = makePeer();

  // Force réception audio+vidéo (plus fiable selon navigateurs)
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
    setWatchStatus(`Erreur offer: ${sendRes?.error || "unknown"}`);
    running = false;
    return;
  }

  setWatchStatus("Offer envoyé, attente answer...");
  poll();
}

async function leave() {
  running = false;
  room = null;
  viewerId = null;
  sinceId = 0;

  try { pc?.close(); } catch {}
  pc = null;

  if (videoEl) {
    videoEl.srcObject = null;
    videoEl.muted = false;
    videoEl.volume = 1;
  }

  detachAudio();

  if (btnLeave) btnLeave.disabled = true;
  setWatchStatus("Déconnecté.");
}

// --- Event delegation : "Regarder" marche même si la liste est re-render ---
if (listEl) {
  listEl.addEventListener("click", (e) => {
    const btn = e.target && e.target.closest ? e.target.closest("button[data-room]") : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const r = btn.getAttribute("data-room");
    if (r) watch(r);
  });
}

if (btnRefresh) btnRefresh.addEventListener("click", (e) => { e.preventDefault(); refreshStreams(); });
if (btnLeave) btnLeave.addEventListener("click", (e) => { e.preventDefault(); leave(); });

refreshStreams();
setInterval(() => refreshStreams().catch(() => {}), 5000);
