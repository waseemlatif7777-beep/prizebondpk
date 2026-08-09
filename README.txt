╔══════════════════════════════════════════════════════════════════╗
║        PRIZE BOND PK — Complete Single-File PHP Website         ║
║        Version 1.0.0  |  www.prizebond.pk                       ║
╚══════════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FILES IN THIS PACKAGE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  index.php    ← MAIN WEBSITE  (upload this to public_html/)
  .htaccess    ← Apache security, GZIP, caching rules
  sitemap.php  ← Dynamic XML sitemap for Google
  robots.txt   ← Search engine crawl instructions
  README.txt   ← This file (do NOT upload to server)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 1 — CREATE DATABASE IN CPANEL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Log in to cPanel → MySQL Databases
2. Under "Create New Database":
   → Type a name, e.g.:  prizebond_db
   → Click "Create Database"

3. Under "MySQL Users" → "Add New User":
   → Username:  pb_user
   → Password:  (use a strong password, write it down)
   → Click "Create User"

4. Under "Add User To Database":
   → User:      pb_user
   → Database:  prizebond_db
   → Click "Add"  →  Grant ALL PRIVILEGES  →  Make Changes

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 2 — CONFIGURE index.php
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Open index.php in a text editor (Notepad++, VS Code, etc.)
Find the CONFIG section near the top (lines ~20-30) and edit:

  define('DB_HOST',    'localhost');        ← usually localhost
  define('DB_NAME',    'cpanelusername_prizebond_db'); ← full DB name
  define('DB_USER',    'cpanelusername_pb_user');      ← full DB user
  define('DB_PASS',    'YourStrongPassword123!');      ← your password
  define('ADMIN_PASS', 'YourAdminPassword!');          ← admin panel password
  define('SITE_NAME',  'Prize Bond PK');               ← your site name

  ⚠️  IMPORTANT: cPanel adds your username as a prefix automatically.
      For example, if your cPanel username is "mysite":
        Database Name → mysite_prizebond_db
        DB User       → mysite_pb_user

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 3 — UPLOAD FILES TO CPANEL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

METHOD A — Using cPanel File Manager (recommended):
  1. cPanel → File Manager → public_html
  2. Click "Upload" → Upload these files:
       index.php
       .htaccess
       sitemap.php
       robots.txt
  3. Make sure .htaccess is visible (click "Settings" → Show Hidden Files)

METHOD B — Using FTP (FileZilla etc.):
  Host:     ftp.yourdomain.com
  Username: your cPanel username
  Password: your cPanel password
  Port:     21
  Upload all 4 files to: /public_html/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 4 — FIRST VISIT (AUTO SETUP)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Open your browser and visit:  https://yourdomain.com

2. The website will automatically:
   ✓ Create 6 database tables (pb_bond_types, pb_draws, pb_winners,
     pb_schedules, pb_subscribers, pb_search_log)
   ✓ Insert all 9 prize bond types with correct prize amounts
   ✓ Show the homepage with placeholder cards

3. You should see the homepage with:
   - Green navbar with "Prize Bond PK" branding
   - Hero search section
   - Empty draw results ("No draw results yet")
   - All 9 bond category cards
   - FAQ, Newsletter, Footer

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 5 — ADMIN PANEL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  URL:  https://yourdomain.com/?page=admin
  Password: whatever you set for ADMIN_PASS in the CONFIG

ADMIN MENU TABS:
  ┌────────────┬──────────────────────────────────────────────────┐
  │ All Draws  │ List all saved draw results. Edit / Delete.      │
  │ Add Draw   │ Add a new draw result with winning numbers.      │
  │ Schedule   │ Add upcoming draw dates for the schedule page.   │
  │ Subscribers│ View newsletter email subscribers. Export CSV.   │
  └────────────┴──────────────────────────────────────────────────┘

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
HOW TO ADD A DRAW RESULT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Go to Admin → "Add Draw" tab
2. Fill in the form:
   • Bond Type    → Select denomination (e.g. Rs.750 Prize Bond)
   • Draw Number  → Official draw serial (e.g. 97)
   • Draw Date    → Date of draw (e.g. 2025-07-15)
   • City         → Where draw was held (e.g. Lahore)
   • PDF URL      → Optional: link to official SBP PDF result

3. Enter winning numbers:
   • First Prize  → Paste the 1 winning number
   • Second Prize → Paste the 3 winning numbers (one per line)
   • Third Prize  → Paste all ~1696 winning numbers

   TIP: You can paste numbers separated by:
        Newlines (one per line) — recommended for third prize
        Commas:   123456, 789012, 345678
        Spaces:   123456 789012 345678

4. Click "Save Draw" — winners are saved instantly.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BULK IMPORT FROM CSV / EXCEL FILE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

