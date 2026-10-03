<?php
// security/id_cards.php - Security Office ID Validation & ID Card Generation Center
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Security Office');
$pageTitle = 'Student ID Validation & Card Management';
$db = getDBConnection();

$tab = $_GET['tab'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

// Metrics
$stmtGen = $db->query("SELECT COUNT(DISTINCT s.StudentID) 
    FROM student s 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    JOIN security_verification sv ON sv.StudentID = s.StudentID 
    WHERE st.TypeName IN ('New', 'Transferee') AND sv.Status = 'Verified'");
$totalGenerated = (int)$stmtGen->fetchColumn();

$stmtVal = $db->query("SELECT COUNT(DISTINCT s.StudentID) 
    FROM student s 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    JOIN security_verification sv ON sv.StudentID = s.StudentID 
    WHERE st.TypeName IN ('Regular', 'Irregular', 'Returnee', 'Shifter') AND sv.Status = 'Verified'");
$totalValidated = (int)$stmtVal->fetchColumn();

$stmtPending = $db->query("SELECT COUNT(*) FROM vw_enrollment_checklist 
    WHERE DepartmentStatus = 'Approved' 
      AND RegistrarStatus = 'Completed' 
      AND AccountingStatus = 'Completed' 
      AND ClinicStatus = 'Completed' 
      AND SecurityStatus != 'Completed'");
$readyPending = (int)$stmtPending->fetchColumn();

// Build query
$whereClauses = ["1=1"];
$params = [];

if ($tab === 'new_transferee') {
    $whereClauses[] = "st.TypeName IN ('New', 'Transferee')";
} elseif ($tab === 'continuing') {
    $whereClauses[] = "st.TypeName IN ('Regular', 'Irregular', 'Returnee', 'Shifter')";
}

if ($search !== '') {
    $whereClauses[] = "(s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? OR p.ProgramName LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($statusFilter === 'confirmed') {
    $whereClauses[] = "chk.OverallStatus = 'CONFIRMED'";
} elseif ($statusFilter === 'pending') {
    $whereClauses[] = "chk.OverallStatus != 'CONFIRMED'";
}

$whereSql = implode(" AND ", $whereClauses);

$stmtList = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, s.Sex,
    p.ProgramName, st.TypeName,
    chk.DepartmentStatus, chk.RegistrarStatus, chk.AccountingStatus, chk.ClinicStatus, chk.SecurityStatus, chk.OverallStatus,
    iv.ValidationDate, iv.Status as IdValidationStatus
    FROM student s 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN vw_enrollment_checklist chk ON chk.StudentID = s.StudentID 
    LEFT JOIN id_validation iv ON iv.StudentID = s.StudentID 
    WHERE {$whereSql}
    ORDER BY chk.OverallStatus = 'CONFIRMED' ASC, s.StudentID DESC 
    LIMIT 100");
$stmtList->execute($params);
$students = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Student ID Validation & Generation Center</h4>
        <p class="text-muted small mb-0">Generate official student ID cards for New and Transferee students; validate and renew student ID records for Regular, Irregular, Returnee, and Shifter students.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/security/verify.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-search me-1"></i> Look up Student for Verification
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card stat-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">New IDs Generated</span>
                    <h3 class="fw-bold my-1 text-primary"><?= number_format($totalGenerated) ?></h3>
                    <small class="text-muted">New & Transferee Students</small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-id-card"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">IDs Validated & Renewed</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalValidated) ?></h3>
                    <small class="text-muted">Regular, Irregular, Returnee, Shifters</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-stamp"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Ready for ID Action</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($readyPending) ?></h3>
                    <small class="text-muted">Clearances Complete &bull; Awaiting Security</small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'all' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="?tab=all&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>">
                    <i class="fas fa-users me-1"></i> All Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'new_transferee' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="?tab=new_transferee&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>">
                    <i class="fas fa-id-card me-1"></i> New & Transferees (ID Generation Queue)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'continuing' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="?tab=continuing&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>">
                    <i class="fas fa-stamp me-1"></i> Regular, Irregular, Returnee & Shifter (ID Validation Queue)
                </a>
            </li>
        </ul>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card-body bg-light border-bottom py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by Student No, Name, or Program..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed / Validated</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Security Action</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    <a href="?tab=<?= e($tab) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-redo me-1"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Type</th>
                    <th>Program</th>
                    <th class="text-center">4-Office Clearances</th>
                    <th class="text-center">Security & ID Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-id-card fa-3x text-muted mb-2 d-block"></i>
                            No student records found matching the specified criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): 
                        $isNewTrans = in_array(strtolower($s['TypeName']), ['new', 'transferee']);
                        $isConfirmed = ($s['OverallStatus'] ?? '') === 'CONFIRMED';
                        $allPriorClear = ($s['DepartmentStatus'] === 'Approved' || $s['DepartmentStatus'] === 'Completed')
                            && $s['RegistrarStatus'] === 'Completed'
                            && $s['AccountingStatus'] === 'Completed'
                            && $s['ClinicStatus'] === 'Completed';
                    ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-primary"><?= e($s['StudentNo']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($s['LastName'] . ', ' . $s['FirstName'] . ' ' . $s['MiddleName']) ?></div>
                                <small class="text-muted"><?= e($s['Sex']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $isNewTrans ? 'bg-info text-dark' : 'bg-secondary' ?>">
                                    <?= e($s['TypeName']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= e($s['ProgramName']) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <span class="badge <?= ($s['DepartmentStatus'] === 'Approved' || $s['DepartmentStatus'] === 'Completed') ? 'bg-success' : 'bg-secondary' ?>" title="Department: <?= e($s['DepartmentStatus']) ?>">D</span>
                                    <span class="badge <?= $s['RegistrarStatus'] === 'Completed' ? 'bg-success' : 'bg-secondary' ?>" title="Registrar: <?= e($s['RegistrarStatus']) ?>">R</span>
                                    <span class="badge <?= $s['AccountingStatus'] === 'Completed' ? 'bg-success' : 'bg-secondary' ?>" title="Accounting: <?= e($s['AccountingStatus']) ?>">A</span>
                                    <span class="badge <?= $s['ClinicStatus'] === 'Completed' ? 'bg-success' : 'bg-secondary' ?>" title="Clinic: <?= e($s['ClinicStatus']) ?>">C</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($isConfirmed): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <?= $isNewTrans ? 'ID Generated & Enrolled' : 'ID Validated & Enrolled' ?>
                                    </span>
                                <?php elseif ($allPriorClear): ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2">
                                        <i class="fas fa-clock me-1"></i> Ready for Security
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border py-1 px-2">
                                        ⏳ Clearances Incomplete
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($isConfirmed): ?>
                                        <?php if ($isNewTrans): ?>
                                            <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $s['StudentID'] ?>" target="_blank" class="btn btn-primary btn-sm" title="Print Official Student ID Card">
                                                <i class="fas fa-id-card me-1"></i> Print ID
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/security/print_validation_sticker.php?student_id=<?= $s['StudentID'] ?>" target="_blank" class="btn btn-success btn-sm" title="Print ID Validation Sticker">
                                                <i class="fas fa-certificate me-1"></i> Validation Slip
                                            </a>
                                            <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $s['StudentID'] ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="View Student ID">
                                                <i class="fas fa-id-card"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/security/verify.php?student_id=<?= $s['StudentID'] ?>" class="btn <?= $isNewTrans ? 'btn-primary' : 'btn-success' ?> btn-sm shadow-sm">
                                            <i class="fas <?= $isNewTrans ? 'fa-id-card' : 'fa-stamp' ?> me-1"></i>
                                            <?= $isNewTrans ? 'Confirm & Generate ID' : 'Confirm & Validate ID' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
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
