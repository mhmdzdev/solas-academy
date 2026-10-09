<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $c = row('SELECT * FROM courses WHERE id=?', [$id]);
    if ($c) {
        q('DELETE FROM lesson_progress WHERE enrollment_id IN (SELECT id FROM enrollments WHERE course_id=?)', [$id]);
        q('DELETE FROM certificates WHERE course_id=?', [$id]);
        q('DELETE FROM enrollments WHERE course_id=?', [$id]);
        q('DELETE FROM lessons WHERE course_id=?', [$id]);
        q('DELETE FROM courses WHERE id=?', [$id]);
        delete_upload($c['image']);
        flash('success', 'Course deleted.');
    }
    redirect('admin/courses.php');
}

$active = 'courses';
$pageTitle = 'Courses';
$topActions = '<a class="btn" href="' . url('admin/course_edit.php') . '">+ Add course</a>';
require __DIR__ . '/_header.php';
$courses = rows('SELECT c.*, (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons, (SELECT COUNT(*) FROM enrollments en WHERE en.course_id=c.id) AS enrolled FROM courses c ORDER BY c.id DESC');
?>
<div class="panel">
<?php if (!$courses): ?>
  <p class="muted">No courses yet. Click “Add course” to create the first one.</p>
<?php else: ?>
  <div class="table-wrap"><table class="table">
    <tr><th></th><th>Title</th><th>Status</th><th>Lessons</th><th>Enrolled</th><th>Certificate validity</th><th></th></tr>
    <?php foreach ($courses as $c): ?>
    <tr>
      <td><?php if ($c['image']): ?><img class="thumb-sm" src="<?= url($c['image']) ?>" alt=""><?php endif; ?></td>
      <td><strong><?= e($c['title']) ?></strong></td>
      <td><span class="badge <?= $c['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
      <td><?= (int)$c['lessons'] ?></td>
      <td><?= (int)$c['enrolled'] ?></td>
      <td><?= e(validity_label($c['cert_validity_months'])) ?></td>
      <td class="actions">
        <a class="btn btn-outline btn-sm" href="<?= url('admin/course_edit.php?id=' . $c['id']) ?>">Edit</a>
        <a class="btn btn-outline btn-sm" href="<?= url('course.php?id=' . $c['id']) ?>" target="_blank">Preview</a>
        <form method="post" data-confirm="Delete this course with all its lessons, enrollments and certificates?">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-danger btn-sm">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
