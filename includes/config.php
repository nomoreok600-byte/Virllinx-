<?php
// ==========================================
// SITE CONFIGURATION
// ==========================================
// Edit database credentials for your cPanel MySQL database
// (Environment variables optionally override these — handy for staging/test copies)

define('DB_HOST', getenv('VL_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('VL_DB_NAME') ?: 'jhrgeenn_Reelvideo6');
define('DB_USER', getenv('VL_DB_USER') ?: 'jhrgeenn_Reelvideo6');
define('DB_PASS', getenv('VL_DB_PASS') ?: 'Qhy0B[sV}=J7BXpO');

// Base URL of the site WITHOUT trailing slash (used for SEO tags & sitemap)
define('SITE_URL', getenv('VL_SITE_URL') ?: 'https://virallinx.site');

// Default admin credentials (created automatically on first run).
// CHANGE THE PASSWORD after first login from Admin > Users.
define('DEFAULT_ADMIN_EMAIL', 'admin@virallinx.site');
define('DEFAULT_ADMIN_PASS', 'admin123');
