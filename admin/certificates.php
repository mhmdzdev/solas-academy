<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();
if (is_post()) {
    csrf_check();
    q('DELETE FROM certificates WHERE id=?', [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Certificate revoked.');
    redirect('admin/certificates.php');
}
$active = 'certificates';
$pageTitle = 'Certificates';
require __DIR__ . '/_header.php';
$certs = rows('SELECT c.*, u.full_name, co.title FROM certificates c JOIN users u ON u.id=c.user_id JOIN courses co ON co.id=c.course_id ORDER BY c.id DESC LIMIT 500');
?>
<div class="panel">
<?php if (!$certs): ?><p class="muted">No certificates issued yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th>Serial</th><th>Student</th><th>Course</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr>
    <?php foreach ($certs as $c): $exp = cert_is_expired($c); ?>
      <tr>
        <td><a href="<?= url('certificate.php?serial=' . urlencode($c['serial'])) ?>" target="_blank"><?= e($c['serial']) ?></a></td>
        <td><?= e($c['full_name']) ?></td><td><?= e($c['title']) ?></td>
        <td><?= e(fmt_date($c['issued_at'])) ?></td>
        <td><?= $c['expires_at'] ? e(fmt_date($c['expires_at'])) : 'Never' ?></td>
        <td><span class="badge <?= $exp ? 'badge-red' : 'badge-green' ?>"><?= $exp ? 'Expired' : 'Valid' ?></span></td>
        <td><form method="post" data-confirm="Revoke this certificate? The verification link will stop working.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm">Revoke</button></form></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
