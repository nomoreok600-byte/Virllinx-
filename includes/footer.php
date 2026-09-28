</div>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand"><?php echo e(setting('site_name', 'ViralLinx')); ?></div>
        <p class="footer-tag"><?php echo e(setting('site_tagline', 'Watch trending AI movies, viral videos and stories in high quality.')); ?></p>
        <nav class="footer-links">
            <a href="<?php echo url(''); ?>">Home</a>
            <a href="<?php echo url('browse'); ?>">Browse</a>
            <a href="<?php echo url('popular'); ?>">Popular</a>
            <a href="<?php echo url('new'); ?>">New</a>
            <a href="<?php echo url('most-liked'); ?>">Most Liked</a>
            <?php
            global $pdo;
            try {
                foreach ($pdo->query("SELECT title, slug FROM pages ORDER BY id ASC") as $p) {
                    echo '<a href="' . url('page/' . e($p['slug'])) . '">' . e($p['title']) . '</a>';
                }
            } catch (Exception $e) {}
            ?>
        </nav>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo e(setting('site_name', 'ViralLinx')); ?>. All rights reserved.</p>
    </div>
    <?php echo render_ads('footer'); ?>
</footer>

<?php echo setting('footer_code'); ?>

<?php
// ---------- Popup Ad ----------
$popup_ads = get_ads('popup');
if (!empty($popup_ads)):
?>
<div id="vl-popup-overlay" style="display:none;">
    <div id="vl-popup-box">
        <button id="vl-popup-close" onclick="document.getElementById('vl-popup-overlay').style.display='none'">&times;</button>
        <?php echo render_ads('popup', false); ?>
    </div>
</div>
<script>
(function() {
    var key = 'vl_popup_shown';
    var freq = <?php echo (int)setting('popup_frequency_hours', 6); ?> * 3600 * 1000;
    var last = parseInt(localStorage.getItem(key) || '0', 10);
    if (Date.now() - last > freq) {
        setTimeout(function() {
            document.getElementById('vl-popup-overlay').style.display = 'flex';
            localStorage.setItem(key, Date.now().toString());
        }, <?php echo (int)setting('popup_delay_seconds', 5); ?> * 1000);
    }
})();
</script>
<?php endif; ?>

<?php
// ---------- Popunder Ad ----------
$popunder_url = trim(setting('popunder_url', ''));
if ($popunder_url):
?>
<script>
(function() {
    var key = 'vl_popunder_shown';
    var freq = <?php echo (int)setting('popunder_frequency_hours', 12); ?> * 3600 * 1000;
    var fired = false;
    function firePopunder() {
        if (fired) return;
        var last = parseInt(localStorage.getItem(key) || '0', 10);
        if (Date.now() - last < freq) return;
        fired = true;
        localStorage.setItem(key, Date.now().toString());
        var w = window.open(<?php echo json_encode($popunder_url); ?>, '_blank');
        if (w) { w.blur(); window.focus(); }
    }
    document.addEventListener('click', firePopunder, { once: false });
})();
</script>
<?php endif; ?>
<?php echo render_ads('popunder', false); ?>

<script src="/assets/app.js?v=3"></script>
<script>
// ---------- YouTube-style sidebar toggle ----------
function toggleSidebar() {
    var isMobile = window.innerWidth <= 900;
    if (isMobile) {
        document.body.classList.toggle('sidebar-open-mobile');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('vl_sidebar', document.body.classList.contains('sidebar-collapsed') ? '0' : '1'); } catch (e) {}
    }
}
try {
    if (localStorage.getItem('vl_sidebar') === '0') document.body.classList.add('sidebar-collapsed');
} catch (e) {}

// Close mobile sidebar / dropdowns on outside click
document.addEventListener('click', function (e) {
    var sidebar = document.getElementById('ytSidebar');
    if (sidebar && document.body.classList.contains('sidebar-open-mobile') && !e.target.closest('#ytSidebar') && !e.target.closest('.menu-toggle-btn')) {
        document.body.classList.remove('sidebar-open-mobile');
    }
    var dd = document.querySelector('.user-dropdown');
    if (dd && dd.classList.contains('open') && !e.target.closest('.user-menu')) {
        dd.classList.remove('open');
    }
});
</script>
</body>
</html>
