<?php
require_once dirname(__DIR__) . '/includes/functions.php';
$me = require_owner();
$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'role' => 'manager'];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $old = [
            'full_name' => preg_replace('/\s+/', ' ', trim($_POST['full_name'] ?? '')),
            'username' => strtolower(trim($_POST['username'] ?? '')),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'role' => ($_POST['role'] ?? '') === 'owner' ? 'owner' : 'manager',
        ];
        $pass = $_POST['password'] ?? '';
        if ($old['full_name'] === '') $errors[] = 'Full name is required.';
        if (!preg_match('/^[a-z0-9_.]{3,30}$/', $old['username'])) $errors[] = 'Username must be 3–30 characters: letters, numbers, dot or underscore.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!$errors && val('SELECT COUNT(*) FROM users WHERE username=?', [$old['username']])) $errors[] = 'This username is already taken.';
        if (!$errors && val('SELECT COUNT(*) FROM users WHERE email=?', [$old['email']])) $errors[] = 'This email is already registered.';
        if (!$errors) {
            insert('users', [
                'username' => $old['username'], 'full_name' => $old['full_name'], 'email' => $old['email'],
                'password' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $old['role'], 'created_at' => now(),
            ]);
            flash('success', role_label($old['role']) . ' account created. Send them the ' . strtolower(role_label($old['role'])) . ' login link from Settings → Access links.');
            redirect('admin/staff.php');
        }
    } elseif ($action === 'delete' || $action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $t = row('SELECT * FROM users WHERE id=?', [$id]);
        // Owners can only act on managers (never on owners, never on themselves).
        if (!$t || $t['role'] !== 'manager') {
            flash('error', 'You can only remove or reset managers here.');
        } elseif ($action === 'delete') {
            q('DELETE FROM users WHERE id=?', [$id]);
            flash('success', 'Manager removed.');
        } else {
            $np = $_POST['password'] ?? '';
            if (strlen($np) < 8) flash('error', 'New password must be at least 8 characters.');
            else { update('users', ['password' => password_hash($np, PASSWORD_DEFAULT)], $id); flash('success', 'Password updated.'); }
        }
        redirect('admin/staff.php');
    }
}

$staff = rows("SELECT * FROM users WHERE role IN ('owner','manager') ORDER BY CASE role WHEN 'owner' THEN 0 ELSE 1 END, id");
$active = 'staff';
$pageTitle = 'Staff';
require __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="panel">
  <h2>Owners &amp; managers</h2>
  <p class="muted" style="font-size:14px">Owners can manage managers and trainees. Managers can manage courses, lessons, certificates and trainees. Staff sign in only through their private link (Settings → Access links).</p>
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Added</th><th></th></tr>
    <?php foreach ($staff as $s): ?>
    <tr>
      <td><?= e($s['full_name']) ?><?= (int)$s['id'] === (int)$me['id'] ? ' <span class="muted">(you)</span>' : '' ?></td>
      <td><?= e($s['username']) ?></td><td><?= e($s['email']) ?></td>
      <td><span class="badge <?= $s['role'] === 'owner' ? 'badge-green' : '' ?>"><?= e(role_label($s['role'])) ?></span></td>
      <td><?= e(fmt_date($s['created_at'])) ?></td>
      <td>
        <?php if ($s['role'] === 'manager'): ?>
        <div class="actions">
          <form method="post" class="actions" style="align-items:center">
            <?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <input type="password" name="password" placeholder="New password" minlength="8" required autocomplete="new-password" style="padding:6px 8px;border:1px solid #cfd8e3;border-radius:8px;width:150px">
            <button class="btn btn-outline btn-sm">Reset</button>
          </form>
          <form method="post" data-confirm="Remove this manager? They will lose access immediately.">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-danger btn-sm">Remove</button>
          </form>
        </div>
        <?php else: ?><span class="muted">—</span><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>

<form method="post" class="form panel">
  <?= csrf_field() ?><input type="hidden" name="action" value="create">
  <h2>Add an owner or manager</h2>
  <label>Full name<input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required></label>
  <div class="form-row">
    <label>Username<input type="text" name="username" value="<?= e($old['username']) ?>" required autocomplete="off"></label>
    <label>Email<input type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="off"></label>
  </div>
  <div class="form-row">
    <label>Temporary password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
    <label>Role
      <select name="role">
        <option value="manager" <?= $old['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
        <option value="owner" <?= $old['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
      </select>
    </label>
  </div>
  <button class="btn">Create account</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
