# Club Link (CampusConnect) – NUST Student Clubs Platform
WAD621S Group Assignment 2026 – Dynamic Student Services Web Application

Club Link solves a real NUST problem: club information is scattered across posters and WhatsApp groups.
Students can discover clubs, join/leave them, RSVP to events and manage their account. Administrators manage
clubs, events, users and contact messages.

## 1. Quick start (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\clublink`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `database/clublink_db.sql` → **Go**
   (this creates the `clublink_db` database, all tables and sample data; it replaces any older copy).
4. Browse to `http://localhost/clublink/`.

Demo accounts: **Admin** `admin@nust.na` / `Admin@123` · **Student** `student@nust.na` / `Student@123`

Database settings are in `includes/db.php` (default: user `root`, empty password, port 3306).
Android (KSWEB/Termux): same steps, use `http://localhost:8080/clublink/`.

## 2. Requirements checklist
| Requirement | Where |
|---|---|
| Home page | `index.php` |
| About / information | `about.php` |
| Registration | `register.php` |
| Login | `login.php`, `logout.php` |
| User dashboard | `dashboard.php` (my clubs, RSVPs, edit profile, change password) |
| Data listing | `clubs.php`, `events.php` |
| Details page | `club-details.php` |
| Search / filtering | `clubs.php` (search, category, sort, pagination), `events.php` (club, upcoming/past) |
| Data-entry forms | register, contact, join/RSVP, admin club & event forms |
| Contact page | `contact.php` |
| Admin section | `admin/` – overview, clubs, events, users, messages (full create / read / update / delete) |
| Relational database | MySQL, 6 tables with foreign keys (`database/clublink_db.sql`) |
| No Bootstrap | Hand-written `assets/css/style.css` |

## 3. Project structure
```
index.php about.php clubs.php club-details.php events.php
register.php login.php logout.php dashboard.php contact.php
admin/            index, clubs, club-form, events, event-form, users, messages
includes/         init.php db.php functions.php header.php footer.php
assets/           css/style.css  js/script.js  images/
database/         clublink_db.sql   (CREATE TABLE statements + sample data)
docs/             Project_Report.docx, ERD.pdf
```

## 4. Database design (for the report)
* `users` (user_id PK, full_name, email UNIQUE, password hash, role ENUM student/admin, created_at)
* `clubs` (club_id PK, club_name UNIQUE, category ENUM, description, meeting_time, location, image)
* `memberships` (user_id FK, club_id FK, UNIQUE pair) – **many-to-many** users ↔ clubs
* `events` (event_id PK, club_id FK, title, description, event_date, location) – one club has many events
* `event_rsvps` (event_id FK, user_id FK, UNIQUE pair) – **many-to-many** users ↔ events
* `messages` (contact form submissions, is_read flag)

Foreign keys use `ON DELETE CASCADE`, so deleting a club removes its memberships and events automatically.
Update `docs/ERD.pdf` to show `events` and `event_rsvps` if your ERD predates them.

## 5. Client-side vs server-side (for the report)
**Client-side:** semantic HTML5 (`header`, `nav`, `main`, `section`, `article`, `aside`, `footer`, `time`, tables with
`scope`/`caption`, labelled forms, `details`); external CSS using variables, Flexbox, CSS Grid, the box model and
media queries at 992 px / 768 px / 480 px (tables turn into cards on phones); JavaScript for the mobile menu, dark mode
(localStorage), live form validation, password-strength meter, show/hide password, character counter, confirm dialogs,
image preview, animated counters and back-to-top.

**Server-side (PHP 8 + mysqli):** form processing, authentication with sessions, role-based authorisation, data retrieval
(joins, counts, pagination), insertion (register, join, RSVP, contact, admin add), **updating** (profile, password,
admin edit club/event, change role, mark message read) and deletion (leave club, cancel RSVP, admin deletes).

## 6. Security implementation (for the report)
| Threat | Defence |
|---|---|
| SQL injection | Every query with user input uses **prepared statements** (`q()/rows()/row()/val()` in `includes/functions.php`); sort columns come from a whitelist |
| XSS | All output escaped with `e()` (`htmlspecialchars`, ENT_QUOTES, UTF-8) |
| CSRF | Per-session token in every POST form, verified with `hash_equals` |
| Password theft | `password_hash()` (bcrypt) / `password_verify()`; strong-password rule; passwords never shown |
| Session attacks | `session_regenerate_id()` on login and password change; HttpOnly + SameSite cookies; full logout |
| Brute force / enumeration | Account locked for 60 s after 5 failed logins; one generic error message |
| Broken access control | `require_login()` and `require_admin()` guards on every protected page; admins cannot delete or demote themselves |
| Unsafe deletes | Deletes are POST + CSRF + confirmation dialog (no GET delete links) |
| Malicious uploads | Size limit 2 MB, real MIME check (`finfo`), `getimagesize`, random file name, allowed types only |
| Spam | Hidden honeypot field on the contact form |
| Other | Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`), errors logged not displayed |

## 7. Challenges and solutions (examples for the report)
* *SQL injection in the first version* – string-built queries were replaced with prepared statements.
* *Responsive tables* – wide admin tables were converted to stacked cards on phones using `data-label` + media queries.
* *Safe image uploads* – replaced `basename()` upload with validated, randomly named files.
* *Shared layout* – header/footer/init includes remove duplicated code across 20+ pages.

## 8. Before you submit
* Write the team members' names in a text file (the brief requires it) and zip with the project.
* Everyone must write some code and be ready to demonstrate – read through the files you will explain.
* Add screenshots, wireframes and the updated ERD to the report.
