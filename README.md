# Psychiatry Academy — Course Platform (PHP)

Courses archive is **public**; taking a course (lessons, progress, certificate) requires a trainee account.

## Roles
| Role | Can do |
|---|---|
| **Owner** (one or more people) | Everything a manager can, plus: add/remove **managers**, add owners, see/regenerate the private login links. Can also delete trainees. |
| **Manager** | Create/edit/delete courses & lessons, upload content, revoke certificates, edit/delete **trainees**, certificate & site settings. Cannot manage owners/managers or see the access links. |
| **Trainee** | View only: browse courses, enroll, study, mark lessons done, get the certificate, edit own username/email/password. |

* Owners and managers **do not use the public login**. They sign in only through secret links:
  * Owner link: `https://your-site/access.php?k=<secret>`
  * Manager link: `https://your-site/access.php?k=<other secret>`
  * They are shown once after install and always in **Admin → Settings → Access links** (owner only), where they can be copied or regenerated. Nothing on the public site links to them; wrong keys return a plain 404.
  * The owner link only accepts owner accounts; the manager link only manager accounts.
* The public `/login.php` and `/register.php` are for **trainees only**. The **GMC number** field exists only in the trainee sign-up form (7 digits) and is printed on their certificate.
* Lost a link? Pin a fixed key in `config.local.php` (`owner_login_key` / `manager_login_key`), see the example file.

## Run it locally
```
php -S localhost:8000
```
Open http://localhost:8000/install.php, create the owner account, **save the two private links**, then delete `install.php`.

## Deploy from GitHub to cPanel
> If `public_html` already runs WordPress, do **not** put this in the root (both have an `index.php`). Use a **sub-domain** (e.g. `academy.yoursite.com`, document root `public_html/academy`) or a sub-folder.

1. Push this repo to GitHub (keep it **private**).
2. cPanel → **Git™ Version Control → Create** → clone URL of your repo (for a private repo add the cPanel SSH key as a *Deploy key* in GitHub). Repository path e.g. `/home/CPANELUSER/repositories/academy`.
3. Edit `.cpanel.yml` (`CPANELUSER` and the target folder) and commit it.
4. On the server create `config.local.php` from `config.local.example.php` (MySQL credentials). Make `data/` and `uploads/` writable (755/775).
5. Git Version Control → **Manage → Deploy HEAD Commit**. Next updates: push, then Pull + Deploy.
6. Open `https://academy.yoursite.com/install.php` → create the owner → save the links → **delete `install.php`**.

`data/` (SQLite database), `uploads/` and `config.local.php` are git-ignored and never overwritten by deployments. Back them up (and the MySQL database if used).

## Requirements
PHP 8.0+ with PDO (`pdo_sqlite` or `pdo_mysql`), `fileinfo`, `mbstring`, `dom`. Apache (the `.htaccess` files protect `data/`, `includes/`, `uploads/`; if your host runs Apache 2.2 replace `Require all denied` with `Deny from all`).

## Features
Courses (classic editor, image, certificate validity) → lessons (Heading 2 builds the lesson table of contents) → trainee enrollment ("taken before?") → course player (lesson TOC + course TOC, green ✓ when done, mobile menu) → automatic certificate with serial + public verification link (`certificate.php?serial=PSY-YYYY-XXXXXXXX`) and "Download PDF" (browser Save as PDF, A4 landscape).

Colors: headings `#174585`, text `#121314`, icons `#4786ea`. Font: Inter.
