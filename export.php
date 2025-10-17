<?php
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$type = $_GET['type'] ?? '';
$filename = $type.'_export_'.date('Ymd_His').'.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$output = fopen('php://output','w');

if($type=='foods'){
    fputcsv($output,['ID','Name','Description','Price','Category','Image']);
    $res = $conn->query("SELECT foods.id, foods.name, foods.description, foods.price, categories.name as category_name, foods.image 
                         FROM foods LEFT JOIN categories ON foods.category_id=categories.id");
    while($row=$res->fetch_assoc()) fputcsv($output,$row);
}
elseif($type=='orders'){
    fputcsv($output,['ID','User','Food','Quantity','Status','Created At']);
    $res = $conn->query("SELECT orders.id, users.name as user_name, foods.name as food_name, orders.quantity, orders.status, orders.created_at 
                         FROM orders LEFT JOIN users ON orders.user_id=users.id 
                         LEFT JOIN foods ON orders.food_id=foods.id");
    while($row=$res->fetch_assoc()) fputcsv($output,$row);
}
elseif($type=='tables'){
    fputcsv($output,['ID','Status']);
    $res = $conn->query("SELECT * FROM tables");
    while($row=$res->fetch_assoc()) fputcsv($output,$row);
}
fclose($output);
