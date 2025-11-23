<?php
// gemminer.php
session_start();

$currentPage     = 'demos'; // ou autre si tu veux changer le surlignage du menu
$pageTitle       = 'Gem Miner Tycoon – Jeu de minage & gestion';
$pageDescription = "Jeu de minage idle : gemmes, logistique, ventes, prestige et classement.";
$pageRobots      = 'noindex,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section" style="padding-top:1.5rem;padding-bottom:1.5rem;">
    <div class="container">
      <h1>Gem Miner Tycoon</h1>
      <p class="section-intro" style="max-width:720px;">
        Cliquez sur le gisement pour extraire des <strong>gemmes</strong>, débloquez des
        <strong>outils</strong>, optimisez la <strong>logistique</strong> et la
        <strong>vente</strong>, puis réinitialisez avec du prestige pour aller encore plus loin.
      </p>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="alert alert-info">
          Vous jouez en invité : la progression ne sera <strong>pas sauvegardée</strong> et vous
          n’apparaîtrez pas dans le classement.
          <a href="/auth/register.php?redirect=/games.php">Créer un compte</a> ou
          <a href="/auth/login.php?redirect=/games.php">Se connecter</a>.
        </p>
      <?php else: ?>
        <p class="alert alert-info">
          Connecté en tant que
          <strong><?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Joueur', ENT_QUOTES, 'UTF-8'); ?></strong>.
          Votre progression est sauvegardée automatiquement.
        </p>
      <?php endif; ?>

      <!-- Layout général -->
      <div class="gm-layout">
        <!-- Gauche : gemme -->
        <div class="gm-left">
          <div class="gm-gem-area">
            <div id="gm-gem-visual" class="gm-gem gm-gem-tier0">
              <img id="gm-gem-img"
                   src="assets/gemminer/gems/gem_tier0.png"
                   alt="Gemme actuelle">
            </div>
            <button id="gm-mine-btn" class="btn btn-primary gm-mine-btn">
              Miner ce gisement
            </button>
            <p class="muted" id="gm-ore-info">
              Quartz terne – PV : <span id="gm-ore-hp">30</span> / <span id="gm-ore-maxhp">30</span>
            </p>
          </div>
        </div>

        <!-- Centre : stats -->
        <div class="gm-center">
          <article class="card gm-card-compact">
            <h2>Statistiques</h2>
            <p>Gemmes : <strong><span id="gm-gems">0</span></strong></p>
            <p>Total extraites : <strong><span id="gm-total-gems">0</span></strong></p>
            <p>Par clic : <strong><span id="gm-dmg-click">1</span></strong> PV / clic,
               <strong><span id="gm-gems-click">1</span></strong> gemme / clic</p>
            <p>Extraction auto : <strong><span id="gm-dps">0</span></strong> PV / s</p>
            <p>Chaîne (minées / transportées / vendues) :<br>
              <strong><span id="gm-prod-mined">0</span></strong> /
              <strong><span id="gm-prod-transported">0</span></strong> /
              <strong><span id="gm-prod-sold">0</span></strong> gemmes / s
            </p>
            <p>Prestige : <strong><span id="gm-prestige-points">0</span></strong></p>
            <p id="gm-prestige-preview" class="muted"></p>
            <p id="gm-save-status" class="muted"></p>
          </article>

          <article class="card gm-card-compact" style="margin-top:0.75rem;">
            <h2>Prestige</h2>
            <p class="muted">
              Réinitialisez votre mine pour gagner des points de prestige permanents.
            </p>
            <button id="gm-prestige-btn" class="btn btn-outline" style="width:100%;">
              Réinitialiser la mine et gagner du prestige
            </button>
          </article>
        </div>

        <!-- Droite : classement + upgrades -->
        <div class="gm-right">
          <article class="card gm-card-compact gm-scroll-card">
            <h2>Classement</h2>
            <p class="muted" style="margin-bottom:0.4rem;">
              Score = gemmes totales / jours d’activité.
            </p>
            <table class="gm-leaderboard">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Joueur</th>
                  <th style="text-align:right;">Gemmes</th>
                  <th style="text-align:right;">Prestige</th>
                  <th style="text-align:right;">Score</th>
                </tr>
              </thead>
              <tbody id="gm-leaderboard-body">
                <tr><td colspan="5" class="muted">Chargement…</td></tr>
              </tbody>
            </table>
          </article>

          <article class="card gm-card-compact gm-scroll-card" style="margin-top:0.75rem;">
            <h2>Améliorations</h2>

            <details class="gm-upgrade-group" open>
              <summary>Extraction</summary>
              <div id="gm-upgrades-mining" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group">
              <summary>Logistique</summary>
              <div id="gm-upgrades-logistics" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group">
              <summary>Vente</summary>
              <div id="gm-upgrades-sales" class="gm-upgrade-list"></div>
            </details>

            <details class="gm-upgrade-group">
              <summary>Prestige</summary>
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
/* Layout serré pour éviter de scroller la page sur un écran de bureau */
.gm-layout {
  display: grid;
  grid-template-columns: 1.1fr 1.4fr 1.5fr;
  gap: 1rem;
  align-items: flex-start;
}

