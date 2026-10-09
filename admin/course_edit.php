<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/_editor.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$course = $id ? row('SELECT * FROM courses WHERE id=?', [$id]) : null;
if ($id && !$course) { http_response_code(404); exit('Course not found'); }
$errors = [];

// lesson actions (only for an existing course)
if ($course && is_post() && in_array($_POST['action'] ?? '', ['delete_lesson', 'save_positions'], true)) {
    csrf_check();
    if ($_POST['action'] === 'delete_lesson') {
        $lid = (int)$_POST['lesson_id'];
        q('DELETE FROM lesson_progress WHERE lesson_id=?', [$lid]);
        q('DELETE FROM lessons WHERE id=? AND course_id=?', [$lid, $id]);
        flash('success', 'Lesson deleted.');
    } else {
        foreach (($_POST['pos'] ?? []) as $lid => $pos) q('UPDATE lessons SET position=? WHERE id=? AND course_id=?', [(int)$pos, (int)$lid, $id]);
        flash('success', 'Lesson order saved.');
    }
    redirect('admin/course_edit.php?id=' . $id);
}

$data = [
    'title' => $course['title'] ?? '',
    'content' => $course['content'] ?? '',
    'cert_value' => 0, 'cert_unit' => 'years',
    'status' => $course['status'] ?? 'published',
];
if ($course) {
    $m = (int)$course['cert_validity_months'];
    if ($m > 0 && $m % 12 === 0) { $data['cert_value'] = $m / 12; $data['cert_unit'] = 'years'; }
    else { $data['cert_value'] = $m; $data['cert_unit'] = 'months'; }
}

if (is_post() && ($_POST['action'] ?? '') === 'save_course') {
    csrf_check();
    $data['title'] = trim($_POST['title'] ?? '');
    $data['content'] = $_POST['content'] ?? '';
    $data['cert_value'] = max(0, (int)($_POST['cert_value'] ?? 0));
    $data['cert_unit'] = ($_POST['cert_unit'] ?? 'years') === 'months' ? 'months' : 'years';
    $data['status'] = ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published';
    if ($data['title'] === '') $errors[] = 'Course title is required.';
    $months = $data['cert_unit'] === 'years' ? $data['cert_value'] * 12 : $data['cert_value'];
    $image = $course['image'] ?? null;
    $newImage = null;
    if (!$errors) {
        try { $newImage = save_image($_FILES['image'] ?? null, 'courses'); }
        catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }
    if (!$errors) {
        if ($newImage) { delete_upload($image); $image = $newImage; }
        if (!empty($_POST['remove_image']) && !$newImage) { delete_upload($image); $image = null; }
        $row = ['title' => $data['title'], 'content' => $data['content'], 'image' => $image, 'cert_validity_months' => $months, 'status' => $data['status']];
        if ($course) { update('courses', $row, $id); flash('success', 'Course updated.'); }
        else { $row['created_at'] = now(); $id = insert('courses', $row); flash('success', 'Course created. Now add its lessons below.'); }
        redirect('admin/course_edit.php?id=' . $id);
    }
}

$lessons = $course ? rows('SELECT * FROM lessons WHERE course_id=? ORDER BY position, id', [$id]) : [];
$active = 'courses';
$pageTitle = $course ? 'Edit course' : 'Add course';
$extraScripts = editor_scripts('#content');
require __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form panel">
  <?= csrf_field() ?><input type="hidden" name="action" value="save_course">
  <label>Course title
    <input type="text" name="title" value="<?= e($data['title']) ?>" required>
  </label>
  <label>Course content / overview
    <textarea id="content" name="content"><?= e($data['content']) ?></textarea>
  </label>
  <label>Course image
    <?php if (!empty($course['image'])): ?>
      <img class="img-preview" src="<?= url($course['image']) ?>" alt="">
      <span class="hint"><input type="checkbox" name="remove_image" value="1"> Remove current image</span>
    <?php endif; ?>
    <input type="file" name="image" accept="image/*">
    <span class="hint">JPG, PNG, WEBP or GIF — up to 5MB. Recommended ratio 16:10.</span>
  </label>
  <div class="form-row">
    <label>Certificate validity
      <span style="display:flex;gap:8px">
        <input type="number" name="cert_value" min="0" value="<?= (int)$data['cert_value'] ?>" style="margin-top:6px">
        <select name="cert_unit" style="margin-top:6px;max-width:140px">
          <option value="years" <?= $data['cert_unit'] === 'years' ? 'selected' : '' ?>>Years</option>
          <option value="months" <?= $data['cert_unit'] === 'months' ? 'selected' : '' ?>>Months</option>
        </select>
      </span>
      <span class="hint">The certificate expires this long after it is issued. Use 0 for no expiry.</span>
    </label>
    <label>Status
      <select name="status">
        <option value="published" <?= $data['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Draft (hidden)</option>
      </select>
    </label>
  </div>
  <p class="muted" style="font-size:14px">The certificate design (logo, signature, wording) is shared by all courses — edit it under <a href="<?= url('admin/settings.php') ?>">Settings</a>. It is issued automatically when a trainee finishes all lessons.</p>
  <button class="btn"><?= $course ? 'Save course' : 'Create course' ?></button>
</form>

<?php if ($course): ?>
<div class="panel">
  <div class="admin-top" style="margin-bottom:10px">
    <h2 style="margin:0">Lessons</h2>
    <a class="btn btn-sm" href="<?= url('admin/lesson_edit.php?course_id=' . $id) ?>">+ Add lesson</a>
  </div>
  <?php if (!$lessons): ?><p class="muted">No lessons yet.</p><?php else: ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save_positions">
    <div class="table-wrap"><table class="table">
      <tr><th>Order</th><th>Title</th><th></th></tr>
      <?php foreach ($lessons as $l): ?>
        <tr>
          <td><input class="pos-input" type="number" name="pos[<?= (int)$l['id'] ?>]" value="<?= (int)$l['position'] ?>"></td>
          <td><?= e($l['title']) ?></td>
          <td class="actions">
            <a class="btn btn-outline btn-sm" href="<?= url('admin/lesson_edit.php?course_id=' . $id . '&id=' . $l['id']) ?>">Edit</a>
            <button class="btn btn-danger btn-sm" type="submit" form="del-<?= (int)$l['id'] ?>">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </table></div>
    <button class="btn btn-outline btn-sm" style="margin-top:12px">Save order</button>
  </form>
  <?php foreach ($lessons as $l): ?>
    <form id="del-<?= (int)$l['id'] ?>" method="post" data-confirm="Delete this lesson?">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete_lesson"><input type="hidden" name="lesson_id" value="<?= (int)$l['id'] ?>">
    </form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
