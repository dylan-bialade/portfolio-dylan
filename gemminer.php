<?php
// gemminer.php
session_start();

$currentPage     = 'demos'; // pour surligner "Démos" si tu veux
$pageTitle       = 'Gem Miner Tycoon – Jeu de minage & gestion';
$pageDescription = "Jeu de minage idle : mineurs, gemmes, logistique, ventes, prestige et classement.";
$pageRobots      = 'noindex,follow'; // tu pourras passer à index,follow plus tard

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Gem Miner Tycoon</h1>
      <p class="section-intro">
        Construisez votre empire de mineurs : cliquez sur le gisement pour extraire des <strong>gemmes</strong>,
        améliorez vos outils, optimisez la <strong>logistique</strong> et développez votre réseau de <strong>boutiques</strong>.
        Gagnez du prestige, débloquez des compétences et grimpez dans le classement.
      </p>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="alert alert-info">
          Vous pouvez jouer en invité, mais votre progression ne sera <strong>pas sauvegardée</strong> et vous n’apparaîtrez
          pas dans le classement. <a href="/auth/register.php">Inscription</a> ou
          <a href="/auth/login.php?redirect=/gemminer.php">Connexion</a> recommandée pour profiter du jeu à fond.
        </p>
      <?php else: ?>
        <p class="alert alert-info">
          Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Joueur', ENT_QUOTES, 'UTF-8'); ?></strong>.
          Votre progression est sauvegardée automatiquement et utilisée pour le classement.
        </p>
      <?php endif; ?>

      <!-- Layout général : gemme à gauche, stats au centre, leaderboard + upgrades à droite -->
      <div class="gm-layout">
        <!-- Haut gauche : gemme principale -->
        <div class="gm-left">
          <div class="gm-gem-area">
            <div id="gm-gem-visual" class="gm-gem gm-gem-tier0">
              <img id="gm-gem-img"
                   src="/assets/gemminer/gems/gem_tier0.png"
                   alt="Gemme actuelle">
            </div>
            <button id="gm-mine-btn" class="btn btn-primary gm-mine-btn">
              Miner ce gisement
            </button>
            <p class="muted" id="gm-ore-info">
              Quartz terne – PV : <span id="gm-ore-hp">100</span> / <span id="gm-ore-maxhp">100</span>
            </p>
          </div>
        </div>

        <!-- Haut centre : stats globales -->
        <div class="gm-center">
          <article class="card">
            <h2>Statistiques</h2>
            <p>Gemmes actuelles : <strong><span id="gm-gems">0</span></strong></p>
            <p>Total de gemmes extraites : <strong><span id="gm-total-gems">0</span></strong></p>
            <p>Extraction par clic : <strong><span id="gm-dmg-click">1</span></strong> PV</p>
            <p>Extraction automatique : <strong><span id="gm-dps">0</span></strong> PV / s</p>
            <p>Production (minées / transportées / vendues) :<br>
              <strong><span id="gm-prod-mined">0</span></strong> /
              <strong><span id="gm-prod-transported">0</span></strong> /
              <strong><span id="gm-prod-sold">0</span></strong> gemmes / s
            </p>
            <p>Points de prestige : <strong><span id="gm-prestige-points">0</span></strong></p>
            <p id="gm-prestige-preview" class="muted"></p>
            <p id="gm-save-status" class="muted"></p>
          </article>

          <article class="card" style="margin-top:1rem;">
            <h2>Prestige & réinitialisation</h2>
            <p>
              En réinitialisant votre mine, vous gagnez des <strong>points de prestige</strong> basés sur le total
              de gemmes extraites. Ces points servent à acheter des compétences permanentes.
            </p>
            <button id="gm-prestige-btn" class="btn btn-outline">
              Réinitialiser la mine et gagner du prestige
            </button>
          </article>
        </div>

        <!-- Haut droite : classement + menus d’upgrades -->
        <div class="gm-right">
          <article class="card">
            <h2>Classement des mineurs</h2>
            <p class="muted">
              Classement basé sur un <strong>score</strong> = gemmes totales / jours d’activité.
              Cela permet aux nouveaux joueurs motivés de rattraper les anciens, tout en récompensant
              la constance.
            </p>
            <table class="gm-leaderboard">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Joueur</th>
                  <th style="text-align:right;">Gemmes totales</th>
                  <th style="text-align:right;">Prestige</th>
                  <th style="text-align:right;">Score</th>
                </tr>
              </thead>
              <tbody id="gm-leaderboard-body">
                <tr><td colspan="5" class="muted">Chargement…</td></tr>
              </tbody>
            </table>
          </article>

          <article class="card" style="margin-top:1rem;">
            <h2>Améliorations</h2>
            <p class="muted">
              Trois catégories : <strong>Extraction</strong>, <strong>Logistique</strong>, <strong>Vente</strong>.
              Chaque achat débloque visuellement de nouveaux outils ou machines.
            </p>

            <details class="gm-upgrade-group" open>
              <summary>Extraction (mineurs & outils)</summary>
              <div id="gm-upgrades-mining" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group">
              <summary>Logistique (transport)</summary>
              <div id="gm-upgrades-logistics" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group">
              <summary>Vente (boutiques & marché)</summary>
              <div id="gm-upgrades-sales" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group" style="margin-top:0.5rem;">
              <summary>Compétences de prestige</summary>
              <div id="gm-prestige-list" class="gm-upgrade-list"></div>
            </details>
          </article>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<style>
