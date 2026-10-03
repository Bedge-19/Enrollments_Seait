<?php
// department/register_student.php - Register New Student and Account
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Register New Student';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 2;

$programs = $db->query("SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName")->fetchAll();
$studentTypes = $db->query("SELECT StudentTypeID, TypeName FROM student_type ORDER BY StudentTypeID")->fetchAll();
$requirements = $db->query("SELECT RequirementID, RequirementName, ApplicableTo FROM requirement ORDER BY RequirementID")->fetchAll();

$errors = [];
$registeredStudent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName = trim($_POST['LastName'] ?? '');
    $firstName = trim($_POST['FirstName'] ?? '');
    $middleName = trim($_POST['MiddleName'] ?? '');
    $birthDate = $_POST['BirthDate'] ?? '';
    $sex = $_POST['Sex'] ?? 'Male';
    $address = trim($_POST['Address'] ?? '');
    $contactNo = trim($_POST['ContactNo'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $programId = (int)($_POST['ProgramID'] ?? 0);
    $studentTypeId = (int)($_POST['StudentTypeID'] ?? 1);

    $guardianName = trim($_POST['GuardianName'] ?? '');
    $guardianContact = trim($_POST['GuardianContactNo'] ?? '');
    $prevSchool = trim($_POST['PreviousSchoolName'] ?? 'N/A');
    $prevProg = trim($_POST['PreviousProgram'] ?? 'N/A');
    $lastYear = trim($_POST['LastYearLevelCompleted'] ?? 'Grade 12');
    $gwa = (float)($_POST['GWA'] ?? 1.75);

    $autoApprove = isset($_POST['auto_approve']) && $_POST['auto_approve'] == '1';
    $selectedReqs = $_POST['requirements'] ?? [];

    // Basic Validation
    if (empty($lastName) || empty($firstName)) $errors[] = 'Student name is required.';
    if (empty($birthDate)) $errors[] = 'Birth date is required.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (empty($contactNo)) $errors[] = 'Contact number is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required.';
    if ($programId <= 0) $errors[] = 'Please select a degree program.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Generate unique Student No
            $prefix = ($studentTypeId == 1) ? 'APP-2026-' : (($studentTypeId == 6) ? 'TRF-2026-' : '2026-');
            $stmtCount = $db->query("SELECT MAX(StudentID) FROM student");
            $nextId = (int)$stmtCount->fetchColumn() + 1;
            $studentNo = $prefix . sprintf("%05d", $nextId);

            // 1. Insert Student
            $stmtStd = $db->prepare("INSERT INTO `student` 
                (`StudentNo`, `LastName`, `FirstName`, `MiddleName`, `BirthDate`, `Sex`, `Address`, `ContactNo`, `Email`, `StudentTypeID`, `ProgramID`, `Status`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $studentStatus = $autoApprove ? 'Enrolled' : 'Applicant';
            $stmtStd->execute([$studentNo, $lastName, $firstName, $middleName, $birthDate, $sex, $address, $contactNo, $email, $studentTypeId, $programId, $studentStatus]);
            $studentId = (int)$db->lastInsertId();

            // 2. Insert Student Profile
            $stmtProf = $db->prepare("INSERT INTO `student_profile` 
                (`Address`, `ContactNo`, `GuardianName`, `GuardianContactNo`, `PreviousSchoolName`, `PreviousProgram`, `LastYearLevelCompleted`, `GWA`, `StudentID`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtProf->execute([$address, $contactNo, $guardianName, $guardianContact, $prevSchool, $prevProg, $lastYear, $gwa, $studentId]);

            // 3. Create Login / Users account
            $cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
            $cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
            $username = $cleanFirst . '.' . $cleanLast . $studentId;
            $hashedPass = password_hash('password123', PASSWORD_DEFAULT);

            $stmtUsr = $db->prepare("INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `student_id`) VALUES (?, ?, 'Student', 'Active', ?)");
            $stmtUsr->execute([$username, $hashedPass, $studentId]);

            $stmtLog = $db->prepare("INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StudentID`) VALUES (?, ?, 'Student', 'Active', ?)");
            $stmtLog->execute([$username, $hashedPass, $studentId]);

            // 4. Create Admission Record
            $admStatus = $autoApprove ? 'Approved' : 'Pending';
            $admRemarks = $autoApprove ? 'Department approval granted on registration' : 'Initial registration submitted';
            $stmtAdm = $db->prepare("INSERT INTO `admission` (`SubmissionDate`, `ApprovalDate`, `Status`, `Remarks`, `StudentID`, `StaffID`) VALUES (CURDATE(), CURDATE(), ?, ?, ?, ?)");
            $stmtAdm->execute([$admStatus, $admRemarks, $studentId, $staffId]);

            // 5. Insert Selected Requirements
            $stmtReq = $db->prepare("INSERT INTO `student_requirement` (`RequirementID`, `StudentID`, `Status`, `DateSubmitted`) VALUES (?, ?, 'Submitted', CURDATE())");
            foreach ($selectedReqs as $reqId) {
                $stmtReq->execute([(int)$reqId, $studentId]);
            }

            // 6. Create Initial Active Enrollment Record for 2026-2027 1st Sem
            $stmtEnr = $db->prepare("INSERT INTO `enrollment` (`EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`) VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, ?)");
            $stmtEnr->execute([$studentId, $staffId]);

            $db->commit();

            logActivity('REGISTER_STUDENT', 'Department', 'student', $studentId, "Registered student {$studentNo} ({$firstName} {$lastName})");
            
            $registeredStudent = [
                'StudentID' => $studentId,
                'StudentNo' => $studentNo,
                'FullName' => "$firstName $middleName $lastName",
                'Username' => $username,
                'Password' => 'password123',
                'Email' => $email,
                'AutoApproved' => $autoApprove
            ];

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error registering student: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Register New Student</h4>
        <p class="text-muted small mb-0">Create new student record, academic profile, admission entry, and portal login account.</p>
    </div>
    <a href="<?= BASE_URL ?>/department/students.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<?php if ($registeredStudent): ?>
    <!-- Successful Registration Card with Direct Credentials -->
    <div class="card shadow border-success mb-4">
        <div class="card-header bg-success text-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-check-circle me-2"></i>Student Registration Successful!</h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted">The student record, academic profile, admission endorsement, and student portal account have been created successfully.</p>
            
            <div class="row g-3 bg-light p-3 rounded-3 border mb-4">
                <div class="col-md-6">
                    <small class="text-muted d-block">Full Name</small>
                    <strong class="fs-5 text-dark"><?= e($registeredStudent['FullName']) ?></strong>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Student ID / Number</small>
                    <strong class="fs-5 text-primary"><?= e($registeredStudent['StudentNo']) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Login Username</small>
                    <code class="fs-6 fw-bold text-dark bg-white px-2 py-1 rounded border"><?= e($registeredStudent['Username']) ?></code>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Default Password</small>
                    <code class="fs-6 fw-bold text-success bg-white px-2 py-1 rounded border"><?= e($registeredStudent['Password']) ?></code>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Department Step 1 Status</small>
                    <span class="badge <?= $registeredStudent['AutoApproved'] ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                        <?= $registeredStudent['AutoApproved'] ? '✓ Approved' : 'Pending Review' ?>
                    </span>
                </div>
            </div>

            <div class="alert alert-info py-2 small mb-4">
                <i class="fas fa-info-circle me-1"></i> The student can log in at <a href="<?= BASE_URL ?>/login.php" target="_blank" class="fw-bold text-primary">Login Page</a> using either their <strong>Student Number (<?= e($registeredStudent['StudentNo']) ?>)</strong>, their <strong>Username (<?= e($registeredStudent['Username']) ?>)</strong>, or their <strong>Email (<?= e($registeredStudent['Email']) ?>)</strong> with password <code>password123</code>.
            </div>

            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/department/register_student.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus me-1"></i> Register Another Student
                </a>
                <a href="<?= BASE_URL ?>/department/students.php?search=<?= urlencode($registeredStudent['StudentNo']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-search me-1"></i> View in Student Directory
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$registeredStudent): ?>
<form method="POST" action="">
    <div class="row g-4">
        <!-- Personal Information Card -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user me-2"></i>1. Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="LastName" class="form-control" required value="<?= e($_POST['LastName'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="FirstName" class="form-control" required value="<?= e($_POST['FirstName'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Middle Name</label>
                            <input type="text" name="MiddleName" class="form-control" value="<?= e($_POST['MiddleName'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Birth Date <span class="text-danger">*</span></label>
                            <input type="date" name="BirthDate" class="form-control" required value="<?= e($_POST['BirthDate'] ?? '2004-01-01') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sex <span class="text-danger">*</span></label>
                            <select name="Sex" class="form-select">
                                <option value="Male" <?= ($_POST['Sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($_POST['Sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Contact No. <span class="text-danger">*</span></label>
                            <input type="text" name="ContactNo" class="form-control" placeholder="09171234567" required value="<?= e($_POST['ContactNo'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="Email" class="form-control" placeholder="student@example.com" required value="<?= e($_POST['Email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Residential Address <span class="text-danger">*</span></label>
                            <input type="text" name="Address" class="form-control" placeholder="House No., Street, City, Province" required value="<?= e($_POST['Address'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previous Educational Background -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-info"><i class="fas fa-school me-2"></i>2. Previous Educational Background & Guardian</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Previous School Name</label>
                            <input type="text" name="PreviousSchoolName" class="form-control" placeholder="High School / College" value="<?= e($_POST['PreviousSchoolName'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Previous Track / Program</label>
                            <input type="text" name="PreviousProgram" class="form-control" placeholder="STEM, ABM, or College Program" value="<?= e($_POST['PreviousProgram'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Level Completed</label>
                            <input type="text" name="LastYearLevelCompleted" class="form-control" placeholder="e.g. Grade 12 or 2nd Year" value="<?= e($_POST['LastYearLevelCompleted'] ?? 'Grade 12') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">General Weighted Average (GWA)</label>
                            <input type="number" step="0.01" name="GWA" class="form-control" placeholder="e.g. 1.50" value="<?= e($_POST['GWA'] ?? '1.75') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Parent / Guardian Name</label>
                            <input type="text" name="GuardianName" class="form-control" value="<?= e($_POST['GuardianName'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Guardian Contact No.</label>
                            <input type="text" name="GuardianContactNo" class="form-control" value="<?= e($_POST['GuardianContactNo'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Program & Requirements Checklist -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-success"><i class="fas fa-graduation-cap me-2"></i>3. Enrollment Classification</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Program Applying For <span class="text-danger">*</span></label>
                        <select name="ProgramID" class="form-select" required>
                            <option value="">Select Degree Program...</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['ProgramID'] ?>" <?= ($_POST['ProgramID'] ?? '') == $p['ProgramID'] ? 'selected' : '' ?>>
                                    <?= e($p['ProgramName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Student Classification <span class="text-danger">*</span></label>
                        <select name="StudentTypeID" class="form-select" required>
                            <?php foreach ($studentTypes as $st): ?>
                                <option value="<?= $st['StudentTypeID'] ?>" <?= ($_POST['StudentTypeID'] ?? 1) == $st['StudentTypeID'] ? 'selected' : '' ?>>
                                    <?= e($st['TypeName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="auto_approve" id="auto_approve" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="auto_approve">Approve Department Admission Immediately</label>
                    </div>
                </div>
            </div>

            <!-- Documents Submitted -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-folder-open me-2 text-warning"></i>Submitted Documents</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($requirements as $req): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="requirements[]" value="<?= $req['RequirementID'] ?>" id="req_<?= $req['RequirementID'] ?>" checked>
                            <label class="form-check-label small" for="req_<?= $req['RequirementID'] ?>">
                                <?= e($req['RequirementName']) ?>
                                <small class="text-muted d-block">(<?= e($req['ApplicableTo']) ?>)</small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="fas fa-save me-1"></i> Submit Registration
                </button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
