<?php
// auth/login.php
session_start();
require __DIR__ . '/../config/db.php';

$currentPage     = '';
$pageTitle       = 'Connexion – Bialadev Studio';
$pageDescription = 'Connexion à votre compte joueur pour le mini-jeu Cookie Dev.';
$pageRobots      = 'noindex,follow';

$errors = [];
$email  = '';

// URL vers laquelle rediriger après login (par exemple devcookie)
$redirect = $_GET['redirect'] ?? '/devcookie.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail n'est pas valide.";
    } else {
        $stmt = $pdo->prepare('SELECT id, pseudo, password_hash FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = "Identifiants incorrects.";
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['pseudo']  = $user['pseudo'];

            header('Location: ' . $redirect);
            exit;
        }
    }
}

include __DIR__ . '/../partials/head.php';
?>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main>
  <section class="section">
    <div class="container">
      <h1>Connexion</h1>
      <p>
        Connectez-vous pour <strong>retrouver votre progression</strong> sur le mini-jeu Cookie Dev.
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

      <form method="post" class="form">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required
                 value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary">Se connecter</button>
      </form>

      <p style="margin-top:1rem;">
        Pas encore de compte ? <a href="/auth/register.php">Créer un compte</a>.
      </p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
