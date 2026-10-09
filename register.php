<?php
require __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'gmc_number' => ''];
if (is_post()) {
    csrf_check();
    $old = [
        'full_name' => preg_replace('/\s+/', ' ', trim($_POST['full_name'] ?? '')),
        'username' => strtolower(trim($_POST['username'] ?? '')),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'gmc_number' => preg_replace('/\s+/', '', trim($_POST['gmc_number'] ?? '')),
    ];
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (count(explode(' ', $old['full_name'])) < 4) $errors[] = 'Please enter your full four-part name (it will appear on your certificate).';
    if (mb_strlen($old['full_name']) > 150) $errors[] = 'Name is too long.';
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $old['username'])) $errors[] = 'Username must be 3–30 characters: letters, numbers, dot or underscore.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!valid_gmc($old['gmc_number'])) $errors[] = 'Please enter a valid GMC number (7 digits).';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';
    if (!$errors && val('SELECT COUNT(*) FROM users WHERE username=?', [$old['username']])) $errors[] = 'This username is already taken.';
    if (!$errors && val('SELECT COUNT(*) FROM users WHERE email=?', [$old['email']])) $errors[] = 'This email is already registered.';
    if (!$errors) {
        $id = insert('users', [
            'username' => $old['username'], 'full_name' => $old['full_name'], 'gmc_number' => $old['gmc_number'], 'email' => $old['email'],
            'password' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'student', 'created_at' => now(),
        ]);
        login_user(row('SELECT * FROM users WHERE id=?', [$id]));
        flash('success', 'Welcome! Your account has been created.');
        $next = $_GET['next'] ?? '';
        redirect(preg_match('~^/(?!/)~', $next) ? $next : 'dashboard.php');
    }
}
$pageTitle = 'Sign up';
include __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <div class="auth-card wide">
    <h1>Create your account</h1>
    <p class="muted">Register once to enroll in any course.</p>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>Full name (four names)
        <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required autocomplete="name">
        <span class="hint">Enter it exactly as you want it printed on your certificate.</span>
      </label>
      <label>GMC number
        <input type="text" name="gmc_number" value="<?= e($old['gmc_number']) ?>" required inputmode="numeric" maxlength="7" pattern="\d{7}">
        <span class="hint">Your 7-digit General Medical Council reference number. It will appear on your certificate.</span>
      </label>
      <div class="form-row">
        <label>Username<input type="text" name="username" value="<?= e($old['username']) ?>" required autocomplete="username"></label>
        <label>Email<input type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="email"></label>
      </div>
      <div class="form-row">
        <label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirm password<input type="password" name="password2" minlength="8" required autocomplete="new-password"></label>
      </div>
      <button class="btn btn-block">Create account</button>
    </form>
    <p class="auth-alt">Already have an account? <a href="<?= url('login.php' . (isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '')) ?>">Log in</a></p>
  </div>
</main>
<?php $noFooter = true; include __DIR__ . '/includes/footer.php'; ?>
