// assets/live/live_autosender.js
// Sender headless compatible OVH mutualisé (live_signal.php + DB polling)
// - Ne dépend plus de #liveWidget (UI optionnelle)
// - Expose window.LiveSender.startFromUserGesture()

const CFG = window.__LIVE_CONFIG__ || {
  apiUrl: "/live_api.php",
  signalUrl: "/live_signal.php",
};

const TAB_ID = (() => {
  const k = "bialadev_live_tab_id";
  let v = sessionStorage.getItem(k);
  if (!v) {
    v = (crypto?.randomUUID ? crypto.randomUUID() : ("tab-" + Math.random().toString(16).slice(2)));
    sessionStorage.setItem(k, v);
  }
  return v;
})();

const LOCK_KEY = "bialadev_live_sender_lock";
const ENABLE_KEY = "bialadev_live_sender_enabled";

function nowMs() { return Date.now(); }

function readLock() {
  try { return JSON.parse(localStorage.getItem(LOCK_KEY) || "null"); } catch { return null; }
}
function writeLock(obj) { localStorage.setItem(LOCK_KEY, JSON.stringify(obj)); }
function clearLock() { localStorage.removeItem(LOCK_KEY); }

function acquireLock() {
  const cur = readLock();
  const stale = !cur || (nowMs() - (cur.ts || 0) > 15000);
  if (stale || cur.tabId === TAB_ID) {
    writeLock({ tabId: TAB_ID, ts: nowMs() });
    return true;
  }
  return false;
}
function refreshLock() {
  const cur = readLock();
  if (cur && cur.tabId === TAB_ID) {
    writeLock({ tabId: TAB_ID, ts: nowMs() });
    return true;
  }
  return false;
}
function isLockOwner() {
  const cur = readLock();
  return !!(cur && cur.tabId === TAB_ID);
}

// UI (optionnelle) — si tu as encore le widget, il fonctionnera.
// Sinon, tout est no-op.
function $(id) { return document.getElementById(id); }
const widget  = $("liveWidget");
const elState = $("liveState");
const btnStart = $("liveStart");
const btnStop = $("liveStop");
const btnSwitch = $("liveSwitch");
const btnHide = $("liveHide");
const elInfo = $("liveInfo");

function uiShow(show) { if (widget) widget.hidden = !show; }
function uiState(text) { if (elState) elState.textContent = text; }
function uiInfo(text) { if (elInfo) elInfo.textContent = text || ""; }
function uiButtons({ canStart, canStop, canSwitch } = {}) {
  if (btnStart) btnStart.disabled = !canStart;
  if (btnStop) btnStop.disabled = !canStop;
  if (btnSwitch) btnSwitch.disabled = !canSwitch;
}

async function api(action, { method = "GET", body = null } = {}) {
  const url = new URL(CFG.apiUrl, window.location.origin);
  url.searchParams.set("action", action);
  const opts = { method, credentials: "same-origin" };
  if (body) {
    opts.headers = { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" };
    opts.body = new URLSearchParams(body);
  }
  const res = await fetch(url.toString(), opts);
  return res.json();
}

async function signalSend({ room, fromRole, direction, msgType, payload, viewerId }) {
  const res = await fetch(`${CFG.signalUrl}?action=send`, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ room, fromRole, direction, msgType, payload, viewerId }),
  });
  return res.json();
}

async function signalReceive({ room, role, sinceId, timeout = 15000 }) {
  const url = new URL(CFG.signalUrl, window.location.origin);
  url.searchParams.set("action", "receive");
  url.searchParams.set("room", room);
  url.searchParams.set("role", role);
  url.searchParams.set("since_id", String(sinceId || 0));
  url.searchParams.set("timeout", String(timeout));
  const res = await fetch(url.toString(), { credentials: "same-origin" });
  return res.json();
}

// Runtime
let status = null;
let running = false;

