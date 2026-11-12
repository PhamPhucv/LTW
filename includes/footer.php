<?php

?>
<footer class="footer mt-auto py-3 bg-white border-top text-muted">
    <div class="container-fluid px-lg-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small">
        <div>
            &copy; <?= date('Y') ?> <strong>CRM Pro</strong> - Bài tập lớn môn Lập trình Web.
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-secondary border">PHP PDO</span>
            <span class="badge bg-secondary-subtle text-secondary border">MySQL</span>
            <span class="badge bg-secondary-subtle text-secondary border">Bootstrap 5</span>
            <span class="badge bg-secondary-subtle text-secondary border">jQuery AJAX</span>
        </div>
    </div>
</footer>

<?php require_once __DIR__ . '/chatbot.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/app.js"></script>

<script src="assets/js/chatbot.js"></script>

<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
