<?php
require_once __DIR__ . '/config.php';
session_start();

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT id_user, nom, mot_de_passe FROM utilisateurs WHERE email = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$id_user = null;
$nom = null;
$hash_password = '';
$stmt->bind_result($id_user, $nom, $hash_password);

$authenticated = false;
if ($stmt->fetch() && $hash_password !== '') {
    if (password_verify($password, $hash_password)) {
        $authenticated = true;
    }
}

$stmt->close();
$conn->close();

if ($authenticated) {
    $_SESSION['user_id']    = $id_user;
    $_SESSION['user_name']  = $nom;
    $_SESSION['user_email'] = $email;
    header('Location: dashboard.php');
    exit();
}

header('Location: login.html?error=invalid');
exit();
?>
