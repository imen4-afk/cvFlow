<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$cv_id = intval($_GET['id'] ?? 0);

if ($cv_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'cv_editor');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify CV belongs to user
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cv'])) {
        $new_titre = trim($_POST['titre'] ?? '');
        $new_presentation = trim($_POST['presentation'] ?? '');
        if (!empty($new_titre)) {
            $stmt = $conn->prepare("UPDATE cv SET titre = ?, presentation = ? WHERE id_cv = ? AND id_user = ?");
            $stmt->bind_param("ssii", $new_titre, $new_presentation, $cv_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $titre = $new_titre;
            $presentation = $new_presentation;
        }
    } elseif (isset($_POST['add_experience'])) {
        $titre_poste = trim($_POST['titre_poste'] ?? '');
        $entreprise = trim($_POST['entreprise'] ?? '');
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $description = trim($_POST['description'] ?? '');
        if (!empty($titre_poste) && !empty($entreprise)) {
            $stmt = $conn->prepare("INSERT INTO experiences (id_cv, titre_poste, entreprise, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $cv_id, $titre_poste, $entreprise, $date_debut, $date_fin, $description);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['add_formation'])) {
        $diplome = trim($_POST['diplome'] ?? '');
        $ecole = trim($_POST['ecole'] ?? '');
        $annee = intval($_POST['annee'] ?? 0);
        if (!empty($diplome) && !empty($ecole) && $annee > 0) {
            $stmt = $conn->prepare("INSERT INTO formations (id_cv, diplome, ecole, annee) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $cv_id, $diplome, $ecole, $annee);
            $stmt->execute();
            $stmt->close();
        }
    }
}

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
<title>Edit CV - CVFlow</title>
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

  .cv-header {
    text-align: center;
    margin-bottom: 40px;
  }
  .cv-header h1 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
  }
  .cv-header p {
    color: #666;
    font-size: 15px;
  }

  .section {
    background: #fff;
    border-radius: 24px;
    padding: 32px;
    margin-bottom: 32px;
    box-shadow: 0 10px 40px rgba(79, 45, 209, 0.08);
  }
  .section h2 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 24px;
    color: #4f2dd1;
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

  .form-row {
    display: flex;
    gap: 20px;
  }
  .form-row .field {
    flex: 1;
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
    margin-right: 12px;
  }
  .btn:hover { background: #3f23a8; }
  .btn-secondary {
    background: #ebe3f7;
    color: #333;
  }
  .btn-secondary:hover { background: #d4c9e7; }

  .list-item {
    background: #f9f7fc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
  }
  .list-item h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
  }
  .list-item p {
    color: #666;
    margin-bottom: 4px;
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
    .form-row { flex-direction: column; }
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
    <section class="cv-header">
      <h1><?php echo htmlspecialchars($titre ?: 'Untitled CV'); ?></h1>
      <p>Edit your CV details, experiences, and education.</p>
    </section>

    <section class="section">
      <h2>CV Information</h2>
      <form action="edit_cv.php?id=<?php echo $cv_id; ?>" method="post">
        <div class="field">
          <label>CV Title</label>
          <input type="text" name="titre" value="<?php echo htmlspecialchars($titre); ?>" required />
        </div>
        <div class="field">
          <label>Personal Presentation</label>
          <textarea name="presentation"><?php echo htmlspecialchars($presentation); ?></textarea>
        </div>
        <button class="btn" type="submit" name="update_cv">Update CV</button>
      </form>
    </section>

    <section class="section">
      <h2>Work Experience</h2>
      <?php if (!empty($experiences)): ?>
        <?php foreach ($experiences as $exp): ?>
          <div class="list-item">
            <h3><?php echo htmlspecialchars($exp['titre_poste']); ?> at <?php echo htmlspecialchars($exp['entreprise']); ?></h3>
            <p><?php echo htmlspecialchars($exp['date_debut']); ?> - <?php echo htmlspecialchars($exp['date_fin'] ?: 'Present'); ?></p>
            <p><?php echo htmlspecialchars($exp['description']); ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No experiences added yet.</p>
      <?php endif; ?>
      <form action="edit_cv.php?id=<?php echo $cv_id; ?>" method="post">
        <div class="field">
          <label>Job Title</label>
          <input type="text" name="titre_poste" placeholder="e.g., Software Developer" required />
        </div>
        <div class="field">
          <label>Company</label>
          <input type="text" name="entreprise" placeholder="e.g., Tech Corp" required />
        </div>
        <div class="form-row">
          <div class="field">
            <label>Start Date</label>
            <input type="date" name="date_debut" />
          </div>
          <div class="field">
            <label>End Date (leave empty if current)</label>
            <input type="date" name="date_fin" />
          </div>
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="description" placeholder="Describe your responsibilities and achievements..."></textarea>
        </div>
        <button class="btn" type="submit" name="add_experience">Add Experience</button>
      </form>
    </section>

    <section class="section">
      <h2>Education</h2>
      <?php if (!empty($formations)): ?>
        <?php foreach ($formations as $form): ?>
          <div class="list-item">
            <h3><?php echo htmlspecialchars($form['diplome']); ?></h3>
            <p><?php echo htmlspecialchars($form['ecole']); ?>, <?php echo htmlspecialchars($form['annee']); ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No education added yet.</p>
      <?php endif; ?>
      <form action="edit_cv.php?id=<?php echo $cv_id; ?>" method="post">
        <div class="field">
          <label>Degree/Diploma</label>
          <input type="text" name="diplome" placeholder="e.g., Bachelor's in Computer Science" required />
        </div>
        <div class="field">
          <label>School/University</label>
          <input type="text" name="ecole" placeholder="e.g., University of Tech" required />
        </div>
        <div class="field">
          <label>Year</label>
          <input type="number" name="annee" placeholder="e.g., 2020" min="1900" max="2030" required />
        </div>
        <button class="btn" type="submit" name="add_formation">Add Education</button>
      </form>
    </section>

    <section class="section">
      <a href="view_cv.php?id=<?php echo $cv_id; ?>" class="btn btn-secondary">Preview CV</a>
      <a href="dashboard.php" class="btn">Back to Dashboard</a>
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