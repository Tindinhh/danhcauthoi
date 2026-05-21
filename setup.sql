-- Import file này trong phpMyAdmin của 42web.io
-- Database: dùng DB đã tạo trong cPanel

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Bảng users
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(100) NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `is_admin`    TINYINT(1) DEFAULT 0,
  `full_name`   VARCHAR(150) DEFAULT '',
  `phone`       VARCHAR(20)  DEFAULT '',
  `bio`         VARCHAR(300) DEFAULT '',
  `avatar`      VARCHAR(255) DEFAULT '',
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nếu DB cũ đã có, chạy các dòng này để thêm cột mới:
-- ALTER TABLE users ADD COLUMN full_name VARCHAR(150) DEFAULT '';
-- ALTER TABLE users ADD COLUMN phone     VARCHAR(20)  DEFAULT '';
-- ALTER TABLE users ADD COLUMN bio       VARCHAR(300) DEFAULT '';
-- ALTER TABLE users ADD COLUMN avatar    VARCHAR(255) DEFAULT '';

-- Bảng bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `date`       DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time`   TIME NOT NULL,
  `note`       VARCHAR(300) DEFAULT '',
  `status`     ENUM('pending','confirmed','rejected') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tài khoản admin mặc định: admin / admin123
-- (password là bcrypt của "admin123")
INSERT IGNORE INTO `users` (`username`, `password`, `is_admin`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

SET FOREIGN_KEY_CHECKS = 1;
