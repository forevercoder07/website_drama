# 🎬 DramaMini Clone - Complete System

Koreyska, Xitoy va Turk dramalari uchun **FULL-STACK** web aplikatsiyasi

## 📋 Loyihaning Asosiy Xususiyatlari

### ✅ Public Section (Ochiq)
- ✓ Kinolar ro'yxati (Movies listing)
- ✓ Kategoriyalar (Categories)
- ✓ Qidirish (Search)
- ✓ Video Player
- ✓ Responsive Design (Mobile + Desktop)

### 🔐 Private Section (Yopiq)
- ✓ Login/Parol (Vaqtga berilgan parol)
- ✓ Device Fingerprinting (Bitta qurilmadan kirish)
- ✓ Device Approval (Admin tasdiq)
- ✓ Watchlist (Sevimli kinolar)
- ✓ Watch History (Ko'rish tarixи)

### 👨‍💼 Admin Panel
- ✓ User Management (Foydalanuvchilar boshqarish)
- ✓ Temporary Password System (Vaqtga berilgan parol)
- ✓ Device Approval (Qurilma ruxsat)
- ✓ Movie Management (Public + Private)
- ✓ User Monitoring (Kuzatish)
- ✓ Login History (Kirish tarixи)
- ✓ Admin Logs (Barcha amallar)
- ✓ Statistics Dashboard

### 🛡️ Security Features
- ✓ SQL Injection Protection (Prepared Statements)
- ✓ XSS Protection (HTML Escaping)
- ✓ CSRF Tokens
- ✓ Rate Limiting (Brute Force)
- ✓ Password Hashing (bcrypt)
- ✓ Session Management
- ✓ Device Fingerprinting
- ✓ HTTPS Ready

---

## 🚀 INSTALLATION

### 1️⃣ Database Setup

```bash
# MySQL / MariaDB
mysql -u root -p < database/schema.sql

# Yoki SQL adminʻga
# database/schema.sql faylni SQL editor orqali bajarish
```

### 2️⃣ Configuration

`config/config.php` fayli tahrir qiling:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Parol
define('DB_NAME', 'dramamini');

define('JWT_SECRET', 'your-secret-key-change-this'); // Davlat qiling!
```

### 3️⃣ Server Setup

**Apache dengan `.htaccess`:**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^api/(.*)$ api/index.php?$1 [QSA,L]
    RewriteRule ^admin/(.*)$ admin/index.php?$1 [QSA,L]
    RewriteRule ^private/(.*)$ private/index.php?$1 [QSA,L]
</IfModule>
```

**Nginx:**

```nginx
location /api {
    rewrite ^/api/(.*)$ /api/index.php?$1 last;
}

location /admin {
    rewrite ^/admin/(.*)$ /admin/index.php?$1 last;
}
```

### 4️⃣ Permissions

```bash
chmod 755 /var/www/drama-mini
chmod 644 /var/www/drama-mini/*.php
mkdir -p /var/www/drama-mini/logs
chmod 777 /var/www/drama-mini/logs
```

---

## 🔑 DEFAULT LOGIN CREDENTIALS

### Admin Panel
- **URL:** `http://localhost/admin/login.php`
- **Username:** `admin`
- **Password:** `admin123`

### User Login (Yopiq Bo'lim)
1. Admin panel'da yangi user yaratish
2. Admin tomonidan vaqtga berilgan parol belgilash
3. Foydalanuvchi shu parol bilan login qiladi

---

## 📂 FILE STRUCTURE

```
drama-mini/
├── config/
│   └── config.php                 # Konfiguratsiya
├── includes/
│   ├── Database.php               # Database Class
│   ├── Auth.php                   # Authentication
│   ├── Security.php               # Security Utilities
│   ├── Admin.php                  # Admin Operations
│   └── Movie.php                  # Movie Management
├── database/
│   └── schema.sql                 # Database Schema
├── api/
│   └── index.php                  # API Endpoints
├── admin/
│   ├── login.php                  # Admin Login
│   └── index.php                  # Admin Panel
├── private/
│   └── login.php                  # User Login (Private)
├── public/
│   └── index.php                  # Public Website
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── logs/                          # Log Files
```

---

## 🔄 WORKFLOW

### 1️⃣ Public User
```
1. Bosh sahifaga kirish
2. Kinolar ko'rish (Public movies)
3. Qidirish
4. Login qilish (Yopiq bo'limga)
```

### 2️⃣ Private User
```
1. Login qilish (Admin tomonidan berilgan parol)
2. Device approval kutish (Birinchi marta)
3. Yopiq kinolar ko'rish
4. Watchlist
5. Watch history
```

### 3️⃣ Admin Workflow
```
1. Admin panel login
2. Yangi user yaratish
3. Vaqtga berilgan parol belgilash
4. Qurilma ruxsatlarini boshqarish
5. Kinolar qo'shish/o'chirish
6. User monitoring
7. Login history ko'rish
8. Admin loglarini tekshirish
```

---

## 🔐 VAQTGA BERILGAN PAROL TIZIMI

### Admin Tomonidan Parol Belgilash
```
Admin Panel → Parollar → Foydalanuvchi Tanlang → Parol Yaratish
```

### Parol Xususiyatlari
- **Avtomatik Generatsiya:** Secure random password
- **Custom Password:** Admin o'zini parol yozishi mumkin
- **Muddat:** 30 kun (admin belgilaydi)
- **Status:** Faol/Bekor/Tugagan/Ishlatilgan
- **Revoke:** Vaqt tugamasdan bekor qilish mumkin

### Vaqt Tugaganda
```sql
-- Avtomatik tekshirish (CRON)
SELECT * FROM temp_passwords 
WHERE expires_at < NOW() 
AND is_revoked = FALSE 
AND is_used = FALSE;
```

---

## 👥 USER MONITORING (KUZATISH)

### Admin Ko'ra Oladi
1. **Login History**
   - Qachon kirdi
   - Qaysi qurilmadan
   - IP address
   - Browser
   - Kirildi/Kirilmadi

2. **Device Management**
   - Qancha qurilmada
   - Qurilma nomi
   - Browser + OS
   - IP address
   - Oxirgi kirish vaqti
   - Tasdiq statusi

3. **Temporary Passwords**
   - Nechta parol berildi
   - Qachon tugaydi
   - Ishlatildi/Ishlatilmadi
   - Revoked (Bekor qilingan)

4. **Statistics**
   - Jami foydalanuvchilar
   - Public/Private kinolar
   - Faol parollar
   - Tasdiq kutayotgan qurilmalar

---

## 🛡️ SECURITY GUIDE

### SQL Injection Protection
```php
// ❌ Xavfsiz emas
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ Xavfsiz
$db->query("SELECT * FROM users WHERE id = ?");
$db->bind('i', $id);
```

### XSS Protection
```php
// ❌ Xavfsiz emas
echo $_GET['name'];

// ✅ Xavfsiz
echo Security::escape($_GET['name']);
```

### CSRF Protection
```php
// Token yaratish
$token = Security::generateCSRFToken();

// Form'da
<input type="hidden" name="csrf_token" value="<?php echo $token; ?>">

// Tekshirish
if (!Security::validateCSRFToken($_POST['csrf_token'])) {
    die('CSRF Attack!');
}
```

### Rate Limiting
```php
if (!Security::checkRateLimit($_SERVER['REMOTE_ADDR'], 'login')) {
    die('Juda ko\'p urinish. Keyinroq harakat qiling');
}
```

---

## 📡 API ENDPOINTS

### Public API
```
GET  /api/movies                  # Kinolar ro'yxati
GET  /api/movie?id=1             # Bitta kino
GET  /api/categories             # Kategoriyalar
GET  /api/search?q=query         # Qidirish
POST /api/login                  # Login
POST /api/logout                 # Logout
```

### Protected API (Login kerak)
```
GET  /api/profile                # Foydalanuvchi profili
GET  /api/private-movies         # Yopiq kinolar
GET  /api/watchlist              # Sevimli kinolar
POST /api/watchlist-add          # Watchlist'ga qo'shish
POST /api/watch-history          # Ko'rish tarixiga saqlash
```

---

## 🔧 TROUBLESHOOTING

### "Database Connection Error"
```
- Database running ekanini tekshirish
- config.php da DB_HOST, DB_USER, DB_PASS
- MySQL permissions
```

### "Admin Panel ga kirish olmayapman"
```
- admin user mavjudligini tekshirish:
  SELECT * FROM users WHERE username = 'admin' AND is_admin = TRUE;

- Parol: admin123
- /admin/login.php address
```

### "Vaqtga berilgan parol ishlamayapti"
```
- Password hash tekshirish
- Expiry time DATABASE'da
- is_revoked, is_used status
```

### "Device Approval ishlamayapti"
```
- device_fingerprints table
- is_approved = FALSE status
- Admin panel'dan tasdiq berish
```

---

## 📊 DATABASE TABLES

| Table | Description |
|-------|-------------|
| users | Foydalanuvchilar |
| temp_passwords | Vaqtga berilgan parollar |
| device_fingerprints | Qurilmalar |
| sessions | Sessiyalar |
| login_history | Kirish tarixи |
| movies | Public kinolar |
| episodes | Public epizodlar |
| private_movies | Yopiq kinolar |
| private_episodes | Yopiq epizodlar |
| categories | Kategoriyalar |
| watchlist | Sevimli kinolar |
| watch_history | Ko'rish tarixи |
| admin_logs | Admin harakatlari |
| rate_limit | Rate limiting |

---

## 🚀 DEPLOYMENT

### Production Checklist
- [ ] `config.php` da JWT_SECRET o'zgartirildi
- [ ] HTTPS enabled
- [ ] Database backup
- [ ] Logs directory permissions
- [ ] Error reporting disabled (display_errors = 0)
- [ ] XSS/CSRF protection tekshirildi
- [ ] Rate limiting enabled
- [ ] Admin parol davlat qilingan
- [ ] Database backups automated

---

## 📞 SUPPORT

Xatolar yoki savollari bo'lsa contact qiling:
- Email: admin@dramamini.net
- Telegram: @dramamini_net

---

**Version:** 1.0  
**Last Updated:** 2026  
**License:** MIT  
**Language:** Uzbek (O'zbek)

---

## 🎯 ROADMAP

- [ ] Video upload functionality
- [ ] Subtitle support
- [ ] Recommendation engine
- [ ] Social features (comments, ratings)
- [ ] Mobile app
- [ ] Payment integration
- [ ] CDN integration
- [ ] Advanced analytics

---

**Enjoy DramaMini! 🎬✨**
