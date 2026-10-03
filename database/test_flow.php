<?php
// database/test_flow.php - End-to-end workflow verification script
require_once __DIR__ . '/../config/db.php';

$db = getDBConnection();
echo "=== TESTING COMPLETE 5-OFFICE ENROLLMENT WORKFLOW ===\n";

// 1. Department creates a new student
$db->beginTransaction();

$studentNo = "APP-TEST-99999";
$stmt = $db->prepare("INSERT INTO student (StudentNo, LastName, FirstName, MiddleName, BirthDate, Sex, Address, ContactNo, Email, StudentTypeID, ProgramID, Status)
    VALUES (?, 'Dela Cruz', 'Juan', 'Santos', '2004-05-15', 'Male', 'Sampaloc, Manila', '09170009999', 'juan.test@student.enrollments.edu', 1, 1, 'Applicant')");
$stmt->execute([$studentNo]);
$studentId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO student_profile (Address, ContactNo, GuardianName, GuardianContactNo, PreviousSchoolName, PreviousProgram, LastYearLevelCompleted, GWA, StudentID)
    VALUES ('Sampaloc, Manila', '09170009999', 'Pedro Dela Cruz', '09170008888', 'Manila High School', 'STEM', 'Grade 12', 1.50, ?)")
    ->execute([$studentId]);

$db->prepare("INSERT INTO admission (SubmissionDate, ApprovalDate, Status, Remarks, StudentID, StaffID)
    VALUES (CURDATE(), CURDATE(), 'Approved', 'Department approved credentials', ?, 2)")
    ->execute([$studentId]);

echo "1. Department: Created student #{$studentId} ({$studentNo}) and Approved Admission.\n";

// 2. Registrar evaluates curriculum and assigns study load (IT101, IT102, GE101, GE102)
$db->prepare("INSERT INTO evaluation (EvaluationDate, Remarks, Status, StudentID, StaffID)
    VALUES (CURDATE(), 'Evaluated standard 1st sem load', 'Approved', ?, 6)")
    ->execute([$studentId]);
$evaluationId = (int)$db->lastInsertId();

$subjects = [1, 2, 3, 4]; // 12 units
foreach ($subjects as $sid) {
    $db->prepare("INSERT INTO evaluation_subject (SubjectStatus, EvaluationID, SubjectID) VALUES ('Approved', ?, ?)")->execute([$evaluationId, $sid]);
}

$db->prepare("INSERT INTO payment (Amount, PaymentDate, ReceiptNo, PaymentStatus, StudentID, StaffID)
    VALUES (9500.00, CURDATE(), '', 'Pending', ?, 7)")
    ->execute([$studentId]);
$paymentId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO enrollment (EnrollmentDate, SchoolYear, Semester, Status, StudentID, StaffID, EvaluationID, PaymentID)
    VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, 6, ?, ?)")
    ->execute([$studentId, $evaluationId, $paymentId]);
$enrollmentId = (int)$db->lastInsertId();

$sectionId = 1; // BSIT-1A
$db->prepare("INSERT INTO blocking (BlockingDate, EnrollmentID, SectionID) VALUES (CURDATE(), ?, ?)")->execute([$enrollmentId, $sectionId]);
foreach ($subjects as $sid) {
    $db->prepare("INSERT INTO enrollment_subject (EnrollmentID, SubjectID, SectionID) VALUES (?, ?, ?)")->execute([$enrollmentId, $sid, $sectionId]);
}

$db->prepare("INSERT INTO clinic (CompletionDate, Status, Remarks, StudentID, StaffID, ClearanceStatus)
    VALUES (CURDATE(), 'Pending', 'Awaiting examination', ?, 8, 'Pending')")
    ->execute([$studentId]);

$db->prepare("INSERT INTO security_verification (EnrollmentID, StudentID, Status, Remarks)
    VALUES (?, ?, 'Pending', 'Awaiting clearances')")
    ->execute([$enrollmentId, $studentId]);

$db->commit();
echo "2. Registrar: Study load generated (12 units, BSIT-1A). Enrollment ID #{$enrollmentId}.\n";

// Check live view state after step 2
$chk = $db->query("SELECT * FROM vw_enrollment_checklist WHERE EnrollmentID = {$enrollmentId}")->fetch();
echo "   -> View Status: Dept={$chk['DepartmentStatus']} | Reg={$chk['RegistrarStatus']} | Acct={$chk['AccountingStatus']} | Clinic={$chk['ClinicStatus']} | Security={$chk['SecurityStatus']} | Overall={$chk['OverallStatus']}\n";

// 3. Accounting collects payment
$db->prepare("UPDATE payment SET PaymentStatus = 'Paid', ReceiptNo = 'OR-TEST-001' WHERE PaymentID = ?")->execute([$paymentId]);
echo "3. Accounting: Payment confirmed (OR-TEST-001).\n";

$chk = $db->query("SELECT * FROM vw_enrollment_checklist WHERE EnrollmentID = {$enrollmentId}")->fetch();
echo "   -> View Status: Acct={$chk['AccountingStatus']}\n";

// 4. Clinic performs examination
$db->prepare("UPDATE clinic SET HeightCM = 172.0, WeightKG = 68.0, ClearanceStatus = 'Cleared', Status = 'Completed' WHERE StudentID = ?")->execute([$studentId]);
$clinicRow = $db->query("SELECT HeightCM, WeightKG, BMI, ClearanceStatus FROM clinic WHERE StudentID = {$studentId}")->fetch();
echo "4. Clinic: Cleared! Height={$clinicRow['HeightCM']}cm, Weight={$clinicRow['WeightKG']}kg, Stored Computed BMI={$clinicRow['BMI']}.\n";

$chk = $db->query("SELECT * FROM vw_enrollment_checklist WHERE EnrollmentID = {$enrollmentId}")->fetch();
echo "   -> View Status: Clinic={$chk['ClinicStatus']}\n";

// 5. Security verifies all clearances and confirms enrollment
$db->prepare("UPDATE security_verification SET Status = 'Verified', VerifiedBy = 9, VerificationDate = CURDATE(), Remarks = 'All 4 clearances confirmed' WHERE EnrollmentID = ?")->execute([$enrollmentId]);
$db->prepare("UPDATE enrollment SET Status = 'Enrolled' WHERE EnrollmentID = ?")->execute([$enrollmentId]);
$db->prepare("UPDATE student SET Status = 'Enrolled' WHERE StudentID = ?")->execute([$studentId]);
echo "5. Security Office: Verified and Confirmed!\n";

$finalChk = $db->query("SELECT * FROM vw_enrollment_checklist WHERE EnrollmentID = {$enrollmentId}")->fetch();
echo "\n=== FINAL CHECKLIST STATE ===\n";
echo " - Student No       : {$finalChk['StudentNo']}\n";
echo " - Student Name     : {$finalChk['StudentName']}\n";
echo " - Department       : {$finalChk['DepartmentStatus']}\n";
echo " - Registrar        : {$finalChk['RegistrarStatus']}\n";
echo " - Accounting       : {$finalChk['AccountingStatus']}\n";
echo " - Clinic           : {$finalChk['ClinicStatus']}\n";
echo " - Security         : {$finalChk['SecurityStatus']}\n";
echo " - OVERALL STATUS   : {$finalChk['OverallStatus']}\n";

if ($finalChk['OverallStatus'] === 'CONFIRMED') {
    echo "\n>>> SUCCESS: COMPLETE WORKFLOW VALIDATED AND CONFIRMED! <<<\n";
} else {
    echo "\n>>> FAILED: Overall status was not confirmed. <<<\n";
}
