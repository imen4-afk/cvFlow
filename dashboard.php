<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'cv_editor');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user's CVs
$stmt = $conn->prepare("SELECT id_cv, titre FROM cv WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cvs = [];
while ($row = $result->fetch_assoc()) {
    $cvs[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVFlow Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3eefb;
            color: #111;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 32px 64px;
        }
        .logo1 {
            height: 74px;
            width: 65px;
            margin-left: -48px;
            margin-top: -138px;
            padding-top: 9px;
            margin-bottom: 24px;
            margin-right: 8px;
        }
        .logo { font-size: 22px; font-weight: 800; }
        .header-right { font-size: 15px; color: #555; }
        .header-right a {
            color: #4f2dd1;
            font-weight: 600;
            text-decoration: none;
            margin-left: 8px;
        }

        /* Layout */
        .container {
            flex: 1;
            padding: 40px 64px 80px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome {
            text-align: center;
            margin-bottom: 40px;
        }
        .welcome h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .welcome p {
            color: #666;
            font-size: 15px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        .btn {
            background: #4f2dd1;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #3f23a8; }
        .btn-secondary {
            background: #ebe3f7;
            color: #333;
        }
        .btn-secondary:hover { background: #d4c9e7; }

        .cv-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .cv-card {
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 40px rgba(79, 45, 209, 0.08);
            text-align: center;
        }
        .cv-card h3 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 16px;
        }
        .cv-card p {
            color: #666;
            margin-bottom: 24px;
        }
        .cv-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        /* Footer */
        footer {
            border-top: 1px solid #e5dcf3;
            padding: 24px 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #666;
            flex-wrap: wrap;
            gap: 16px;
        }
        footer .links { display: flex; gap: 32px; }
        footer .links a { color: #555; text-decoration: none; }

        @media (max-width: 900px) {
            .container { padding: 24px; }
            header, footer { padding: 24px; }
            .actions { flex-direction: column; align-items: center; }
            .cv-list { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo1"><h3>CVFlow</h3></div>
        <div class="header-right">
            Welcome, <?php echo htmlspecialchars($user_name); ?> |
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main class="container">
        <section class="welcome">
            <h1>Elevate your career to an art form.</h1>
            <p>Manage your CVs and create professional resumes with ease.</p>
        </section>

        <section class="actions">
            <a href="create_cv.php" class="btn">Create New CV</a>
            <a href="#" class="btn btn-secondary">Import CV</a>
        </section>

        <section class="cv-list">
            <?php if (empty($cvs)): ?>
                <div class="cv-card">
                    <h3>No CVs Yet</h3>
                    <p>Create your first CV to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cvs as $cv): ?>
                    <div class="cv-card">
                        <h3><?php echo htmlspecialchars($cv['titre'] ?: 'Untitled CV'); ?></h3>
                        <p>Manage your professional profile.</p>
                        <div class="cv-actions">
                            <a href="view_cv.php?id=<?php echo $cv['id_cv']; ?>" class="btn btn-secondary">View</a>
                            <a href="edit_cv.php?id=<?php echo $cv['id_cv']; ?>" class="btn">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="logo">CVFlow</div>
        <div class="links">
            <a href="#">Imen Othmen</a>
            <a href="#">Jasser Nsiri</a>
            <a href="#">Hadil Rjeb</a>
            <a href="#">Rahma Chouaieb</a>
        </div>
        <div>@projet web GLSI2</div>
    </footer>
</body>
</html>