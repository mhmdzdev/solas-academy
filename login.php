<?php
require __DIR__ . '/includes/functions.php';
$next = $_GET['next'] ?? '';
$safeNext = preg_match('~^/(?!/)~', $next) ? $next : '';
if (current_user()) redirect($safeNext ?: (is_staff() ? 'admin/index.php' : 'dashboard.php'));

$error = '';
$ident = '';
if (is_post()) {
    csrf_check();
    $ident = strtolower(trim($_POST['identifier'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $fails = $_SESSION['login_fails'] ?? 0;
    $lockUntil = $_SESSION['login_lock'] ?? 0;
    if ($lockUntil > time()) {
        $error = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $u = row("SELECT * FROM users WHERE (email=? OR username=?) AND role='student'", [$ident, $ident]);
        if ($u && password_verify($pass, $u['password'])) {
            unset($_SESSION['login_fails'], $_SESSION['login_lock']);
            if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) update('users', ['password' => password_hash($pass, PASSWORD_DEFAULT)], $u['id']);
            login_user($u);
            redirect($safeNext ?: 'dashboard.php');
        }
        $fails++;
        $_SESSION['login_fails'] = $fails;
        if ($fails >= 5) { $_SESSION['login_lock'] = time() + 60; $_SESSION['login_fails'] = 0; }
        $error = 'Incorrect email/username or password.';
    }
}
$pageTitle = 'Log in';
include __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <div class="auth-card">
    <h1>Log in</h1>
    <p class="muted">Use your email or username.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Email or username<input type="text" name="identifier" value="<?= e($ident) ?>" required autofocus autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-block">Log in</button>
    </form>
    <p class="auth-alt">New here? <a href="<?= url('register.php' . ($safeNext ? '?next=' . urlencode($safeNext) : '')) ?>">Create an account</a></p>
  </div>
</main>
<?php $noFooter = true; include __DIR__ . '/includes/footer.php'; ?>
