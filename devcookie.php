<?php
// devcookie.php
session_start();

$currentPage     = '';
$pageTitle       = 'Cookie Dev – Mini-jeu clicker Bialadev Studio';
$pageDescription = "Mini-jeu type Cookie Clicker dans l’univers du développement web : gagnez des lignes de code et débloquez langages, frameworks et outils.";
$pageRobots      = 'noindex,follow';

$isLoggedIn = isset($_SESSION['user_id']);
$pseudo     = $isLoggedIn ? ($_SESSION['pseudo'] ?? 'Joueur') : null;

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Cookie Dev – Clicker du développeur</h1>
      <p class="section-intro">
        Gagnez des <strong>lignes de code</strong> en cliquant, débloquez des
        <strong>langages</strong>, <strong>frameworks</strong> et <strong>outils</strong> pour
        coder toujours plus vite. Mini-jeu purement démonstratif pour montrer un peu de
        <strong>front</strong> + <strong>back-end</strong> (comptes utilisateurs, sauvegarde en base).
      </p>

      <?php if ($isLoggedIn): ?>
        <div class="alert alert-info">
          Connecté en tant que <strong><?php echo htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8'); ?></strong>.
          Votre progression sera sauvegardée automatiquement.
          (<a href="/auth/logout.php">Se déconnecter</a>)
        </div>
      <?php else: ?>
        <div class="alert alert-warning">
          Vous jouez en invité. Pour sauvegarder votre progression :
          <a href="/auth/register.php">créez un compte</a> ou
          <a href="/auth/login.php?redirect=/devcookie.php">connectez-vous</a>.
        </div>
      <?php endif; ?>

      <div class="grid demos-grid">
        <!-- Zone principale du jeu -->
        <article class="card card-demo">
          <h2>Poste de travail</h2>
          <p>
            Cliquez sur le bouton <strong>Coder</strong> pour produire des lignes de code.
            Les améliorations augmentent la quantité gagnée par clic ou automatiquement.
          </p>

          <div class="demo-block">
            <p>
              Lignes de code : <strong><span id="devcookie-points">0</span></strong><br>
              Par clic : <span id="devcookie-per-click">1</span><br>
              Par seconde : <span id="devcookie-per-second">0</span>
            </p>

            <button id="devcookie-click-btn" class="btn btn-primary" style="margin-top:0.5rem;">
              Coder 💻
            </button>

            <p class="demo-message" id="devcookie-message" style="margin-top:0.75rem;">
              Commencez à coder, puis investissez dans des langages, frameworks et outils !
            </p>
          </div>
        </article>

        <!-- Zone des améliorations -->
        <article class="card card-demo">
          <h2>Améliorations</h2>
          <p>
            Investissez vos lignes de code dans des compétences. Chaque niveau augmente votre
            productivité.
          </p>

          <div class="demo-block" id="devcookie-upgrades">
            <!-- Les cartes d'upgrades seront générées en JS -->
          </div>
        </article>

        <!-- Infos / sauvegarde -->
        <article class="card card-demo">
          <h2>Progression & sauvegarde</h2>
          <p>
            L’état du jeu (lignes de code, améliorations, vitesse) est conservé côté navigateur.
            Si vous êtes connecté, il est également <strong>sauvegardé en base</strong>.
          </p>
          <div class="demo-block">
            <p id="devcookie-save-status">
              Sauvegarde <em>non initialisée</em>.
            </p>
            <button id="devcookie-save-btn" class="btn btn-outline">
              Sauvegarder maintenant
            </button>
            <p style="margin-top:0.75rem; font-size:0.9rem;">
              La sauvegarde automatique se fait régulièrement pendant que vous jouez,
              si vous êtes connecté.
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// ==========================
// Config & état du jeu
// ==========================

const DEVCOOKIE_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

