<?php if (empty($noFooter)): ?>
<footer class="site-footer">
  <div class="container">&copy; <?= date('Y') ?> <?= e(setting('site_name', cfg('site_name'))) ?>. All rights reserved.</div>
</footer>
<?php endif; ?>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
