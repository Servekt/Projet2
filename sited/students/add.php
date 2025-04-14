<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_login();
if (!is_admin() && !is_prof()) die("Accès refusé.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'])) die('CSRF invalide.');

    $username = $_POST['username'];
    $grade = $_POST['grade'];

    // Refuser les balises HTML
    // Vérifier si l'utilisateur existe déjà
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        die("Ce nom d'utilisateur est déjà pris.");
    }
    if ($username !== strip_tags($username)) {
        die("Le nom d'utilisateur ne doit pas contenir de balises HTML.");
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, grade) VALUES (?, ?, 'etudiant', ?)");
    $stmt->execute([$username, '', $grade]);
    file_put_contents(__DIR__ . '/../logs/security.log', "[" . date("Y-m-d H:i:s") . "] Ajout étudiant par " . ($_SESSION['user_id'] ?? 'inconnu') . " : $username\n", FILE_APPEND);
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../style.css">
    <title>Ajouter un étudiant</title>
</head>
<body class="page-centered">
<div class="form-container">
<h2>Ajouter un étudiant</h2>
<form method="POST">
    <input name="username" placeholder="Nom d'utilisateur" required><br>
    <input name="grade" type="number" step="0.01" placeholder="Note" required><br>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <button type="submit">Ajouter</button>
</form>
<a href="list.php">← Retour à la liste</a>
</div>
</body>
</html>
