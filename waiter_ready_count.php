<?php
session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
$ready = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status='ready' AND order_type='Dine-In'")->fetch_assoc()['cnt'];
echo $ready;
