<?php
// Expects optional: $page_title, $page_description, $page_canonical, $og_image
$site_name = setting('site_name', 'ViralLinx');
$page_title = $page_title ?? setting('seo_home_title', $site_name . ' - Watch AI Movies & Stories');
$page_description = $page_description ?? setting('seo_home_description', 'Watch trending AI movies, viral videos and stories in high quality on ' . $site_name . '.');
$page_canonical = $page_canonical ?? canonical_url($_SERVER['REQUEST_URI'] ?? '/');
$og_image = $og_image ?? setting('seo_default_image', '');
$user = current_user();
$nav_active = strtok(trim($_SERVER['REQUEST_URI'] ?? '/', '/'), '?');
try { $nav_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC LIMIT 14")->fetchAll(); } catch (Exception $e) { $nav_categories = []; }
$nav_tags = get_all_tags(12);
function yt_svg($path) { return '<svg viewBox="0 0 24 24"><path d="' . $path . '"/></svg>'; }
$yt_icons = [
    'home' => 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',
    'browse' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm3.5-11.5-2.1 5.4-5.4 2.1 2.1-5.4z',
    'popular' => 'M13.5 0.67s0.74 2.65 0.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l0.03-0.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5 0.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-0.36 3.6-1.21 4.62-2.58 0.39 1.29 0.59 2.65 0.59 4.04 0 2.65-2.15 4.8-4.8 4.8z',
    'new' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 11h-5v-2h3V6h2v7z',
    'liked' => 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
    'random' => 'M10.59 9.17 5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm0.33 9.41-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z',
    'profile' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
    'admin' => 'M12 2 4 5v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V5l-8-3z',
    'category' => 'M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z',
    'tag' => 'M21.41 11.58l-9-9A2 2 0 0 0 11 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 .59 1.42l9 9A2 2 0 0 0 13 22a2 2 0 0 0 1.41-.59l7-7A2 2 0 0 0 22 13a2 2 0 0 0-.59-1.42zM6.5 8A1.5 1.5 0 1 1 8 6.5 1.5 1.5 0 0 1 6.5 8z',
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?></title>
    <meta name="description" content="<?php echo e($page_description); ?>">
    <?php if (setting('seo_keywords')): ?><meta name="keywords" content="<?php echo e(setting('seo_keywords')); ?>"><?php endif; ?>
    <link rel="canonical" href="<?php echo e($page_canonical); ?>">
    <meta name="robots" content="index, follow">
    <!-- Open Graph -->
    <meta property="og:site_name" content="<?php echo e($site_name); ?>">
    <meta property="og:title" content="<?php echo e($page_title); ?>">
    <meta property="og:description" content="<?php echo e($page_description); ?>">
    <meta property="og:url" content="<?php echo e($page_canonical); ?>">
    <meta property="og:type" content="<?php echo isset($og_type) ? e($og_type) : 'website'; ?>">
    <?php if ($og_image): ?><meta property="og:image" content="<?php echo e($og_image); ?>"><?php endif; ?>
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($page_title); ?>">
    <meta name="twitter:description" content="<?php echo e($page_description); ?>">
    <?php if ($og_image): ?><meta name="twitter:image" content="<?php echo e($og_image); ?>"><?php endif; ?>
    <?php if (setting('google_site_verification')): ?><meta name="google-site-verification" content="<?php echo e(setting('google_site_verification')); ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css?v=3">
    <?php if (isset($extra_head)) echo $extra_head; ?>
    <?php echo setting('google_analytics_code'); ?>
    <?php echo setting('header_code'); ?>
    <?php echo render_ads('header', false); ?>
</head>
<body>

<header class="navbar">
    <button class="menu-toggle-btn" onclick="toggleSidebar()" aria-label="Menu">
        <svg viewBox="0 0 24 24"><path d="M21 6H3V5h18v1zm0 5H3v1h18v-1zm0 6H3v1h18v-1z"/></svg>
    </button>

    <a href="<?php echo url(''); ?>" class="brand-logo">
        <svg viewBox="0 0 90 64" xmlns="http://www.w3.org/2000/svg">
            <path d="M88.32 10.14a11.25 11.25 0 0 0-7.91-7.96C73.4.5 45 .5 45 .5s-28.4 0-35.41 1.68a11.25 11.25 0 0 0-7.9 7.96C0 17.17 0 32 0 32s0 14.83 1.69 21.86a11.25 11.25 0 0 0 7.9 7.96C16.6 63.5 45 63.5 45 63.5s28.4 0 35.41-1.68a11.25 11.25 0 0 0 7.91-7.96C90 46.83 90 32 90 32s0-14.83-1.68-21.86z" fill="#ff0033"/>
            <path d="M36 45.5V18.5L60 32z" fill="#fff"/>
        </svg>
        <span class="brand-text"><?php echo e(setting('site_name', 'ViralLinx')); ?></span>
    </a>

    <form class="search-box" method="GET" action="<?php echo url('search'); ?>">
        <input type="text" name="q" placeholder="Search videos, tags..." value="<?php echo e($_GET['q'] ?? ''); ?>">
        <button type="submit" aria-label="Search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </button>
    </form>

    <div class="nav-auth">
        <?php if ($user): ?>
            <div class="user-menu">
                <button class="user-chip" onclick="document.querySelector('.user-dropdown').classList.toggle('open')">
                    <span class="avatar" style="background:<?php echo e($user['avatar_color']); ?>"><?php echo e(strtoupper(substr($user['username'], 0, 1))); ?></span>
                </button>
                <div class="user-dropdown">
                    <a href="<?php echo url('profile'); ?>"><?php echo yt_svg($yt_icons['profile']); ?> My Profile</a>
                    <?php if ($user['role'] === 'admin'): ?><a href="<?php echo url('admin'); ?>"><?php echo yt_svg($yt_icons['admin']); ?> Admin Panel</a><?php endif; ?>
                    <a href="<?php echo url('logout'); ?>">Log out</a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo url('login'); ?>" class="auth-btn"><?php echo yt_svg($yt_icons['profile']); ?><span>Sign in</span></a>
        <?php endif; ?>
    </div>
</header>

<aside class="yt-sidebar" id="ytSidebar">
    <div class="yt-sidebar-section">
        <a href="<?php echo url(''); ?>" class="yt-sidebar-link <?php echo $nav_active === '' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['home']); ?> Home</a>
        <a href="<?php echo url('browse'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'browse' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['browse']); ?> Browse</a>
        <a href="<?php echo url('popular'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'popular' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['popular']); ?> Popular</a>
        <a href="<?php echo url('new'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'new' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['new']); ?> New</a>
        <a href="<?php echo url('most-liked'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'most-liked' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['liked']); ?> Most Liked</a>
        <a href="<?php echo url('random'); ?>" class="yt-sidebar-link"><?php echo yt_svg($yt_icons['random']); ?> Random</a>
    </div>
    <div class="yt-sidebar-section">
        <div class="yt-sidebar-heading">You</div>
        <?php if ($user): ?>
            <a href="<?php echo url('profile'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'profile' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['profile']); ?> My Profile</a>
            <?php if ($user['role'] === 'admin'): ?><a href="<?php echo url('admin'); ?>" class="yt-sidebar-link"><?php echo yt_svg($yt_icons['admin']); ?> Admin Panel</a><?php endif; ?>
        <?php else: ?>
            <a href="<?php echo url('login'); ?>" class="yt-sidebar-link"><?php echo yt_svg($yt_icons['profile']); ?> Sign in</a>
            <a href="<?php echo url('register'); ?>" class="yt-sidebar-link"><?php echo yt_svg($yt_icons['liked']); ?> Create account</a>
        <?php endif; ?>
    </div>
    <div class="yt-sidebar-section">
        <div class="yt-sidebar-heading">Tags</div>
        <a href="<?php echo url('tags'); ?>" class="yt-sidebar-link <?php echo $nav_active === 'tags' ? 'active' : ''; ?>"><?php echo yt_svg($yt_icons['tag']); ?> All Tags</a>
        <?php foreach ($nav_tags as $t): ?>
            <a href="<?php echo url('tag/' . urlencode($t['name'])); ?>" class="yt-sidebar-link sidebar-section-hide"><?php echo yt_svg($yt_icons['tag']); ?> #<?php echo e($t['name']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($nav_categories)): ?>
    <div class="yt-sidebar-section">
        <div class="yt-sidebar-heading">Categories</div>
        <?php foreach ($nav_categories as $cat): ?>
            <a href="<?php echo url('browse?cat=' . $cat['id']); ?>" class="yt-sidebar-link sidebar-section-hide"><?php echo yt_svg($yt_icons['category']); ?> <?php echo e($cat['name']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="yt-sidebar-note">ViralLinx &copy; <?php echo date('Y'); ?><br>Watch. Like. Share.</div>
</aside>

<main class="yt-layout">
<div class="main-container">
