<?php
require_once dirname(__DIR__) . '/includes/functions.php';
$admin = require_admin();
$siteName = setting('site_name', cfg('site_name'));
$active = $active ?? '';
$nav = [
    'dashboard' => ['index.php', 'Dashboard', 'home'],
    'courses' => ['courses.php', 'Courses', 'book'],
    'students' => ['students.php', 'Trainees', 'users'],
    'certificates' => ['certificates.php', 'Certificates', 'cert'],
    'settings' => ['settings.php', 'Settings', 'cog'],
];
if (is_owner()) $nav = array_slice($nav, 0, 4, true) + ['staff' => ['staff.php', 'Staff', 'user']] + array_slice($nav, 4, null, true);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ?? 'Admin') . ' | Admin') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body>
<div class="admin">
  <aside class="admin-side">
    <div class="brand-box"><img src="<?= asset('assets/img/logo.png') ?>" alt=""></div>
    <?php foreach ($nav as $k => [$href, $label, $ic]): ?>
      <a class="<?= $active === $k ? 'active' : '' ?>" href="<?= url('admin/' . $href) ?>"><?= icon($ic) ?> <?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="sp"></div>
    <div style="padding:8px 12px;font-size:13px;color:#8db7ff"><?= e($admin['full_name']) ?><br><span style="color:#cfe0fa"><?= e(role_label($admin['role'])) ?></span></div>
    <a href="<?= url('index.php') ?>"><?= icon('link') ?> View site</a>
    <a href="<?= url('logout.php?_csrf=' . urlencode(csrf_token())) ?>"><?= icon('back') ?> Log out</a>
  </aside>
  <main class="admin-main">
    <div class="admin-top">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-toggle" type="button" data-toggle-admin><?= icon('menu') ?></button>
        <h1><?= e($pageTitle ?? '') ?></h1>
      </div>
      <div class="actions"><?= $topActions ?? '' ?></div>
    </div>
    <?php foreach (flashes() as [$t, $m]): ?>
      <div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div>
    <?php endforeach; ?>
