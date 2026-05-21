<?php
require_once 'config.php';

if (isLoggedIn()) redirect('dashboard.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3)    $errors[] = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    if (strlen($password) < 6)    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($password !== $confirm)   $errors[] = 'Mật khẩu xác nhận không khớp.';

    if (empty($errors)) {
        // Kiểm tra username tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt2->bind_param('ss', $username, $hash);
            $stmt2->execute();
            setFlash('success', 'Đăng ký thành công! Hãy đăng nhập.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Đăng ký — danhcauthoi';
include 'includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header text-center mb-4">
      <div class="auth-icon">🏸</div>
      <h2 class="auth-title">Đăng ký</h2>
      <p class="text-muted">Tạo tài khoản để bắt đầu đặt sân</p>
    </div>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Tên đăng nhập</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="username" class="form-control"
                 placeholder="username (ít nhất 3 ký tự)"
                 value="<?= h($_POST['username'] ?? '') ?>"
                 minlength="3" maxlength="50" required autofocus />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Mật khẩu</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" id="pw1" class="form-control"
                 placeholder="Ít nhất 6 ký tự" minlength="6" required />
          <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pw1','eye1')">
            <i class="bi bi-eye" id="eye1"></i>
          </button>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="confirm_password" id="pw2" class="form-control"
                 placeholder="Nhập lại mật khẩu" minlength="6" required />
          <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pw2','eye2')">
            <i class="bi bi-eye" id="eye2"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-person-plus me-2"></i>Tạo tài khoản
      </button>
    </form>

    <hr class="my-4" />
    <p class="text-center mb-0 text-muted">
      Đã có tài khoản?
      <a href="login.php" class="link-primary fw-semibold">Đăng nhập</a>
    </p>
  </div>
</div>

<script>
function togglePwd(id, eyeId) {
  const f = document.getElementById(id);
  const i = document.getElementById(eyeId);
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php include 'includes/footer.php'; ?>
