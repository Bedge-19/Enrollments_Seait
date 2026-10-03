<?php
// seeder.php - High Performance Database Seeder for `enrollments`
// Generates:
// - 35,000+ total students (25,000+ existing, 5,000 new, 5,000 transferees)
// - Departments, Programs, Subjects (10+ per program), Curricula, Instructors, Sections, Schedules
// - Staff and User accounts for all 7 roles
// - Grades, Evaluations, Admissions, Payments, Clinic records, Security Verifications, and Checklist records

set_time_limit(0);
ini_set('memory_limit', '2048M');

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$dbname = 'enrollments';

echo "=== STARTING ENROLLMENT SYSTEM DATABASE SEEDER ===\n";
$startTime = microtime(true);

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("SET autocommit = 0");

// Clear existing data cleanly
$tablesToTruncate = [
    'system_log', 'student_requirement', 'student_grades', 'credited_subject',
    'shifting', 'clearance', 'id_validation', 'register', 'login', 'users',
    'security_verification', 'clinic', 'payment_item', 'blocking',
    'enrollment_subject', 'enrollment', 'payment', 'evaluation_subject',
    'evaluation', 'admission', 'student_profile', 'student', 'student_type',
    'schedule', 'section', 'instructor', 'curriculum_subject', 'curriculum',
    'subject', 'requirement', 'program', 'staff', 'department', 'academic_year'
];

echo "Truncating existing tables...\n";
foreach ($tablesToTruncate as $t) {
    $pdo->exec("TRUNCATE TABLE `{$t}`");
}