@media (max-width: 960px) {
  .gm-layout {
    grid-template-columns: 1fr;
  }
}

.gm-card-compact {
  padding: 0.8rem 1rem;
}

.gm-scroll-card {
  max-height: 340px;
  overflow-y: auto;
}

/* Zone gemme */
.gm-gem-area {
  background: #020617;
  border-radius: 1rem;
  padding: 1.2rem;
  color: #e5e7eb;
  box-shadow: 0 12px 30px rgba(15,23,42,0.7);
  text-align: center;
}

.gm-gem {
  width: 135px;
  height: 135px;
  margin: 0 auto 0.6rem auto;
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

/* Aura selon le tier */
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
  font-size: 0.8rem;
  margin-top: 0.4rem;
}
.gm-leaderboard th,
.gm-leaderboard td {
  padding: 0.2rem 0.25rem;
  border-bottom: 1px solid #e5e7eb;
}
.gm-leaderboard th {
  text-align: left;
}

/* Upgrades */
.gm-upgrade-group {
  margin-top: 0.3rem;
}
.gm-upgrade-group summary {
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
}
.gm-upgrade-list {
  margin-top: 0.3rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.gm-upgrade-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.3rem 0.4rem;
  border-radius: 0.5rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  font-size: 0.8rem;
}
.gm-upgrade-item h4 {
  margin: 0;
  font-size: 0.85rem;
}
.gm-upgrade-item p {
  margin: 0.08rem 0;
}

.gm-upgrade-main {
  display: flex;
  align-items: flex-start;
  gap: 0.4rem;
}

.gm-upgrade-icon {
  width: 26px;
  height: 26px;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0 4px 8px rgba(15,23,42,0.3));
}
</style>

<script>
// =============================
// Config & état du jeu
// =============================
const GM_API_URL = 'gemminer_api.php';
const GM_SAVE_INTERVAL_MS = 10000;

// Types de minerais : HP croissants et récompenses croissantes
// -> début : très rapide, ensuite ça se durcit
const GM_ORE_TYPES = [
  { name: 'Quartz terne',        baseHp: 30,    baseReward: 15 },
  { name: 'Malachite veineuse',  baseHp: 150,   baseReward: 60 },
  { name: 'Fer profond',         baseHp: 600,   baseReward: 200 },
  { name: 'Or ancien',           baseHp: 2400,  baseReward: 700 },
  { name: 'Cristal arcanique',   baseHp: 10000, baseReward: 2200 },
  { name: 'Gemme du Néant',      baseHp: 60000, baseReward: 7000 },
];

// Upgrades Extraction
const GM_UPGRADES_MINING = {
  handTools: {
    key: 'handTools',
    label: 'Outils à main',
    description: 'Légère augmentation des dégâts et des gemmes par clic.',
    baseCost: 20,
    costMul: 1.2,
    dmgPerLevel: 1,
    gemPerLevel: 0.4
  },
  pickaxe: {
    key: 'pickaxe',
    label: 'Pioches améliorées',
    description: 'Gros bonus de dégâts par clic.',
    baseCost: 150,
    costMul: 1.22,
    dmgPerLevel: 3,
    gemPerLevel: 0.8
  },
  drill: {
    key: 'drill',
    label: 'Foreuses mécaniques',
    description: 'Augmente l’extraction automatique (PV/s).',
    baseCost: 400,
    costMul: 1.25,
    dpsPerLevel: 2
  },
  megaDrill: {
    key: 'megaDrill',
    label: 'Foreuse de chantier',
    description: 'Gros bonus d’extraction automatique.',
    baseCost: 2000,
    costMul: 1.3,
    dpsPerLevel: 10
  },
  absurdEngine: {
    key: 'absurdEngine',
    label: 'Machine absurde interdimensionnelle',
    description: 'Bonus important sur clics et extraction auto.',
    baseCost: 15000,
    costMul: 1.35,
    dmgPerLevel: 10,
    gemPerLevel: 3,
    dpsPerLevel: 25
  }
};

