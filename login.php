<?php
// login.php - Simple login processing using GET
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: login.html');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'cv_editor');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users into an array and search for a matching record
$result = $conn->query("SELECT username, password FROM users");
$users = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

$found = false;
foreach ($users as $user) {
    if ($user['username'] === $username && $user['password'] === $password) {
        $found = true;
        break;
    }
}

if ($found) {
    // Login successful
    $conn->close();
    header('Location: dashboard.html');
    exit();
} else {
    // Login failed - ask if user wants to sign up and show signup form
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sign Up</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f0f0f0;
            }
            fieldset {
                border: 1px solid #ccc;
                padding: 20px;
                border-radius: 5px;
                background-color: white;
            }
            legend {
                font-weight: bold;
            }
            .message {
                margin-bottom: 15px;
                color: #d9534f;
                text-align: center;
            }
            label {
                display: block;
                margin-bottom: 5px;
            }
            input[type="text"], input[type="password"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                border: 1px solid #ccc;
                border-radius: 3px;
            }
            button {
                padding: 10px 20px;
                background-color: #5cb85c;
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                width: 100%;
            }
            button:hover {
                background-color: #4cae4c;
            }
        </style>
    </head>
    <body>
        <fieldset>
            <legend>Sign Up</legend>
            <div class="message">
                User not found in the database. Would you like to sign up?
            </div>
            <form action="signup.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Sign Up</button>
            </form>
        </fieldset>
    </body>
    </html>
    <?php
    exit();
}
?>