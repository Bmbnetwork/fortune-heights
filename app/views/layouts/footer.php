<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> <?= SCHOOL_NAME ?>. All rights reserved.</p>
        <p class="text-muted">Located at <?= SCHOOL_ADDRESS ?></p>
    </div>
</footer>

<style>
.footer {
    padding: 20px 25px;
    text-align: center;
    border-top: 1px solid var(--gray-200);
    background: var(--white);
    margin-top: 30px;
}
.footer p { margin: 3px 0; font-size: 13px; }
</style>

<!-- Main JavaScript -->
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>

</body>
</html>