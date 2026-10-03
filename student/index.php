<?php
// student/index.php - Student Confirmation Checklist Dashboard & ID Validation Status
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Student');
$pageTitle = 'Confirmation Checklist & ID Status';
$db = getDBConnection();
$studentId = (int)$_SESSION['student_id'];

// Get Student details
$stmt = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE s.StudentID = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// Ensure active enrollment record exists for this term
$stmtChk = $db->prepare("SELECT * FROM vw_enrollment_checklist WHERE StudentID = ? ORDER BY EnrollmentID DESC LIMIT 1");
$stmtChk->execute([$studentId]);
$checklist = $stmtChk->fetch();

if (!$checklist) {
    // Auto-create active enrollment record if not yet created
    $db->prepare("INSERT INTO `enrollment` (`EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`) VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, 2)")
       ->execute([$studentId]);
    
    // Re-fetch live checklist
    $stmtChk->execute([$studentId]);
    $checklist = $stmtChk->fetch();
}

// Fallback check for admission status if checklist DepartmentStatus is pending
$stmtAdm = $db->prepare("SELECT Status FROM admission WHERE StudentID = ? ORDER BY AdmissionID DESC LIMIT 1");
$stmtAdm->execute([$studentId]);
$admissionStatus = $stmtAdm->fetchColumn();

$deptStatus = $checklist['DepartmentStatus'] ?? ($admissionStatus ?: 'Pending');

// Fetch latest payment
$stmtPay = $db->prepare("SELECT * FROM payment WHERE StudentID = ? ORDER BY PaymentID DESC LIMIT 1");
$stmtPay->execute([$studentId]);
$payment = $stmtPay->fetch();

// Fetch latest clinic record
$stmtCln = $db->prepare("SELECT * FROM clinic WHERE StudentID = ? ORDER BY ClinicID DESC LIMIT 1");
$stmtCln->execute([$studentId]);
$clinic = $stmtCln->fetch();

