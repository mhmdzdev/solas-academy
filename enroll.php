<?php
require __DIR__ . '/includes/functions.php';
$user = require_login();
$cid = (int)($_GET['course'] ?? $_POST['course'] ?? 0);
$course = row("SELECT * FROM courses WHERE id=? AND status='published'", [$cid]);
if (!$course) { http_response_code(404); exit('Course not found.'); }

if (is_staff()) redirect('course.php?id=' . $cid);
$enr = latest_enrollment($user['id'], $cid);
if ($enr) {
    $cert = row('SELECT * FROM certificates WHERE enrollment_id=?', [$enr['id']]);
    $canRetake = $enr['completed_at'] && $cert && cert_is_expired($cert);
    if (!$canRetake) redirect('course.php?id=' . $cid);
}

$error = '';
if (is_post()) {
    csrf_check();
    $taken = $_POST['taken_before'] ?? '';
    $last = trim($_POST['last_taken_date'] ?? '');
    if (!in_array($taken, ['yes', 'no'], true)) {
        $error = 'Please tell us whether you have taken this course before.';
    } elseif ($taken === 'yes') {
        $d = DateTime::createFromFormat('Y-m-d', $last);
        if (!$d || $d->format('Y-m-d') !== $last) $error = 'Please enter the date you last took this course.';
        elseif ($last > date('Y-m-d')) $error = 'The date cannot be in the future.';
    }
    if (!$error) {
        insert('enrollments', [
            'user_id' => $user['id'], 'course_id' => $cid,
            'taken_before' => $taken === 'yes' ? 1 : 0,
            'last_taken_date' => $taken === 'yes' ? $last : null,
            'enrolled_at' => now(),
        ]);
        redirect('course.php?id=' . $cid);
    }
}
$pageTitle = 'Enroll — ' . $course['title'];
include __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap">
  <div class="auth-card wide">
    <h1><?= e($course['title']) ?></h1>
    <p class="muted">Certificate validity: <?= e(validity_label($course['cert_validity_months'])) ?></p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="course" value="<?= (int)$cid ?>">
      <strong>Have you taken this course before?</strong>
      <div class="radio-row">
        <label><input type="radio" name="taken_before" value="yes" <?= ($_POST['taken_before'] ?? '') === 'yes' ? 'checked' : '' ?>> Yes</label>
        <label><input type="radio" name="taken_before" value="no" <?= ($_POST['taken_before'] ?? '') === 'no' ? 'checked' : '' ?>> No</label>
      </div>
      <div id="last-date-box" class="hidden">
        <label>When did you last take this course?
          <input type="date" id="last_taken_date" name="last_taken_date" max="<?= date('Y-m-d') ?>" value="<?= e($_POST['last_taken_date'] ?? '') ?>">
        </label>
      </div>
      <button id="enroll-submit" class="btn btn-block hidden">Enroll now</button>
    </form>
    <p class="auth-alt"><a href="<?= url('index.php') ?>">&larr; Back to courses</a></p>
  </div>
</main>
<?php $noFooter = true; include __DIR__ . '/includes/footer.php'; ?>
