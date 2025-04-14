<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/csrf.php';
require_once 'includes/auth.php';

require_login();
if (!is_admin()) {
    die("Accès refusé.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Refuser les balises HTML
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($username !== strip_tags($username)) {
        die("Le nom d'utilisateur ne doit pas contenir de balises HTML.");
    }

    // Vérifier si ce nom existe déjà
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        die("Ce nom d'utilisateur est déjà pris.");
    }

    // Vérification soumission formulaire
    if (!check_csrf_token($_POST['csrf_token'])) die('CSRF invalide.');

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'prof')");
    $stmt->execute([
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT)
    ]);
    file_put_contents(__DIR__ . '/logs/security.log', "[" . date("Y-m-d H:i:s") . "] Création de compte professeur pour $username\n", FILE_APPEND);
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="style.css">
<title>Inscription Prof</title></head><body class="page-centered">
<div class="form-container">
<h2>Inscription Professeur</h2>
<form method="POST">
    <input name="username" placeholder="Nom d'utilisateur professeur" required><br>
    <input name="password" type="password" placeholder="Mot de passe" required><br>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <button type="submit">S'inscrire</button>
</form>
<a href="dashboard.php">← Retour au tableau de bord</a>
</div>
</body></html>