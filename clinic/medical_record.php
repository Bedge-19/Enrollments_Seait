<?php
// clinic/medical_record.php - Student Medical Examination & Physical Clearance
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Clinic');
$pageTitle = 'Medical Examination & Clearance';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 8;

$studentId = (int)($_GET['student_id'] ?? 0);
$student = null;
$clinic = null;

// Handle Medical Submission / Clearance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_medical'])) {
    $targetStudentId = (int)$_POST['student_id'];
    $height = (float)($_POST['HeightCM'] ?? 0);
    $weight = (float)($_POST['WeightKG'] ?? 0);
    $history = trim($_POST['MedicalHistory'] ?? '');
    $remarks = trim($_POST['Remarks'] ?? 'Physical examination completed. Fit for academic enrollment.');
    $clearanceStatus = $_POST['ClearanceStatus'] ?? 'Cleared';
    $status = ($clearanceStatus === 'Cleared') ? 'Completed' : 'Pending';

    try {
        $stmtChk = $db->prepare("SELECT ClinicID FROM clinic WHERE StudentID = ?");
        $stmtChk->execute([$targetStudentId]);
        $existingId = $stmtChk->fetchColumn();

        if ($existingId) {
            $stmtUpd = $db->prepare("UPDATE clinic SET 
                CompletionDate = CURDATE(), Status = ?, Remarks = ?, StaffID = ?, 
                HeightCM = ?, WeightKG = ?, MedicalHistory = ?, ClearanceStatus = ? 
                WHERE ClinicID = ?");
            $stmtUpd->execute([$status, $remarks, $staffId, $height, $weight, $history, $clearanceStatus, $existingId]);
        } else {
            $stmtIns = $db->prepare("INSERT INTO clinic 
                (CompletionDate, Status, Remarks, StudentID, StaffID, HeightCM, WeightKG, MedicalHistory, ClearanceStatus) 
                VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$status, $remarks, $targetStudentId, $staffId, $height, $weight, $history, $clearanceStatus]);
        }

        logActivity('UPDATE_CLINIC_RECORD', 'Clinic', 'clinic', $targetStudentId, "Medical clearance status updated to {$clearanceStatus}");
        setFlash('success', "Medical record saved successfully! Clearance status: {$clearanceStatus}.");
        header("Location: " . BASE_URL . "/clinic/medical_record.php?student_id=" . $targetStudentId);
        exit;

    } catch (Exception $e) {
        setFlash('danger', "Error saving medical record: " . $e->getMessage());
    }
}

if ($studentId > 0) {
    $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
        FROM student s 
        LEFT JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        WHERE s.StudentID = ?");
    $stmtStd->execute([$studentId]);
    $student = $stmtStd->fetch();

    if ($student) {
        $stmtCln = $db->prepare("SELECT * FROM clinic WHERE StudentID = ? ORDER BY ClinicID DESC LIMIT 1");
        $stmtCln->execute([$studentId]);
        $clinic = $stmtCln->fetch();
    }
}

