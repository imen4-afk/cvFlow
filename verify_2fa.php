<?php
session_start();

if (!isset($_SESSION['2fa_pending_id'])) {
    header('Location: login.html');
    exit();
}

$error   = '';
$success = '';
$user_id = (int) $_SESSION['2fa_pending_id'];

// ── Handle resend ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    $conn = new mysqli('localhost', 'root', '', 'cv_editor');
    if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600);

    $stmt = $conn->prepare("UPDATE utilisateurs SET otp_code = ?, otp_expires = ? WHERE id_user = ?");
    $stmt->bind_param("ssi", $otp, $expires, $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $email = $_SESSION['2fa_pending_email'];
    $nom   = $_SESSION['2fa_pending_name'];
    $body  = "Hello $nom,\n\nYour new verification code is:\n\n  $otp\n\nIt expires in 10 minutes.\n\n– CVFlow";
    $hdr   = "From: noreply@cvflow.local\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    mail($email, 'CVFlow – New verification code', $body, $hdr);

    $success = 'A new code has been sent to your email.';
}

// ── Handle OTP verification ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp_input = trim($_POST['otp'] ?? '');

    if (empty($otp_input)) {
        $error = 'Please enter the 6-digit code.';
    } else {
        $conn = new mysqli('localhost', 'root', '', 'cv_editor');
        if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

        $stmt = $conn->prepare(
            "SELECT nom, email, otp_code, otp_expires FROM utilisateurs WHERE id_user = ?"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (
            $user &&
            $user['otp_code'] !== null &&
            hash_equals($user['otp_code'], $otp_input) &&
            strtotime($user['otp_expires']) > time()
        ) {
            // Success – clear the OTP and complete login
            $stmt = $conn->prepare(
                "UPDATE utilisateurs SET otp_code = NULL, otp_expires = NULL WHERE id_user = ?"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $conn->close();

            unset($_SESSION['2fa_pending_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name']);
            $_SESSION['user_id']    = $user_id;
            $_SESSION['user_name']  = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $conn->close();
            $error = 'Invalid or expired code. Please try again.';
        }
    }
}

$masked_email = '';
if (!empty($_SESSION['2fa_pending_email'])) {
    $parts = explode('@', $_SESSION['2fa_pending_email']);
    $masked_email = substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2)) . '@' . $parts[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Two-Factor Verification – CVFlow</title>
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
    --accent: #4f6ef2;
    --accent-h: #3a56c5;
    --accent-grad: linear-gradient(90deg, #4f6ef2, #7fa6f9);
    --border: #e5e5e5;
    --shadow: rgba(0,0,0,0.1);
    --hdr: #444;
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
    --accent: #6b8af5;
    --accent-h: #8fa5ff;
    --accent-grad: linear-gradient(90deg, #6b8af5, #9ab0fa);
    --border: #2d3347;
    --shadow: rgba(0,0,0,0.35);
    --hdr: #c8cad4;
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
  .header-right { font-size: 15px; color: var(--subtle); display: flex; align-items: center; }
  .header-right a {
    color: var(--accent);
    font-weight: 600;
    text-decoration: none;
    margin-left: 8px;
    transition: color 0.3s ease;
  }
  .header-right a:hover { color: var(--accent-h); }

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

  .card {
    background: var(--card);
    border-radius: 20px;
    padding: 48px;
    box-shadow: 0 8px 30px var(--shadow);
    width: 100%;
    max-width: 400px;
    transform: translateY(40px);
    opacity: 0;
    animation: fadeUp 1s ease forwards;
  }
  .card h2 {
    font-size: 30px;
    font-weight: 800;
    margin-bottom: 8px;
    color: var(--accent);
  }
  .card .subtitle {
    color: var(--muted);
    font-size: 15px;
    margin-bottom: 28px;
  }

  .shield-icon {
    font-size: 48px;
    text-align: center;
    margin-bottom: 16px;
  }

  .alert {
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    margin-bottom: 20px;
    font-weight: 600;
  }
  .alert-error   { background: #fee2e2; color: #b91c1c; }
  .alert-success { background: #d1fae5; color: #065f46; }
  [data-theme="dark"] .alert-error   { background: #3b0a0a; color: #fca5a5; }
  [data-theme="dark"] .alert-success { background: #052e16; color: #6ee7b7; }

  .field { margin-bottom: 20px; }
  .field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--accent);
    margin-bottom: 8px;
  }

  /* Big OTP input */
  .otp-input {
    width: 100%;
    background: var(--input-bg);
    border: 2px solid var(--border);
    border-radius: 999px;
    padding: 16px 20px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 12px;
    text-align: center;
    color: var(--color);
    outline: none;
    transition: box-shadow 0.3s ease, border-color 0.3s ease;
  }
  .otp-input:focus {
    box-shadow: 0 0 0 3px rgba(107,138,245,0.35);
    border-color: var(--accent);
  }
  .otp-input::placeholder { color: #aaa; letter-spacing: 4px; font-size: 20px; font-weight: 400; }

  .submit {
    width: 100%;
    background: var(--accent-grad);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 16px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.3s ease;
    animation: pulse 3s infinite;
    margin-bottom: 12px;
  }
  .submit:hover { transform: scale(1.05); background: var(--accent-h); }

  .resend-row {
    text-align: center;
    font-size: 14px;
    color: var(--muted);
  }
  .resend-row button {
    background: none;
    border: none;
    color: var(--accent);
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    padding: 0;
    transition: color 0.3s;
  }
  .resend-row button:hover { color: var(--accent-h); }

  .timer {
    text-align: center;
    font-size: 13px;
    color: var(--muted);
    margin-top: 10px;
  }
  .timer span { font-weight: 700; color: var(--accent); }

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
    width: 44px; height: 24px;
    background: var(--border);
    border-radius: 12px;
    transition: background 0.3s;
    flex-shrink: 0;
  }
  .thumb {
    position: absolute;
    top: 3px; left: 3px;
    width: 18px; height: 18px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: transform 0.3s;
  }
  .theme-switch input:checked + .track { background: var(--accent); }
  .theme-switch input:checked + .track .thumb { transform: translateX(20px); }

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

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeDown {
    from { opacity: 0; transform: translateY(-40px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes pulse {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.02); }
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
    .card { padding: 32px 24px; }
  }
</style>
</head>
<body>
  <div id="bgParticles" aria-hidden="true"></div>
  <header>
    <div class="logo1"><h3>CVFlow</h3></div>
    <div class="header-right">
      <a href="login.html">Back to Login</a>
      <label class="theme-switch" title="Toggle dark mode">
        ☀️<input type="checkbox" id="themeCheckbox" /><span class="track"><span class="thumb"></span></span>🌙
      </label>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <div class="shield-icon">🔐</div>
      <h2>Verify It's You</h2>
      <p class="subtitle">
        We sent a 6-digit code to
        <strong><?php echo htmlspecialchars($masked_email); ?></strong>.
        Enter it below to continue.
      </p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="field">
          <label>Verification Code</label>
          <input
            class="otp-input"
            type="text"
            name="otp"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            placeholder="000000"
            autofocus
            required
          />
        </div>
        <button class="submit" type="submit">Verify &amp; Login</button>
      </form>

      <div class="resend-row">
        Didn't receive it?
        <form method="post" style="display:inline;">
          <button type="submit" name="resend" value="1">Resend code</button>
        </form>
      </div>

      <div class="timer">Code expires in <span id="countdown">10:00</span></div>
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

    // Countdown timer (visual only – server enforces real expiry)
    (function() {
      let secs = 600;
      const el = document.getElementById('countdown');
      const tick = setInterval(function() {
        secs--;
        if (secs <= 0) { clearInterval(tick); el.textContent = 'expired'; return; }
        const m = Math.floor(secs / 60).toString().padStart(2, '0');
        const s = (secs % 60).toString().padStart(2, '0');
        el.textContent = m + ':' + s;
      }, 1000);
    })();
  </script>
</body>
</html>