const UPGRADE_DEFS = {
  html: {
    key: 'html',
    name: 'HTML & CSS',
    description: 'Mise en page de base. +1 ligne par clic par niveau.',
    baseCost: 10,
    costFactor: 1.5,
    perClickBonus: 1,
    perSecondBonus: 0
  },
  js: {
    key: 'js',
    name: 'JavaScript',
    description: 'Logique côté client. +3 lignes par clic par niveau.',
    baseCost: 50,
    costFactor: 1.6,
    perClickBonus: 3,
    perSecondBonus: 0
  },
  php: {
    key: 'php',
    name: 'PHP Backend',
    description: 'Traitement côté serveur. +2 lignes par seconde par niveau.',
    baseCost: 100,
    costFactor: 1.7,
    perClickBonus: 0,
    perSecondBonus: 2
  },
  symfony: {
    key: 'symfony',
    name: 'Symfony',
    description: 'Framework structuré. +5 lignes par clic et +3 par seconde par niveau.',
    baseCost: 300,
    costFactor: 1.8,
    perClickBonus: 5,
    perSecondBonus: 3
  },
  devops: {
    key: 'devops',
    name: 'CI/CD & DevOps',
    description: 'Automatisation du déploiement. +10 lignes par seconde par niveau.',
    baseCost: 800,
    costFactor: 2.0,
    perClickBonus: 0,
    perSecondBonus: 10
  }
};

let devcookieState = {
  points: 0,
  perClick: 1,
  perSecond: 0,
  totalClicks: 0,
  upgrades: {
    html: { level: 0 },
    js: { level: 0 },
    php: { level: 0 },
    symfony: { level: 0 },
    devops: { level: 0 }
  }
};

let lastTick = Date.now();

// ==========================
// Helpers
// ==========================

function getUpgradeCost(key) {
  const def = UPGRADE_DEFS[key];
  const level = devcookieState.upgrades[key]?.level || 0;
  return Math.floor(def.baseCost * Math.pow(def.costFactor, level));
}

function recalcBonuses() {
  let perClick = 1;
  let perSecond = 0;

  for (const key in UPGRADE_DEFS) {
    const def = UPGRADE_DEFS[key];
    const level = devcookieState.upgrades[key]?.level || 0;
    perClick += def.perClickBonus * level;
    perSecond += def.perSecondBonus * level;
  }

  devcookieState.perClick = perClick;
  devcookieState.perSecond = perSecond;
}

function updateUI() {
  const pointsEl = document.getElementById('devcookie-points');
  const pcEl = document.getElementById('devcookie-per-click');
  const psEl = document.getElementById('devcookie-per-second');

  if (pointsEl) pointsEl.textContent = Math.floor(devcookieState.points);
  if (pcEl) pcEl.textContent = devcookieState.perClick.toString();
  if (psEl) psEl.textContent = devcookieState.perSecond.toString();

  // Mettre à jour les cartes d'upgrade
  for (const key in UPGRADE_DEFS) {
    const levelEl = document.querySelector('[data-upgrade-level="' + key + '"]');
    const costEl  = document.querySelector('[data-upgrade-cost="' + key + '"]');
    const btnEl   = document.querySelector('[data-upgrade-buy="' + key + '"]');
    const cost = getUpgradeCost(key);
    const level = devcookieState.upgrades[key]?.level || 0;

    if (levelEl) levelEl.textContent = level.toString();
    if (costEl) costEl.textContent = cost.toString();
    if (btnEl) {
      btnEl.disabled = devcookieState.points < cost;
    }
  }
}

function initUpgradesUI() {
  const container = document.getElementById('devcookie-upgrades');
  if (!container) return;

  container.innerHTML = '';

  for (const key in UPGRADE_DEFS) {
    const def = UPGRADE_DEFS[key];
    const card = document.createElement('div');
    card.className = 'demo-block';
    card.style.marginBottom = '0.75rem';

    card.innerHTML = `
      <strong>${def.name}</strong><br>
      <span style="font-size:0.9rem;">${def.description}</span>
      <p style="margin-top:0.3rem; font-size:0.9rem;">
        Niveau : <span data-upgrade-level="${key}">0</span><br>
        Coût : <span data-upgrade-cost="${key}">${def.baseCost}</span> lignes
      </p>
      <button class="btn btn-outline" data-upgrade-buy="${key}">Acheter</button>
    `;

    container.appendChild(card);
  }

  container.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-upgrade-buy]');
    if (!btn) return;
    const key = btn.getAttribute('data-upgrade-buy');
    buyUpgrade(key);
  });

  updateUI();
}

function buyUpgrade(key) {
  const def = UPGRADE_DEFS[key];
  if (!def) return;

  const cost = getUpgradeCost(key);
  if (devcookieState.points < cost) {
    setMessage("Pas assez de lignes de code pour acheter " + def.name + ".");
    return;
  }

  devcookieState.points -= cost;
  const current = devcookieState.upgrades[key]?.level || 0;
  devcookieState.upgrades[key] = { level: current + 1 };
  recalcBonuses();
  updateUI();
  setMessage("Amélioration achetée : " + def.name + " (niveau " + devcookieState.upgrades[key].level + ").");
  queueSave();
}