/* Layout global du jeu */
.gm-layout {
  display: grid;
  grid-template-columns: 1.1fr 1.4fr 1.4fr;
  gap: 1rem;
  align-items: flex-start;
}

@media (max-width: 960px) {
  .gm-layout {
    grid-template-columns: 1fr;
  }
}

.gm-gem-area {
  background: #020617;
  border-radius: 1rem;
  padding: 1.2rem;
  color: #e5e7eb;
  box-shadow: 0 12px 30px rgba(15,23,42,0.7);
  text-align: center;
}

/* Conteneur de la gemme */
.gm-gem {
  width: 140px;
  height: 140px;
  margin: 0 auto 0.8rem auto;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  box-shadow: 0 0 0 3px rgba(148,163,184,0.8);
  overflow: hidden;
}

.gm-gem img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 8px 12px rgba(0,0,0,0.45));
}

/* Aura visuelle par tiers */
.gm-gem-tier0 { background: radial-gradient(circle at 30% 30%, #9ca3af, #020617); }
.gm-gem-tier1 { background: radial-gradient(circle at 30% 30%, #38bdf8, #020617); }
.gm-gem-tier2 { background: radial-gradient(circle at 30% 30%, #22c55e, #020617); }
.gm-gem-tier3 { background: radial-gradient(circle at 30% 30%, #f97316, #020617); }
.gm-gem-tier4 { background: radial-gradient(circle at 30% 30%, #a855f7, #020617); }
.gm-gem-tier5 { background: radial-gradient(circle at 30% 30%, #e11d48, #020617); }

.gm-gem-clicked {
  transform: scale(0.95);
  box-shadow: 0 0 0 4px rgba(251,191,36,0.9);
}

.gm-mine-btn {
  width: 100%;
  margin-bottom: 0.4rem;
}

/* Leaderboard */
.gm-leaderboard {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  margin-top: 0.6rem;
}
.gm-leaderboard th,
.gm-leaderboard td {
  padding: 0.25rem 0.3rem;
  border-bottom: 1px solid #e5e7eb;
}
.gm-leaderboard th {
  text-align: left;
}

/* Groupes d’upgrades */
.gm-upgrade-group {
  margin-top: 0.5rem;
}
.gm-upgrade-group summary {
  cursor: pointer;
  font-weight: 600;
}
.gm-upgrade-list {
  margin-top: 0.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

/* Items d’upgrades */
.gm-upgrade-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.35rem 0.4rem;
  border-radius: 0.5rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  font-size: 0.85rem;
}
.gm-upgrade-item h4 {
  margin: 0;
  font-size: 0.9rem;
}
.gm-upgrade-item p {
  margin: 0.1rem 0;
}

.gm-upgrade-main {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.gm-upgrade-icon {
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0 4px 8px rgba(15,23,42,0.3));
}
</style>

<script>
// =============================
// Config & état du jeu
// =============================
const GM_API_URL = '/gemminer_api.php';
const GM_SAVE_INTERVAL_MS = 10000;

// Types de minerais (tiers)
const GM_ORE_TYPES = [
  { name: 'Quartz terne',        emoji: '💠', baseHp: 100 },
  { name: 'Malachite veineuse',  emoji: '🟢', baseHp: 300 },
  { name: 'Fer profond',         emoji: '🧱', baseHp: 900 },
  { name: 'Or ancien',           emoji: '🟡', baseHp: 2500 },
  { name: 'Cristal arcanique',   emoji: '🔮', baseHp: 7500 },
  { name: 'Gemme du Néant',      emoji: '🌀', baseHp: 20000 },
];

// Upgrades Extraction
const GM_UPGRADES_MINING = {
  handTools: {
    key: 'handTools',
    label: 'Outils à main',
    description: 'Augmente légèrement les dégâts par clic.',
    baseCost: 20,
    costMul: 1.15,
    dmgPerLevel: 1
  },
  pickaxe: {
    key: 'pickaxe',
    label: 'Pioches améliorées',
    description: 'Augmente fortement les dégâts par clic.',
    baseCost: 150,
    costMul: 1.17,
    dmgPerLevel: 3
  },
  drill: {
    key: 'drill',
    label: 'Foreuses mécaniques',
    description: 'Augmente l’extraction automatique (PV/s).',
    baseCost: 400,
    costMul: 1.2,
    dpsPerLevel: 2
  },
  megaDrill: {
    key: 'megaDrill',
    label: 'Foreuse de chantier',
    description: 'Gros bonus d’extraction automatique.',
    baseCost: 2000,
    costMul: 1.25,
    dpsPerLevel: 10
  },
  absurdEngine: {
    key: 'absurdEngine',
    label: 'Machine absurde interdimensionnelle',
    description: 'Bonus fou de dégâts par clic et PV/s.',
    baseCost: 15000,
    costMul: 1.3,
    dmgPerLevel: 15,
    dpsPerLevel: 30
  }
};

// Upgrades Logistique
const GM_UPGRADES_LOGISTICS = {
  carts: {
    key: 'carts',
    label: 'Chariots de mine',
    description: 'Améliore la fraction de gemmes effectivement transportées.',
    baseCost: 50,
    costMul: 1.15,
    effPerLevel: 0.05 // +5% de transport effectif
  },
  rail: {
    key: 'rail',
    label: 'Rail souterrain',
    description: 'Grosse amélioration de transport.',
    baseCost: 300,
    costMul: 1.18,
    effPerLevel: 0.09
  },
  portal: {
    key: 'portal',
    label: 'Portails de transfert',
    description: 'Le flux de transport devient presque instantané.',
    baseCost: 2500,
    costMul: 1.23,
    effPerLevel: 0.15
  }
};

// Upgrades Vente
const GM_UPGRADES_SALES = {
  stall: {
    key: 'stall',
    label: 'Petit stand de vente',
    description: 'Vous vendez une partie des gemmes extraites.',
    baseCost: 80,
    costMul: 1.16,
    effPerLevel: 0.05
  },
  shop: {
    key: 'shop',
    label: 'Boutique en ville',
    description: 'Les ventes deviennent beaucoup plus régulières.',
    baseCost: 350,
    costMul: 1.18,
    effPerLevel: 0.08
  },
  export: {
    key: 'export',
    label: 'Contrats d’export',
    description: 'Vous vendez une grande partie de ce que vous transportez.',
    baseCost: 2000,
    costMul: 1.22,
    effPerLevel: 0.12
  }
};

// Prestige upgrades
const GM_PRESTIGE_UPGRADES = {
  globalDps: {
    key: 'globalDps',
    label: 'Dynamitage organisé',
    description: '+15 % d’extraction automatique par niveau.',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 10
  },
  clickBoost: {
    key: 'clickBoost',
    label: 'Technique de frappe',
    description: '+25 % de dégâts par clic par niveau.',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 10
  },
  chainEfficiency: {
    key: 'chainEfficiency',
    label: 'Chaîne complète optimisée',
    description: '+5 % de productivité globale (minage → vente) par niveau.',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 15
  },
  rareOreChance: {
    key: 'rareOreChance',
    label: 'Chance de filon rare',
    description: 'Chance d’obtenir des gemmes bonus lors de la destruction d’un gisement.',
    baseCost: 2,
    costGrowth: 1,
    maxLevel: 10
  }
};

const GM_DEFAULT_STATE = {
  gems: 0,
  totalGems: 0,
  currentOre: {
    tier: 0,
    hp: 100,
    maxHp: 100,
    name: 'Quartz terne'
  },
  miningUpgrades: {},
  logisticsUpgrades: {},
  salesUpgrades: {},
  prestigePoints: 0,
  prestigeUpgrades: {},
  stats: {
    minedPerSec: 0,
    transportedPerSec: 0,
    soldPerSec: 0
  }
};

let gmState = null;
let gmDerived = {
  clickDmg: 1,
  dps: 0,
  minedPerSec: 0,
  transportedPerSec: 0,
  soldPerSec: 0
};
let gmCanServerSave = false;

// =============================
// Helpers icônes
// =============================
function gmGetUpgradeIconPath(group, key) {
  if (group === 'miningUpgrades') {
    switch (key) {
      case 'handTools':     return '/assets/gemminer/icons/mining_handtools.png';
      case 'pickaxe':       return '/assets/gemminer/icons/mining_pickaxe.png';
      case 'drill':         return '/assets/gemminer/icons/mining_drill.png';
      case 'megaDrill':     return '/assets/gemminer/icons/mining_megadrill.png';
      case 'absurdEngine':  return '/assets/gemminer/icons/mining_absurdengine.png';
    }
  }
  if (group === 'logisticsUpgrades') {
    switch (key) {
      case 'carts':   return '/assets/gemminer/icons/logistics_carts.png';
      case 'rail':    return '/assets/gemminer/icons/logistics_rail.png';
      case 'portal':  return '/assets/gemminer/icons/logistics_portal.png';
    }
  }
  if (group === 'salesUpgrades') {
    switch (key) {
      case 'stall':   return '/assets/gemminer/icons/sales_stall.png';
      case 'shop':    return '/assets/gemminer/icons/sales_shop.png';
      case 'export':  return '/assets/gemminer/icons/sales_export.png';
    }
  }
  if (group === 'prestige') {
    switch (key) {
      case 'globalDps':        return '/assets/gemminer/icons/prestige_globaldps.png';
      case 'clickBoost':       return '/assets/gemminer/icons/prestige_clickboost.png';
      case 'chainEfficiency':  return '/assets/gemminer/icons/prestige_chain.png';
      case 'rareOreChance':    return '/assets/gemminer/icons/prestige_rareore.png';
    }
  }
  return '';
}

// =============================
// Utilitaires d’état
// =============================
function gmMergeState(raw) {
  const base = JSON.parse(JSON.stringify(GM_DEFAULT_STATE));
  const s = raw && typeof raw === 'object' ? raw : {};

  base.gems      = s.gems      != null ? s.gems      : base.gems;
  base.totalGems = s.totalGems != null ? s.totalGems : base.totalGems;
  base.currentOre = Object.assign({}, base.currentOre, s.currentOre || {});
  base.miningUpgrades    = Object.assign({}, base.miningUpgrades, s.miningUpgrades || {});
  base.logisticsUpgrades = Object.assign({}, base.logisticsUpgrades, s.logisticsUpgrades || {});
  base.salesUpgrades     = Object.assign({}, base.salesUpgrades, s.salesUpgrades || {});
  base.prestigePoints    = s.prestigePoints != null ? s.prestigePoints : base.prestigePoints;
  base.prestigeUpgrades  = Object.assign({}, base.prestigeUpgrades, s.prestigeUpgrades || {});
  base.stats             = Object.assign({}, base.stats, s.stats || base.stats);

  return base;
}

function gmGetUpgradeLevel(group, key) {
  const g = gmState[group] || {};
  return (g[key] && g[key].level) || 0;
}

function gmGetPrestigeLevel(key) {
  const g = gmState.prestigeUpgrades || {};
  return (g[key] && g[key].level) || 0;
}

function gmGetPrestigeCost(key) {
  const cfg = GM_PRESTIGE_UPGRADES[key];
  if (!cfg) return Infinity;
  const lvl = gmGetPrestigeLevel(key);
  return cfg.baseCost + lvl * cfg.costGrowth;
}

function gmComputePrestigeGain(state) {
  const t = state.totalGems || 0;
  return Math.floor(t / 50000); // 1 point / 50 000 gemmes extraites
}

// Dérivées : dégâts / clic, DPS, chaîne de prod
function gmComputeDerived(state) {
  let click = 1;
  let dps   = 0;

  // Extraction
  Object.keys(GM_UPGRADES_MINING).forEach(k => {
    const cfg = GM_UPGRADES_MINING[k];
    const lvl = gmGetUpgradeLevel('miningUpgrades', k);
    if (!lvl) return;
    if (cfg.dmgPerLevel) click += cfg.dmgPerLevel * lvl;
    if (cfg.dpsPerLevel) dps   += cfg.dpsPerLevel * lvl;
  });

  // Prestige click & dps
  const clickPrestige = gmGetPrestigeLevel('clickBoost');
  const dpsPrestige   = gmGetPrestigeLevel('globalDps');
  const chainPrestige = gmGetPrestigeLevel('chainEfficiency');

  click *= (1 + 0.25 * clickPrestige);
  dps   *= (1 + 0.15 * dpsPrestige);

  // Base “minedPerSec” = DPS
  let minedPerSec = dps;

  // Logistique et vente : on ne fait pas une vraie file mais une eff globale
  let transportEff = 0;
  Object.keys(GM_UPGRADES_LOGISTICS).forEach(k => {
    const cfg = GM_UPGRADES_LOGISTICS[k];
    const lvl = gmGetUpgradeLevel('logisticsUpgrades', k);
    if (!lvl) return;
    transportEff += cfg.effPerLevel * lvl;
  });

  let salesEff = 0;
  Object.keys(GM_UPGRADES_SALES).forEach(k => {
    const cfg = GM_UPGRADES_SALES[k];
    const lvl = gmGetUpgradeLevel('salesUpgrades', k);
    if (!lvl) return;
    salesEff += cfg.effPerLevel * lvl;
  });

  // Cap maximal à 95% pour éviter 100%
  transportEff = Math.min(0.95, transportEff);
  salesEff     = Math.min(0.95, salesEff);

  let transportedPerSec = minedPerSec * (0.1 + transportEff); // mini 10% même sans upgrades
  let soldPerSec        = transportedPerSec * (0.1 + salesEff);

  const chainBoost = 1 + 0.05 * chainPrestige;
  minedPerSec       *= chainBoost;
  transportedPerSec *= chainBoost;
  soldPerSec        *= chainBoost;

  return {
    clickDmg: click,
    dps,
    minedPerSec,
    transportedPerSec,
    soldPerSec
  };
}

// =============================
// Rendering
// =============================
function gmRender(updateDerived = false) {
  if (!gmState) return;
  if (updateDerived) gmDerived = gmComputeDerived(gmState);

  const g = gmState;

  const gemsEl       = document.getElementById('gm-gems');
  const totalGemsEl  = document.getElementById('gm-total-gems');
  const dmgClickEl   = document.getElementById('gm-dmg-click');
  const dpsEl        = document.getElementById('gm-dps');
  const prodMinedEl  = document.getElementById('gm-prod-mined');
  const prodTransEl  = document.getElementById('gm-prod-transported');
  const prodSoldEl   = document.getElementById('gm-prod-sold');
  const prestigeEl   = document.getElementById('gm-prestige-points');
  const oreHpEl      = document.getElementById('gm-ore-hp');
  const oreMaxHpEl   = document.getElementById('gm-ore-maxhp');
  const oreInfoEl    = document.getElementById('gm-ore-info');
  const gemVisualEl  = document.getElementById('gm-gem-visual');
  const gemImgEl     = document.getElementById('gm-gem-img');
  const prestigePrev = document.getElementById('gm-prestige-preview');

  if (gemsEl)      gemsEl.textContent     = Math.floor(g.gems);
  if (totalGemsEl) totalGemsEl.textContent= Math.floor(g.totalGems);
  if (dmgClickEl)  dmgClickEl.textContent = gmDerived.clickDmg.toFixed(1);
  if (dpsEl)       dpsEl.textContent      = gmDerived.dps.toFixed(1);
  if (prodMinedEl) prodMinedEl.textContent= gmDerived.minedPerSec.toFixed(1);
  if (prodTransEl) prodTransEl.textContent= gmDerived.transportedPerSec.toFixed(1);
  if (prodSoldEl)  prodSoldEl.textContent = gmDerived.soldPerSec.toFixed(1);
  if (prestigeEl)  prestigeEl.textContent = g.prestigePoints || 0;

  const oreTier = g.currentOre.tier || 0;
  const oreType = GM_ORE_TYPES[oreTier] || GM_ORE_TYPES[0];

  if (oreHpEl)    oreHpEl.textContent    = Math.max(0, g.currentOre.hp.toFixed(1));
  if (oreMaxHpEl) oreMaxHpEl.textContent = g.currentOre.maxHp.toFixed(1);
  if (oreInfoEl)  oreInfoEl.textContent  = `${oreType.name} – PV : ${Math.max(0, g.currentOre.hp.toFixed(1))} / ${g.currentOre.maxHp.toFixed(1)}`;

  if (gemVisualEl) {
    gemVisualEl.className = 'gm-gem gm-gem-tier' + Math.min(5, oreTier);
  }
  if (gemImgEl) {
    const tierClamped = Math.min(5, oreTier);
    gemImgEl.src = '/assets/gemminer/gems/gem_tier' + tierClamped + '.png';
    gemImgEl.alt = oreType.name;
  }

  if (prestigePrev) {
    const gain = gmComputePrestigeGain(gmState);
    if (gain > 0) {
      prestigePrev.textContent = `En réinitialisant maintenant, vous gagneriez environ ${gain} point(s) de prestige.`;
    } else {
      prestigePrev.textContent = `Extrayez davantage de gemmes pour débloquer vos premiers points de prestige.`;
    }
  }

  gmRenderUpgrades();
  gmRenderPrestige();
}

function gmComputeUpgradeCost(group, cfg) {
  const lvl = gmGetUpgradeLevel(group, cfg.key);
  const base = cfg.baseCost * Math.pow(cfg.costMul, lvl);
  return Math.ceil(base);
}

function gmRenderUpgrades() {
  const contMining    = document.getElementById('gm-upgrades-mining');
  const contLogistics = document.getElementById('gm-upgrades-logistics');
  const contSales     = document.getElementById('gm-upgrades-sales');
  if (!contMining || !contLogistics || !contSales) return;

  contMining.innerHTML    = '';
  contLogistics.innerHTML = '';
  contSales.innerHTML     = '';

  // Extraction
  Object.values(GM_UPGRADES_MINING).forEach(cfg => {
    const lvl  = gmGetUpgradeLevel('miningUpgrades', cfg.key);
    const cost = gmComputeUpgradeCost('miningUpgrades', cfg);
    const icon = gmGetUpgradeIconPath('miningUpgrades', cfg.key);

    const div = document.createElement('div');
    div.className = 'gm-upgrade-item';
    div.innerHTML = `
      <div class="gm-upgrade-main">
        ${icon ? `<img src="${icon}" alt="" class="gm-upgrade-icon">` : ''}
        <div>
          <h4>${cfg.label}</h4>
          <p>${cfg.description}</p>
          <p class="muted">Niveau : ${lvl}</p>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.85rem;">Coût : ${cost} gemmes</div>
        <button class="btn btn-primary" data-upgrade-group="miningUpgrades" data-upgrade="${cfg.key}">Acheter</button>
      </div>
    `;
    contMining.appendChild(div);
  });

  // Logistique
  Object.values(GM_UPGRADES_LOGISTICS).forEach(cfg => {
    const lvl  = gmGetUpgradeLevel('logisticsUpgrades', cfg.key);
    const cost = gmComputeUpgradeCost('logisticsUpgrades', cfg);
    const icon = gmGetUpgradeIconPath('logisticsUpgrades', cfg.key);

    const div = document.createElement('div');
    div.className = 'gm-upgrade-item';
    div.innerHTML = `
      <div class="gm-upgrade-main">
        ${icon ? `<img src="${icon}" alt="" class="gm-upgrade-icon">` : ''}
        <div>
          <h4>${cfg.label}</h4>
          <p>${cfg.description}</p>
          <p class="muted">Niveau : ${lvl}</p>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.85rem;">Coût : ${cost} gemmes</div>
        <button class="btn btn-primary" data-upgrade-group="logisticsUpgrades" data-upgrade="${cfg.key}">Acheter</button>
      </div>
    `;
    contLogistics.appendChild(div);
  });

  // Vente
  Object.values(GM_UPGRADES_SALES).forEach(cfg => {
    const lvl  = gmGetUpgradeLevel('salesUpgrades', cfg.key);
    const cost = gmComputeUpgradeCost('salesUpgrades', cfg);
    const icon = gmGetUpgradeIconPath('salesUpgrades', cfg.key);

    const div = document.createElement('div');
    div.className = 'gm-upgrade-item';
    div.innerHTML = `
      <div class="gm-upgrade-main">
        ${icon ? `<img src="${icon}" alt="" class="gm-upgrade-icon">` : ''}
        <div>
          <h4>${cfg.label}</h4>
          <p>${cfg.description}</p>
          <p class="muted">Niveau : ${lvl}</p>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.85rem;">Coût : ${cost} gemmes</div>
        <button class="btn btn-primary" data-upgrade-group="salesUpgrades" data-upgrade="${cfg.key}">Acheter</button>
      </div>
    `;
    contSales.appendChild(div);
  });

  document.querySelectorAll('button[data-upgrade]').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.getAttribute('data-upgrade-group');
      const key   = btn.getAttribute('data-upgrade');
      gmBuyUpgrade(group, key);
    });
  });
}

function gmRenderPrestige() {
  const cont = document.getElementById('gm-prestige-list');
  if (!cont) return;

  cont.innerHTML = '';

  Object.values(GM_PRESTIGE_UPGRADES).forEach(cfg => {
    const lvl  = gmGetPrestigeLevel(cfg.key);
    const cost = gmGetPrestigeCost(cfg.key);
    const maxInfo = cfg.maxLevel ? ` (max ${cfg.maxLevel})` : '';
    const icon = gmGetUpgradeIconPath('prestige', cfg.key);

    const div = document.createElement('div');
    div.className = 'gm-upgrade-item';
    div.innerHTML = `
      <div class="gm-upgrade-main">
        ${icon ? `<img src="${icon}" alt="" class="gm-upgrade-icon">` : ''}
        <div>
          <h4>${cfg.label}${maxInfo}</h4>
          <p>${cfg.description}</p>
          <p class="muted">Niveau : ${lvl}</p>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.85rem;">Coût : ${cost} point(s)</div>
        <button class="btn btn-outline" data-prestige="${cfg.key}">Acheter</button>
      </div>
    `;
    cont.appendChild(div);
  });

  cont.querySelectorAll('button[data-prestige]').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.getAttribute('data-prestige');
      gmBuyPrestigeUpgrade(key);
    });
  });
}

// =============================
// Actions joueur
// =============================
function gmMineClick() {
  if (!gmState) return;
  const ore = gmState.currentOre;
  const dmg = gmDerived.clickDmg;

  ore.hp -= dmg;
  if (ore.hp < 0) ore.hp = 0;

  const gemVisualEl = document.getElementById('gm-gem-visual');
  if (gemVisualEl) {
    gemVisualEl.classList.add('gm-gem-clicked');
    setTimeout(() => gemVisualEl.classList.remove('gm-gem-clicked'), 80);
  }

  if (ore.hp <= 0) {
    gmDestroyOre();
  }
  gmRender(false);
}

function gmDestroyOre() {
  const oreTier = gmState.currentOre.tier || 0;
  const oreType = GM_ORE_TYPES[oreTier] || GM_ORE_TYPES[0];
  // Récompense = HP max / 10 à la louche
  const gain = Math.max(1, Math.floor(oreType.baseHp / 10));
  gmState.gems      += gain;
  gmState.totalGems += gain;

  // Chance de gain bonus via prestige rareOreChance
  const rareLvl = gmGetPrestigeLevel('rareOreChance');
  if (rareLvl > 0) {
    const chance = Math.min(0.5, rareLvl * 0.04); // 4% / niveau, cap 50%
    if (Math.random() < chance) {
      const bonus = Math.floor(gain * 2);
      gmState.gems      += bonus;
      gmState.totalGems += bonus;
    }
  }

  const nextTier = Math.min(GM_ORE_TYPES.length - 1, oreTier + 1);
  const nextType = GM_ORE_TYPES[nextTier];
  gmState.currentOre = {
    tier: nextTier,
    hp: nextType.baseHp,
    maxHp: nextType.baseHp,
    name: nextType.name
  };
}

function gmBuyUpgrade(group, key) {
  if (!gmState) return;
  let cfg;
  if (group === 'miningUpgrades')         cfg = GM_UPGRADES_MINING[key];
  else if (group === 'logisticsUpgrades') cfg = GM_UPGRADES_LOGISTICS[key];
  else if (group === 'salesUpgrades')     cfg = GM_UPGRADES_SALES[key];

  if (!cfg) return;

  const cost = gmComputeUpgradeCost(group, cfg);
  if (gmState.gems < cost) {
    alert("Pas assez de gemmes pour cette amélioration.");
    return;
  }

  gmState.gems -= cost;
  if (!gmState[group][key]) {
    gmState[group][key] = { level: 0 };
  }
  gmState[group][key].level += 1;

  gmDerived = gmComputeDerived(gmState);
  gmRender(false);
  gmScheduleSave();
}

function gmBuyPrestigeUpgrade(key) {
  if (!gmState) return;
  const cfg = GM_PRESTIGE_UPGRADES[key];
  if (!cfg) return;

  const lvl = gmGetPrestigeLevel(key);
  if (cfg.maxLevel && lvl >= cfg.maxLevel) {
    alert("Cette compétence est déjà au niveau maximum.");
    return;
  }

  const cost = gmGetPrestigeCost(key);
  if ((gmState.prestigePoints || 0) < cost) {
    alert("Pas assez de points de prestige.");
    return;
  }

  gmState.prestigePoints -= cost;
  if (!gmState.prestigeUpgrades[key]) {
    gmState.prestigeUpgrades[key] = { level: 0 };
  }
  gmState.prestigeUpgrades[key].level = lvl + 1;

  gmDerived = gmComputeDerived(gmState);
  gmRender(false);
  gmScheduleSave();
}

function gmDoPrestige() {
  if (!gmState) return;
  const gain = gmComputePrestigeGain(gmState);
  if (gain <= 0) {
    alert("Vous n’avez pas encore extrait assez de gemmes pour gagner du prestige.");
    return;
  }

  const confirmMsg =
    "Vous allez réinitialiser votre mine.\n\n" +
    "- Gemmes, upgrades extraction / logistique / vente seront remis à zéro.\n" +
    "- Vous conserverez vos compétences de prestige actuelles.\n" +
    "- Vous gagnerez " + gain + " point(s) de prestige supplémentaire(s).\n\n" +
    "Confirmer ?";
  if (!window.confirm(confirmMsg)) return;

  const keptPrestige = Object.assign({}, gmState.prestigeUpgrades || {});
  const newPrestige  = (gmState.prestigePoints || 0) + gain;

  gmState = JSON.parse(JSON.stringify(GM_DEFAULT_STATE));
  gmState.prestigeUpgrades = keptPrestige;
  gmState.prestigePoints   = newPrestige;

  gmDerived = gmComputeDerived(gmState);
  gmRender(false);
  gmSaveNow(); // sauvegarde immédiate après prestige
}

// =============================
// Boucle & sauvegarde
// =============================
function gmTick() {
  if (!gmState) return;
  gmDerived = gmComputeDerived(gmState);

  // Extraction auto => on fait comme si on réduisait les PV du gisement en continu
  let dps = gmDerived.dps;
  if (dps > 0) {
    gmState.currentOre.hp -= dps;
    if (gmState.currentOre.hp <= 0) {
      gmDestroyOre();
    }
  }

  // On considère la prod/s comme "vendue" = gemmes supplémentaires
  const sold = gmDerived.soldPerSec;
  if (sold > 0) {
    gmState.gems      += sold;
    gmState.totalGems += sold;
  }

  gmRender(false);
}

function gmSaveNow() {
  if (!gmCanServerSave || !gmState) return;

  fetch(GM_API_URL + '?action=save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ state: gmState })
  })
    .then(r => r.json())
    .then(data => {
      const statusEl = document.getElementById('gm-save-status');
      if (!statusEl) return;
      if (data.ok) {
        statusEl.textContent = 'Progression sauvegardée ✔';
      } else {
        statusEl.textContent = 'Sauvegarde non disponible (invité ou erreur).';
      }
    })
    .catch(() => {
      const statusEl = document.getElementById('gm-save-status');
      if (statusEl) {
        statusEl.textContent = 'Erreur de sauvegarde (connexion ?).';
      }
    });
}

function gmScheduleSave() {
  // Pour l’instant on laisse le timer gérer, tu peux ajouter un debounce si besoin
}

// =============================
// Leaderboard
// =============================
function gmLoadLeaderboard() {
  const tbody = document.getElementById('gm-leaderboard-body');
  if (!tbody) return;

  fetch(GM_API_URL + '?action=leaderboard')
    .then(r => r.json())
    .then(data => {
      if (!data.ok) {
        tbody.innerHTML = '<tr><td colspan="5" class="muted">Impossible de charger le classement.</td></tr>';
        return;
      }
      const items = data.items || [];
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="muted">Pas encore de mineurs classés.</td></tr>';
        return;
      }
      tbody.innerHTML = '';
      items.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${item.pseudo}</td>
          <td style="text-align:right;">${item.totalGems}</td>
          <td style="text-align:right;">${item.prestigePoints}</td>
          <td style="text-align:right;">${item.score.toFixed(1)}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(() => {
      tbody.innerHTML = '<tr><td colspan="5" class="muted">Erreur de chargement du classement.</td></tr>';
    });
}

// =============================
// Init
// =============================
function gmInit() {
  const btnMine     = document.getElementById('gm-mine-btn');
  const btnPrestige = document.getElementById('gm-prestige-btn');

  if (btnMine)     btnMine.addEventListener('click', gmMineClick);
  if (btnPrestige) btnPrestige.addEventListener('click', gmDoPrestige);

  fetch(GM_API_URL + '?action=load')
    .then(r => r.json())
    .then(data => {
      gmCanServerSave = !!data.loggedIn && !!data.canSave;
      gmState = gmMergeState(data.state || {});
      gmDerived = gmComputeDerived(gmState);
      gmRender(false);
    })
    .catch(() => {
      gmState = gmMergeState(null);
      gmDerived = gmComputeDerived(gmState);
      gmRender(false);
    })
    .finally(() => {
      setInterval(gmTick, 1000);
      setInterval(gmSaveNow, GM_SAVE_INTERVAL_MS);
      gmLoadLeaderboard();
    });
}

document.addEventListener('DOMContentLoaded', gmInit);
</script>

</body>
</html>
