<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$u = row("SELECT * FROM users WHERE id=? AND role='student'", [$id]);
if (!$u) { http_response_code(404); exit('Trainee not found'); }
$errors = [];

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        q('DELETE FROM lesson_progress WHERE enrollment_id IN (SELECT id FROM enrollments WHERE user_id=?)', [$id]);
        q('DELETE FROM certificates WHERE user_id=?', [$id]);
        q('DELETE FROM enrollments WHERE user_id=?', [$id]);
        q('DELETE FROM users WHERE id=?', [$id]);
        flash('success', 'Trainee deleted.');
        redirect('admin/students.php');
    }
    $name = trim($_POST['full_name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $gmc = preg_replace('/\s+/', '', trim($_POST['gmc_number'] ?? ''));
    $pass = $_POST['password'] ?? '';
    if ($name === '') $errors[] = 'Full name is required.';
    if (!preg_match('/^[a-z0-9_.]{3,30}$/', $username)) $errors[] = 'Invalid username.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if ($gmc !== '' && !valid_gmc($gmc)) $errors[] = 'GMC number must be 7 digits.';
    if ($pass !== '' && strlen($pass) < 8) $errors[] = 'New password must be at least 8 characters.';
    if (!$errors && val('SELECT COUNT(*) FROM users WHERE username=? AND id<>?', [$username, $id])) $errors[] = 'Username already taken.';
    if (!$errors && val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$email, $id])) $errors[] = 'Email already used.';
    if (!$errors) {
        $d = ['full_name' => $name, 'username' => $username, 'email' => $email, 'gmc_number' => $gmc !== '' ? $gmc : null];
        if ($pass !== '') $d['password'] = password_hash($pass, PASSWORD_DEFAULT);
        update('users', $d, $id);
        flash('success', 'Trainee updated.');
        redirect('admin/student_edit.php?id=' . $id);
    }
    $u = array_merge($u, ['full_name' => $name, 'username' => $username, 'email' => $email, 'gmc_number' => $gmc]);
}

$enrolls = rows('SELECT e.*, c.title FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.id DESC', [$id]);
$active = 'students';
$pageTitle = 'Edit trainee';
require __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form panel">
  <?= csrf_field() ?>
  <label>Full name (printed on certificates)<input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required></label>
  <label>GMC number (printed on certificates)<input type="text" name="gmc_number" value="<?= e($u['gmc_number'] ?? '') ?>" maxlength="7"></label>
  <div class="form-row">
    <label>Username<input type="text" name="username" value="<?= e($u['username']) ?>" required></label>
    <label>Email<input type="email" name="email" value="<?= e($u['email']) ?>" required></label>
  </div>
  <label>New password <span class="hint">Leave empty to keep the current password.</span><input type="password" name="password" autocomplete="new-password"></label>
  <button class="btn">Save</button>
</form>

<div class="panel">
  <h2>Enrollments</h2>
  <?php if (!$enrolls): ?><p class="muted">No enrollments.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th>Course</th><th>Enrolled</th><th>Progress</th><th>Status</th></tr>
    <?php foreach ($enrolls as $en): [$d, $t] = enrollment_progress($en); ?>
      <tr><td><?= e($en['title']) ?></td><td><?= e(fmt_date($en['enrolled_at'])) ?></td><td><?= $d ?>/<?= $t ?></td>
      <td><?= $en['completed_at'] ? '<span class="badge badge-green">Completed</span>' : '<span class="badge">In progress</span>' ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>

<form method="post" data-confirm="Delete this trainee and all their data? This cannot be undone.">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-danger btn-sm">Delete trainee</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
