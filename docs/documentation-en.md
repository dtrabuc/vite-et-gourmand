# Vite & Gourmand — Technical documentation

Vite & Gourmand is a web application for ordering catering menus from a **fictitious** caterer in Bordeaux. It was built for the Studi ECF (French "Web and Mobile Web Developer" certification).

Live site: https://dylantrabuc.alwaysdata.net

## 1. Overview

| Role | What they can do |
|---|---|
| Visitor | Browse and filter menus, view menu details (gallery, dishes, allergens), contact form, quote request, sign up |
| Customer | Place orders (delivery, pickup or on site), track, edit or cancel pending orders, leave reviews, manage profile |
| Employee | Manage orders and statuses, menus, dishes, galleries, opening hours, reviews, customers and quotes |
| Administrator | Everything an employee can do, plus employee accounts, revenue and per-menu order statistics |

## 2. Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, custom MVC without a framework (router, dependency container, middlewares) |
| Relational database | MariaDB, accessed through PDO with native prepared statements |
| NoSQL database | MongoDB (`mongodb` extension + `mongodb/mongodb` library) |
| Frontend | Server-rendered PHP templates, Bootstrap 5.3, vanilla JavaScript (no build step, no npm) |
| Libraries | PHPMailer (SMTP), libphonenumber (phone validation), intl-tel-input (phone fields) |
| Containers | Docker Compose: PHP 8.2 + Apache, MariaDB 11.4, MongoDB 7, Mailpit |

## 3. Running locally with Docker

The only requirement is **Docker** with Compose v2.

```bash
git clone -b dev https://github.com/dtrabuc/vite-et-gourmand.git
cd vite-et-gourmand
docker compose up -d --build
```

On first start, MariaDB imports `database/schema.sql` and MongoDB runs `database/mongodb-init.js`. The app container waits for the database, then sets the demo account passwords once.

| Service | URL |
|---|---|
| Website | http://localhost:8080 |
| Mailpit (test mailbox, catches every e-mail sent by the app) | http://localhost:8025 |

Demo accounts (Docker only):

| Role | Login page | E-mail | Password |
|---|---|---|---|
| Customer | `/login` | `user@viteetgourmand.com` | `Client!Docker2026` |
| Employee | `/admin/login` | `employee@viteetgourmand.com` | `Employe!Docker2026` |
| Admin | `/admin/login` | `admin@viteetgourmand.com` | `Admin!Docker2026` |

Useful commands:

```bash
docker compose ps                 # service status
docker compose logs app           # application logs
docker compose down -v            # remove everything, including databases
VG_HTTP_PORT=8090 VG_MAIL_PORT=8026 docker compose up -d --build   # other ports
```

Inside Docker, the app reads its configuration from the `environment` section of `docker-compose.yml`. The project `.env` file is never copied into the image.

Delivery addresses are checked with OpenStreetMap (Photon for search, OSRM for road distance). No API key is needed, only Internet access; if the services cannot be reached, customers can confirm their address manually.

## 4. Running without Docker

Requirements: PHP 8.2+ with `pdo_mysql`, `mongodb` (1.21+), `mbstring` and `openssl`; Composer 2; MariaDB; MongoDB with `mongosh`.

```bash
composer install
cp .env.example .env
mysql -u root -p -e "CREATE DATABASE viteetgourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p viteetgourmand < database/schema.sql
mongosh "mongodb://127.0.0.1:27017/viteetgourmand" --file database/mongodb-init.js
php scripts/set-password.php --sync
php -S localhost:8000 -t public
```

All variables are listed in `.env.example`. Main groups:

- `APP_*`: environment, debug mode, public URL, time zone.
- `DB_*`: MariaDB connection.
- `MONGO_*`: MongoDB connection (full URI or separate fields).
- `MAIL_*`: SMTP server. If `MAIL_HOST` is empty, no e-mail is sent.
- `DEMO_*`: demo account credentials, applied by `scripts/set-password.php --sync`.

## 5. Architecture

```text
public/index.php  →  Application  →  Router  →  Middlewares  →  Controller  →  Service  →  Repository  →  MariaDB / MongoDB
                                                                     ↓
                                                                   View (PHP template)
```

| Folder | Role |
|---|---|
| `App/Core` | Bootstrapping, `.env` loading, router, dependency container, session, database connections, views, uploads |
| `App/Middleware` | `Security` (headers, CSRF, rate limiting), `Auth`, `Guest`, `Staff`, `Admin` |
| `App/Controller` | Reads the request, calls services, renders views or redirects |
| `App/Service` | Business rules: authentication, orders and pricing, delivery distance, menus, e-mails, password policy, rate limiting |
| `App/Repository` | All SQL and MongoDB queries |
| `App/Entity` | `User`, `Menu`, `Order` |
| `App/View` | PHP templates; every output is escaped with `$escape()` |
| `config/routes.php` | Route table: method, path, `Controller@action`, middlewares |

Each route declares its middlewares, for example:

```php
['POST', '/register', 'AuthController@register', ['Security', 'Guest']],
['GET', '/admin/orders', 'AdminController@orders', ['Security', 'Staff']],
```

## 6. Data

### MariaDB

`database/schema.sql` is the **single SQL file**. It contains the full structure and the demo data (3 accounts, opening hours, 10 allergens, 46 dishes (aperitifs, starters, mains, sides, desserts, digestifs), 10 menus, 5 completed orders). There are no separate migration files.

- The file has no `CREATE DATABASE` or `USE` statement, so it can be imported into any selected database: locally, in Docker or on the hosting server.
- **It drops and recreates every table.** Never import it into a database you want to keep.

Main tables: `users`, `menus`, `dishes`, `allergens` and their link tables, `orders` and `order_status_history`, `opening_hours`, `quote_requests`, `user_notifications`.

