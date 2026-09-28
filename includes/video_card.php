<?php // Expects $v (video row, optionally with category_name) ?>
<a href="<?php echo video_url($v); ?>" class="video-card">
    <div class="thumb-wrapper">
        <img src="<?php echo e($v['thumbnail_url']); ?>" alt="<?php echo e($v['title']); ?>" loading="lazy">
        <?php if (!empty($v['category_name'])): ?>
            <span class="card-cat"><?php echo e($v['category_name']); ?></span>
        <?php endif; ?>
        <div class="play-icon">
            <div class="glass-circle">
                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
        </div>
    </div>
    <div class="card-details">
        <span class="card-avatar" style="background:<?php
            $colors = ['#ff0033', '#3ea6ff', '#2ecc71', '#f59e0b', '#a855f7', '#ec4899', '#e11d48'];
            echo e($colors[abs(crc32($v['title'])) % count($colors)]);
        ?>"><?php echo e(strtoupper(substr(trim($v['title']), 0, 1))); ?></span>
        <div class="card-text">
            <div class="card-title"><?php echo e($v['title']); ?></div>
            <div class="card-meta">
                <span><?php echo format_count($v['views']); ?> views</span>
                <span class="dot">&bull;</span>
                <span><?php echo time_ago($v['created_at']); ?></span>
            </div>
            <div class="card-meta">
                <span>&#10084; <?php echo format_count($v['likes'] ?? 0); ?> likes</span>
                <?php if (!empty($v['tags'])): $first_tags = array_slice(get_video_tags($v), 0, 2); ?>
                    <?php foreach ($first_tags as $tg): ?>
                        <span class="dot">&bull;</span>
                        <span>#<?php echo e($tg); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</a>
