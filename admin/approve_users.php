<?php
// admin/approve_users.php
session_start();
require_once __DIR__ . "/../php/config.php";
require_once __DIR__ . "/../php/utils/session.php";

// ✅ Only allow admins
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /admin_login.html");
    exit;
}

$conn = db();

// 🔒 CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) die("Invalid CSRF token");

    // Approve/Reject users
    if (isset($_POST['user_id'], $_POST['action'])) {
        $uid = intval($_POST['user_id']);
        $action = $_POST['action'];
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
        }
        header("Location: approve_users.php");
        exit;
    }

    // Create new admin
    if (isset($_POST['new_admin_name'], $_POST['new_admin_email'], $_POST['new_admin_password'])) {
        $name  = trim($_POST['new_admin_name']);
        $email = trim($_POST['new_admin_email']);
        $pass  = $_POST['new_admin_password'];

        if (!$name || !$email || !$pass) {
            $error = "All admin fields are required";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Email already exists";
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (name,email,password_hash,role,approved) VALUES (?,?,?,?,1)");
                $role = 'admin';
                $stmt->bind_param("ssssi", $name, $email, $hash, $role, $approved = 1);
                if ($stmt->execute()) {
                    $success = "New admin created successfully";
                } else {
                    $error = "Failed to create admin";
                }
            }
        }
    }
}

// ✅ Fetch admin dashboard stats
$totalUsers    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pendingUsers  = $conn->query("SELECT COUNT(*) as c FROM users WHERE approved = 0")->fetch_assoc()['c'];
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$totalFaculty  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty'")->fetch_assoc()['c'];
$totalCompany  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='company'")->fetch_assoc()['c'];

// ✅ Fetch pending users
$pendingResult = $conn->query("SELECT id, name, email, role FROM users WHERE approved = 0 AND role IN ('faculty','company')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body { font-family: Arial, sans-serif; margin:20px; background:#f5f5f5; }
h1,h2 { margin-bottom:10px; }
.stats { display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap; }
.card { background:white; padding:15px; border-radius:5px; flex:1; min-width:120px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1); }
table { border-collapse: collapse; width:100%; background:white; border-radius:5px; overflow:hidden; box-shadow:0 0 5px rgba(0,0,0,0.1); }
th, td { border:1px solid #ddd; padding:10px; text-align:left; }
th { background:#eee; }
button { padding:5px 10px; margin:0 2px; cursor:pointer; border:none; border-radius:3px; }
button.approve { background:#4CAF50; color:white; }
button.reject { background:#f44336; color:white; }
form { display:inline; }
.admin-form { margin-top:20px; padding:15px; background:white; border-radius:5px; box-shadow:0 0 5px rgba(0,0,0,0.1); width:300px; }
.admin-form input { display:block; width:100%; margin-bottom:12px ; padding:8px;box-sizing:border-box;border: 1px solid #ccc;border-radius: 4px; }
.admin-form button { display: block;width:100%; padding: 10px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 5px;}
    .admin-form button:hover {
    background: #45a049;
}
.success { color:green; }
.error { color:red; }
</style>
</head>
<body>

<h1>Admin Dashboard</h1>

<!-- Dashboard Stats -->
<div class="stats">
    <div class="card"><strong>Total Users</strong><br><?= $totalUsers ?></div>
    <div class="card"><strong>Pending Approvals</strong><br><?= $pendingUsers ?></div>
    <div class="card"><strong>Students</strong><br><?= $totalStudents ?></div>
    <div class="card"><strong>Faculty</strong><br><?= $totalFaculty ?></div>
    <div class="card"><strong>Companies</strong><br><?= $totalCompany ?></div>
</div>

<h2>Pending Faculty & Company Registrations</h2>

<?php if ($pendingResult->num_rows > 0): ?>
<table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>
    <?php while ($row = $pendingResult->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="action" value="approve" class="approve" onclick="return confirm('Approve this user?')">✅ Approve</button>
                    <button type="submit" name="action" value="reject" class="reject" onclick="return confirm('Reject this user?')">❌ Reject</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No pending approvals 🎉</p>
<?php endif; ?>

<h2>Create New Admin</h2>
<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<form method="post" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="text" name="new_admin_name" placeholder="Admin Name" required>
    <input type="email" name="new_admin_email" placeholder="Admin Email" required>
    <input type="password" name="new_admin_password" placeholder="Password" required>
    <button type="submit">Create Admin</button>
</form>

</body>
</html>
