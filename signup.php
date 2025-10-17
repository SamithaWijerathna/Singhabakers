<?php
session_start();
$conn = new mysqli("localhost","root","","singhabakers");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if(isset($_POST['signup'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']); // use password_hash() later for security

    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        $error = "Email already registered!";
    } else {
        $conn->query("INSERT INTO users (name,email,password) VALUES('$name','$email','$password')");
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'user';
        header("Location: user_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
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
.card {
    border-radius: 15px;
      background: rgba(255, 254, 254, 1);
}
.btn-primary {
    background-color: #28a745;
    border: none;
}
.btn-primary:hover {
    background-color: #218838;
}
.logo {
    font-weight: 600;
    color: #28a745;
}
</style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-3">Sign Up</h3>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="signup" class="btn btn-success w-100">Sign Up</button>
                    </form>
                    <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
