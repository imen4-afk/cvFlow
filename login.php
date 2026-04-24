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
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("s", $email);
$stmt->execute();
$id_user = null; $nom = null; $hash_password = '';
$stmt->bind_result($id_user, $nom, $hash_password);

$authenticated = false;
if ($stmt->fetch() && $hash_password !== '') {
    if (password_verify($password, $hash_password)) {
        $authenticated = true;
    }
}
$stmt->close();

if (!$authenticated) {
    $conn->close();
    header('Location: login.html?error=invalid');
    exit();
}

// Generate 6-digit OTP and store it with a 10-minute expiry
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 600);

$stmt = $conn->prepare("UPDATE utilisateurs SET otp_code = ?, otp_expires = ? WHERE id_user = ?");
$stmt->bind_param("ssi", $otp, $expires, $id_user);
$stmt->execute();
$stmt->close();
$conn->close();

// Send OTP email
$subject = 'CVFlow – Your verification code';
$body    = "Hello $nom,\n\nYour 2-factor verification code is:\n\n  $otp\n\nIt expires in 10 minutes. Do not share it with anyone.\n\n– CVFlow";
$headers = "From: " . MAIL_FROM . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
mail($email, $subject, $body, $headers);

// Store pending user ID in session (NOT the full login yet)
$_SESSION['2fa_pending_id']    = $id_user;
$_SESSION['2fa_pending_email'] = $email;
$_SESSION['2fa_pending_name']  = $nom;

header('Location: verify_2fa.php');
exit();
?>
