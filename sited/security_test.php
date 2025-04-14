<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true); 

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title> Test </title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
        h1 { color: #007bff; }
        .test-result { margin-bottom: 20px; padding: 15px; background: #fff; border-left: 5px solid #007bff; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        pre { background: #eee; padding: 10px; overflow-x: auto; }
        .ok { border-left-color: green; }
        .fail { border-left-color: red; }
        #exportBtn { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
<h1>Tests de Sécurité</h1>

<?php
function test($label, $result, $detail = '', $evidence = '', $anchor = '') {
    $status = $result ? "ok" : "fail";
    echo "<div class='test-result $status'><a id='$anchor'></a><strong>$label :</strong> " . ($result ? "✅ OK" : "❌ FAIL");
    if ($detail) echo "<br><em>$detail</em>";
    if ($evidence) echo "<pre>" . htmlspecialchars($evidence) . "</pre>";
    echo "</div>";
}

$cookie_file = __DIR__ . '/cookie.txt';
$site = 'http://localhost/sited';
$logs_path = __DIR__ . "/logs/security.log";

// login csrf
$ch = curl_init("$site/login.php");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file]);
$login_html = curl_exec($ch); curl_close($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $login_html, $m);
$csrf_login = $m[1];

// Authentification
$old_id = null;
$new_id = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$old_id = session_id();
session_write_close();

$ch = curl_init("$site/login.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => 'admin',
        'password' => 'admin123',
        'csrf_token' => $csrf_login
    ])
]);
$login_response = curl_exec($ch);
$cookie_data = file_get_contents($cookie_file);
preg_match('/PHPSESSID\s+([^\s]+)/', $cookie_data, $match);
$new_id = $match[1] ?? null;
curl_close($ch);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_write_close();
}

$login_ok = strpos($login_response, 'Dernières activités') !== false;
test("1. Authentification (admin)", $login_ok, "Connexion avec compte admin", $login_response, "auth");

// XSS csrf
$ch = curl_init("$site/students/add.php");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file]);
$add_form = curl_exec($ch); curl_close($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $add_form, $m);
$csrf_xss = $m[1];

// XSS test
$ch = curl_init("$site/students/add.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['username' => '<script>alert("XSS")</script>', 'password' => 'xss123', 'grade' => '15', 'csrf_token' => $csrf_xss]
]);
$response_xss = curl_exec($ch); curl_close($ch);
test("2. XSS protégé (ajout étudiant)", strpos($response_xss, '<script>') === false, "Injection XSS", $response_xss, "xss");

// SQL Injection
$ch = curl_init("$site/login.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_POSTFIELDS => ['username' => "' OR 1=1 --", 'password' => 'test', 'csrf_token' => $csrf_login]
]);
$response_sqli = curl_exec($ch); curl_close($ch);
test("3. Injection SQL protégée", strpos($response_sqli, 'Identifiants invalides') !== false, "Payload SQL", $response_sqli, "sqli");

// CSRF
$ch = curl_init("$site/students/add.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['username' => 'csrf'.uniqid(), 'password' => 'csrf', 'grade' => '10', 'csrf_token' => 'INVALID']
]);
$response_csrf = curl_exec($ch); curl_close($ch);
test("4. CSRF protégé", strpos($response_csrf, 'CSRF') !== false, "Token invalide", $response_csrf, "csrf");

// Accès sans session
$ch = curl_init("$site/students/list.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => 'cookie_fake.txt',
    CURLOPT_FOLLOWLOCATION => true
]);
$response_access = curl_exec($ch);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
$is_redirected_to_login = strpos($final_url, 'login') !== false;
test("5. Accès sans session", $is_redirected_to_login, "Redirection vers la page de login", "URL finale : $final_url", "access");

// Contrôle du rôle
$ch = curl_init("$site/register_prof.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => 'cookie_fake.txt',
    CURLOPT_FOLLOWLOCATION => true
]);
$response_role = curl_exec($ch);
$final_url_role = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
$is_redirected_role = strpos($final_url_role, 'login') !== false;
test("6. Contrôle de rôle (prof)", $is_redirected_role, "Redirection vers la page de login", "URL finale : $final_url_role", "role");

test("7. Session fixation", $old_id !== $new_id && $old_id !== null && $new_id !== null, "L’ID de session doit changer après connexion", "Avant: $old_id\nAprès: $new_id", "session");

// Journalisation
$log_output = file_exists($logs_path) ? file_get_contents($logs_path) : '';
test("8. Journalisation connexions", strpos($log_output, "Connexion réussie") !== false, "Logs sécurité", $log_output, "log");

// Déconnexion sécurisée
$ch = curl_init("$site/logout.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_FOLLOWLOCATION => true
]);
$response_logout = curl_exec($ch);
$final_logout_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
$logout_ok = strpos($final_logout_url, 'login') !== false;
test("9. Déconnexion sécurisée", $logout_ok, "Redirection vers login après logout", "URL : $final_logout_url", "logout");

// Contrôle d'accès 
$ch = curl_init("$site/students/edit.php?id=1");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_FOLLOWLOCATION => true
]);
$response_priv = curl_exec($ch);
$final_url_priv = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
$access_control_ok = strpos($final_url_priv, 'login') !== false;
test("10. Contrôle accès ", $access_control_ok, "Un étudiant ne doit pas accéder à un autre compte", "URL finale : $final_url_priv", "access-control");

// Expiration session 
if (file_exists($cookie_file)) {
    unlink($cookie_file);
}
$ch = curl_init("$site/dashboard.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true
]);
$response_exp = curl_exec($ch);
$final_exp_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
$expired_ok = strpos($final_exp_url, 'login') !== false;
test("11. Expiration session", $expired_ok, "Redirection après perte de session", "URL : $final_exp_url", "session-expired");
?>

<button id="exportBtn" onclick="window.print()">📄 Exporter ce rapport en PDF</button>
</body>
</html>
