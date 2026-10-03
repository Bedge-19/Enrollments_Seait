<?php
// department/index.php - Department Dashboard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Department Dashboard';
$db = getDBConnection();
$deptId = $_SESSION['department_id'] ?? 1;

// Metrics
$stmtPendAdm = $db->query("SELECT COUNT(*) FROM admission WHERE Status = 'Pending'");
$pendingAdmissions = (int)$stmtPendAdm->fetchColumn();

$stmtPendTrf = $db->query("SELECT COUNT(*) FROM credited_subject WHERE Status = 'Pending'");
$pendingTransferees = (int)$stmtPendTrf->fetchColumn();

$stmtTotalStudents = $db->query("SELECT COUNT(*) FROM student");
$totalStudents = (int)$stmtTotalStudents->fetchColumn();

$stmtNewApplicants = $db->query("SELECT COUNT(*) FROM student WHERE StudentTypeID = 1");
$newApplicants = (int)$stmtNewApplicants->fetchColumn();

// Recent Admissions Queue
$stmtRecent = $db->query("SELECT a.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, st.TypeName 
    FROM admission a 
    JOIN student s ON s.StudentID = a.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE a.Status = 'Pending' 
    ORDER BY a.AdmissionID DESC 
    LIMIT 10");
$recentAdmissions = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Department Office Overview</h4>
        <p class="text-muted small mb-0">Initial student evaluation, admission review, transferee crediting, and program management.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/department/review.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-clipboard-check me-1"></i> Review Queue
        </a>
        <a href="<?= BASE_URL ?>/department/register_student.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Register Student
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Pending Admissions</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($pendingAdmissions) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/department/review.php" class="text-decoration-none fw-semibold">Review queue &rarr;</a></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Transferee Credits</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($pendingTransferees) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/department/evaluate_transferee.php" class="text-decoration-none fw-semibold">Evaluate subjects &rarr;</a></small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-exchange-alt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">New Applicants</span>
                    <h3 class="fw-bold my-1 text-primary"><?= number_format($newApplicants) ?></h3>
                    <small class="text-muted">Awaiting enrollment</small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Master Records</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalStudents) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/department/students.php" class="text-decoration-none fw-semibold">Directory &rarr;</a></small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-database"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Admissions Queue Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-list-alt me-2 text-warning"></i>Admissions Pending Review</h6>
            <small class="text-muted">Students waiting for Step 1 Department endorsement</small>
        </div>
        <a href="<?= BASE_URL ?>/department/review.php" class="btn btn-outline-primary btn-sm">View Full Review Queue</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th>Classification</th>
                    <th>Submission Date</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentAdmissions)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                            All admissions have been reviewed and approved.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentAdmissions as $adm): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($adm['StudentNo']) ?></td>
                            <td class="fw-semibold text-dark"><?= e($adm['LastName'] . ', ' . $adm['FirstName'] . ' ' . $adm['MiddleName']) ?></td>
                            <td><?= e($adm['ProgramName']) ?></td>
                            <td><span class="badge badge-soft-dark"><?= e($adm['TypeName']) ?></span></td>
                            <td class="small text-muted"><?= formatDate($adm['SubmissionDate']) ?></td>
                            <td><span class="badge badge-soft-warning">Pending Review</span></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/department/review.php?admission_id=<?= $adm['AdmissionID'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i> Review
                                </a>
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