// Upgrades Logistique
const GM_UPGRADES_LOGISTICS = {
  carts: {
    key: 'carts',
    label: 'Chariots de mine',
    description: 'Une plus grande partie de la production est transportée.',
    baseCost: 50,
    costMul: 1.18,
    effPerLevel: 0.05
  },
  rail: {
    key: 'rail',
    label: 'Rail souterrain',
    description: 'Gros gain de transport.',
    baseCost: 300,
    costMul: 1.2,
    effPerLevel: 0.09
  },
  portal: {
    key: 'portal',
    label: 'Portails de transfert',
    description: 'Transport quasi instantané.',
    baseCost: 2500,
    costMul: 1.25,
    effPerLevel: 0.15
  }
};

// Upgrades Vente
const GM_UPGRADES_SALES = {
  stall: {
    key: 'stall',
    label: 'Petit stand de vente',
    description: 'Vous vendez une fraction des gemmes transportées.',
    baseCost: 80,
    costMul: 1.18,
    effPerLevel: 0.05
  },
  shop: {
    key: 'shop',
    label: 'Boutique en ville',
    description: 'Les ventes deviennent plus régulières.',
    baseCost: 350,
    costMul: 1.2,
    effPerLevel: 0.08
  },
  export: {
    key: 'export',
    label: 'Contrats d’export',
    description: 'Vous vendez une grande partie de ce que vous transportez.',
    baseCost: 2000,
    costMul: 1.25,
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
    description: '+20 % de dégâts par clic par niveau.',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 10
  },
  chainEfficiency: {
    key: 'chainEfficiency',
    label: 'Chaîne optimisée',
    description: '+5 % sur toute la chaîne minage → vente.',
    baseCost: 1,
    costGrowth: 1,
    maxLevel: 15
  },
  rareOreChance: {
    key: 'rareOreChance',
    label: 'Filon rare',
    description: 'Chance d’obtenir un bonus de gemmes en changeant de minerai.',
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
    hp: 30,
    maxHp: 30,
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
  gemsPerClick: 1,
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
      case 'handTools':     return 'assets/gemminer/icons/mining_handtools.png';
      case 'pickaxe':       return 'assets/gemminer/icons/mining_pickaxe.png';
      case 'drill':         return 'assets/gemminer/icons/mining_drill.png';
      case 'megaDrill':     return 'assets/gemminer/icons/mining_megadrill.png';
      case 'absurdEngine':  return 'assets/gemminer/icons/mining_absurdengine.png';
    }
  }
  if (group === 'logisticsUpgrades') {
    switch (key) {
      case 'carts':   return 'assets/gemminer/icons/logistics_carts.png';
      case 'rail':    return 'assets/gemminer/icons/logistics_rail.png';
      case 'portal':  return 'assets/gemminer/icons/logistics_portal.png';
    }
  }
  if (group === 'salesUpgrades') {
    switch (key) {
      case 'stall':   return 'assets/gemminer/icons/sales_stall.png';
      case 'shop':    return 'assets/gemminer/icons/sales_shop.png';
      case 'export':  return 'assets/gemminer/icons/sales_export.png';
    }
  }
  if (group === 'prestige') {
    switch (key) {
      case 'globalDps':        return 'assets/gemminer/icons/prestige_globaldps.png';
      case 'clickBoost':       return 'assets/gemminer/icons/prestige_clickboost.png';
      case 'chainEfficiency':  return 'assets/gemminer/icons/prestige_chain.png';
      case 'rareOreChance':    return 'assets/gemminer/icons/prestige_rareore.png';
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

// Dérivées : dégâts / clic, gemmes / clic, DPS + chaîne
function gmComputeDerived(state) {
  let click = 1;
  let gemsPerClick = 1;
  let dps   = 0;

  // Extraction
  Object.keys(GM_UPGRADES_MINING).forEach(k => {
    const cfg = GM_UPGRADES_MINING[k];
    const lvl = gmGetUpgradeLevel('miningUpgrades', k);
    if (!lvl) return;
    if (cfg.dmgPerLevel) click += cfg.dmgPerLevel * lvl;
    if (cfg.gemPerLevel) gemsPerClick += cfg.gemPerLevel * lvl;
    if (cfg.dpsPerLevel) dps   += cfg.dpsPerLevel * lvl;
  });

  // Prestige clic & dps
  const clickPrestige = gmGetPrestigeLevel('clickBoost');
  const dpsPrestige   = gmGetPrestigeLevel('globalDps');
  const chainPrestige = gmGetPrestigeLevel('chainEfficiency');

  click        *= (1 + 0.20 * clickPrestige);
  gemsPerClick *= (1 + 0.15 * clickPrestige); // les mêmes points rendent le clic plus rentable
  dps          *= (1 + 0.15 * dpsPrestige);

  // Base “minedPerSec” = DPS
  let minedPerSec = dps;

  // Logistique et vente
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

  transportEff = Math.min(0.95, transportEff);
  salesEff     = Math.min(0.95, salesEff);

  let transportedPerSec = minedPerSec * (0.1 + transportEff);
  let soldPerSec        = transportedPerSec * (0.1 + salesEff);

  const chainBoost = 1 + 0.05 * chainPrestige;
  minedPerSec       *= chainBoost;
  transportedPerSec *= chainBoost;
  soldPerSec        *= chainBoost;

  return {
    clickDmg: click,
    gemsPerClick,
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
  const gemsClickEl  = document.getElementById('gm-gems-click');
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
  if (gemsClickEl) gemsClickEl.textContent= gmDerived.gemsPerClick.toFixed(1);
  if (dpsEl)       dpsEl.textContent      = gmDerived.dps.toFixed(1);
  if (prodMinedEl) prodMinedEl.textContent= gmDerived.minedPerSec.toFixed(1);
  if (prodTransEl) prodTransEl.textContent= gmDerived.transportedPerSec.toFixed(1);
  if (prodSoldEl)  prodSoldEl.textContent = gmDerived.soldPerSec.toFixed(1);
  if (prestigeEl)  prestigeEl.textContent = g.prestigePoints || 0;

  const oreTier = g.currentOre.tier || 0;
  const oreType = GM_ORE_TYPES[oreTier] || GM_ORE_TYPES[0];

  if (oreHpEl)    oreHpEl.textContent    = Math.max(0, g.currentOre.hp.toFixed(1));
  if (oreMaxHpEl) oreMaxHpEl.textContent = g.currentOre.maxHp.toFixed(1);
  if (oreInfoEl)  oreInfoEl.textContent  =
      `${oreType.name} – PV : ${Math.max(0, g.currentOre.hp.toFixed(1))} / ${g.currentOre.maxHp.toFixed(1)}`;

  if (gemVisualEl) {
    gemVisualEl.className = 'gm-gem gm-gem-tier' + Math.min(5, oreTier);
  }
  if (gemImgEl) {
    const tierClamped = Math.min(5, oreTier);
    gemImgEl.src = 'assets/gemminer/gems/gem_tier' + tierClamped + '.png';
    gemImgEl.alt = oreType.name;
  }

  if (prestigePrev) {
    const gain = gmComputePrestigeGain(gmState);
    if (gain > 0) {
      prestigePrev.textContent = `Réinitialisation : environ ${gain} point(s) de prestige.`;
    } else {
      prestigePrev.textContent = `Extrayez plus de gemmes pour débloquer vos premiers points de prestige.`;
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
        <div style="font-size:0.8rem;">Coût : ${cost} gemmes</div>
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
        <div style="font-size:0.8rem;">Coût : ${cost} gemmes</div>
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
        <div style="font-size:0.8rem;">Coût : ${cost} gemmes</div>
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
        <div style="font-size:0.8rem;">Coût : ${cost} point(s)</div>
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
  const gemsGain = gmDerived.gemsPerClick;

  // Gemmes gagnées à chaque clic
  gmState.gems      += gemsGain;
  gmState.totalGems += gemsGain;

  // Progression sur le minerai
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

  // Récompense forte à chaque minerai cassé
  let gain = oreType.baseReward;
  gmState.gems      += gain;
  gmState.totalGems += gain;

  // Chance de bonus rare via prestige
  const rareLvl = gmGetPrestigeLevel('rareOreChance');
  if (rareLvl > 0) {
    const chance = Math.min(0.5, rareLvl * 0.04); // 4% / niveau, cap 50%
    if (Math.random() < chance) {
      const bonus = Math.floor(gain * 2);
      gmState.gems      += bonus;
      gmState.totalGems += bonus;
    }
  }

  // On monte de tier, ce qui rend le prochain minerai plus long à casser
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
  gmSaveNow();
}

// =============================
// Boucle & sauvegarde
// =============================
function gmTick() {
  if (!gmState) return;
  gmDerived = gmComputeDerived(gmState);

  // Extraction auto sur le minerai
  let dps = gmDerived.dps;
  if (dps > 0) {
    gmState.currentOre.hp -= dps;
    if (gmState.currentOre.hp <= 0) {
      gmDestroyOre();
    }
  }

  // Production vendue -> gemmes
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
  // On laisse le timer faire le travail, pas de debounce nécessaire pour l’instant
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
