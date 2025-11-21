<?php
// Valeurs par défaut si non définies dans la page
if (!isset($pageTitle)) {
    $pageTitle = 'Bialadev Studio – Développeur web freelance';
}
if (!isset($pageDescription)) {
    $pageDescription = '';
}
if (!isset($pageRobots)) {
    $pageRobots = 'index,follow';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

  <?php if ($pageDescription !== ''): ?>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
  <?php endif; ?>

  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="<?php echo htmlspecialchars($pageRobots, ENT_QUOTES, 'UTF-8'); ?>" />
  <link rel="stylesheet" href="style.css" />

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-XRK50F6YD0"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XRK50F6YD0');
  </script>
</head>
