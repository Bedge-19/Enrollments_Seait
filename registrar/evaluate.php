<?php
// registrar/evaluate.php - Student Curriculum Evaluation & Study Load Assignment
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Registrar');
$pageTitle = 'Curriculum Evaluation & Study Load Assignment';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 6;

$studentId = (int)($_GET['student_id'] ?? 0);
$student = null;
$passedSubjectIds = [];
$failedSubjectIds = [];
$creditedSubjectIds = [];
$curriculumSubjects = [];
$sections = [];

if ($studentId > 0) {
    $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName, a.Status as AdmissionStatus, a.ApprovalDate 
        FROM student s 
        LEFT JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        LEFT JOIN admission a ON a.StudentID = s.StudentID 
        WHERE s.StudentID = ?");
    $stmtStd->execute([$studentId]);
    $student = $stmtStd->fetch();

    if ($student) {
        // 1. Get Passed / Failed grades
        $stmtGrades = $db->prepare("SELECT SubjectID, FinalGrade, GradeStatus FROM student_grades WHERE StudentID = ?");
        $stmtGrades->execute([$studentId]);
        foreach ($stmtGrades->fetchAll() as $g) {
            if ($g['GradeStatus'] === 'Passed') {
                $passedSubjectIds[] = (int)$g['SubjectID'];
            } elseif ($g['GradeStatus'] === 'Failed') {
                $failedSubjectIds[] = (int)$g['SubjectID'];
            }
        }

        // 2. Get Credited subjects
        $stmtCred = $db->prepare("SELECT SubjectID FROM credited_subject WHERE StudentID = ? AND IsCredited = 1");
        $stmtCred->execute([$studentId]);
        $creditedSubjectIds = $stmtCred->fetchAll(PDO::FETCH_COLUMN);

        // 3. Get Curriculum Subjects for student's program
        $stmtCurr = $db->prepare("SELECT sub.*, cs.YearLevel, cs.Semester, cs.IsMinor 
            FROM curriculum c 
            JOIN curriculum_subject cs ON cs.CurriculumID = c.CurriculumID 
            JOIN subject sub ON sub.SubjectID = cs.SubjectID 
            WHERE c.ProgramID = ? 
            ORDER BY cs.YearLevel ASC, cs.Semester ASC, sub.SubjectCode ASC");
        $stmtCurr->execute([$student['ProgramID']]);
        $curriculumSubjects = $stmtCurr->fetchAll();

        // 4. Get available Sections for student's program
        $stmtSec = $db->prepare("SELECT * FROM section WHERE ProgramID = ? ORDER BY YearLevel ASC, SectionCode ASC");
        $stmtSec->execute([$student['ProgramID']]);
        $sections = $stmtSec->fetchAll();
    }
}

// Handle Study Load Assignment Submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_load'])) {
    $targetStudentId = (int)$_POST['student_id'];
    $selectedSubjectIds = $_POST['subjects'] ?? [];
    $sectionId = (int)($_POST['section_id'] ?? 0);
    $officialStudentNo = trim($_POST['official_student_no'] ?? '');

    if (empty($selectedSubjectIds)) {
        $errors[] = "Please select at least one subject for the study load.";
    }
    if ($sectionId <= 0) {
        $errors[] = "Please select an assigned Section/Block.";
    }

    // Schedule Conflict Detection
    if (empty($errors)) {
        $inClause = implode(',', array_fill(0, count($selectedSubjectIds), '?'));
        $stmtSched = $db->prepare("SELECT s.*, sub.SubjectCode 
            FROM schedule s 
            JOIN subject sub ON sub.SubjectID = s.SubjectID 
            WHERE s.SectionID = ? AND s.SubjectID IN ({$inClause})");
        $stmtSched->execute(array_merge([$sectionId], $selectedSubjectIds));
        $schedules = $stmtSched->fetchAll();

        // Check for overlapping time slots on same day
        for ($i = 0; $i < count($schedules); $i++) {
            for ($j = $i + 1; $j < count($schedules); $j++) {
                if ($schedules[$i]['Day'] === $schedules[$j]['Day']) {
                    $startA = strtotime($schedules[$i]['TimeStart']);
                    $endA   = strtotime($schedules[$i]['TimeEnd']);
                    $startB = strtotime($schedules[$j]['TimeStart']);
                    $endB   = strtotime($schedules[$j]['TimeEnd']);

                    if (max($startA, $startB) < min($endA, $endB)) {
                        $errors[] = "Schedule Conflict: {$schedules[$i]['SubjectCode']} and {$schedules[$j]['SubjectCode']} overlap on {$schedules[$i]['Day']}.";
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Update Student Number if new/transferee official ID was provided
            if (!empty($officialStudentNo)) {
                $db->prepare("UPDATE student SET StudentNo = ? WHERE StudentID = ?")->execute([$officialStudentNo, $targetStudentId]);
            }

            // 2. Insert or update Evaluation
            $stmtEv = $db->prepare("INSERT INTO evaluation (EvaluationDate, Remarks, Status, StudentID, StaffID) 
                VALUES (CURDATE(), 'Curriculum evaluated and approved for current semester load', 'Approved', ?, ?)");
            $stmtEv->execute([$targetStudentId, $staffId]);
            $evaluationId = (int)$db->lastInsertId();

            // 3. Insert evaluation subjects
            $stmtEvSub = $db->prepare("INSERT INTO evaluation_subject (SubjectStatus, EvaluationID, SubjectID) VALUES ('Approved', ?, ?)");
            foreach ($selectedSubjectIds as $subId) {
                $stmtEvSub->execute([$evaluationId, (int)$subId]);
            }

            // 4. Calculate total units and assess initial payment
            $stmtUnits = $db->prepare("SELECT SUM(Units) FROM subject WHERE SubjectID IN ({$inClause})");
            $stmtUnits->execute($selectedSubjectIds);
            $totalUnits = (float)$stmtUnits->fetchColumn();
            $tuitionFee = $totalUnits * 500.00; // 500 per unit
            $miscFee = 3500.00;
            $totalAmount = $tuitionFee + $miscFee;

            // 5. Insert initial Payment record (Pending)
            $stmtPay = $db->prepare("INSERT INTO payment (Amount, PaymentDate, ReceiptNo, PaymentStatus, StudentID, StaffID) 
                VALUES (?, CURDATE(), '', 'Pending', ?, ?)");
            $stmtPay->execute([$totalAmount, $targetStudentId, 7]); // Staff 7 = Accounting
            $paymentId = (int)$db->lastInsertId();

            // 6. Insert Payment Items
            $stmtPayItem = $db->prepare("INSERT INTO payment_item (PaymentID, FeeType, Description, Amount) VALUES (?, ?, ?, ?)");
            $stmtPayItem->execute([$paymentId, 'Tuition Fee', "Enrolled assessment for {$totalUnits} units", $tuitionFee]);
            $stmtPayItem->execute([$paymentId, 'Miscellaneous Fee', 'Library, Athletics, Lab, Medical Fees', $miscFee]);

            // 7. Insert Enrollment record
            $stmtEnr = $db->prepare("INSERT INTO enrollment (EnrollmentDate, SchoolYear, Semester, Status, StudentID, StaffID, EvaluationID, PaymentID) 
                VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, ?, ?, ?)");
            $stmtEnr->execute([$targetStudentId, $staffId, $evaluationId, $paymentId]);
            $enrollmentId = (int)$db->lastInsertId();

            // 8. Insert Enrollment Subjects & Blocking
            $stmtEnrSub = $db->prepare("INSERT INTO enrollment_subject (EnrollmentID, SubjectID, SectionID) VALUES (?, ?, ?)");
            foreach ($selectedSubjectIds as $subId) {
                $stmtEnrSub->execute([$enrollmentId, (int)$subId, $sectionId]);
            }

            $stmtBlock = $db->prepare("INSERT INTO blocking (BlockingDate, EnrollmentID, SectionID) VALUES (CURDATE(), ?, ?)");
            $stmtBlock->execute([$enrollmentId, $sectionId]);

            // 9. Initialize Clinic record if not existing
            $stmtChkClinic = $db->prepare("SELECT COUNT(*) FROM clinic WHERE StudentID = ?");
            $stmtChkClinic->execute([$targetStudentId]);
            if ($stmtChkClinic->fetchColumn() == 0) {
                $db->prepare("INSERT INTO clinic (CompletionDate, Status, Remarks, StudentID, StaffID, ClearanceStatus) 
                    VALUES (CURDATE(), 'Pending', 'Awaiting health examination', ?, 8, 'Pending')")
                    ->execute([$targetStudentId]);
            }

            // 10. Initialize Security Verification record
            $db->prepare("INSERT INTO security_verification (EnrollmentID, StudentID, Status, Remarks) 
                VALUES (?, ?, 'Pending', 'Awaiting multi-office clearance completion')
                ON DUPLICATE KEY UPDATE Status = 'Pending'")
                ->execute([$enrollmentId, $targetStudentId]);

            $db->commit();

            logActivity('ASSIGN_STUDY_LOAD', 'Registrar', 'enrollment', $enrollmentId, "Assigned study load ({$totalUnits} units) to student {$student['StudentNo']}");
            setFlash('success', "Study load successfully assigned and approved! Next step: Accounting Payment.");
            header("Location: " . BASE_URL . "/registrar/index.php");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error generating study load: " . $e->getMessage();
        }
    }
}

// Student search helper if no student selected
$searchResults = [];
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '' && !$student) {
    $stmtS = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, st.TypeName 
        FROM student s 
        JOIN program p ON p.ProgramID = s.ProgramID 
        JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        WHERE s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? 
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
        <h4 class="fw-bold text-dark mb-1">Curriculum Evaluation & Study Load</h4>
        <p class="text-muted small mb-0">Select required subjects, assign block/section, check schedule conflicts, and generate study load.</p>
    </div>
    <?php if ($student): ?>
        <a href="<?= BASE_URL ?>/registrar/evaluate.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-search me-1"></i> Choose Another Student
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><i class="fas fa-exclamation-triangle me-1"></i><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$student): ?>
    <!-- Student Search Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i>Select Student for Evaluation</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Enter Student Number (e.g. 2024-00001, APP-2026-00001) or Name..." value="<?= e($searchQuery) ?>" autofocus required>
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
                                <small class="text-muted d-block"><?= e($res['ProgramName']) ?> &bull; <span class="badge bg-secondary"><?= e($res['TypeName']) ?></span></small>
                            </div>
                            <span class="btn btn-sm btn-primary"><i class="fas fa-arrow-right me-1"></i> Evaluate</span>
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
                        <strong>Status:</strong> <span class="badge bg-info text-dark"><?= e($student['TypeName']) ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <span class="badge bg-success p-2"><i class="fas fa-check-circle me-1"></i> Dept. Admission Approved</span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="assign_load" value="1">
        <input type="hidden" name="student_id" value="<?= $student['StudentID'] ?>">

        <div class="row g-4">
            <!-- Left: Curriculum Subjects Matrix -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-book me-2"></i>Curriculum Subjects & Remaining Requirements</h6>
                        <span class="badge bg-light text-dark border">Term: 1st Semester</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">Enroll</th>
                                        <th>Subject Code & Title</th>
                                        <th class="text-center">Year / Sem</th>
                                        <th class="text-center">Units</th>
                                        <th class="text-center">Academic Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($curriculumSubjects)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No curriculum subjects found for this program.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($curriculumSubjects as $cs):
                                            $isPassed = in_array((int)$cs['SubjectID'], $passedSubjectIds);
                                            $isCredited = in_array((int)$cs['SubjectID'], $creditedSubjectIds);
                                            $isFailed = in_array((int)$cs['SubjectID'], $failedSubjectIds);
                                            $isCompleted = $isPassed || $isCredited;

                                            // Default recommendation: 1st year 1st sem subjects or failed subjects
                                            $isRecommended = (!$isCompleted && ($cs['YearLevel'] == 1 && $cs['Semester'] == '1st')) || $isFailed;
                                        ?>
                                            <tr class="<?= $isCompleted ? 'table-light text-muted' : '' ?>">
                                                <td class="text-center">
                                                    <?php if ($isCompleted): ?>
                                                        <i class="fas fa-check-circle text-success" title="Completed / Credited"></i>
                                                    <?php else: ?>
                                                        <input type="checkbox" name="subjects[]" value="<?= $cs['SubjectID'] ?>" class="form-check-input" <?= $isRecommended ? 'checked' : '' ?>>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold <?= $isCompleted ? 'text-muted' : 'text-dark' ?>"><?= e($cs['SubjectCode']) ?></div>
                                                    <small><?= e($cs['SubjectTitle']) ?></small>
                                                </td>
                                                <td class="text-center small">Yr <?= $cs['YearLevel'] ?> &bull; <?= e($cs['Semester']) ?></td>
                                                <td class="text-center fw-bold"><?= number_format((float)$cs['Units'], 1) ?></td>
                                                <td class="text-center">
                                                    <?php if ($isPassed): ?>
                                                        <span class="badge bg-success">Passed</span>
                                                    <?php elseif ($isCredited): ?>
                                                        <span class="badge bg-info text-dark">Credited</span>
                                                    <?php elseif ($isFailed): ?>
                                                        <span class="badge bg-danger">Failed (Retake)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border">Available</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Section & Block Assignment -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-layer-group me-2"></i>Section & Student Number Assignment</h6>
                    </div>
                    <div class="card-body">
                        <!-- Official Student Number (for applicants/transferees) -->
                        <?php if (str_starts_with($student['StudentNo'], 'APP-') || str_starts_with($student['StudentNo'], 'TRF-')): ?>
                            <div class="mb-3 p-3 bg-warning-subtle rounded border border-warning">
                                <label class="form-label small fw-bold text-dark"><i class="fas fa-id-badge me-1"></i> Assign Official Student Number</label>
                                <input type="text" name="official_student_no" class="form-control form-control-sm fw-bold text-primary" value="2026-<?= sprintf('%05d', $student['StudentID']) ?>" required>
                                <small class="text-muted">Replaces temporary applicant ID upon load confirmation.</small>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="official_student_no" value="<?= e($student['StudentNo']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Assigned Section / Block <span class="text-danger">*</span></label>
                            <select name="section_id" class="form-select" required>
                                <option value="">Select Section...</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['SectionID'] ?>">
                                        <?= e($sec['SectionCode']) ?> (Year Level <?= $sec['YearLevel'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-info small py-2">
                            <i class="fas fa-info-circle me-1"></i>
                            The system will automatically link schedule records for the assigned section and evaluate conflict rules.
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> Confirm & Generate Study Load
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
