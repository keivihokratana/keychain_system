# 🔑 Photo Keychain Ordering System
### *KeyChain Studio — Full Setup Guide*

---

## 📁 Folder Structure

```
keychain_system/
├── index.php                  ← Home/Landing page
├── login.php                  ← Login & Register
├── logout.php                 ← Session logout
├── dashboard.php              ← Customer dashboard
├── database.sql               ← DB schema + seed data
│
├── assets/
│   ├── css/style.css          ← Global stylesheet (Gen Z aesthetic)
│   └── js/main.js             ← Vanilla JavaScript
│
├── includes/
│   ├── config.php             ← DB connection & constants
│   ├── functions.php          ← Auth, helpers, flash messages
│   ├── sidebar.php            ← Shared layout (opens <html>)
│   └── footer.php             ← Closes layout + loads JS
│
├── pages/                     ← Customer pages
│   ├── gallery.php            ← Browse designs
│   ├── customize.php          ← Customize & order
│   ├── my-orders.php          ← Order history + payment upload
│   ├── track.php              ← Order tracking by ref#
│   └── profile.php            ← Profile management
│
├── admin/                     ← Admin pages
│   ├── dashboard.php          ← Admin overview + revenue chart
│   ├── orders.php             ← Order management + status update
│   ├── designs.php            ← Design CRUD
│   └── customers.php          ← Customer management
│
└── uploads/                   ← Auto-created upload folders
    ├── photos/                ← Customer order photos
    ├── payments/              ← Payment proof screenshots
    └── designs/               ← Design preview images
```

---

## ⚙️ Installation Steps

### 1. Requirements
- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with mod_rewrite (or XAMPP/WAMP/MAMP)

### 2. Setup

**Step 1 — Copy files**
```
Place the `keychain_system/` folder inside your web root:
- XAMPP: C:/xampp/htdocs/keychain_system/
- MAMP:  /Applications/MAMP/htdocs/keychain_system/
- Linux: /var/www/html/keychain_system/
```

**Step 2 — Create the database**
```sql
-- In phpMyAdmin or MySQL CLI:
SOURCE /path/to/keychain_system/database.sql;
```

**Step 3 — Configure database**
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'keychain_db');
define('SITE_URL', 'http://localhost/keychain_system');
```

**Step 4 — Set upload folder permissions**
```bash
chmod 755 uploads/
chmod 755 uploads/photos/
chmod 755 uploads/payments/
chmod 755 uploads/designs/
```
On Windows (XAMPP): folders are writable by default.

**Step 5 — Open in browser**
```
http://localhost/keychain_system/
```

---

## 🔐 Default Login Credentials

| Role     | Email                  | Password  |
|----------|------------------------|-----------|
| Admin    | admin@keychain.com     | password  |
| Customer | Register a new account | —         |

> ⚠️ Change the admin password immediately after first login!

---

## 🌟 Features Summary

### Customer Features
- ✅ Register & login with session auth
- ✅ Browse keychain designs (gallery)
- ✅ Upload photo for customization
- ✅ Pick a design + add personalized text
- ✅ Quantity selector with live price calc
- ✅ Place orders with auto ref# generation
- ✅ Upload payment proof (GCash, bank transfer, etc.)
- ✅ Order history with status tracking
- ✅ Track order by reference number
- ✅ Profile management (name, phone, address, avatar, password)

### Admin Features
- ✅ Admin dashboard with revenue bar chart
- ✅ Order management — view, update status, add notes
- ✅ View customer photos and payment proofs
- ✅ Design CRUD — add/edit/delete/toggle active
- ✅ Customer management — view profiles and order history
- ✅ Order status history log
- ✅ Sales summary stats

---

## 💡 Design Notes
- **Color palette**: Terracotta, cream, blush, charcoal
- **Fonts**: Playfair Display (headings) + DM Sans (body)
- **Aesthetic**: Gen Z minimal — soft shadows, rounded corners, warm neutrals
- **Responsive**: Mobile-first, sidebar collapses on mobile with overlay
- **No frameworks**: Pure PHP, HTML, CSS, Vanilla JS, MySQL

---

## 📞 Support
This project is built for academic/capstone purposes.
Customize freely for your own use case!
