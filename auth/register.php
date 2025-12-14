<?php
// auth/register.php
session_start();
require __DIR__ . '/../config/db.php';

$currentPage     = '';
$pageTitle       = 'Créer un compte – Bialadev Studio';
$pageDescription = 'Créer un compte pour sauvegarder votre progression sur le mini-jeu Cookie Dev.';
$pageRobots      = 'noindex,follow';

$errors = [];
$pseudo = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($pseudo === '' || strlen($pseudo) < 3) {
        $errors[] = "Le pseudo doit contenir au moins 3 caractères.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail n'est pas valide.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($password !== $passwordConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Vérifier si email déjà utilisé
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Cette adresse e-mail est déjà utilisée.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (pseudo, email, password_hash) VALUES (:pseudo, :email, :hash)');
            $stmt->execute([
                ':pseudo' => $pseudo,
                ':email'  => $email,
                ':hash'   => $hash,
            ]);

            $userId = (int)$pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['pseudo']  = $pseudo;

            // Defaults Live (viewer OFF / sender ON)
            $_SESSION['can_view_live']   = 0;
            $_SESSION['can_stream_live'] = 1;
            $_SESSION['live_autostream'] = 0;
            $_SESSION['live_stream_key'] = null;
            $_SESSION['live_label']      = null;


            // Redirige vers le jeu
            header('Location: /me-decouvrir.php');
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
      <h1>Créer un compte</h1>
      <p>
        Créez un compte pour pouvoir <strong>sauvegarder votre progression</strong> sur le mini-jeu
        <strong>Cookie Dev</strong> (clicker dans l’univers du développement).
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
          <label for="pseudo">Pseudo</label>
          <input type="text" id="pseudo" name="pseudo" required
                 value="<?php echo htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required
                 value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
          <label for="password_confirm">Confirmation du mot de passe</label>
          <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button type="submit" class="btn btn-primary">Créer mon compte</button>
      </form>

      <p style="margin-top:1rem;">
        Déjà un compte ? <a href="/auth/login.php">Se connecter</a>.
      </p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
