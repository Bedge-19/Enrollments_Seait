<?php
// includes/navbar.php
$user = currentUser();
$role = $user['role'] ?? ($_SESSION['role'] ?? 'Guest');
$name = $user['name'] ?? ($_SESSION['name'] ?? 'User');
?>
<main class="app-main">
    <nav class="app-navbar no-print">
        <!-- Left: Toggle + Page Title -->
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle-btn d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle Navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-page-title">
                <h5><?= e($pageTitle ?? 'Dashboard') ?></h5>
                <span class="ay-badge d-none d-md-inline-block">A.Y. 2026–2027 &nbsp;•&nbsp; 1st Semester</span>
            </div>
        </div>

        <!-- Right: User info + dropdown -->
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="topbar-user-name"><?= e($name) ?></div>
                <div class="topbar-user-role"><i class="fas fa-circle" style="font-size:7px;color:#4ade80;"></i> <?= e($role) ?></div>
            </div>
            <div class="dropdown">
                <button class="topbar-avatar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="dropdown-header-row">
                            <small style="font-size:11px;color:var(--on-surface-variant);display:block;">Signed in as</small>
                            <strong style="font-size:14px;color:var(--on-surface);display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($user['username'] ?? $name) ?></strong>
                            <span class="badge bg-primary mt-1"><?= e($role) ?></span>
                        </div>
                    </li>
                    <?php if ($role === 'Student'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/index.php"><i class="fas fa-tasks text-muted"></i> Checklist</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/study_load.php"><i class="fas fa-book-reader text-muted"></i> Study Load</a></li>
                    <?php elseif ($role === 'Department'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/department/review.php"><i class="fas fa-clipboard-check text-muted"></i> Review Admissions</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/department/students.php"><i class="fas fa-users text-muted"></i> Student Directory</a></li>
                    <?php elseif ($role === 'Registrar'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/registrar/evaluate.php"><i class="fas fa-user-graduate text-muted"></i> Study Load Queue</a></li>
                    <?php elseif ($role === 'Accounting'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/accounting/payment.php"><i class="fas fa-cash-register text-muted"></i> Collect Payment</a></li>
                    <?php elseif ($role === 'Clinic'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/clinic/medical_record.php"><i class="fas fa-heartbeat text-muted"></i> Medical Exam</a></li>
                    <?php elseif ($role === 'Security Office'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/security/verify.php"><i class="fas fa-shield-alt text-muted"></i> Verify Clearances</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/security/id_cards.php"><i class="fas fa-id-card text-muted"></i> ID Validation & Cards</a></li>
                    <?php elseif ($role === 'Admin'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php"><i class="fas fa-user-cog text-muted"></i> User Accounts</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/logs.php"><i class="fas fa-history text-muted"></i> Audit Logs</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="app-content">
        <?= displayFlash() ?>
