<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDBConnection();

echo "Testing End-to-End Registration & Student Login Workflow...\n";

// 1. Simulate Department Registering a Student
$firstName = "Maria";
$lastName = "Santos";
$email = "maria.santos" . time() . "@example.com";
$birthDate = "2005-06-15";
$sex = "Female";
$address = "123 Taft Ave, Manila";
$contactNo = "09181234567";
$programId = 1; // BSIT
$studentTypeId = 1; // New
$staffId = 2;

$stmtCount = $db->query("SELECT MAX(StudentID) FROM student");
$nextId = (int)$stmtCount->fetchColumn() + 1;
$studentNo = "APP-2026-" . sprintf("%05d", $nextId);

$stmtStd = $db->prepare("INSERT INTO `student` (`StudentNo`, `LastName`, `FirstName`, `MiddleName`, `BirthDate`, `Sex`, `Address`, `ContactNo`, `Email`, `StudentTypeID`, `ProgramID`, `Status`) VALUES (?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, 'Applicant')");
$stmtStd->execute([$studentNo, $lastName, $firstName, $birthDate, $sex, $address, $contactNo, $email, $studentTypeId, $programId]);
$studentId = (int)$db->lastInsertId();

$cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
$cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
$username = $cleanFirst . '.' . $cleanLast . $studentId;
$hashedPass = password_hash('password123', PASSWORD_DEFAULT);

$db->prepare("INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `student_id`) VALUES (?, ?, 'Student', 'Active', ?)")
   ->execute([$username, $hashedPass, $studentId]);

$db->prepare("INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StudentID`) VALUES (?, ?, 'Student', 'Active', ?)")
   ->execute([$username, $hashedPass, $studentId]);

$db->prepare("INSERT INTO `admission` (`SubmissionDate`, `ApprovalDate`, `Status`, `Remarks`, `StudentID`, `StaffID`) VALUES (CURDATE(), CURDATE(), 'Approved', 'Department approval granted on registration', ?, ?)")
   ->execute([$studentId, $staffId]);

$db->prepare("INSERT INTO `enrollment` (`EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`, `EvaluationID`, `PaymentID`) VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, ?, NULL, NULL)")
   ->execute([$studentId, $staffId]);

echo "Registered Student: $firstName $lastName | Student No: $studentNo | Username: $username\n";

// 2. Test Login by StudentNo
$stmtLoginByNo = $db->prepare("SELECT u.* FROM `users` u 
    LEFT JOIN `student` s ON s.StudentID = u.student_id 
    WHERE (LOWER(u.`username`) = LOWER(?) OR LOWER(s.`StudentNo`) = LOWER(?) OR LOWER(s.`Email`) = LOWER(?)) 
    AND u.`status` = 'Active' LIMIT 1");

$stmtLoginByNo->execute([$studentNo, $studentNo, $studentNo]);
$userByNo = $stmtLoginByNo->fetch();
assert($userByNo !== false, "Login by StudentNo should succeed");
assert(password_verify('password123', $userByNo['password_hash']), "Password check should pass");
echo "✓ Login by StudentNo ($studentNo) passed!\n";

// 3. Test Login by Username
$stmtLoginByNo->execute([$username, $username, $username]);
$userByName = $stmtLoginByNo->fetch();
assert($userByName !== false, "Login by Username should succeed");
echo "✓ Login by Username ($username) passed!\n";

// 4. Test Login by Email
$stmtLoginByNo->execute([$email, $email, $email]);
$userByEmail = $stmtLoginByNo->fetch();
assert($userByEmail !== false, "Login by Email should succeed");
echo "✓ Login by Email ($email) passed!\n";

// 5. Test Checklist View for new student
$stmtChk = $db->prepare("SELECT * FROM vw_enrollment_checklist WHERE StudentID = ? ORDER BY EnrollmentID DESC LIMIT 1");
$stmtChk->execute([$studentId]);
$chk = $stmtChk->fetch();

assert($chk !== false, "vw_enrollment_checklist should return a row");
echo "✓ Checklist record exists: EnrollmentID={$chk['EnrollmentID']} | DeptStatus={$chk['DepartmentStatus']} | OverallStatus={$chk['OverallStatus']}\n";
assert($chk['DepartmentStatus'] === 'Approved', "DepartmentStatus should be Approved");

// 6. Test rendering student dashboard
$_SESSION['user_id'] = $userByName['id'];
$_SESSION['username'] = $userByName['username'];
$_SESSION['role'] = 'Student';
$_SESSION['student_id'] = $studentId;
$_SESSION['full_name'] = "$firstName $lastName";
$_SESSION['student_no'] = $studentNo;

ob_start();
include __DIR__ . '/../student/index.php';
$html = ob_get_clean();

assert(strpos($html, $studentNo) !== false, "Dashboard contains StudentNo");
assert(strpos($html, "Maria Santos") !== false, "Dashboard contains student name");
assert(strpos($html, "Step 1: Department Office") !== false, "Dashboard contains Step 1");
assert(strpos($html, "Completed") !== false, "Step 1 is marked Completed");

echo "✓ Student Dashboard HTML rendered successfully (" . strlen($html) . " bytes)!\n";
echo "\nALL TESTS PASSED WITH 100% SUCCESS!\n";
