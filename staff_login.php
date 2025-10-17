<?php
session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB connection failed");

$msg = "";
if(isset($_POST['login'])){
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Fetch record by role
    $res = $conn->query("SELECT * FROM staff_login WHERE role='$role' LIMIT 1");

    if($res->num_rows > 0){
        $staff = $res->fetch_assoc();

        if($staff['password'] === $password){
            // Set session
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['role'] = $staff['role'];

            // Redirect based on role
            switch($role){
                case 'admin': header("Location: admin.php"); break;
                case 'manager': header("Location: manager_dashboard.php"); break;
                case 'cashier': header("Location: cashier_dashboard.php"); break;
                case 'chef': header("Location: chef_dashboard.php"); break;
                case 'waiter': header("Location: waiter_dashboard.php"); break;
                default: header("Location: index.php");
            }
            exit;
        } else {
            $msg = "❌ Incorrect password for selected role.";
        }
    } else {
        $msg = "❌ Role not found in system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Login | Singha Bakers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* === Global Glassy Neon Theme === */
body {
  background: radial-gradient(circle at top left, #039d9dff, #000);
  background-attachment: fixed;
  color: #fff;
  font-family: 'Poppins', sans-serif;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* Animated gradient overlay */
body::before {
  content: "";
  position: absolute;
  width: 150%;
  height: 150%;
  background: radial-gradient(circle at 20% 30%, rgba(0,230,118,0.15), transparent 60%),
              radial-gradient(circle at 80% 70%, rgba(0,255,200,0.1), transparent 60%);
  filter: blur(100px);
  z-index: 0;
}

/* === Login Card === */
.card {
  position: relative;
  width: 420px;
  padding: 35px;
  background: rgba(20,20,20,0.9);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 0 25px rgba(199, 200, 200, 0.25), inset 0 0 10px rgba(255,255,255,0.05);
  z-index: 1;
  animation: fadeIn 1s ease;
}

/* Title */
h3 {
  color: #039d9dff;
  text-shadow: 0 0 12px rgba(3, 3, 3, 0.6);
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Inputs and select */
select, input {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  border-radius: 10px !important;
  padding: 10px;
}
select:focus, input:focus {
  outline: none;
  border-color: #00e676;
  box-shadow: 0 0 8px rgba(0,255,200,0.4);
}

/* Button */
.btn-success {
  background: linear-gradient(90deg, #00e676, #00bfa5);
  border: none;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,255,200,0.3);
  transition: 0.3s;
}
.btn-success:hover {
  background: linear-gradient(90deg, #00ffc8, #00e676);
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(0,255,200,0.5);
}

/* Alert styling */
.alert-danger {
  background: rgba(255,0,0,0.1);
  border: 1px solid rgba(255,0,0,0.3);
  color: #ff6b6b;
  border-radius: 10px;
}

/* Footer text */
.footer-text {
  color: #aaa;
  font-size: 0.9em;
  margin-top: 20px;
  text-shadow: 0 0 6px rgba(255,255,255,0.15);
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<div class="card text-center">
  <h3 class="mb-4"><i class="bi bi-door-open-fill"></i> Staff Login</h3>
  <?php if($msg): ?><div class="alert alert-danger py-2"><?= $msg ?></div><?php endif; ?>
  
  <form method="post">
    <div class="mb-3">
      <label for="role" class="form-label">Select Role</label>
      <select name="role" id="role" class="form-select" required>
        <option value="">-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="cashier">Cashier</option>
        <option value="chef">Chef</option>
        <option value="waiter">Waiter</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password" required>
    </div>
    <button type="submit" name="login" class="btn btn-success w-100">Login</button>
  </form>

  <p class="footer-text">© <?= date('Y') ?> Singha Bakers Staff Portal</p>
</div>

</body>
</html>
