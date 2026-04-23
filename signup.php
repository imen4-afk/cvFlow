<?php
// signup.php - Signup processing using the utilisateurs table
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($nom) || empty($email) || empty($mot_de_passe)) {
        echo "All fields are required.";
        exit();
    }

    $hash_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    $conn = new mysqli('localhost', 'root', '', 'cv_editor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id_user FROM utilisateurs WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        echo "Email already exists. <a href='login.html'>Login</a> instead.";
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sss", $nom, $email, $hash_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Location: login.html?signup=success');
        exit();
    }

    $stmt->close();
    $conn->close();
    echo "Signup failed. Please try again.";
    exit();
}

header('Location: signup.html');
exit();
?>