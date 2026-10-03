<?php
// includes/sidebar.php
$user = currentUser();
$role = $user['role'] ?? ($_SESSION['role'] ?? '');
$name = $user['name'] ?? ($_SESSION['name'] ?? 'User');
$currentScript = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Fetch live pending counts for staff roles
$badgeDept = 0;
$badgeReg = 0;
$badgeAcct = 0;
$badgeCln = 0;
$badgeSec = 0;

try {
    $dbNav = getDBConnection();
    if ($role === 'Department') {
        $stmtB = $dbNav->query("SELECT COUNT(*) FROM admission WHERE Status = 'Pending'");
        $badgeDept = (int)$stmtB->fetchColumn();
    } elseif ($role === 'Registrar') {
        $stmtB = $dbNav->query("SELECT COUNT(*) FROM admission a JOIN student s ON s.StudentID = a.StudentID LEFT JOIN evaluation ev ON ev.StudentID = s.StudentID WHERE a.Status = 'Approved' AND (ev.Status IS NULL OR ev.Status = 'Pending')");
        $badgeReg = (int)$stmtB->fetchColumn();
    } elseif ($role === 'Accounting') {
        $stmtB = $dbNav->query("SELECT COUNT(*) FROM payment WHERE PaymentStatus = 'Pending'");
        $badgeAcct = (int)$stmtB->fetchColumn();
    } elseif ($role === 'Clinic') {
        $stmtB = $dbNav->query("SELECT COUNT(*) FROM clinic WHERE ClearanceStatus = 'Pending'");
        $badgeCln = (int)$stmtB->fetchColumn();
    } elseif ($role === 'Security Office') {
        $stmtB = $dbNav->query("SELECT COUNT(*) FROM vw_enrollment_checklist WHERE DepartmentStatus = 'Approved' AND RegistrarStatus = 'Completed' AND AccountingStatus = 'Completed' AND ClinicStatus = 'Completed' AND SecurityStatus != 'Completed'");
        $badgeSec = (int)$stmtB->fetchColumn();
    }
} catch (Exception $e) {
    // Fail silently on badge errors
}
?>
<aside class="app-sidebar no-print" id="appSidebar">
    <!-- Brand & Mobile Close -->
    <div class="sidebar-brand-wrapper">
        <a href="<?= BASE_URL ?>/" class="sidebar-brand">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT Logo" class="brand-logo-img" />
            <div class="sidebar-brand-text">
                <span class="brand-title">SEAIT</span>
                <span class="brand-subtitle">Enrollment Portal</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebarClose" aria-label="Close Navigation">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-nav">
        <!-- User Info Pill -->
        <div class="sidebar-user-pill">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($name, 0, 1)) ?>
            </div>
            <div style="overflow:hidden;min-width:0;">
                <div class="sidebar-user-name"><?= e($name) ?></div>
                <div class="sidebar-user-role">
                    <span class="sidebar-user-dot"></span>
                    <?= e($role) ?>
                </div>
            </div>
        </div>

        <div class="sidebar-heading"><?= e($role) ?> Navigation</div>

        <?php if ($role === 'Student'): ?>
            <a href="<?= BASE_URL ?>/student/index.php" class="nav-link <?= ($currentDir === 'student' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tasks"></i> Enrollment Checklist</span>
            </a>
            <a href="<?= BASE_URL ?>/student/study_load.php" class="nav-link <?= ($currentScript === 'study_load.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-book-reader"></i> My Study Load</span>
            </a>
            <a href="<?= BASE_URL ?>/student/grades.php" class="nav-link <?= ($currentScript === 'grades.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-graduation-cap"></i> Academic History</span>
            </a>
            <a href="<?= BASE_URL ?>/student/id_card.php" class="nav-link <?= ($currentScript === 'id_card.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-id-card"></i> Student ID Card</span>
            </a>

        <?php elseif ($role === 'Department'): ?>
            <a href="<?= BASE_URL ?>/department/index.php" class="nav-link <?= ($currentDir === 'department' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/department/review.php" class="nav-link <?= ($currentScript === 'review.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-clipboard-check"></i> Admissions Review</span>
                <?php if ($badgeDept > 0): ?>
                    <span class="badge"><?= number_format($badgeDept) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/department/students.php" class="nav-link <?= ($currentScript === 'students.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-users"></i> Student Directory</span>
            </a>
            <a href="<?= BASE_URL ?>/department/register_student.php" class="nav-link <?= ($currentScript === 'register_student.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-user-plus"></i> Register Student</span>
            </a>
            <a href="<?= BASE_URL ?>/department/evaluate_transferee.php" class="nav-link <?= ($currentScript === 'evaluate_transferee.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-exchange-alt"></i> Transferee Credits</span>
            </a>
            <a href="<?= BASE_URL ?>/department/student_grades.php" class="nav-link <?= ($currentScript === 'student_grades.php' || $currentScript === 'print_grades.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-award"></i> Student Grades</span>
            </a>

        <?php elseif ($role === 'Registrar'): ?>
            <a href="<?= BASE_URL ?>/registrar/index.php" class="nav-link <?= ($currentDir === 'registrar' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/registrar/evaluate.php" class="nav-link <?= ($currentScript === 'evaluate.php' || $currentScript === 'assign_load.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-user-graduate"></i> Study Load Assignment</span>
                <?php if ($badgeReg > 0): ?>
                    <span class="badge"><?= number_format($badgeReg) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/registrar/schedules.php" class="nav-link <?= ($currentScript === 'schedules.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-calendar-alt"></i> Master Schedules</span>
            </a>
            <a href="<?= BASE_URL ?>/registrar/sections.php" class="nav-link <?= ($currentScript === 'sections.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-layer-group"></i> Sections &amp; Blocks</span>
            </a>

        <?php elseif ($role === 'Accounting'): ?>
            <a href="<?= BASE_URL ?>/accounting/index.php" class="nav-link <?= ($currentDir === 'accounting' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/accounting/payment.php" class="nav-link <?= ($currentScript === 'payment.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-cash-register"></i> Collect Payment</span>
                <?php if ($badgeAcct > 0): ?>
                    <span class="badge"><?= number_format($badgeAcct) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/accounting/receipt.php" class="nav-link <?= ($currentScript === 'receipt.php' || $currentScript === 'print_receipt.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-receipt"></i> Payment Receipts</span>
            </a>

        <?php elseif ($role === 'Clinic'): ?>
            <a href="<?= BASE_URL ?>/clinic/index.php" class="nav-link <?= ($currentDir === 'clinic' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/clinic/medical_record.php" class="nav-link <?= ($currentScript === 'medical_record.php' || $currentScript === 'print_clearance.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-heartbeat"></i> Medical Records</span>
                <?php if ($badgeCln > 0): ?>
                    <span class="badge"><?= number_format($badgeCln) ?></span>
                <?php endif; ?>
            </a>

        <?php elseif ($role === 'Security Office'): ?>
            <a href="<?= BASE_URL ?>/security/index.php" class="nav-link <?= ($currentDir === 'security' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/security/verify.php" class="nav-link <?= ($currentScript === 'verify.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-shield-alt"></i> Final Confirmation</span>
                <?php if ($badgeSec > 0): ?>
                    <span class="badge"><?= number_format($badgeSec) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/security/id_cards.php" class="nav-link <?= ($currentScript === 'id_cards.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-id-card"></i> ID Validation & Cards</span>
            </a>

        <?php elseif ($role === 'Admin'): ?>
            <a href="<?= BASE_URL ?>/admin/index.php" class="nav-link <?= ($currentDir === 'admin' && $currentScript === 'index.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-link <?= ($currentScript === 'users.php' || $currentScript === 'edit_user.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-user-cog"></i> User Management</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/create_user.php" class="nav-link <?= ($currentScript === 'create_user.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-user-plus"></i> Add Staff Account</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/logs.php" class="nav-link <?= ($currentScript === 'logs.php') ? 'active' : '' ?>">
                <span class="nav-link-content"><i class="fas fa-history"></i> System Audit Logs</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-heading mt-3">Account</div>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-link" style="color:rgba(239,68,68,0.8);">
            <span class="nav-link-content"><i class="fas fa-sign-out-alt"></i> Sign Out</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
