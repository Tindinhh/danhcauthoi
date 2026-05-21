# 🏸 danhcauthoi — PHP + MySQL

Web đặt lịch đánh cầu lông. Chạy trên **42web.io** (InfinityFree) hoàn toàn miễn phí.

---

## Deploy lên 42web.io — Step by step

### Bước 1: Tạo hosting
1. Vào https://42web.io → **Sign up** / đăng nhập
2. Tạo website mới → chọn domain (vd: `danhcauthoi.42web.io`)
3. Vào **Control Panel (cPanel)**

### Bước 2: Tạo Database MySQL
1. cPanel → **MySQL Databases**
2. Tạo **Database** mới → ghi lại tên (vd: `epiz_xxx_cauthoi`)
3. Tạo **User** mới → ghi lại username + password
4. **Add User to Database** → chọn All Privileges

### Bước 3: Import database
1. cPanel → **phpMyAdmin**
2. Chọn database vừa tạo
3. Tab **Import** → chọn file `setup.sql` → **Go**

### Bước 4: Sửa config.php
Mở file `config.php`, điền thông tin DB vào:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'epiz_xxx_user');    // username vừa tạo
define('DB_PASS', 'matkhaudb');        // password vừa tạo
define('DB_NAME', 'epiz_xxx_cauthoi'); // tên database
```

### Bước 5: Upload code
1. cPanel → **File Manager** → vào thư mục `htdocs`
2. Upload toàn bộ file (trừ `setup.sql` và `README.md`)
3. Hoặc dùng **FTP** (FileZilla) với thông tin FTP trong cPanel

### Bước 6: Truy cập
Vào `https://danhcauthoi.42web.io` là xong! 🎉

---

## Tài khoản admin mặc định
- **Username:** `admin`
- **Password:** `admin123`

⚠️ Đổi ngay sau khi deploy: đăng nhập → vào DB qua phpMyAdmin → đổi password

---

## Cấu trúc file
```
htdocs/
├── config.php        ← Đổi thông tin DB vào đây
├── index.php
├── login.php
├── logout.php
├── register.php
├── dashboard.php
├── admin.php
├── includes/
│   ├── header.php
│   └── footer.php
└── static/
    └── style.css
```
