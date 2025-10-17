<?php
// update_field.php
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$id = intval($_POST['id']);
$table = $_POST['table'];
$field = $_POST['field'];
$value = $_POST['value'];

// Allow only specific fields for safety
$allowed_tables = ['foods','categories','inventory','user_discount'];
if(!in_array($table,$allowed_tables)) die('Invalid table');

$conn->query("UPDATE $table SET $field='$value' WHERE id=$id");
echo "Updated successfully";
?>
