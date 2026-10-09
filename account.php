<?php
require __DIR__ . '/includes/functions.php';
$user = require_login();
$isStudent = $user['role'] === 'student';
$errors = [];
$form = $_POST['form'] ?? '';

if (is_post()) {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    if (!password_verify($current, $user['password'])) {
        $errors[] = 'Your current password is incorrect.';
    } elseif ($form === 'profile') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $gmc = preg_replace('/\s+/', '', trim($_POST['gmc_number'] ?? ''));
        if ($isStudent && empty($user['gmc_number']) && !valid_gmc($gmc)) $errors[] = 'Please enter a valid GMC number (7 digits).';
        if (!preg_match('/^[a-z0-9_.]{3,30}$/', $username)) $errors[] = 'Username must be 3–30 characters: letters, numbers, dot or underscore.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (!$errors && val('SELECT COUNT(*) FROM users WHERE username=? AND id<>?', [$username, $user['id']])) $errors[] = 'This username is already taken.';
        if (!$errors && val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$email, $user['id']])) $errors[] = 'This email is already registered.';
        if (!$errors) {
            $d = ['username' => $username, 'email' => $email];
            if ($isStudent && empty($user['gmc_number'])) $d['gmc_number'] = $gmc;
            update('users', $d, $user['id']);
            flash('success', 'Your account details were updated.');
            redirect('account.php');
        }
        $user['username'] = $username; $user['email'] = $email;
    } elseif ($form === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        elseif ($new !== ($_POST['new_password2'] ?? '')) $errors[] = 'New passwords do not match.';
        if (!$errors) {
            update('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], $user['id']);
            session_regenerate_id(true);
            flash('success', 'Your password was changed.');
            redirect('account.php');
        }
    }
}
$pageTitle = 'Account settings';
include __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="container" style="max-width:720px">
    <div class="page-head"><h1>Account settings</h1><p class="muted"><?php if (!$isStudent): ?>Signed in as <strong><?= e($user['full_name']) ?></strong> (<?= e(role_label($user['role'])) ?>).<?php else: ?>Name on certificates: <strong><?= e($user['full_name']) ?></strong> (contact the administrator to change it).<?php if (!empty($user['gmc_number'])): ?> GMC number: <strong><?= e($user['gmc_number']) ?></strong><?php endif; ?><?php endif; ?></p></div>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

    <form method="post" class="form panel">
      <?= csrf_field() ?><input type="hidden" name="form" value="profile">
      <h2>Username &amp; email</h2>
      <div class="form-row">
        <label>Username<input type="text" name="username" value="<?= e($user['username']) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
      </div>
      <?php if ($isStudent && empty($user['gmc_number'])): ?>
        <label>GMC number<input type="text" name="gmc_number" required inputmode="numeric" maxlength="7" pattern="\d{7}"><span class="hint">Required for your certificate.</span></label>
      <?php endif; ?>
      <label>Current password (to confirm)<input type="password" name="current_password" required autocomplete="current-password"></label>
      <button class="btn">Save changes</button>
    </form>

    <form method="post" class="form panel">
      <?= csrf_field() ?><input type="hidden" name="form" value="password">
      <h2>Change password</h2>
      <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
      <div class="form-row">
        <label>New password<input type="password" name="new_password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirm new password<input type="password" name="new_password2" minlength="8" required autocomplete="new-password"></label>
      </div>
      <button class="btn">Change password</button>
    </form>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
