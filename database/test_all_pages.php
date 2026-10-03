<?php
// database/test_all_pages.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$testNotices = [];
set_error_handler(function($severity, $message, $file, $line) use (&$testNotices) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $testNotices[] = ['msg' => $message, 'file' => $file, 'line' => $line];
    return true;
});

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDBConnection();
$studentId = $db->query("SELECT StudentID FROM student LIMIT 1")->fetchColumn() ?: 1;
$enrollmentId = $db->query("SELECT EnrollmentID FROM enrollment LIMIT 1")->fetchColumn() ?: 1;
$paymentId = $db->query("SELECT PaymentID FROM payment LIMIT 1")->fetchColumn() ?: 1;
$clinicStudentId = $db->query("SELECT c.StudentID FROM clinic c JOIN student s ON s.StudentID = c.StudentID JOIN program p ON p.ProgramID = s.ProgramID LIMIT 1")->fetchColumn() ?: $studentId;
$gradesStudentId = $db->query("SELECT StudentID FROM student_grades LIMIT 1")->fetchColumn() ?: $studentId;
$transfereeStudentId = $db->query("SELECT StudentID FROM credited_subject LIMIT 1")->fetchColumn() ?: $studentId;
$enrolledStudentId = $db->query("SELECT StudentID FROM enrollment LIMIT 1")->fetchColumn() ?: $studentId;

$tests = [
    // Public
    ['file' => 'landing.php', 'role' => null, 'get' => []],
    ['file' => 'login.php', 'role' => null, 'get' => []],
    // Admin
    ['file' => 'admin/index.php', 'role' => 'Admin', 'get' => []],
    ['file' => 'admin/create_user.php', 'role' => 'Admin', 'get' => []],
    ['file' => 'admin/logs.php', 'role' => 'Admin', 'get' => []],
    ['file' => 'admin/users.php', 'role' => 'Admin', 'get' => []],
    // Accounting
    ['file' => 'accounting/index.php', 'role' => 'Accounting', 'get' => []],
    ['file' => 'accounting/payment.php', 'role' => 'Accounting', 'get' => ['id' => $enrolledStudentId, 'enrollment_id' => $enrollmentId]],
    ['file' => 'accounting/receipt.php', 'role' => 'Accounting', 'get' => ['payment_id' => $paymentId]],
    ['file' => 'accounting/print_receipt.php', 'role' => 'Accounting', 'get' => ['payment_id' => $paymentId]],
    // Clinic
    ['file' => 'clinic/index.php', 'role' => 'Clinic', 'get' => []],
    ['file' => 'clinic/medical_record.php', 'role' => 'Clinic', 'get' => ['student_id' => $clinicStudentId]],
    ['file' => 'clinic/print_clearance.php', 'role' => 'Clinic', 'get' => ['student_id' => $clinicStudentId]],
    // Department
    ['file' => 'department/index.php', 'role' => 'Department', 'get' => []],
    ['file' => 'department/register_student.php', 'role' => 'Department', 'get' => []],
    ['file' => 'department/review.php', 'role' => 'Department', 'get' => ['student_id' => $studentId]],
    ['file' => 'department/students.php', 'role' => 'Department', 'get' => []],
    ['file' => 'department/student_grades.php', 'role' => 'Department', 'get' => ['student_id' => $gradesStudentId]],
    ['file' => 'department/print_grades.php', 'role' => 'Department', 'get' => ['student_id' => $gradesStudentId]],
    ['file' => 'department/evaluate_transferee.php', 'role' => 'Department', 'get' => ['student_id' => $transfereeStudentId]],
    // Registrar
    ['file' => 'registrar/index.php', 'role' => 'Registrar', 'get' => []],
    ['file' => 'registrar/evaluate.php', 'role' => 'Registrar', 'get' => ['student_id' => $studentId]],
    ['file' => 'registrar/schedules.php', 'role' => 'Registrar', 'get' => []],
    ['file' => 'registrar/sections.php', 'role' => 'Registrar', 'get' => []],
    // Security
    ['file' => 'security/index.php', 'role' => 'Security Office', 'get' => []],
    ['file' => 'security/verify.php', 'role' => 'Security Office', 'get' => ['student_id' => $enrolledStudentId]],
    // Student
    ['file' => 'student/index.php', 'role' => 'Student', 'get' => []],
    ['file' => 'student/grades.php', 'role' => 'Student', 'get' => []],
    ['file' => 'student/study_load.php', 'role' => 'Student', 'get' => []],
    ['file' => 'student/print_study_load.php', 'role' => 'Student', 'get' => []],
    ['file' => 'student/print_confirmation.php', 'role' => 'Student', 'get' => []],
];

$failures = [];
foreach ($tests as $t) {
    $file = $t['file'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $t['get'];
    $_POST = [];
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $t['role'];
    $_SESSION['username'] = 'testuser';
    $_SESSION['full_name'] = 'Test User';
    $_SESSION['student_id'] = $studentId;
    $_SESSION['staff_id'] = 1;
    
    // Note: header() calls inside scripts might fail if output already started or exit,
    // but in GET mode with correct role and parameters, pages shouldn't redirect
    ob_start();
    try {
        include __DIR__ . '/../' . $file;
        $out = ob_get_clean();
        echo "✓ Checked $file (" . strlen($out) . " bytes)\n";
    } catch (Throwable $ex) {
        ob_end_clean();
        $failures[] = [
            'file' => $file,
            'role' => $t['role'],
            'exception' => get_class($ex),
            'message' => $ex->getMessage(),
            'line' => $ex->getLine(),
            'ex_file' => $ex->getFile()
        ];
        echo "✗ Failed $file: " . $ex->getMessage() . " at " . $ex->getFile() . ":" . $ex->getLine() . "\n";
    }
}

if (!empty($failures)) {
    echo "\n=== FAILURES ENCOUNTERED (" . count($failures) . ") ===\n";
    print_r($failures);
} else {
    echo "\n=== ALL PAGES RENDERED WITHOUT UNCAUGHT EXCEPTIONS! ===\n";
}

if (!empty($testNotices)) {
    echo "\n=== NOTICES / WARNINGS ENCOUNTERED (" . count($testNotices) . ") ===\n";
    print_r($testNotices);
} else {
    echo "\n=== ZERO PHP NOTICES OR WARNINGS! ===\n";
}
