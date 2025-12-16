<?php
// ar_flappy.php
session_start();
// Désactive le sender sur cette page
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AR Flappy Nose</title>

  <script>window.__LIVE_DISABLE_AUTOSENDER__ = true;</script>

  <!-- MediaPipe Tasks Vision (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision/vision_bundle.js" crossorigin="anonymous"></script>

  <style>
    html,body{margin:0;height:100%;background:#0b0b10;color:#fff;font-family:system-ui}
    #wrap{position:relative;height:100%}
    #v{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
    #c{position:absolute;inset:0;width:100%;height:100%}
    #ui{position:absolute;left:12px;top:12px;right:12px;display:flex;gap:10px;align-items:center}
    #ui button{padding:10px 12px;border:0;border-radius:12px}
    #ui .pill{margin-left:auto;background:rgba(0,0,0,.45);padding:8px 10px;border-radius:999px}
  </style>
</head>
<body>
  <div id="wrap">
    <video id="v" playsinline autoplay muted></video>
    <canvas id="c"></canvas>

    <div id="ui">
      <button id="start">Démarrer</button>
      <button id="stop">Stop</button>
      <div class="pill" id="info">Prêt.</div>
    </div>
  </div>

  <script src="/assets/ar/ar_flappy.js"></script>
</body>
</html>
