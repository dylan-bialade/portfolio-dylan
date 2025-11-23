<?php
// auth/login.php
session_start();

// Si tu veux empêcher un utilisateur déjà connecté de voir la page de login,
// tu peux décommenter ce bloc plus tard. Pour l'instant on le laisse COMMENTÉ.
/*
if (isset($_SESSION['user_id'])) {
    header('Location: /games.php');
    exit;
}
*/

require __DIR__ . '/../config/db.php';

$errors = [];
$email = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/games.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = "Veuillez renseigner l'email et le mot de passe.";
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, pseudo, email, password_hash FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Erreur de connexion à la base de données.";
            $user = null;
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            // Connexion OK
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo']  = $user['pseudo'];

            // Redirection après login
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = "Identifiants incorrects.";
        }
    }
}

// Variables pour le head.php
$currentPage     = ''; // ou 'auth' si tu as une rubrique
$pageTitle       = 'Connexion – Bialadev Studio';
$pageDescription = 'Connexion au compte Bialadev Studio pour accéder aux jeux et à la sauvegarde.';
$pageRobots      = 'noindex,nofollow';

include __DIR__ . '/../partials/head.php';
?>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container" style="max-width: 480px;">
      <h1>Connexion</h1>
      <p class="section-intro">
        Connectez-vous pour sauvegarder votre progression, apparaître dans les classements et
        accéder pleinement aux démos.
      </p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="/auth/login.php">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input
            type="password"
            id="password"
            name="password"
            required
          >
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">
          Se connecter
        </button>
      </form>

      <p class="muted" style="margin-top:1rem;">
        Pas encore de compte ?
        <a href="/auth/register.php?redirect=/games.php">Créer un compte</a>
      </p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
