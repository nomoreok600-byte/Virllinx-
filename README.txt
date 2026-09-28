==========================================
 ViralLinx — Premium Video Platform (PHP)
 YouTube-style UI • Multi-Category • Tags
==========================================

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
category chips, YT video cards), user registration/login, per-user likes,
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
static page builder (About/Privacy/DMCA...), CSV export, and a complete
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
tokens on forms, includes/ directory blocked from direct access,
admin panel noindex, security headers in .htaccess, tag input sanitized
(strip_tags + character whitelist) and escaped on output.

NOTES
-----
- includes/config.php now optionally reads environment variables
  (VL_DB_HOST, VL_DB_NAME, VL_DB_USER, VL_DB_PASS, VL_SITE_URL) — useful
  for staging copies. Leave them unset on cPanel and the file defaults
  are used exactly as before.
