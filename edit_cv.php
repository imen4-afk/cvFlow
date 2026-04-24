<?php
require_once __DIR__ . '/config.php';
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

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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
        $date_fin   = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
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
<script>
  (function(){ document.documentElement.setAttribute('data-theme', localStorage.getItem('cvflow-theme')||'light'); })();
</script>
<link rel="stylesheet" href="particles.css" />
<style>
  :root {
    --bg: linear-gradient(135deg, #dfe9f3, #ffffff);
    --bg-mid: linear-gradient(135deg, #fceabb, #f8b500);
    --bg-end: linear-gradient(135deg, #c9e4f7, #e6f0ff);
    --color: #222;
    --muted: #666;
    --subtle: #555;
    --card: #fff;
    --input-bg: #f5f7ff;
    --item-bg: #f9f7fc;
    --accent: #4f6ef2;
    --accent-h: #3a56c5;
    --accent-grad: linear-gradient(90deg, #4f6ef2, #7fa6f9);
    --border: #e5e5e5;
    --shadow: rgba(0,0,0,0.1);
    --hdr: #444;
    --btn-sec: #ebe3f7;
    --btn-sec-h: #d4c9e7;
    --btn-sec-color: #333;
  }
  [data-theme="dark"] {
    --bg: linear-gradient(135deg, #0f1117, #1a1d2e);
    --bg-mid: linear-gradient(135deg, #1a1a3e, #2a1545);
    --bg-end: linear-gradient(135deg, #0d1b2a, #1c2540);
    --color: #e8eaf0;
    --muted: #9aa3b5;
    --subtle: #9aa3b5;
    --card: #1e2235;
    --input-bg: #252a3d;
    --item-bg: #252a3d;
    --accent: #6b8af5;
    --accent-h: #8fa5ff;
    --accent-grad: linear-gradient(90deg, #6b8af5, #9ab0fa);
    --border: #2d3347;
    --shadow: rgba(0,0,0,0.35);
    --hdr: #c8cad4;
    --btn-sec: #2d3347;
    --btn-sec-h: #363d54;
    --btn-sec-color: #e8eaf0;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: var(--bg);
    color: var(--color);
    line-height: 1.5;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    animation: bgShift 25s ease-in-out infinite alternate;
  }

  /* Header */
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px 64px;
    animation: fadeDown 1s ease forwards;
    color: var(--hdr);
  }
  .logo1 h3 {
    font-size: 28px;
    font-weight: 800;
    color: var(--accent);
    letter-spacing: 1px;
    transition: transform 0.3s ease;
  }
  .logo1 h3:hover { transform: scale(1.05); }

  .logo { font-size: 22px; font-weight: 800; }
  .header-right { font-size: 15px; color: var(--subtle); display: flex; align-items: center; }
  .header-right a {
    color: var(--accent);
    font-weight: 600;
    text-decoration: none;
    margin-left: 8px;
    transition: color 0.3s ease;
  }
  .header-right a:hover { color: var(--accent-h); }

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
    animation: fadeIn 1.5s ease;
  }
  .cv-header h1 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
    color: var(--accent);
    animation: slideIn 1s ease;
  }
  .cv-header p { color: var(--muted); font-size: 15px; }

  .section {
    background: var(--card);
    border-radius: 20px;
    padding: 48px;
    margin-bottom: 32px;
    box-shadow: 0 8px 30px var(--shadow);
    transform: translateY(40px);
    opacity: 0;
    animation: fadeUp 1s ease forwards;
  }
  .section h2 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 24px;
    color: var(--accent);
    animation: slideIn 1s ease;
  }

  .field { margin-bottom: 20px; }
  .field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--accent);
    margin-bottom: 8px;
  }
  .field input, .field textarea {
    width: 100%;
    background: var(--input-bg);
    border: none;
    border-radius: 999px;
    padding: 14px 20px;
    font-size: 15px;
    color: var(--color);
    outline: none;
    resize: vertical;
    transition: box-shadow 0.3s ease;
  }
  .field textarea { border-radius: 18px; min-height: 100px; }
  .field input:focus, .field textarea:focus { box-shadow: 0 0 0 3px rgba(107,138,245,0.35); }
  .field input::placeholder, .field textarea::placeholder { color: #aaa; }

  .form-row { display: flex; gap: 20px; }
  .form-row .field { flex: 1; }

  .btn {
    background: var(--accent-grad);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 14px 28px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-right: 12px;
    transition: transform 0.2s ease, background 0.3s ease;
    animation: pulse 3s infinite;
  }
  .btn:hover { transform: scale(1.05); background: var(--accent-h); }
  .btn-secondary {
    background: var(--btn-sec);
    color: var(--btn-sec-color);
    border: none;
    border-radius: 999px;
    padding: 14px 28px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background 0.3s ease;
  }
  .btn-secondary:hover { background: var(--btn-sec-h); }

  .list-item {
    background: var(--item-bg);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    animation: fadeIn 1.2s ease;
  }
  .list-item h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--accent);
  }
  .list-item p { color: var(--muted); margin-bottom: 4px; }

  /* Theme switch */
  .theme-switch {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    margin-left: 16px;
    font-size: 15px;
    user-select: none;
  }
  .theme-switch input { display: none; }
  .track {
    position: relative;
    width: 44px;
    height: 24px;
    background: var(--border);
    border-radius: 12px;
    transition: background 0.3s;
    flex-shrink: 0;
  }
  .thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: transform 0.3s;
  }
  .theme-switch input:checked + .track { background: var(--accent); }
  .theme-switch input:checked + .track .thumb { transform: translateX(20px); }

  /* Footer */
  footer {
    border-top: 1px solid var(--border);
    padding: 24px 64px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: var(--muted);
    flex-wrap: wrap;
    gap: 16px;
    animation: fadeUp 1.2s ease forwards;
  }
  footer .links { display: flex; gap: 32px; }
  footer .links a { color: var(--accent); text-decoration: none; transition: color 0.3s ease; }
  footer .links a:hover { color: var(--accent-h); }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeDown {
    from { opacity: 0; transform: translateY(-40px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateX(-40px); }
    to { opacity: 1; transform: translateX(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
  }
  @keyframes bgShift {
    0%   { background: var(--bg); }
    50%  { background: var(--bg-mid); }
    100% { background: var(--bg-end); }
  }

  @media (max-width: 900px) {
    .container { padding: 24px; }
    header, footer { padding: 24px; }
    .section { padding: 32px 24px; }
    .form-row { flex-direction: column; }
  }
</style>

</head>
<body>
  <div id="bgParticles" aria-hidden="true"></div>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      <a href="dashboard.php">Back to Dashboard</a>
      <label class="theme-switch" title="Toggle dark mode">
        ☀️<input type="checkbox" id="themeCheckbox" /><span class="track"><span class="thumb"></span></span>🌙
      </label>
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
            <p><?php echo htmlspecialchars($exp['date_debut']); ?> - <?php echo ($exp['date_fin'] && $exp['date_fin'] !== '0000-00-00') ? htmlspecialchars($exp['date_fin']) : 'Present'; ?></p>
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

  <script src="particles.js"></script>
  <script>
    (function() {
      const root = document.documentElement;
      const cb   = document.getElementById('themeCheckbox');
      const saved = localStorage.getItem('cvflow-theme') || 'light';
      root.setAttribute('data-theme', saved);
      cb.checked = (saved === 'dark');
      cb.addEventListener('change', function() {
        const next = this.checked ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        localStorage.setItem('cvflow-theme', next);
      });
    })();
  </script>
</body>
</html>