echo "1. Seeding Reference Tables (Academic Years, Student Types, Requirements)...\n";
// Academic Years
$pdo->exec("INSERT INTO `academic_year` (`AcademicYearID`, `SchoolYear`, `Semester`, `IsCurrent`) VALUES
    (1, '2025-2026', '2nd', 0),
    (2, '2026-2027', '1st', 1)
");

// Student Types
$pdo->exec("INSERT INTO `student_type` (`StudentTypeID`, `TypeName`) VALUES
    (1, 'New'),
    (2, 'Regular'),
    (3, 'Irregular'),
    (4, 'Shifter'),
    (5, 'Returnee'),
    (6, 'Transferee')
");

// Requirements
$pdo->exec("INSERT INTO `requirement` (`RequirementID`, `RequirementName`, `ApplicableTo`) VALUES
    (1, 'PSA Authenticated Birth Certificate', 'All'),
    (2, 'Form 138 / High School Report Card', 'New'),
    (3, 'Certificate of Good Moral Character', 'New'),
    (4, '2x2 Recent Colored ID Pictures (4 copies)', 'All'),
    (5, 'Honorable Dismissal / Transfer Credential', 'Transferee'),
    (6, 'Official Transcript of Records (TOR)', 'Transferee'),
    (7, 'Certified Course Description / Syllabus', 'Transferee'),
    (8, 'Approved Shifting Form', 'Shifter'),
    (9, 'Readmission / Returnee Clearance Slip', 'Returnee'),
    (10, 'Barangay Clearance', 'All'),
    (11, 'Medical Certificate / Physical Exam Result', 'All')
");

echo "2. Seeding Departments & Programs...\n";
$departments = [
    1 => 'College of Information and Computer Studies',
    2 => 'College of Engineering and Architecture',
    3 => 'College of Business and Accountancy',
    4 => 'College of Nursing and Allied Health',
    5 => 'College of Arts and Sciences',
    6 => 'College of Education',
    7 => 'College of Criminology',
    8 => 'College of Hospitality and Tourism Management'
];

$stmt = $pdo->prepare("INSERT INTO `department` (`departmentID`, `DepartmentName`) VALUES (?, ?)");
foreach ($departments as $id => $name) {
    $stmt->execute([$id, $name]);
}

$programs = [
    1 => ['BS in Information Technology', 'Technology', 1],
    2 => ['BS in Computer Science', 'Technology', 1],
    3 => ['BS in Computer Engineering', 'Engineering', 2],
    4 => ['BS in Civil Engineering', 'Engineering', 2],
    5 => ['BS in Business Administration - Financial Management', 'Business', 3],
    6 => ['BS in Business Administration - Marketing Management', 'Business', 3],
    7 => ['BS in Accountancy', 'Business', 3],
    8 => ['BS in Nursing', 'Health Sciences', 4],
    9 => ['Bachelor of Secondary Education', 'Education', 6],
    10 => ['BS in Criminology', 'Criminal Justice', 7],
    11 => ['BS in Hospitality Management', 'Hospitality', 8]
];

$stmtProg = $pdo->prepare("INSERT INTO `program` (`ProgramID`, `ProgramName`, `Cluster`, `DepartmentID`) VALUES (?, ?, ?, ?)");
foreach ($programs as $id => $p) {
    $stmtProg->execute([$id, $p[0], $p[1], $p[2]]);
}

echo "3. Seeding Staff and Staff Logins for All 7 Roles...\n";
$hashedPassword = password_hash('password123', PASSWORD_DEFAULT);

$staffData = [
    // StaffID, LastName, FirstName, MiddleName, Email, ContactNo, DeptID, RoleID, Username
    [1, 'Santos', 'Alexander', 'Cruz', 'admin@enrollments.edu', '09171110001', 1, 'Admin', 'admin'],
    [2, 'Reyes', 'Maria Elena', 'Bautista', 'cics.chair@enrollments.edu', '09171110002', 1, 'Department', 'dept_cics'],
    [3, 'Garcia', 'Roberto', 'Navarro', 'cea.chair@enrollments.edu', '09171110003', 2, 'Department', 'dept_cea'],
    [4, 'Mendoza', 'Corazon', 'Diaz', 'cba.chair@enrollments.edu', '09171110004', 3, 'Department', 'dept_cba'],
    [5, 'Fernandez', 'Lourdes', 'Flores', 'cnahs.chair@enrollments.edu', '09171110005', 4, 'Department', 'dept_cnahs'],
    [6, 'Torres', 'Victoria', 'Salazar', 'registrar@enrollments.edu', '09171110006', 1, 'Registrar', 'registrar'],
    [7, 'Aquino', 'Benjamin', 'Castro', 'accounting@enrollments.edu', '09171110007', 3, 'Accounting', 'accounting'],
    [8, 'Villanueva', 'Dr. Patricia', 'Ramos', 'clinic@enrollments.edu', '09171110008', 4, 'Clinic', 'clinic'],
    [9, 'del Rosario', 'Captain Mario', 'Tan', 'security@enrollments.edu', '09171110009', 1, 'Security Office', 'security']
];

$stmtStaff = $pdo->prepare("INSERT INTO `staff` (`StaffID`, `LastName`, `FirstName`, `MiddleName`, `Email`, `ContactNo`, `DepartmentID`, `RoleID`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmtLogin = $pdo->prepare("INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StaffID`, `StudentID`) VALUES (?, ?, 'Staff', 'Active', ?, NULL)");
$stmtUser = $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `staff_id`, `student_id`) VALUES (?, ?, ?, 'Active', ?, NULL)");

foreach ($staffData as $s) {
    $stmtStaff->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7]]);
    $stmtLogin->execute([$s[8], $hashedPassword, $s[0]]);
    $stmtUser->execute([$s[8], $hashedPassword, $s[7], $s[0]]);
}

echo "4. Seeding Instructors...\n";
$firstNames = ['John', 'Paul', 'George', 'Ringo', 'James', 'David', 'Michael', 'Robert', 'William', 'Richard', 'Joseph', 'Thomas', 'Charles', 'Daniel', 'Matthew', 'Mark', 'Luke', 'Mary', 'Jennifer', 'Patricia', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen', 'Nancy', 'Lisa', 'Betty'];
$lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Flores', 'Gonzales', 'Bautista', 'Villanueva', 'Ramos', 'Castro', 'Rivera', 'Aquino', 'Navarro', 'Salazar', 'Mercado', 'Valdez', 'Pascual', 'Ocampo', 'Tolentino', 'Cruz', 'Morales', 'Soriano', 'Gomez', 'Lim', 'Tan', 'Chua', 'Sy', 'Castillo', 'Espiritu'];

$stmtInst = $pdo->prepare("INSERT INTO `instructor` (`InstructorID`, `EmployeeID`, `LastName`, `FirstName`, `MiddleName`, `DepartmentID`, `Status`) VALUES (?, ?, ?, ?, ?, ?, 'Active')");

$instId = 1;
for ($d = 1; $d <= 8; $d++) {
    for ($i = 1; $i <= 10; $i++) {
        $empId = sprintf("EMP-%02d-%03d", $d, $i);
        $fn = $firstNames[($instId * 3) % count($firstNames)];
        $ln = $lastNames[($instId * 7) % count($lastNames)];
        $mn = $lastNames[($instId * 5) % count($lastNames)];
        $stmtInst->execute([$instId, $empId, $ln, $fn, $mn, $d]);
        $instId++;
    }
}

echo "5. Seeding Subjects, Curricula and Curriculum Subjects for All Programs...\n";
// Define realistic 4-year subject templates (at least 12 subjects per program across 1st to 4th year)
$programSubjects = [
    // BSIT
    1 => [
        ['IT101', 'Introduction to Computing', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['IT102', 'Computer Programming 1', 3.0, 'Major', 'Lecture/Laboratory', 1, '1st', 0],
        ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['GE102', 'Understanding the Self', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['IT103', 'Computer Programming 2', 3.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['IT104', 'Data Structures and Algorithms', 3.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['GE103', 'Readings in Philippine History', 3.0, 'Minor', 'Lecture', 1, '2nd', 1],
        ['GE104', 'Mathematics in the Modern World', 3.0, 'Minor', 'Lecture', 1, '2nd', 1],
        ['IT201', 'Information Management & Database', 3.0, 'Major', 'Lecture/Laboratory', 2, '1st', 0],
        ['IT202', 'Networking 1 - Fundamentals', 3.0, 'Major', 'Lecture/Laboratory', 2, '1st', 0],
        ['IT203', 'Web Systems and Technologies', 3.0, 'Major', 'Lecture/Laboratory', 2, '2nd', 0],
        ['IT204', 'Object-Oriented Programming', 3.0, 'Major', 'Lecture/Laboratory', 2, '2nd', 0],
        ['IT301', 'Systems Integration and Architecture', 3.0, 'Major', 'Lecture', 3, '1st', 0],
        ['IT302', 'Information Assurance & Security', 3.0, 'Major', 'Lecture', 3, '2nd', 0],
        ['IT401', 'Capstone Project and Research 1', 3.0, 'Major', 'Lecture', 4, '1st', 0],
        ['IT402', 'Capstone Project and Research 2', 3.0, 'Major', 'Lecture', 4, '2nd', 0]
    ],
    // BSCS
    2 => [
        ['CS101', 'Introduction to Computer Science', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['CS102', 'Discrete Structures 1', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['GE102', 'Understanding the Self', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['CS103', 'Object-Oriented Programming with Java', 3.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['CS104', 'Discrete Structures 2', 3.0, 'Major', 'Lecture', 1, '2nd', 0],
        ['GE103', 'Readings in Philippine History', 3.0, 'Minor', 'Lecture', 1, '2nd', 1],
        ['CS201', 'Design and Analysis of Algorithms', 3.0, 'Major', 'Lecture', 2, '1st', 0],
        ['CS202', 'Automata and Language Theory', 3.0, 'Major', 'Lecture', 2, '2nd', 0],
        ['CS301', 'Artificial Intelligence', 3.0, 'Major', 'Lecture/Laboratory', 3, '1st', 0],
        ['CS401', 'Thesis Project 1', 3.0, 'Major', 'Lecture', 4, '1st', 0],
        ['CS402', 'Thesis Project 2', 3.0, 'Major', 'Lecture', 4, '2nd', 0]
    ],
    // BSCpE
    3 => [
        ['CPE101', 'Engineering Mechanics', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['CPE102', 'Calculus 1', 4.0, 'Major', 'Lecture', 1, '1st', 0],
        ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['CPE103', 'Calculus 2', 4.0, 'Major', 'Lecture', 1, '2nd', 0],
        ['CPE104', 'Circuits 1', 3.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['CPE201', 'Digital Logic Design', 3.0, 'Major', 'Lecture/Laboratory', 2, '1st', 0],
        ['CPE202', 'Microprocessors & Microcontrollers', 3.0, 'Major', 'Lecture/Laboratory', 2, '2nd', 0],
        ['CPE301', 'Embedded Systems', 3.0, 'Major', 'Lecture/Laboratory', 3, '1st', 0],
        ['CPE302', 'Computer Architecture & Org', 3.0, 'Major', 'Lecture', 3, '2nd', 0],
        ['CPE401', 'CpE Practice and Design 1', 3.0, 'Major', 'Lecture', 4, '1st', 0]
    ],
    // BSBA-FM
    5 => [
        ['BA101', 'Principles of Management', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['BA102', 'Financial Accounting 1', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['GE104', 'Mathematics in the Modern World', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['BA103', 'Managerial Economics', 3.0, 'Major', 'Lecture', 1, '2nd', 0],
        ['BA104', 'Financial Management Principles', 3.0, 'Major', 'Lecture', 1, '2nd', 0],
        ['BA201', 'Capital Markets and Investments', 3.0, 'Major', 'Lecture', 2, '1st', 0],
        ['BA202', 'Banking and Financial Institutions', 3.0, 'Major', 'Lecture', 2, '2nd', 0],
        ['BA301', 'Strategic Financial Management', 3.0, 'Major', 'Lecture', 3, '1st', 0],
        ['BA401', 'Feasibility Study & Research', 3.0, 'Major', 'Lecture', 4, '1st', 0]
    ],
    // BSN (Nursing)
    8 => [
        ['NUR101', 'Theoretical Foundations in Nursing', 3.0, 'Major', 'Lecture', 1, '1st', 0],
        ['NUR102', 'Anatomy and Physiology', 4.0, 'Major', 'Lecture/Laboratory', 1, '1st', 0],
        ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
        ['NUR103', 'Health Assessment', 3.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['NUR104', 'Fundamentals of Nursing Practice', 4.0, 'Major', 'Lecture/Laboratory', 1, '2nd', 0],
        ['NUR201', 'Microbiology and Parasitology', 3.0, 'Major', 'Lecture/Laboratory', 2, '1st', 0],
        ['NUR202', 'Pharmacology for Nursing', 3.0, 'Major', 'Lecture', 2, '2nd', 0],
        ['NUR301', 'Care of Mother, Child & Adolescent', 6.0, 'Major', 'Lecture/Laboratory', 3, '1st', 0],
        ['NUR302', 'Nursing Research 1', 3.0, 'Major', 'Lecture', 3, '2nd', 0],
        ['NUR401', 'Intensive Clinical Practicum', 6.0, 'Major', 'Lecture/Laboratory', 4, '1st', 0]
    ]
];

// Fallback generic subjects for other programs (4, 6, 7, 9, 10, 11)
for ($pid = 1; $pid <= 11; $pid++) {
    if (!isset($programSubjects[$pid])) {
        $prefix = "PRG" . $pid;
        $programSubjects[$pid] = [
            [$prefix . '-101', 'Fundamental Course 1', 3.0, 'Major', 'Lecture', 1, '1st', 0],
            [$prefix . '-102', 'Fundamental Course 2', 3.0, 'Major', 'Lecture', 1, '1st', 0],
            ['GE101', 'Purposive Communication', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
            ['GE102', 'Understanding the Self', 3.0, 'Minor', 'Lecture', 1, '1st', 1],
            [$prefix . '-103', 'Intermediate Course 1', 3.0, 'Major', 'Lecture', 1, '2nd', 0],
            [$prefix . '-104', 'Applied Methodology', 3.0, 'Major', 'Lecture', 1, '2nd', 0],
            ['GE103', 'Readings in Philippine History', 3.0, 'Minor', 'Lecture', 1, '2nd', 1],
            [$prefix . '-201', 'Core Discipline Course 1', 3.0, 'Major', 'Lecture', 2, '1st', 0],
            [$prefix . '-202', 'Core Discipline Course 2', 3.0, 'Major', 'Lecture', 2, '2nd', 0],
            [$prefix . '-301', 'Advanced Discipline Course', 3.0, 'Major', 'Lecture', 3, '1st', 0],
            [$prefix . '-401', 'Undergraduate Research Project', 3.0, 'Major', 'Lecture', 4, '1st', 0]
        ];
    }
}

$stmtSubj = $pdo->prepare("INSERT INTO `subject` (`SubjectID`, `SubjectCode`, `SubjectTitle`, `Units`, `SubjectType`, `Classification`, `ProgramID`) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtCurr = $pdo->prepare("INSERT INTO `curriculum` (`CurriculumID`, `ProgramID`, `EffectiveSchoolYear`) VALUES (?, ?, '2025-2026')");
$stmtCurrSubj = $pdo->prepare("INSERT INTO `curriculum_subject` (`CurriculumID`, `SubjectID`, `YearLevel`, `Semester`, `PrerequisiteSubjectID`, `IsMinor`) VALUES (?, ?, ?, ?, NULL, ?)");

$subjId = 1;
$currId = 1;
$createdSubjectsMap = []; // [code_pid => id]

foreach ($programSubjects as $pid => $subjs) {
    $stmtCurr->execute([$currId, $pid]);
    
    foreach ($subjs as $s) {
        $code = $s[0];
        $title = $s[1];
        $units = $s[2];
        $type = $s[3];
        $class = $s[4];
        $yr = $s[5];
        $sem = $s[6];
        $isMinor = $s[7];

        $stmtSubj->execute([$subjId, $code, $title, $units, $type, $class, $pid]);
        $stmtCurrSubj->execute([$currId, $subjId, $yr, $sem, $isMinor]);
        $createdSubjectsMap[$pid][] = [
            'id' => $subjId,
            'code' => $code,
            'title' => $title,
            'units' => $units,
            'year' => $yr,
            'sem' => $sem,
            'isMinor' => $isMinor
        ];
        $subjId++;
    }
    $currId++;
}

echo "6. Seeding Sections and Schedules...\n";
$stmtSec = $pdo->prepare("INSERT INTO `section` (`SectionID`, `SectionCode`, `YearLevel`, `ProgramID`) VALUES (?, ?, ?, ?)");
$stmtSched = $pdo->prepare("INSERT INTO `schedule` (`ScheduleID`, `Day`, `TimeStart`, `TimeEnd`, `SectionID`, `SubjectID`, `InstructorID`) VALUES (?, ?, ?, ?, ?, ?, ?)");

$secId = 1;
$schedId = 1;
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$timeSlots = [
    ['08:00:00', '09:30:00'],
    ['09:30:00', '11:00:00'],
    ['11:00:00', '12:30:00'],
    ['13:00:00', '14:30:00'],
    ['14:30:00', '16:00:00'],
    ['16:00:00', '17:30:00']
];

$sectionListByProgram = [];

for ($pid = 1; $pid <= 11; $pid++) {
    for ($yr = 1; $yr <= 4; $yr++) {
        foreach (['A', 'B'] as $secLetter) {
            $progCode = explode(' ', $programs[$pid][0])[0];
            $secCode = "{$progCode}-{$yr}{$secLetter}";
            $stmtSec->execute([$secId, $secCode, $yr, $pid]);
            $sectionListByProgram[$pid][$yr][] = $secId;

            // Generate schedules for subjects belonging to this year & 1st sem
            if (isset($createdSubjectsMap[$pid])) {
                $slotIdx = 0;
                foreach ($createdSubjectsMap[$pid] as $cs) {
                    if ($cs['year'] == $yr && $cs['sem'] == '1st') {
                        $day = $days[$slotIdx % count($days)];
                        $slot = $timeSlots[$slotIdx % count($timeSlots)];
                        $inst = ($secId + $slotIdx) % 80 + 1; // pick active instructor
                        $stmtSched->execute([$schedId, $day, $slot[0], $slot[1], $secId, $cs['id'], $inst]);
                        $schedId++;
                        $slotIdx++;
                    }
                }
            }
            $secId++;
        }
    }
}

echo "7. Generating 35,000+ Students (25,000+ Existing, 5,000 New, 5,000 Transferees)...\n";

$cities = ['Manila', 'Quezon City', 'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong', 'Marikina', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'San Juan', 'Taguig', 'Valenzuela', 'Cavite', 'Laguna', 'Rizal', 'Bulacan'];
$schools = ['Polytechnic University of the Philippines', 'University of the Philippines', 'University of Santo Tomas', 'Ateneo de Manila University', 'De La Salle University', 'Far Eastern University', 'Adamson University', 'National University', 'Mapua University', 'Technological University of the Philippines', 'University of the East', 'Pamantasan ng Lungsod ng Maynila'];

$totalExisting = 25200;
$totalNew = 5000;
$totalTransferee = 5000;
$totalStudents = $totalExisting + $totalNew + $totalTransferee; // 35,200 total

$batchSize = 1000;

// Reusable prepared inserts
$insertStudentSql = "INSERT INTO `student` (`StudentID`, `StudentNo`, `LastName`, `FirstName`, `MiddleName`, `BirthDate`, `Sex`, `Address`, `ContactNo`, `Email`, `StudentTypeID`, `ProgramID`, `Status`) VALUES ";
$insertProfileSql = "INSERT INTO `student_profile` (`ProfileID`, `Address`, `ContactNo`, `GuardianName`, `GuardianContactNo`, `PreviousSchoolName`, `PreviousProgram`, `LastYearLevelCompleted`, `GWA`, `StudentID`) VALUES ";
$insertUserSql = "INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `student_id`, `staff_id`) VALUES ";
$insertLoginSql = "INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StudentID`, `StaffID`) VALUES ";

$studentValues = [];
$profileValues = [];
$userValues = [];
$loginValues = [];

$currentStudentId = 1;

for ($i = 1; $i <= $totalStudents; $i++) {
    $currentStudentId = $i;
    $fn = $firstNames[$i % count($firstNames)];
    $ln = $lastNames[($i * 3) % count($lastNames)];
    $mn = $lastNames[($i * 5) % count($lastNames)];
    $sex = ($i % 2 == 0) ? 'Female' : 'Male';
    $city = $cities[$i % count($cities)];
    $address = (100 + ($i % 800)) . " " . $lastNames[($i * 2) % count($lastNames)] . " St., " . $city;
    $contact = "09" . sprintf("%09d", 170000000 + ($i % 89999999));
    $bday = date('Y-m-d', strtotime('2000-01-01 +' . ($i % 2500) . ' days'));
    $gwa = sprintf("%.2f", 1.25 + (($i % 150) / 100));

    $guardianFn = $firstNames[($i + 5) % count($firstNames)];
    $guardianLn = $ln;
    $guardianContact = "09" . sprintf("%09d", 180000000 + ($i % 89999999));
    $prevSchool = $schools[$i % count($schools)];

    if ($i <= $totalExisting) {
        // Existing students (Regular, Irregular, Shifter, Returnee)
        $typeRoll = $i % 100;
        if ($typeRoll < 45) {
            $studentTypeId = 2; // Regular
        } elseif ($typeRoll < 70) {
            $studentTypeId = 3; // Irregular
        } elseif ($typeRoll < 85) {
            $studentTypeId = 4; // Shifter
        } else {
            $studentTypeId = 5; // Returnee
        }
        $status = 'Enrolled';
        $studentNo = sprintf("2024-%05d", $i);
        $programId = ($i % 11) + 1;
        $prevProg = $programs[$programId][0];
        $yrCompleted = "Year " . (($i % 3) + 1);
    } elseif ($i <= $totalExisting + $totalNew) {
        // New students (5,000)
        $studentTypeId = 1; // New
        $status = 'Applicant';
        $studentNo = sprintf("APP-2026-%05d", $i - $totalExisting);
        $programId = (($i - $totalExisting) % 11) + 1;
        $prevProg = "Senior High School - " . (($i % 2 == 0) ? 'STEM' : 'ABM');
        $yrCompleted = "Grade 12";
    } else {
        // Transferees (5,000)
        $studentTypeId = 6; // Transferee
        $status = 'Applicant';
        $studentNo = sprintf("TRF-2026-%05d", $i - ($totalExisting + $totalNew));
        $programId = (($i - ($totalExisting + $totalNew)) % 11) + 1;
        $prevProg = "BS in Business Management";
        $yrCompleted = "2nd Year";
    }

    $email = strtolower($fn . "." . str_replace(' ', '', $ln) . $i . "@student.enrollments.edu");
    $username = "student_" . $i;

    $studentValues[] = sprintf(
        "(%d, %s, %s, %s, %s, %s, '%s', %s, '%s', %s, %d, %d, '%s')",
        $i,
        $pdo->quote($studentNo),
        $pdo->quote($ln),
        $pdo->quote($fn),
        $pdo->quote($mn),
        $pdo->quote($bday),
        $sex,
        $pdo->quote($address),
        $contact,
        $pdo->quote($email),
        $studentTypeId,
        $programId,
        $status
    );

    $profileValues[] = sprintf(
        "(%d, %s, '%s', %s, '%s', %s, %s, %s, %s, %d)",
        $i,
        $pdo->quote($address),
        $contact,
        $pdo->quote($guardianFn . " " . $guardianLn),
        $guardianContact,
        $pdo->quote($prevSchool),
        $pdo->quote($prevProg),
        $pdo->quote($yrCompleted),
        $gwa,
        $i
    );

    // Create user login accounts for selected students (e.g. first 5,000 + sample across types)
    if ($i <= 5000 || ($i % 10 == 0)) {
        $userValues[] = sprintf(
            "(%s, %s, 'Student', 'Active', %d, NULL)",
            $pdo->quote($username),
            $pdo->quote($hashedPassword),
            $i
        );
        $loginValues[] = sprintf(
            "(%s, %s, 'Student', 'Active', %d, NULL)",
            $pdo->quote($username),
            $pdo->quote($hashedPassword),
            $i
        );
    }

    if (count($studentValues) >= $batchSize) {
        $pdo->exec($insertStudentSql . implode(',', $studentValues));
        $pdo->exec($insertProfileSql . implode(',', $profileValues));
        $studentValues = [];
        $profileValues = [];
    }

    if (count($userValues) >= $batchSize) {
        $pdo->exec($insertUserSql . implode(',', $userValues));
        $pdo->exec($insertLoginSql . implode(',', $loginValues));
        $userValues = [];
        $loginValues = [];
    }

    if ($i % 5000 == 0) {
        echo "   Generated {$i} / {$totalStudents} student master records...\n";
    }
}

if (!empty($studentValues)) {
    $pdo->exec($insertStudentSql . implode(',', $studentValues));
    $pdo->exec($insertProfileSql . implode(',', $profileValues));
}
if (!empty($userValues)) {
    $pdo->exec($insertUserSql . implode(',', $userValues));
    $pdo->exec($insertLoginSql . implode(',', $loginValues));
}

echo "8. Seeding Admissions for New Students & Applicants...\n";
// Create admission records for new students (5,000)
$admissionValues = [];
$startNew = $totalExisting + 1;
$endNew = $totalExisting + $totalNew;

for ($i = $startNew; $i <= $endNew; $i++) {
    // 3,000 Pending, 2,000 Approved
    $status = ($i - $startNew < 3000) ? 'Pending' : 'Approved';
    $subDate = date('Y-m-d', strtotime('2026-08-01 +' . ($i % 20) . ' days'));
    $appDate = ($status == 'Approved') ? date('Y-m-d', strtotime($subDate . ' + 2 days')) : $subDate;
    $staff = ($i % 4) + 2; // CICS, CEA, CBA, CNAHS chairs
    $remarks = ($status == 'Approved') ? 'Documents verified complete' : 'Under initial document evaluation';

    $admissionValues[] = sprintf(
        "(%s, %s, '%s', %s, %d, %d)",
        $pdo->quote($subDate),
        $pdo->quote($appDate),
        $status,
        $pdo->quote($remarks),
        $i,
        $staff
    );

    if (count($admissionValues) >= $batchSize) {
        $pdo->exec("INSERT INTO `admission` (`SubmissionDate`, `ApprovalDate`, `Status`, `Remarks`, `StudentID`, `StaffID`) VALUES " . implode(',', $admissionValues));
        $admissionValues = [];
    }
}
if (!empty($admissionValues)) {
    $pdo->exec("INSERT INTO `admission` (`SubmissionDate`, `ApprovalDate`, `Status`, `Remarks`, `StudentID`, `StaffID`) VALUES " . implode(',', $admissionValues));
}

echo "9. Seeding Transferee Subject Crediting Records (5,000 Transferees)...\n";
$creditValues = [];
$startTrf = $totalExisting + $totalNew + 1;
$endTrf = $totalStudents;

$transfereeSubjectNames = [
    'General English & Communication', 'College Algebra', 'Basic Computing Concepts',
    'Philippine Governance & Constitution', 'General Psychology', 'Environmental Science',
    'Advanced Corporate Accounting', 'Database Theory & Design', 'Applied Physics'
];

for ($i = $startTrf; $i <= $endTrf; $i++) {
    $school = $schools[$i % count($schools)];
    $targetSubjectId = (($i % 10) + 1); // maps to available subject ID
    $isCredited = ($i % 3 != 0) ? 1 : 0; // 66% credited, 33% uncredited
    $origGrade = ($isCredited) ? sprintf("%.2f", 1.25 + (($i % 75) / 100)) : '3.25';
    $status = ($isCredited) ? 'Approved' : 'Rejected';
    $reason = ($isCredited) ? NULL : (($i % 2 == 0) ? 'Course syllabus does not match minimum university standard' : 'Grade below minimum credit rating of 2.50');
    $origSubj = $transfereeSubjectNames[$i % count($transfereeSubjectNames)];
    $evalDate = '2026-08-15';
    $evalStaff = 2; // CICS Chair

    $creditValues[] = sprintf(
        "('Transferee', %s, %s, %s, '%s', %s, %d, %d, 3.0, %d, %s, %d, '%s')",
        $pdo->quote($origSubj),
        $pdo->quote($origGrade),
        $pdo->quote($school),
        $status,
        $pdo->quote($isCredited ? 'Credited via curriculum equivalent' : $reason),
        $i,
        $targetSubjectId,
        $isCredited,
        $reason ? $pdo->quote($reason) : 'NULL',
        $evalStaff,
        $evalDate
    );

    if (count($creditValues) >= $batchSize) {
        $pdo->exec("INSERT INTO `credited_subject` (`SourceType`, `OriginalSubjectName`, `OriginalGrade`, `PreviousInstitution`, `Status`, `Remarks`, `StudentID`, `SubjectID`, `EquivalentUnits`, `IsCredited`, `ReasonIfNotCredited`, `EvaluatedBy`, `EvaluationDate`) VALUES " . implode(',', $creditValues));
        $creditValues = [];
    }
}
if (!empty($creditValues)) {
    $pdo->exec("INSERT INTO `credited_subject` (`SourceType`, `OriginalSubjectName`, `OriginalGrade`, `PreviousInstitution`, `Status`, `Remarks`, `StudentID`, `SubjectID`, `EquivalentUnits`, `IsCredited`, `ReasonIfNotCredited`, `EvaluatedBy`, `EvaluationDate`) VALUES " . implode(',', $creditValues));
}

echo "10. Seeding Historical Grades for Existing Students...\n";
$gradeValues = [];
// Generate grades for first 8,000 existing students for past terms
for ($i = 1; $i <= 8000; $i++) {
    $pid = ($i % 11) + 1;
    if (isset($createdSubjectsMap[$pid])) {
        foreach (array_slice($createdSubjectsMap[$pid], 0, 4) as $s) {
            $isPass = ($i % 10 != 0); // 90% pass, 10% fail
            $grade = $isPass ? sprintf("%.2f", 1.25 + (($i % 150) / 100)) : '5.00';
            $gradeStatus = $isPass ? 'Passed' : 'Failed';
            $encodedBy = 2; // Staff
            $dateEnc = '2026-05-30';

            $gradeValues[] = sprintf(
                "(%d, %d, 1, %s, '%s', %d, '%s')",
                $i,
                $s['id'],
                $grade,
                $gradeStatus,
                $encodedBy,
                $dateEnc
            );

            if (count($gradeValues) >= $batchSize) {
                $pdo->exec("INSERT INTO `student_grades` (`StudentID`, `SubjectID`, `AcademicYearID`, `FinalGrade`, `GradeStatus`, `EncodedBy`, `DateEncoded`) VALUES " . implode(',', $gradeValues));
                $gradeValues = [];
            }
        }
    }
}
if (!empty($gradeValues)) {
    $pdo->exec("INSERT INTO `student_grades` (`StudentID`, `SubjectID`, `AcademicYearID`, `FinalGrade`, `GradeStatus`, `EncodedBy`, `DateEncoded`) VALUES " . implode(',', $gradeValues));
}

echo "11. Seeding Active Enrollments & Complete Pipeline Stages for Testing...\n";
// Create 3,000 live enrollment workflows representing all pipeline stages
// 1. Department Approved (Admission Approved)
// 2. Registrar Evaluation (Pending / Approved)
// 3. Accounting Payment (Pending / Paid)
// 4. Clinic (Pending / Cleared)
// 5. Security Verification (Pending / Verified -> Confirmed)

$evalValues = [];
$payValues = [];
$enrValues = [];
$clinicValues = [];
$secValues = [];
$payItemValues = [];

for ($i = 1; $i <= 3000; $i++) {
    $studentId = $i;
    $stage = $i % 5; 
    // Stage 0: Completed & Confirmed
    // Stage 1: Department & Registrar Done, Accounting Pending (Unpaid)
    // Stage 2: Accounting Paid, Clinic Pending
    // Stage 3: Clinic Cleared, Security Pending
    // Stage 4: Registrar Pending Evaluation

    $evalStatus = ($stage == 4) ? 'Pending' : 'Approved';
    $evalDate = '2026-08-10';
    $evalStaff = 6; // Registrar

    $paymentStatus = ($stage == 1 || $stage == 4) ? 'Pending' : 'Paid';
    $amount = 15500.00;
    $receiptNo = ($paymentStatus == 'Paid') ? sprintf("OR-2026-%05d", $i) : '';
    $payDate = '2026-08-12';
    $payStaff = 7; // Accounting

    $clinicStatus = ($stage == 0 || $stage == 3) ? 'Cleared' : 'Pending';
    $height = 165.0 + ($i % 25);
    $weight = 55.0 + ($i % 30);
    $clinicDate = '2026-08-14';
    $clinicStaff = 8; // Clinic

    $secStatus = ($stage == 0) ? 'Verified' : 'Pending';
    $secDate = ($secStatus == 'Verified') ? '2026-08-16' : NULL;
    $secStaff = ($secStatus == 'Verified') ? 9 : NULL;

    // Admission record
    $admissionValues[] = sprintf(
        "('2026-08-01', '2026-08-02', 'Approved', 'Cleared for enrollment', %d, 2)",
        $studentId
    );

    // Evaluation
    $evalValues[] = sprintf(
        "(%d, '%s', 'Evaluated study load', '%s', %d, %d)",
        $i,
        $evalDate,
        $evalStatus,
        $studentId,
        $evalStaff
    );

    // Payment
    $payValues[] = sprintf(
        "(%d, %.2f, '%s', %s, '%s', %d, %d)",
        $i,
        $amount,
        $payDate,
        $pdo->quote($receiptNo),
        $paymentStatus,
        $studentId,
        $payStaff
    );

    // Enrollment
    $enrStatus = ($secStatus == 'Verified') ? 'Enrolled' : 'Pending';
    $enrValues[] = sprintf(
        "(%d, '2026-08-10', '2026-2027', '1st', '%s', %d, 6, %d, %d)",
        $i,
        $enrStatus,
        $studentId,
        $i, // EvaluationID
        $i  // PaymentID
    );

    // Clinic
    $clinicValues[] = sprintf(
        "('%s', '%s', 'Regular physical checkup completed', %d, %d, %.2f, %.2f, 'No chronic illness', '%s')",
        $clinicDate,
        ($clinicStatus == 'Cleared') ? 'Completed' : 'Pending',
        $studentId,
        $clinicStaff,
        $height,
        $weight,
        $clinicStatus
    );

    // Security Verification
    $secValues[] = sprintf(
        "(%d, %d, '%s', %s, %s, %s)",
        $i, // EnrollmentID
        $studentId,
        $secStatus,
        $secStaff ? $secStaff : 'NULL',
        $secDate ? $pdo->quote($secDate) : 'NULL',
        $pdo->quote($secStatus == 'Verified' ? 'All office clearances verified. Confirmed enrolled.' : 'Awaiting final clearance')
    );

    // Payment item breakdown
    if ($paymentStatus == 'Paid') {
        $payItemValues[] = sprintf("(%d, 'Tuition Fee', 'Enrolled lecture/laboratory units', 12000.00)", $i);
        $payItemValues[] = sprintf("(%d, 'Miscellaneous Fee', 'Library, Athletics, Medical, Lab fee', 3500.00)", $i);
    }
}

if (!empty($admissionValues)) {
    $pdo->exec("INSERT INTO `admission` (`SubmissionDate`, `ApprovalDate`, `Status`, `Remarks`, `StudentID`, `StaffID`) VALUES " . implode(',', $admissionValues));
}
if (!empty($evalValues)) {
    $pdo->exec("INSERT INTO `evaluation` (`EvaluationID`, `EvaluationDate`, `Remarks`, `Status`, `StudentID`, `StaffID`) VALUES " . implode(',', $evalValues));
}
if (!empty($payValues)) {
    $pdo->exec("INSERT INTO `payment` (`PaymentID`, `Amount`, `PaymentDate`, `ReceiptNo`, `PaymentStatus`, `StudentID`, `StaffID`) VALUES " . implode(',', $payValues));
}
if (!empty($enrValues)) {
    $pdo->exec("INSERT INTO `enrollment` (`EnrollmentID`, `EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`, `EvaluationID`, `PaymentID`) VALUES " . implode(',', $enrValues));
}
if (!empty($clinicValues)) {
    $pdo->exec("INSERT INTO `clinic` (`CompletionDate`, `Status`, `Remarks`, `StudentID`, `StaffID`, `HeightCM`, `WeightKG`, `MedicalHistory`, `ClearanceStatus`) VALUES " . implode(',', $clinicValues));
}
if (!empty($secValues)) {
    $pdo->exec("INSERT INTO `security_verification` (`EnrollmentID`, `StudentID`, `Status`, `VerifiedBy`, `VerificationDate`, `Remarks`) VALUES " . implode(',', $secValues));
}
if (!empty($payItemValues)) {
    $pdo->exec("INSERT INTO `payment_item` (`PaymentID`, `FeeType`, `Description`, `Amount`) VALUES " . implode(',', $payItemValues));
}

// Enrollment Subjects & Blocking for enrolled students
$enrSubjValues = [];
$blockingValues = [];
for ($i = 1; $i <= 3000; $i++) {
    $pid = ($i % 11) + 1;
    $sec = (isset($sectionListByProgram[$pid][1][0])) ? $sectionListByProgram[$pid][1][0] : 1;
    $blockingValues[] = sprintf("('2026-08-10', %d, %d)", $i, $sec);

    if (isset($createdSubjectsMap[$pid])) {
        foreach (array_slice($createdSubjectsMap[$pid], 0, 4) as $s) {
            $enrSubjValues[] = sprintf("(%d, %d, %d)", $i, $s['id'], $sec);
        }
    }
}
$pdo->exec("INSERT INTO `blocking` (`BlockingDate`, `EnrollmentID`, `SectionID`) VALUES " . implode(',', $blockingValues));
$pdo->exec("INSERT INTO `enrollment_subject` (`EnrollmentID`, `SubjectID`, `SectionID`) VALUES " . implode(',', $enrSubjValues));

// Seed sample audit logs
$pdo->exec("INSERT INTO `system_log` (`User`, `Role`, `Action`, `Module`, `TableAffected`, `RecordID`, `Details`, `IPAddress`) VALUES
    ('admin', 'Admin', 'DATABASE_SEED', 'System', 'all', 1, 'Initial high-performance system test data seeded', '127.0.0.1'),
    ('registrar', 'Registrar', 'EVALUATION_APPROVED', 'Registrar', 'evaluation', 1, 'Approved study load for Student 2024-00001', '127.0.0.1'),
    ('accounting', 'Accounting', 'PAYMENT_RECEIVED', 'Accounting', 'payment', 1, 'Received full tuition payment OR-2026-00001', '127.0.0.1'),
    ('clinic', 'Clinic', 'MEDICAL_CLEARED', 'Clinic', 'clinic', 1, 'Completed medical evaluation and issued clearance', '127.0.0.1'),
    ('security', 'Security Office', 'ENROLLMENT_CONFIRMED', 'Security', 'security_verification', 1, 'Final confirmation issued. Status = CONFIRMED', '127.0.0.1')
");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
$pdo->exec("COMMIT");

$elapsed = round(microtime(true) - $startTime, 2);

echo "\n=== SEEDING COMPLETED SUCCESSFULLY in {$elapsed}s! ===\n";

// Verification queries
echo "\n--- Verification Summary ---\n";
$queries = [
    "Total Students" => "SELECT COUNT(*) FROM student",
    "New Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 1",
    "Regular Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 2",
    "Irregular Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 3",
    "Shifter Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 4",
    "Returnee Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 5",
    "Transferee Students" => "SELECT COUNT(*) FROM student WHERE StudentTypeID = 6",
    "Total Users" => "SELECT COUNT(*) FROM users",
    "Total Staff" => "SELECT COUNT(*) FROM staff",
    "Total Programs" => "SELECT COUNT(*) FROM program",
    "Total Subjects" => "SELECT COUNT(*) FROM subject",
    "Total Schedules" => "SELECT COUNT(*) FROM schedule",
    "Total Admissions" => "SELECT COUNT(*) FROM admission",
    "Total Credited Subjects (Transferees)" => "SELECT COUNT(*) FROM credited_subject",
    "Total Student Grades" => "SELECT COUNT(*) FROM student_grades",
    "Active Enrollments" => "SELECT COUNT(*) FROM enrollment",
    "View Checklist Rows" => "SELECT COUNT(*) FROM vw_enrollment_checklist",
    "Checklist Confirmed (Security Verified)" => "SELECT COUNT(*) FROM vw_enrollment_checklist WHERE OverallStatus = 'CONFIRMED'"
];

foreach ($queries as $label => $q) {
    $count = $pdo->query($q)->fetchColumn();
    echo sprintf(" - %-42s : %d\n", $label, $count);
}
