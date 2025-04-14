<?php
echo "<h1>Résultats des tests de sécurité (mode debug)</h1>";
function test($label, $result, $details = '') {
    echo "<p><strong>$label :</strong> " . ($result ? "✅ OK" : "❌ FAIL");
    if ($details) echo "<br><pre style='background:#f9f9f9;padding:5px;border:1px solid #ccc;'>$details</pre>";
    echo "</p>";
}

// Test 1 : XSS / CSRF combiné
$ch = curl_init('http://localhost/site/students/add.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
curl_setopt($ch, CURLOPT_POST, true);
$post_data1 = [
    'username' => '<script>alert("XSS")</script>',
    'password' => 'test',
    'grade' => '12',
    'csrf_token' => 'FAKE'
];
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data1));
$response1 = curl_exec($ch);
$xss_blocked = strpos($response1, 'CSRF') !== false;
test('XSS (CSRF combiné)', $xss_blocked, "POST:
" . print_r($post_data1, true) . "

Réponse:
" . htmlspecialchars(substr($response1, 0, 500)));
curl_close($ch);

// Test 2 : SQL Injection
$ch = curl_init('http://localhost/site/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$post_data2 = [
    'username' => "' OR 1=1 --",
    'password' => 'test',
    'csrf_token' => 'FAKE'
];
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data2));
$response2 = curl_exec($ch);
$sqli_successful = strpos($response2, 'Identifiants invalides') === false;
test('Injection SQL', !$sqli_successful, "POST:
" . print_r($post_data2, true) . "

Réponse:
" . htmlspecialchars(substr($response2, 0, 500)));
curl_close($ch);

// Test 3 : Accès sans login
$ch = curl_init('http://localhost/site/students/list.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response3 = curl_exec($ch);
$unauthenticated = strpos($response3, 'login') !== false || strpos($response3, 'Accès refusé') !== false;
test('Accès non authentifié à students/list.php', $unauthenticated, "Réponse:
" . htmlspecialchars(substr($response3, 0, 500)));
curl_close($ch);

// Test 4 : Session fixation
session_start();
$old_id = session_id();
session_regenerate_id(true);
$new_id = session_id();
test('Session Fixation', $old_id !== $new_id, "Ancien ID: $old_id
Nouveau ID: $new_id");

echo "<hr><p>⚠️ Assure-toi que localhost/site est bien lancé sous WAMP.</p>";
?>