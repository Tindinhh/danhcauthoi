<?php
require_once 'config.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Lấy thông tin user hiện tại
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$errors   = [];
$tab      = $_GET['tab'] ?? 'info'; // info | password

// ─── XỬ LÝ CẬP NHẬT THÔNG TIN ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_info'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');

    // Xử lý avatar upload
    $avatar = $user['avatar']; // giữ ảnh cũ mặc định

    if (!empty($_FILES['avatar']['name'])) {
        $file      = $_FILES['avatar'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize   = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Chỉ hỗ trợ ảnh JPG, PNG, GIF, WEBP.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Ảnh tối đa 2MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload thất bại. Thử lại.';
        } else {
            // Xóa ảnh cũ nếu có
            if ($user['avatar'] && file_exists(__DIR__ . '/' . $user['avatar'])) {
                unlink(__DIR__ . '/' . $user['avatar']);
            }
            $newName = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            $dest    = __DIR__ . '/uploads/avatars/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $avatar = 'uploads/avatars/' . $newName;
            } else {
                $errors[] = 'Không thể lưu ảnh. Kiểm tra quyền thư mục uploads/avatars/';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, bio=?, avatar=? WHERE id=?");
        $stmt->bind_param('ssssi', $full_name, $phone, $bio, $avatar, $uid);
        $stmt->execute();

        // Reload user
        $stmt2 = $conn->prepare("SELECT * FROM users WHERE id=?");
        $stmt2->bind_param('i', $uid);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();

        setFlash('success', 'Đã cập nhật thông tin!');
        redirect('profile.php?tab=info');
    }
    $tab = 'info';
}

// ─── XỬ LÝ ĐỔI MẬT KHẨU ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $errors[] = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $uid);
        $stmt->execute();
        setFlash('success', 'Đã đổi mật khẩu thành công!');
        redirect('profile.php?tab=password');
    }
    $tab = 'password';
}

// Đếm booking của user
$bCount = $conn->prepare("SELECT COUNT(*) as c FROM bookings WHERE user_id=?");
$bCount->bind_param('i', $uid);
$bCount->execute();
$totalBookings = $bCount->get_result()->fetch_assoc()['c'];

$confirmedCount = $conn->prepare("SELECT COUNT(*) as c FROM bookings WHERE user_id=? AND status='confirmed'");
$confirmedCount->bind_param('i', $uid);
$confirmedCount->execute();
$totalConfirmed = $confirmedCount->get_result()->fetch_assoc()['c'];

$pageTitle = 'Tài khoản — danhcauthoi';
include 'includes/header.php';
?>

