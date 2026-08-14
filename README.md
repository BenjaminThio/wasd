# WASD

An open marketplace and digital distribution platform for indie games and
applications. Developers publish their work, players browse a store, keep a
wishlist and a cart, buy, review, download builds, and — where the developer has
uploaded an HTML5 build — play a game straight in the browser without installing
anything.

Built for **UECS2094 / UECS2194 / EECS2194 Web Application Development**.

Written entirely in **HTML, CSS, JavaScript, PHP and MySQL**. No frameworks, no
UI kits, no CSS or JavaScript libraries, no CDN — every line of the stylesheet,
every DOM interaction, every SQL query and every icon in the project is part of
this repository.

---

## Requirements

| Component | Version used | Notes |
|---|---|---|
| PHP | 8.3 or newer | Requires the `pdo_mysql`, `mbstring` and `zip` extensions. All three are on by default in WampServer and XAMPP |
| MySQL / MariaDB | 5.7+ / 10.4+ | |
| Apache | 2.4 | `mod_rewrite` required; `mod_headers`, `mod_deflate` and `mod_expires` are used when present |

The simplest way to get all of these at once on Windows is **WampServer**; on
macOS or Linux, XAMPP or MAMP works the same way. The project was developed on
WampServer 3.3 with PHP 8.3 and MySQL 8.0.

`mod_rewrite` is not optional. Every page request is routed through
`src/index.php` by the rules in `.htaccess`, so without it you will get a 404 on
everything but the home page.

---

## Installation

### 1. Put the project where Apache can see it

Copy the project folder into your web root so that it sits at:

```
<web root>/wasd
```

On WampServer that is `C:\wamp64\www\wasd`; on XAMPP it is `htdocs/wasd`.

The application works out of any folder name — `BASE_URL` is derived from the
directory at runtime in `src/config.php` — but the instructions below assume
`wasd`.

### 2. Create the database

Create an empty database and run the supplied SQL file against it. The file
drops any existing tables first, then creates all thirteen tables and populates
them with sample data, so it is safe to re-run whenever you want a clean slate.

From the command line:

```bash
mysql -u root -p -e "CREATE DATABASE wasd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
mysql -u root -p wasd < database/database.sql
```

Or, using **phpMyAdmin**: create a database named `wasd`, open the **Import**
tab, choose `database/database.sql` and press **Go**.

### 3. Configure the connection

Create a file named `.env` in the project root (the same folder as this README).
It is not in the repository, because it holds the database password.

```ini
DB_HOST=localhost
DB_NAME=wasd
DB_USER=root
DB_PASSWORD=your_mysql_password
APP_DEBUG=false
PLAY_ORIGIN=
```

| Key | Meaning |
|---|---|
| `DB_HOST` | MySQL host, normally `localhost` |
| `DB_NAME` | The database created in step 2 |
| `DB_USER` | MySQL user |
| `DB_PASSWORD` | That user's password. On a default WampServer install `root` has an empty password — leave the value blank |
| `APP_DEBUG` | `false` for normal use. `true` prints PHP errors into the page, which is useful while developing and unsafe anywhere else |
| `PLAY_ORIGIN` | Optional. See *Browser-playable builds* below. Leave empty and everything except cross-origin isolation still works |

`.env` is blocked from being served by `.htaccess` and ignored by git. It should
stay that way — anything readable over HTTP is readable by anyone.

### 4. Make the upload folder writable

Cover art, screenshots and uploaded builds are written to `public/uploads`.
Create it if it does not exist:

```bash
mkdir -p public/uploads/games public/uploads/avatars
```

On Windows nothing further is needed. On macOS or Linux, give the web server
user write access to it.

### 5. Start Apache and MySQL, then open the site

```
http://localhost/wasd
```

---

## Signing in

The sample data ships with four accounts. They all share the same demo password:

| Email | Password |
|---|---|
| `gamerguy@example.com` | `Player!23` |
| `noob@example.com` | `Player!23` |
| `sniper@example.com` | `Player!23` |
| `casual@example.com` | `Player!23` |

`gamerguy@example.com` is the most useful one to start with. It already owns
published games, so the developer dashboard and project editor have something in
them, and it is the only seeded account with the `is_admin` flag set, so it is
the one that can open the contact inbox at `/inbox`.

Messages sent through the form on the contact page are stored in the
`contact_message` table and read at **`/inbox`**. Nothing is emailed anywhere, so
the form works without a mail server configured. Any other account gets a 403
there; to grant a second one access:

```bash
mysql -u root -p wasd -e "UPDATE user SET is_admin = 1 WHERE email = 'noob@example.com';"
```

You can also register a new account from **Sign Up**. Passwords must be at least
six characters and contain an uppercase letter, a lowercase letter, a number and
a symbol, and are stored as bcrypt hashes — the database never holds a password
in a readable form.

---

## Browser-playable builds (optional)

A developer can mark an uploaded HTML5 build as playable, and it then runs
inside the game page. Builds exported from Godot or Unity with threading enabled
need `SharedArrayBuffer`, which browsers only grant to a
**cross-origin isolated** page — and a page cannot be isolated from an iframe on
its own origin.

