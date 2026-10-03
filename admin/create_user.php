<?php
// admin/create_user.php - Create Staff and Administrative Accounts
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Admin');
$pageTitle = 'Create Staff Account';
$db = getDBConnection();

$departments = $db->query("SELECT departmentID, DepartmentName FROM department ORDER BY departmentID")->fetchAll();
$roles = ['Department', 'Registrar', 'Accounting', 'Clinic', 'Security Office', 'Admin'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? 'password123';
    $role = $_POST['role'] ?? 'Registrar';
    $firstName = trim($_POST['FirstName'] ?? '');
    $lastName = trim($_POST['LastName'] ?? '');
    $middleName = trim($_POST['MiddleName'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $contactNo = trim($_POST['ContactNo'] ?? '');
    $departmentId = (int)($_POST['DepartmentID'] ?? 1);

    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($firstName) || empty($lastName)) $errors[] = 'Staff first and last names are required.';
    if (empty($email)) $errors[] = 'Email address is required.';

    // Check if username exists
    $stmtChk = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmtChk->execute([$username]);
    if ($stmtChk->fetchColumn() > 0) {
        $errors[] = 'Username already exists. Please choose a different username.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Insert Staff
            $stmtStf = $db->prepare("INSERT INTO staff (LastName, FirstName, MiddleName, Email, ContactNo, DepartmentID, RoleID) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtStf->execute([$lastName, $firstName, $middleName, $email, $contactNo, $departmentId, $role]);
            $staffId = (int)$db->lastInsertId();

            // 2. Insert Login & Users
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmtLog = $db->prepare("INSERT INTO login (Username, PasswordHash, UserType, Status, StaffID) VALUES (?, ?, 'Staff', 'Active', ?)");
            $stmtLog->execute([$username, $hashed, $staffId]);

            $stmtUsr = $db->prepare("INSERT INTO users (username, password_hash, role, status, staff_id) VALUES (?, ?, ?, 'Active', ?)");
            $stmtUsr->execute([$username, $hashed, $role, $staffId]);

            $db->commit();

            logActivity('CREATE_STAFF_ACCOUNT', 'Admin', 'staff', $staffId, "Created staff account {$username} with role {$role}");
            setFlash('success', "Staff account created successfully! Username: {$username}");
            header("Location: " . BASE_URL . "/admin/users.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error creating account: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add Staff Account</h4>
        <p class="text-muted small mb-0">Create new staff account for Department, Registrar, Accounting, Clinic, or Security.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user-plus me-2"></i>Staff Information & Access Role</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Login Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. reg_staff1" required value="<?= e($_POST['username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                            <input type="text" name="password" class="form-control" value="<?= e($_POST['password'] ?? 'password123') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>" <?= ($_POST['role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Department</label>
                            <select name="DepartmentID" class="form-select">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['departmentID'] ?>" <?= ($_POST['DepartmentID'] ?? 1) == $d['departmentID'] ? 'selected' : '' ?>>
                                        <?= e($d['DepartmentName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="FirstName" class="form-control" required value="<?= e($_POST['FirstName'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="LastName" class="form-control" required value="<?= e($_POST['LastName'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Middle Name</label>
                            <input type="text" name="MiddleName" class="form-control" value="<?= e($_POST['MiddleName'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="Email" class="form-control" required value="<?= e($_POST['Email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Number</label>
                            <input type="text" name="ContactNo" class="form-control" placeholder="09171234567" value="<?= e($_POST['ContactNo'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="fas fa-save me-1"></i> Save Staff Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
