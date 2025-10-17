<?php
session_start();
if(!isset($_SESSION['user_id'])) exit('Not logged in');

require 'db.php'; // your database connection

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id']);
$rating = intval($_POST['rating']);
$comment = $conn->real_escape_string($_POST['comment']);

// Check if review already exists
$check = $conn->query("SELECT * FROM reviews WHERE order_id=$order_id AND user_id=$user_id");
if($check->num_rows > 0) exit('You already reviewed this order');

// Insert review
$conn->query("INSERT INTO reviews (user_id, order_id, rating, comment) VALUES ($user_id, $order_id, $rating, '$comment')");
echo 'success';
?>
