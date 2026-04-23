<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$cv_id = intval($_GET['id'] ?? 0);

if ($cv_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'cv_editor');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify CV belongs to user and fetch details
$stmt = $conn->prepare("SELECT titre, presentation FROM cv WHERE id_cv = ? AND id_user = ?");
$stmt->bind_param("ii", $cv_id, $user_id);
$stmt->execute();
$stmt->bind_result($titre, $presentation);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    header('Location: dashboard.php');
    exit();
}
$stmt->close();

// Fetch experiences
$experiences = [];
$stmt = $conn->prepare("SELECT titre_poste, entreprise, date_debut, date_fin, description FROM experiences WHERE id_cv = ? ORDER BY date_debut DESC");
$stmt->bind_param("i", $cv_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $experiences[] = $row;
}
$stmt->close();

// Fetch formations
$formations = [];
$stmt = $conn->prepare("SELECT diplome, ecole, annee FROM formations WHERE id_cv = ? ORDER BY annee DESC");
$stmt->bind_param("i", $cv_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $formations[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo htmlspecialchars($titre ?: 'CV'); ?> - CVFlow</title>
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
    max-width: 1000px;
    margin: 0 auto;
    width: 100%;
  }

  .cv-preview {
    background: #fff;
    border-radius: 24px;
    padding: 48px;
    box-shadow: 0 10px 40px rgba(79, 45, 209, 0.08);
    margin-bottom: 32px;
  }

  .cv-header {
    text-align: center;
    margin-bottom: 32px;
    border-bottom: 2px solid #4f2dd1;
    padding-bottom: 24px;
  }
  .cv-header h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 8px;
  }
  .cv-header .name {
    font-size: 24px;
    color: #4f2dd1;
    margin-bottom: 8px;
  }
  .cv-header .email {
    color: #666;
  }

  .cv-section {
    margin-bottom: 32px;
  }
  .cv-section h2 {
    font-size: 24px;
    font-weight: 800;
    color: #4f2dd1;
    margin-bottom: 16px;
    border-bottom: 1px solid #e5dcf3;
    padding-bottom: 8px;
  }

  .cv-item {
    margin-bottom: 24px;
  }
  .cv-item h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .cv-item .meta {
    color: #666;
    font-size: 14px;
    margin-bottom: 8px;
  }
  .cv-item p {
    color: #444;
  }

  .actions {
    text-align: center;
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
    margin: 0 8px;
  }
  .btn:hover { background: #3f23a8; }
  .btn-secondary {
    background: #ebe3f7;
    color: #333;
  }
  .btn-secondary:hover { background: #d4c9e7; }

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
    .cv-preview { padding: 32px 24px; }
    .actions .btn { display: block; margin: 8px 0; }
  }

  @media print {
    body { background: white; }
    header, footer, .actions { display: none; }
    .cv-preview { box-shadow: none; }
  }
</style>
</head>
<body>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      <a href="edit_cv.php?id=<?php echo $cv_id; ?>">Edit CV</a> |
      <a href="dashboard.php">Back to Dashboard</a>
    </div>
  </header>

  <main class="container">
    <section class="cv-preview">
      <div class="cv-header">
        <h1><?php echo htmlspecialchars($titre ?: 'Curriculum Vitae'); ?></h1>
        <div class="name"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
      </div>

      <?php if (!empty($presentation)): ?>
      <div class="cv-section">
        <h2>Personal Presentation</h2>
        <p><?php echo nl2br(htmlspecialchars($presentation)); ?></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($experiences)): ?>
      <div class="cv-section">
        <h2>Work Experience</h2>
        <?php foreach ($experiences as $exp): ?>
          <div class="cv-item">
            <h3><?php echo htmlspecialchars($exp['titre_poste']); ?> at <?php echo htmlspecialchars($exp['entreprise']); ?></h3>
            <div class="meta"><?php echo htmlspecialchars($exp['date_debut']); ?> - <?php echo htmlspecialchars($exp['date_fin'] ?: 'Present'); ?></div>
            <p><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($formations)): ?>
      <div class="cv-section">
        <h2>Education</h2>
        <?php foreach ($formations as $form): ?>
          <div class="cv-item">
            <h3><?php echo htmlspecialchars($form['diplome']); ?></h3>
            <div class="meta"><?php echo htmlspecialchars($form['ecole']); ?>, <?php echo htmlspecialchars($form['annee']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="actions">
      <a href="edit_cv.php?id=<?php echo $cv_id; ?>" class="btn">Edit CV</a>
      <button onclick="window.print()" class="btn btn-secondary">Print CV</button>
      <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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