On the Add Draw page, scroll to "Import from CSV / Excel File":

  CSV FORMAT (two columns):
  ┌──────────┬──────────────┐
  │ prize_type│ winning_number│
  ├──────────┼──────────────┤
  │ first    │ 0234567      │
  │ second   │ 1234567      │
  │ second   │ 2345678      │
  │ second   │ 3456789      │
  │ third    │ 4567890      │
  │ third    │ 5678901      │
  │ ...      │ ...          │
  └──────────┴──────────────┘

  EXCEL FORMAT: Same two-column structure, saved as .xlsx or .xls

  SIMPLE FORMAT: Just a single column of numbers (all treated as
  third prize). Useful when you already have 1st/2nd entered and
  just need to bulk-import the third prize numbers.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ALL WEBSITE PAGES (URL STRUCTURE)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Homepage:         https://yourdomain.com/
  Search:           https://yourdomain.com/?page=search
  Schedule:         https://yourdomain.com/?page=schedule
  About:            https://yourdomain.com/?page=about
  Contact:          https://yourdomain.com/?page=contact
  Privacy Policy:   https://yourdomain.com/?page=privacy
  Terms:            https://yourdomain.com/?page=terms
  Admin Panel:      https://yourdomain.com/?page=admin

  Bond Category Pages (replace slug):
    Rs.100  →  https://yourdomain.com/?page=bond&type=prize-bond-100
    Rs.200  →  https://yourdomain.com/?page=bond&type=prize-bond-200
    Rs.750  →  https://yourdomain.com/?page=bond&type=prize-bond-750
    Rs.1500 →  https://yourdomain.com/?page=bond&type=prize-bond-1500
    Rs.3000 →  https://yourdomain.com/?page=bond&type=prize-bond-3000
    Rs.7500 →  https://yourdomain.com/?page=bond&type=prize-bond-7500
    Rs.15000→  https://yourdomain.com/?page=bond&type=prize-bond-15000
    Rs.25000→  https://yourdomain.com/?page=bond&type=prize-bond-25000
    Rs.40000→  https://yourdomain.com/?page=bond&type=prize-bond-40000

  Draw Result Pages:
    https://yourdomain.com/?page=draw&id=1
    https://yourdomain.com/?page=draw&id=2
    (ID is the auto-incremented draw ID from the database)

  XML Sitemap:      https://yourdomain.com/sitemap.php
  (Submit this to Google Search Console)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SEO SETUP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. UPDATE robots.txt before uploading:
   Change:  Sitemap: https://yourdomain.com/sitemap.php
   To:      Sitemap: https://YOURREALDOMAIN.com/sitemap.php

2. UPDATE sitemap.php:
   Change the DB_HOST/DB_NAME/DB_USER/DB_PASS constants at the
   top of sitemap.php to match your index.php config.

3. SUBMIT TO GOOGLE SEARCH CONSOLE:
   → Go to https://search.google.com/search-console
   → Add Property → enter your domain
   → Sitemaps → Submit: https://yourdomain.com/sitemap.php

4. GOOGLE ANALYTICS 4:
   Edit the CONFIG section in index.php, find where it says:
   define('ADMIN_PASS', '...');
   Add below it:
   define('GA4_ID', 'G-XXXXXXXXXX'); // Your GA4 Measurement ID
   Then in the <head> section, add your GA4 tracking snippet.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ENABLE HTTPS (SSL)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. In cPanel → SSL/TLS → Install a free Let's Encrypt certificate

