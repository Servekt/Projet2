<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'])) die('CSRF invalide.');

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Refuser les balises HTML dans le nom d'utilisateur
    if ($username !== strip_tags($username)) {
        die("Le nom d'utilisateur ne doit pas contenir de balises HTML.");
    }

    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'etudiant'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (!empty($user['password'])) {
            die("Ce compte a déjà été activé.");
        }
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    } else {
        // Créer un nouveau compte
        // Vérifier si ce nom est déjà pris (double vérification)
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            die("Ce nom d'utilisateur est déjà pris.");
        }
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'etudiant')");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    }
    file_put_contents(__DIR__ . '/logs/security.log', "[" . date("Y-m-d H:i:s") . "] Inscription étudiant pour $username\n", FILE_APPEND);
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <title>Inscription Étudiant</title>
</head>
<body class="page-centered">
<div class="form-container">
<h2>Inscription Étudiant</h2>
<form method="POST">
    <input name="username" placeholder="Nom d'utilisateur" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <button type="submit">S'inscrire</button>
</form>
<a href="login.php">← Retour à la connexion</a>
</div>
</body>
</html>
