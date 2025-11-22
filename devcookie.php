<?php
// devcookie.php
session_start();

$currentPage     = 'demos'; // pour mettre à jour le menu si besoin
$pageTitle       = 'Cookie Dev – Mini-jeu clicker développeur';
$pageDescription = "Mini-jeu clicker dans l’univers du développement : lignes de code, upgrades, prestige et classement.";
$pageRobots      = 'noindex,follow'; // tu peux passer à index,follow si tu veux le référencer
include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Cookie Dev – Clicker développeur</h1>
      <p class="section-intro">
        Mini-jeu de type <strong>idle / clicker</strong> dans l’univers du développement web :
        cliquez pour produire des <strong>lignes de code</strong>, achetez des améliorations,
        débloquez un <strong>système de prestige</strong> et grimpez dans le
        <strong>classement des développeurs</strong>.
      </p>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="alert alert-info">
          Vous pouvez jouer en invité, mais votre progression ne sera <strong>pas sauvegardée</strong> sur le serveur.
          Créez un compte ou connectez-vous pour activer la sauvegarde et apparaître dans le classement :
          <a href="/auth/register.php">Inscription</a> · <a href="/auth/login.php?redirect=/devcookie.php">Connexion</a>.
        </p>
      <?php else: ?>
        <p class="alert alert-info">
          Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8'); ?></strong>.
          Votre progression est sauvegardée automatiquement côté serveur et utilisée pour le classement.
        </p>
      <?php endif; ?>

      <!-- Zone principale du jeu -->
      <div class="grid grid-2" style="margin-top:2rem; align-items:flex-start;">
        <!-- Colonne gauche : stats + clic + upgrades -->
        <div>
          <article class="card">
            <h2>Statistiques</h2>
            <p>Lignes de code actuelles : <strong><span id="cd-lines">0</span></strong></p>
            <p>Total de lignes produites : <strong><span id="cd-total-lines">0</span></strong></p>
            <p>Lignes par clic : <strong><span id="cd-per-click">1</span></strong></p>
            <p>Lignes par seconde : <strong><span id="cd-lps">0</span></strong></p>
            <p>Points de prestige disponibles : <strong><span id="cd-prestige-points">0</span></strong></p>
            <p id="cd-prestige-preview" class="muted" style="margin-top:0.4rem;"></p>
            <p id="cd-save-status" class="muted" style="margin-top:0.4rem;"></p>
          </article>

          <article class="card" style="margin-top:1rem;">
            <h2>Actions</h2>
            <button id="cd-main-btn" class="btn btn-primary" style="width:100%; margin-bottom:0.75rem;">
              Coder une nouvelle fonctionnalité
            </button>
            <p class="muted">
              Cliquez pour produire des lignes de code, puis investissez-les dans des améliorations
              (développeurs, outils, automatisation…) pour augmenter vos lignes par seconde.
            </p>
          </article>

          <article class="card" style="margin-top:1rem;">
            <h2>Améliorations (lignes / seconde & clic)</h2>
            <p class="muted">
              Chaque amélioration augmente soit vos <strong>lignes par seconde</strong>,
              soit vos <strong>lignes par clic</strong>. Le coût augmente avec le niveau.
              Certaines améliorations deviennent très puissantes sur le long terme.
            </p>
            <div id="cd-upgrades-list" class="demo-block" style="margin-top:0.75rem;"></div>
          </article>
        </div>

        <!-- Colonne droite : prestige + arbre de compétences + classement -->
        <div>
          <article class="card">
            <h2>Prestige & arbre de compétences</h2>
            <p>
              En réinitialisant votre progression, vous gagnez des <strong>points de prestige</strong>
              en fonction de votre total de lignes produites. Ces points permettent d’acheter
              des bonus permanents (arbre de compétences) : plus de lignes / seconde,
              réduction du coût des améliorations, clics plus puissants, chance de gain bonus, etc.
            </p>
            <button id="cd-prestige-btn" class="btn btn-outline" style="margin-top:0.5rem;">
              Réinitialiser et gagner des points de prestige
            </button>
            <p class="muted" style="margin-top:0.4rem;">
              La réinitialisation remet à zéro vos lignes et vos améliorations, mais vous conservez
              vos points de prestige et vos compétences débloquées.
            </p>

            <h3 style="margin-top:1rem;">Compétences de prestige</h3>
            <div id="cd-prestige-list" class="demo-block" style="margin-top:0.5rem;"></div>
          </article>

          <article class="card" style="margin-top:1rem;">
            <h2>Classement des développeurs</h2>
            <p class="muted">
              Classement basé sur le <strong>total de lignes produites</strong> (toutes parties cumulées).
              Nécessite un compte pour apparaître dans le tableau.
            </p>
            <table style="width:100%; font-size:0.9rem; margin-top:0.75rem; border-collapse:collapse;">
              <thead>
                <tr>
                  <th style="text-align:left; border-bottom:1px solid #e5e7eb;">#</th>
                  <th style="text-align:left; border-bottom:1px solid #e5e7eb;">Pseudo</th>
                  <th style="text-align:right; border-bottom:1px solid #e5e7eb;">Total lignes</th>
                  <th style="text-align:right; border-bottom:1px solid #e5e7eb;">Prestige</th>
                </tr>
              </thead>
              <tbody id="cd-leaderboard-body">
                <tr><td colspan="4" class="muted">Chargement du classement…</td></tr>
              </tbody>
            </table>
          </article>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// ==============================
