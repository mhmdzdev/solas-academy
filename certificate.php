<?php
require __DIR__ . '/includes/functions.php';
$serial = trim($_GET['serial'] ?? '');
$cert = $serial !== '' ? row('SELECT c.*, u.full_name, u.gmc_number, co.title AS course_title FROM certificates c JOIN users u ON u.id=c.user_id JOIN courses co ON co.id=c.course_id WHERE c.serial=?', [$serial]) : null;
$siteName = setting('site_name', cfg('site_name'));

if (!$cert) {
    http_response_code(404);
    $pageTitle = 'Certificate not found';
    include __DIR__ . '/includes/header.php';
    echo '<main class="auth-wrap"><div class="auth-card"><h1>Certificate not found</h1><p class="muted">No certificate matches the serial number you entered. Please check the link and try again.</p><a class="btn btn-block" href="' . e(url('index.php')) . '">Go to the website</a></div></main>';
    $noFooter = true;
    include __DIR__ . '/includes/footer.php';
    exit;
}
$expired = cert_is_expired($cert);
$link = cert_url($cert['serial']);
$sig = setting('signature', '');
$docName = setting('doctor_name', '');
$docTitle = setting('doctor_title', '');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate <?= e($cert['serial']) ?> | <?= e($siteName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{--heading:#174585;--text:#121314;--icon:#4786ea}
*{box-sizing:border-box}
body{margin:0;background:#e9eef6;font-family:'Inter',sans-serif;color:var(--text);padding:20px}
.toolbar{max-width:1123px;margin:0 auto 16px;display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.toolbar .left{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;gap:8px;align-items:center;background:var(--heading);color:#fff;border:0;padding:10px 18px;border-radius:9px;font:600 15px 'Inter',sans-serif;cursor:pointer;text-decoration:none}
.btn.outline{background:#fff;color:var(--heading);border:1px solid #c9d5e8}
.status{padding:6px 14px;border-radius:99px;font-weight:600;font-size:14px}
.status.ok{background:#e8f7ef;color:#14653a}.status.bad{background:#fdecea;color:#8e2a20}
.linkbox{max-width:1123px;margin:0 auto 16px;background:#fff;border:1px solid #d7e0ee;border-radius:10px;padding:10px 14px;font-size:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.linkbox code{background:#eef2f8;padding:4px 8px;border-radius:6px;word-break:break-all;flex:1}
.sheet{width:1123px;height:794px;max-width:100%;margin:0 auto;background:#fff;position:relative;box-shadow:0 10px 40px rgba(23,69,133,.18);overflow:hidden}
.frame{position:absolute;inset:18px;border:3px solid var(--heading);padding:8px}
.frame-in{height:100%;border:1px solid var(--icon);display:flex;flex-direction:column;align-items:center;text-align:center;padding:34px 60px 26px;position:relative}
.logo{height:62px;margin-bottom:10px}
h1.title{font-family:'Playfair Display',serif;font-weight:600;font-size:46px;color:var(--heading);margin:14px 0 6px;letter-spacing:.5px}
.sub{font-size:16px;color:#4a5565;margin:4px 0}
.name{font-family:'Playfair Display',serif;font-size:44px;color:var(--heading);margin:14px 0 8px;font-weight:600;border-bottom:2px solid var(--icon);padding:0 40px 8px;display:inline-block;max-width:100%}
.gmc{margin:0 0 4px}.gmc b{color:var(--text)}
.course{font-size:28px;font-weight:700;color:var(--text);margin:8px 0 0;line-height:1.3}
.dates{display:flex;gap:70px;margin-top:26px;justify-content:center}
.dates div{font-size:13px;color:#4a5565;text-transform:uppercase;letter-spacing:.08em}
.dates b{display:block;font-size:18px;color:var(--text);text-transform:none;letter-spacing:0;margin-top:3px}
.bottom{margin-top:auto;width:100%;display:flex;justify-content:space-between;align-items:flex-end;gap:20px}
.serial{text-align:left;font-size:12.5px;color:#4a5565;line-height:1.7}
.serial b{color:var(--text)}
.signature{text-align:center;min-width:250px}
.signature img{max-height:70px;max-width:230px;display:block;margin:0 auto 2px}
.signature .line{border-top:1.5px solid var(--text);padding-top:6px;font-weight:600;font-size:15px}
.signature .role{font-size:13px;color:#4a5565}
.sig-space{height:70px}
.watermark{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-18deg);font-size:110px;font-weight:700;color:rgba(192,57,43,.14);border:8px solid rgba(192,57,43,.14);padding:0 30px;border-radius:14px;letter-spacing:6px}
@media(max-width:1150px){.sheet{transform-origin:top left}}
@page{size:A4 landscape;margin:0}
@media print{
  body{background:#fff;padding:0}
  .toolbar,.linkbox,.noprint{display:none!important}
  .sheet{box-shadow:none;width:297mm;height:210mm;margin:0}
  .frame{inset:6mm}
  *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>
<div class="toolbar">
  <div class="left">
    <button class="btn" onclick="window.print()">⬇ Download PDF</button>
    <button class="btn outline" id="copy">Copy verification link</button>
    <span class="status <?= $expired ? 'bad' : 'ok' ?>"><?= $expired ? 'Expired' : 'Valid certificate' ?></span>
  </div>
  <a class="btn outline" href="<?= url('index.php') ?>"><?= e($siteName) ?></a>
</div>
<div class="linkbox"><span>Verification link (add it to your CV):</span><code id="lnk"><?= e($link) ?></code></div>
<div style="max-width:100%;overflow-x:auto">
<div class="sheet" id="sheet">
  <div class="frame"><div class="frame-in">
    <img class="logo" src="<?= asset('assets/img/logo.png') ?>" alt="<?= e($siteName) ?>">
    <h1 class="title"><?= e(setting('cert_title', 'Certificate of Completion')) ?></h1>
    <p class="sub">This is to certify that</p>
    <div class="name"><?= e($cert['full_name']) ?></div>
    <?php if (!empty($cert['gmc_number'])): ?><p class="sub gmc">GMC No: <b><?= e($cert['gmc_number']) ?></b></p><?php endif; ?>
    <p class="sub">has successfully completed the course</p>
    <p class="course"><?= e($cert['course_title']) ?></p>
    <div class="dates">
      <div>Date of issue<b><?= e(fmt_date($cert['issued_at'], 'F j, Y')) ?></b></div>
      <div>Valid until<b><?= $cert['expires_at'] ? e(fmt_date($cert['expires_at'], 'F j, Y')) : 'No expiry' ?></b></div>
    </div>
    <div class="bottom">
      <div class="serial">Certificate serial: <b><?= e($cert['serial']) ?></b><br>Verify at: <?= e($link) ?></div>
      <div class="signature">
        <?php if ($sig): ?><img src="<?= url($sig) ?>" alt="Signature"><?php else: ?><div class="sig-space"></div><?php endif; ?>
        <div class="line"><?= e($docName ?: '') ?></div>
        <div class="role"><?= e($docTitle) ?></div>
      </div>
    </div>
    <?php if ($expired): ?><div class="watermark">EXPIRED</div><?php endif; ?>
  </div></div>
</div>
</div>
<script>
document.getElementById('copy').addEventListener('click', function () {
  var t = document.getElementById('lnk').textContent, b = this;
  (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { b.textContent = 'Copied ✓'; }, function () { prompt('Copy this link:', t); });
});
</script>
</body>
</html>
