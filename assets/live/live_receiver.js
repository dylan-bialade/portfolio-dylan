// assets/live/live_receiver.js
// Receiver — UI inchangée, audio via WebAudio (plus fiable que <video> seul)

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

// WebAudio
let audioCtx = null;
let audioSourceNode = null;

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

// Débloque l’audio (doit être appelé suite à un geste utilisateur, ici le clic "Regarder")
async function unlockAudio() {
  try {
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state !== "running") {
      await audioCtx.resume();
    }
  } catch (e) {
    console.warn("[Receiver] unlockAudio failed:", e);
  }
}

function detachWebAudio() {
  try { audioSourceNode?.disconnect(); } catch {}
  audioSourceNode = null;
}

function attachWebAudio(stream) {
  if (!audioCtx) return; // pas débloqué => rien
  detachWebAudio();

  try {
    // Utilise l’audio du stream (si présent) via WebAudio
    audioSourceNode = audioCtx.createMediaStreamSource(stream);
    audioSourceNode.connect(audioCtx.destination);
  } catch (e) {
    console.warn("[Receiver] attachWebAudio failed:", e);
  }
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

function makePeer() {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.ontrack = (ev) => {
    const stream = ev.streams[0];
    if (!stream || !videoEl) return;

    // Debug utile (tu peux le laisser)
    try {
      console.log("[Receiver] audioTracks:", stream.getAudioTracks().length, "videoTracks:", stream.getVideoTracks().length);
    } catch {}

    // Vidéo (UI inchangée)
    videoEl.srcObject = stream;
    videoEl.muted = false;
    videoEl.volume = 1;
    videoEl.play().catch(() => { /* autoplay peut bloquer la partie <video> */ });

    // Audio via WebAudio (si unlockAudio() a été fait au clic)
    if (stream.getAudioTracks && stream.getAudioTracks().length > 0) {
      attachWebAudio(stream);
    }
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

    // IMPORTANT: geste utilisateur => débloque WebAudio ici
    await unlockAudio();

    room = String(r);
    viewerId = "v_" + Math.random().toString(16).slice(2);
    sinceId = 0;
    running = true;

    setWatchStatus(`Connexion au flux ${room}...`);
    if (btnLeave) btnLeave.disabled = false;

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

  detachWebAudio();

  if (videoEl) {
    videoEl.srcObject = null;
    videoEl.muted = false;
    videoEl.volume = 1;
  }

  if (btnLeave) btnLeave.disabled = true;
  setWatchStatus("Déconnecté.");
}

if (btnRefresh) btnRefresh.addEventListener("click", refreshStreams);
if (btnLeave) btnLeave.addEventListener("click", leave);

refreshStreams();
