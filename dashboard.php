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

// Handle CV deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cv'])) {
    $del_id = intval($_POST['cv_id'] ?? 0);
    if ($del_id > 0) {
        $conn->begin_transaction();
        try {
            $s = $conn->prepare("DELETE FROM experiences WHERE id_cv = (SELECT id_cv FROM cv WHERE id_cv = ? AND id_user = ?)");
            $s->bind_param("ii", $del_id, $user_id);
            $s->execute(); $s->close();

            $s = $conn->prepare("DELETE FROM formations WHERE id_cv = (SELECT id_cv FROM cv WHERE id_cv = ? AND id_user = ?)");
            $s->bind_param("ii", $del_id, $user_id);
            $s->execute(); $s->close();

            $s = $conn->prepare("DELETE FROM cv WHERE id_cv = ? AND id_user = ?");
            $s->bind_param("ii", $del_id, $user_id);
            $s->execute(); $s->close();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CVFlow Dashboard</title>
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
      --btn-sec: #f5f7ff;
      --btn-sec-h: #e0e7ff;
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
      --btn-sec: #252a3d;
      --btn-sec-h: #2d3347;
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

    .header-right { font-size: 15px; color: var(--subtle); display: flex; align-items: center; gap: 4px; }
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

    .welcome {
      text-align: center;
      margin-bottom: 40px;
      animation: fadeUp 1s ease forwards;
    }
    .welcome h1 {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 8px;
      color: var(--accent);
    }
    .welcome p { color: var(--muted); font-size: 15px; }

    .actions {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-bottom: 40px;
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
      transition: transform 0.2s ease, background 0.3s ease;
      animation: pulse 3s infinite;
    }
    .btn:hover { transform: scale(1.05); background: var(--accent-h); }
    .btn-secondary {
      background: var(--btn-sec);
      color: var(--btn-sec-color);
      transition: background 0.3s ease;
      animation: none;
    }
    .btn-secondary:hover { background: var(--btn-sec-h); transform: scale(1.05); }
    .btn-danger {
      background: linear-gradient(90deg, #f25f5f, #f99f9f);
      color: #fff;
      border: none;
      border-radius: 999px;
      padding: 14px 28px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.3s ease;
    }
    .btn-danger:hover { transform: scale(1.05); background: #d13f3f; }

    .cv-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
    }
    .cv-card {
      background: var(--card);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 8px 30px var(--shadow);
      text-align: center;
      transform: translateY(20px);
      opacity: 0;
      animation: fadeUp 1s ease forwards;
    }
    .cv-card h3 {
      font-size: 24px;
      font-weight: 800;
      margin-bottom: 16px;
      color: var(--accent);
    }
    .cv-card p { color: var(--muted); margin-bottom: 24px; }
    .cv-actions {
      display: flex;
      gap: 8px;
      justify-content: center;
    }
    .cv-actions .btn,
    .cv-actions .btn-secondary,
    .cv-actions .btn-danger {
      flex: 1;
      padding: 10px 12px;
      font-size: 14px;
      text-align: center;
    }

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
      .actions { flex-direction: column; align-items: center; }
      .cv-list { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div id="bgParticles" aria-hidden="true"></div>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      Welcome, <?php echo htmlspecialchars($user_name); ?> |
      <a href="logout.php">Logout</a>
      <label class="theme-switch" title="Toggle dark mode">
        ☀️<input type="checkbox" id="themeCheckbox" /><span class="track"><span class="thumb"></span></span>🌙
      </label>
    </div>
  </header>

  <main class="container">
    <section class="welcome">
      <h1>Elevate your career to an art form.</h1>
      <p>Manage your CVs and create professional resumes with ease.</p>
    </section>

    <section class="actions">
      <a href="create_cv.php" class="btn">Create New CV</a>
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
              <form method="post" style="display:contents;" onsubmit="return confirm('Delete this CV? This cannot be undone.');">
                <input type="hidden" name="cv_id" value="<?php echo $cv['id_cv']; ?>" />
                <button type="submit" name="delete_cv" class="btn-danger">Delete</button>
              </form>
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