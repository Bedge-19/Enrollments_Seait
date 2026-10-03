<?php
// admin/logs.php - System Audit Trail Logs Browser
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Admin');
$pageTitle = 'System Audit Trail Logs';
$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$moduleFilter = trim($_GET['module'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(l.User LIKE ? OR l.Action LIKE ? OR l.Details LIKE ? OR l.TableAffected LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}
if ($moduleFilter !== '') {
    $where[] = "l.Module = ?";
    $params[] = $moduleFilter;
}
if ($roleFilter !== '') {
    $where[] = "l.Role = ?";
    $params[] = $roleFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmtCount = $db->prepare("SELECT COUNT(*) FROM system_log l {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmtLogs = $db->prepare("SELECT l.* FROM system_log l {$whereSql} ORDER BY l.LogID DESC LIMIT {$limit} OFFSET {$offset}");
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

$modules = $db->query("SELECT DISTINCT Module FROM system_log WHERE Module IS NOT NULL ORDER BY Module")->fetchAll(PDO::FETCH_COLUMN);
$roles = ['Admin', 'Department', 'Registrar', 'Accounting', 'Clinic', 'Security Office', 'Student', 'System'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">System Audit Trail Logs</h4>
        <p class="text-muted small mb-0">Total of <strong><?= number_format($total) ?></strong> audit entries recorded.</p>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by user, action, details, table..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= $m ?>" <?= $moduleFilter === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-12 d-flex gap-1 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Audit Trail Logs Table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Target / Record</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No audit logs matching search filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="small text-muted text-nowrap"><?= formatDate($l['Timestamp'], 'M d, Y h:i A') ?></td>
                            <td class="fw-bold text-dark"><?= e($l['User']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($l['Role']) ?></span></td>
                            <td><span class="badge bg-light text-primary border"><?= e($l['Action']) ?></span></td>
                            <td class="small fw-semibold"><?= e($l['Module']) ?></td>
                            <td class="small">
                                <?php if (!empty($l['TableAffected'])): ?>
                                    <code><?= e($l['TableAffected']) ?></code>
                                    <?php if ($l['RecordID']): ?>
                                        <span class="text-muted">(#<?= $l['RecordID'] ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e($l['Details'] ?? '-') ?></td>
                            <td class="small text-muted"><code><?= e($l['IPAddress'] ?? '127.0.0.1') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($total) ?> logs)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&module=<?= urlencode($moduleFilter) ?>&role=<?= urlencode($roleFilter) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&module=<?= urlencode($moduleFilter) ?>&role=<?= urlencode($roleFilter) ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
