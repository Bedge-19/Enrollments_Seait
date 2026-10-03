<?php
// department/evaluate_transferee.php - Transferee Course Crediting & Evaluation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Transferee Subject Evaluation';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 2;

// Handle Credit / Reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $creditId = (int)($_POST['credit_id'] ?? 0);
    $isCredited = ($_POST['action'] === 'credit') ? 1 : 0;
    $status = $isCredited ? 'Approved' : 'Rejected';
    $equivUnits = (float)($_POST['equivalent_units'] ?? 3.0);
    $equivSubjectId = (int)($_POST['subject_id'] ?? 1);
    $reason = trim($_POST['reason'] ?? ($isCredited ? 'Credited by Department evaluation' : 'Subject syllabus does not match minimum requirements'));

    $stmtUpd = $db->prepare("UPDATE credited_subject SET 
        SubjectID = ?, EquivalentUnits = ?, IsCredited = ?, Status = ?, 
        ReasonIfNotCredited = ?, Remarks = ?, EvaluatedBy = ?, EvaluationDate = CURDATE() 
        WHERE CreditID = ?");
    $stmtUpd->execute([$equivSubjectId, $equivUnits, $isCredited, $status, ($isCredited ? NULL : $reason), $reason, $staffId, $creditId]);

    logActivity('EVALUATE_TRANSFEREE_CREDIT', 'Department', 'credited_subject', $creditId, "Transferee subject credit status updated to {$status}");
    setFlash('success', "Course evaluation updated successfully ({$status}).");
    header("Location: " . BASE_URL . "/department/evaluate_transferee.php");
    exit;
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? 'All';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["cs.SourceType = 'Transferee'"];
$params = [];

if ($search !== '') {
    $where[] = "(s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? OR cs.OriginalSubjectName LIKE ? OR cs.PreviousInstitution LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}

if ($filterStatus === 'Credited') {
    $where[] = "cs.IsCredited = 1";
} elseif ($filterStatus === 'Uncredited') {
    $where[] = "cs.IsCredited = 0";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM credited_subject cs JOIN student s ON s.StudentID = cs.StudentID {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmtCredits = $db->prepare("SELECT cs.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, sub.SubjectCode, sub.SubjectTitle 
    FROM credited_subject cs 
    JOIN student s ON s.StudentID = cs.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN subject sub ON sub.SubjectID = cs.SubjectID 
    {$whereSql} 
    ORDER BY cs.CreditID DESC 
    LIMIT {$limit} OFFSET {$offset}");
$stmtCredits->execute($params);
$credits = $stmtCredits->fetchAll();

$allSubjects = $db->query("SELECT SubjectID, SubjectCode, SubjectTitle, Units FROM subject ORDER BY SubjectCode")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Transferee Subject Crediting</h4>
        <p class="text-muted small mb-0">Evaluate external subjects from previous universities, assign equivalent subjects, and credit units.</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="?status=All" class="btn <?= $filterStatus === 'All' ? 'btn-primary' : 'btn-outline-secondary' ?>">All Records (<?= number_format($total) ?>)</a>
        <a href="?status=Credited" class="btn <?= $filterStatus === 'Credited' ? 'btn-success' : 'btn-outline-secondary' ?>">Credited</a>
        <a href="?status=Uncredited" class="btn <?= $filterStatus === 'Uncredited' ? 'btn-danger' : 'btn-outline-secondary' ?>">Rejected</a>
    </div>
</div>

<!-- Search Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Student No, Student Name, Previous School, or Course Name..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search me-1"></i> Search</button>
                <a href="<?= BASE_URL ?>/department/evaluate_transferee.php" class="btn btn-light btn-sm border"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Transferee Credits Table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Previous School</th>
                    <th>Original Course & Grade</th>
                    <th>University Equivalent</th>
                    <th class="text-center">Units</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($credits)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No transferee credit evaluation records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($credits as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary"><?= e($c['StudentNo']) ?></div>
                                <small class="text-dark fw-semibold"><?= e($c['LastName'] . ', ' . $c['FirstName']) ?></small>
                                <div class="text-muted small"><?= e($c['ProgramName']) ?></div>
                            </td>
                            <td class="small"><?= e($c['PreviousInstitution'] ?? 'External University') ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($c['OriginalSubjectName']) ?></div>
                                <span class="badge bg-secondary-subtle text-secondary">Grade: <?= e($c['OriginalGrade']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= e($c['SubjectCode']) ?></div>
                                <small class="text-muted"><?= e($c['SubjectTitle']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= number_format((float)$c['EquivalentUnits'], 1) ?></td>
                            <td>
                                <?php if ($c['IsCredited'] == 1): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Credited</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Not Credited</span>
                                    <small class="text-muted d-block"><?= e($c['ReasonIfNotCredited'] ?? '-') ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCreditModal<?= $c['CreditID'] ?>">
                                    <i class="fas fa-edit me-1"></i> Evaluate
                                </button>

                                <!-- Edit Credit Modal -->
                                <div class="modal fade" id="editCreditModal<?= $c['CreditID'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog text-start">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <input type="hidden" name="credit_id" value="<?= $c['CreditID'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Transferee Course Crediting</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="p-3 bg-light rounded mb-3">
                                                        <div class="small text-muted">Original Course:</div>
                                                        <div class="fw-bold text-dark fs-6"><?= e($c['OriginalSubjectName']) ?> (Grade: <?= e($c['OriginalGrade']) ?>)</div>
                                                        <div class="small text-muted">School: <?= e($c['PreviousInstitution']) ?></div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Equivalent Institutional Subject</label>
                                                        <select name="subject_id" class="form-select form-select-sm" required>
                                                            <?php foreach ($allSubjects as $sub): ?>
                                                                <option value="<?= $sub['SubjectID'] ?>" <?= $sub['SubjectID'] == $c['SubjectID'] ? 'selected' : '' ?>>
                                                                    <?= e($sub['SubjectCode'] . ' - ' . $sub['SubjectTitle'] . ' (' . $sub['Units'] . ' units)') ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Equivalent Units</label>
                                                        <input type="number" step="0.5" name="equivalent_units" class="form-control form-control-sm" value="<?= $c['EquivalentUnits'] ?? 3.0 ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Reason / Evaluation Justification</label>
                                                        <textarea name="reason" class="form-control form-control-sm" rows="2"><?= e($c['Remarks'] ?? $c['ReasonIfNotCredited'] ?? 'Syllabus content verified equivalent to institutional course standards.') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer d-flex justify-content-between">
                                                    <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-times me-1"></i> Disapprove Credit
                                                    </button>
                                                    <button type="submit" name="action" value="credit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i> Approve & Credit Subject
                                                    </button>
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
            <small class="text-muted">Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($total) ?> records)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
