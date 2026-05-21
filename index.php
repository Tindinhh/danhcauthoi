<?php
require_once 'config.php';
$pageTitle = 'danhcauthoi 🏸 — Đặt lịch đánh cầu';

// Lấy 5 booking confirmed sắp tới
$upcoming = [];
$result = $conn->query("
    SELECT b.*, u.username
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'confirmed' AND b.date >= CURDATE()
    ORDER BY b.date ASC, b.start_time ASC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) $upcoming[] = $row;

include 'includes/header.php';
?>

<!-- HERO -->
<section class="hero-section">
  <div class="container">
    <div class="hero-content text-center">
      <div class="hero-badge mb-3">🏸 Hệ thống đặt sân cầu lông</div>
      <h1 class="hero-title">danhcauthoi</h1>
      <p class="hero-sub">Đặt lịch nhanh gọn · Xem lịch chung · Không trùng giờ</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
        <?php if (isLoggedIn()): ?>
          <a href="dashboard.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-calendar-plus me-2"></i>Đặt sân ngay
          </a>
        <?php else: ?>
          <a href="register.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-person-plus me-2"></i>Tạo tài khoản
          </a>
          <a href="login.php" class="btn btn-outline-light btn-lg px-5">Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="py-5">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <div class="col-md-4">
        <div class="feature-card text-center p-4">
          <div class="feature-icon">📅</div>
          <h5>Đặt lịch dễ dàng</h5>
          <p class="text-muted mb-0">Chọn ngày, giờ và gửi yêu cầu chỉ trong vài giây.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center p-4">
          <div class="feature-icon">✅</div>
          <h5>Admin xác nhận</h5>
          <p class="text-muted mb-0">Admin duyệt booking, tránh trùng giờ hoàn toàn.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card text-center p-4">
          <div class="feature-icon">👀</div>
          <h5>Xem lịch chung</h5>
          <p class="text-muted mb-0">Mọi người đều thấy lịch đã confirmed rõ ràng.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- UPCOMING -->
<?php if ($upcoming): ?>
<section class="py-4">
  <div class="container">
    <h4 class="section-title mb-4"><i class="bi bi-calendar-check me-2"></i>Lịch sắp tới</h4>
    <div class="row g-3">
      <?php foreach ($upcoming as $b): ?>
      <div class="col-md-4">
        <div class="booking-card booking-confirmed p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold"><?= h($b['date']) ?></div>
              <div class="text-muted small">
                <?= h(substr($b['start_time'],0,5)) ?> – <?= h(substr($b['end_time'],0,5)) ?>
              </div>
              <?php if ($b['note']): ?>
                <div class="small mt-1 fst-italic"><?= h($b['note']) ?></div>
              <?php endif; ?>
            </div>
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Confirmed</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
