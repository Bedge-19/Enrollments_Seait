<?php
// admin/users.php - User Accounts Management
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Admin');
$pageTitle = 'User Accounts Management';
$db = getDBConnection();

// Toggle Status (Activate / Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();

    if ($u) {
        $newStatus = ($u['status'] === 'Active') ? 'Inactive' : 'Active';
        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
        $db->prepare("UPDATE login SET Status = ? WHERE Username = ?")->execute([$newStatus, $u['username']]);

        logActivity('USER_STATUS_CHANGE', 'Admin', 'users', $userId, "Changed status of {$u['username']} to {$newStatus}");
        setFlash('success', "User {$u['username']} is now {$newStatus}.");
    }
    header("Location: " . BASE_URL . "/admin/users.php");
    exit;
}

// Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = (int)$_POST['user_id'];
    $newPass = trim($_POST['new_password'] ?? 'password123');
    $hashed = password_hash($newPass, PASSWORD_DEFAULT);

    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $username = $stmt->fetchColumn();

    if ($username) {
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hashed, $userId]);
        $db->prepare("UPDATE login SET PasswordHash = ? WHERE Username = ?")->execute([$hashed, $username]);

        logActivity('RESET_PASSWORD', 'Admin', 'users', $userId, "Reset password for user {$username}");
        setFlash('success', "Password for {$username} has been reset to: {$newPass}");
    }
    header("Location: " . BASE_URL . "/admin/users.php");
    exit;
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.username LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
}
if ($roleFilter !== '') {
    $where[] = "u.role = ?";
    $params[] = $roleFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmtCount = $db->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmtUsers = $db->prepare("SELECT u.*, 
    COALESCE(CONCAT(s.FirstName, ' ', s.LastName), CONCAT(stf.FirstName, ' ', stf.LastName)) as FullName 
    FROM users u 
    LEFT JOIN student s ON s.StudentID = u.student_id 
    LEFT JOIN staff stf ON stf.StaffID = u.staff_id 
    {$whereSql} 
    ORDER BY u.id DESC 
    LIMIT {$limit} OFFSET {$offset}");
$stmtUsers->execute($params);
$usersList = $stmtUsers->fetchAll();

$roles = ['Admin', 'Department', 'Registrar', 'Accounting', 'Clinic', 'Security Office', 'Student'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">User Accounts Directory</h4>
        <p class="text-muted small mb-0">Manage system user roles, access statuses, and password credentials.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/create_user.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Add Staff Account
        </a>
    </div>
</div>

<!-- Search Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-7">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by username..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-12 d-flex gap-1 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-search me-1"></i> Search</button>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Linked Account / Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($u['username']) ?></td>
                            <td><?= e($u['FullName'] ?: 'System / Root') ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= e($u['role']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= e($u['status']) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Toggle Status Button -->
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn <?= $u['status'] === 'Active' ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm" title="<?= $u['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>" onclick="return confirm('Change status of user <?= $u['username'] ?>?');">
                                            <i class="fas <?= $u['status'] === 'Active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Reset Password Modal Trigger -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#resetModal<?= $u['id'] ?>" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>

                                <!-- Reset Password Modal -->
                                <div class="modal fade" id="resetModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog text-start">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <input type="hidden" name="reset_password" value="1">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Reset Password: <?= e($u['username']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label small fw-semibold">New Password</label>
                                                    <input type="text" name="new_password" class="form-control" value="password123" required>
                                                    <small class="text-muted">Enter new password for this user account.</small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Save Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($total) ?> accounts)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?role=<?= urlencode($roleFilter) ?>&search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?role=<?= urlencode($roleFilter) ?>&search=<?= urlencode($search) ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
