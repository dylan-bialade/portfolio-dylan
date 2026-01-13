<?php
$currentPage = 'contact';

// Initialisation
$values = [
    'email'   => '',
    'phone'   => '',
    'type'    => '',
    'message' => '',
];
$errors = [];
$success = false;

// Libellés lisibles pour l'email + affichage
$typeLabels = [
    ''              => 'Non précisé',
    'site_vitrine'  => 'Création de site vitrine',
    'mise_a_jour_site' => 'Mise à jour / refonte d’un site existant',
    'outil_metier'  => 'Outil métier sur mesure',
    'ia_automation' => 'Mise en place IA / automatisation',
    'coaching_bts'  => 'Coaching / remise à niveau (BTS dev ou réseau)',
    'autre'         => 'Autre / à préciser',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['email']   = trim($_POST['email']   ?? '');
    $values['phone']   = trim($_POST['phone']   ?? '');
    $values['type']    = trim($_POST['type']    ?? '');
    $values['message'] = trim($_POST['message'] ?? '');

    // Validation
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez indiquer une adresse e-mail valide.";
    }

    if ($values['message'] === '') {
        $errors[] = "Merci de décrire brièvement votre projet ou votre besoin.";
    }

    if (empty($errors)) {
        $to      = 'contact@bialadev.fr'; // adresse de réception
        $subject = 'Nouveau message depuis Bialadev Studio';

        $typeLabel = $typeLabels[$values['type']] ?? ('Autre / à préciser (' . $values['type'] . ')');

        $body =
            "Nouveau message depuis le formulaire de contact du site Bialadev Studio:\n\n" .
            "E-mail : " . $values['email'] . "\n" .
            "Téléphone : " . ($values['phone'] ?: 'Non renseigné') . "\n" .
            "Demande : " . $typeLabel . "\n\n" .
            "Message :\n" .
            $values['message'] . "\n";

        $from = 'contact@bialadev.fr';

        $headers = "From: Bialadev Studio <" . $from . ">\r\n" .
                   "Reply-To: " . $values['email'] . "\r\n" .
                   "Content-Type: text/plain; charset=utf-8\r\n";

        $sent = @mail($to, $subject, $body, $headers);

        if ($sent) {
            $success = true;
            $values = [
                'email'   => '',
                'phone'   => '',
                'type'    => '',
                'message' => '',
            ];
        } else {
            $errors[] = "L’e-mail n’a pas pu être envoyé. Vous pouvez m’écrire directement à contact@bialadev.fr.";
        }
    }
}
?>
<?php
$currentPage     = 'contact';
$pageTitle       = 'Contact – Bialadev Studio | Discuter de votre projet';
$pageDescription = "Contactez Bialadev Studio pour un site vitrine, un outil métier, une automatisation/IA ou un accompagnement (coaching BTS dev/réseau).";
$pageRobots      = 'index,follow';

include __DIR__ . '/partials/head.php';
?>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>
<main>
  <section class="section">
    <div class="container">
      <h1>Me contacter</h1>
      <p class="section-intro">
        Vous avez un projet de site, un outil métier, une automatisation (IA) ou vous cherchez un accompagnement
        pour vous mettre à niveau (BTS dev/réseau) ? Remplissez ce formulaire et je vous répondrai rapidement.
      </p>
      <p class="section-intro">
        Les tarifs indiqués sur la page <a href="tarifs.php">Tarifs</a> sont
        <strong>indicatifs</strong> et peuvent être adaptés à votre situation.
        N’hésitez pas à décrire votre besoin : je vous répondrai avec une estimation
        puis, si vous le souhaitez, un devis plus précis.
      </p>

      <?php if ($success): ?>
        <div class="alert alert-success">
          Merci, votre message a bien été envoyé. Je reviendrai vers vous dès que possible.
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="form card form-contact" novalidate>
        <div class="form-row">
          <label for="email">Votre e-mail *</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            value="<?php echo htmlspecialchars($values['email']); ?>"
            placeholder="nom@exemple.fr"
          />
        </div>

        <div class="form-row">
          <label for="phone">Votre téléphone (optionnel)</label>
          <input
            type="text"
            id="phone"
            name="phone"
            value="<?php echo htmlspecialchars($values['phone']); ?>"
            placeholder="06…"
          />
        </div>

        <div class="form-row">
          <label for="type">Type de demande</label>
          <select id="type" name="type">
            <option value="">Sélectionnez une option</option>

            <option value="site_vitrine" <?php echo $values['type'] === 'site_vitrine' ? 'selected' : ''; ?>>
              Création de site vitrine
            </option>

            <option value="mise_a_jour_site" <?php echo $values['type'] === 'mise_a_jour_site' ? 'selected' : ''; ?>>
              Mise à jour / refonte d’un site existant
            </option>

            <option value="outil_metier" <?php echo $values['type'] === 'outil_metier' ? 'selected' : ''; ?>>
              Outil métier sur mesure
            </option>

            <!-- NOUVEAU -->
            <option value="ia_automation" <?php echo $values['type'] === 'ia_automation' ? 'selected' : ''; ?>>
              Mise en place IA / automatisation
            </option>

            <!-- NOUVEAU -->
            <option value="coaching_bts" <?php echo $values['type'] === 'coaching_bts' ? 'selected' : ''; ?>>
              Coaching / remise à niveau (BTS dev ou réseau)
            </option>

            <option value="autre" <?php echo $values['type'] === 'autre' ? 'selected' : ''; ?>>
              Autre / à préciser
            </option>
          </select>
        </div>

        <div class="form-row">
          <label for="message">Votre besoin *</label>
          <textarea
            id="message"
            name="message"
            rows="6"
            required
            placeholder="Décrivez en quelques lignes votre activité, votre besoin et vos objectifs…"
          ><?php echo htmlspecialchars($values['message']); ?></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Envoyer</button>
          <p class="form-note">
            * Champs obligatoires. Vos informations ne sont utilisées que pour répondre à votre demande.
          </p>
        </div>
      </form>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
