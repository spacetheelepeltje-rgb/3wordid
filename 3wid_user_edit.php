<?php
require_once 'php/functions.php';
require_once 'login/config.php';

$data = current_user_from_session($conn);

if ($data == NULL) {
    header('Location: login/options.php?message=' . rawurlencode('Please log in.'));
    exit;
}

$admin_id = 4;

if ((int)$data['id'] !== $admin_id) {
    header('Location: 3wid_list.php?message=' . rawurlencode('Not allowed.'));
    exit;
}

$action_token = get_action_token();

// Only these columns can be changed on this page. Everything else (gender,
// email hash, dates, tokens, ...) is shown read-only and never written back.
// Writing every column back used to break saving: a NULL or empty gender was
// posted back as '' which the gender ENUM column does not accept.
// type: 'i' = whole number, 'd' = decimal number, 's' = text
$editable = [
    'max_3wids' => ['type' => 'i', 'label' => 'Extra 3WordIDs allowed (max_3wids)', 'hint' => 'The user can create this number + 1 3WordIDs.'],
    'user_type' => ['type' => 'i', 'label' => 'User type (user_type)', 'hint' => '0 = free, 9 = blocked. Other numbers as you use them.'],
    'credit'    => ['type' => 'd', 'label' => 'Credit in € (credit)', 'hint' => ''],
    'full_name' => ['type' => 's', 'label' => 'Name (full_name)', 'hint' => ''],
];

// Never shown at all.
$hidden_columns = ['csrf_token', 'token', 'session_id', 'password_hash'];

