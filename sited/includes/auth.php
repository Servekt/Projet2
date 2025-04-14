<?php
require_once 'db.php';

function log_event($action) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            die(" Impossible de créer le dossier de logs ($log_dir)");
        }
    }

    $log_file = $log_dir . '/security.log';
    if (!file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $action . PHP_EOL, FILE_APPEND)) {
        die(" Impossible d'écrire dans le fichier $log_file");
    }
}

function login($username, $password) {
    global $pdo;

    $ip = $_SERVER['REMOTE_ADDR'];
    $log_dir = __DIR__ . '/../logs';
    $attempts_file = $log_dir . '/attempts.json';

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            die("Erreur : Impossible de créer le dossier logs ($log_dir)");
        }
    }

    // Chargement du fichier de tentatives
    $attempts = [];
    if (file_exists($attempts_file)) {
        $json = file_get_contents($attempts_file);
        $attempts = json_decode($json, true);
        if (!is_array($attempts)) $attempts = [];
    }

    // Nettoyage des anciennes tentatives (1 heures)
    foreach ($attempts as $ip_key => $data) {
        if (time() - $data['last_attempt'] > 3600) {
            unset($attempts[$ip_key]);
        }
    }

    if (isset($attempts[$ip]) && $attempts[$ip]['count'] >= 5) {
        log_event(" Blocage IP $ip après trop de tentatives");
        die('Trop de tentatives. Réessayez plus tard.');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        log_event("Connexion réussie pour l'utilisateur: $username");

        unset($attempts[$ip]);
        file_put_contents($attempts_file, json_encode($attempts));
        return true;
    }

    log_event("Échec de connexion pour l'utilisateur: $username");

    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 1, 'last_attempt' => time()];
    } else {
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_attempt'] = time();
    }

    if (!file_put_contents($attempts_file, json_encode($attempts))) {
        die(" Impossible d'écrire dans $attempts_file");
    }

    return false;
}

function require_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_prof() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'prof';
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant';
}
?>