<div class="container py-5">
  <div class="row g-4 justify-content-center">

    <!-- LEFT: Avatar + Stats -->
    <div class="col-lg-3 col-md-4">

      <!-- Avatar card -->
      <div class="card-panel text-center mb-4">
        <div class="avatar-wrapper mx-auto mb-3">
          <?php if ($user['avatar'] && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
            <img src="<?= h($user['avatar']) ?>?v=<?= time() ?>"
                 class="avatar-img" alt="Avatar" id="avatarPreview" />
          <?php else: ?>
            <div class="avatar-placeholder" id="avatarPlaceholder">
              <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <img src="" class="avatar-img d-none" alt="Avatar" id="avatarPreview" />
          <?php endif; ?>
        </div>

        <h5 class="fw-bold mb-0">
          <?= h($user['full_name'] ?: $user['username']) ?>
        </h5>
        <?php if ($user['full_name']): ?>
          <div class="text-muted small">@<?= h($user['username']) ?></div>
        <?php endif; ?>
        <?php if ($user['is_admin']): ?>
          <span class="badge bg-danger mt-1">Admin</span>
        <?php endif; ?>

        <?php if ($user['bio']): ?>
          <p class="text-muted small mt-2 mb-0 fst-italic">"<?= h($user['bio']) ?>"</p>
        <?php endif; ?>
      </div>

      <!-- Stats -->
      <div class="card-panel">
        <h6 class="panel-title mb-3">📊 Thống kê</h6>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">Tổng booking</span>
          <span class="fw-bold"><?= $totalBookings ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">Đã confirmed</span>
          <span class="fw-bold text-success"><?= $totalConfirmed ?></span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted small">Thành viên từ</span>
          <span class="fw-bold small"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
        </div>
      </div>

    </div>

    <!-- RIGHT: Form tabs -->
    <div class="col-lg-7 col-md-8">
      <div class="card-panel">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTabs">
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'info' ? 'active' : '' ?>"
               href="profile.php?tab=info">
              <i class="bi bi-person me-1"></i>Thông tin cá nhân
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'password' ? 'active' : '' ?>"
               href="profile.php?tab=password">
              <i class="bi bi-lock me-1"></i>Đổi mật khẩu
            </a>
          </li>
        </ul>

        <!-- Error messages -->
        <?php foreach ($errors as $e): ?>
          <div class="alert alert-danger"><?= h($e) ?></div>
        <?php endforeach; ?>

        <!-- TAB: THÔNG TIN CÁ NHÂN -->
        <?php if ($tab === 'info'): ?>
        <form method="POST" enctype="multipart/form-data">

          <!-- Avatar upload -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Ảnh đại diện</label>
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-sm-wrapper">
                <?php if ($user['avatar'] && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                  <img src="<?= h($user['avatar']) ?>?v=<?= time() ?>"
                       class="avatar-sm" id="avatarThumb" />
                <?php else: ?>
                  <div class="avatar-sm-placeholder" id="avatarThumb">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <input type="file" name="avatar" id="avatarInput"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       class="form-control form-control-sm"
                       style="max-width:260px"
                       onchange="previewAvatar(this)" />
                <div class="text-muted small mt-1">JPG, PNG, GIF, WEBP — tối đa 2MB</div>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tên đăng nhập</label>
              <input type="text" class="form-control" value="<?= h($user['username']) ?>" disabled />
              <div class="text-muted small mt-1">Không thể thay đổi</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Họ và tên</label>
              <input type="text" name="full_name" class="form-control"
                     placeholder="Nguyễn Văn A"
                     value="<?= h($user['full_name'] ?? '') ?>"
                     maxlength="150" />
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Số điện thoại</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-telephone"></i></span>
              <input type="tel" name="phone" class="form-control"
                     placeholder="0912 345 678"
                     value="<?= h($user['phone'] ?? '') ?>"
                     maxlength="20" />
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Giới thiệu bản thân <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
            <textarea name="bio" class="form-control" rows="3"
                      placeholder="VD: Hay đánh buổi tối, chơi tay ngang 🏸"
                      maxlength="300"><?= h($user['bio'] ?? '') ?></textarea>
          </div>

          <button type="submit" name="save_info" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-check-circle me-2"></i>Lưu thay đổi
          </button>
        </form>

        <!-- TAB: ĐỔI MẬT KHẨU -->
        <?php elseif ($tab === 'password'): ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="current_password" id="pw0"
                     class="form-control" placeholder="••••••" required />
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pw0','eye0')">
                <i class="bi bi-eye" id="eye0"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Mật khẩu mới</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" name="new_password" id="pw1"
                     class="form-control" placeholder="Ít nhất 6 ký tự"
                     minlength="6" required />
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pw1','eye1')">
                <i class="bi bi-eye" id="eye1"></i>
              </button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" name="confirm_password" id="pw2"
                     class="form-control" placeholder="Nhập lại mật khẩu mới"
                     minlength="6" required />
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pw2','eye2')">
                <i class="bi bi-eye" id="eye2"></i>
              </button>
            </div>
          </div>
          <button type="submit" name="save_password" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-shield-check me-2"></i>Đổi mật khẩu
          </button>
        </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
function togglePwd(id, eyeId) {
  const f = document.getElementById(id);
  const i = document.getElementById(eyeId);
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    // Preview lớn (sidebar)
    const prev  = document.getElementById('avatarPreview');
    const place = document.getElementById('avatarPlaceholder');
    if (prev)  { prev.src = e.target.result; prev.classList.remove('d-none'); }
    if (place) place.classList.add('d-none');

    // Preview nhỏ (bên form)
    const thumb = document.getElementById('avatarThumb');
    if (thumb) {
      if (thumb.tagName === 'IMG') {
        thumb.src = e.target.result;
      } else {
        // Thay div placeholder bằng img
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'avatar-sm';
        img.id = 'avatarThumb';
        thumb.replaceWith(img);
      }
    }
  };
  reader.readAsDataURL(input.files[0]);
}
</script>

<?php include 'includes/footer.php'; ?>