let pc = null;
let stream = null;
let room = null;
let label = null;
let facingMode = "environment";
let pollSinceId = 0;
let heartbeatTimer = null;
let lockTimer = null;
let currentViewerId = null;

function resetRuntime() {
  running = false;
  currentViewerId = null;
  pollSinceId = 0;

  if (heartbeatTimer) clearInterval(heartbeatTimer);
  if (lockTimer) clearInterval(lockTimer);
  heartbeatTimer = null;
  lockTimer = null;

  try { pc?.close(); } catch {}
  pc = null;

  try { stream?.getTracks()?.forEach(t => t.stop()); } catch {}
  stream = null;
}

async function startCapture() {
  return navigator.mediaDevices.getUserMedia({
    audio: true,
    video: { facingMode }
  });
}

function makePeer(roomKey) {
  const peer = new RTCPeerConnection({
    iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
  });

  peer.onicecandidate = async (ev) => {
    if (!ev.candidate) return;
    if (!currentViewerId) return;

    await signalSend({
      room: roomKey,
      fromRole: "sender",
      direction: "to_viewer",
      msgType: "ice",
      viewerId: currentViewerId,
      payload: { candidate: ev.candidate }
    });
  };

  return peer;
}

async function handleOffer(msg) {
  const offer = msg?.payload?.offer;
  const viewerId = msg?.viewerId;

  if (!offer || !viewerId) return;

  currentViewerId = viewerId;

  uiInfo(`Viewer connecté: ${String(viewerId).slice(0, 8)}…`);
  uiState("Connexion…");

  await pc.setRemoteDescription(offer);
  const answer = await pc.createAnswer();
  await pc.setLocalDescription(answer);

  await signalSend({
    room,
    fromRole: "sender",
    direction: "to_viewer",
    msgType: "answer",
    viewerId: currentViewerId,
    payload: { answer }
  });

  uiState("Live actif");
}

async function handleIce(msg) {
  const candidate = msg?.payload?.candidate;
  const viewerId = msg?.viewerId;

  if (!candidate) return;
  if (currentViewerId && viewerId && viewerId !== currentViewerId) return;

  try { await pc.addIceCandidate(candidate); } catch {}
}

async function pollLoop() {
  while (running && isLockOwner()) {
    const res = await signalReceive({ room, role: "sender", sinceId: pollSinceId, timeout: 15000 });

    if (!running) return;

    if (!res || res.ok !== true) {
      uiInfo("Signalisation indisponible.");
      await new Promise(r => setTimeout(r, 1200));
      continue;
    }

    const msgs = res.messages || [];
    pollSinceId = res.lastId || pollSinceId;

    for (const m of msgs) {
      if (m.msgType === "offer") await handleOffer(m);
      if (m.msgType === "ice") await handleIce(m);
    }
  }
}

async function enableIfNeeded() {
  status = await api("status");
  if (!status.loggedIn) throw new Error("not_logged_in");
  if (!status.canStream) throw new Error("no_stream_permission");

  room = status.streamKey;
  label = status.label;

  if (!status.liveAutostream) {
    const res = await api("enable", { method: "POST", body: { label } });
    if (!res.ok) throw new Error("enable_failed");
  }
}

async function startInternal() {
  if (running) return;

  // lock onglet
  if (!acquireLock()) {
    uiInfo("Live déjà actif dans un autre onglet.");
    uiState("Verrouillé");
    uiButtons({ canStart: true, canStop: false, canSwitch: false });
    return;
  }

  status = await api("status");
  if (!status.loggedIn || !status.canStream) {
    uiShow(false);
    clearLock();
    return;
  }

  room = status.streamKey;
  label = status.label;

  uiState("Démarrage…");
  uiInfo("");

  // capture
  stream = await startCapture();

  pc = makePeer(room);
  stream.getTracks().forEach(t => pc.addTrack(t, stream));

  running = true;
  localStorage.setItem(ENABLE_KEY, "1");

  uiShow(true);
  uiState("En attente receiver…");
  uiButtons({ canStart: false, canStop: true, canSwitch: true });

  heartbeatTimer = setInterval(() => api("heartbeat").catch(() => {}), 10000);
  lockTimer = setInterval(() => refreshLock(), 5000);

  pollLoop().catch((e) => {
    console.error(e);
    if (running) uiInfo("Erreur Live (poll).");
  });
}

