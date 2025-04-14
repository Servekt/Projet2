<?php
// Démarrage de la session utilisateur
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

// Récupérer les étudiants
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'etudiant'");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Étudiants</title>
    <link href="../style.css" rel="stylesheet" />
</head>
<body>

<nav>
    <?php if (is_admin()): ?>
        <a href="../dashboard.php">Dashboard</a>
    <?php endif; ?>
    <a href="list.php">Étudiants</a>
    <a href="../logout.php">Déconnexion</a>
</nav>

<div class="container-table">
    <h2>Liste des étudiants</h2>
    <table class="styled-table">
        <tr>
            <th>ID</th>
            <th>Nom d'utilisateur</th>
            <th>Note</th>
            <th>Créé le</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($students as $student): ?>
        <tr>
            <td><?= $student['id'] ?></td>
            <td><?= htmlspecialchars($student['username']) ?></td>
            <td><?= $student['grade'] ?></td>
            <td><?= $student['created_at'] ?></td>
            <td>
                <a href="edit.php?id=<?= $student['id'] ?>">Modifier</a> |
                <a href="delete.php?id=<?= $student['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="add.php">Ajouter un étudiant</a>
</div>

</body>
</html>
