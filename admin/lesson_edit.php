<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/_editor.php';
require_admin();

$cid = (int)($_GET['course_id'] ?? 0);
$course = row('SELECT * FROM courses WHERE id=?', [$cid]);
if (!$course) { http_response_code(404); exit('Course not found'); }
$id = (int)($_GET['id'] ?? 0);
$lesson = $id ? row('SELECT * FROM lessons WHERE id=? AND course_id=?', [$id, $cid]) : null;
if ($id && !$lesson) { http_response_code(404); exit('Lesson not found'); }

$title = $lesson['title'] ?? '';
$content = $lesson['content'] ?? '';
$errors = [];

if (is_post()) {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    if ($title === '') $errors[] = 'Lesson title is required.';
    if (!$errors) {
        if ($lesson) {
            update('lessons', ['title' => $title, 'content' => $content], $id);
        } else {
            $pos = (int)val('SELECT COALESCE(MAX(position),0) FROM lessons WHERE course_id=?', [$cid]) + 1;
            insert('lessons', ['course_id' => $cid, 'title' => $title, 'content' => $content, 'position' => $pos]);
        }
        flash('success', 'Lesson saved.');
        redirect('admin/course_edit.php?id=' . $cid);
    }
}

$active = 'courses';
$pageTitle = ($lesson ? 'Edit lesson' : 'Add lesson') . ' — ' . $course['title'];
$extraScripts = editor_scripts('#content');
require __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form panel">
  <?= csrf_field() ?>
  <label>Lesson title
    <input type="text" name="title" value="<?= e($title) ?>" required>
  </label>
  <label>Lesson content
    <textarea id="content" name="content"><?= e($content) ?></textarea>
    <span class="hint">Use “Heading 2” for section titles — they appear automatically in the lesson’s table of contents for the trainee.</span>
  </label>
  <div class="actions">
    <button class="btn">Save lesson</button>
    <a class="btn btn-outline" href="<?= url('admin/course_edit.php?id=' . $cid) ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
