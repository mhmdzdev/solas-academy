<?php
require __DIR__ . '/includes/functions.php';
$user = require_login();
$cid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$course = row('SELECT * FROM courses WHERE id=?', [$cid]);
if (!$course || ($course['status'] !== 'published' && !is_admin())) { http_response_code(404); exit('Course not found.'); }

$enr = latest_enrollment($user['id'], $cid);
$preview = false;
if (!$enr) {
    if (is_admin()) $preview = true;
    else redirect('enroll.php?course=' . $cid);
}

$lessons = rows('SELECT * FROM lessons WHERE course_id=? ORDER BY position, id', [$cid]);
$doneIds = [];
if ($enr) foreach (rows('SELECT lesson_id FROM lesson_progress WHERE enrollment_id=?', [$enr['id']]) as $r) $doneIds[(int)$r['lesson_id']] = true;

// Mark done / undo
if (is_post() && $enr) {
    csrf_check();
    $lid = (int)($_POST['lesson_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    $lessonRow = row('SELECT * FROM lessons WHERE id=? AND course_id=?', [$lid, $cid]);
    if ($lessonRow) {
        if ($act === 'done' && empty($doneIds[$lid])) {
            insert('lesson_progress', ['enrollment_id' => $enr['id'], 'lesson_id' => $lid, 'completed_at' => now()]);
            [$d, $t] = enrollment_progress($enr);
            if ($t > 0 && $d >= $t && !$enr['completed_at']) {
                q('UPDATE enrollments SET completed_at=? WHERE id=?', [now(), $enr['id']]);
                $enr['completed_at'] = now();
                issue_certificate($enr);
                flash('success', 'Congratulations! You completed the course and your certificate is ready.');
            } else {
                flash('success', 'Lesson marked as done.');
            }
        } elseif ($act === 'undone' && !$enr['completed_at']) {
            q('DELETE FROM lesson_progress WHERE enrollment_id=? AND lesson_id=?', [$enr['id'], $lid]);
        }
    }
    // go to next lesson after "done"
    $go = $lid;
    if ($act === 'done') {
        foreach ($lessons as $i => $l) if ((int)$l['id'] === $lid && isset($lessons[$i + 1])) { $go = (int)$lessons[$i + 1]['id']; break; }
    }
    redirect('course.php?id=' . $cid . '&lesson=' . $go);
}

// Current lesson
$current = null;
$wanted = (int)($_GET['lesson'] ?? 0);
foreach ($lessons as $l) if ((int)$l['id'] === $wanted) { $current = $l; break; }
if (!$current) {
    foreach ($lessons as $l) if (empty($doneIds[(int)$l['id']])) { $current = $l; break; }
    if (!$current && $lessons) $current = $lessons[0];
}
$curIdx = 0;
foreach ($lessons as $i => $l) if ($current && $l['id'] === $current['id']) $curIdx = $i;
$prev = $lessons[$curIdx - 1] ?? null;
$next = $lessons[$curIdx + 1] ?? null;

[$bodyHtml, $toc] = $current ? lesson_with_toc($current['content']) : ['', []];
$doneCount = count($doneIds);
$total = count($lessons);
$cert = $enr ? row('SELECT * FROM certificates WHERE enrollment_id=?', [$enr['id']]) : null;

function render_lesson_toc($toc) {
    if (!$toc) return '<p class="empty">No sections in this lesson.</p>';
    $o = '<ul>';
    foreach ($toc as $t) $o .= '<li><a href="#' . e($t['id']) . '">' . e($t['text']) . '</a></li>';
    return $o . '</ul>';
}
function render_course_toc($lessons, $doneIds, $current, $cid) {
    $o = '<ul>';
    foreach ($lessons as $i => $l) {
        $done = !empty($doneIds[(int)$l['id']]);
        $cls = ($done ? 'done ' : '') . ($current && $l['id'] === $current['id'] ? 'current' : '');
        $o .= '<li><a class="' . trim($cls) . '" href="' . e(url('course.php?id=' . $cid . '&lesson=' . $l['id'])) . '"><span class="num">' . ($done ? icon('check', 13) : ($i + 1)) . '</span><span>' . e($l['title']) . '</span></a></li>';
    }
    return $o . '</ul>';
}

$pageTitle = $course['title'];
$bodyClass = 'player-page';
$noHeader = true;
$noFooter = true;
include __DIR__ . '/includes/header.php';
?>
<div class="player-bar">
  <div class="container">
    <a class="back-link" href="<?= url(is_staff() ? 'admin/courses.php' : 'dashboard.php') ?>"><?= icon('back') ?> <?= is_staff() ? 'Back to admin' : 'Back to my account' ?></a>
    <button class="menu-btn" type="button" data-open-drawer><?= icon('menu') ?> Course menu</button>
  </div>
</div>

<div class="container player">
  <aside class="side">
    <div class="toc-box lesson-toc">
      <h4>In this lesson</h4>
      <?= render_lesson_toc($toc) ?>
    </div>
    <div class="toc-box course-toc">
      <h4><?= e($course['title']) ?></h4>
      <div class="course-progress"><?= $doneCount ?> of <?= $total ?> lessons completed</div>
      <?= render_course_toc($lessons, $doneIds, $current, $cid) ?>
    </div>
  </aside>

  <section>
    <?php if ($preview): ?><div class="alert alert-info">Admin preview — progress is not tracked.</div><?php endif; ?>
    <?php if ($cert): ?>
      <div class="cert-banner">
        <span><strong>Course completed.</strong> Your certificate <?= e($cert['serial']) ?> is ready.</span>
        <a class="btn btn-green btn-sm" href="<?= url('certificate.php?serial=' . urlencode($cert['serial'])) ?>" target="_blank">View certificate</a>
      </div>
    <?php endif; ?>

    <?php if ($current): ?>
    <div class="m-only m-toc toc-box lesson-toc">
      <h4>In this lesson</h4>
      <?= render_lesson_toc($toc) ?>
    </div>
    <article class="lesson-main">
      <h1><?= e($current['title']) ?></h1>
      <div class="lesson-body"><?= $bodyHtml ?></div>

      <div class="lesson-actions">
        <div class="actions">
          <?php if ($prev): ?><a class="btn btn-outline btn-sm" href="<?= url('course.php?id=' . $cid . '&lesson=' . $prev['id']) ?>">&larr; Previous</a><?php endif; ?>
          <?php if ($next): ?><a class="btn btn-outline btn-sm" href="<?= url('course.php?id=' . $cid . '&lesson=' . $next['id']) ?>">Next &rarr;</a><?php endif; ?>
        </div>
        <?php if ($enr): $isDone = !empty($doneIds[(int)$current['id']]); ?>
          <form method="post" class="actions" style="align-items:center">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$cid ?>"><input type="hidden" name="lesson_id" value="<?= (int)$current['id'] ?>">
            <?php if ($isDone): ?>
              <span class="badge badge-green"><?= icon('check', 14) ?> Done</span>
              <?php if (!$enr['completed_at']): ?><input type="hidden" name="action" value="undone"><button class="btn-link" style="font-size:14px">Undo</button><?php endif; ?>
            <?php else: ?>
              <input type="hidden" name="action" value="done"><button class="btn btn-green"><?= icon('check', 18) ?> Mark as done</button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>
    </article>
    <?php else: ?>
      <article class="lesson-main"><h1><?= e($course['title']) ?></h1><p class="muted">Lessons for this course will be added soon.</p></article>
    <?php endif; ?>
  </section>
</div>

<div class="drawer-backdrop" id="drawer-backdrop"></div>
<aside class="drawer" id="course-drawer">
  <div class="drawer-head"><b>Course lessons</b><button type="button" data-close-drawer aria-label="Close"><?= icon('close', 22) ?></button></div>
  <div class="toc-box course-toc" style="border:0;padding:0">
    <div class="course-progress"><?= $doneCount ?> of <?= $total ?> lessons completed</div>
    <?= render_course_toc($lessons, $doneIds, $current, $cid) ?>
  </div>
</aside>
<?php include __DIR__ . '/includes/footer.php'; ?>
