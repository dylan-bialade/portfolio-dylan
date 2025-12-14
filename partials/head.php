<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Bialadev Studio – Portfolio';
}
if (!isset($pageDescription)) {
    $pageDescription = "Portfolio de Dylan Bialade – développement web, projets, mini-démos et services freelance.";
}
if (!isset($pageRobots)) {
    $pageRobots = 'index,follow';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="robots" content="<?php echo htmlspecialchars($pageRobots, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Favicon éventuel -->
  <!-- <link rel="icon" href="/assets/img/favicon.ico"> -->

  <!-- CSS principal -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/live/live.css">

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-XRK50F6YD0"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XRK50F6YD0');
  </script>
</head>
