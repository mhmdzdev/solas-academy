<?php
require __DIR__ . '/includes/functions.php';
$user = require_login();
if (is_staff()) redirect('admin/index.php');

$enrolls = rows('SELECT e.*, c.title, c.image, c.cert_validity_months FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.id DESC', [$user['id']]);
// keep only the latest enrollment per course
$seen = []; $mine = [];
foreach ($enrolls as $en) { if (isset($seen[$en['course_id']])) continue; $seen[$en['course_id']] = true; $mine[] = $en; }
$certs = rows('SELECT c.*, co.title FROM certificates c JOIN courses co ON co.id=c.course_id WHERE c.user_id=? ORDER BY c.id DESC', [$user['id']]);

$pageTitle = 'My Dashboard';
include __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="container">
    <div class="page-head">
      <h1>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
      <p class="muted"><?= e($user['full_name']) ?> &middot; <?= e($user['email']) ?></p>
    </div>

    <div class="panel">
      <h2>My courses</h2>
      <?php if (!$mine): ?>
        <p class="muted">You haven’t enrolled in any course yet.</p>
        <a class="btn" href="<?= url('index.php') ?>">Browse courses</a>
      <?php else: foreach ($mine as $en):
        [$d, $t] = enrollment_progress($en);
        $pct = $t ? (int)round($d / $t * 100) : 0;
        $cert = row('SELECT * FROM certificates WHERE enrollment_id=?', [$en['id']]);
        $expired = $cert && cert_is_expired($cert);
      ?>
      <div class="course-row">
        <?php if ($en['image']): ?><img class="thumb" src="<?= url($en['image']) ?>" alt=""><?php else: ?><div class="thumb"></div><?php endif; ?>
        <div class="info">
          <h3><?= e($en['title']) ?></h3>
          <div class="progress <?= $pct === 100 ? 'full' : '' ?>"><i style="width:<?= $pct ?>%"></i></div>
          <span class="muted" style="font-size:14px"><?= $d ?> of <?= $t ?> lessons &middot; <?= $pct ?>%</span>
          <?php if ($cert): ?>
            <div style="margin-top:6px">
              <span class="badge <?= $expired ? 'badge-red' : 'badge-green' ?>"><?= $expired ? 'Certificate expired ' . e(fmt_date($cert['expires_at'])) : ($cert['expires_at'] ? 'Valid until ' . e(fmt_date($cert['expires_at'])) : 'Certificate — no expiry') ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="actions">
          <?php if ($expired): ?>
            <a class="btn btn-sm" href="<?= url('enroll.php?course=' . $en['course_id']) ?>">Retake course</a>
          <?php else: ?>
            <a class="btn btn-sm" href="<?= url('course.php?id=' . $en['course_id']) ?>"><?= $en['completed_at'] ? 'Review' : ($d ? 'Continue' : 'Start') ?></a>
          <?php endif; ?>
          <?php if ($cert): ?><a class="btn btn-outline btn-sm" target="_blank" href="<?= url('certificate.php?serial=' . urlencode($cert['serial'])) ?>">Certificate</a><?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($certs): ?>
    <div class="panel">
      <h2>My certificates</h2>
      <div class="table-wrap"><table class="table">
        <tr><th>Course</th><th>Serial</th><th>Issued</th><th>Valid until</th><th></th></tr>
        <?php foreach ($certs as $c): ?>
          <tr>
            <td><?= e($c['title']) ?></td><td><?= e($c['serial']) ?></td>
            <td><?= e(fmt_date($c['issued_at'])) ?></td>
            <td><?= $c['expires_at'] ? e(fmt_date($c['expires_at'])) : 'No expiry' ?></td>
            <td><a class="btn btn-outline btn-sm" target="_blank" href="<?= url('certificate.php?serial=' . urlencode($c['serial'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </table></div>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
