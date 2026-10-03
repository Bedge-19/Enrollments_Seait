<?php
require_once __DIR__ . '/../config/db.php';
$db = getDBConnection();

$noEnroll = $db->query("SELECT COUNT(*) FROM student s LEFT JOIN enrollment e ON e.StudentID = s.StudentID WHERE e.EnrollmentID IS NULL")->fetchColumn();
echo "Students without enrollment record: $noEnroll\n";

$noUser = $db->query("SELECT COUNT(*) FROM student s LEFT JOIN users u ON u.student_id = s.StudentID WHERE u.id IS NULL")->fetchColumn();
echo "Students without users account: $noUser\n";

// Show the latest 5 registered students and their users accounts
$latest = $db->query("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.Email, u.id as user_id, u.username, u.password_hash, e.EnrollmentID 
    FROM student s 
    LEFT JOIN users u ON u.student_id = s.StudentID 
    LEFT JOIN enrollment e ON e.StudentID = s.StudentID 
    ORDER BY s.StudentID DESC LIMIT 5")->fetchAll();

foreach ($latest as $l) {
    echo "ID: {$l['StudentID']} | StudentNo: {$l['StudentNo']} | Name: {$l['FirstName']} {$l['LastName']} | User: {$l['username']} (User ID: {$l['user_id']}) | EnrollID: {$l['EnrollmentID']}\n";
}