// Fetch ID validation record
$stmtIdVal = $db->prepare("SELECT iv.*, stf.FirstName as StaffFirst, stf.LastName as StaffLast 
    FROM id_validation iv 
    LEFT JOIN staff stf ON stf.StaffID = iv.StaffID 
    WHERE iv.StudentID = ? 
    ORDER BY iv.ValidationID DESC LIMIT 1");
$stmtIdVal->execute([$studentId]);
$idValidation = $stmtIdVal->fetch();

// Fetch Security verification record
$stmtSec = $db->prepare("SELECT sv.*, stf.FirstName as StaffFirst, stf.LastName as StaffLast 
    FROM security_verification sv 
    LEFT JOIN staff stf ON stf.StaffID = sv.VerifiedBy 
    WHERE sv.StudentID = ? 
    ORDER BY sv.SecurityVerificationID DESC LIMIT 1");
$stmtSec->execute([$studentId]);
$secVerification = $stmtSec->fetch();

// Count enrolled subjects and units
$stmtUnits = $db->prepare("SELECT COUNT(es.EnrollmentSubjectID) as total_subjects, COALESCE(SUM(sub.Units), 0) as total_units 
    FROM enrollment e 
    JOIN enrollment_subject es ON es.EnrollmentID = e.EnrollmentID 
    JOIN subject sub ON sub.SubjectID = es.SubjectID 
    WHERE e.StudentID = ? AND e.SchoolYear = '2026-2027' AND e.Semester = '1st'");
$stmtUnits->execute([$studentId]);
$unitsSummary = $stmtUnits->fetch();

// Student type classification
$typeName = $student['TypeName'] ?? '';
$isNewOrTransferee = in_array(strtolower($typeName), ['new', 'transferee']);
$isConfirmed = ($checklist['OverallStatus'] ?? '') === 'CONFIRMED';
$isSecurityDone = ($checklist['SecurityStatus'] ?? '') === 'Completed';

// Calculate progress percentage
$stepsCompleted = 0;
if ($deptStatus === 'Approved' || $deptStatus === 'Completed') $stepsCompleted++;
if (($checklist['RegistrarStatus'] ?? '') === 'Completed') $stepsCompleted++;
if (($checklist['AccountingStatus'] ?? '') === 'Completed') $stepsCompleted++;
if (($checklist['ClinicStatus'] ?? '') === 'Completed') $stepsCompleted++;
if ($isSecurityDone) $stepsCompleted++;
$progressPct = ($stepsCompleted / 5) * 100;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Student Header Card -->
<div class="dashboard-header">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle <?= $isNewOrTransferee ? 'bg-primary' : 'bg-dark' ?> text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 56px; height: 56px; font-size: 1.3rem;">
            <?= strtoupper(substr($student['FirstName'] ?? 'S', 0, 1) . substr($student['LastName'] ?? 'T', 0, 1)) ?>
        </div>
        <div>
            <h4 class="mb-1 fw-bold text-dark"><?= e($student['FirstName'] . ' ' . ($student['MiddleName'] ? $student['MiddleName'] . ' ' : '') . $student['LastName']) ?></h4>
            <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold text-primary"><?= e($student['StudentNo']) ?></span>
                <span>&bull;</span>
                <span><?= e($student['ProgramName']) ?></span>
                <span>&bull;</span>
                <span class="badge <?= $isNewOrTransferee ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= e($student['TypeName']) ?></span>
                <?php if ($isConfirmed): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="fas fa-id-card me-1"></i> <?= $isNewOrTransferee ? 'ID Card Generated' : 'ID Validated (2026-2027)' ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div>
        <?php if ($isConfirmed): ?>
            <span class="badge bg-success px-3 py-2 fs-6 shadow-sm"><i class="fas fa-check-circle me-1"></i> ENROLLMENT CONFIRMED & ID VALIDATED</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm"><i class="fas fa-clock me-1"></i> <?= $stepsCompleted ?> OF 5 OFFICES COMPLETED</span>
        <?php endif; ?>
    </div>
</div>

<!-- Progress Bar -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-dark small"><i class="fas fa-tasks text-primary me-2"></i>5-Office Clearance Pipeline Completion</span>
            <span class="fw-bold text-primary small"><?= $progressPct ?>% Completed</span>
        </div>
        <div class="progress" style="height: 10px; border-radius: 6px;">
            <div class="progress-bar progress-bar-striped <?= $progressPct == 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $progressPct ?>%;" aria-valuenow="<?= $progressPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<!-- Checklist Steps Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-clipboard-check me-2"></i>Official 5-Office Clearance Sequence</h5>
                <span class="badge bg-light text-dark border">Term: A.Y. 2026-2027 (1st Sem)</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    Students must complete clearance across all 5 designated offices in strict sequential order. At Step 5, the Security Office validates your student ID (or generates a new ID for new/transferee students) and confirms official enrollment.
                </p>

                <div class="checklist-timeline">
                    <?php
                    $steps = [
                        [
                            'step' => 1,
                            'title' => 'Department Office',
                            'desc' => 'Program admission, document review, and academic evaluation.',
                            'status' => $deptStatus,
                            'icon' => 'fa-building',
                            'is_completed' => ($deptStatus === 'Approved' || $deptStatus === 'Completed'),
                            'badge_text' => ($deptStatus === 'Approved' || $deptStatus === 'Completed') ? '✓ Approved' : '⏳ Pending'
                        ],
                        [
                            'step' => 2,
                            'title' => 'Registrar Office',
                            'desc' => 'Curriculum evaluation, subject loading, blocking, and schedule assignment.',
                            'status' => $checklist['RegistrarStatus'] ?? 'Pending',
                            'icon' => 'fa-clipboard-list',
                            'is_completed' => ($checklist['RegistrarStatus'] ?? '') === 'Completed',
                            'badge_text' => (($checklist['RegistrarStatus'] ?? '') === 'Completed') ? '✓ Completed' : '⏳ Pending'
                        ],
                        [
                            'step' => 3,
                            'title' => 'Accounting Office',
                            'desc' => 'Assessment of tuition & miscellaneous fees, payment processing, and receipt issuance.',
                            'status' => $checklist['AccountingStatus'] ?? 'Pending',
                            'icon' => 'fa-cash-register',
                            'is_completed' => ($checklist['AccountingStatus'] ?? '') === 'Completed',
                            'badge_text' => (($checklist['AccountingStatus'] ?? '') === 'Completed') ? '✓ Paid & Cleared' : '⏳ Pending Payment'
                        ],
                        [
                            'step' => 4,
                            'title' => 'Clinic / Health Services',
                            'desc' => 'Medical history, height, weight, BMI recording, and physical clearance.',
                            'status' => $checklist['ClinicStatus'] ?? 'Pending',
                            'icon' => 'fa-heartbeat',
                            'is_completed' => ($checklist['ClinicStatus'] ?? '') === 'Completed',
                            'badge_text' => (($checklist['ClinicStatus'] ?? '') === 'Completed') ? '✓ Medically Cleared' : '⏳ Pending Exam'
                        ],
                        [
                            'step' => 5,
                            'title' => $isNewOrTransferee 
                                ? 'Security Office (ID Generation & Final Confirmation)' 
                                : 'Security Office (ID Validation & Final Confirmation)',
                            'desc' => $isNewOrTransferee 
                                ? 'Audit 4 clearances, official Student ID card generation, and final enrollment confirmation.' 
                                : 'Audit 4 clearances, physical Student ID validation with semester sticker renewal, and final confirmation.',
                            'status' => $checklist['SecurityStatus'] ?? 'Pending',
                            'icon' => 'fa-shield-alt',
                            'is_completed' => $isSecurityDone,
                            'badge_text' => $isSecurityDone 
                                ? ($isNewOrTransferee ? '✓ ID Generated & Confirmed' : '✓ ID Validated & Confirmed')
                                : ($isNewOrTransferee ? '⏳ Awaiting ID Generation' : '⏳ Awaiting ID Validation')
                        ]
                    ];

                    foreach ($steps as $s):
                        $cardClass = $s['is_completed'] ? 'completed' : 'pending';
                        $badgeClass = $s['is_completed'] ? 'badge-soft-success' : 'badge-soft-warning';
                    ?>
                        <div class="checklist-step-card <?= $cardClass ?> shadow-sm">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon <?= $s['is_completed'] ? 'bg-success text-white' : 'bg-light text-muted border' ?>">
                                    <i class="fas <?= $s['icon'] ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark">Step <?= $s['step'] ?>: <?= e($s['title']) ?></h6>
                                    <small class="text-muted"><?= e($s['desc']) ?></small>
                                </div>
                            </div>
                            <div class="text-end ps-3">
                                <span class="badge <?= $badgeClass ?> px-3 py-2 fw-semibold">
                                    <i class="fas <?= $s['is_completed'] ? 'fa-check' : 'fa-hourglass-half' ?> me-1"></i>
                                    <?= e($s['badge_text']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- END OF CLEARANCE SEQUENCE: Official ID Validation Status Card -->
                <div class="card border <?= $isConfirmed ? 'border-success bg-success-subtle' : 'border-warning bg-light' ?> mt-4 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-3 rounded-circle <?= $isConfirmed ? 'bg-success text-white' : 'bg-warning text-dark' ?> shadow-sm">
                                    <i class="fas <?= $isConfirmed ? 'fa-id-badge fa-2x' : 'fa-shield-alt fa-2x' ?>"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold mb-0 text-dark">
                                            <?= $isConfirmed 
                                                ? ($isNewOrTransferee ? 'Student ID Card Generated & Validated' : 'Student ID Card Validated & Renewed')
                                                : 'Student ID Validation Pending' ?>
                                        </h5>
                                        <span class="badge <?= $isConfirmed ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= $isConfirmed ? '✓ VALIDATED' : '⏳ PENDING' ?>
                                        </span>
                                    </div>
                                    <p class="small text-muted mb-0">
                                        <?php if ($isConfirmed): ?>
                                            <?= $isNewOrTransferee 
                                                ? "Your official campus ID card has been generated by the Security Office for A.Y. 2026-2027 (1st Semester)." 
                                                : "Your student ID has been validated by the Security Office with active registration for A.Y. 2026-2027 1st Semester." ?>
                                            <?php if (!empty($idValidation['ValidationDate'])): ?>
                                                <span class="d-block mt-1 text-success fw-semibold">
                                                    <i class="fas fa-check-circle me-1"></i> Validated on <?= formatDate($idValidation['ValidationDate']) ?>
                                                    <?php if (!empty($idValidation['StaffFirst'])): ?>
                                                        by Security Officer <?= e($idValidation['StaffFirst'] . ' ' . $idValidation['StaffLast']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Please present yourself to the Security Office after completing Department, Registrar, Accounting, and Clinic steps to finalize your ID validation and enrollment confirmation.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($isConfirmed): ?>
                                    <?php if ($isNewOrTransferee): ?>
                                        <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-primary btn-sm shadow-sm">
                                            <i class="fas fa-id-card me-1"></i> View / Print Student ID
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/security/print_validation_sticker.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-success btn-sm shadow-sm">
                                            <i class="fas fa-stamp me-1"></i> Print Validation Sticker
                                        </a>
                                        <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-outline-primary btn-sm shadow-sm">
                                            <i class="fas fa-id-card me-1"></i> View Digital ID
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/student/print_confirmation.php" target="_blank" class="btn btn-outline-success btn-sm shadow-sm">
                                        <i class="fas fa-print me-1"></i> Confirmation Slip
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/student/id_card.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-info-circle me-1"></i> ID Requirements Info
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Quick Overview Stats / Actions (Right Column) -->
    <div class="col-lg-4">
        <!-- Student ID Card Mini Widget -->
        <div class="card mb-4 shadow-sm border <?= $isConfirmed ? 'border-primary' : 'border-light' ?>">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-id-card text-primary me-2"></i>Campus ID Status</h6>
                <span class="badge <?= $isConfirmed ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= $isConfirmed ? 'VALIDATED' : 'PENDING' ?>
                </span>
            </div>
            <div class="card-body text-center p-3 bg-light">
                <div class="p-3 bg-white rounded border shadow-xs mb-3 text-start">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-muted">ID NUMBER</span>
                        <span class="fw-bold text-primary font-monospace"><?= e($student['StudentNo']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-muted">CARD TYPE</span>
                        <span class="badge <?= $isNewOrTransferee ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= e($student['TypeName']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-muted">VALIDITY</span>
                        <span class="small fw-semibold text-dark">A.Y. 2026-2027 (1st Sem)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted">SECURITY STAMP</span>
                        <span class="badge <?= $isConfirmed ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                            <?= $isConfirmed ? '✓ Validated' : '⏳ Unvalidated' ?>
                        </span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/student/id_card.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-id-card me-1"></i> Open Student ID Portal
                    </a>
                </div>
            </div>
        </div>

        <!-- Academic Summary Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>Enrollment Summary</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Enrolled Subjects:</span>
                        <span class="fw-bold"><?= (int)$unitsSummary['total_subjects'] ?> Subjects</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Total Units:</span>
                        <span class="fw-bold text-primary"><?= number_format((float)$unitsSummary['total_units'], 1) ?> Units</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Accounting Status:</span>
                        <span class="badge <?= ($payment && $payment['PaymentStatus'] === 'Paid') ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                            <?= e($payment['PaymentStatus'] ?? 'Unassessed') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Medical Clearance:</span>
                        <span class="badge <?= ($clinic && $clinic['ClearanceStatus'] === 'Cleared') ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                            <?= e($clinic['ClearanceStatus'] ?? 'Pending') ?>
                        </span>
                    </li>
                </ul>

                <div class="d-grid gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/student/study_load.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-book-reader me-1"></i> View Detailed Study Load
                    </a>
                    <a href="<?= BASE_URL ?>/student/print_study_load.php" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i> Print Study Load (COR)
                    </a>
                </div>
            </div>
        </div>

        <!-- Assistance Card -->
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-question-circle text-info me-2"></i>Clearance Guide</h6>
                <p class="text-muted small mb-0">
                    If any step is shown as pending, please visit the designated campus office with your student documents to complete physical validation.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
