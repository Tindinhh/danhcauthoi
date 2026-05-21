<?php
require_once 'config.php';
requireLogin();

// Xử lý đặt sân
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time   = $_POST['end_time'] ?? '';
    $note       = trim($_POST['note'] ?? '');

    if ($start_time >= $end_time) {
        setFlash('danger', 'Giờ kết thúc phải sau giờ bắt đầu.');
    } elseif (checkConflict($conn, $date, $start_time, $end_time)) {
        setFlash('danger', 'Khung giờ này đã có người đặt (confirmed). Hãy chọn giờ khác.');
    } else {
        $uid  = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, date, start_time, end_time, note) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $uid, $date, $start_time, $end_time, $note);
        $stmt->execute();
        setFlash('success', 'Đã gửi yêu cầu đặt sân! Chờ admin xác nhận.');
        redirect('dashboard.php');
    }
}

// Xóa booking (chỉ pending/rejected)
if (isset($_GET['delete'])) {
    $bid  = (int)$_GET['delete'];
    $uid  = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id=? AND user_id=? AND status != 'confirmed'");
    $stmt->bind_param('ii', $bid, $uid);
    $stmt->execute();
    setFlash('info', 'Đã xóa booking.');
    redirect('dashboard.php');
}

// Booking của user
$uid = $_SESSION['user_id'];
$myBookings = [];
$r = $conn->prepare("SELECT * FROM bookings WHERE user_id=? ORDER BY date DESC, start_time ASC");
$r->bind_param('i', $uid);
$r->execute();
$res = $r->get_result();
while ($row = $res->fetch_assoc()) $myBookings[] = $row;

// Lịch confirmed chung
$allConfirmed = [];
$res2 = $conn->query("
    SELECT b.*, u.username
    FROM bookings b JOIN users u ON b.user_id = u.id
    WHERE b.status = 'confirmed'
    ORDER BY b.date ASC, b.start_time ASC
");
while ($row = $res2->fetch_assoc()) $allConfirmed[] = $row;

$pageTitle = 'Đặt sân — danhcauthoi';
include 'includes/header.php';
?>

<div class="container py-5">
  <div class="row g-4">

    <!-- FORM ĐẶT SÂN -->
    <div class="col-lg-5">
      <div class="card-panel">
        <h4 class="panel-title mb-4"><i class="bi bi-calendar-plus me-2"></i>Đặt sân mới</h4>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">Ngày chơi</label>
            <input type="date" name="date" class="form-control form-control-lg"
                   min="<?= date('Y-m-d') ?>" required />
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Giờ bắt đầu</label>
              <input type="time" name="start_time" class="form-control form-control-lg" required />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Giờ kết thúc</label>
              <input type="time" name="end_time" class="form-control form-control-lg" required />
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Ghi chú <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
            <input type="text" name="note" class="form-control"
                   placeholder="VD: chơi 4 người, cần mang vợt..." maxlength="200" />
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-send me-2"></i>Gửi yêu cầu đặt sân
          </button>
        </form>
        <div class="alert alert-info mt-3 mb-0 small">
          <i class="bi bi-info-circle me-1"></i>
          Booking sẽ ở trạng thái <strong>Chờ duyệt</strong> cho đến khi admin xác nhận.
        </div>
      </div>
    </div>

    <!-- DANH SÁCH -->
    <div class="col-lg-7">

      <!-- Booking của tôi -->
      <div class="card-panel mb-4">
        <h4 class="panel-title mb-3"><i class="bi bi-list-ul me-2"></i>Booking của tôi</h4>
        <?php if ($myBookings): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr><th>Ngày</th><th>Giờ</th><th>Ghi chú</th><th>Trạng thái</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($myBookings as $b): ?>
              <tr>
                <td class="fw-semibold"><?= h($b['date']) ?></td>
                <td><?= h(substr($b['start_time'],0,5)) ?> – <?= h(substr($b['end_time'],0,5)) ?></td>
                <td class="text-muted small"><?= h($b['note'] ?: '—') ?></td>
                <td>
                  <?php if ($b['status'] === 'confirmed'): ?>
                    <span class="badge bg-success">✅ Confirmed</span>
                  <?php elseif ($b['status'] === 'rejected'): ?>
                    <span class="badge bg-danger">❌ Rejected</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">⏳ Chờ duyệt</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($b['status'] !== 'confirmed'): ?>
                    <a href="dashboard.php?delete=<?= $b['id'] ?>"
                       class="btn btn-outline-danger btn-sm"
                       onclick="return confirm('Xóa booking này?')">
                      <i class="bi bi-trash"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="empty-state py-4">
            <div class="empty-icon">📅</div>
            <p class="text-muted">Bạn chưa có booking nào. Hãy đặt sân!</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Lịch confirmed chung -->
      <div class="card-panel">
        <h4 class="panel-title mb-3"><i class="bi bi-calendar-check me-2"></i>Lịch sân đã được duyệt</h4>
        <?php if ($allConfirmed): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr><th>Ngày</th><th>Giờ</th><th>Người đặt</th><th>Ghi chú</th></tr>
            </thead>
            <tbody>
              <?php foreach ($allConfirmed as $b): ?>
              <tr>
                <td class="fw-semibold"><?= h($b['date']) ?></td>
                <td><?= h(substr($b['start_time'],0,5)) ?> – <?= h(substr($b['end_time'],0,5)) ?></td>
                <td><span class="badge bg-light text-dark"><i class="bi bi-person me-1"></i><?= h($b['username']) ?></span></td>
                <td class="text-muted small"><?= h($b['note'] ?: '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="empty-state py-3">
            <div class="empty-icon">🏸</div>
            <p class="text-muted">Chưa có lịch nào được xác nhận.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