2. Once installed, open .htaccess and uncomment these 2 lines:
   (remove the # at the start)
     # RewriteCond %{HTTPS} off
     # RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

3. This forces all traffic to HTTPS automatically.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DARK MODE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Dark mode is built-in. Click the moon 🌙 icon in the top-right
  navbar. The preference is saved in the visitor's browser
  (localStorage) and persists across visits.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CHANGING COLORS / BRANDING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  All colors are CSS variables. Find this section in index.php:

    :root {
      --pk:#0B8F3A;    ← Primary green
      --pk-d:#076B2B;  ← Dark green (hover)
      --pk-m:#1DA852;  ← Medium green (accents)
      --pk-l:#E8F5EE;  ← Light green (backgrounds)
      ...
    }

  Change --pk to any color to rebrand. For example:
    Blue:  --pk:#1E40AF   (good for a finance/banking theme)
    Red:   --pk:#DC2626   (not recommended — Pakistan flag green is best!)

  To change the site name and tagline:
    define('SITE_NAME',    'My Prize Bond Site');
    define('SITE_TAGLINE', 'Check Your Bond Results Here');

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DATABASE TABLES REFERENCE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  pb_bond_types   → 9 bond denominations (seeded automatically)
  pb_draws        → One row per draw event
  pb_winners      → One row per winning number (linked to draw)
  pb_schedules    → Upcoming draw schedule entries
  pb_subscribers  → Newsletter email subscribers
  pb_search_log   → Anonymised search analytics

  To view your data: cPanel → phpMyAdmin → select your database

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PROBLEM: "Database Connection Error" on first visit
  → Double-check DB_NAME, DB_USER, DB_PASS in CONFIG section
  → Remember: cPanel prefixes DB name/user with your account name
  → Example: cpaneluser_prizebond_db (NOT just prizebond_db)
  → Test connection in cPanel → phpMyAdmin first

PROBLEM: White/blank page
  → Enable PHP errors temporarily: change error_reporting(0) to
    error_reporting(E_ALL) at top of index.php
  → Check cPanel → Error Logs for PHP fatal errors
  → Ensure PHP 7.4+ is active (cPanel → MultiPHP Manager)

PROBLEM: .htaccess causing 500 error
  → Your host may not have mod_rewrite enabled
  → Try removing the RewriteEngine block (keep only security headers)
  → Or contact your host to enable mod_rewrite

PROBLEM: Admin panel not saving draws
  → Make sure you're using the correct admin password
  → Try logging out and back in (?page=admin&logout=1)
  → Check phpMyAdmin to confirm tables were created

PROBLEM: Search returns no results
  → First add draws via Admin → Add Draw
  → The search only works after winning numbers are saved to the DB
  → Make sure the winning numbers are saved without spaces/dashes
    (the system strips non-numeric characters automatically)

PROBLEM: CSV/Excel import not working
  → Ensure the file has correct column format (see CSV FORMAT above)
  → Try a plain .csv file first — it's most compatible
  → Excel files must be .xlsx (not old .xls format)

PROBLEM: Countdown timers show wrong time
  → The countdown uses the draw date at 10:30 AM PKT
  → Verify your server timezone is set correctly
  → cPanel → PHP settings → date.timezone = Asia/Karachi

PROBLEM: Dark mode not persisting
  → Visitor's browser must allow localStorage (most do by default)
  → Incognito/private windows may not persist the preference

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PHP VERSION REQUIREMENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Minimum:    PHP 7.4
  Recommended: PHP 8.1 or PHP 8.2
  MySQL:       5.7+ or MariaDB 10.3+

  To check/change PHP version in cPanel:
  Software → MultiPHP Manager → select your domain → PHP 8.1

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECURITY CHECKLIST (before going live)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  [ ] Change ADMIN_PASS from default to a strong password (16+ chars)
  [ ] Enable HTTPS and uncomment the HTTPS redirect in .htaccess
  [ ] Update robots.txt with your real domain URL
  [ ] Update sitemap.php DB credentials to match index.php
  [ ] Delete README.txt from your server after setup
  [ ] Do NOT upload database/schema.sql to public_html/
  [ ] Set PHP error display OFF (already done in .htaccess)
  [ ] Run cPanel Softaculous Security Scan or Imunify360

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WEBSITE FEATURES SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  PUBLIC SITE:
  ✓ Homepage with hero, latest draws, all 9 bond type cards
  ✓ AJAX instant search (no page reload)
  ✓ Bulk search: up to 500 numbers at once
  ✓ File search: Upload CSV or Excel, check all numbers at once
  ✓ Bond category pages (individual pages for each denomination)
  ✓ Complete draw result pages with all winning numbers
  ✓ Prize number display: 1st (gold), 2nd (silver), 3rd (green)
  ✓ Upcoming draw schedule with live countdown timers
  ✓ Animated statistics counters
  ✓ FAQ accordion
  ✓ Newsletter email subscription
  ✓ About Us, Contact Us, Privacy Policy, Terms & Conditions pages
  ✓ Dark Mode / Light Mode toggle (saved per-visitor)
  ✓ Fully responsive (mobile, tablet, desktop)
  ✓ Back to top button
  ✓ Search history saved in browser (last 10 searches)
  ✓ Share draw results via copy link
  ✓ Print draw results (print-optimised CSS)
  ✓ Download official PDF (links to SBP website)
  ✓ Schema.org breadcrumb in all page breadcrumbs
  ✓ XML sitemap (sitemap.php)
  ✓ robots.txt

  ADMIN PANEL (?page=admin):
  ✓ Secure session-based login (password protected)
  ✓ Dashboard with live statistics
  ✓ Add draw: bond type, draw#, date, city, PDF URL
  ✓ Enter winning numbers: 1st / 2nd / 3rd prize
  ✓ Import winning numbers from CSV or Excel file
  ✓ Edit existing draw results
  ✓ Delete draws (with confirmation)
  ✓ Paginated draws list
  ✓ Add upcoming draw schedule entries
  ✓ View all newsletter subscribers
  ✓ Export subscribers to CSV

  TECHNICAL:
  ✓ Single PHP file — no framework, no dependencies to install
  ✓ Pure PHP + MySQL (PDO) — works on any shared hosting
  ✓ Bootstrap 5 (CDN) — no local assets needed
  ✓ SQL injection protected (PDO prepared statements everywhere)
  ✓ XSS protected (htmlspecialchars on all output)
  ✓ CSRF token for admin form submissions
  ✓ Auto-creates database tables on first run
  ✓ Seeds all 9 prize bond types automatically
  ✓ GZIP compression via .htaccess
  ✓ Browser caching via .htaccess
  ✓ Blocks common attack patterns in .htaccess
  ✓ PHP sessions secured (httponly, SameSite) via .htaccess

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SUPPORT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Need help? Use the Contact page on your website once it's live,
  or refer to the Troubleshooting section above.

  Official SBP Prize Bond information:
  https://www.sbp.org.pk/bsc/prizebond.asp

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
