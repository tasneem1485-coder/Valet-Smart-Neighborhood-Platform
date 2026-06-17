# VALET PHP Backend — Setup Guide

## Requirements
- XAMPP (Apache + MySQL + PHP 8.0+)

---

## Step 1 — Copy the project to XAMPP

Copy the entire `valet-php` folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\valet\
```

Your folder structure should look like:
```
htdocs/
└── valet/
    ├── index.php
    ├── styles.css
    ├── script.js
    ├── config/
    │   ├── db.php
    │   └── helpers.php
    ├── api/
    │   ├── auth/
    │   │   ├── login.php
    │   │   ├── register.php
    │   │   ├── logout.php
    │   │   └── me.php
    │   ├── bookings/
    │   │   ├── create.php
    │   │   ├── list.php
    │   │   └── cancel.php
    │   └── contact/
    │       └── send.php
    ├── images/
    │   ├── hero-bg.jpg
    │   ├── services/
    │   └── ...
    └── database.sql
```

---

## Step 2 — Start XAMPP

1. Open the **XAMPP Control Panel**
2. Start **Apache**
3. Start **MySQL**

---

## Step 3 — Create the database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar
3. Name it `valet_db` and click **Create**
4. Click the `valet_db` database
5. Click the **"Import"** tab at the top
6. Click **"Choose File"** and select `valet-php/database.sql`
7. Click **"Go"** at the bottom

---

## Step 4 — Configure the database connection

Open `config/db.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // XAMPP default
define('DB_PASS', '');       // XAMPP default (empty password)
define('DB_NAME', 'valet_db');
```

If you set a password for MySQL root in XAMPP, update `DB_PASS`.

---

## Step 5 — Open the website

Go to: `http://localhost/valet/`

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `api/auth/register.php` | Create new user account |
| POST | `api/auth/login.php` | Sign in |
| POST | `api/auth/logout.php` | Sign out |
| GET  | `api/auth/me.php` | Get current user info |
| POST | `api/bookings/create.php` | Create a booking (guests allowed) |
| GET  | `api/bookings/list.php` | List user's bookings (login required) |
| POST | `api/bookings/cancel.php` | Cancel a booking (login required) |
| POST | `api/contact/send.php` | Send a contact message |

### Request format
All POST endpoints accept `application/x-www-form-urlencoded` or `multipart/form-data` (form fields).
They also accept `application/json` body.

### Response format
All endpoints return JSON:
```json
{ "success": true, "message": "...", ...extraData }
```

---

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | Registered users |
| `bookings` | Service booking requests |
| `contact_messages` | Contact form submissions |
| `password_resets` | Password reset tokens |
| `user_sessions` | Optional DB-stored sessions |
