<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();
$errors = [];

if (is_post() && ($_POST['action'] ?? '') === 'regen_key') {
    csrf_check();
    $who = require_owner();
    $r = ($_POST['role'] ?? '') === 'manager' ? 'manager' : 'owner';
    if (cfg($r . '_login_key')) { flash('error', 'This link is fixed in config.local.php — edit it there.'); }
    else { set_setting($r . '_key', bin2hex(random_bytes(16))); flash('success', ucfirst($r) . ' login link regenerated. The old link no longer works.'); }
    redirect('admin/settings.php');
}

if (is_post()) {
    csrf_check();
    try {
        $sig = save_image($_FILES['signature'] ?? null, 'signatures', 3);
    } catch (Throwable $e) { $sig = null; $errors[] = $e->getMessage(); }
    if (!$errors) {
        set_setting('site_name', trim($_POST['site_name'] ?? '') ?: cfg('site_name'));
        set_setting('cert_title', trim($_POST['cert_title'] ?? '') ?: 'Certificate of Completion');
        set_setting('doctor_name', trim($_POST['doctor_name'] ?? ''));
        set_setting('doctor_title', trim($_POST['doctor_title'] ?? ''));
        if ($sig) { delete_upload(setting('signature', '')); set_setting('signature', $sig); }
        if (!empty($_POST['remove_signature']) && !$sig) { delete_upload(setting('signature', '')); set_setting('signature', ''); }
        flash('success', 'Settings saved.');
        redirect('admin/settings.php');
    }
}
$active = 'settings';
$pageTitle = 'Settings';
require __DIR__ . '/_header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form panel">
  <?= csrf_field() ?>
  <h2>Platform</h2>
  <label>Site name<input type="text" name="site_name" value="<?= e(setting('site_name', cfg('site_name'))) ?>"></label>

  <h2 style="margin-top:26px">Certificate design</h2>
  <p class="muted" style="font-size:14px">The certificate shows: platform logo, trainee name (from their account), course name, issue date, expiry date, serial number and the signature below.</p>
  <label>Certificate title<input type="text" name="cert_title" value="<?= e(setting('cert_title', 'Certificate of Completion')) ?>"></label>
  <div class="form-row">
    <label>Doctor / signatory name<input type="text" name="doctor_name" value="<?= e(setting('doctor_name', '')) ?>" placeholder="Dr. Full Name"></label>
    <label>Signatory title<input type="text" name="doctor_title" value="<?= e(setting('doctor_title', '')) ?>" placeholder="Founder & Lead Instructor"></label>
  </div>
  <label>Signature image
    <?php if (setting('signature')): ?>
      <img class="img-preview" style="background:#fff;padding:8px" src="<?= url(setting('signature')) ?>" alt="">
      <span class="hint"><input type="checkbox" name="remove_signature" value="1"> Remove current signature</span>
    <?php endif; ?>
    <input type="file" name="signature" accept="image/*">
    <span class="hint">Use a PNG with a transparent background for best results.</span>
  </label>
  <button class="btn">Save settings</button>
</form>
<?php if (is_owner()): ?>
<div class="panel">
  <h2>Access links (private)</h2>
  <p class="muted" style="font-size:14px">Staff sign in only through these secret links — they are not shown anywhere on the public website. Send the manager link yourself to people you trust. If a link leaks, regenerate it (the old one stops working immediately).</p>
  <?php foreach (['owner' => 'Owner login link', 'manager' => 'Manager login link'] as $r => $label): $lnk = staff_link($r); ?>
    <div class="form" style="margin-bottom:18px">
      <strong><?= e($label) ?></strong>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;align-items:center">
        <input type="text" readonly value="<?= e($lnk) ?>" id="lnk-<?= $r ?>" onclick="this.select()" style="flex:1;min-width:240px;padding:10px 12px;border:1px solid #cfd8e3;border-radius:9px;background:#f7f9fc;font:inherit;font-size:14px">
        <button type="button" class="btn btn-outline btn-sm" data-copy="lnk-<?= $r ?>">Copy</button>
        <form method="post" data-confirm="Regenerate the <?= e($r) ?> link? The old link will stop working.">
          <?= csrf_field() ?><input type="hidden" name="action" value="regen_key"><input type="hidden" name="role" value="<?= e($r) ?>">
          <button class="btn btn-danger btn-sm"<?= cfg($r . '_login_key') ? ' disabled title="Fixed in config.local.php"' : '' ?>>Regenerate</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = document.getElementById(b.getAttribute('data-copy')); i.select();
    (navigator.clipboard ? navigator.clipboard.writeText(i.value) : Promise.reject()).then(function () { b.textContent = 'Copied ✓'; }, function () { document.execCommand('copy'); b.textContent = 'Copied ✓'; });
  });
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