If you want those builds to run, point `PLAY_ORIGIN` at a second hostname served
by the same Apache instance:

```ini
PLAY_ORIGIN=http://127.0.0.1
```

Use only a scheme and host — no path. `http://127.0.0.1` alongside
`http://localhost` is enough, because the browser treats them as different
origins.

Leave `PLAY_ORIGIN` empty and the player still works for single-threaded builds,
which covers most HTML5 exports.

---

## Project layout

```
wasd/
├── .htaccess              Routing, security headers, compression, caching
├── .env                   Database credentials (you create this; never committed)
├── database/
│   └── database.sql       Schema + sample data. Drops and rebuilds everything
├── public/
│   ├── assets/            Images used by the static pages
│   ├── fonts/             Self-hosted webfonts
│   ├── js/                Shared client-side scripts, including the SPA router
│   └── uploads/           Runtime uploads: cover art, screenshots, builds
└── src/
    ├── index.php          Front controller. Resolves a URL to a page folder
    ├── config.php         BASE_URL and error-reporting policy
    ├── app/               One folder per page: index.php + optional style.css
    │   ├── (auth)/        Sign in and sign up
    │   ├── api/           JSON endpoints (cart, wishlist, reviews, contact, …)
    │   ├── store/  game/  cart/  wishlist/  checkout/  library/
    │   ├── profile/  dashboard/  project/
    │   └── contact/       Contact hub, help centre, partners, press
    ├── lib/               Database, Auth, Csrf, Api, Env, Media, Uploads
    └── models/            Users, Games, Game, Review, Library, ContactMessages…
```

Routing follows the folder structure: `/wasd/store` loads `src/app/store/index.php`,
and a folder in brackets such as `(auth)` groups pages without appearing in the
URL. If a page folder contains a `style.css`, the front controller links it
automatically.

---

## How the pieces fit together

- **Front controller.** `.htaccess` sends every request that is not a real file
  to `src/index.php`, which maps the path to a folder under `src/app` and
  renders it inside the shared layout. The resolved path is checked against the
  app directory with `realpath()` before anything is included, so a crafted URL
  cannot walk out of it.

- **Soft navigation.** `public/js` contains a small router that intercepts
  internal links, fetches the next page with an `X-SPA-Request` header and swaps
  the contents of `#app-root`, keeping the header, the footer and scroll
  position intact. Every URL still works as a normal full page load — the router
  is an enhancement, not a requirement.

- **Database access.** Everything goes through `src/lib/Database.php`, which
  holds one PDO connection per request and prepares every statement with
  explicitly typed bound parameters. There is no string concatenation into SQL
  anywhere in the project.

- **Authentication.** `src/lib/Auth.php` owns the session. Passwords are hashed
  with `password_hash()` and checked with `password_verify()`; the session ID is
  regenerated on login to close off session fixation; the session cookie is
  `HttpOnly` and `SameSite=Strict`.

- **Write protection.** Every state-changing endpoint requires the CSRF token
  from `src/lib/Csrf.php`, compared with `hash_equals()`. Ownership is checked
  on the server for every write — the buttons in the interface are a
  convenience, never the gate.

---

## Deploying somewhere real

Two things must change before this is exposed to the internet:

1. In `src/lib/Auth.php`, set the session cookie's `secure` flag to `true`. It
   is `false` so that the project runs over plain HTTP on `localhost`; over
   HTTPS it should be on.
2. In `.env`, keep `APP_DEBUG=false` and use a MySQL account restricted to this
   one database rather than `root`.

`.htaccess` already denies `.env`, `.sql`, `.git`, the `database/` folder and
other files that should never be served, and sets the security headers
(`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
`Permissions-Policy` and a `Content-Security-Policy`). Those rules only take
effect if Apache is configured
with `AllowOverride All` for the web root — which is the WampServer default.

---

## Troubleshooting

**Every page except the home page 404s.**
`mod_rewrite` is off, or `AllowOverride` is set to `None` so `.htaccess` is
ignored. On WampServer, enable *Apache → Apache modules → rewrite_module* and
restart.

**"The server is not configured correctly."**
The application could not read `.env`. Check that the file exists in the project
root, is named exactly `.env`, and contains all four `DB_` keys. The specific
reason is written to the PHP error log rather than to the page, on purpose.

**A blank page or a connection error on every page.**
MySQL is not running, or the credentials in `.env` are wrong. Confirm with
`mysql -u root -p` and check that `DB_NAME` matches the database you imported
into.

**Uploads fail or cover art does not appear.**
`public/uploads` does not exist or is not writable, or the file is larger than
PHP allows. Raise `upload_max_filesize` and `post_max_size` in `php.ini` — game
builds are routinely tens of megabytes.

**An HTML5 build shows "Failed to fetch" or a `SharedArrayBuffer` error.**
That build needs cross-origin isolation. Set `PLAY_ORIGIN` as described above.
