<?php
declare(strict_types=1);
?>
<?php
// If the header started output buffering for translation, translate the page body before closing layout markup.
if (!empty($GLOBALS['edupay_translate_body']) && ob_get_level() > 0) {
    echo translate_rendered_html(ob_get_clean());
    $GLOBALS['edupay_translate_body'] = false;
}
?>
<?php if (($layout ?? 'public') === 'admin' || ($layout ?? 'public') === 'student'): ?>
    </main>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<footer class="footer">
    <span><?= h(tr('EduPay+ gestion comptable des etudiants')) ?></span>
</footer>
<?php $jsVersion = (string)@filemtime(__DIR__ . '/../assets/js/main.js'); ?>
<script src="<?= h(url('assets/js/main.js') . '?v=' . $jsVersion) ?>"></script>
</body>
</html>