function tick() {
  const now = Date.now();
  const deltaSec = (now - lastTick) / 1000;
  lastTick = now;

  if (devcookieState.perSecond > 0) {
    devcookieState.points += devcookieState.perSecond * deltaSec;
    updateUI();
  }

  requestAnimationFrame(tick);
}

function setMessage(msg) {
  const el = document.getElementById('devcookie-message');
  if (!el) return;
  el.textContent = msg;
}

// ==========================
// Sauvegarde / chargement
// ==========================

let saveScheduled = false;
let lastSaveTime = 0;

function getStateForSave() {
  return {
    points: devcookieState.points,
    perClick: devcookieState.perClick,
    perSecond: devcookieState.perSecond,
    totalClicks: devcookieState.totalClicks,
    upgrades: devcookieState.upgrades
  };
}

function applyLoadedState(state) {
  if (!state) return;
  devcookieState.points      = state.points ?? 0;
  devcookieState.perClick    = state.perClick ?? 1;
  devcookieState.perSecond   = state.perSecond ?? 0;
  devcookieState.totalClicks = state.totalClicks ?? 0;
  devcookieState.upgrades    = state.upgrades ?? devcookieState.upgrades;
  recalcBonuses();
  updateUI();
}

function updateSaveStatus(text) {
  const el = document.getElementById('devcookie-save-status');
  if (el) el.textContent = text;
}

function saveNow() {
  if (!DEVCOOKIE_LOGGED_IN) {
    updateSaveStatus("Sauvegarde seulement locale (non connecté).");
    return;
  }

  const payload = new URLSearchParams();
  payload.append('action', 'save');
  payload.append('state', JSON.stringify(getStateForSave()));

  fetch('/devcookie_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: payload.toString()
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        lastSaveTime = Date.now();
        updateSaveStatus("Dernière sauvegarde : " + new Date().toLocaleTimeString());
      } else {
        updateSaveStatus("Erreur de sauvegarde : " + (data.error || 'inconnue'));
      }
    })
    .catch(err => {
      console.error(err);
      updateSaveStatus("Erreur réseau lors de la sauvegarde.");
    });
}

function queueSave() {
  if (!DEVCOOKIE_LOGGED_IN) return;
  const now = Date.now();
  if (now - lastSaveTime < 5000) {
    // pas plus d'une sauvegarde toutes les 5 secondes
    return;
  }
  saveNow();
}

function loadStateFromServer() {
  if (!DEVCOOKIE_LOGGED_IN) {
    updateSaveStatus("Jouez en invité : progression seulement côté navigateur.");
    return;
  }

  const payload = new URLSearchParams();
  payload.append('action', 'load');

  fetch('/devcookie_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: payload.toString()
  })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.state) {
        applyLoadedState(data.state);
        updateSaveStatus("Sauvegarde chargée depuis le serveur.");
      } else {
        updateSaveStatus("Aucune sauvegarde serveur trouvée. Une nouvelle sera créée.");
      }
    })
    .catch(err => {
      console.error(err);
      updateSaveStatus("Erreur lors du chargement de la sauvegarde.");
    });
}

// ==========================
// Init
// ==========================

document.addEventListener('DOMContentLoaded', function () {
  const clickBtn = document.getElementById('devcookie-click-btn');
  const saveBtn  = document.getElementById('devcookie-save-btn');

  initUpgradesUI();
  recalcBonuses();
  updateUI();

  if (clickBtn) {
    clickBtn.addEventListener('click', function () {
      devcookieState.points += devcookieState.perClick;
      devcookieState.totalClicks += 1;
      updateUI();
      setMessage("Vous codez... +" + devcookieState.perClick + " lignes de code !");
      queueSave();
    });
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      saveNow();
    });
  }

  loadStateFromServer();
  lastTick = Date.now();
  requestAnimationFrame(tick);

  // Sauvegarde automatique toutes les 30 secondes si connecté
  if (DEVCOOKIE_LOGGED_IN) {
    setInterval(queueSave, 30000);
  }

  window.addEventListener('beforeunload', function () {
    if (DEVCOOKIE_LOGGED_IN) {
      queueSave();
    }
  });
});
</script>

</body>
</html>
