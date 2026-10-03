<?php
// admin/index.php - System Administrator Dashboard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Admin');
$pageTitle = 'Administrator Dashboard';
$db = getDBConnection();

// Metrics
$stmtUsers = $db->query("SELECT COUNT(*) FROM users");
$totalUsers = (int)$stmtUsers->fetchColumn();

$stmtStaff = $db->query("SELECT COUNT(*) FROM staff");
$totalStaff = (int)$stmtStaff->fetchColumn();

$stmtActiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'Active'");
$activeUsers = (int)$stmtActiveUsers->fetchColumn();

$stmtTotalLogs = $db->query("SELECT COUNT(*) FROM system_log");
$totalLogs = (int)$stmtTotalLogs->fetchColumn();

// Recent System Audit Logs
$stmtLogs = $db->query("SELECT * FROM system_log ORDER BY LogID DESC LIMIT 15");
$recentLogs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">System Administration &amp; Security</h4>
        <p class="text-muted small mb-0">Manage SEAIT staff accounts, user permissions, and monitor system audit trails.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-history me-1"></i> Audit Logs
        </a>
        <a href="<?= BASE_URL ?>/admin/create_user.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Add Staff Account
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Accounts</span>
                    <h3 class="fw-bold my-1 text-primary"><?= number_format($totalUsers) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/admin/users.php" class="text-decoration-none fw-semibold">Manage accounts &rarr;</a></small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-users-cog"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Staff Members</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalStaff) ?></h3>
                    <small class="text-muted">7 Office Roles Active</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-id-card-alt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Active Status</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($activeUsers) ?></h3>
                    <small class="text-muted">Enabled Accounts</small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Audit Trail Logs</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($totalLogs) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/admin/logs.php" class="text-decoration-none fw-semibold">View logs &rarr;</a></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-history"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent System Audit Trail Logs Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-alt me-2 text-primary"></i>Recent System Activity & Audit Trail</h6>
            <small class="text-muted">Real-time log of changes and operations performed across modules</small>
        </div>
        <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-outline-primary btn-sm">View Full Audit Logs</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-history fa-2x text-muted mb-2 d-block"></i>
                            No audit logs recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $l): ?>
                        <tr>
                            <td class="small text-muted"><?= formatDate($l['Timestamp'], 'M d, Y h:i A') ?></td>
                            <td class="fw-bold text-dark"><?= e($l['User']) ?></td>
                            <td><span class="badge badge-soft-dark"><?= e($l['Role']) ?></span></td>
                            <td><span class="badge badge-soft-primary"><?= e($l['Action']) ?></span></td>
                            <td class="small fw-semibold"><?= e($l['Module']) ?></td>
                            <td class="small text-muted"><?= e($l['Details'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