// Config générale du jeu
// ==============================
const CD_API_URL = '/devcookie_api.php';
const CD_SAVE_INTERVAL_MS = 10000; // autosave toutes les 10s

// Upgrades "classiques" : lignes / seconde & lignes / clic
const CD_UPGRADES_CONFIG = {
  juniorDev: {
    key: 'juniorDev',
    label: 'Développeur junior',
    description: '+0,2 ligne / seconde par niveau',
    type: 'lps',
    baseCost: 10,
    costMul: 1.15,
    lpsGain: 0.2
  },
  seniorDev: {
    key: 'seniorDev',
    label: 'Développeur senior',
    description: '+1 ligne / seconde par niveau',
    type: 'lps',
    baseCost: 100,
    costMul: 1.17,
    lpsGain: 1
  },
  architecte: {
    key: 'architecte',
    label: 'Architecte logiciel',
    description: '+5 lignes / seconde par niveau',
    type: 'lps',
    baseCost: 800,
    costMul: 1.2,
    lpsGain: 5
  },
  devOps: {
    key: 'devOps',
    label: 'Automatisation DevOps',
    description: '+12 lignes / seconde par niveau',
    type: 'lps',
    baseCost: 2500,
    costMul: 1.25,
    lpsGain: 12
  },
  ideBoost: {
    key: 'ideBoost',
    label: 'Optimisation IDE',
    description: '+1 ligne par clic par niveau',
    type: 'click',
    baseCost: 25,
    costMul: 1.18,
    clickGain: 1
  },
  onlineCourse: {
    key: 'onlineCourse',
    label: 'Formation en ligne',
    description: '+3 lignes par clic par niveau',
    type: 'click',
    baseCost: 150,
    costMul: 1.18,
    clickGain: 3
  },
  unitTests: {
    key: 'unitTests',
    label: 'Suite de tests automatisés',
    description: '+0,5 ligne / seconde par niveau (refactor facilité)',
    type: 'lps',
    baseCost: 180,
    costMul: 1.17,
    lpsGain: 0.5
  },
  mentoring: {
    key: 'mentoring',
    label: 'Mentorat',
    description: '+5 % lignes / clic par niveau',
    type: 'clickMultiplier',
    baseCost: 500,
    costMul: 1.22,
    percentPerLevel: 0.05
  }
};

// Arbre de prestige : bonus permanents
const CD_PRESTIGE_CONFIG = {
  lpsBoost: {
    key: 'lpsBoost',
    label: 'Productivité globale',
    description: '+15 % lignes / seconde par niveau (cumulatif)',
    baseCost: 1,
    costGrowth: 1, // coût = baseCost + level * costGrowth
    maxLevel: 10
  },
  costReduction: {
    key: 'costReduction',
    label: 'Négociation licence / outils',
    description: '-3 % sur le coût de toutes les améliorations par niveau (max 60 %)',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 20
  },
  clickBoost: {
    key: 'clickBoost',
    label: 'Focus absolu',
    description: '+25 % lignes / clic par niveau',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 10
  },
  luckBoost: {
    key: 'luckBoost',
    label: 'Inspiration soudaine',
    description: '+3 % de chance de doubler un clic (max 50 %)',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 15
  }
};