// Student search helper if no student selected
$searchResults = [];
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '' && !$student) {
    $stmtS = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, cln.ClearanceStatus 
        FROM student s 
        JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN clinic cln ON cln.StudentID = s.StudentID 
        WHERE s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? 
        ORDER BY s.StudentID ASC 
        LIMIT 15");
    $term = "%{$searchQuery}%";
    $stmtS->execute([$term, $term, $term]);
    $searchResults = $stmtS->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Medical Record & Clearance</h4>
        <p class="text-muted small mb-0">Record student health metrics, calculate stored BMI, document medical history, and issue physical clearance.</p>
    </div>
    <?php if ($student): ?>
        <div>
            <?php if (($clinic['ClearanceStatus'] ?? '') === 'Cleared'): ?>
                <a href="<?= BASE_URL ?>/clinic/print_clearance.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-print me-1"></i> Print Clearance Certificate
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/clinic/medical_record.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-search me-1"></i> Search Another Student
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$student): ?>
    <!-- Search Student Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i>Search Student for Medical Examination</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Enter Student Number (e.g. 2024-00001) or Student Name..." value="<?= e($searchQuery) ?>" autofocus required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Search Student</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <div class="list-group mt-3">
                    <div class="list-group-item bg-light fw-bold small text-uppercase">Select Student:</div>
                    <?php foreach ($searchResults as $res): ?>
                        <a href="?student_id=<?= $res['StudentID'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= e($res['StudentNo']) ?></span> &mdash; 
                                <span class="text-dark fw-semibold"><?= e($res['LastName'] . ', ' . $res['FirstName'] . ' ' . $res['MiddleName']) ?></span>
                                <small class="text-muted d-block"><?= e($res['ProgramName']) ?></small>
                            </div>
                            <div>
                                <span class="badge <?= ($res['ClearanceStatus'] ?? '') === 'Cleared' ? 'bg-success' : 'bg-warning text-dark' ?> me-2">
                                    <?= e($res['ClearanceStatus'] ?? 'Pending') ?>
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
                    <h5 class="fw-bold text-dark mb-1"><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></h5>
                    <div class="text-muted small">
                        <strong>Student ID:</strong> <span class="text-primary fw-bold"><?= e($student['StudentNo']) ?></span> &bull; 
                        <strong>Program:</strong> <?= e($student['ProgramName']) ?> &bull; 
                        <strong>Sex / Birthdate:</strong> <?= e($student['Sex']) ?>, <?= formatDate($student['BirthDate']) ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <?php if (($clinic['ClearanceStatus'] ?? '') === 'Cleared'): ?>
                        <span class="badge bg-success p-2 fs-6"><i class="fas fa-check-circle me-1"></i> Medically Cleared</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark p-2 fs-6"><i class="fas fa-clock me-1"></i> Pending Clearance</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Examination Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-heartbeat me-2"></i>Physical Metrics & Health Examination Form</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="save_medical" value="1">
                <input type="hidden" name="student_id" value="<?= $student['StudentID'] ?>">

                <div class="row g-4 mb-4">
                    <!-- Physical Metrics -->
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Height (cm) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.5" id="HeightCM" name="HeightCM" class="form-control" placeholder="e.g. 170" value="<?= $clinic['HeightCM'] ?? '165' ?>" required>
                            <span class="input-group-text bg-light">cm</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Weight (kg) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.5" id="WeightKG" name="WeightKG" class="form-control" placeholder="e.g. 60" value="<?= $clinic['WeightKG'] ?? '60' ?>" required>
                            <span class="input-group-text bg-light">kg</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Computed Body Mass Index (BMI)</label>
                        <div class="input-group">
                            <input type="text" id="bmi_display" class="form-control bg-light fw-bold text-primary" readonly value="<?= $clinic['BMI'] ?? '' ?>">
                            <span class="input-group-text bg-light"><span id="bmi_category" class="badge bg-success"></span></span>
                        </div>
                        <small class="text-muted">Calculated automatically in real-time and stored via generated column.</small>
                    </div>

                    <!-- Medical History & Remarks -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Medical / Surgical History</label>
                        <textarea name="MedicalHistory" class="form-control" rows="3" placeholder="Disclose allergies, chronic conditions, surgeries, or medications..."><?= e($clinic['MedicalHistory'] ?? 'No known chronic illness or major allergies.') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Physician Examination Remarks</label>
                        <textarea name="Remarks" class="form-control" rows="3" placeholder="Physical exam findings, vital signs, remarks..."><?= e($clinic['Remarks'] ?? 'Physical examination completed. Normal vital signs. Cleared for enrollment.') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Medical Clearance Decision <span class="text-danger">*</span></label>
                        <select name="ClearanceStatus" class="form-select" required>
                            <option value="Cleared" <?= ($clinic['ClearanceStatus'] ?? 'Cleared') === 'Cleared' ? 'selected' : '' ?>>
                                Cleared (Fit to Enroll & Study)
                            </option>
                            <option value="Not Cleared" <?= ($clinic['ClearanceStatus'] ?? '') === 'Not Cleared' ? 'selected' : '' ?>>
                                Not Cleared (Requires Specialist Follow-up)
                            </option>
                            <option value="Pending" <?= ($clinic['ClearanceStatus'] ?? '') === 'Pending' ? 'selected' : '' ?>>
                                Pending (Awaiting Lab / X-Ray Results)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                        <i class="fas fa-save me-1"></i> Save Medical Record & Issue Clearance
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