### MongoDB

`database/mongodb-init.js` drops and recreates three collections:

- `menu_images`: the image gallery of each menu;
- `comments`: customer reviews and their moderation status;
- `menu_statistics`: orders and revenue per menu, shown in the admin dashboard chart.

**Why two databases?** Linked data that needs integrity (foreign keys, transactions) lives in MariaDB. For example, creating an order, decreasing the stock and writing the first history line happen in one transaction. Independent documents that are read as a whole live in MongoDB.

## 7. Sign-up flow

1. The visitor fills in the form and either types a password or ticks **"Suggest a random secure password"**. The browser then generates a 16-character password with `crypto.getRandomValues`. It can be regenerated or copied.
2. A **Show / Hide** button is added to every password field on the site (`public/assets/js/password.js`).
3. The server validates every field again, including the password policy: at least 10 characters, with uppercase, lowercase, a digit and a special character. It then hashes the password with bcrypt and creates an active account.
4. A welcome e-mail without the password is sent. If sending fails, the account is kept anyway.
5. The customer signs in at `/login`.

## 8. Menus and pricing

The catalogue works like a food delivery app: the visitor picks the number of guests at the top of `/menus`, and every card shows the price per person, what each guest gets and the total for that number of guests.

- `menus.base_price` is the price for the menu's minimum number of guests (`min_people`). The site shows the **price per person** (`base_price / min_people`).
- Total = price per person × guests, with **10 % off** from `min_people + 5` guests.
- Each guest gets 1 aperitif (with or without alcohol), 1 starter, 1 main, every side dish, 1 dessert and 1 digestif (none in the kids' menu). When a menu offers two options in a category, each guest picks one (`menu_composition()` in `App/Core/functions.php`).
- Delivery: free in Bordeaux; up to 25 km (straight line) 5 € + 0.59 € per road km. Further away, the customer chooses pickup (free) or explicitly accepts out-of-zone delivery at the same rate. Pickup and on-site meals are free. Payment in cash.

All amounts come from `App/Service/MenuPricing.php`, used by the catalogue API (`/menus/filter?people=N`), the menu page (`/menus/{id}/quote?people=N`), the order form and the e-mails. The server recalculates the total when the order is saved.

### Ordering, notifications and delivery addresses

- `/orders/new` starts with the number of guests (stepper and quick picks). Every menu possible for that number is listed with its total; menus needing more guests are folded away, never blocking.
- The delivery address is suggested and checked with OpenStreetMap (`GeoService`, `/api/address/autocomplete`, `/api/address/check`). The street name must match, otherwise the address is only "approximate". Orders store `address_status` (`verified`, `manual`) and `delivery_zone` (`bordeaux`, `zone`, `outside`).
- Outside the 25 km zone the customer picks pickup or ticks "deliver anyway" with the computed fee. If the address cannot be verified, they can confirm it manually; when no distance can be computed, staff confirm the delivery fee by phone (`delivery_cost_override` in the staff edit form).
- After ordering, a confirmation page shows a progress tracker. Each status change creates an in-app notification (bell with unread count in the navbar, toasts polled every 30 s from `/notifications/feed`) and sends an e-mail.

## 9. Security

- **SQL injection**: native prepared statements everywhere.
- **XSS**: every output escaped; Content Security Policy without inline scripts.
- **CSRF**: token checked on every POST request.
- **Passwords**: bcrypt with a configurable cost, a single strength policy, account lock after 5 failures and per-IP rate limiting.
- **Password reset**: single-use link, valid for 1 hour, stored as a SHA-256 hash.
- **Sessions**: `HttpOnly` and `SameSite=Lax` cookies, `Secure` over HTTPS, ID regenerated at login, 30-minute idle timeout.
- **Access control**: customers only see their own orders; staff routes are protected by the `Staff` and `Admin` middlewares.
- **Uploads**: file type checked from the content (JPEG, PNG, WebP), 5 MB maximum, random file name.
- **Outbound calls** to OpenStreetMap services (Photon, OSRM) do not follow redirects, send an identifying User-Agent and are cached. Address search and checks require a login and are rate limited.
- **Secrets** live only in `.env`, which is ignored by Git.
- **Verified** with an automated probe (134 checks): every protected route refuses anonymous users, customers cannot reach staff pages, employees cannot reach admin pages, a customer cannot read, edit, cancel or review another customer's order, POST requests without a CSRF token get a 403. Sensitive files (`.env`, SQL, sources, `.git`) are not reachable from the web.

### GDPR

- Privacy policy (`/confidentialite`) and terms of sale (`/cgv`) list the data, purposes, legal bases, retention periods and recipients (OpenStreetMap services are in the EU).
- Allergy information is health data: explicit consent is required (checkbox, checked by the server) whenever a customer adds kitchen notes or an allergen-free variant.
- From the profile, customers can download all their data (JSON) and delete their account: personal data is erased; past orders are kept without identity, address, phone or allergy data for accounting; reviews are removed.

## 10. Deployment (Alwaysdata)

1. Back up the database and the files that will be replaced.
2. Upload the changed files over FTPS. Never overwrite `.env`, `storage/` or `public/uploads/`.
3. Update the live database structure with targeted `ALTER TABLE` statements, tested on a copy first.
4. Check the main pages, then test a real sign-up with a disposable mailbox.

## 11. Design documents

`docs/conception/` contains a UML class diagram (entities with attributes **and methods**, MongoDB documents, application services), the Merise data model (MCD), the use-case diagram and two sequence diagrams (sign-up, ordering), in `.drawio`, `.png` and `.svg`. They are generated from `docs/conception/outils/diagrams.py`.
