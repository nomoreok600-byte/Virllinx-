==========================================
 ViralLinx — Premium Video Platform (PHP)
 YouTube-style UI • Multi-Category • Tags
==========================================

WHAT'S NEW IN THIS RELEASE
--------------------------
1. WATCH PAGE FIXED & REBUILT (YouTube-style)
   - The old vertical reel layout (9:16, phone-style) is gone. Videos now
     play in a proper 16:9 player on desktop, like YouTube.
   - Channel/owner row with avatar, site name, views • upload time.
   - Joined Like/Dislike button group, Share (native share sheet with
     copy-link fallback), and WhatsApp buttons.
   - Tag + category chips, expandable description panel, comments with
     per-comment likes, and an "Up Next" recommendations sidebar (12
     videos, same category first).
   - Safe-embed validation: broken or unsafe embeds show a clean
     "Stream unavailable" fallback instead of a broken player.
2. YOUR OWN LOGO (no more YouTube branding)
   - Site header, admin sidebar, and settings preview now use a custom
     ViralLinx lightning-bolt mark by default.
   - Admin > Settings: upload a logo image (PNG/JPG/WEBP/SVG/ICO, 2 MB
     max) or paste a logo URL, with live preview and one-click remove.
3. SECURITY HARDENING
   - CSRF tokens on every form AND AJAX action (likes, admin actions,
     logo upload), hardened session cookies (HttpOnly/Secure/SameSite,
     ID rotation, User-Agent binding), rate limits on likes + comments,
     security headers (CSP, X-Frame-Options, nosniff, Referrer-Policy,
     Permissions-Policy) in PHP and .htaccess, strict embed validation,
     and write-protected uploads/branding/.
4. PLAYER & LAYOUT POLISH (latest)
   - Player now stays perfectly centered: the 16:9 box no longer gets
     letterboxed by a height clamp, caps at 1280px, and centers in the
     column on any screen size.
   - REEL MODE REMOVED — the site is long-form only (no shorts-style
     vertical playback). The Reel button, full-screen reel overlay, and
     all related CSS/JS were deleted.
   - Header user icon (avatar) reduced from 32px to 26px to match the
     YouTube-style header proportions.
   - Larger video title, and a subtle divider under the channel/owner
     row like YouTube.
   - Stylesheet bumped to v=5 so all visitors get the new styles.

DELIVERABLE / PACKAGE
---------------------
- virallinx-youtube-style.zip — the complete, ready-to-upload site build
  (all PHP pages, assets, .htaccess; excludes tests and git files).
- To deploy on cPanel: upload the zip to public_html via File Manager,
  right-click > Extract, and overwrite the old files. Your existing
  includes/config.php database credentials are preserved; the schema
  auto-migrates on the first visit.
- Verify after upload: open any video page (clean 16:9 watch layout),
  check Admin > Settings for the Logo / Branding box, and hard-refresh
  (Ctrl+F5) once so browsers pick up style.css?v=5 and app.js?v=4.

INSTALLATION (cPanel)
---------------------
1. Upload ALL files (including the hidden .htaccess) to public_html.
2. Edit includes/config.php:
   - Database host, name, user, password (your existing DB works — tables
     are created/upgraded automatically on first visit).
   - SITE_URL: your domain (used for SEO tags & sitemap).
3. Visit your site once — the database schema is auto-created/migrated.
4. Log in to the admin panel at:  https://yourdomain.com/admin
   Default login:  admin@virallinx.site  /  admin123
   >>> CHANGE THIS PASSWORD IMMEDIATELY (Admin > Users > Reset Password).

UPGRADING FROM A PREVIOUS VERSION (safe)
----------------------------------------
- Just upload all files over the old ones. On the first visit the schema
  auto-migrates: it adds a `tags` column to `videos`, creates the
  `video_categories` junction table, and back-fills it from your existing
  single categories. Nothing is deleted or renamed.
- The old ?cat=X links and the category_id column keep working.

