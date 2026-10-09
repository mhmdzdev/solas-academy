<?php
/**
 * Private sign-in for the platform owner and managers.
 * Reached only through a secret link (access.php?k=SECRET). It is not linked anywhere on the site.
 * Owner link -> owner accounts only. Manager link -> manager accounts only.
 */
require __DIR__ . '/includes/functions.php';
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

$key = (string)($_GET['k'] ?? $_POST['k'] ?? '');
$ctx = staff_role_for_key($key);
if (!$ctx) deny_hidden();                       // wrong/unknown key: look like a missing page
if (is_staff()) redirect('admin/index.php');

$error = '';
$ident = '';
if (is_post()) {
    csrf_check();
    $ident = strtolower(trim($_POST['identifier'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $lockUntil = $_SESSION['staff_lock'] ?? 0;
    if ($lockUntil > time()) {
        $error = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $u = row('SELECT * FROM users WHERE (email=? OR username=?) AND role=?', [$ident, $ident, $ctx]);
        if ($u && password_verify($pass, $u['password'])) {
            unset($_SESSION['staff_fails'], $_SESSION['staff_lock']);
            login_user($u);
            redirect('admin/index.php');
        }
        $fails = ($_SESSION['staff_fails'] ?? 0) + 1;
        $_SESSION['staff_fails'] = $fails;
        if ($fails >= 5) { $_SESSION['staff_lock'] = time() + 120; $_SESSION['staff_fails'] = 0; }
        usleep(400000);
        $error = 'Incorrect email/username or password.';
    }
}
$pageTitle = ($ctx === 'owner' ? 'Owner' : 'Manager') . ' sign in';
$bodyClass = 'auth-page';
$noHeader = true;
$noindex = true;
include __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <div class="auth-card">
    <img class="auth-logo" src="<?= asset('assets/img/logo.png') ?>" alt="">
    <h1><?= $ctx === 'owner' ? 'Owner' : 'Manager' ?> sign in</h1>
    <p class="muted">Private access — please do not share this page.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form" action="<?= e(url('access.php')) ?>">
      <?= csrf_field() ?><input type="hidden" name="k" value="<?= e($key) ?>">
      <label>Email or username<input type="text" name="identifier" value="<?= e($ident) ?>" required autofocus autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-block">Sign in</button>
    </form>
  </div>
</main>
<?php $noFooter = true; include __DIR__ . '/includes/footer.php'; ?>
