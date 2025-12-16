// assets/live/live_receiver.js
// Receiver (page réservée aux comptes autorisés)

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

function makePeer() {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.ontrack = (ev) => {
    if (videoEl) videoEl.srcObject = ev.streams[0];
  };

  peer.onicecandidate = async (ev) => {
    if (!ev.candidate || !room) return;
    await signalPost({
      action: "send",
      room,
      fromRole: "viewer",
      direction: "viewer_to_sender",
      msgType: "ice",
      payload: ev.candidate,
      viewerId
    });
  };

  return peer;
}

async function poll() {
  if (!running || !room) return;

  const res = await signalPost({ action: "poll", room, viewerId, sinceId });
  if (res.ok && Array.isArray(res.messages)) {
    for (const m of res.messages) {
      sinceId = Math.max(sinceId, m.id || 0);

      if (m.msg_type === "answer") {
        await pc.setRemoteDescription(m.payload);
        setWatchStatus("Lecture en cours.");
      } else if (m.msg_type === "ice") {
        try { await pc.addIceCandidate(m.payload); } catch {}
      }
    }
  }

  setTimeout(poll, 800);
}

async function watch(r) {
  try {
    await leave();

    room = r;
    viewerId = "v_" + Math.random().toString(16).slice(2);
    sinceId = 0;
    running = true;

    setWatchStatus(`Connexion au flux ${room}...`);

    pc = makePeer();

    // Viewer = offerer
    const offer = await pc.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true });
    await pc.setLocalDescription(offer);

    await signalPost({
      action: "send",
      room,
      fromRole: "viewer",
      direction: "viewer_to_sender",
      msgType: "offer",
      payload: offer,
      viewerId
    });

    setWatchStatus("Offer envoyé, attente answer...");
    btnLeave && (btnLeave.disabled = false);

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
  if (videoEl) videoEl.srcObject = null;
  btnLeave && (btnLeave.disabled = true);
  setWatchStatus("Déconnecté.");
}

btnRefresh && btnRefresh.addEventListener("click", refreshStreams);
btnLeave && btnLeave.addEventListener("click", leave);

refreshStreams();
