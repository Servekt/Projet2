<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Vérification soumission formulaire
    if (!check_csrf_token($_POST['csrf_token'])) {
        die('CSRF token invalide.');
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    if (login($username, $password)) {
       
        if (is_admin()) {
            header('Location: dashboard.php');
        } elseif (is_prof()) {
            header('Location: students/list.php');
        } else {
            header('Location: student_dashboard.php');
        }
        exit;
    } else {
        $error = "Identifiants invalides.";
    }
}
?>
<!DOCTYPE html>
<html><head>
    <link rel="stylesheet" href="style.css">
<title>Connexion</title></head><body class="page-centered">
<div class="form-container">
<h2>Connexion</h2>
<form method="POST">
    <input name="username" placeholder="Nom d'utilisateur" required><br>
    <input name="password" type="password" placeholder="Mot de passe" required><br>
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <button type="submit">Connexion</button>
</form>
<p><a href="register.php">Créer un compte étudiant</a></p>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
</div>
</body></html>