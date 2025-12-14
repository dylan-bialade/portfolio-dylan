<?php
$currentPage = 'about';
$pageTitle = "À propos";
?>
<?php require __DIR__ . '/partials/header.php'; ?>

<main>

  <!-- HERO (utilise .hero / .hero-inner / .hero-panel) -->
  <section class="hero">
    <div class="container hero-inner">
      <div class="hero-text">
        <div class="hero-chip">Profil professionnel</div>
        <h1>À propos</h1>

        <p class="hero-subtitle">
          Je m’appelle <strong>Dylan Bialade</strong>. Je suis un profil <strong>sérieux</strong> et <strong>impliqué</strong> :
          je m’investis pleinement dans chaque poste que j’occupe, que ce soit en développement, en support, en vente,
          en animation ou en restauration.
        </p>

        <div class="hero-cta">
          <a class="btn btn-primary" href="/projects.php">Voir mes projets</a>
          <a class="btn btn-outline" href="/contact.php">Me contacter</a>
          <a class="btn btn-outline" href="https://github.com/dylan-bialade" target="_blank" rel="noopener">GitHub</a>
        </div>

        <p class="hero-note">
          Mon objectif : apporter de la fiabilité, une vraie mentalité “terrain” et livrer des solutions utiles, maintenables et propres.
        </p>
      </div>

      <aside class="hero-panel">
        <div class="hero-chip">Ce que tu peux attendre de moi</div>
        <h2>Sérieux, constance, et sens du service</h2>
        <ul class="hero-list">
          <li><strong>Implication :</strong> je prends les responsabilités à cœur (entreprise familiale, équipe, procédures).</li>
          <li><strong>Polyvalence :</strong> web/API, scripts, Android/Arduino + expérience client et opérationnelle.</li>
          <li><strong>Esprit “résolution” :</strong> diagnostic, priorisation, communication claire (support / ticketing).</li>
          <li><strong>Fiabilité :</strong> capable de tenir le rythme et la qualité même en environnement exigeant.</li>
        </ul>
      </aside>
    </div>
  </section>

  <!-- SECTION : Ce qui me définit -->
  <section class="section">
    <div class="container">
      <h2>Ce qui me définit</h2>
      <p class="section-intro">
        Je combine une base technique (développement, automatisation, support) avec une vraie expérience “terrain”
        (vente, animation, restauration). Résultat : je suis à l’aise autant dans la production que dans la relation et l’exécution.
      </p>

      <div class="grid services-grid">
        <div class="card card-service">
          <h3>Sérieux & implication</h3>
          <p>
            Je m’implique dans n’importe quel poste : respect des consignes, fiabilité, constance, et volonté d’apprendre vite.
          </p>
        </div>

        <div class="card card-service">
          <h3>Entreprise familiale depuis mes 14 ans</h3>
          <p>
            J’ai grandi avec la culture du travail : présence régulière, sens des responsabilités, et exigence de résultat.
            (Sur mes CV, Les Serres du Jansau apparaît sur plusieurs périodes, dont une activité continue en vente/conseil). 
          </p>
        </div>

        <div class="card card-service">
          <h3>Culture du service</h3>
          <p>
            Support/ticketing, vente, animation : je sais écouter, reformuler, expliquer et trouver une solution. La qualité
            de service est un réflexe chez moi.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION ALT : Expériences (preuves) -->
  <section class="section section-alt">
    <div class="container">
      <h2>Mes expériences (preuves concrètes)</h2>
      <p class="section-intro">
        Chaque expérience m’a apporté une compétence transférable : discipline, gestion du stress, relationnel, rigueur,
        et capacité à livrer.
      </p>

      <div class="grid projects-grid">

        <div class="card project-card">
          <h2>Les Serres du Jansau (Gaillac) — Vente / conseil horticulture</h2>
          <div class="project-tech">Entreprise familiale • Relation client • Encaissement</div>
          <p>
            Conseil et vente, encaissement, accueil, relation client. Expérience sur plusieurs années (2020–2025 sur un de tes CV),
            et sur différentes périodes dans tes autres CV.
          </p>
        </div>

        <div class="card project-card">
          <h2>Les Serres du Jansau — Arduino & Android</h2>
          <div class="project-tech">Embarqué • Mobile • RGPD</div>
          <p>
            Développement d’une solution technique d’analyse/mesure liée à l’arrosage + application Android (création/gestion comptes,
            logique RGPD selon CV).
          </p>
        </div>

        <div class="card project-card">
          <h2>Ocsalis — Web / API & scripts PowerShell</h2>
          <div class="project-tech">Automatisation • Serveurs • API OVH</div>
          <p>
            Scripts PowerShell de configuration de serveurs et développement d’une solution web/API pour la gestion de serveurs OVH
            (selon tes CV).
          </p>
        </div>

        <div class="card project-card">
          <h2>Quincaillerie Angles — Support SI / ticketing</h2>
          <div class="project-tech">Incidents • Dépannage • Service</div>
          <p>
            Gestion des incidents, dépannage, installations, outils de ticketing. Une vraie école du diagnostic et de la communication.
          </p>
        </div>

        <div class="card project-card">
          <h2>McDonald’s (Aurillac) — Équipier polyvalent (CDI)</h2>
          <div class="project-tech">Rythme • Procédures • Esprit d’équipe</div>
          <p>
            Service et préparation en respectant des protocoles stricts, travail en équipe, gestion du stress et du temps.
          </p>
        </div>

        <div class="card project-card">
          <h2>Animation — COUGOUS / RECREA’BRENS</h2>
          <div class="project-tech">Responsabilité • Pédagogie • Gestion de groupe</div>
          <p>
            Encadrement et animation d’enfants (6–14 ans) sur périodes de vacances scolaires : organisation, sécurité, communication.
          </p>
        </div>

        <div class="card project-card">
          <h2>EDS Électronique — Réparation / maintenance</h2>
          <div class="project-tech">Diagnostic • Matériel • Rigueur</div>
          <p>
            Réparation de matériel multimédia et approche technique terrain.
          </p>
        </div>

        <div class="card project-card">
          <h2>Chambre des Métiers et de l’Artisanat</h2>
          <div class="project-tech">Cadre pro • Méthode</div>
          <p>
            Expérience en structure (mentionnée sur tes CV), dans un cadre plus institutionnel.
          </p>
        </div>

      </div>

      <div class="section-cta-center">
        <a class="btn btn-primary" href="/projects.php">Voir mes projets</a>
        <a class="btn btn-outline" href="/contact.php">Me contacter</a>
      </div>
    </div>
  </section>

  <!-- SECTION : Compétences -->
  <section class="section">
    <div class="container">
      <h2>Compétences</h2>
      <p class="section-intro">
        Base technique + soft skills opérationnels : je suis efficace dans une équipe, et fiable dans l’exécution.
      </p>

      <div class="grid skills-grid">
        <div class="card">
          <h3>Développement</h3>
          <p>PHP, SQL, Java, JavaScript, HTML/CSS, Android, Arduino (selon tes CV).</p>
        </div>

        <div class="card">
          <h3>Automatisation / SI</h3>
          <p>PowerShell, configurations, logique serveurs, support et ticketing.</p>
        </div>

        <div class="card">
          <h3>Soft skills</h3>
          <p>Esprit d’équipe, autonomie, adaptation, gestion du temps, communication, accueil & relation client.</p>
        </div>

        <div class="card">
          <h3>Centres d’intérêt</h3>
          <p>Échecs (logique), lecture, jeux vidéo, natation, musculation, scoutisme.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="section section-alt">
    <div class="container">
      <h2>Travaillons ensemble</h2>
      <p class="section-intro">
        Si tu recherches quelqu’un de sérieux, impliqué et fiable — capable de tenir le rythme, de communiquer clairement,
        et de livrer — je suis disponible pour en discuter.
      </p>

      <div class="hero-cta">
        <a class="btn btn-primary" href="/contact.php">Me contacter</a>
        <a class="btn btn-outline" href="/tarifs.php">Voir mes tarifs</a>
        <a class="btn btn-outline" href="/projects.php">Voir mes projets</a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
