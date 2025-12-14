<?php
$currentPage = 'about';
$pageTitle = "À propos";
?>
<?php require __DIR__ . '/partials/header.php'; ?>

<main class="main about">

  <!-- INTRO / POSITIONNEMENT -->
  <section class="section">
    <div class="container">
      <h1>À propos</h1>

      <p class="about-lead">
        Je suis <strong>Dylan Bialade</strong>, étudiant en <strong>développement</strong> (BTS SIO option SLAM) avec une
        vraie culture du <strong>terrain</strong>. Ce qui me caractérise le plus : je suis <strong>sérieux</strong>,
        <strong>impliqué</strong>, et je m’investis pleinement dans chaque poste que j’occupe — que ce soit en dev, en support,
        en vente, en animation ou en restauration.
      </p>

      <div class="about-badges">
        <span class="about-badge">Fiabilité / régularité</span>
        <span class="about-badge">Sens du service</span>
        <span class="about-badge">Travail d’équipe</span>
        <span class="about-badge">Autonomie</span>
        <span class="about-badge">Résolution de problèmes</span>
      </div>

      <div class="about-actions">
        <a class="btn" href="/projects.php">Voir mes projets</a>
        <a class="btn btn-outline" href="/contact.php">Me contacter</a>
        <a class="btn btn-ghost" href="https://github.com/dylan-bialade" target="_blank" rel="noopener">GitHub</a>
      </div>
    </div>
  </section>

  <!-- SERIEUX / IMPLICATION (LA PARTIE QUE TU VEUX VRAIMENT METTRE EN AVANT) -->
  <section class="section">
    <div class="container">
      <h2>Mon sérieux, ce n’est pas une phrase : c’est un historique</h2>

      <div class="about-grid">
        <div class="about-card">
          <h3>Entreprise familiale : constance et sens des responsabilités</h3>
          <p>
            J’ai travaillé dans l’entreprise familiale (Les Serres du Jansau) sur la durée, avec des missions concrètes :
            <strong>conseil client</strong>, <strong>vente</strong>, <strong>encaissement</strong>, tenue et organisation.
            C’est une expérience qui m’a appris à être fiable, à m’impliquer, et à “faire tourner” une activité au quotidien.
            :contentReference[oaicite:4]{index=4}
          </p>
          <ul class="about-list">
            <li><strong>Relation client</strong> : écoute, compréhension du besoin, conseil</li>
            <li><strong>Rigueur</strong> : caisse, procédures, qualité de service</li>
            <li><strong>Implication</strong> : on ne “bâcle” pas quand l’activité dépend de toi</li>
          </ul>
          <p class="about-note">
            Tu m’as indiqué que tu y travailles <strong>depuis tes 14 ans</strong> : je peux l’afficher tel quel sur le site (c’est très valorisant),
            ou le formuler plus neutre (“depuis l’adolescence”) si tu préfères.
          </p>
        </div>

        <div class="about-card">
          <h3>Restauration : discipline, rythme, esprit d’équipe</h3>
          <p>
            Mon CDI d’équipier polyvalent chez McDonald’s m’a renforcé sur le <strong>rythme</strong>, la <strong>discipline</strong>,
            le <strong>respect des protocoles</strong> et la <strong>gestion du stress</strong> — des qualités directement utiles en entreprise.
            :contentReference[oaicite:5]{index=5}
          </p>
          <ul class="about-list">
            <li>Travail d’équipe, réactivité, fiabilité</li>
            <li>Gestion du temps, procédures, qualité</li>
            <li>Tenir un niveau constant, même en rush</li>
          </ul>
        </div>

        <div class="about-card">
          <h3>Animation : pédagogie, responsabilité, confiance</h3>
          <p>
            L’animation (contrats d’engagement à l’éducation) m’a appris la <strong>responsabilité</strong>, la <strong>pédagogie</strong>
            et la gestion de groupe (jeunes 6–14 ans). Ça prouve ma capacité à prendre en charge, organiser et communiquer clairement.
            :contentReference[oaicite:6]{index=6}
          </p>
          <ul class="about-list">
            <li>Gestion de groupe, sécurité, organisation</li>
            <li>Communication claire, posture responsable</li>
            <li>Transmission (utile en équipe dev aussi)</li>
          </ul>
        </div>

        <div class="about-card">
          <h3>Support / SI : diagnostic et sens du service</h3>
          <p>
            En environnement SI (Quincaillerie Angles), j’ai fait de la <strong>gestion d’incidents</strong> et du dépannage, avec logique
            service et outils de ticketing selon CV. Ça développe un vrai réflexe : comprendre vite, résoudre, et expliquer.
            :contentReference[oaicite:7]{index=7} :contentReference[oaicite:8]{index=8}
          </p>
          <ul class="about-list">
            <li>Analyse rapide, priorisation, résolution</li>
            <li>Communication avec utilisateurs / équipes</li>
            <li>Pragmatisme : résultat et continuité de service</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- EXPERIENCES TECH (EN MODE “PREUVES”) -->
  <section class="section">
    <div class="container">
      <h2>Expériences techniques</h2>

      <div class="about-timeline">
        <div class="about-timeline-item">
          <div class="about-timeline-tag">Web / Scripts</div>
          <h3>Ocsalis — scripts PowerShell & web/API (OVH)</h3>
          <p>
            Développement de scripts PowerShell de configuration serveurs + développement d’une solution web/API pour la gestion de serveurs OVH.
            :contentReference[oaicite:9]{index=9}
          </p>
        </div>

        <div class="about-timeline-item">
          <div class="about-timeline-tag">Arduino / Android</div>
          <h3>EURL Les Serres du Jansau — solution d’analyse arrosage & appli Android</h3>
          <p>
            Développement d’une solution technique d’analyse de l’arrosage + application Android (gestion de comptes / fidélisation, logique RGPD selon CV).
            :contentReference[oaicite:10]{index=10}
          </p>
        </div>

        <div class="about-timeline-item">
          <div class="about-timeline-tag">Électronique</div>
          <h3>EDS Électronique — réparation & maintenance</h3>
          <p>
            Réparation de matériel multimédia / approche technique terrain.
            :contentReference[oaicite:11]{index=11}
          </p>
        </div>

        <div class="about-timeline-item">
          <div class="about-timeline-tag">Structure / environnement pro</div>
          <h3>Chambre des Métiers et de l’Artisanat — expérience en structure</h3>
          <p>
            Expérience mentionnée sur tes CV (Onet-le-Château).
            :contentReference[oaicite:12]{index=12}
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- COMPETENCES + PROFIL -->
  <section class="section">
    <div class="container">
      <h2>Compétences & posture professionnelle</h2>

      <div class="about-grid">
        <div class="about-card">
          <h3>Technos / domaines</h3>
          <p>
            PHP, SQL, Java, JavaScript, HTML/CSS, PowerShell, Arduino, Android (selon CV).
            :contentReference[oaicite:13]{index=13} :contentReference[oaicite:14]{index=14}
          </p>
        </div>

        <div class="about-card">
          <h3>Soft skills (ce qui me rend efficace)</h3>
          <ul class="about-list">
            <li><strong>Sérieux & implication</strong> : je prends les choses à cœur</li>
            <li><strong>Fiabilité</strong> : ponctualité, constance, respect des consignes</li>
            <li><strong>Esprit d’équipe</strong> : coordination, entraide, communication</li>
            <li><strong>Autonomie</strong> : je cherche, je teste, je reviens avec une solution</li>
            <li><strong>Service</strong> : je pense “utilisateur / client” et résultat</li>
          </ul>
          <p class="about-note">
            Plusieurs de ces qualités sont explicitement présentes sur tes CV (accueil, communication, autonomie, adaptation, gestion du temps).
            :contentReference[oaicite:15]{index=15}
          </p>
        </div>

        <div class="about-card">
          <h3>Centres d’intérêt (et ce que ça dit de moi)</h3>
          <p>
            Échecs (logique/stratégie), lecture (curiosité), sport (discipline), scoutisme (engagement).
            :contentReference[oaicite:16]{index=16} :contentReference[oaicite:17]{index=17}
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="section">
    <div class="container">
      <h2>Si tu recherches quelqu’un de fiable et impliqué</h2>
      <p>
        Que ce soit pour un projet web / logiciel, ou une mission plus “terrain”, je suis à l’aise dans les environnements qui demandent
        du sérieux, de la rigueur, et une vraie implication.
      </p>
      <div class="about-actions">
        <a class="btn" href="/contact.php">Me contacter</a>
        <a class="btn btn-outline" href="/projects.php">Voir mes projets</a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
