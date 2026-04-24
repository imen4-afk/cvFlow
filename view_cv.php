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
    max-width: 1000px;
    margin: 0 auto;
    width: 100%;
  }

  /* ATS CV preview — clean black-and-white, printer-friendly */
  .cv-preview {
    background: #fff;
    border-radius: 8px;
    padding: 56px 64px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    margin-bottom: 32px;
    transform: translateY(40px);
    opacity: 0;
    animation: fadeUp 1s ease forwards;
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
  }

  .cv-header {
    text-align: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 2px solid #111;
  }
  .cv-header .name {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #111;
    margin-bottom: 6px;
  }
  .cv-header .contact {
    font-size: 13px;
    color: #444;
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
  }

  .cv-section {
    margin-bottom: 28px;
  }
  .cv-section h2 {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #111;
    border-bottom: 1px solid #111;
    padding-bottom: 4px;
    margin-bottom: 16px;
  }

  .cv-item {
    margin-bottom: 18px;
  }
  .cv-item-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 16px;
  }
  .cv-item-header .title {
    font-size: 14px;
    font-weight: 700;
    color: #111;
  }
  .cv-item-header .dates {
    font-size: 13px;
    color: #444;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .cv-item .company {
    font-size: 13px;
    color: #444;
    font-style: italic;
    margin-bottom: 6px;
  }
  .cv-item ul {
    list-style: disc;
    padding-left: 20px;
  }
  .cv-item ul li {
    font-size: 13px;
    color: #222;
    margin-bottom: 3px;
    line-height: 1.5;
  }
  .cv-item .plain-text {
    font-size: 13px;
    color: #222;
    line-height: 1.6;
  }

  .actions {
    text-align: center;
    animation: fadeIn 1.5s ease;
  }
  .btn {
    background: var(--accent-grad);
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
    transition: transform 0.2s ease, background 0.3s ease;
    animation: pulse 3s infinite;
  }
  .btn:hover { transform: scale(1.05); background: var(--accent-h); }
  .btn-secondary {
    background: var(--btn-sec);
    color: var(--btn-sec-color);
    animation: none;
  }
  .btn-secondary:hover { background: var(--btn-sec-h); transform: scale(1.05); }

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

  /* Dark mode overrides for the ATS CV (uses hardcoded black text) */
  [data-theme="dark"] .cv-preview {
    background: #1e2235;
    color: #e8eaf0;
  }
  [data-theme="dark"] .cv-preview .cv-header { border-color: #6b8af5; }
  [data-theme="dark"] .cv-preview .cv-header .name { color: #e8eaf0; }
  [data-theme="dark"] .cv-preview .cv-header .contact { color: #9aa3b5; }
  [data-theme="dark"] .cv-preview .cv-section h2 { color: #e8eaf0; border-color: #9aa3b5; }
  [data-theme="dark"] .cv-preview .cv-item-header .title { color: #e8eaf0; }
  [data-theme="dark"] .cv-preview .cv-item-header .dates,
  [data-theme="dark"] .cv-preview .cv-item .company,
  [data-theme="dark"] .cv-preview .cv-item .plain-text,
  [data-theme="dark"] .cv-preview .cv-item ul li { color: #9aa3b5; }

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
    .cv-preview { padding: 32px 24px; }
    .actions .btn { display: block; margin: 8px auto; }
    .cv-item-header { flex-direction: column; gap: 2px; }
  }

  @media print {
    body { background: white; animation: none; }
    header, footer, .actions { display: none; }
    .cv-preview {
      box-shadow: none;
      opacity: 1;
      transform: none;
      border-radius: 0;
      padding: 0;
      margin: 0;
    }
  }
</style>
</head>
<body>
  <div id="bgParticles" aria-hidden="true"></div>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      <a href="edit_cv.php?id=<?php echo $cv_id; ?>">Edit CV</a> |
      <a href="dashboard.php">Back to Dashboard</a>
      <label class="theme-switch" title="Toggle dark mode">
        ☀️<input type="checkbox" id="themeCheckbox" /><span class="track"><span class="thumb"></span></span>🌙
      </label>
    </div>
  </header>

  <main class="container">
    <section class="cv-preview">

      <div class="cv-header">
        <div class="name"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="contact">
          <span><?php echo htmlspecialchars($user_email); ?></span>
        </div>
      </div>

      <?php if (!empty($presentation)): ?>
      <div class="cv-section">
        <h2>Professional Summary</h2>
        <div class="cv-item">
          <p class="plain-text"><?php echo nl2br(htmlspecialchars($presentation)); ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($experiences)): ?>
      <div class="cv-section">
        <h2>Work Experience</h2>
        <?php foreach ($experiences as $exp): ?>
          <div class="cv-item">
            <div class="cv-item-header">
              <span class="title"><?php echo htmlspecialchars($exp['titre_poste']); ?></span>
              <span class="dates">
                <?php echo htmlspecialchars($exp['date_debut']); ?> &ndash; <?php echo htmlspecialchars($exp['date_fin'] ?: 'Present'); ?>
              </span>
            </div>
            <div class="company"><?php echo htmlspecialchars($exp['entreprise']); ?></div>
            <?php if (!empty($exp['description'])): ?>
              <?php $lines = array_filter(array_map('trim', explode("\n", $exp['description']))); ?>
              <?php if (count($lines) > 1): ?>
                <ul><?php foreach ($lines as $line): ?><li><?php echo htmlspecialchars($line); ?></li><?php endforeach; ?></ul>
              <?php else: ?>
                <p class="plain-text"><?php echo htmlspecialchars($exp['description']); ?></p>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($formations)): ?>
      <div class="cv-section">
        <h2>Education</h2>
        <?php foreach ($formations as $form): ?>
          <div class="cv-item">
            <div class="cv-item-header">
              <span class="title"><?php echo htmlspecialchars($form['diplome']); ?></span>
              <span class="dates"><?php echo htmlspecialchars($form['annee']); ?></span>
            </div>
            <div class="company"><?php echo htmlspecialchars($form['ecole']); ?></div>
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