CLEAN URLS (no .php)
--------------------
Handled by .htaccess (Apache mod_rewrite — enabled on cPanel by default):
  /                      Home
  /browse /popular /new /most-liked /random /search?q=...
  /watch/123/video-title-slug
  /tags                  All tags cloud
  /tag/ai-movie          Videos with a specific tag (spaces allowed)
  /browse?cats=1,2,3     Multi-category filter (legacy ?cat= still works)
  /login /register /logout /profile
  /page/about-us         Static pages
  /admin                 Admin panel
  /sitemap.xml           Auto-generated XML sitemap
Old links like /watch.php?id=5 are 301-redirected to the new URLs.

FEATURES
--------
Public: YouTube-style dark theme (fixed header, collapsible sidebar,
category chips, YT video cards), fully rebuilt YouTube-style watch page
(16:9 player, owner row, like/dislike group, share buttons, Up Next
sidebar), user registration/login, per-user likes,
comments, watch history, liked videos, profile, multi-category filter
chips (select several categories at once), tag chips on every video,
tag pages (/tags + /tag/<name>), tag-aware search, category filters,
pagination, random video, popular/new/most-liked pages, share buttons
(WhatsApp/copy), mobile responsive with mobile sidebar drawer.

SEO: clean URLs with slugs, canonical tags, meta title/description per
video, Open Graph + Twitter cards, VideoObject JSON-LD structured data
(now including video keywords from tags + categories), XML sitemap,
robots.txt, Google Search Console verification field, 301 redirects.

Admin (/admin — YouTube-Studio-style dark panel): dashboard with metrics,
video manager (add/edit/delete, bulk actions, drafts, featured hero video,
per-video SEO fields, MULTI-SELECT categories, comma-separated TAGS),
categories, comment moderation (approve/spam/delete), full user
management (create, ban, promote to admin, reset password, delete),
static page builder (About/Privacy/DMCA...), CSV export, logo upload /
logo URL with live preview (Settings), and a complete
Ads Manager:
  - Header code (Google AdSense auto ads / any script into <head>)
  - Footer code
  - Google Analytics / Tag Manager code
  - Banner ad units by placement: header, footer, below player,
    above grid, sidebar, popup, popunder, custom
  - Popup modal ads with frequency + delay control
  - Popunder ads with frequency control
  - Pause/activate any ad unit with one click

MULTI-CATEGORY & TAGS (how they work)
-------------------------------------
- A video can belong to ANY number of categories. In Admin > Videos the
  category box is a multi-select (hold Ctrl/Cmd to pick several).
- Tags are comma-separated free-form keywords (max 15 per video). They
  appear as chips on the video page and cards, get their own browse pages
  (/tags, /tag/<name>), are included in search, and are added to the
  VideoObject JSON-LD keywords for Google.
- Internally a `video_categories` junction table stores the pairs; the
  legacy videos.category_id column is kept in sync (primary category) so
  old queries and links never break.

SECURITY
--------
Passwords hashed (bcrypt), PDO prepared statements everywhere, CSRF
tokens on ALL forms AND AJAX actions (likes, admin row actions, logo
upload), includes/ directory blocked from direct access, admin panel
noindex, hardened session cookies (HttpOnly / Secure / SameSite=Lax,
periodic ID rotation, User-Agent binding), per-session rate limits on
likes and comments, security headers (CSP, X-Frame-Options, nosniff,
Referrer-Policy, Permissions-Policy) in PHP and .htaccess, tag input
sanitized (strip_tags + character whitelist) and escaped on output,
strict embed validation on the watch page (http(s) only, no scripts
inside iframes), and uploads/branding/ write-protected at runtime.

BRANDING / LOGO (Admin > Settings)
----------------------------------
- Upload your own logo image (PNG / JPG / WEBP / SVG / ICO, max 2 MB)
  or paste a logo image URL. It replaces the default lightning-bolt
  mark in the site header, admin sidebar, and preview pane.
- Remove the logo to fall back to the built-in ViralLinx mark.
- Logo files are validated by real MIME type (not extension) and stored
  under uploads/branding/ which is protected against script execution.

NOTES
-----
- includes/config.php now optionally reads environment variables
  (VL_DB_HOST, VL_DB_NAME, VL_DB_USER, VL_DB_PASS, VL_SITE_URL) — useful
  for staging copies. Leave them unset on cPanel and the file defaults
  are used exactly as before.