// État par défaut côté front
const CD_DEFAULT_STATE = {
  lines: 0,
  totalLines: 0,
  manualClicks: 0,
  upgrades: {
    juniorDev: { level: 0 },
    seniorDev: { level: 0 },
    architecte: { level: 0 },
    devOps: { level: 0 },
    ideBoost: { level: 0 },
    onlineCourse: { level: 0 },
    unitTests: { level: 0 },
    mentoring: { level: 0 }
  },
  prestigePoints: 0,
  prestigeUpgrades: {
    lpsBoost: { level: 0 },
    costReduction: { level: 0 },
    clickBoost: { level: 0 },
    luckBoost: { level: 0 }
  }
};

let cdState = null;
let cdDerived = {
  perClick: 1,
  lps: 0,
  luckChance: 0,
  costReductionFactor: 1
};
let cdCanServerSave = false;
let cdSaveTimer = null;

// ==============================
// Utilitaires état / dérivés
// ==============================
function cdMergeState(raw) {
  // Fusionne l'état brut avec le state par défaut pour supporter les anciennes versions
  const base = JSON.parse(JSON.stringify(CD_DEFAULT_STATE));
  const s = raw && typeof raw === 'object' ? raw : {};

  base.lines        = s.lines        != null ? s.lines        : base.lines;
  base.totalLines   = s.totalLines   != null ? s.totalLines   : base.totalLines;
  base.manualClicks = s.manualClicks != null ? s.manualClicks : base.manualClicks;
  base.prestigePoints = s.prestigePoints != null ? s.prestigePoints : base.prestigePoints;

  base.upgrades = Object.assign({}, base.upgrades, s.upgrades || {});
  base.prestigeUpgrades = Object.assign({}, base.prestigeUpgrades, s.prestigeUpgrades || {});

  return base;
}

function cdGetCostReductionFactor(state) {
  const lvl = (state.prestigeUpgrades.costReduction && state.prestigeUpgrades.costReduction.level) || 0;
  const perLevel = 0.03; // 3 % par niveau
  const total = Math.min(0.6, lvl * perLevel);
  return 1 - total;
}

function cdComputeDerived(state) {
  let perClickBase = 1;
  let baseLps = 0;
  let clickMultiplierBonus = 0;

  Object.keys(CD_UPGRADES_CONFIG).forEach(key => {
    const cfg = CD_UPGRADES_CONFIG[key];
    const lvl = (state.upgrades[key] && state.upgrades[key].level) || 0;
    if (lvl <= 0) return;

    if (cfg.type === 'lps') {
      baseLps += cfg.lpsGain * lvl;
    } else if (cfg.type === 'click') {
      perClickBase += cfg.clickGain * lvl;
    } else if (cfg.type === 'clickMultiplier') {
      clickMultiplierBonus += (cfg.percentPerLevel || 0) * lvl; // ex: 0.05 = +5 % / niveau
    }
  });

  const clickBoostLvl = (state.prestigeUpgrades.clickBoost && state.prestigeUpgrades.clickBoost.level) || 0;
  const lpsBoostLvl   = (state.prestigeUpgrades.lpsBoost   && state.prestigeUpgrades.lpsBoost.level)   || 0;
  const luckLvl       = (state.prestigeUpgrades.luckBoost  && state.prestigeUpgrades.luckBoost.level)  || 0;

  let perClick = perClickBase;
  perClick *= (1 + clickMultiplierBonus); // bonus upgrades type clickMultiplier
  perClick *= (1 + clickBoostLvl * 0.25); // +25 % / niveau de prestige clickBoost

  let lps = baseLps * (1 + lpsBoostLvl * 0.15); // +15 % / niveau de prestige lpsBoost

  const luckChance = Math.min(0.5, luckLvl * 0.03); // +3 % / niveau, cap à 50 %

  const costReductionFactor = cdGetCostReductionFactor(state);

  return { perClick, lps, luckChance, costReductionFactor };
}

