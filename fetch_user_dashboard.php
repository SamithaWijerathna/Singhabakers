<?php
session_start();
if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$host = "localhost";
$db = "singhabakers";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

// --- User ID ---
$user_id = $_SESSION['user_id'];

// --- Summary ---
$summary = [
    'total_orders' => 0,
    'active_orders' => 0,
    'total_spent' => 0
];

$res = $conn->query("SELECT COUNT(*) as total_orders, 
                            SUM(total_amount) as total_spent 
                     FROM orders 
                     WHERE user_id='$user_id'");
if($row = $res->fetch_assoc()){
    $summary['total_orders'] = intval($row['total_orders']);
    $summary['total_spent'] = floatval($row['total_spent']);
}

$res = $conn->query("SELECT COUNT(*) as active_orders 
                     FROM orders 
                     WHERE user_id='$user_id' AND status IN ('pending','preparing')");
if($row = $res->fetch_assoc()){
    $summary['active_orders'] = intval($row['active_orders']);
}

// --- Profile ---
$profile = [];
$res = $conn->query("SELECT name,email,created_at FROM users WHERE id='$user_id'");
if($row = $res->fetch_assoc()){
    $profile = $row;
}

// --- Member Progress ---
$member_progress = [
    'level_name' => 'Bronze 🟤',
    'progress' => 0,
    'next_goal' => 30000,
    'total_discount' => 0
];

$total_spent = $summary['total_spent'];
if($total_spent >= 100000){
    $member_progress['level_name'] = 'Platinum ⚪';
    $member_progress['progress'] = 100;
    $member_progress['next_goal'] = 0;
}elseif($total_spent >= 50000){
    $member_progress['level_name'] = 'Gold 🟡';
    $member_progress['progress'] = ($total_spent-50000)/50000*100;
    $member_progress['next_goal'] = 100000;
}elseif($total_spent >= 30000){
    $member_progress['level_name'] = 'Silver ⚪';
    $member_progress['progress'] = ($total_spent-30000)/20000*100;
    $member_progress['next_goal'] = 50000;
}else{
    $member_progress['progress'] = $total_spent/30000*100;
    $member_progress['next_goal'] = 30000;
}

// Total discount earned
$res = $conn->query("SELECT SUM(discount_amount) as total_discount FROM user_discounts WHERE user_id='$user_id'");
if($row = $res->fetch_assoc()){
    $member_progress['total_discount'] = floatval($row['total_discount']);
}

// --- Recent Orders ---
$orders = [];
$res = $conn->query("SELECT * FROM orders WHERE user_id='$user_id' ORDER BY created_at DESC LIMIT 5");
while($order = $res->fetch_assoc()){
    $items = [];
    $q_items = $conn->query("
        SELECT oi.quantity, f.name 
        FROM order_items oi 
        JOIN foods f ON oi.food_id = f.id 
        WHERE oi.order_id='".$order['id']."'
    ");
    while($i = $q_items->fetch_assoc()){
        $items[] = $i;
    }
    $orders[] = [
        'id' => $order['id'],
        'items' => $items,
        'total_amount' => number_format($order['total_amount'],2),
        'status' => $order['status'],
        'table_number' => $order['table_info'] ?? '-'
    ];
}

// --- Notifications (optional) ---
$notifications = []; // You can fetch custom notifications if needed

echo json_encode([
    'summary' => $summary,
    'profile' => $profile,
    'member_progress' => $member_progress,
    'orders' => $orders,
    'notifications' => $notifications
]);
?>
