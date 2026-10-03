<?php
// security/verify.php - Security Office Final Multi-Office Verification & ID Validation Screen
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Security Office');
$pageTitle = 'Final Enrollment Verification & ID Validation';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 9;

$studentId = (int)($_GET['student_id'] ?? 0);
$student = null;
$checklist = null;
$payment = null;
$clinic = null;
$enrollment = null;
$idValidation = null;

// Handle Final Confirmation & ID Validation / Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_final'])) {
    $targetEnrollmentId = (int)$_POST['enrollment_id'];
    $targetStudentId = (int)$_POST['student_id'];
    $studentTypeName = trim($_POST['student_type_name'] ?? '');
    $idAction = trim($_POST['id_action'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    // Fetch checklist to enforce prerequisite rule
    $stmtChk = $db->prepare("SELECT * FROM vw_enrollment_checklist WHERE EnrollmentID = ?");
    $stmtChk->execute([$targetEnrollmentId]);
    $chkData = $stmtChk->fetch();

    $canConfirm = $chkData 
        && ($chkData['DepartmentStatus'] === 'Approved' || $chkData['DepartmentStatus'] === 'Completed')
        && $chkData['RegistrarStatus'] === 'Completed'
        && $chkData['AccountingStatus'] === 'Completed'
        && $chkData['ClinicStatus'] === 'Completed';

    if (!$canConfirm) {
        setFlash('danger', 'Security Validation Error: Cannot confirm enrollment because one or more required office steps are incomplete!');
    } else {
        try {
            $db->beginTransaction();

            $isNewOrTransferee = in_array(strtolower($studentTypeName), ['new', 'transferee']);

            if (empty($remarks)) {
                $remarks = $isNewOrTransferee 
                    ? 'All required office clearances verified. Official Student ID Generated and Enrollment Confirmed.'
                    : 'All required office clearances verified. Student ID Validated for current term and Enrollment Confirmed.';
            }

            // 1. Update Security Verification record
            $stmtUpdSec = $db->prepare("UPDATE security_verification SET 
                Status = 'Verified', VerifiedBy = ?, VerificationDate = CURDATE(), Remarks = ? 
                WHERE EnrollmentID = ?");
            $stmtUpdSec->execute([$staffId, $remarks, $targetEnrollmentId]);

            // 2. Insert or Update ID Validation record
            $stmtCheckIdVal = $db->prepare("SELECT ValidationID FROM id_validation WHERE StudentID = ? LIMIT 1");
            $stmtCheckIdVal->execute([$targetStudentId]);
            $existingValId = $stmtCheckIdVal->fetchColumn();

            if ($existingValId) {
                $stmtVal = $db->prepare("UPDATE id_validation SET ValidationDate = CURDATE(), Status = 'Completed', StaffID = ? WHERE ValidationID = ?");
                $stmtVal->execute([$staffId, $existingValId]);
            } else {
                $stmtVal = $db->prepare("INSERT INTO id_validation (ValidationDate, Status, StudentID, StaffID) VALUES (CURDATE(), 'Completed', ?, ?)");
                $stmtVal->execute([$targetStudentId, $staffId]);
            }

            // 3. Update Enrollment and Student Status to Enrolled
            $db->prepare("UPDATE enrollment SET Status = 'Enrolled' WHERE EnrollmentID = ?")->execute([$targetEnrollmentId]);
            $db->prepare("UPDATE student SET Status = 'Enrolled' WHERE StudentID = ?")->execute([$targetStudentId]);

            $db->commit();

            if ($isNewOrTransferee) {
                logActivity('ID_GENERATION_AND_CONFIRMATION', 'Security Office', 'id_validation', $targetStudentId, "Generated New Student ID & Confirmed Enrollment for Student ID {$targetStudentId} ({$studentTypeName})");
                setFlash('success', "Enrollment officially CONFIRMED and new Student ID CARD GENERATED! You may now print the student's ID card.");
            } else {
                logActivity('ID_VALIDATION_AND_CONFIRMATION', 'Security Office', 'id_validation', $targetStudentId, "Validated Student ID & Confirmed Enrollment for Student ID {$targetStudentId} ({$studentTypeName})");
                setFlash('success', "Enrollment officially CONFIRMED and Student ID VALIDATED for current semester! You may now print the validation sticker.");
            }

            header("Location: " . BASE_URL . "/security/verify.php?student_id=" . $targetStudentId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', "Error verifying enrollment: " . $e->getMessage());
        }
    }
}

if ($studentId > 0) {
    $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName, sp.GuardianName, sp.GuardianContactNo 
        FROM student s 
        LEFT JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        LEFT JOIN student_profile sp ON sp.StudentID = s.StudentID 
        WHERE s.StudentID = ?");
    $stmtStd->execute([$studentId]);
    $student = $stmtStd->fetch();

    if ($student) {
        $stmtChk = $db->prepare("SELECT * FROM vw_enrollment_checklist WHERE StudentID = ? ORDER BY EnrollmentID DESC LIMIT 1");
        $stmtChk->execute([$studentId]);
        $checklist = $stmtChk->fetch();

        $stmtPay = $db->prepare("SELECT * FROM payment WHERE StudentID = ? ORDER BY PaymentID DESC LIMIT 1");
        $stmtPay->execute([$studentId]);
        $payment = $stmtPay->fetch();

        $stmtCln = $db->prepare("SELECT * FROM clinic WHERE StudentID = ? ORDER BY ClinicID DESC LIMIT 1");
        $stmtCln->execute([$studentId]);
        $clinic = $stmtCln->fetch();

        $stmtEnr = $db->prepare("SELECT e.*, sec.SectionCode 
            FROM enrollment e 
            LEFT JOIN blocking b ON b.EnrollmentID = e.EnrollmentID 
            LEFT JOIN section sec ON sec.SectionID = b.SectionID 
            WHERE e.StudentID = ? 
            ORDER BY e.EnrollmentID DESC LIMIT 1");
        $stmtEnr->execute([$studentId]);
        $enrollment = $stmtEnr->fetch();

        $stmtIdVal = $db->prepare("SELECT iv.*, stf.FirstName as StaffFirstName, stf.LastName as StaffLastName 
            FROM id_validation iv 
            LEFT JOIN staff stf ON stf.StaffID = iv.StaffID 
            WHERE iv.StudentID = ? 
            ORDER BY iv.ValidationID DESC LIMIT 1");
        $stmtIdVal->execute([$studentId]);
        $idValidation = $stmtIdVal->fetch();
    }
}

// Student search helper if no student selected
$searchResults = [];
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '' && !$student) {
    $stmtS = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, st.TypeName, chk.OverallStatus 
        FROM student s 
        JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        LEFT JOIN vw_enrollment_checklist chk ON chk.StudentID = s.StudentID 
        WHERE s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? 
        LIMIT 15");
    $term = "%{$searchQuery}%";
    $stmtS->execute([$term, $term, $term]);
    $searchResults = $stmtS->fetchAll();
}

// Check if all 4 steps are complete
$allPriorStepsComplete = $checklist 
    && ($checklist['DepartmentStatus'] === 'Approved' || $checklist['DepartmentStatus'] === 'Completed')
    && $checklist['RegistrarStatus'] === 'Completed'
    && $checklist['AccountingStatus'] === 'Completed'
    && $checklist['ClinicStatus'] === 'Completed';

$isConfirmed = ($checklist['OverallStatus'] ?? '') === 'CONFIRMED';
$typeName = $student['TypeName'] ?? '';
$isNewOrTransferee = in_array(strtolower($typeName), ['new', 'transferee']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1">Final Enrollment Verification & ID Management</h4>
        <p class="text-muted small mb-0">Audit 4-office clearance stamps, confirm enrollment, generate ID cards for new/transferee students, or validate existing IDs for regular/irregular/returnee/shifters.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/security/id_cards.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-id-card me-1"></i> ID Validation Center
        </a>
        <?php if ($student): ?>
            <a href="<?= BASE_URL ?>/security/verify.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-search me-1"></i> Look up Another Student
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$student): ?>
    <!-- Search Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i>Search Student for Final Security Verification & ID Processing</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Enter Student Number (e.g. 2024-00001) or Name..." value="<?= e($searchQuery) ?>" autofocus required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Look up Student</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <div class="list-group mt-3">
                    <div class="list-group-item bg-light fw-bold small text-uppercase">Select Student:</div>
                    <?php foreach ($searchResults as $res): 
                        $resIsNewTrans = in_array(strtolower($res['TypeName'] ?? ''), ['new', 'transferee']);
                    ?>
                        <a href="?student_id=<?= $res['StudentID'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= e($res['StudentNo']) ?></span> &mdash; 
                                <span class="text-dark fw-semibold"><?= e($res['LastName'] . ', ' . $res['FirstName'] . ' ' . $res['MiddleName']) ?></span>
                                <div class="small text-muted mt-1">
                                    <span class="badge <?= $resIsNewTrans ? 'bg-info text-dark' : 'bg-secondary' ?> me-1"><?= e($res['TypeName']) ?></span>
                                    <span><?= e($res['ProgramName']) ?></span>
                                    <span class="ms-2 text-primary fw-semibold">
                                        <i class="fas <?= $resIsNewTrans ? 'fa-id-card' : 'fa-stamp' ?> me-1"></i>
                                        <?= $resIsNewTrans ? 'Requires ID Generation' : 'Requires ID Validation' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= ($res['OverallStatus'] ?? '') === 'CONFIRMED' ? 'bg-success' : 'bg-warning text-dark' ?> me-2">
                                    <?= e($res['OverallStatus'] ?? 'IN PROGRESS') ?>
                                </span>
                                <span class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($searchQuery !== ''): ?>
                <div class="alert alert-warning mt-3 mb-0 small">No students found matching "<?= e($searchQuery) ?>".</div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Student Overview Banner -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle <?= $isNewOrTransferee ? 'bg-primary' : 'bg-dark' ?> text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 52px; height: 52px; font-size: 1.2rem;">
                            <?= strtoupper(substr($student['FirstName'] ?? 'S', 0, 1) . substr($student['LastName'] ?? 'T', 0, 1)) ?>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></h5>
                            <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
                                <strong>Student ID:</strong> <span class="text-primary fw-bold"><?= e($student['StudentNo']) ?></span> &bull; 
                                <strong>Program:</strong> <?= e($student['ProgramName']) ?> &bull; 
                                <strong>Type:</strong> 
                                <span class="badge <?= $isNewOrTransferee ? 'bg-info text-dark' : 'bg-secondary' ?> fw-bold">
                                    <?= e($student['TypeName']) ?>
                                </span>
                                <?php if ($isNewOrTransferee): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="fas fa-id-card me-1"></i> New ID Card Generation Workflow
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        <i class="fas fa-stamp me-1"></i> ID Validation & Sticker Renewal Workflow
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if ($isConfirmed): ?>
                        <span class="badge bg-success p-2 fs-6 shadow-sm"><i class="fas fa-check-circle me-1"></i> ENROLLMENT CONFIRMED</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark p-2 fs-6 shadow-sm"><i class="fas fa-clock me-1"></i> Awaiting Verification</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$checklist): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No active enrollment checklist record found for this student. The student has not initiated enrollment processing.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left Column: Clearances & ID Feature Box -->
            <div class="col-lg-7">
                <!-- 4-Office Clearance Checklist Status Audit -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-alt me-2 text-primary"></i>4-Office Prerequisite Clearance Audit</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <!-- 1. Department -->
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-building text-info me-2"></i>1. Department Admission</h6>
                                    <small class="text-muted">Document verification & registration</small>
                                </div>
                                <span class="badge <?= ($checklist['DepartmentStatus'] === 'Approved' || $checklist['DepartmentStatus'] === 'Completed') ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                                    <?= ($checklist['DepartmentStatus'] === 'Approved' || $checklist['DepartmentStatus'] === 'Completed') ? '✓ Approved' : '⏳ Pending' ?>
                                </span>
                            </div>

                            <!-- 2. Registrar -->
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard-list text-success me-2"></i>2. Registrar Study Load</h6>
                                    <small class="text-muted">Curriculum evaluation & schedule assignment</small>
                                </div>
                                <span class="badge <?= $checklist['RegistrarStatus'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                                    <?= $checklist['RegistrarStatus'] === 'Completed' ? '✓ Completed' : '⏳ Pending' ?>
                                </span>
                            </div>

                            <!-- 3. Accounting -->
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-cash-register text-warning me-2"></i>3. Accounting Payment</h6>
                                    <small class="text-muted">Receipt: <?= e($payment['ReceiptNo'] ?? 'No payment recorded') ?></small>
                                </div>
                                <span class="badge <?= $checklist['AccountingStatus'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                                    <?= $checklist['AccountingStatus'] === 'Completed' ? '✓ Paid & Cleared' : '⏳ Pending Payment' ?>
                                </span>
                            </div>

                            <!-- 4. Clinic -->
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-heartbeat text-danger me-2"></i>4. Clinic Health Clearance</h6>
                                    <small class="text-muted">BMI: <?= $clinic['BMI'] ?? 'Unassessed' ?> kg/m&sup2;</small>
                                </div>
                                <span class="badge <?= $checklist['ClinicStatus'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                                    <?= $checklist['ClinicStatus'] === 'Completed' ? '✓ Medically Cleared' : '⏳ Pending Clearance' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ID Validation / Generation Protocol Card -->
                <div class="card shadow-sm border <?= $isNewOrTransferee ? 'border-primary' : 'border-warning' ?> mb-4">
                    <div class="card-header <?= $isNewOrTransferee ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-dark' ?> py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas <?= $isNewOrTransferee ? 'fa-id-card' : 'fa-stamp' ?> me-2"></i>
                            Security Office ID Protocol: <?= $isNewOrTransferee ? 'New Student ID Card Generation' : 'Existing Student ID Validation' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-3 rounded-3 <?= $isNewOrTransferee ? 'bg-primary text-white' : 'bg-warning text-dark' ?> shadow-sm">
                                <i class="fas <?= $isNewOrTransferee ? 'fa-id-badge fa-2x' : 'fa-id-card fa-2x' ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <?php if ($isNewOrTransferee): ?>
                                    <h6 class="fw-bold text-dark mb-1">New ID Card Issuance Required</h6>
                                    <p class="small text-muted mb-2">
                                        Because this student is registered as a <strong><?= e($student['TypeName']) ?></strong> student, Security Office will <strong>generate and issue a brand-new official Student ID Card</strong> upon final confirmation.
                                    </p>
                                    <div class="badge bg-light text-dark border p-2 small">
                                        <i class="fas fa-qrcode me-1 text-primary"></i> Digital Barcode & QR Code Auto-Generated
                                    </div>
                                <?php else: ?>
                                    <h6 class="fw-bold text-dark mb-1">Student ID Validation & Renewal Required</h6>
                                    <p class="small text-muted mb-2">
                                        Because this student is a continuing <strong><?= e($student['TypeName']) ?></strong> student, Security Office will <strong>validate their existing physical ID card</strong> and issue a validated term sticker for <strong>A.Y. 2026-2027 1st Semester</strong>.
                                    </p>
                                    <div class="badge bg-light text-dark border p-2 small">
                                        <i class="fas fa-check-double me-1 text-success"></i> Semester Validation Stamp & Sticker Slip
                                    </div>
                                <?php endif; ?>

                                <?php if ($idValidation && $idValidation['Status'] === 'Completed'): ?>
                                    <div class="mt-3 p-2 bg-success-subtle text-success border border-success-subtle rounded small d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-check-circle me-1"></i> <strong>ID Status:</strong> Completed & Recorded on <?= formatDate($idValidation['ValidationDate']) ?>
                                            <?php if (!empty($idValidation['StaffFirstName'])): ?>
                                                by <?= e($idValidation['StaffFirstName'] . ' ' . $idValidation['StaffLastName']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Security Verification & ID Actions -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold <?= $isConfirmed ? 'text-success' : 'text-primary' ?>">
                            <i class="fas fa-lock-open me-2"></i>Security Final Authorization & ID Confirmation
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isConfirmed): ?>
                            <!-- Confirmed State -->
                            <div class="alert alert-success py-3 text-center mb-3">
                                <i class="fas fa-check-circle fa-3x mb-2 text-success"></i>
                                <h5 class="fw-bold mb-1">Enrollment Confirmed!</h5>
                                <p class="small text-muted mb-0">
                                    All 4 office clearances verified. 
                                    <?= $isNewOrTransferee ? 'Official Student ID Card generated.' : 'Existing Student ID validated for current semester.' ?>
                                </p>
                            </div>

                            <h6 class="fw-bold text-dark small text-uppercase mb-2">Official Documents & Prints:</h6>
                            <div class="d-grid gap-2 mb-3">
                                <?php if ($isNewOrTransferee): ?>
                                    <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-primary py-2 shadow-sm">
                                        <i class="fas fa-id-card me-1"></i> Print / View Student ID Card
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/security/print_validation_sticker.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-success py-2 shadow-sm">
                                        <i class="fas fa-certificate me-1"></i> Print ID Validation Sticker / Slip
                                    </a>
                                    <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-outline-primary py-2">
                                        <i class="fas fa-id-card me-1"></i> View / Reprint Student ID Card
                                    </a>
                                <?php endif; ?>

                                <a href="<?= BASE_URL ?>/student/print_confirmation.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-outline-secondary py-2">
                                    <i class="fas fa-print me-1"></i> Print Enrollment Confirmation Slip
                                </a>
                            </div>

                            <div class="p-3 bg-light rounded border small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Security Verification:</span>
                                    <span class="fw-bold text-success">✓ Verified</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">ID Feature:</span>
                                    <span class="fw-bold text-primary"><?= $isNewOrTransferee ? 'ID Card Generated' : 'ID Validated' ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Term:</span>
                                    <span class="fw-semibold">A.Y. 2026-2027 (1st Sem)</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Unconfirmed / Pending State -->
                            <?php if (!$allPriorStepsComplete): ?>
                                <div class="alert alert-warning py-3">
                                    <h6 class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Cannot Confirm Yet</h6>
                                    <p class="small mb-0">
                                        The student has not finished all 4 previous office requirements. Security confirmation and ID processing is locked until Department, Registrar, Accounting, and Clinic are complete.
                                    </p>
                                </div>
                                <button class="btn btn-secondary w-100 py-2" disabled>
                                    <i class="fas fa-lock me-1"></i> Final Confirmation Locked
                                </button>
                            <?php else: ?>
                                <div class="alert alert-success py-2 small mb-3">
                                    <i class="fas fa-check-double me-1"></i>
                                    All 4 prerequisite clearances are verified. You can now execute the final confirmation and <?= $isNewOrTransferee ? 'generate the new student ID' : 'validate the existing student ID' ?>.
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="confirm_final" value="1">
                                    <input type="hidden" name="enrollment_id" value="<?= $checklist['EnrollmentID'] ?>">
                                    <input type="hidden" name="student_id" value="<?= $student['StudentID'] ?>">
                                    <input type="hidden" name="student_type_name" value="<?= e($student['TypeName']) ?>">
                                    <input type="hidden" name="id_action" value="<?= $isNewOrTransferee ? 'generate' : 'validate' ?>">

                                    <div class="card bg-light border mb-3">
                                        <div class="card-body p-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked id="idCheckAction" disabled>
                                                <label class="form-check-label fw-bold small text-dark" for="idCheckAction">
                                                    <?php if ($isNewOrTransferee): ?>
                                                        <i class="fas fa-id-card text-primary me-1"></i> Generate & Issue New Student ID Card
                                                    <?php else: ?>
                                                        <i class="fas fa-stamp text-success me-1"></i> Validate Physical ID & Issue Current Semester Sticker
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ps-4">
                                                <?= $isNewOrTransferee 
                                                    ? 'Automatically prepares official digital ID card with student info and barcode.'
                                                    : 'Verifies physical card authenticity and affixes A.Y. 2026-2027 1st Sem validation record.' ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Security Officer Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="3"><?= $isNewOrTransferee 
                                            ? "All clearances verified. Official Student ID Generated and issued. Confirmed enrolled."
                                            : "All clearances verified. Physical Student ID validated for A.Y. 2026-2027 1st Sem. Confirmed enrolled." ?></textarea>
                                    </div>

                                    <?php if ($isNewOrTransferee): ?>
                                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Confirm final enrollment and generate Student ID for this new/transferee student?');">
                                            <i class="fas fa-id-card me-1"></i> Confirm & Generate Student ID
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Confirm final enrollment and validate Student ID for this regular/irregular/returnee/shifter student?');">
                                            <i class="fas fa-stamp me-1"></i> Confirm & Validate Student ID
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