function cdGetUpgradeLevel(key) {
  return (cdState.upgrades[key] && cdState.upgrades[key].level) || 0;
}

function cdGetUpgradeCost(key) {
  const cfg = CD_UPGRADES_CONFIG[key];
  if (!cfg) return Infinity;
  const lvl = cdGetUpgradeLevel(key);
  const base = cfg.baseCost * Math.pow(cfg.costMul, lvl);
  return Math.ceil(base * cdDerived.costReductionFactor);
}

function cdGetPrestigeLevel(key) {
  return (cdState.prestigeUpgrades[key] && cdState.prestigeUpgrades[key].level) || 0;
}

function cdGetPrestigeCost(key) {
  const cfg = CD_PRESTIGE_CONFIG[key];
  if (!cfg) return Infinity;
  const lvl = cdGetPrestigeLevel(key);
  return cfg.baseCost + lvl * cfg.costGrowth;
}

// prestige calcul : très simple : 1 point / 50 000 lignes totales
function cdComputePrestigeGain(state) {
  const t = state.totalLines || 0;
  const gain = Math.floor(t / 50000);
  return gain;
}

// ==============================
// Rendering UI
// ==============================
function cdRender(updateDerived = false) {
  if (!cdState) return;

  if (updateDerived) {
    cdDerived = cdComputeDerived(cdState);
  }

  const linesEl      = document.getElementById('cd-lines');
  const totalEl      = document.getElementById('cd-total-lines');
  const perClickEl   = document.getElementById('cd-per-click');
  const lpsEl        = document.getElementById('cd-lps');
  const prestigeEl   = document.getElementById('cd-prestige-points');
  const prestigePrev = document.getElementById('cd-prestige-preview');

  if (linesEl)    linesEl.textContent    = cdState.lines.toFixed(1).replace('.0', '');
  if (totalEl)    totalEl.textContent    = cdState.totalLines.toFixed(1).replace('.0', '');
  if (perClickEl) perClickEl.textContent = cdDerived.perClick.toFixed(2);
  if (lpsEl)      lpsEl.textContent      = cdDerived.lps.toFixed(2);
  if (prestigeEl) prestigeEl.textContent = cdState.prestigePoints || 0;

  if (prestigePrev) {
    const gain = cdComputePrestigeGain(cdState);
    if (gain > 0) {
      prestigePrev.textContent = 'Si vous réinitialisez maintenant, vous gagnerez environ ' +
        gain + ' point(s) de prestige.';
    } else {
      prestigePrev.textContent = 'Accumulez davantage de lignes totales pour débloquer des points de prestige.';
    }
  }

  cdRenderUpgrades();
  cdRenderPrestige();
}

