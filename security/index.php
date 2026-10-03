<?php
// security/index.php - Security Office Dashboard & Verification Queue
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Security Office');
$pageTitle = 'Security Office Verification & ID Management';
$db = getDBConnection();

// Metrics
$stmtConfirmed = $db->query("SELECT COUNT(*) FROM vw_enrollment_checklist WHERE OverallStatus = 'CONFIRMED'");
$totalConfirmed = (int)$stmtConfirmed->fetchColumn();

$stmtPending = $db->query("SELECT COUNT(*) FROM vw_enrollment_checklist WHERE OverallStatus = 'IN PROGRESS' AND DepartmentStatus = 'Approved' AND RegistrarStatus = 'Completed' AND AccountingStatus = 'Completed' AND ClinicStatus = 'Completed'");
$readyForVerification = (int)$stmtPending->fetchColumn();

// ID Specific Metrics
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

// Queue of students who have completed prior 4 steps and are waiting for Security verification
$stmtQueue = $db->query("SELECT chk.*, s.Sex, p.ProgramName, st.TypeName 
    FROM vw_enrollment_checklist chk 
    JOIN student s ON s.StudentID = chk.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE chk.DepartmentStatus = 'Approved' 
      AND chk.RegistrarStatus = 'Completed' 
      AND chk.AccountingStatus = 'Completed' 
      AND chk.ClinicStatus = 'Completed' 
      AND chk.SecurityStatus != 'Completed'
    LIMIT 25");
$readyQueue = $stmtQueue->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Security Office & ID Validation Hub</h4>
        <p class="text-muted small mb-0">Final gatekeeping validation across all office clearances, official enrollment confirmation, ID generation, and ID renewal.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/security/id_cards.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-id-card me-1"></i> ID Validation Center
        </a>
        <a href="<?= BASE_URL ?>/security/verify.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-search me-1"></i> Look up Student
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Confirmed Enrolled</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalConfirmed) ?></h3>
                    <small class="text-muted">Cleared & confirmed students</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-shield-alt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Ready for Confirmation</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($readyForVerification) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/security/verify.php" class="text-decoration-none fw-semibold">Verify queue &rarr;</a></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">New IDs Generated</span>
                    <h3 class="fw-bold my-1 text-primary"><?= number_format($totalGenerated) ?></h3>
                    <small class="text-muted">New & Transferees</small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-id-card"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">IDs Validated</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($totalValidated) ?></h3>
                    <small class="text-muted">Regular, Irreg, Returnee, Shifter</small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-stamp"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Ready for Security Confirmation & ID Action Queue Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-check-circle me-2 text-success"></i>Students Ready for Final Confirmation & ID Processing (Step 5)</h6>
            <small class="text-muted">All 4 prior department clearances are complete. Confirm enrollment to generate new ID or validate existing ID.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/security/id_cards.php" class="btn btn-outline-secondary btn-sm">View All ID Records</a>
            <a href="<?= BASE_URL ?>/security/verify.php" class="btn btn-primary btn-sm">Search by Student ID</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Student Type</th>
                    <th>Program</th>
                    <th class="text-center">Prerequisite Clearances</th>
                    <th>Required ID Action</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($readyQueue)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-2 d-block"></i>
                            No students currently in queue waiting for final security confirmation.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($readyQueue as $q): 
                        $isNewTrans = in_array(strtolower($q['TypeName']), ['new', 'transferee']);
                    ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($q['StudentNo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($q['StudentName']) ?></div>
                                <small class="text-muted"><?= e($q['Sex']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $isNewTrans ? 'bg-info text-dark' : 'bg-secondary' ?>">
                                    <?= e($q['TypeName']) ?>
                                </span>
                            </td>
                            <td><span class="small text-muted"><?= e($q['ProgramName']) ?></span></td>
                            <td class="text-center">
                                <span class="badge bg-success" title="Department">Dept ✓</span>
                                <span class="badge bg-success" title="Registrar">Reg ✓</span>
                                <span class="badge bg-success" title="Accounting">Acct ✓</span>
                                <span class="badge bg-success" title="Clinic">Clinic ✓</span>
                            </td>
                            <td>
                                <?php if ($isNewTrans): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="fas fa-id-card me-1"></i> Generate New ID Card
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        <i class="fas fa-stamp me-1"></i> Validate Existing ID
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/security/verify.php?student_id=<?= $q['StudentID'] ?>" class="btn <?= $isNewTrans ? 'btn-primary' : 'btn-success' ?> btn-sm shadow-sm">
                                    <i class="fas <?= $isNewTrans ? 'fa-id-card' : 'fa-stamp' ?> me-1"></i>
                                    <?= $isNewTrans ? 'Confirm & Generate ID' : 'Confirm & Validate ID' ?>
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
