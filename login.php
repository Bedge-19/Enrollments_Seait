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
    <title>Sign In — SEAIT Enrollment Portal</title>
    <meta name="description" content="Sign in to the South East Asian Institute of Technology Enrollment and Confirmation System">
    <!-- Work Sans -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fonts/fonts.css">
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #ff8c00;
            --primary-dark: #904d00;
            --primary-light: #ffb77d;
            --primary-bg: #fff5e6;
            --primary-container: #ffdcc3;
            --surface: #f8f9fa;
            --surface-high: #e7e8e9;
            --on-surface: #191c1d;
            --on-surface-variant: #564334;
            --outline-variant: #ddc1ae;
            --font: 'Work Sans', -apple-system, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            min-height: 100%;
            height: auto;
        }
        body {
            font-family: var(--font);
            display: flex;
            min-height: 100vh;
            background: #111827;
            color: var(--on-surface);
            overflow-x: hidden;
        }

        /* ── LEFT PANEL ── */
        .login-left {
            width: 400px;
            flex-shrink: 0;
            background: linear-gradient(165deg, #7c3f00 0%, #ff8c00 50%, #ffaa00 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 36px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 350px 350px at 85% 15%, rgba(255,255,255,0.18) 0%, transparent 65%),
                radial-gradient(ellipse 250px 250px at 15% 85%, rgba(0,0,0,0.18) 0%, transparent 65%);
            pointer-events: none;
        }
        .login-left-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
        }
        .login-left-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin: auto 0;
            width: 100%;
        }
        .login-logo {
            width: 88px;
            height: 88px;
            border-radius: 20px;
            object-fit: contain;
            border: 3px solid rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            padding: 8px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.22);
        }
        .login-school-name {
            font-size: 21px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.25;
            margin-bottom: 8px;
            letter-spacing: -0.01em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .login-tagline {
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.88);
            font-style: italic;
            margin-bottom: 30px;
            line-height: 1.45;
        }
        .login-badges {
            display: flex;
            flex-direction: column;
            gap: 9px;
            text-align: left;
            width: 100%;
        }
        .login-badge-item {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.25);
            backdrop-filter: blur(6px);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.98);
            font-size: 13px;
            font-weight: 500;
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .login-badge-item:hover {
            transform: translateX(4px);
            background: rgba(255,255,255,0.2);
        }
        .login-badge-item i {
            width: 22px;
            text-align: center;
            font-size: 14px;
            color: rgba(255,255,255,0.95);
        }
        .login-year {
            position: relative;
            z-index: 2;
            font-size: 12px;
            color: rgba(255,255,255,0.65);
            font-weight: 500;
            text-align: center;
            margin-top: 24px;
        }

        /* ── RIGHT PANEL ── */
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
            isolation: isolate;
            min-height: 100vh;
        }
        .login-right::before {
            content: '';
            position: absolute;
            inset: -40px;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, 0.65) 0%, rgba(144, 77, 0, 0.40) 100%),
                url('<?= BASE_URL ?>/photo/bgraound.jpg') center/cover no-repeat;
            filter: blur(10px);
            transform: scale(1.05);
            z-index: -1;
            pointer-events: none;
        }
        .login-form-wrap {
            width: 100%;
            max-width: 450px;
            margin: auto;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px) saturate(1.8);
            -webkit-backdrop-filter: blur(20px) saturate(1.8);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28), 0 0 0 1px rgba(255, 255, 255, 0.7);
        }
        .login-welcome {
            margin-bottom: 26px;
        }
        .login-welcome h2 {
            font-size: 25px;
            font-weight: 800;
            color: var(--on-surface);
            letter-spacing: -0.015em;
            margin-bottom: 6px;
        }
        .login-welcome p {
            font-size: 13.5px;
            color: var(--on-surface-variant);
            line-height: 1.5;
        }
        .login-welcome .seait-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary-bg);
            border: 1px solid var(--primary-container);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 14px;
            letter-spacing: 0.02em;
        }
        .login-welcome .seait-chip::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
            display: inline-block;
        }

        /* Form elements */
        .form-label-lg {
            font-size: 13px;
            font-weight: 600;
            color: var(--on-surface);
            display: block;
            margin-bottom: 6px;
        }
        .input-field {
            display: flex;
            align-items: center;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            transition: all 0.2s ease;
            overflow: hidden;
            height: 48px;
        }
        .input-field:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(255, 140, 0, 0.18);
            background: #ffffff;
        }
        .input-icon {
            padding: 0 14px;
            color: #6b7280;
            font-size: 14px;
            flex-shrink: 0;
        }
        .input-field input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 14px 10px 0;
            font-size: 14px;
            font-family: var(--font);
            color: var(--on-surface);
            outline: none;
            min-width: 0;
        }
        .input-field input::placeholder { color: #9ca3af; }
        .input-toggle {
            background: none;
            border: none;
            padding: 0 14px;
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .input-toggle:hover { color: var(--on-surface); }
        .input-hint {
            font-size: 11.5px;
            color: #6b7280;
            margin-top: 5px;
            line-height: 1.4;
        }
        .field-row {
            margin-bottom: 18px;
        }
        .field-row-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        /* Submit btn */
        .btn-signin {
            width: 100%;
            height: 48px;
            padding: 0 24px;
            background: linear-gradient(135deg, #ff8c00 0%, #e67300 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            font-family: var(--font);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            box-shadow: 0 4px 14px rgba(255, 140, 0, 0.35);
        }
        .btn-signin:hover {
            background: linear-gradient(135deg, #e67300 0%, #cc6600 100%);
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.45);
            transform: translateY(-1px);
        }
        .btn-signin:active { transform: translateY(0); }

        /* Alerts */
        .login-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #991b1b;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        .login-alert-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        /* Divider */
        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0 16px;
            color: #6b7280;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .login-divider::before, .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        /* Demo grid */
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 7px;
        }
        .demo-btn {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: var(--font);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .demo-btn:hover {
            border-color: var(--primary);
            background: #fff8f0;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.15);
        }
        .demo-btn .demo-role {
            font-size: 10px;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: block;
            margin-top: 3px;
        }
        .demo-btn .demo-user {
            font-size: 10px;
            color: #64748b;
            display: block;
        }
        .demo-icon { font-size: 15px; }

        /* Footer note */
        .login-footer {
            margin-top: 24px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            line-height: 1.5;
        }
        .login-footer a {
            color: var(--primary-dark);
            font-weight: 700;
            text-decoration: none;
        }
        .login-footer a:hover { text-decoration: underline; }

        /* Responsive Layout */
        @media (max-width: 960px) {
            body {
                flex-direction: column;
                min-height: 100vh;
            }
            .login-left {
                width: 100%;
                min-height: auto;
                padding: 32px 24px 28px;
            }
            .login-logo { width: 68px; height: 68px; margin-bottom: 12px; }
            .login-school-name { font-size: 19px; }
            .login-tagline { margin-bottom: 18px; font-size: 12.5px; }
            .login-badges { display: none; }
            .login-year { display: none; }
            .login-right {
                padding: 36px 20px 48px;
                min-height: auto;
            }
            .login-form-wrap {
                max-width: 440px;
                padding: 32px 24px;
            }
        }

        @media (max-width: 480px) {
            .login-left { padding: 24px 16px 20px; }
            .login-right { padding: 24px 14px 36px; }
            .login-form-wrap { padding: 24px 18px; border-radius: 18px; }
            .demo-grid { grid-template-columns: repeat(2, 1fr); }
            .demo-btn:last-child { grid-column: span 2; }
        }

        /* ── Login Skeleton Loader ── */
        @keyframes skeleton-shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .login-skeleton {
            background: linear-gradient(90deg, #edeef0 25%, #f7f8f9 37%, #edeef0 63%);
            background-size: 400% 100%;
            animation: skeleton-shimmer 1.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            border-radius: 6px;
        }
        #pageSkeletonLoader {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #ffffff;
            z-index: 99999;
            display: flex;
            transition: opacity 0.35s ease, visibility 0.35s ease;
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }
        #pageSkeletonLoader.fade-out {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
    </style>
</head>
<body>

<!-- ═══ Login Page Skeleton Loader ═══ -->
<div id="pageSkeletonLoader" aria-hidden="true">
    <div style="width:42%;background:#fff5e6;padding:48px 40px;display:flex;flex-direction:column;justify-content:center;" class="d-none d-md-flex">
        <div class="login-skeleton" style="width:72px;height:72px;border-radius:12px;margin-bottom:24px;"></div>
        <div class="login-skeleton" style="width:80%;height:32px;margin-bottom:12px;"></div>
        <div class="login-skeleton" style="width:60%;height:18px;margin-bottom:32px;"></div>
        <div class="login-skeleton" style="width:90%;height:40px;margin-bottom:12px;border-radius:9999px;"></div>
        <div class="login-skeleton" style="width:90%;height:40px;border-radius:9999px;"></div>
    </div>
    <div style="flex:1;background:#ffffff;padding:48px;display:flex;align-items:center;justify-content:center;">
        <div style="width:100%;max-width:420px;">
            <div class="login-skeleton" style="width:140px;height:24px;border-radius:9999px;margin-bottom:20px;"></div>
            <div class="login-skeleton" style="width:60%;height:32px;margin-bottom:12px;"></div>
            <div class="login-skeleton" style="width:90%;height:16px;margin-bottom:32px;"></div>
            <div class="login-skeleton" style="width:100%;height:48px;margin-bottom:20px;border-radius:8px;"></div>
            <div class="login-skeleton" style="width:100%;height:48px;margin-bottom:28px;border-radius:8px;"></div>
            <div class="login-skeleton" style="width:100%;height:46px;border-radius:8px;"></div>
        </div>
    </div>
</div>

<!-- ═══ LEFT PANEL — SEAIT Branding ═══ -->
<div class="login-left">
    <div class="login-left-grid"></div>
    <div class="login-left-content">
        <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT Logo" class="login-logo" />
        <div class="login-school-name">South East Asian<br>Institute of Technology</div>
        <div class="login-tagline">"Committed to The Total Development<br>of The Student"</div>

        <div class="login-badges">
            <div class="login-badge-item">
                <i class="fas fa-graduation-cap"></i>
                <span>100% Tuition-Free Education</span>
            </div>
            <div class="login-badge-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Tupi, South Cotabato, Philippines</span>
            </div>
            <div class="login-badge-item">
                <i class="fas fa-calendar-check"></i>
                <span>A.Y. 2026–2027 Enrollment Open</span>
            </div>
            <div class="login-badge-item">
                <i class="fas fa-shield-alt"></i>
                <span>CHED &nbsp;·&nbsp; DepEd &nbsp;·&nbsp; TESDA Recognized</span>
            </div>
        </div>
        <div class="login-year">© <?= date('Y') ?> SEAIT Enrollment Portal</div>
    </div>
</div>

<!-- ═══ RIGHT PANEL — Login Form ═══ -->
<div class="login-right">
    <div class="login-form-wrap">
        <div class="login-welcome">
            <div class="seait-chip">SEAIT Enrollment &amp; Confirmation System</div>
            <h2>Welcome back 👋</h2>
            <p>Sign in to access your enrollment portal. Students may use their Student No., Email, or Username.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="login-alert">
                <i class="fas fa-exclamation-circle" style="margin-top:1px;flex-shrink:0;"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php
        // Flash messages
        $flash = getFlash();
        if ($flash): ?>
            <div class="login-alert <?= $flash['type'] === 'success' ? 'login-alert-success' : '' ?>">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>" style="margin-top:1px;flex-shrink:0;"></i>
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="field-row">
                <label class="form-label-lg" for="username">Username / Student No. / Email</label>
                <div class="input-field">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="username" name="username"
                           placeholder="e.g. APP-2026-35203 or username"
                           value="<?= e($inputIdentifier) ?>"
                           required autofocus autocomplete="username" />
                </div>
                <div class="input-hint">Students may use their Student No., Email, or system username.</div>
            </div>

            <div class="field-row">
                <div class="field-row-head">
                    <label class="form-label-lg" for="password" style="margin-bottom:0;">Password</label>
                    <span style="font-size:12px;color:var(--on-surface-variant);">Default: <code>password123</code></span>
                </div>
                <div class="input-field">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           required autocomplete="current-password" />
                    <button type="button" class="input-toggle" id="togglePass" aria-label="Show password" tabindex="-1">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-signin" id="signinBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In to Portal
            </button>
        </form>

        <div class="login-divider">Quick Demo Access</div>

        <div class="demo-grid">
            <button type="button" class="demo-btn" onclick="fillLogin('student_1')">
                <span class="demo-icon">🎓</span>
                <span class="demo-role">Student</span>
                <span class="demo-user">student_1</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('dept_cics')">
                <span class="demo-icon">🏫</span>
                <span class="demo-role">Dept.</span>
                <span class="demo-user">dept_cics</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('registrar')">
                <span class="demo-icon">📋</span>
                <span class="demo-role">Registrar</span>
                <span class="demo-user">registrar</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('accounting')">
                <span class="demo-icon">💰</span>
                <span class="demo-role">Accounting</span>
                <span class="demo-user">accounting</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('clinic')">
                <span class="demo-icon">🏥</span>
                <span class="demo-role">Clinic</span>
                <span class="demo-user">clinic</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('security')">
                <span class="demo-icon">🛡️</span>
                <span class="demo-role">Security</span>
                <span class="demo-user">security</span>
            </button>
            <button type="button" class="demo-btn" onclick="fillLogin('admin')">
                <span class="demo-icon">⚙️</span>
                <span class="demo-role">Admin</span>
                <span class="demo-user">admin</span>
            </button>
        </div>

        <div class="login-footer">
            <a href="<?= BASE_URL ?>/landing.php">← Back to SEAIT Website</a>
            &nbsp;·&nbsp;
            South East Asian Institute of Technology, Inc.
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function fillLogin(u) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = 'password123';
    document.getElementById('username').focus();
}

// Toggle password visibility
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

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('signinBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in…';
});

// Dismiss Skeleton Loader
function hideLoginSkeleton() {
    const skeleton = document.getElementById('pageSkeletonLoader');
    if (skeleton) {
        skeleton.classList.add('fade-out');
        setTimeout(() => { skeleton.style.display = 'none'; }, 380);
    }
}
if (document.readyState === 'complete') {
    setTimeout(hideLoginSkeleton, 150);
} else {
    window.addEventListener('load', () => setTimeout(hideLoginSkeleton, 150));
    setTimeout(hideLoginSkeleton, 1000);
}
</script>
</body>
</html>