function cdRenderUpgrades() {
  const container = document.getElementById('cd-upgrades-list');
  if (!container) return;

  container.innerHTML = '';

  Object.keys(CD_UPGRADES_CONFIG).forEach(key => {
    const cfg = CD_UPGRADES_CONFIG[key];
    const lvl = cdGetUpgradeLevel(key);
    const cost = cdGetUpgradeCost(key);

    const div = document.createElement('div');
    div.className = 'demo-block';
    div.style.marginBottom = '0.5rem';

    div.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
        <div>
          <strong>${cfg.label}</strong><br>
          <span style="font-size:0.85rem; color:#4b5563;">${cfg.description}</span><br>
          <span style="font-size:0.8rem; color:#6b7280;">Niveau actuel : ${lvl}</span>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.85rem; margin-bottom:0.25rem;">Coût : ${cost} lignes</div>
          <button class="btn btn-primary" data-upgrade="${cfg.key}">Acheter</button>
        </div>
      </div>
    `;

    container.appendChild(div);
  });

  // Binder les boutons
  container.querySelectorAll('button[data-upgrade]').forEach(btn => {
    btn.addEventListener('click', function () {
      const key = this.getAttribute('data-upgrade');
      cdBuyUpgrade(key);
    });
  });
}

function cdRenderPrestige() {
  const container = document.getElementById('cd-prestige-list');
  if (!container) return;

  container.innerHTML = '';

  Object.keys(CD_PRESTIGE_CONFIG).forEach(key => {
    const cfg = CD_PRESTIGE_CONFIG[key];
    const lvl = cdGetPrestigeLevel(key);
    const cost = cdGetPrestigeCost(key);

    const div = document.createElement('div');
    div.className = 'demo-block';
    div.style.marginBottom = '0.5rem';

    const maxInfo = cfg.maxLevel ? ` (max ${cfg.maxLevel})` : '';

    div.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
        <div>
          <strong>${cfg.label}</strong>${maxInfo}<br>
          <span style="font-size:0.85rem; color:#4b5563;">${cfg.description}</span><br>
          <span style="font-size:0.8rem; color:#6b7280;">Niveau actuel : ${lvl}</span>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.85rem; margin-bottom:0.25rem;">Coût : ${cost} point(s)</div>
          <button class="btn btn-outline" data-prestige="${cfg.key}">Améliorer</button>
        </div>
      </div>
    `;

    container.appendChild(div);
  });

  container.querySelectorAll('button[data-prestige]').forEach(btn => {
    btn.addEventListener('click', function () {
      const key = this.getAttribute('data-prestige');
      cdBuyPrestigeUpgrade(key);
    });
  });
}

// ==============================
// Actions user
// ==============================
function cdClickMain() {
  if (!cdState) return;
  let gain = cdDerived.perClick;
  if (cdDerived.luckChance > 0 && Math.random() < cdDerived.luckChance) {
    gain *= 2; // clic critique
  }
  cdState.lines += gain;
  cdState.totalLines += gain;
  cdState.manualClicks = (cdState.manualClicks || 0) + 1;
  cdRender(false);
}

function cdBuyUpgrade(key) {
  const cfg = CD_UPGRADES_CONFIG[key];
  if (!cfg || !cdState) return;
  const cost = cdGetUpgradeCost(key);
  if (cdState.lines < cost) {
    alert("Vous n'avez pas assez de lignes pour cette amélioration.");
    return;
  }
  cdState.lines -= cost;
  if (!cdState.upgrades[key]) {
    cdState.upgrades[key] = { level: 0 };
  }
  cdState.upgrades[key].level += 1;
  cdDerived = cdComputeDerived(cdState);
  cdRender(false);
  cdScheduleSaveSoon();
}

function cdBuyPrestigeUpgrade(key) {
  const cfg = CD_PRESTIGE_CONFIG[key];
  if (!cfg || !cdState) return;

  const currentLevel = cdGetPrestigeLevel(key);
  if (cfg.maxLevel && currentLevel >= cfg.maxLevel) {
    alert("Cette compétence est déjà au niveau maximum.");
    return;
  }

  const cost = cdGetPrestigeCost(key);
  if ((cdState.prestigePoints || 0) < cost) {
    alert("Vous n'avez pas assez de points de prestige.");
    return;
  }

  cdState.prestigePoints -= cost;
  if (!cdState.prestigeUpgrades[key]) {
    cdState.prestigeUpgrades[key] = { level: 0 };
  }
  cdState.prestigeUpgrades[key].level = currentLevel + 1;

  cdDerived = cdComputeDerived(cdState);
  cdRender(false);
  cdScheduleSaveSoon();
}

function cdDoPrestige() {
  if (!cdState) return;
  const gain = cdComputePrestigeGain(cdState);
  if (gain <= 0) {
    alert("Vous n'avez pas encore produit assez de lignes pour obtenir des points de prestige.");
    return;
  }
  const confirmMsg =
    "La réinitialisation va remettre à zéro vos lignes et vos améliorations,\n" +
    "mais vous conserverez vos points de prestige et vos compétences débloquées.\n\n" +
    "Vous obtiendrez " + gain + " point(s) de prestige supplémentaires.\n\n" +
    "Confirmer ?";
  if (!window.confirm(confirmMsg)) {
    return;
  }

  const newPrestigePoints = (cdState.prestigePoints || 0) + gain;
  // On garde les prestigeUpgrades, on reset le reste
  const keptPrestigeUpgrades = Object.assign({}, cdState.prestigeUpgrades || {});

  cdState = JSON.parse(JSON.stringify(CD_DEFAULT_STATE));
  cdState.prestigePoints = newPrestigePoints;
  cdState.prestigeUpgrades = keptPrestigeUpgrades;

  cdDerived = cdComputeDerived(cdState);
  cdRender(false);
  cdSaveNow(); // sauvegarde immédiate après un prestige
}

