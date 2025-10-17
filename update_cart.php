<?php
session_start();

if (!isset($_POST['action']) || $_POST['action'] !== 'add') {
    exit('Invalid request');
}

$food_id = intval($_POST['food_id']);
if ($food_id <= 0) exit('Invalid food ID');

// DB connection
$host="localhost"; $user="root"; $pass=""; $db="singhabakers";
$conn = new mysqli($host,$user,$pass,$db);
if ($conn->connect_error) exit('DB connection failed');

if (!isset($_SESSION['user_id'])) {
    exit('not_logged_in');
}

$user_id = intval($_SESSION['user_id']);

// --- Update DB Cart ---
$res = $conn->query("SELECT * FROM cart WHERE user_id=$user_id AND food_id=$food_id");
if ($res->num_rows > 0) {
    $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id=$user_id AND food_id=$food_id");
} else {
    $conn->query("INSERT INTO cart (user_id, food_id, quantity) VALUES ($user_id, $food_id, 1)");
}

// --- Update session cart ---
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_SESSION['cart'][$food_id])) {
    $_SESSION['cart'][$food_id]['qty']++;
} else {
    $_SESSION['cart'][$food_id] = ['id' => $food_id, 'qty' => 1];
}

// Return success
echo 'success';
exit;
?>
