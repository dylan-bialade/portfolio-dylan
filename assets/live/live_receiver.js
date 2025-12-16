// assets/live/live_receiver.js
// Receiver (UI inchangée) + FIX SON (Audio unlock + audio element caché)

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

// --- Audio unlock (fiable) ---
let audioCtx = null;
let audioEl = null;

function setStatus(text) { if (statusEl) statusEl.textContent = text || ""; }
function setWatchStatus(text) { if (watchStatusEl) watchStatusEl.textContent = text || ""; }

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

function renderStreams(streams) {
  if (!listEl) return;

  if (!streams || !streams.length) {
    listEl.innerHTML = `<div class="live-muted">Aucun flux en ligne (heartbeat &lt; 30s).</div>`;
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
    btn.addEventListener("click", () => watch(btn.dataset.room));
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

async function unlockAudioOnUserGesture() {
  // 1) WebAudio unlock (très fiable)
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state !== "running") await audioCtx.resume();

    // petit “beep” silencieux (buffer vide) pour finaliser l’unlock sur certains mobiles
    const buffer = audioCtx.createBuffer(1, 1, 22050);
    const src = audioCtx.createBufferSource();
    src.buffer = buffer;
    src.connect(audioCtx.destination);
    src.start(0);
  } catch {}

  // 2) audio tag unlock
  try {
    ensureAudioElements();
    await audioEl.play();
  } catch {}
}

function attachAudioStream(stream) {
  try {
    ensureAudioElements();
    const tracks = stream.getAudioTracks ? stream.getAudioTracks() : [];
    if (!tracks || tracks.length === 0) {
      // debug simple dans ton UI existante
      setWatchStatus("Lecture en cours (AUDIO: aucune piste reçue).");
      return;
    }
    const audioOnly = new MediaStream(tracks);
    audioEl.srcObject = audioOnly;
    audioEl.muted = false;
    audioEl.volume = 1;
    audioEl.play().catch(() => {});
  } catch (e) {
    console.warn("[Receiver] attachAudioStream error", e);
  }
}

function makePeer() {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.ontrack = (ev) => {
    const stream = ev.streams[0];
    if (!stream) return;

    // Vidéo (UI identique)
    if (videoEl) {
      videoEl.srcObject = stream;
      videoEl.muted = false;
      videoEl.volume = 1;
      videoEl.play().catch(() => {});
    }

    // Audio (sortie via <audio> caché)
    attachAudioStream(stream);
  };

  peer.onicecandidate = async (ev) => {
    if (!ev.candidate) return;
    await signalSend({ room, msgType: "ice", payload: { candidate: ev.candidate } });
  };

  return peer;
}

async function watch(roomKey) {
  await leave();

  // IMPORTANT: le clic “Regarder” est un geste utilisateur -> unlock audio ici
  await unlockAudioOnUserGesture();

  room = roomKey;
  viewerId = (crypto?.randomUUID ? crypto.randomUUID() : ("v-" + Math.random().toString(16).slice(2)));
  sinceId = 0;

  pc = makePeer();
  running = true;

  // IMPORTANT: transceivers pour forcer réception audio/vidéo (fiable sur versions récentes)
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

  pollLoop().catch((e) => {
    console.error(e);
    setWatchStatus("Erreur (poll).");
  });
}

async function handleAnswer(msg) {
  const answer = msg?.payload?.answer;
  if (!answer) return;
  await pc.setRemoteDescription(answer);

  // retente audio play après answer
  try { audioEl?.play?.(); } catch {}

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

  // stop audio output
  try { if (audioEl) audioEl.srcObject = null; } catch {}

  if (btnLeave) btnLeave.disabled = true;
  setWatchStatus("");
}

async function refreshStreams() {
  setStatus("Chargement…");
  const res = await api("list_streams");
  if (!res || res.ok !== true) {
    setStatus("Impossible de charger la liste.");
    renderStreams([]);
    return;
  }
  setStatus(`${res.streams.length} flux en ligne`);
  renderStreams(res.streams);
}

document.addEventListener("DOMContentLoaded", () => {
  if (btnRefresh) btnRefresh.addEventListener("click", refreshStreams);
  if (btnLeave) btnLeave.addEventListener("click", leave);

  refreshStreams().catch((e) => {
    console.error(e);
    setStatus("Erreur.");
  });

  // refresh léger toutes les 5 secondes
  setInterval(() => refreshStreams().catch(() => {}), 5000);
});
