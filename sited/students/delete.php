<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
if (!is_admin() && !is_prof()) die("Accès refusé.");

$id = $_GET['id'] ?? null;
if (!$id) die("ID manquant.");

$pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'etudiant'")->execute([$id]);
file_put_contents(__DIR__ . '/../logs/security.log', "[" . date("Y-m-d H:i:s") . "] Suppression étudiant ID $id par " . ($_SESSION['user_id'] ?? 'inconnu') . "\n", FILE_APPEND);
header("Location: list.php");
exit;
