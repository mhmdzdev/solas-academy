<?php
$active = 'dashboard';
$pageTitle = 'Dashboard';
require __DIR__ . '/_header.php';

$stats = [
    ['Courses', val('SELECT COUNT(*) FROM courses'), 'book'],
    ['Trainees', val("SELECT COUNT(*) FROM users WHERE role='student'"), 'users'],
    ['Enrollments', val('SELECT COUNT(*) FROM enrollments'), 'user'],
    ['Certificates issued', val('SELECT COUNT(*) FROM certificates'), 'cert'],
];
$recent = rows('SELECT c.*, u.full_name, co.title FROM certificates c JOIN users u ON u.id=c.user_id JOIN courses co ON co.id=c.course_id ORDER BY c.id DESC LIMIT 8');
?>
<div class="stat-grid">
  <?php foreach ($stats as [$l, $n, $ic]): ?>
    <div class="stat"><?= icon($ic, 30) ?><div><b><?= (int)$n ?></b><span><?= e($l) ?></span></div></div>
  <?php endforeach; ?>
</div>
<div class="panel">
  <h2>Latest certificates</h2>
  <?php if (!$recent): ?><p class="muted">No certificates issued yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th>Serial</th><th>Student</th><th>Course</th><th>Issued</th></tr>
    <?php foreach ($recent as $r): ?>
      <tr><td><a href="<?= url('certificate.php?serial=' . urlencode($r['serial'])) ?>" target="_blank"><?= e($r['serial']) ?></a></td><td><?= e($r['full_name']) ?></td><td><?= e($r['title']) ?></td><td><?= e(fmt_date($r['issued_at'])) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<div class="actions"><a class="btn" href="<?= url('admin/course_edit.php') ?>">+ Add course</a></div>
<?php require __DIR__ . '/_footer.php'; ?>
