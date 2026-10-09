<?php
define('INSTALLING', true);
require __DIR__ . '/includes/functions.php';

$hasAdmin = false;
try { $hasAdmin = (int)val("SELECT COUNT(*) FROM users WHERE role IN ('owner','admin')") > 0; } catch (Throwable $e) {}

$errors = [];
$done = false;
$old = ['full_name' => '', 'username' => '', 'email' => ''];

if (!$hasAdmin && is_post()) {
    csrf_check();
    $old = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'username'  => strtolower(trim($_POST['username'] ?? '')),
        'email'     => strtolower(trim($_POST['email'] ?? '')),
    ];
    $pass = $_POST['password'] ?? '';
    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $old['username'])) $errors[] = 'Username: 3-30 characters (letters, numbers, dot, underscore).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        $mysql = cfg('db_driver') === 'mysql';
        $pk = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $long = $mysql ? 'LONGTEXT' : 'TEXT';
        $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $sql = [
            "CREATE TABLE IF NOT EXISTS users (id $pk, username VARCHAR(60) NOT NULL UNIQUE, full_name VARCHAR(190) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'student', gmc_number VARCHAR(30) NULL, created_at VARCHAR(19) NOT NULL)$tail",
            "CREATE TABLE IF NOT EXISTS courses (id $pk, title VARCHAR(255) NOT NULL, content $long, image VARCHAR(255) NULL, cert_validity_months INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'published', created_at VARCHAR(19) NOT NULL)$tail",
            "CREATE TABLE IF NOT EXISTS lessons (id $pk, course_id INT NOT NULL, title VARCHAR(255) NOT NULL, content $long, position INT NOT NULL DEFAULT 0)$tail",
            "CREATE TABLE IF NOT EXISTS enrollments (id $pk, user_id INT NOT NULL, course_id INT NOT NULL, taken_before TINYINT NOT NULL DEFAULT 0, last_taken_date VARCHAR(10) NULL, enrolled_at VARCHAR(19) NOT NULL, completed_at VARCHAR(19) NULL)$tail",
            "CREATE TABLE IF NOT EXISTS lesson_progress (id $pk, enrollment_id INT NOT NULL, lesson_id INT NOT NULL, completed_at VARCHAR(19) NOT NULL)$tail",
            "CREATE TABLE IF NOT EXISTS certificates (id $pk, serial VARCHAR(40) NOT NULL UNIQUE, user_id INT NOT NULL, course_id INT NOT NULL, enrollment_id INT NOT NULL, issued_at VARCHAR(10) NOT NULL, expires_at VARCHAR(10) NULL)$tail",
            "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(100) NOT NULL PRIMARY KEY, v $long)$tail",
        ];
        try {
            foreach ($sql as $s) db()->exec($s);
            insert('users', [
                'username' => $old['username'], 'full_name' => $old['full_name'], 'email' => $old['email'],
                'password' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'owner', 'created_at' => now(),
            ]);
            set_setting('site_name', cfg('site_name'));
            set_setting('cert_title', 'Certificate of Completion');
            set_setting('doctor_name', '');
            set_setting('doctor_title', 'Founder & Lead Instructor');
            staff_key('owner'); staff_key('manager');
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
$pageTitle = 'Install';
$bodyClass = 'auth-page';
$noHeader = true;
include __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <div class="auth-card">
    <img class="auth-logo" src="<?= asset('assets/img/logo.png') ?>" alt="">
    <?php if ($done): ?>
      <h1>Installation complete</h1>
      <p style="text-align:left">The <strong>owner</strong> account was created. <strong>Delete <code>install.php</code> from the server now.</strong></p>
      <div class="alert alert-info" style="text-align:left;word-break:break-all">
        <strong>Save these private links now.</strong> They are not shown anywhere on the public website.<br><br>
        <strong>Owner login link</strong> (only you):<br><code><?= e(staff_link('owner')) ?></code><br><br>
        <strong>Manager login link</strong> (you can send it to your managers):<br><code><?= e(staff_link('manager')) ?></code><br><br>
        You can see/regenerate them later in <em>Admin → Settings → Access links</em>.
      </div>
      <a class="btn btn-block" href="<?= e(staff_link('owner')) ?>">Open owner login</a>
    <?php elseif ($hasAdmin): ?>
      <h1>Already installed</h1>
      <p>The platform is already set up. For security, delete <code>install.php</code> from the server.</p>
      <a class="btn btn-block" href="<?= url('index.php') ?>">Open the site</a>
    <?php else: ?>
      <h1>Install the platform</h1>
      <p class="muted">Create the database tables and the first <strong>owner</strong> account.</p>
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Full name<input name="full_name" value="<?= e($old['full_name']) ?>" required></label>
        <label>Username<input name="username" value="<?= e($old['username']) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
        <label>Password<input type="password" name="password" minlength="8" required></label>
        <button class="btn btn-block">Install</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php $noFooter = true; include __DIR__ . '/includes/footer.php'; ?>