async function startFromUserGesture() {
  // à appeler depuis un clic (Me découvrir)
  await enableIfNeeded();
  await startInternal();
  return running;
}

async function stop() {
  resetRuntime();
  uiState("Arrêté");
  uiInfo("");
  uiButtons({ canStart: true, canStop: false, canSwitch: false });

  localStorage.removeItem(ENABLE_KEY);

  if (isLockOwner()) clearLock();

  try { await api("disable", { method: "POST" }); } catch {}
}

async function switchCamera() {
  if (!running || !pc) return;

  facingMode = (facingMode === "user") ? "environment" : "user";
  uiInfo("Switch caméra…");

  let newStream;
  try { newStream = await startCapture(); }
  catch { uiInfo("Switch impossible (permission)."); return; }

  const videoTrack = newStream.getVideoTracks()[0] || null;
  const audioTrack = newStream.getAudioTracks()[0] || null;

  const senders = pc.getSenders();
  for (const s of senders) {
    if (s.track && s.track.kind === "video" && videoTrack) await s.replaceTrack(videoTrack);
    if (s.track && s.track.kind === "audio" && audioTrack) await s.replaceTrack(audioTrack);
  }

  try { stream?.getTracks()?.forEach(t => t.stop()); } catch {}
  stream = newStream;

  uiInfo("Caméra switched.");
}

// Boot: si l’utilisateur a déjà activé, on retente automatiquement à chaque page
async function boot() {
  try {
    status = await api("status");
    if (!status.loggedIn || !status.canStream) {
      uiShow(false);
      return;
    }

    // Affiche le widget si présent, mais n’en dépend pas
    uiShow(!!widget);
    uiButtons({ canStart: true, canStop: false, canSwitch: false });

    const shouldAuto = localStorage.getItem(ENABLE_KEY) === "1" || status.liveAutostream === true;
    if (shouldAuto) {
      // Si getUserMedia est bloqué sans geste, ça échouera silencieusement.
      startInternal().catch(() => {});
    }
  } catch (e) {
    console.error("[LiveSender] boot error", e);
  }
}

// UI bindings (si widget existe)
function bindUI() {
  if (!widget) return;

  if (btnStart) btnStart.addEventListener("click", () => startFromUserGesture().catch(console.error));
  if (btnStop) btnStop.addEventListener("click", () => stop().catch(() => {}));
  if (btnSwitch) btnSwitch.addEventListener("click", () => switchCamera().catch(() => {}));

  if (btnHide) {
    btnHide.addEventListener("click", () => {
      widget.hidden = true;
      const showBtn = document.createElement("button");
      showBtn.textContent = "Live";
      showBtn.className = "live-widget";
      showBtn.style.padding = "10px 14px";
      showBtn.style.maxWidth = "unset";
      showBtn.addEventListener("click", () => {
        showBtn.remove();
        widget.hidden = false;
      });
      document.body.appendChild(showBtn);
    });
  }

  window.addEventListener("beforeunload", () => { if (isLockOwner()) clearLock(); });
}

// Expose API
window.LiveSender = {
  startFromUserGesture,
  stop,
  switchCamera,
  isRunning: () => running,
};

// Compat ancien nom (au cas où)
window.BialadevLive = window.BialadevLive || {};
window.BialadevLive.enableAndStart = startFromUserGesture;
window.BialadevLive.start = startInternal;
window.BialadevLive.stop = stop;
window.BialadevLive.switchCamera = switchCamera;

document.addEventListener("DOMContentLoaded", () => {
  bindUI();
  boot();
});
