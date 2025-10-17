<?php
session_start();

// Check logged-in user/staff
$userName = $_SESSION['user_name'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;

$staffName = $_SESSION['staff_name'] ?? null;
$staffRole = $_SESSION['role'] ?? null;

// Cart count
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;

// Current page for active link highlight
$current_page = basename($_SERVER['PHP_SELF']);

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $message = trim($_POST['message']);

    if ($name && $email && $message) {
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'singhabakers@gmail.com';       // Replace with your email
            $mail->Password   = 'gmrj quht kmke gtme';          // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom($email, $name);
            $mail->addAddress('singhabakers@gmail.com', 'Singha Bakers'); // Destination email

            // Content
            $mail->isHTML(true);
            $mail->Subject = "New message from Singha Bakers Contact Form";
            $mail->Body    = "<p><b>Name:</b> $name</p>
                              <p><b>Email:</b> $email</p>
                              <p><b>Message:</b><br>$message</p>";

            $mail->send();
            $success = "Your message has been sent successfully!";
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us - Singha Bakers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background: radial-gradient(circle at top left, #1a1a1a, #0d0d0d);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    margin: 0;
}
.section-title {
    font-size: 2.3rem;
    font-weight: 700;
    text-align: center;
    color: #04d4a7ff;
    margin-bottom: 2rem;
}
.card {
    background: rgba(5,5,5,0.8);
    border-radius: 15px;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 30px;
}
.btn-success {
    background: #00ffc8;
    border: none;
    color: #000;
    transition: 0.3s;
}
.btn-success:hover {
    background: #04d4a7ff;
    color: #fff;
}
input, textarea {
    background: rgba(0,0,0,0.6);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
}
input:focus, textarea:focus {
    outline: none;
    border-color: #04d4a7ff;
    box-shadow: 0 0 10px #04d4a7ff;
}
.contact-info i {
    color: #04d4a7ff;
}

body {
    background: radial-gradient(circle at top left, #1a1a1a, #0d0d0d);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    min-height: 100vh;
    overflow-x: hidden;
}

/* === 3D Animated Background Glow === */
body::before {
    content: "";
    position: fixed;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(0, 230, 118, 0.2), transparent 70%),
                radial-gradient(circle at bottom right, rgba(0, 200, 255, 0.2), transparent 70%);
    animation: glowmove 12s infinite alternate;
    z-index: -1;
}
@keyframes glowmove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(10%, 10%); }
}

/* === Navbar === */
.navbar {
    background: rgba(10, 10, 10, 0.7);
    backdrop-filter: blur(10px);
    box-shadow: 0 5px 25px rgba(0, 230, 118, 0.2);
    padding: 12px 25px;
}
.navbar a.nav-link {
    color: #ddd;
    transition: 0.3s;
}
.navbar a.nav-link:hover {
    color: #048d6fff;

}

/* === Cards === */
.card {
    background: rgba(0, 0, 0, 0.77);
    border-radius: 15px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transition: all 0.3s;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(1, 1, 1, 0.25);
}
.card img {
    border-radius: 12px 12px 0 0;
    height: 220px;
    object-fit: cover;
}
.card-title {
    color: #ffffffff;
}
.card-text {
    color: #ccc;
}
.map-container {
    width: 100%;
    height: 400px;
    border-radius: 15px;
    overflow: hidden;
    margin-top: 30px;
}

.form-label {
    color: #ffffffff;
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<section class="py-5">
    <div class="container">
        <h2 class="section-title">Contact Us</h2>

        <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php elseif($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="row text-center contact-info mt-5">
            <div class="col-md-6 mb-3">
                <i class="fa fa-phone fa-2x mb-2"></i>
                <p>+94 37 222 3333</p>
            </div>
            <div class="col-md-6 mb-3">
                <i class="fa fa-envelope fa-2x mb-2"></i>
                <p>info@singhabakers.lk</p>
            </div>
        </div>

        <!-- Map -->
        <div class="map-container">
            <iframe src="https://www.google.com/maps?q=kurunegala&output=embed"
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
