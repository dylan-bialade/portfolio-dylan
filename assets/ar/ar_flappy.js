// assets/ar/ar_flappy.js
const v = document.getElementById("v");
const c = document.getElementById("c");
const ctx = c.getContext("2d");
const info = document.getElementById("info");
const btnStart = document.getElementById("start");
const btnStop = document.getElementById("stop");

let faceLandmarker = null;
let running = false;
let stream = null;
let lastVideoTime = -1;

let wakeLock = null;
async function requestWakeLock() {
  try {
    if (!("wakeLock" in navigator)) return;
    wakeLock = await navigator.wakeLock.request("screen");
  } catch {}
}
document.addEventListener("visibilitychange", async () => {
  if (document.visibilityState === "visible" && running) requestWakeLock();
});

// Mini game state (Flappy-like)
let birdY = 200;
let birdV = 0;
let score = 0;
let pipes = [];
let lastNoseY = null;

function resize() {
  c.width = window.innerWidth;
  c.height = window.innerHeight;
}
window.addEventListener("resize", resize);
resize();

function resetGame() {
  birdY = c.height * 0.5;
  birdV = 0;
  score = 0;
  pipes = [];
  lastNoseY = null;
  for (let i = 0; i < 3; i++) spawnPipe(i * 260 + c.width + 200);
}

function spawnPipe(x) {
  const gap = Math.max(160, c.height * 0.22);
  const center = (Math.random() * 0.5 + 0.25) * c.height;
  pipes.push({ x, center, gap, passed: false });
}

async function initFace() {
  // vision_bundle expose FilesetResolver + FaceLandmarker
  const vision = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm"
  ); // :contentReference[oaicite:3]{index=3}

  faceLandmarker = await FaceLandmarker.createFromOptions(vision, {
    baseOptions: {
      modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task"
    },
    runningMode: "VIDEO",
    numFaces: 1
  });
}

async function startCamera() {
  stream = await navigator.mediaDevices.getUserMedia({
    audio: false,
    video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 720 } }
  });
  v.srcObject = stream;
  await v.play();
}

function flap() {
  birdV = -9;
}

function updatePhysics() {
  birdV += 0.6;       // gravity
  birdY += birdV;

  // clamp
  if (birdY < 10) { birdY = 10; birdV = 0; }
  if (birdY > c.height - 10) { birdY = c.height - 10; birdV = 0; }
}

function updatePipes() {
  for (const p of pipes) {
    p.x -= 4.2;
    if (!p.passed && p.x < c.width * 0.35) {
      p.passed = true;
      score++;
    }
  }
  // recycle
  if (pipes.length && pipes[0].x < -80) {
    pipes.shift();
    spawnPipe(c.width + 240);
  }
}

function collides() {
  const bx = c.width * 0.35;
  const r = 18;

  for (const p of pipes) {
    const w = 70;
    const top = p.center - p.gap / 2;
    const bot = p.center + p.gap / 2;

    const inX = bx + r > p.x && bx - r < p.x + w;
    if (!inX) continue;

    if (birdY - r < top || birdY + r > bot) return true;
  }
  return false;
}

function draw() {
  ctx.clearRect(0, 0, c.width, c.height);

  // pipes
  for (const p of pipes) {
    const w = 70;
    const top = p.center - p.gap / 2;
    const bot = p.center + p.gap / 2;

    ctx.fillRect(p.x, 0, w, top);
    ctx.fillRect(p.x, bot, w, c.height - bot);
  }

  // bird
  const bx = c.width * 0.35;
  ctx.beginPath();
  ctx.arc(bx, birdY, 18, 0, Math.PI * 2);
  ctx.fill();

  // score
  ctx.font = "20px system-ui";
  ctx.fillText(`Score: ${score}`, 16, 74);
}

function useNoseToFlap(noseYNorm) {
  if (lastNoseY === null) {
    lastNoseY = noseYNorm;
    return;
  }
  const dy = lastNoseY - noseYNorm; // nez qui “remonte” => dy positif
  if (dy > 0.03) flap();
  lastNoseY = noseYNorm;
}

function loop() {
  if (!running) return;

  // detection (throttle by video time)
  if (v.currentTime !== lastVideoTime) {
    const ts = performance.now();
    const res = faceLandmarker.detectForVideo(v, ts);
    lastVideoTime = v.currentTime;

    const lm = res?.faceLandmarks?.[0];
    // landmark 1 ~ nez (pour débuter). Ajustable ensuite.
    if (lm && lm[1]) {
      useNoseToFlap(lm[1].y);
      info.textContent = "Contrôle nez actif.";
    } else {
      info.textContent = "Place ton visage dans le cadre.";
    }
  }

  updatePhysics();
  updatePipes();
  draw();

  if (collides()) {
    info.textContent = `Perdu. Score ${score}.`;
    resetGame();
  }

  requestAnimationFrame(loop);
}

btnStart.addEventListener("click", async () => {
  if (running) return;
  info.textContent = "Initialisation...";
  await requestWakeLock();

  if (!faceLandmarker) await initFace();
  await startCamera();
  resetGame();

  running = true;
  info.textContent = "Go.";
  loop();
});

btnStop.addEventListener("click", () => {
  running = false;
  try { stream?.getTracks()?.forEach(t => t.stop()); } catch {}
  stream = null;
  v.srcObject = null;
  try { wakeLock?.release(); } catch {}
  wakeLock = null;
  info.textContent = "Arrêté.";
});
