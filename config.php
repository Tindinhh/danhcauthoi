<?php
// ─── Đổi thông tin này theo cPanel của 42web.io ───
define('DB_HOST', 'sql206.infinityfree.com');
define('DB_USER', 'if0_41983696');
define('DB_PASS', 'mật_khẩu_database');
define('DB_NAME', 'if0_41983696_danhcau');

define('SITE_NAME', 'danhcauthoi.42web.io');

// Kết nối
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="padding:2rem;color:red;font-family:sans-serif;">
        ❌ Lỗi kết nối database: ' . $conn->connect_error . '
        <br><small>Kiểm tra lại config.php</small>
    </div>');
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Không có quyền truy cập trang này.');
        header('Location: dashboard.php');
        exit;
    }
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Kiểm tra trùng giờ (chỉ với confirmed bookings)
function checkConflict($conn, string $date, string $start, string $end, int $excludeId = 0): bool {
    $sql = "SELECT id FROM bookings
            WHERE date = ? AND status = 'confirmed'
            AND start_time < ? AND end_time > ?
            AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $date, $end, $start, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}
