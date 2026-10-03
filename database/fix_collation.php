<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=enrollments", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
$pdo->exec("ALTER DATABASE `enrollments` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    if ($t !== 'vw_enrollment_checklist') {
        $pdo->exec("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }
}

$pdo->exec("DROP VIEW IF EXISTS `vw_enrollment_checklist`");
$pdo->exec("CREATE VIEW `vw_enrollment_checklist` AS 
SELECT 
  `e`.`EnrollmentID` AS `EnrollmentID`, 
  `e`.`StudentID` AS `StudentID`, 
  `s`.`StudentNo` AS `StudentNo`, 
  concat(`s`.`LastName`,', ',`s`.`FirstName`) AS `StudentName`, 
  `e`.`SchoolYear` AS `SchoolYear`, 
  `e`.`Semester` AS `Semester`, 
  coalesce(`a`.`Status`,'Pending') AS `DepartmentStatus`, 
  (case when (`ev`.`Status` = 'Approved') then 'Completed' when (`ev`.`Status` is null) then 'Pending' else `ev`.`Status` end) AS `RegistrarStatus`, 
  (case when (`p`.`PaymentStatus` = 'Paid') then 'Completed' when (`p`.`PaymentStatus` is null) then 'Pending' else `p`.`PaymentStatus` end) AS `AccountingStatus`, 
  (case when (`c`.`ClearanceStatus` = 'Cleared') then 'Completed' when (`c`.`ClearanceStatus` is null) then 'Pending' else `c`.`ClearanceStatus` end) AS `ClinicStatus`, 
  (case when (`sv`.`Status` = 'Verified') then 'Completed' when (`sv`.`Status` is null) then 'Pending' else `sv`.`Status` end) AS `SecurityStatus`, 
  (case when (`sv`.`Status` = 'Verified') then 'CONFIRMED' else 'IN PROGRESS' end) AS `OverallStatus` 
FROM ((((((`enrollment` `e` 
  join `student` `s` on((`s`.`StudentID` = `e`.`StudentID`))) 
  left join `admission` `a` on((`a`.`StudentID` = `e`.`StudentID`))) 
  left join `evaluation` `ev` on((`ev`.`EvaluationID` = `e`.`EvaluationID`))) 
  left join `payment` `p` on((`p`.`PaymentID` = `e`.`PaymentID`))) 
  left join `clinic` `c` on((`c`.`StudentID` = `e`.`StudentID`))) 
  left join `security_verification` `sv` on((`sv`.`EnrollmentID` = `e`.`EnrollmentID`)))");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

$confirmed = $pdo->query("SELECT COUNT(*) FROM vw_enrollment_checklist WHERE OverallStatus = 'CONFIRMED'")->fetchColumn();
echo "Recreated view! Confirmed count: {$confirmed}\n";
