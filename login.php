<?php
require_once 'config.php';

if (isLoggedIn()) redirect('dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        setFlash('success', 'Chào mừng trở lại, ' . $user['username'] . '! 🏸');
        redirect('dashboard.php');
    } else {
        setFlash('danger', 'Sai tài khoản hoặc mật khẩu.');
    }
}

$pageTitle = 'Đăng nhập — danhcauthoi';
include 'includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header text-center mb-4">
      <div class="auth-icon">🏸</div>
      <h2 class="auth-title">Đăng nhập</h2>
      <p class="text-muted">Chào mừng quay lại!</p>
    </div>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Tên đăng nhập</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="username" class="form-control"
                 placeholder="username" required autofocus />
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Mật khẩu</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" id="pw" class="form-control"
                 placeholder="••••••" required />
          <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">
            <i class="bi bi-eye" id="eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
      </button>
    </form>

    <hr class="my-4" />
    <p class="text-center mb-0 text-muted">
      Chưa có tài khoản?
      <a href="register.php" class="link-primary fw-semibold">Đăng ký ngay</a>
    </p>
  </div>
</div>

<script>
function togglePwd() {
  const f = document.getElementById('pw');
  const i = document.getElementById('eye');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php include 'includes/footer.php'; ?>
