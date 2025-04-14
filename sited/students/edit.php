<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_login();
if (!is_admin() && !is_prof()) die("Accès refusé.");

$id = $_GET['id'] ?? null;
if (!$id) die("ID manquant.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification soumission formulaire
    if (!check_csrf_token($_POST['csrf_token'])) die('CSRF invalide.');
    $stmt = $pdo->prepare("UPDATE users SET grade = ? WHERE id = ? AND role = 'etudiant'");
    $stmt->execute([$_POST['grade'], $id]);
    file_put_contents(__DIR__ . '/../logs/security.log', "[" . date("Y-m-d H:i:s") . "] Modification note étudiant ID $id par " . ($_SESSION['user_id'] ?? 'inconnu') . "\n", FILE_APPEND);
    header("Location: list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'etudiant'");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) die("Étudiant introuvable.");
?>
<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="../style.css"><title>Modifier un étudiant</title></head><body class="page-centered">
<div class="form-container">
<h2>Modifier la note de <?= htmlspecialchars($student['username']) ?></h2>
<form method="POST">
    <input name="grade" type="number" step="0.01" value="<?= $student['grade'] ?>" placeholder="Note"><br>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <button type="submit">Enregistrer</button>
</form>
<a href="list.php">← Retour à la liste</a>
</div>
</body></html>
