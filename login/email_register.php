<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_token'])) {
    header('Location: ../3wid_list.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_csrf = $_POST['csrf_token'] ?? '';
    $email_value = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $posted_csrf)) {
        $error = 'Session expired. Try again.';
    } elseif (!filter_var($email_value, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $email_hash = md5($email_value);

        $stmt = $conn->prepare('SELECT id, password_hash FROM google_users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email_hash);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing && !empty($existing['password_hash'])) {
            $error = 'That account already exists. Sign in instead.';
        } elseif ($existing && empty($existing['password_hash'])) {
            $error = 'This email already uses Google. Continue with Google.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $provider = 'password';
            $verified = 0;
            $empty = '';

            $ins = $conn->prepare(
                'INSERT INTO google_users
                    (email, password_hash, first_name, last_name, gender, full_name, picture, verifiedEmail, token, auth_provider)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->bind_param(
                'sssssssiss',
                $email_hash,
                $hash,
                $empty,
                $empty,
                $empty,
                $empty,
                $empty,
                $verified,
                $token,
                $provider
            );

            if ($ins->execute()) {
                session_regenerate_id(true);
                $_SESSION['user_token'] = $token;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ../3wid_list.php');
                exit;
            }

            $error = 'Could not create the account. Confirm the password_hash column exists.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account — 3WordID</title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:#0F1C3F; --navy-hover:#162756; --slate:#5B6478; --muted:#8A93A6;
            --line:#E6E3DC; --paper:#F7F6F3; --white:#FFFFFF; --gold:#B45309;
            --shadow:0 10px 30px rgba(15,28,63,.08);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:Inter,system-ui,sans-serif;background:var(--paper);color:var(--navy);min-height:100vh;display:flex;flex-direction:column}
        .site-header{position:sticky;top:0;display:flex;align-items:center;justify-content:space-between;padding:16px 28px;background:var(--paper);border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--navy);font-weight:800;letter-spacing:-.04em}
        .brand-mark{width:34px;height:34px;border-radius:10px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;line-height:.9;text-align:center}
        .btn-header{display:inline-flex;align-items:center;height:36px;padding:0 14px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--navy);text-decoration:none;font-size:14px;font-weight:600}
        .wrap{width:100%;max-width:440px;margin:48px auto 0;padding:0 24px 48px;flex:1}
        .eyebrow{color:var(--gold);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px}
        h1{font-size:32px;font-weight:800;letter-spacing:-.04em;margin-bottom:8px}
        .sub{color:var(--slate);font-size:15px;line-height:1.5;margin-bottom:24px}
        .flash{background:#fff;border:1px solid #ECEAE4;border-left:4px solid var(--gold);border-radius:14px;padding:12px 14px;color:var(--slate);font-size:14px;margin-bottom:16px}
        .card{background:#fff;border:1px solid #ECEAE4;border-radius:16px;padding:22px;box-shadow:var(--shadow)}
        label{display:block;font-size:13px;font-weight:600;margin:0 0 6px}
        input{width:100%;height:44px;border:1px solid var(--line);border-radius:10px;padding:0 12px;font:inherit;margin-bottom:14px;background:#fff;color:var(--navy)}
        input:focus{outline:2px solid var(--navy);outline-offset:1px}
        .meta{color:var(--muted);font-size:12px;margin:-8px 0 14px}
        .btn{width:100%;height:44px;border:0;border-radius:10px;background:var(--navy);color:#fff;font-weight:600;cursor:pointer;font-size:14px}
        .btn:hover{background:var(--navy-hover)}
        .hint{color:var(--muted);font-size:13px;text-align:center;margin-top:20px}
        .hint a{color:var(--navy);font-weight:600;text-decoration:none}
        footer{text-align:center;color:var(--muted);font-size:13px;padding:24px}
        footer a{color:var(--muted);margin:0 8px;text-decoration:none}
        @media (max-width:800px){.site-header{padding:14px 20px}h1{font-size:28px}.wrap{margin-top:32px}}
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="../index.php"><span class="brand-mark"><img width='35' src='https://3WordID.com/img/3wid_big.png'></span>3WordID</a>

        <a class="btn-header" href="../index.php">Home page</a>
    </header>

    <main class="wrap">
        <div class="eyebrow">Account</div>
        <h1>Create account</h1>
        <p class="sub">Email and password. The address is hashed before it is saved. Unused accounts will be deleted after 3 months.</p>

        <?php if ($error !== ''): ?>
            <div class="flash"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="card" method="post" action="email_register.php" autocomplete="off">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required maxlength="255" placeholder="you@example.com" value="<?php echo htmlspecialchars($email_value, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required minlength="10" placeholder="At least 10 characters">
            <p class="meta">Minimum 10 characters.</p>

            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" required minlength="10" placeholder="Repeat password">

            <!-- Placeholder: email verification is not sent. Enable later if you start mailing. -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn" type="submit">Create account</button>
        </form>

        <p class="hint">
            Already registered? <a href="email_login.php">Sign in</a><br>
            <a href="options.php">Use Google instead</a>
        </p>
    </main>

    <footer>
        
    </footer>
</body>
</html>
