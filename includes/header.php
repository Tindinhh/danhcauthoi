<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?? 'danhcauthoi 🏸' ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="static/style.css" />
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">🏸 danhcauthoi</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <?php if (isLoggedIn()):
          global $conn;
          $uid_h = $_SESSION['user_id'];
          $sq = $conn->prepare("SELECT avatar, full_name FROM users WHERE id=?");
          $sq->bind_param('i', $uid_h);
          $sq->execute();
          $uInfo = $sq->get_result()->fetch_assoc();
          $hasAvt = !empty($uInfo['avatar']) && file_exists(__DIR__ . '/../' . $uInfo['avatar']);
        ?>
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-calendar3"></i> Đặt sân</a>
          </li>
          <?php if (isAdmin()): ?>
          <li class="nav-item">
            <a class="nav-link nav-admin" href="admin.php"><i class="bi bi-shield-check"></i> Admin</a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link nav-user" href="profile.php">
              <?php if ($hasAvt): ?>
                <img src="<?= h($uInfo['avatar']) ?>" class="nav-avatar" alt="avatar" />
              <?php else: ?>
                <span class="nav-avatar-placeholder"><?= strtoupper(substr($_SESSION['username'],0,1)) ?></span>
              <?php endif; ?>
              <?= h(!empty($uInfo['full_name']) ? $uInfo['full_name'] : $_SESSION['username']) ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm" href="logout.php">Đăng xuất</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Đăng nhập</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-primary btn-sm" href="register.php">Đăng ký</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-3">
<?php $flash = getFlash(); if ($flash): ?>
  <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
</div>
