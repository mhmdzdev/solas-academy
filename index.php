<?php
require __DIR__ . '/includes/functions.php';
$courses = rows("SELECT * FROM courses WHERE status='published' ORDER BY id DESC");
$pageTitle = 'Courses';
include __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="container">
    <div class="page-head">
      <h1>Courses</h1>
      <p class="muted">Choose a course, complete its lessons and earn a verifiable certificate.</p>
    </div>
    <?php if (!$courses): ?>
      <div class="panel"><p class="muted" style="margin:0">No courses are available yet. Please check back soon.</p></div>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($courses as $c): ?>
      <article class="card">
        <div class="card-img">
          <?php if ($c['image']): ?><img src="<?= url($c['image']) ?>" alt="<?= e($c['title']) ?>" loading="lazy">
          <?php else: ?><img class="ph" src="<?= asset('assets/img/logo.png') ?>" alt=""><?php endif; ?>
        </div>
        <div class="card-body">
          <p class="card-title"><?= e($c['title']) ?></p>
          <div class="card-meta">
            <a class="btn btn-sm" href="<?= url('enroll.php?course=' . $c['id']) ?>">Enroll now</a>
            <span class="validity"><?= icon('cal', 16) ?> Certificate validity: <?= e(validity_label($c['cert_validity_months'])) ?></span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
