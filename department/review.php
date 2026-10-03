<?php
// department/review.php - Department Admissions Review Queue & Approval
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Admissions Review & Approval';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 2;

// Process POST approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $admissionId = (int)($_POST['admission_id'] ?? 0);
    $action = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $remarks = trim($_POST['remarks'] ?? ($action === 'Approved' ? 'Admissions documents verified and approved.' : 'Admission rejected.'));

    $stmt = $db->prepare("SELECT a.*, s.StudentNo, s.FirstName, s.LastName FROM admission a JOIN student s ON s.StudentID = a.StudentID WHERE a.AdmissionID = ?");
    $stmt->execute([$admissionId]);
    $adm = $stmt->fetch();

    if ($adm) {
        $stmtUpd = $db->prepare("UPDATE admission SET Status = ?, Remarks = ?, ApprovalDate = CURDATE(), StaffID = ? WHERE AdmissionID = ?");
        $stmtUpd->execute([$action, $remarks, $staffId, $admissionId]);

        // If approved, update student status to Enrolled
        if ($action === 'Approved') {
            $db->prepare("UPDATE student SET Status = 'Enrolled' WHERE StudentID = ?")->execute([$adm['StudentID']]);
        }

        logActivity('ADMISSION_' . strtoupper($action), 'Department', 'admission', $admissionId, "Admission {$action} for student {$adm['StudentNo']} ({$adm['FirstName']} {$adm['LastName']})");
        setFlash('success', "Admission for {$adm['FirstName']} {$adm['LastName']} ({$adm['StudentNo']}) marked as {$action}.");
    }
    header("Location: " . BASE_URL . "/department/review.php");
    exit;
}

// Fetch pending admissions
$filterStatus = $_GET['status'] ?? 'Pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$stmtCount = $db->prepare("SELECT COUNT(*) FROM admission WHERE Status = ?");
$stmtCount->execute([$filterStatus]);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmtList = $db->prepare("SELECT a.*, s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, s.Email, s.ContactNo, p.ProgramName, st.TypeName, sp.PreviousSchoolName, sp.GWA 
    FROM admission a 
    JOIN student s ON s.StudentID = a.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN student_profile sp ON sp.StudentID = s.StudentID 
    WHERE a.Status = ? 
    ORDER BY a.AdmissionID DESC 
    LIMIT {$limit} OFFSET {$offset}");
$stmtList->execute([$filterStatus]);
$admissions = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Admissions Review Queue</h4>
        <p class="text-muted small mb-0">Evaluate student applications, PSA documentation, and approve for Registrar study loading.</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="?status=Pending" class="btn <?= $filterStatus === 'Pending' ? 'btn-warning' : 'btn-outline-secondary' ?>">
            <i class="fas fa-clock me-1"></i> Pending Queue
        </a>
        <a href="?status=Approved" class="btn <?= $filterStatus === 'Approved' ? 'btn-success' : 'btn-outline-secondary' ?>">
            <i class="fas fa-check-circle me-1"></i> Approved (<?= number_format($db->query("SELECT COUNT(*) FROM admission WHERE Status='Approved'")->fetchColumn()) ?>)
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th>Classification</th>
                    <th>Previous School & GWA</th>
                    <th>Submission Date</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admissions)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No applications found in <?= e($filterStatus) ?> status.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admissions as $adm): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($adm['StudentNo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($adm['LastName'] . ', ' . $adm['FirstName'] . ' ' . $adm['MiddleName']) ?></div>
                                <small class="text-muted"><?= e($adm['Email']) ?></small>
                            </td>
                            <td><?= e($adm['ProgramName']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($adm['TypeName']) ?></span></td>
                            <td class="small">
                                <div><?= e($adm['PreviousSchoolName'] ?? 'N/A') ?></div>
                                <span class="badge bg-secondary-subtle text-secondary">GWA: <?= $adm['GWA'] ? number_format((float)$adm['GWA'], 2) : '-' ?></span>
                            </td>
                            <td class="small text-muted"><?= formatDate($adm['SubmissionDate']) ?></td>
                            <td>
                                <span class="badge <?= $adm['Status'] === 'Approved' ? 'bg-success' : ($adm['Status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                    <?= e($adm['Status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($adm['Status'] === 'Pending'): ?>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $adm['AdmissionID'] ?>">
                                        <i class="fas fa-clipboard-check me-1"></i> Review
                                    </button>

                                    <!-- Review Modal -->
                                    <div class="modal fade" id="reviewModal<?= $adm['AdmissionID'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog text-start">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="admission_id" value="<?= $adm['AdmissionID'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Admission Review: <?= e($adm['StudentNo']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="p-3 bg-light rounded mb-3">
                                                            <div class="fw-bold text-dark fs-6"><?= e($adm['FirstName'] . ' ' . $adm['LastName']) ?></div>
                                                            <div class="small text-muted"><?= e($adm['ProgramName']) ?> &bull; <?= e($adm['TypeName']) ?></div>
                                                            <div class="small text-muted mt-1">Previous: <?= e($adm['PreviousSchoolName'] ?? 'N/A') ?> (GWA: <?= $adm['GWA'] ?>)</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Review Remarks</label>
                                                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter evaluation comments or approval notes...">Credentials and PSA document verified complete. Recommended for enrollment.</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer d-flex justify-content-between">
                                                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to reject this applicant?');">
                                                            <i class="fas fa-times me-1"></i> Reject
                                                        </button>
                                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check me-1"></i> Approve Admission
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fas fa-check text-success me-1"></i><?= e($adm['Remarks'] ?? 'Approved') ?></span>
                                <?php endif; ?>
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
                    <a class="page-link" href="?status=<?= urlencode($filterStatus) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?status=<?= urlencode($filterStatus) ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
