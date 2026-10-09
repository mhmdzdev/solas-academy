<?php
$active = 'students';
$pageTitle = 'Trainees';
require __DIR__ . '/_header.php';
$s = trim($_GET['s'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id) AS enrolled, (SELECT COUNT(*) FROM certificates c WHERE c.user_id=u.id) AS certs FROM users u WHERE u.role='student'";
$p = [];
if ($s !== '') { $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.gmc_number LIKE ?)'; $p = ["%$s%", "%$s%", "%$s%", "%$s%"]; }
$students = rows($sql . ' ORDER BY u.id DESC LIMIT 300', $p);
?>
<div class="panel">
  <form method="get" class="form" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="text" name="s" value="<?= e($s) ?>" placeholder="Search by name, email or username" style="margin:0">
    <button class="btn">Search</button>
  </form>
  <?php if (!$students): ?><p class="muted">No trainees found.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>GMC</th><th>Username</th><th>Email</th><th>Courses</th><th>Certificates</th><th>Joined</th><th></th></tr>
    <?php foreach ($students as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td><td><?= e($u['gmc_number'] ?? '—') ?></td><td><?= e($u['username']) ?></td><td><?= e($u['email']) ?></td>
        <td><?= (int)$u['enrolled'] ?></td><td><?= (int)$u['certs'] ?></td><td><?= e(fmt_date($u['created_at'])) ?></td>
        <td><a class="btn btn-outline btn-sm" href="<?= url('admin/student_edit.php?id=' . $u['id']) ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
