<?php
$currentPage     = 'demos';
$pageTitle       = 'Démos interactives – Bialadev Studio';
$pageDescription = "Petites démos de mini-jeux et d’interfaces interactives réalisées en JavaScript pour illustrer mes compétences front-end et back-end.";
$pageRobots      = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Démos interactives</h1>
      <p class="section-intro">
        Voici quelques petites démos en <strong>JavaScript</strong> inspirées de différents projets :
        mini-jeux, interactions front-end et une démo plus avancée (<strong>Cookie Dev</strong>)
        qui utilise aussi un <strong>vrai back-end</strong> (PHP, MySQL, comptes utilisateurs).
      </p>

      <div class="grid demos-grid">
        <!-- Démo 1 : Jeu de clic rapide -->
        <article class="card card-demo">
          <h2>Jeu de clic rapide</h2>
          <p>
            Cliquez le plus de fois possible en <strong>5 secondes</strong>. Démo simple pour illustrer la gestion
            du temps, des événements et de l’état en JavaScript.
          </p>
          <div class="demo-block" id="click-game">
            <button id="click-game-start" class="btn btn-primary">Démarrer la partie</button>
            <button id="click-game-btn" class="btn btn-outline" disabled>Cliquer ici</button>
            <p>Temps restant : <span id="click-game-time">5</span> s</p>
            <p>Score : <span id="click-game-score">0</span> clic(s)</p>
            <p id="click-game-message" class="demo-message"></p>
          </div>
        </article>

        <!-- Démo 2 : Deviner le nombre -->
        <article class="card card-demo">
          <h2>Deviner le nombre</h2>
          <p>
            Le script choisit un nombre entre <strong>1 et 100</strong>. À vous de le deviner.
            Cette démo illustre la gestion d’entrées utilisateur, de messages d’erreur
            et de logique conditionnelle.
          </p>
          <div class="demo-block" id="guess-game">
            <p>J’ai choisi un nombre entre 1 et 100. À vous de deviner :</p>
            <input type="number" id="guess-input" min="1" max="100" />
            <button id="guess-btn" class="btn btn-primary">Valider</button>
            <p id="guess-feedback" class="demo-message"></p>
            <p>Essais : <span id="guess-attempts">0</span></p>
            <button id="guess-restart" class="btn btn-outline" style="display:none;">Rejouer</button>
          </div>
        </article>

        <!-- Démo 3 : Animation simple -->
        <article class="card card-demo">
          <h2>Animation &amp; interactions</h2>
          <p>
            Petit carré que l’on peut déplacer avec les touches du clavier
            (<kbd>Z</kbd>, <kbd>Q</kbd>, <kbd>S</kbd>, <kbd>D</kbd> ou flèches) dans une zone limitée.
            Démo pour montrer la gestion des événements clavier et des positions.
          </p>
          <div class="demo-block" id="move-game">
            <div id="move-area">
              <div id="player-square"></div>
            </div>
            <p class="demo-message">
              Utilisez les flèches ou ZQSD pour déplacer le carré.
            </p>
          </div>
        </article>

        <!-- Démo 4 : Cookie Dev (lien vers la version avec back-end) -->
        <article class="card card-demo">
          <h2>Cookie Dev – Clicker du développeur</h2>
          <p>
            Mini-jeu de type <strong>Cookie Clicker</strong> dans l’univers du développement web :
            vous gagnez des lignes de code en cliquant et débloquez des <strong>langages</strong>,
            <strong>frameworks</strong> et <strong>outils</strong>.
          </p>
          <p class="demo-message">
            Cette démo utilise un <strong>back-end en PHP</strong> avec une
            <strong>base MySQL</strong> pour gérer les comptes utilisateurs et
            <strong>sauvegarder la progression</strong>.
          </p>
          <div class="demo-block">
            <p style="margin-bottom:0.75rem;">
              Le jeu complet est disponible sur une page dédiée :
            </p>
            <a href="/devcookie.php" class="btn btn-primary">Jouer à Cookie Dev</a>
            <p style="margin-top:0.6rem; font-size:0.9rem;">
              Vous pouvez jouer en invité, mais si vous <a href="/auth/register.php">créez un compte</a>
              ou <a href="/auth/login.php?redirect=/devcookie.php">vous connectez</a>, votre progression
              sera sauvegardée.
            </p>
          </div>
        </article>
      </div>

      <!-- Démos liées à certains projets -->
      <section class="section" style="padding-top:2rem;">
        <h2>Démos liées à certains projets</h2>
        <p class="section-intro">
          Ces exemples sont inspirés de projets présentés sur la page
          <a href="/projects.php">Projets</a> : dashboard d’administration, gestion de planning,
          etc. L’objectif est de montrer, en version simplifiée, des comportements que
          j’implémente dans ces applications.
        </p>

        <div class="grid demos-grid">
          <!-- Démo projets 1 : Filtre de liste (dashboard / OVH) -->
          <article class="card card-demo">
            <h2>Filtrage d’une liste de projets</h2>
            <p>
              Démo inspirée de mon <strong>dashboard OVH / outils d’administration</strong> :
              filtrer une liste côté front avant d’envoyer une requête plus complexe au serveur
              (ou à une DataTable).
            </p>
            <div class="demo-block">
              <label for="projects-filter-input">Filtrer par nom :</label>
              <input type="text" id="projects-filter-input" placeholder="ex : planning, api, ovh..." />
              <ul id="projects-filter-list">
                <li>Dashboard OVH – gestion de domaines et VPS</li>
                <li>PlanningApp – gestion de planning employés</li>
                <li>Garbadge – gestion de repas / menus</li>
                <li>JCDecaux Vélo’v – disponibilité des stations</li>
                <li>SteamRest – API + front pour bibliothèque de jeux</li>
              </ul>
            </div>
          </article>

          <!-- Démo projets 2 : Mini planning cliquable -->
          <article class="card card-demo">
            <h2>Mini planning cliquable</h2>
            <p>
              Démo inspirée du projet <strong>PlanningApp</strong> : affecter ou retirer rapidement
              une présence en cliquant sur un créneau (matin / après-midi).
            </p>
            <p class="demo-subtitle">
              Cliquez sur les cases pour marquer la présence d’un collaborateur.
            </p>
            <div class="demo-block">
              <div class="mini-planning" id="mini-planning">
                <div class="mini-planning-slot" data-slot="lun-matin">Lun<br />Matin</div>
                <div class="mini-planning-slot" data-slot="lun-aprem">Lun<br />Après-midi</div>
                <div class="mini-planning-slot" data-slot="mar-matin">Mar<br />Matin</div>
                <div class="mini-planning-slot" data-slot="mar-aprem">Mar<br />Après-midi</div>
                <div class="mini-planning-slot" data-slot="mer-matin">Mer<br />Matin</div>
                <div class="mini-planning-slot" data-slot="mer-aprem">Mer<br />Après-midi</div>
                <div class="mini-planning-slot" data-slot="jeu-matin">Jeu<br />Matin</div>
                <div class="mini-planning-slot" data-slot="jeu-aprem">Jeu<br />Après-midi</div>
              </div>
              <p id="mini-planning-output" class="demo-message" style="margin-top:0.75rem;">
                Aucun créneau sélectionné pour le moment.
              </p>
            </div>
          </article>
        </div>
      </section>

      <section class="section" style="padding-top:2rem;">
        <h2>Et pour les projets plus complexes ?</h2>
        <p>
          Ces exemples sont volontairement simples, mais ils s’appuient sur les mêmes bases que
          des interfaces plus avancées : gestion des événements, mise à jour de l’interface
          en fonction de l’état, validation d’entrées, etc.
        </p>
        <p>
          Pour des projets plus complets (applications web, outils métiers, APIs, etc.),
          vous pouvez consulter la page <a href="/projects.php">Projets</a> ou
          <a href="/contact.php">me contacter</a> pour discuter d’un besoin spécifique.
        </p>
      </section>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<!-- Script JS pour les démos -->
