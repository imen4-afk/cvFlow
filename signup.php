<?php
// signup.php - Simple signup processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        echo "All fields are required.";
        exit();
    }

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'cv_editor');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email already exists.";
        $stmt->close();
        $conn->close();
        exit();
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $password);
    if ($stmt->execute()) {
        echo "Signup successful! <a href='login.html'>Login now</a>";
    } else {
        echo "Signup failed.";
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: signup.html');
    exit();
}
?>