<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();
if (!is_admin()) {
    die("Accès refusé.");
}
// Récupération du nombre d'étudiants
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'etudiant'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalStudents = $result['total'];
} catch (PDOException $e) {
    $totalStudents = "Erreur : " . $e->getMessage();
}

// Lecture des logs
$log_lines = [];
$log_file = 'logs/security.log';
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_lines = $lines;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="students/list.php">Étudiants</a>
        <a href="logout.php">Déconnexion</a>
    
    <a href="register_prof.php">Créer un compte professeur</a></nav>

    <div class="dashboard-container">
    <div class="dashboard-card" style="max-height: 100px;">
    <h2>Nombre d'étudiants</h2>
            <p><?php echo htmlspecialchars($totalStudents); ?></p>
        </div>

            <div class="dashboard-card">
        <h2>Dernières activités</h2>
        <div class="log-table-container">
            <ul>
                <?php foreach ($log_lines as $line): ?>
                    <li><?php echo htmlspecialchars($line); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
