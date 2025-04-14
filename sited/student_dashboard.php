<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

if (!is_student()) die("Accès refusé.");

// On récupère l'utilisateur connecté via username
$stmt = $pdo->prepare("SELECT username, grade FROM users WHERE username = ? AND role = 'etudiant'");
$stmt->execute([$_SESSION['username']]);
$student = $stmt->fetch();
?>
<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="style.css"><title>Mon espace étudiant</title></head><body>
<h2>Bienvenue <?= htmlspecialchars($_SESSION['username']) ?></h2>
<?php if ($student): ?>
<p>Nom d'utilisateur : <?= htmlspecialchars($student['username']) ?></p>
<p>Note : <?= $student['grade'] !== null ? htmlspecialchars($student['grade']) : "Aucune note" ?></p>
<?php else: ?>
<p>Aucune donnée trouvée pour votre compte.</p>
<?php endif; ?>
<a href="logout.php">Déconnexion</a>
</body></html>
