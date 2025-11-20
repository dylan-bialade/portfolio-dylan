<?php
// Traitement du formulaire
$errors = [];
$success = false;

// Valeurs par défaut pour garder les champs remplis en cas d'erreur
$values = [
    'email'   => '',
    'phone'   => '',
    'offer'   => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération & nettoyage
    $values['email']   = trim($_POST['email'] ?? '');
    $values['phone']   = trim($_POST['phone'] ?? '');
    $values['offer']   = trim($_POST['offer'] ?? '');
    $values['message'] = trim($_POST['message'] ?? '');

    // Validation simple
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Merci d’indiquer une adresse e-mail valide.";
    }

    if ($values['phone'] === '') {
        $errors[] = "Merci d’indiquer un numéro de téléphone.";
    }

    if ($values['message'] === '') {
        $errors[] = "Merci de décrire votre projet et vos besoins.";
    }

    // Si pas d’erreur, on "enverrait" l'email
    if (empty($errors)) {
        $to      = 'contact@bialadev.fr'; // tu recevras tout ici
        $subject = 'Nouveau projet depuis Bialadev Studio';
        $body    =
            "Nouveau message depuis le formulaire de contact du portfolio :\n\n" .
            "E-mail : " . $values['email'] . "\n" .
            "Téléphone : " . $values['phone'] . "\n" .
            "Offre / type de projet : " . ($values['offer'] ?: 'Non précisé') . "\n\n" .
            "Description du projet et des besoins :\n" .
            $values['message'] . "\n";

        $from = 'contact@bialadev.fr'; // adresse expéditrice cohérente avec ton domaine

        $headers = "From: Bialadev Studio <" . $from . ">\r\n" .
                "Reply-To: " . $values['email'] . "\r\n" .
                "Content-Type: text/plain; charset=utf-8\r\n";

        $sent = @mail($to, $subject, $body, $headers);

        if ($sent) {
            $success = true;
            $values = [
                'email'   => '',
                'phone'   => '',
                'offer'   => '',
                'message' => '',
            ];
        } else {
            $errors[] = "L’e-mail n’a pas pu être envoyé. Vous pouvez aussi me contacter directement à contact@bialadev.fr.";
        }

    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Contact – Bialadev Studio | Création de sites et outils web</title>
  <meta name="description"
        content="Contactez Bialadev Studio pour un projet de site vitrine, la mise à jour d’un site existant ou le développement d’un petit outil métier sur mesure dans le Tarn." />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="index, follow" />
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="logo">
        <span class="logo-mark">&lt;/&gt;</span>
        <span class="logo-text">Dylan Bialade</span>
      </div>
      <nav class="nav">
        <a href="index.html" class="nav-link">Accueil</a>
        <a href="projects.html" class="nav-link">Projets</a>
        <a href="veille.php" class="nav-link">Veille</a>
        <a href="contact.php" class="nav-link active">Contact</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="section">
      <div class="container">
        <h1 class="page-title">Prendre contact</h1>
        <p class="section-intro">
          Vous avez un projet de site, un outil métier à développer, ou besoin de faire évoluer
          une solution existante&nbsp;? Ce formulaire permet de me transmettre vos coordonnées,
          l’offre qui vous intéresse et un descriptif de votre projet et de vos besoins.
        </p>

        <?php if ($success): ?>
          <div class="form-alert success">
            🎉 Merci, votre message a bien été pris en compte. Je vous recontacterai dès que possible.
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="form-alert error">
            <p><strong>Merci de corriger les points suivants :</strong></p>
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="form-card">
          <form method="post" action="contact.php" class="contact-form" novalidate>
            <!-- Email -->
            <div class="form-group">
              <label for="email">Adresse e-mail <span class="required">*</span></label>
              <input
                type="email"
                id="email"
                name="email"
                required
                value="<?php echo htmlspecialchars($values['email']); ?>"
                placeholder="votre.email@example.com"
              />
            </div>

            <!-- Téléphone -->
            <div class="form-group">
              <label for="phone">Numéro de téléphone <span class="required">*</span></label>
              <input
                type="tel"
                id="phone"
                name="phone"
                required
                value="<?php echo htmlspecialchars($values['phone']); ?>"
                placeholder="06 00 00 00 00"
              />
            </div>

            <!-- Offre / type de projet -->
            <div class="form-group">
              <label for="offer">Type de projet / offre qui vous intéresse</label>
              <select id="offer" name="offer">
                <option value="" <?php echo $values['offer'] === '' ? 'selected' : ''; ?>>Choisir…</option>
                <option value="Site vitrine"
                  <?php echo $values['offer'] === 'Site vitrine' ? 'selected' : ''; ?>>
                  Création d’un site vitrine
                </option>
                <option value="Mise à jour de site"
                  <?php echo $values['offer'] === 'Mise à jour de site' ? 'selected' : ''; ?>>
                  Mise à jour / évolution d’un site existant
                </option>
                <option value="Outil métier"
                  <?php echo $values['offer'] === 'Outil métier' ? 'selected' : ''; ?>>
                  Développement d’un petit outil métier
                </option>
                <option value="Autre"
                  <?php echo $values['offer'] === 'Autre' ? 'selected' : ''; ?>>
                  Autre (à préciser dans la description)
                </option>
              </select>
            </div>

            <!-- Description du projet -->
            <div class="form-group">
              <label for="message">
                Décrivez votre projet et vos besoins <span class="required">*</span>
              </label>
              <textarea
                id="message"
                name="message"
                rows="7"
                required
                placeholder="Contexte, objectifs, fonctionnalités souhaitées, délais approximatifs…"
              ><?php echo htmlspecialchars($values['message']); ?></textarea>
            </div>

            <!-- Bouton -->
            <div class="form-actions">
              <button type="submit" class="btn primary">Envoyer ma demande</button>
            </div>

            <p class="form-note">
              Les champs marqués par <span class="required">*</span> sont obligatoires.  
              Vos coordonnées ne sont utilisées que pour vous recontacter à propos de votre projet.
            </p>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-inner">
      <p>© <span id="year"></span> – Dylan Bialade. Tous droits réservés.</p>
      <p class="footer-note">Formulaire de contact dédié aux demandes de projet.</p>
    </div>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>