function db_3wordid_admin_update_user($id, $changes, $editable) {
    $id = (int)$id;
    if ($id < 1 || !$changes) {
        return false;
    }

    $sets = [];
    $values = [];
    $types = '';
    foreach ($changes as $column => $value) {
        if ($column === 'password_hash') {
            $type = 's';
        } elseif (isset($editable[$column])) {
            $type = $editable[$column]['type'];
        } else {
            continue;
        }
        $sets[] = '`' . $column . '` = ?';
        $values[] = $value;
        $types .= $type;
    }
    if (!$sets) {
        return false;
    }

    $types .= 'i';
    $values[] = $id;

    $conn = get_db_conn();
    try {
        $stmt = $conn->prepare('UPDATE google_users SET ' . implode(', ', $sets) . ' WHERE id = ?');
        if (!$stmt) {
            error_log('user update prepare failed: ' . $conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('user update failed for user ' . $id . ': ' . $e->getMessage());
        return false;
    }
    if ($ok) {
        error_log('admin updated user ' . $id . ': ' . implode(', ', array_keys($changes)));
    }
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_action_token($_POST['action_token'] ?? '')) {
        header('Location: 3wid_user_list.php?message=' . rawurlencode('Session expired, please try again.'));
        exit;
    }

    $edit_id = (int)($_POST['id'] ?? 0);
    $existing = db_3wordid_get_google_user($edit_id);
    if (!$existing) {
        header('Location: 3wid_user_list.php?message=' . rawurlencode('User not found.'));
        exit;
    }

    $posted = isset($_POST['f']) && is_array($_POST['f']) ? $_POST['f'] : [];
    $changes = [];
    $errors = [];

    foreach ($editable as $column => $info) {
        // Only columns that really exist in this database and were posted.
        if (!array_key_exists($column, $existing) || !array_key_exists($column, $posted)) {
            continue;
        }
        $raw = trim((string)$posted[$column]);
        $old = $existing[$column];

        if ($info['type'] === 'i') {
            if (!preg_match('/^-?[0-9]+$/', $raw)) {
                $errors[] = $column . ' must be a whole number';
                continue;
            }
            $value = (int)$raw;
            if ($column === 'max_3wids' && $value < 0) {
                $errors[] = 'max_3wids cannot be negative';
                continue;
            }
            if ($old !== null && (string)(int)$old === (string)$value) {
                continue;
            }
        } elseif ($info['type'] === 'd') {
            $raw = str_replace(',', '.', $raw);
            if (!is_numeric($raw)) {
                $errors[] = $column . ' must be a number';
                continue;
            }
            $value = (float)$raw;
            if ($old !== null && is_numeric($old) && (float)$old === $value) {
                continue;
            }
        } else {
            $value = $raw;
            if ((string)$old === $value) {
                continue;
            }
        }
        $changes[$column] = $value;
    }

    $new_password = (string)($_POST['new_password'] ?? '');
    if ($new_password !== '' && array_key_exists('password_hash', $existing)) {
        $changes['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    if ($errors) {
        $note = 'Not saved: ' . implode('; ', $errors) . '.';
    } elseif (!$changes) {
        $note = 'Nothing changed.';
    } else {
        $ok = db_3wordid_admin_update_user($edit_id, $changes, $editable);
        $note = $ok ? 'User saved.' : 'Update failed, see the error log.';
    }

    header(
        'Location: 3wid_user_edit.php?id=' . $edit_id .
        '&message=' . rawurlencode($note)
    );
    exit;
}

$edit_id = (int)($_GET['id'] ?? 0);
$message = isset($_GET['message']) ? (string)$_GET['message'] : '';

$user = db_3wordid_get_google_user($edit_id);
if (!$user) {
    header('Location: 3wid_user_list.php?message=' . rawurlencode('User not found.'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $google_stats; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3WordID — Edit user <?php echo (int)$user['id']; ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <style>
      :root {
        --navy: #0F1C3F;
        --navy-hover: #162756;
        --slate: #5B6478;
        --muted: #8A93A6;
        --line: #E6E3DC;
        --paper: #F7F6F3;
        --white: #FFFFFF;
        --gold: #B45309;
        --ok: #16794A;
        --danger: #9B1C1C;
        --shadow: 0 10px 30px rgba(15,28,63,.08);
        --well: #F3F1EC;
        --card-line: #ECEAE4;
      }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
        background: var(--paper);
        color: var(--navy);
        min-height: 100vh;
      }
      a { color: var(--navy); text-decoration: none; }
      .site-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 28px;
        background: rgba(247,246,243,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 20;
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--navy);
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: 20px;
      }
      .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        overflow: hidden;
        background: var(--navy);
      }
      .header-actions { display: flex; align-items: center; gap: 10px; }
      .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 14px;
        cursor: pointer;
        border: 1px solid transparent;
        line-height: 1.2;
        text-decoration: none;
      }
      .btn-primary { background: var(--navy); color: #fff; }
      .btn-primary:hover { background: var(--navy-hover); color: #fff; }
      .btn-ghost { background: var(--white); color: var(--navy); border-color: var(--line); }
      .logout-icon { color: var(--navy); display: inline-flex; padding: 6px; }
      .page { max-width: 1100px; margin: 0 auto; padding: 28px 26px 64px; }
      .eyebrow {
        color: var(--gold);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin: 0 0 8px;
      }
      h1 {
        margin: 0 0 8px;
        font-size: clamp(32px, 5vw, 44px);
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.05;
      }
      .subhead { margin: 0 0 24px; color: var(--slate); font-size: 16px; }
      .flash {
        background: #fff;
        border: 1px solid #eceae4;
        border-left: 4px solid var(--gold);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-weight: 600;
      }
      .panel {
        background: var(--white);
        border: 1px solid var(--card-line);
        border-radius: 16px;
        box-shadow: var(--shadow);
        padding: 22px;
      }
      .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
      }
      .field { margin-bottom: 4px; }
      .field.wide { grid-column: 1 / -1; }
      .field label.lbl {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--slate);
        margin-bottom: 6px;
      }
      input[type="text"],
      input[type="password"],
      textarea {
        width: 100%;
        background: var(--white);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 12px 14px;
        font: 15px/1.4 Inter, system-ui, sans-serif;
        color: var(--navy);
        outline: none;
      }
      input[readonly] { background: var(--well); color: var(--slate); }
      input:focus, textarea:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(15,28,63,.08);
      }
      textarea { min-height: 90px; resize: vertical; }
      .hint { font-size: 13px; color: var(--muted); margin: 6px 0 0; }
      .form-actions { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
      .site-footer {
        border-top: 1px solid var(--line);
        padding: 18px 28px 28px;
        color: var(--muted);
        font-size: 13px;
      }
      @media (max-width: 800px) {
        .grid { grid-template-columns: 1fr; }
      }
    </style>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?php echo htmlspecialchars($main_url); ?>" title="to 3wordID.com">
        <span class="brand-mark"><img width="35" src="https://3WordID.com/img/3wid_big.png" alt=""></span>
        3WordID
    </a>
    <div class="header-actions">
        <a class="btn btn-ghost" href="3wid_user_list.php">All users</a>
        <a class="btn btn-ghost" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>">3WordIDs of this user</a>
        <a class="btn btn-ghost" href="3wid_list.php">Dashboard</a>
        <a href="login/logout.php" class="logout-icon" title="log out">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Logout Icon">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</header>

<main class="page">
    <p class="eyebrow">Admin</p>
    <h1>Edit user <?php echo (int)$user['id']; ?></h1>
    <p class="subhead">Only the fields below can be changed. The other details are shown for reference and are never written back.</p>

    <?php if ($message !== '') { ?>
        <div class="flash"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form class="panel" method="post" action="3wid_user_edit.php">
        <input type="hidden" name="action_token" value="<?php echo htmlspecialchars($action_token); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

        <div class="grid">
            <?php foreach ($editable as $column => $info) {
                if (!array_key_exists($column, $user)) {
                    continue;
                }
            ?>
                <div class="field">
                    <label class="lbl" for="field_<?php echo htmlspecialchars($column); ?>">
                        <?php echo htmlspecialchars($info['label']); ?>
                    </label>
                    <input type="text"
                           id="field_<?php echo htmlspecialchars($column); ?>"
                           name="f[<?php echo htmlspecialchars($column); ?>]"
                           <?php if ($info['type'] !== 's') { ?>inputmode="decimal"<?php } ?>
                           value="<?php echo htmlspecialchars((string)$user[$column]); ?>">
                    <?php if ($info['hint'] !== '') { ?>
                        <p class="hint"><?php echo htmlspecialchars($info['hint']); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (array_key_exists('password_hash', $user)) { ?>
            <div class="field wide">
                <label class="lbl" for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                <p class="hint">Leave blank to keep the current password.</p>
            </div>
            <?php } ?>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Save user</button>
            <a class="btn btn-ghost" href="3wid_user_3wids.php?user_id=<?php echo (int)$user['id']; ?>">3WordIDs of this user</a>
            <a class="btn btn-ghost" href="3wid_user_list.php">Back to user list</a>
        </div>
    </form>

    <div class="panel" style="margin-top:18px;">
        <p class="eyebrow">Other details (read-only)</p>
        <div class="grid">
            <?php foreach ($user as $column => $value) {
                if (in_array($column, $hidden_columns, true) || isset($editable[$column])) {
                    continue;
                }
                $is_long = is_string($value) && strlen($value) > 80;
            ?>
                <div class="field<?php echo $is_long ? ' wide' : ''; ?>">
                    <label class="lbl"><?php echo htmlspecialchars($column); ?></label>
                    <input type="text" readonly value="<?php echo htmlspecialchars($value === null ? '(empty)' : (string)$value); ?>">
                </div>
            <?php } ?>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div><?php echo $footer; ?></div>
</footer>
</body>
</html>