<script>
// ==========================
// Démo 1 : Jeu de clic rapide
// ==========================
(function () {
  const startBtn = document.getElementById('click-game-start');
  const clickBtn = document.getElementById('click-game-btn');
  const timeSpan = document.getElementById('click-game-time');
  const scoreSpan = document.getElementById('click-game-score');
  const messageEl = document.getElementById('click-game-message');

  if (!startBtn || !clickBtn) return; // sécurité

  let timeLeft = 5;
  let score = 0;
  let timer = null;

  function resetGame() {
    timeLeft = 5;
    score = 0;
    timeSpan.textContent = timeLeft;
    scoreSpan.textContent = score;
    messageEl.textContent = '';
    clickBtn.disabled = true;
  }

  startBtn.addEventListener('click', function () {
    resetGame();
    clickBtn.disabled = false;
    clickBtn.focus();
    timer = setInterval(function () {
      timeLeft--;
      timeSpan.textContent = timeLeft;
      if (timeLeft <= 0) {
        clearInterval(timer);
        clickBtn.disabled = true;
        messageEl.textContent = 'Partie terminée ! Votre score : ' + score + ' clic(s).';
      }
    }, 1000);
  });

  clickBtn.addEventListener('click', function () {
    if (timeLeft > 0) {
      score++;
      scoreSpan.textContent = score;
    }
  });
})();

