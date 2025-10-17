<?php
session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$type = $_GET['type'] ?? '';

if($type=='chat'){
    // Fetch latest 50 messages
    $messages = $conn->query("SELECT messages.*, users.name as user_name 
                              FROM messages 
                              LEFT JOIN users ON messages.user_id=users.id 
                              ORDER BY messages.id DESC LIMIT 50");
    while($m=$messages->fetch_assoc()){
        echo "<p><strong>".htmlspecialchars($m['user_name']).":</strong> ".htmlspecialchars($m['message'])."</p>";
    }
}

elseif($type=='tables'){
    // Fetch tables status table
    $tables = $conn->query("SELECT * FROM tables");
    echo '<table class="table table-bordered text-center">';
    echo '<thead class="table-dark"><tr><th>Table ID</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    while($t=$tables->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.$t['id'].'</td>';
        echo '<td>'.ucfirst($t['status']).'</td>';
        echo '<td>';
        if($t['status']=='booked'){
            echo '<a href="?release='.$t['id'].'" class="btn btn-warning btn-sm">Release</a>';
        } else { echo '<span class="text-success">Available</span>'; }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

elseif($type=='orders'){
    // Fetch latest 20 orders for notification panel
    $orders = $conn->query("SELECT orders.*, users.name as user_name, foods.name as food_name 
                            FROM orders 
                            LEFT JOIN users ON orders.user_id=users.id 
                            LEFT JOIN foods ON orders.food_id=foods.id 
                            ORDER BY orders.id DESC LIMIT 20");
    while($o=$orders->fetch_assoc()){
        echo '<p><strong>Order #'.$o['id'].' - '.htmlspecialchars($o['user_name']).'</strong>: '.htmlspecialchars($o['food_name']).' x'.$o['quantity'].' ('.$o['status'].')</p>';
    }
}

elseif($type=='notifications'){
    // Combine latest messages and orders
    $messages = $conn->query("SELECT messages.*, users.name as user_name 
                              FROM messages 
                              LEFT JOIN users ON messages.user_id=users.id 
                              ORDER BY messages.id DESC LIMIT 10");
    $orders = $conn->query("SELECT orders.*, users.name as user_name, foods.name as food_name 
                            FROM orders 
                            LEFT JOIN users ON orders.user_id=users.id 
                            LEFT JOIN foods ON orders.food_id=foods.id 
                            ORDER BY orders.id DESC LIMIT 10");

    echo '<h6>Latest Orders</h6>';
    while($o=$orders->fetch_assoc()){
        echo '<p><strong>Order #'.$o['id'].' - '.htmlspecialchars($o['user_name']).'</strong>: '.htmlspecialchars($o['food_name']).' x'.$o['quantity'].' ('.$o['status'].')</p>';
    }

    echo '<h6 class="mt-2">Latest Messages</h6>';
    while($m=$messages->fetch_assoc()){
        echo "<p><strong>".htmlspecialchars($m['user_name']).":</strong> ".htmlspecialchars($m['message'])."</p>";
    }
}
?>
