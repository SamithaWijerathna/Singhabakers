<?php

session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB Connection failed: ".$conn->connect_error);

// Use staff_id from login
$user_id = $_SESSION['staff_id'] ?? 0;
if(!$user_id || !isset($_POST['id'])){
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$ann_id = intval($_POST['id']);

// Fetch current noted_by JSON
$res = $conn->query("SELECT noted_by FROM admin_announcements WHERE id=$ann_id");
if($res && $res->num_rows){
    $row = $res->fetch_assoc();
    $noted = json_decode($row['noted_by'] ?? '[]', true);
    if(!in_array($user_id, $noted)){
        $noted[] = $user_id;
        $noted_json = $conn->real_escape_string(json_encode($noted));
        $conn->query("UPDATE admin_announcements SET noted_by='$noted_json' WHERE id=$ann_id");
    }
}

echo "Success";