// ==========================
// Démo 2 : Deviner le nombre
// ==========================
(function () {
  const input = document.getElementById('guess-input');
  const btn = document.getElementById('guess-btn');
  const feedback = document.getElementById('guess-feedback');
  const attemptsSpan = document.getElementById('guess-attempts');
  const restartBtn = document.getElementById('guess-restart');

  if (!input || !btn) return; // sécurité

  let secret = Math.floor(Math.random() * 100) + 1;
  let attempts = 0;
  let finished = false;

  function checkGuess() {
    if (finished) return;
    const value = parseInt(input.value, 10);
    if (isNaN(value) || value < 1 || value > 100) {
      feedback.textContent = 'Veuillez entrer un nombre entre 1 et 100.';
      return;
    }
    attempts++;
    attemptsSpan.textContent = attempts;

    if (value === secret) {
      feedback.textContent = 'Bravo ! Vous avez trouvé en ' + attempts + ' essai(s).';
      finished = true;
      restartBtn.style.display = 'inline-block';
    } else if (value < secret) {
      feedback.textContent = 'Trop petit.';
    } else {
      feedback.textContent = 'Trop grand.';
    }
  }

  btn.addEventListener('click', checkGuess);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      checkGuess();
    }
  });

  restartBtn.addEventListener('click', function () {
    secret = Math.floor(Math.random() * 100) + 1;
    attempts = 0;
    attemptsSpan.textContent = attempts;
    input.value = '';
    feedback.textContent = '';
    finished = false;
    restartBtn.style.display = 'none';
  });
})();

// ==========================
// Démo 3 : Mouvement du carré
// ==========================
(function () {
  const area = document.getElementById('move-area');
  const player = document.getElementById('player-square');
  if (!area || !player) return;

  let x = 10;
  let y = 10;
  const step = 10;

  function updatePosition() {
    player.style.transform = 'translate(' + x + 'px,' + y + 'px)';
  }

  function handleKey(e) {
    const key = e.key.toLowerCase();
    const rect = area.getBoundingClientRect();
    const size = 30; // taille du carré (cf. CSS)
    const maxX = rect.width - size - 4;
    const maxY = rect.height - size - 4;

    if (key === 'arrowup' || key === 'z') {
      y = Math.max(0, y - step);
    } else if (key === 'arrowdown' || key === 's') {
      y = Math.min(maxY, y + step);
    } else if (key === 'arrowleft' || key === 'q') {
      x = Math.max(0, x - step);
    } else if (key === 'arrowright' || key === 'd') {
      x = Math.min(maxX, x + step);
    }
    updatePosition();
  }

  window.addEventListener('keydown', handleKey);
  updatePosition();
})();

// ==========================
// Démo projets 1 : Filtre de liste
// ==========================
(function () {
  const input = document.getElementById('projects-filter-input');
  const items = document.querySelectorAll('#projects-filter-list li');

  if (!input || items.length === 0) return;

  input.addEventListener('input', function () {
    const query = input.value.toLowerCase().trim();
    items.forEach(function (li) {
      const text = li.textContent.toLowerCase();
      li.style.display = text.includes(query) ? '' : 'none';
    });
  });
})();

// ==========================
// Démo projets 2 : Mini planning cliquable
// ==========================
(function () {
  const container = document.getElementById('mini-planning');
  const output = document.getElementById('mini-planning-output');

  if (!container || !output) return;

  function updateOutput() {
    const active = container.querySelectorAll('.mini-planning-slot.is-active');
    if (active.length === 0) {
      output.textContent = 'Aucun créneau sélectionné pour le moment.';
      return;
    }
    const labels = Array.prototype.map.call(active, function (slot) {
      return slot.textContent.replace(/\s+/g, ' ').trim();
    });
    output.textContent = 'Créneaux sélectionnés : ' + labels.join(', ');
  }

  container.addEventListener('click', function (e) {
    const target = e.target.closest('.mini-planning-slot');
    if (!target) return;
    target.classList.toggle('is-active');
    updateOutput();
  });

  updateOutput();
})();
</script>

</body>
</html>
