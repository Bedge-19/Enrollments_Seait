<?php
// login.php - Multi-role Authentication Page (Supports Username, Student No, or Email)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header("Location: " . getRoleDashboardUrl($_SESSION['role']));
    exit;
}

$error = '';
$inputIdentifier = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $inputIdentifier = $identifier;

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your username, student number, or email and password.';
    } else {
        $db = getDBConnection();

        // 1. Find user by Username, StudentNo, or Email
        $stmt = $db->prepare("SELECT u.* FROM `users` u 
            LEFT JOIN `student` s ON s.StudentID = u.student_id 
            WHERE (LOWER(u.`username`) = LOWER(?) OR LOWER(s.`StudentNo`) = LOWER(?) OR LOWER(s.`Email`) = LOWER(?)) 
            AND u.`status` = 'Active' 
            LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();

        // 2. If user row is missing in `users`, but exists in `student` table (Auto-repair student login)
        if (!$user) {
            $stmtStdCheck = $db->prepare("SELECT * FROM `student` 
                WHERE (LOWER(`StudentNo`) = LOWER(?) OR LOWER(`Email`) = LOWER(?) OR LOWER(CONCAT(REPLACE(`FirstName`, ' ', ''), '.', REPLACE(`LastName`, ' ', ''), `StudentID`)) = LOWER(?)) 
                LIMIT 1");
            $stmtStdCheck->execute([$identifier, $identifier, $identifier]);
            $stdRecord = $stmtStdCheck->fetch();

            if ($stdRecord) {
                // Auto create users record
                $stdUser = strtolower(str_replace(' ', '', $stdRecord['FirstName']) . '.' . str_replace(' ', '', $stdRecord['LastName']) . $stdRecord['StudentID']);
                $hash = password_hash('password123', PASSWORD_DEFAULT);
                
                $db->prepare("INSERT INTO `users` (`username`, `password_hash`, `role`, `status`, `student_id`) VALUES (?, ?, 'Student', 'Active', ?)")
                   ->execute([$stdUser, $hash, $stdRecord['StudentID']]);
                
                $db->prepare("INSERT INTO `login` (`Username`, `PasswordHash`, `UserType`, `Status`, `StudentID`) VALUES (?, ?, 'Student', 'Active', ?)")
                   ->execute([$stdUser, $hash, $stdRecord['StudentID']]);

                // Re-fetch created user
                $stmt->execute([$stdUser, $stdRecord['StudentNo'], $stdRecord['Email']]);
                $user = $stmt->fetch();
            }
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['student_id'] = $user['student_id'];

            // Fetch extra profile details
            if ($user['role'] === 'Student' && $user['student_id']) {
                $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
                    FROM student s 
                    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
                    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
                    WHERE s.StudentID = ?");
                $stmtStd->execute([$user['student_id']]);
                $std = $stmtStd->fetch();
                $_SESSION['full_name'] = $std ? ($std['FirstName'] . ' ' . $std['LastName']) : $user['username'];
                $_SESSION['student_no'] = $std['StudentNo'] ?? '';
                $_SESSION['program_name'] = $std['ProgramName'] ?? '';
                $_SESSION['student_type'] = $std['TypeName'] ?? '';

                // Ensure student has active enrollment row for current term
                $stmtEnrCheck = $db->prepare("SELECT EnrollmentID FROM enrollment WHERE StudentID = ? AND SchoolYear = '2026-2027' AND Semester = '1st' LIMIT 1");
                $stmtEnrCheck->execute([$user['student_id']]);
                if (!$stmtEnrCheck->fetch()) {
                    $db->prepare("INSERT INTO `enrollment` (`EnrollmentDate`, `SchoolYear`, `Semester`, `Status`, `StudentID`, `StaffID`) VALUES (CURDATE(), '2026-2027', '1st', 'Pending', ?, 2)")
                       ->execute([$user['student_id']]);
                }
            } elseif ($user['staff_id']) {
                $stmtStf = $db->prepare("SELECT s.*, d.DepartmentName 
                    FROM staff s 
                    LEFT JOIN department d ON d.departmentID = s.DepartmentID 
                    WHERE s.StaffID = ?");
                $stmtStf->execute([$user['staff_id']]);
                $stf = $stmtStf->fetch();
                $_SESSION['full_name'] = $stf ? ($stf['FirstName'] . ' ' . $stf['LastName']) : $user['username'];
                $_SESSION['department_id'] = $stf['DepartmentID'] ?? null;
                $_SESSION['department_name'] = $stf['DepartmentName'] ?? '';
            } else {
                $_SESSION['full_name'] = ucfirst($user['username']);
            }

            // Update login table last login timestamp
            $db->prepare("UPDATE `login` SET `LastLogin` = NOW() WHERE `Username` = ?")->execute([$user['username']]);

            logActivity('USER_LOGIN', 'Authentication', 'users', $user['id'], "User logged in with role {$user['role']}");
            setFlash('success', "Welcome back, " . $_SESSION['full_name'] . "!");
            header("Location: " . getRoleDashboardUrl($user['role']));
            exit;
        } else {
            $error = 'Invalid username, student number, or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — SEAIT Enrollment Management System</title>
    <meta name="description" content="Sign in to the South East Asian Institute of Technology Enrollment System">
    <!-- Work Sans -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fonts/fonts.css">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        /* Institutional Modern UI - Scoped */
        :root {
            --brand-primary: #FF8C00;      /* SEAIT Orange */
            --brand-accent: #0F4C81;       /* Executive Navy */
            --brand-accent-hover: #0A365C;
            --surface-50: #F8FAFC;
            --surface-100: #F1F5F9;
            --surface-card: #FFFFFF;
            --text-primary: #0F172A;
            --text-secondary: #64748B;
            --border-color: #E2E8F0;
            --ring-color: rgba(255, 140, 0, 0.25);
            --font: 'Work Sans', -apple-system, sans-serif;
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: var(--font);
            margin: 0;
            padding: 0;
            background-color: var(--surface-50);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ── Split Layout ── */
        .auth-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* ── Left Hero Panel ── */
        .auth-hero {
            flex: 1;
            background: linear-gradient(135deg, #CC5200 0%, #FF8C00 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 5rem;
            color: #ffffff;
            overflow: hidden;
            z-index: 1;
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('<?= BASE_URL ?>/photo/bgraound.jpg') center/cover;
            opacity: 0.15;
            mix-blend-mode: overlay;
        }

        .auth-hero-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .auth-hero-content {
            position: relative;
            z-index: 10;
            max-width: 540px;
        }

        .hero-logo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px;
            border-radius: var(--radius-md);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            margin-bottom: 2.5rem;
            object-fit: contain;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 3rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .accreditation-badge {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .accreditation-badge i {
            color: var(--brand-accent);
            font-size: 1.5rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .accreditation-text {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.3;
        }

        /* ── Right Form Panel ── */
        .auth-panel {
            width: 100%;
            max-width: 560px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface-card);
            padding: 3rem 4.5rem;
            box-shadow: -15px 0 40px rgba(0,0,0,0.06);
            position: relative;
            z-index: 10;
        }

        .auth-header {
            margin-bottom: 2.5rem;
        }

        .auth-header h2 {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.5;
        }

        /* Alerts */
        .alert-custom {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.75rem;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /* Floating Inputs */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control-custom {
            width: 100%;
            height: 60px;
            padding: 1.25rem 1rem 0.25rem 3.25rem;
            font-size: 1rem;
            font-family: var(--font);
            color: var(--text-primary);
            background: var(--surface-100);
            border: 1.5px solid transparent;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .form-control-custom:focus {
            background: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px var(--ring-color);
            outline: none;
        }

        .form-label-floating {
            position: absolute;
            left: 3.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.95rem;
            pointer-events: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .form-control-custom:focus ~ .form-label-floating,
        .form-control-custom:not(:placeholder-shown) ~ .form-label-floating {
            top: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--brand-primary);
        }

        .input-icon-left {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.15rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control-custom:focus ~ .input-icon-left {
            color: var(--brand-primary);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            transition: color 0.2s;
            font-size: 1.1rem;
        }
        .password-toggle:hover {
            color: var(--text-primary);
        }

        /* Buttons */
        .btn-submit {
            width: 100%;
            height: 56px;
            background: var(--brand-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }
        .btn-submit:hover {
            background: #E67E00;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 140, 0, 0.4);
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        /* Demo Links */
        .demo-section {
            margin-top: 3rem;
        }
        .demo-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #cbd5e1;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1.25rem;
        }
        .demo-divider::before, .demo-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .demo-divider:not(:empty)::before { margin-right: 1em; }
        .demo-divider:not(:empty)::after { margin-left: 1em; }

        .demo-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            justify-content: center;
        }
        .demo-badge {
            background: var(--surface-100);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .demo-badge i { font-size: 0.9rem; }
        .demo-badge:hover {
            background: var(--brand-primary);
            color: #fff;
            border-color: var(--brand-primary);
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3);
            transform: translateY(-1px);
        }

        /* Footer */
        .auth-footer {
            margin-top: auto;
            text-align: center;
            font-size: 0.85rem;
            color: #94a3b8;
            padding-top: 3rem;
            line-height: 1.6;
        }
        .auth-footer a {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .auth-footer a:hover { opacity: 0.8; }

        /* Responsive */
        @media (max-width: 1024px) {
            .auth-hero { padding: 3rem 2.5rem; }
            .auth-panel { padding: 3rem 2.5rem; max-width: 480px; }
            .hero-title { font-size: 2rem; }
        }
        @media (max-width: 768px) {
            .auth-container { flex-direction: column; }
            .auth-hero {
                flex: none;
                padding: 3rem 2rem;
                text-align: center;
                align-items: center;
            }
            .auth-hero-content {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .auth-panel {
                max-width: 100%;
                padding: 2.5rem 2rem;
                box-shadow: 0 -20px 40px rgba(0,0,0,0.05);
                border-radius: 30px 30px 0 0;
                margin-top: -20px;
            }
        }
        @media (max-width: 480px) {
            .auth-panel { padding: 2rem 1.5rem; }
            .demo-badge { flex: 1 1 calc(50% - 0.6rem); justify-content: center; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- ═══ Left / Hero Panel ═══ -->
    <div class="auth-hero">
        <div class="auth-hero-grid"></div>
        <div class="auth-hero-content">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT Logo" class="hero-logo" onerror="this.src='https://ui-avatars.com/api/?name=SEAIT&background=ffffff&color=0f4c81&size=100'">
            <h1 class="hero-title">South East Asian Institute of Technology</h1>
            <p class="hero-subtitle">Enrollment Management System &bull; A.Y. 2026–2027</p>
            
            <div class="accreditation-badge">
                <i class="fas fa-award"></i>
                <div class="accreditation-text">
                    <span style="display:block; font-size:0.75rem; color:rgba(255,255,255,0.7); margin-bottom:2px;">Officially Recognized By</span>
                    CHED &bull; DepEd &bull; TESDA
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Right / Form Panel ═══ -->
    <div class="auth-panel">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to access your portal and manage enrollments securely.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-error">
                <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert-custom <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'info-circle' ?>" style="margin-top:2px;"></i>
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <input type="text" id="username" name="username" class="form-control-custom" placeholder=" " value="<?= e($inputIdentifier) ?>" required autofocus autocomplete="username">
                <label for="username" class="form-label-floating">Username / Student No. / Email</label>
                <i class="fas fa-user input-icon-left"></i>
            </div>

            <div class="form-group" style="margin-bottom: 0.5rem;">
                <input type="password" id="password" name="password" class="form-control-custom" placeholder=" " required autocomplete="current-password">
                <label for="password" class="form-label-floating">Password</label>
                <i class="fas fa-lock input-icon-left"></i>
                <button type="button" class="password-toggle" id="togglePass" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            
            <div style="text-align: right; margin-bottom: 1.75rem;">
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Default password: <code style="color: var(--brand-primary); font-weight: 700; padding: 2px 6px; background: var(--surface-100); border-radius: 4px;">password123</code></span>
            </div>

            <button type="submit" class="btn-submit" id="signinBtn">
                Sign In to Portal <i class="fas fa-arrow-right" style="font-size: 0.9rem;"></i>
            </button>
        </form>

        <div class="demo-section">
            <div class="demo-divider">Quick Demo Access</div>
            <div class="demo-roles">
                <button type="button" class="demo-badge" onclick="fillLogin('student_1')"><i class="fas fa-user-graduate"></i> Student</button>
                <button type="button" class="demo-badge" onclick="fillLogin('dept_cics')"><i class="fas fa-building"></i> Department</button>
                <button type="button" class="demo-badge" onclick="fillLogin('registrar')"><i class="fas fa-file-alt"></i> Registrar</button>
                <button type="button" class="demo-badge" onclick="fillLogin('accounting')"><i class="fas fa-wallet"></i> Accounting</button>
                <button type="button" class="demo-badge" onclick="fillLogin('clinic')"><i class="fas fa-stethoscope"></i> Clinic</button>
                <button type="button" class="demo-badge" onclick="fillLogin('admin')"><i class="fas fa-cog"></i> Admin</button>
            </div>
        </div>

        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/landing.php"><i class="fas fa-arrow-left" style="margin-right: 4px;"></i> Back to SEAIT Website</a>
            <div style="margin-top: 0.75rem;">&copy; <?= date('Y') ?> South East Asian Institute of Technology, Inc.</div>
        </div>
    </div>
</div>

<script>
function fillLogin(u) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = 'password123';
    document.getElementById('username').focus();
}

const togglePass = document.getElementById('togglePass');
const passInput  = document.getElementById('password');
const toggleIcon = document.getElementById('toggleIcon');
if (togglePass) {
    togglePass.addEventListener('click', () => {
        const shown = passInput.type === 'text';
        passInput.type = shown ? 'password' : 'text';
        toggleIcon.className = shown ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('signinBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
});
</script>
</body>
</html>
