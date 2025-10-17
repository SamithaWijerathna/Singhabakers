<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "singhabakers");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Handle login submission ---
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = md5($_POST['password']); // Assuming you store MD5-hashed passwords

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND password=?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Save session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // --- Check if we need to redirect to previous action (like Add to Cart) ---
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        }

        // Default redirect after normal login
        header("Location: user_dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Singha Bakers</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body>
<div class="container">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-5">
            <div class="card shadow-lg p-4">
                <h3 class="text-center mb-3 logo">Singha Bakers</h3>
                <h5 class="text-center mb-4">Welcome Back</h5>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </form>

                <p class="mt-3 text-center mb-0">Don't have an account? <a href="signup.php" class="text-success fw-bold">Sign Up</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
