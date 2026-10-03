<?php
// database/fix_student_logins.php
require_once __DIR__ . '/../config/db.php';
$db = getDBConnection();

echo "1. Altering enrollment table to allow NULL for EvaluationID and PaymentID during initial registration...\n";
$db->exec("ALTER TABLE `enrollment` MODIFY `EvaluationID` int NULL DEFAULT NULL, MODIFY `PaymentID` int NULL DEFAULT NULL");
echo "   Table altered successfully.\n";

echo "2. Checking and fixing missing user accounts for students...\n";
$students = $db->query("SELECT s.StudentID, s.StudentNo, s.FirstName, s.LastName, s.Email, u.id as user_id 
    FROM student s 
    LEFT JOIN users u ON u.student_id = s.StudentID 
    WHERE u.id IS NULL")->fetchAll();

$passHash = password_hash('password123', PASSWORD_DEFAULT);
$stmtUsr = $db->prepare("INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `student_id`) VALUES (?, ?, 'Student', 'Active', ?)");
$stmtLog = $db->prepare("INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StudentID`) VALUES (?, ?, 'Student', 'Active', ?)");

$fixedUsers = 0;
foreach ($students as $st) {
    $cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $st['FirstName']));
    $cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $st['LastName']));
    $username = $cleanFirst . '.' . $cleanLast . $st['StudentID'];

    // Check if username collision
    $chk = $db->prepare("SELECT id FROM users WHERE username = ?");
    $chk->execute([$username]);
    if ($chk->fetch()) {
        $username = "student_" . $st['StudentID'];
    }

    $stmtUsr->execute([$username, $passHash, $st['StudentID']]);
    $stmtLog->execute([$username, $passHash, $st['StudentID']]);
    $fixedUsers++;
}
echo "   Created $fixedUsers missing user accounts.\n";

echo "3. Ensuring all newly registered students have active 2026-2027 1st Sem enrollment records...\n";
$enrMissing = $db->query("SELECT s.StudentID 
    FROM student s 
    LEFT JOIN enrollment e ON e.StudentID = s.StudentID AND e.SchoolYear = '2026-2027' AND e.Semester = '1st' 
    WHERE e.EnrollmentID IS NULL AND (s.StudentID > 35200 OR s.Status = 'Enrolled' OR s.Status = 'Applicant')")->fetchAll();

$stmtEnr = $db->prepare("INSERT INTO `enrollment` (`EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`, `EvaluationID`, `PaymentID`) VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, 2, NULL, NULL)");

$fixedEnr = 0;
foreach ($enrMissing as $em) {
    $stmtEnr->execute([$em['StudentID']]);
    $fixedEnr++;
}
echo "   Created $fixedEnr missing enrollment records.\n";

echo "\nVerification of recently registered students:\n";
$recent = $db->query("SELECT s.StudentID, s.StudentNo, s.FirstName, s.LastName, s.Email, u.username, e.EnrollmentID, chk.DepartmentStatus, chk.OverallStatus 
    FROM student s 
    LEFT JOIN users u ON u.student_id = s.StudentID 
    LEFT JOIN enrollment e ON e.StudentID = s.StudentID AND e.SchoolYear = '2026-2027' AND e.Semester = '1st' 
    LEFT JOIN vw_enrollment_checklist chk ON chk.StudentID = s.StudentID 
    ORDER BY s.StudentID DESC LIMIT 6")->fetchAll();

foreach ($recent as $r) {
    echo sprintf(
        " - [%d] %s (%s) | User: %s | EnrollID: %s | DeptStatus: %s | Overall: %s\n",
        $r['StudentID'],
        $r['StudentNo'],
        $r['FirstName'] . ' ' . $r['LastName'],
        $r['username'],
        $r['EnrollmentID'] ?? 'NONE',
        $r['DepartmentStatus'] ?? 'Pending',
        $r['OverallStatus'] ?? 'IN PROGRESS'
    );
}

echo "\nALL DONE!\n";
