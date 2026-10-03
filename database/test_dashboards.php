<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$roles = ["Student", "Department", "Registrar", "Accounting", "Clinic", "Security Office", "Admin"];
foreach ($roles as $r) {
    echo "Testing role $r...\n";
    $_SESSION["user_id"] = 1;
    $_SESSION["role"] = $r;
    $_SESSION["name"] = "Test $r";
    $_SESSION["username"] = strtolower(str_replace(" ", "", $r));
    if ($r === "Student") {
        $db = getDBConnection();
        $st = $db->query("SELECT StudentID FROM student LIMIT 1")->fetchColumn();
        $_SESSION["student_id"] = $st ?: 1;
    }
    
    $dir = match($r) {
        "Student" => "student",
        "Department" => "department",
        "Registrar" => "registrar",
        "Accounting" => "accounting",
        "Clinic" => "clinic",
        "Security Office" => "security",
        "Admin" => "admin",
    };
    
    ob_start();
    include __DIR__ . "/../$dir/index.php";
    $output = ob_get_clean();
    echo "  Rendered " . strlen($output) . " bytes successfully.\n";
}
echo "ALL DASHBOARDS RENDERED WITHOUT ERRORS!\n";
