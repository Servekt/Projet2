<?php session_start();
file_put_contents(__DIR__ . '/logs/security.log', "[" . date("Y-m-d H:i:s") . "] Déconnexion utilisateur ID: " . ($_SESSION['user_id'] ?? 'inconnu') . "\n", FILE_APPEND);
session_destroy();
header('Location: login.php'); exit; ?>