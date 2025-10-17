<?php
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn=new mysqli($host,$user,$pass,$db);
$num=$_GET['num'];
$conn->query("UPDATE tables_status SET is_available=1 WHERE table_number=$num");
header("Location: admin.php");
?>
