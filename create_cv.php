<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $presentation = trim($_POST['presentation'] ?? '');

    if (empty($titre)) {
        $error = "Title is required.";
    } else {
        $conn = new mysqli('localhost', 'root', '', 'cv_editor');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO cv (id_user, titre, presentation) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $titre, $presentation);
        if ($stmt->execute()) {
            $cv_id = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            header("Location: edit_cv.php?id=$cv_id");
            exit();
        } else {
            $error = "Failed to create CV.";
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Create CV - CVFlow</title>
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
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 64px 80px;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
  }

  /* Card */
  .card {
    background: #fff;
    border-radius: 24px;
    padding: 48px;
    box-shadow: 0 10px 40px rgba(79, 45, 209, 0.08);
    width: 100%;
    max-width: 600px;
  }
  .card h2 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
  }
  .card .subtitle {
    color: #666;
    font-size: 15px;
    margin-bottom: 28px;
  }

  .field { margin-bottom: 20px; }
  .field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #4f2dd1;
    margin-bottom: 8px;
  }
  .field input, .field textarea {
    width: 100%;
    background: #ebe3f7;
    border: none;
    border-radius: 999px;
    padding: 14px 20px;
    font-size: 15px;
    outline: none;
    resize: vertical;
  }
  .field textarea {
    border-radius: 18px;
    min-height: 100px;
  }
  .field input::placeholder, .field textarea::placeholder { color: #aaa; }

  .error {
    color: #d9534f;
    font-size: 14px;
    margin-bottom: 20px;
  }

  .submit {
    width: 100%;
    background: #4f2dd1;
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 18px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
  }
  .submit:hover { background: #3f23a8; }

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
    .card { padding: 32px 24px; }
  }
</style>
</head>
<body>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      <a href="dashboard.php">Back to Dashboard</a>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <h2>Create New CV</h2>
      <p class="subtitle">Start building your professional CV.</p>

      <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="create_cv.php" method="post">
        <div class="field">
          <label>CV Title</label>
          <input type="text" name="titre" placeholder="e.g., Software Developer CV" required />
        </div>
        <div class="field">
          <label>Personal Presentation</label>
          <textarea name="presentation" placeholder="Write a brief introduction about yourself..."></textarea>
        </div>

        <button class="submit" type="submit">Create CV</button>
      </form>
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