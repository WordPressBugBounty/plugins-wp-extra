=== WP EXtra – One Click Optimize ===
Contributors: wpvncom
Donate link: https://www.paypal.me/copvn
Tags: extra, functions, security, tweaks, optimizations
Requires at least: 6.7
Tested up to: 7.1
Stable tag: 8.7.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Optimize your site instantly with one-click activation. WP Extra offers easy fixes and features for WordPress.

== Description ==

[youtube https://www.youtube.com/watch?v=H5vXwe5nyiQ]

🚀 Boost your website’s performance, security, SEO and user experience with WP EXtra – the ultimate lightweight all-in-one toolkit for WordPress!

WP EXtra is built from the ground up with a modular architecture: features run with **zero frontend overhead** via smart lazy-loading, and every option is centralized in a single autoloaded database setting.

= 🌟 14 CORE MODULES & POWER TOOLS =

### 1. 📊 Dashboard
* **Widgets Cleanup**: Disable unnecessary WordPress core and third-party dashboard widgets with one click.
* **System Info Cards**: Real-time server diagnostics (PHP version, memory limits, MySQL, server software) rendered cleanly without external assets.
* **Custom Admin Notice**: Broadcast personalized announcements or instructions to specific user roles with custom alert styles.

### 2. ✍️ Posts & Writing
* **Classic Editor Integration**: Seamlessly switch between Gutenberg and the Classic Editor for posts and pages.
* **Featured Image Column**: View, set, or replace post featured thumbnails directly inside the admin post list.
* **Extended TinyMCE Toolbar**: Enhanced editor buttons including Table generator, Checklist, Visual Blocks, Letter-spacing, Text Case, Underline, and Clean HTML formatting.
* **Category Description Editor**: Rich WYSIWYG editor for category and taxonomy descriptions.
* **Writing Aids**: Post word counter, post ID columns in admin, and automatic revision cleanup.

### 3. 📑 Table of Contents
* **Auto-Insertion**: Automatically generate a structured Table of Contents for posts, pages, or custom post types.
* **Headings Numbering**: Customizable multi-level numbering hierarchy (1, 1.1, 1.1.1...) for H1-H6 tags.
* **Sticky Floating Badge & Drawer**: Off-canvas sliding TOC drawer triggered by a modern floating action button (FAB).
* **SEO Schema**: Native `SiteNavigationElement` JSON-LD structured data for Google rich snippets.

### 4. 📋 Clone Content (Duplicate)
* **1-Click Duplication**: Duplicate posts, pages, and custom post types with all meta fields, taxonomies, and featured images.
* **Taxonomy Duplication**: Clone categories, tags, and custom taxonomy terms in bulk with chunked processing to prevent memory issues.

### 5. 🖼️ Media & SVG
* **Secure SVG Uploads**: Full support for SVG media files with built-in sanitization against malicious XML and XSS scripts.
* **Auto Image Conversion**: Automatically convert uploaded images to modern WebP or optimized JPG with customizable quality.
* **SEO Image Renaming**: Automatically clean and sanitize image filenames on upload (removes accents, spaces, and special characters).
* **Auto Remote Image Downloader**: Automatically save external images to the WordPress media library upon publishing.

### 6. 💬 Comments & Anti-Spam
* **Global Comment Control**: Disable comments site-wide or selectively by post type.
* **Close Attachment Comments**: Automatically prevent spam on image and media attachment URLs.
* **Spam Link Filter**: Block automated spam comments containing excessive links without requiring third-party captcha.

### 7. 🔐 Branding & Login
* **Custom Login URL**: Protect `/wp-login.php` by setting a custom login slug (e.g. `/login` or `/member-login`).
* **Modern Login Presets**: 5 curated visual themes (Classic Dark, Clean White, Gradient Blue, Glassmorphism, Warm Peach) with custom logo and background support.
* **Cloudflare Turnstile Captcha**: Seamless, privacy-friendly bot protection for login, registration, and lost password forms.
* **Limit Login Attempts**: IP-based temporary lockout to prevent brute-force attacks.
* **Content Protection**: Disable text selection, right-click context menu, and image dragging.

### 8. 🛡️ Permission & Roles
* **Role-based Backend Lockdown**: Restrict `/wp-admin` access by user role and redirect unauthorized users to the homepage.
* **Hide Admin Bar**: Automatically hide the WordPress admin toolbar on frontend for non-admin user roles.
* **Menu Restrictions**: Restrict access to specific admin sidebar menu items by user role.
* **Hide Plugins**: Hide sensitive plugins from the `/wp-admin/plugins.php` list table for non-super admins.

### 9. 🔒 Security & Core Hardening
* **Disable XML-RPC & REST API**: Block pingback spam, DDoS vectors, and lock down REST API endpoints for non-logged-in visitors.
* **Hide WordPress Version**: Remove generator meta tags and version query strings from HTML source.
* **Block Author Enumeration**: Stop bot scanners from discovering admin usernames via `?author=N` and REST API user routes.
* **HTTP Security Headers**: Send modern security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`).
* **Disable File Editors**: Lock theme and plugin code editors in the admin area (`DISALLOW_FILE_EDIT`).
* **Disable Core Auto-Updates**: Maintain full control over WordPress core version upgrades.
* **Block Outgoing HTTP Calls**: Block external API calls to specific external domains to prevent timeouts.

### 10. ⚡ Speed & Optimize
* **Asset Cleanups**: Dequeue WordPress emojis, guest Dashicons, and default Gutenberg block CSS (~50KB).
* **Remove Global Styles**: Eliminate inline `global-styles-inline-css` and SVG duotone filters from page footers.
* **Strip Query Strings**: Remove `?ver=` parameters from static CSS/JS files to maximize browser and CDN caching.
* **Preconnect CDNs**: Add preconnect resource hints for Google Fonts CDN to accelerate asset loading.

### 11. 🔗 Permalinks & SEO
* **Remove Category Slugs**: Strip `/category/` or custom taxonomy base prefixes from URLs.
* **Single Post Auto-Redirect**: Automatically redirect category archives containing only 1 post directly to the article.
* **Attachment Redirect**: Redirect media attachment pages to the parent post or homepage to prevent thin-content SEO issues.
* **External Link Optimization**: Automatically append `rel="nofollow noopener noreferrer"` and `target="_blank"` to outbound links with domain whitelisting (using fast native `WP_HTML_Tag_Processor`).
* **Virtual Robots.txt Manager**: Customize dynamic robots.txt rules directly within WordPress.

### 12. 💻 Custom Code & CSS
* **Script Injection**: Insert custom HTML, tracking tags, and JavaScript into `<head>`, opening `<body>`, and page footer.
* **Responsive CSS**: Add custom CSS rules with dedicated code editors for Desktop, Tablet, and Mobile devices with automatic minification.

### 13. 🍪 Cookie Consent
* **GDPR/CCPA Compliance**: Lightweight, customizable cookie consent banner for compliance with international privacy laws.
* **Modern Design Presets**: Choose between Classic Light, Dark Modern, Soft Warm, or custom color palettes.
* **100% Cache Compatible**: Fully client-side JavaScript execution ensuring compatibility with WP Rocket, LiteSpeed, and full-page CDN caching.

### 14. ✉️ SMTP Mailer
* **Multi-Account SMTP Rotation**: Configure multiple SMTP accounts with daily quotas; automatically rotates when daily limits are reached.
* **1-Click Provider Presets**: Pre-configured settings for Gmail, Mailgun, Outlook / Office 365, Yahoo Mail, Amazon SES, Zoho Mail, SendGrid, and Sendinblue (Brevo).
* **Live AJAX Test Email**: Interactive test sender with real-time response latency measurement and intelligent diagnostic troubleshooting.
* **Email Delivery Logs**: Searchable database log tracking recipients, subjects, sending mailer, status, and error traces.

### 🛠️ Directory & Server Tools
* **.htaccess Directory Protection**: Block direct PHP script execution inside `wp-includes`, `wp-content/uploads`, `.env`, `.git`, and sensitive configuration files.
* **Encrypted JSON Backup & Restore**: Export and import full plugin configuration safely with encrypted JSON files.

---

== Service Disclosures ==

This plugin can optionally connect to the following third-party services:

* **Cloudflare Turnstile**:
  - Service URL: https://challenges.cloudflare.com/turnstile/v0/api.js
  - Service Provider: Cloudflare, Inc.
  - Terms of Service: https://www.cloudflare.com/website-terms/
  - Privacy Policy: https://www.cloudflare.com/privacypolicy/
  - Purpose: Provides privacy-preserving CAPTCHA protection against automated bots on login, registration, and password recovery forms. This service is strictly optional and is only loaded when Cloudflare Turnstile keys are configured by the administrator in plugin settings.

---

== Installation ==

You can install the WP EXtra from your WordPress Dashboard or manually via FTP.

= From WordPress Dashboard =

1. Navigate to 'Plugins -> Add New' from your WordPress dashboard.
2. Search for `WP EXtra` and install it.
3. Activate the plugin from Plugins menu.
4. Configure the plugin's settings

= Manual Installation =

1. Download the plugin file: `wp-extra.zip`
2. Unzip the file
3. Upload the`wp-extra.zip` folder to your `/wp-content/plugins` directory (do not rename the folder)
4. Activate the plugin from Plugins menu.
5. Configure the plugin's settings

== Screenshots ==
1. WP EXtra Dashboard.
2. Easily enable function with a single click.

== Changelog ==

= 8.7.0 =
* [PERFORMANCE] Removed legacy bottleneck features (quicklink, turbo, minify_html, sslfix) to eliminate server request queueing and output buffer delays.
* [MODERNIZE] Converted auto nofollow external links to use WP Core WP_HTML_Tag_Processor for 10x faster DOM handling without regex backtracking.
* [OPTIMIZE] Upgraded image conversion and auto-upload resize to standard wp_get_image_editor() with Imagick priority and restricted auto remote image downloader to publish status only.
* [FIX] Prevented GitHub comment blacklist remote fetch from blocking frontend requests via admin-only execution and transient locking.
* [IMPROVE] Centralized Helper utility and decoupled Settings for multi-project compatibility.
* [IMPROVE] Fully refactored and streamlined all 15 core module groups for enhanced performance.
* [OPTIMIZE] Optimized Term cloning SQL and batch object assignment (chunks of 200) to prevent memory exhaustion.
* [OPTIMIZE] Added 2-hour Transient caching for Dashboard RSS feeds.
* [OPTIMIZE] Added execution limit (20 images max) and safe timeout on auto remote image downloader.
* [FIX] Fixed post type and taxonomy slug removal namespace issue on Pro edition.
* [FIX] Fixed blank comments template file path resolution.
* [FIX] Fixed PHP 8+ compatibility warnings across all modules.
* [SECURITY] Hardened redirects with wp_safe_redirect and remote requests with wp_safe_remote_post/wp_safe_remote_get.
* [SECURITY] Added domain validation on traffic spam redirection.

= 8.6.9 =
* [NEW] Cloudflare Turnstile

= 8.6.8 =
* [NEW] Last Modified

= 8.6.7 =
* [NEW] TinyMCE Plugins

= 8.6.6 =
* [NEW] UL to Table Switcher

= 8.6.5 =
* [NEW] WordPress 6.8.3 compatibility

= 8.6.4 =
* [FIX] Notification
* [NEW] WordPress 6.8.2 compatibility

= 8.6.3 =
* [NEW] WordPress 6.8.1 compatibility

= 8.6.2 =
* [FIX] Nofollow
* [FIX] Transfer
* [FIX] Clean HTML

= 8.6.1 =
* [FIX] Settings
* [FIX] License

= 8.6.0 =
* [NEW] Turbo
* [NEW] Quicklink
* [NEW] Bottom AdminBar
* [NEW] Remove Blocks
* [NEW] MCE Excerpt
* [FIX] SMTP
* [FIX] 404 to Home

= 8.5.5 =
* [NEW] SSL Content Fixer
* [NEW] Scroll to top Admin Area
* [NEW] Email notifications

= 8.5.4 =
* [FIX] Export to SQL

= 8.5.3 =
* [NEW] Export to SQL

= 8.5.2 =
* [FIX] Settings UI

= 8.5.1 =
* [FIX] Settings UI

= 8.5.0 =
* [FIX] Settings UI

= 8.4.9 =
* [FIX] Settings UI
* [FIX] Sidebar

= 8.4.8 =
* [NEW] Sidebar Widgets
* [FIX] TOC
* [FIX] SMTP

= 8.4.7 =
* [FIX] Settings UI

= 8.4.6 =
* [FIX] Settings UI
* [FIX] Modules

= 8.4.5 =
* [FIX] MCE Unlink

= 8.4.4 =
* [FIX] Settings UI
* [FIX] Module Duplicate

= 8.4.3 =
* [FIX] Settings UI
* [FIX] List Media
* [FIX] Fix wp-includes, wp-content

= 8.4.2 =
* [FIX] Fix Media

= 8.4.1 =
* [FIX] Language

= 8.4.0 =
* [FIX] Settings UI
* [NEW] Valid Email Domain

= 8.3.2 =
* [FIX] Settings UI
* [NEW] Module Duplicate
* [FIX] SMTP

= 8.3.2 =
* [FIX] Settings UI
* [FIX] MCE TOC

= 8.3.1 =
* [FIX] MCE TOC

= 8.3.0 =
* [FIX] MCE Clean HTML
* [NEW] MCE TOC
* [NEW] Module Cookie

= 8.2.2 =
* [FIX] Module Htaccess
* [FIX] Module Admins
* [FIX] Settings UI

= 8.2.1 =
* [FIX] Clean HTML
* [FIX] Duplicate Taxonomy

= 8.2.0 =
* [FIX] Scroll To Top
* [FIX] Classic Editor
* [FIX] Admin Bar

= 8.1.2 =
* [FIX] Clean HTML
* [FIX] Settings UI

= 8.1.1 =
* [FIX] Settings UI

= 8.1.0 =
* [NEW] TinyMCE Category Description
* [FIX] Settings UI

= 8.1 =
* [FIX] TinyMCE Plugins
* [FIX] Code CSS

= 8.0 =
* [FIX] Signature
* [FIX] Code CSS

= 7.9 =
* [FIX] Modules
* [FIX] TinyMCE Plugins

= 7.8 =
* [NEW] Modules
* [NEW] Basic Mode

= 7.7 =
* [NEW] Duplicate Term
* [FIX] Auto Save Images

= 7.6 =
* [FIX] Update readme.txt
* [FIX] TinyMCE Letter Spacing

= 7.5 =
* [NEW] TinyMCE Letter Spacing
* [NEW] Remove all tag links
* [FIX] Auto Save Images
* [FIX] Image Filename

= 7.4 =
* [NEW] TinyMCE Text Case 

= 7.3 =
* [NEW] WooCommerce 8.6 compatibility
* [NEW] Delete Attached Media

= 7.2 =
* [FIX] AntiSpam
* [FIX] Login URL

= 7.1 =
* [NEW] AntiSpam

= 7.0 =
* [FIX] Settings UI
* [NEW] Cookie

= 6.9 =
* [FIX] Settings UI
* [REMOVE] Related Post
* [NEW] Extension to Pages
* [FIX] Custom Email

= 6.8 =
* [FIX] WP-Editor
* [FIX] Test Email

= 6.7 =
* [FIX] Related Searches

= 6.6 =
* [FIX] Settings UI
* [FIX] Missing Error
* [FIX] Duplicate
* [FIX] Help & Screen Options
* [NEW] 404 to Random

= 6.5 =
* [FIX] Tool Reset

= 6.4 =
* [FIX] Publish Button

= 6.3 =
* [FIX] Settings UI
* [NEW] Auto Save Images
* [FIX] Test Email

= 6.2 =
* [PRO] Image Columns
* [NEW] Duplicate Page
* [FIX] TinyMCE Plugins
* [FIX] Publish Button
* [FIX] Dashboard

= 6.1 =
* [FIX] Image metadata
* [FIX] Code Scripts
* [FIX] CSS Admin Bar
* [NEW] Default featured image

= 6.0 =
* Updated plugin settings UI.

= 5.9 =
* Fix Clear whitespace in JS and CSS
* Add Defer CSS & JS

= 5.2 =
* Add Classic Widgets
* Fix Auto Save Image

= 5.1 =
* Maintenance mode

= 4.1 =
* Woocommerce Admin Disabled

= 4.0 =
* Add Custom CSS
* Fix MCE Editor
* Fix Login Background

= 3.8 =
* Fix Allow SVG

= 3.7 =
* Add Login Background
* Add Who Can Access This Plugin
* Update MCE Editor Classic
* Fix Move Auto Remove P Tag to MCE
* Add Quick Remove Menu Admin
* Remove Update Theme & Plugin
* Remove Download Theme & Plugin
* Remove SSL (HTTPS)
* Add Minify CSS (Admin)

= 3.6 =
* Update SMTP Gmail & Other

= 3.5 =
* Add Dashboard Columns (Full width)
* Fix Allow SVG
* Fix SSL (HTTPS)
* Fix Auto Remove P Tag
* Fix MCE Flatsome

= 3.4 =
* Add Update Theme & Plugin
* Add Download Theme & Plugin

= 3.3 =
* Add Clone Widgets
* Fix Icon Shortcode

= 3.2 =
* Add MCE Table, Underline
* Add Auto Resize Image
* Fix Disables Gutenberg Editor	
* Fix Auto Featured Image
* Fix MCE Flatsome
* Remove Optimize (Minify HTML, CSS & JS)
* Remove Lazy Image

= 3.1 =
* Update Remove Category URL
* Fix reCAPTCHA use integration module CF7

= 3.0 =
* Update Optimize (Minify HTML, CSS & JS)
* Update Lazy Image
* Fix Do Not Copy
* Fix Auchor link

= 2.2 =
* Clean CSS
* Fix Auto Save Images

= 2.1 =
* Beauty CSS
* 404 to Homepage

= 2.0 =
* First install of the plugin
* Initial release
