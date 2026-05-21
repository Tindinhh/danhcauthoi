<?php
require_once 'config.php';
requireAdmin();

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid    = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm') {
        // Kiểm tra trùng trước
        $stmt = $conn->prepare("SELECT date, start_time, end_time FROM bookings WHERE id=?");
        $stmt->bind_param('i', $bid);
        $stmt->execute();
        $b = $stmt->get_result()->fetch_assoc();

        if ($b && checkConflict($conn, $b['date'], $b['start_time'], $b['end_time'], $bid)) {
            setFlash('danger', 'Không thể xác nhận — khung giờ bị trùng với booking đã confirmed khác.');
        } else {
            $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?")->bind_param('i',$bid) && true;
            $u = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
            $u->bind_param('i', $bid); $u->execute();
            setFlash('success', 'Đã xác nhận booking.');
        }
    } elseif ($action === 'reject') {
        $u = $conn->prepare("UPDATE bookings SET status='rejected' WHERE id=?");
        $u->bind_param('i', $bid); $u->execute();
        setFlash('warning', 'Đã từ chối booking.');
    } elseif ($action === 'delete') {
        $u = $conn->prepare("DELETE FROM bookings WHERE id=?");
        $u->bind_param('i', $bid); $u->execute();
        setFlash('info', 'Đã xóa booking.');
    } elseif ($action === 'toggle_admin') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === $_SESSION['user_id']) {
            setFlash('danger', 'Không thể tự bỏ quyền admin của chính mình.');
        } else {
            $u = $conn->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id=?");
            $u->bind_param('i', $uid); $u->execute();
            setFlash('success', 'Đã cập nhật quyền admin.');
        }
    }
    redirect('admin.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

// Filter
$filterStatus = $_GET['status'] ?? 'all';
$where = $filterStatus !== 'all' ? "WHERE b.status = '" . $conn->real_escape_string($filterStatus) . "'" : '';

$bookings = [];
$res = $conn->query("
    SELECT b.*, u.username
    FROM bookings b JOIN users u ON b.user_id = u.id
    $where
    ORDER BY b.date ASC, b.start_time ASC
");
while ($row = $res->fetch_assoc()) $bookings[] = $row;

// Stats
$total     = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
$confirmed = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='confirmed'")->fetch_assoc()['c'];
$rejected  = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='rejected'")->fetch_assoc()['c'];

// Top players
$topPlayers = [];
$res = $conn->query("
    SELECT u.username, COUNT(b.id) as cnt
    FROM bookings b JOIN users u ON b.user_id = u.id
    GROUP BY b.user_id ORDER BY cnt DESC LIMIT 5
");
while ($row = $res->fetch_assoc()) $topPlayers[] = $row;

// Stats by date
$statsByDate = [];
$res = $conn->query("SELECT date, COUNT(*) as cnt FROM bookings GROUP BY date ORDER BY date DESC LIMIT 10");
while ($row = $res->fetch_assoc()) $statsByDate[] = $row;

// Users
$users = [];
$res = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) $users[] = $row;

$pageTitle = 'Admin Dashboard — danhcauthoi';
include 'includes/header.php';
?>

<div class="container py-5">

  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="admin-avatar"><i class="bi bi-shield-check"></i></div>
    <div>
      <h2 class="mb-0 fw-bold">Admin Dashboard</h2>
      <p class="text-muted mb-0">Quản lý booking & thành viên</p>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-total"><div class="stat-num"><?= $total ?></div><div class="stat-label">Tổng booking</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-pending"><div class="stat-num"><?= $pending ?></div><div class="stat-label">Chờ duyệt</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-confirmed"><div class="stat-num"><?= $confirmed ?></div><div class="stat-label">Đã xác nhận</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-rejected"><div class="stat-num"><?= $rejected ?></div><div class="stat-label">Từ chối</div></div>
    </div>
  </div>

  <div class="row g-4">

    <!-- BOOKINGS -->
    <div class="col-lg-8">
      <div class="card-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h5 class="panel-title mb-0"><i class="bi bi-list-check me-2"></i>Danh sách booking</h5>
          <div class="btn-group btn-group-sm">
            <?php
            $statuses = ['all'=>'Tất cả','pending'=>'Chờ duyệt','confirmed'=>'Confirmed','rejected'=>'Rejected'];
            $btnClass = ['all'=>'btn-primary','pending'=>'btn-warning','confirmed'=>'btn-success','rejected'=>'btn-danger'];
            foreach ($statuses as $s => $label):
              $active = $filterStatus === $s ? $btnClass[$s] : 'btn-outline-secondary';
            ?>
            <a href="admin.php?status=<?= $s ?>" class="btn <?= $active ?>"><?= $label ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($bookings): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr><th>#</th><th>Người đặt</th><th>Ngày</th><th>Giờ</th><th>Ghi chú</th><th>Trạng thái</th><th>Hành động</th></tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
              <tr>
                <td class="text-muted small"><?= $b['id'] ?></td>
                <td class="fw-semibold"><?= h($b['username']) ?></td>
                <td class="fw-semibold"><?= h($b['date']) ?></td>
                <td><?= h(substr($b['start_time'],0,5)) ?> – <?= h(substr($b['end_time'],0,5)) ?></td>
                <td class="text-muted small"><?= h($b['note'] ?: '—') ?></td>
                <td>
                  <?php if ($b['status'] === 'confirmed'): ?>
                    <span class="badge bg-success">✅ Confirmed</span>
                  <?php elseif ($b['status'] === 'rejected'): ?>
                    <span class="badge bg-danger">❌ Rejected</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">⏳ Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" class="d-flex gap-1 flex-wrap">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <?php if ($b['status'] === 'pending'): ?>
                      <button name="action" value="confirm" class="btn btn-success btn-sm" title="Xác nhận">
                        <i class="bi bi-check-lg"></i>
                      </button>
                      <button name="action" value="reject" class="btn btn-warning btn-sm" title="Từ chối">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    <?php endif; ?>
                    <button name="action" value="delete" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Xóa booking #<?= $b['id'] ?>?')" title="Xóa">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="empty-state py-4">
            <div class="empty-icon">📭</div>
            <p class="text-muted">Không có booking nào.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">

      <!-- Top players -->
      <div class="card-panel mb-4">
        <h5 class="panel-title mb-3"><i class="bi bi-trophy me-2"></i>Top người đặt nhiều nhất</h5>
        <?php if ($topPlayers): ?>
        <ol class="list-group list-group-flush list-group-numbered">
          <?php foreach ($topPlayers as $p): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span><?= h($p['username']) ?></span>
            <span class="badge bg-primary rounded-pill"><?= $p['cnt'] ?> lần</span>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
          <p class="text-muted mb-0">Chưa có dữ liệu.</p>
        <?php endif; ?>
      </div>

      <!-- Stats by date -->
      <div class="card-panel mb-4">
        <h5 class="panel-title mb-3"><i class="bi bi-bar-chart me-2"></i>Thống kê theo ngày</h5>
        <?php foreach ($statsByDate as $s): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small fw-semibold"><?= h($s['date']) ?></span>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="width:80px;height:8px;">
              <div class="progress-bar bg-primary" style="width:<?= min($s['cnt']*25,100) ?>%"></div>
            </div>
            <span class="badge bg-secondary"><?= $s['cnt'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Users -->
      <div class="card-panel">
        <h5 class="panel-title mb-3"><i class="bi bi-people me-2"></i>Thành viên (<?= count($users) ?>)</h5>
        <?php foreach ($users as $u): ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <span class="fw-semibold"><?= h($u['username']) ?></span>
            <?php if ($u['is_admin']): ?><span class="badge bg-danger ms-1 small">Admin</span><?php endif; ?>
          </div>
          <?php if ($u['id'] != $_SESSION['user_id']): ?>
          <form method="POST" onsubmit="return confirm('Thay đổi quyền admin cho <?= h($u['username']) ?>?')">
            <input type="hidden" name="action" value="toggle_admin">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
              <?= $u['is_admin'] ? '🔓 Bỏ admin' : '🔐 Cấp admin' ?>
            </button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