// ==============================
// Boucle de jeu & sauvegarde
// ==============================
function cdTick() {
  if (!cdState) return;
  if (cdDerived.lps > 0) {
    cdState.lines += cdDerived.lps;
    cdState.totalLines += cdDerived.lps;
    cdRender(false);
  }
}

function cdSaveNow() {
  if (!cdCanServerSave || !cdState) return;

  fetch(CD_API_URL + '?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ state: cdState })
  })
    .then(response => response.json())
    .then(data => {
      const statusEl = document.getElementById('cd-save-status');
      if (!statusEl) return;
      if (data.ok) {
        statusEl.textContent = 'Progression sauvegardée ✔ (' + (data.savedAt || 'serveur') + ')';
      } else {
        statusEl.textContent = 'Sauvegarde non disponible (joueur non connecté ou erreur).';
      }
    })
    .catch(() => {
      const statusEl = document.getElementById('cd-save-status');
      if (statusEl) {
        statusEl.textContent = 'Erreur lors de la sauvegarde (connexion réseau ?).';
      }
    });
}

function cdScheduleSaveSoon() {
  // On peut choisir de limiter les saves trop fréquentes; ici on laisse la boucle interval gérer
  // mais on force une sauvegarde dans quelques secondes
  // (la boucle d'interval le fera de toute façon)
}

// ==============================
// Leaderboard
// ==============================
function cdLoadLeaderboard() {
  const tbody = document.getElementById('cd-leaderboard-body');
  if (!tbody) return;

  fetch(CD_API_URL + '?action=leaderboard')
    .then(response => response.json())
    .then(data => {
      if (!data.ok) {
        tbody.innerHTML = '<tr><td colspan="4" class="muted">Impossible de charger le classement.</td></tr>';
        return;
      }
      const items = data.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="muted">Pas encore de joueurs classés.</td></tr>';
        return;
      }
      tbody.innerHTML = '';
      items.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${index + 1}</td>
          <td>${item.pseudo}</td>
          <td style="text-align:right;">${item.totalLines}</td>
          <td style="text-align:right;">${item.prestigePoints}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="4" class="muted">Erreur de chargement du classement.</td></tr>';
    });
}

// ==============================
// Initialisation
// ==============================
function cdInit() {
  const mainBtn     = document.getElementById('cd-main-btn');
  const prestigeBtn = document.getElementById('cd-prestige-btn');

  if (mainBtn) {
    mainBtn.addEventListener('click', cdClickMain);
  }
  if (prestigeBtn) {
    prestigeBtn.addEventListener('click', cdDoPrestige);
  }

  // Chargement de l'état depuis le serveur
  fetch(CD_API_URL + '?action=load')
    .then(response => response.json())
    .then(data => {
      cdCanServerSave = !!data.loggedIn && !!data.canSave;
      cdState = cdMergeState(data.state || {});
      cdDerived = cdComputeDerived(cdState);
      cdRender(false);
    })
    .catch(() => {
      cdState = cdMergeState(null);
      cdDerived = cdComputeDerived(cdState);
      cdRender(false);
    })
    .finally(() => {
      // Boucle de jeu (1 tick / seconde)
      setInterval(cdTick, 1000);
      // Sauvegarde auto toutes les X secondes (si connecté)
      cdSaveTimer = setInterval(cdSaveNow, CD_SAVE_INTERVAL_MS);
      // Leaderboard
      cdLoadLeaderboard();
    });
}

document.addEventListener('DOMContentLoaded', cdInit);
</script>

</body>
</html>
