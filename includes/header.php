<?php
$user = current_user();
$siteName = setting('site_name', cfg('site_name'));
$pageTitle = $pageTitle ?? '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer"><?php endif; ?>
<title><?= e($pageTitle ? $pageTitle . ' | ' . $siteName : $siteName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php if (empty($noHeader)): ?>
<header class="site-header">
  <div class="container header-in">
    <a class="brand" href="<?= url('index.php') ?>"><img src="<?= asset('assets/img/logo.png') ?>" alt="<?= e($siteName) ?>"></a>
    <button class="nav-toggle" type="button" aria-label="Menu" data-toggle-nav><?= icon('menu', 24) ?></button>
    <nav class="main-nav" id="main-nav">
      <a href="<?= url('index.php') ?>">Courses</a>
      <?php if ($user): ?>
        <?php if (is_staff()): ?><a href="<?= url('admin/index.php') ?>">Admin panel</a><?php else: ?><a href="<?= url('dashboard.php') ?>">My Dashboard</a><?php endif; ?>
        <a href="<?= url('account.php') ?>">Account</a>
        <a class="btn btn-outline btn-sm" href="<?= url('logout.php?_csrf=' . urlencode(csrf_token())) ?>">Log out</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Log in</a>
        <a class="btn btn-sm" href="<?= url('register.php') ?>">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php endif; ?>
<?php foreach (flashes() as [$t, $m]): ?>
  <div class="container"><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div></div>
<?php endforeach; ?>
