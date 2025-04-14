<?php
require_once 'includes/db.php';

$hashed = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$hashed]);

echo "Mot de passe admin mis Ã  jour.";
?>