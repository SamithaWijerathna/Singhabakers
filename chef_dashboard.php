<link rel="stylesheet" href="style_dashboard.css">
<?php
session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

include 'universal_notifications.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch all announcements
$announcements = $conn->query("SELECT * FROM admin_announcements ORDER BY created_at DESC");

// Get unread announcements count
$unread_ann = $conn->query("
    SELECT * FROM admin_announcements 
    WHERE NOT JSON_CONTAINS(noted_by, JSON_QUOTE('$user_id'))
    ORDER BY created_at DESC
");
$unread_count = $unread_ann->num_rows;

// --- Handle Inventory Status ---
if(isset($_GET['inventory_id']) && isset($_GET['status'])){
    $id = intval($_GET['inventory_id']);
    $status = $_GET['status']; // low / out
    $conn->query("UPDATE inventory SET status='$status' WHERE id=$id");
    header("Location: chef_dashboard.php");
    exit;
}

// --- Handle Order Status Update ---
if(isset($_POST['order_id']) && isset($_POST['order_status'])){
    $order_id = intval($_POST['order_id']);
    $status = $_POST['order_status'];
    $conn->query("UPDATE orders SET status='$status' WHERE id=$order_id");
    header("Location: chef_dashboard.php");
    exit;
}

// --- Inventory Search ---
$inventory_search = $_GET['inventory_search'] ?? '';

// --- Fetch inventory ---
$inventory_sql = "SELECT * FROM inventory";
if($inventory_search){
    $inventory_sql .= " WHERE item_name LIKE '%".$conn->real_escape_string($inventory_search)."%'";
}
$inventory_sql .= " ORDER BY created_at DESC";
$inventory = $conn->query($inventory_sql);

$normal_items = []; $alert_items = [];
while($i = $inventory->fetch_assoc()){
    if($i['status'] == 'normal'){ $normal_items[] = $i; } 
    else{ $alert_items[] = $i; }
}

// --- Fetch Active Orders with Food Names ---
$search_table = $_GET['table'] ?? '';
$search_user  = $_GET['user'] ?? '';

$active_orders_sql = "
SELECT o.*, u.name as user_name, t.table_number,
       GROUP_CONCAT(f.name SEPARATOR ', ') AS food_names
FROM orders o
LEFT JOIN users u ON o.user_id=u.id
LEFT JOIN tables t ON o.table_id=t.id
LEFT JOIN order_items oi ON oi.order_id = o.id
LEFT JOIN foods f ON f.id = oi.food_id
WHERE o.status IN ('pending','cooking','ready')
";

if($search_table) $active_orders_sql .= " AND t.table_number LIKE '%".$conn->real_escape_string($search_table)."%' ";
if($search_user) $active_orders_sql .= " AND u.name LIKE '%".$conn->real_escape_string($search_user)."%' ";

$active_orders_sql .= "
GROUP BY o.id
ORDER BY 
    CASE WHEN o.status='pending' THEN 0
         WHEN o.status='cooking' THEN 1
         WHEN o.status='ready' THEN 2
    END, o.created_at DESC
";
$active_orders = $conn->query($active_orders_sql);

// --- Fetch Completed Orders ---
$completed_orders_sql = "
SELECT o.*, u.name as user_name, t.table_number
FROM orders o
LEFT JOIN users u ON o.user_id=u.id
LEFT JOIN tables t ON o.table_id=t.id
WHERE o.status='completed'
ORDER BY o.created_at DESC";
$completed_orders = $conn->query($completed_orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chef Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: #121212;
    color: #f1f1f1;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-top: 80px;
    transition: padding-top 0.3s ease;
}
/* Sidebar */
.sidebar {
    width: 220px;
    background: linear-gradient(180deg, #1f1f1f, #2c2c2c);
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    padding-top: 80px;
    box-shadow: 3px 0 10px rgba(0,0,0,0.5);
    border-right: 1px solid #333;
}
.sidebar h4 { color: #fff; text-align: center; margin-bottom: 30px; }
.sidebar a { display: block; color: #ccc; padding: 12px 20px; margin: 5px 10px; border-radius: 8px; text-decoration: none; transition: 0.3s; }
.sidebar a:hover { background: #007bff; color: #fff; transform: translateX(5px); }
/* Main Content */
.main-content { margin-left: 240px; padding: 25px; }
/* Cards */
.card { background:#1e1e1e; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 5px 15px rgba(0,0,0,0.3); }
/* Inventory Status Labels */
.status-normal { background:#1e7e34;color:#fff;font-weight:600;border-radius:5px;text-align:center; }
.status-low { background:#ffc107;color:#000;font-weight:600;border-radius:5px;text-align:center; }
.status-out { background:#dc3545;color:#fff;font-weight:600;border-radius:5px;text-align:center; }
/* Buttons */
.btn { border-radius:6px; transition:0.3s; }
.btn:hover { transform:scale(1.05); }
/* Section visibility */
.hidden { display:none; }
.announcements-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-width: 650px;
    margin-top: 20px;
}

.announcement-card {
    background: linear-gradient(135deg, #1f1f1f, #2a2a2a);
    color: #f1f1f1;
    border-left: 5px solid #007bff;
    border-radius: 10px;
    padding: 15px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.announcement-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.5);
}

.announcement-card.unread {
    border-left-color: #ff4d4f; /* red for unread */
}

.announcement-card.noted {
    opacity: 0.7;
}

.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 1rem;
}

.announcement-body {
    font-size: 0.95rem;
    margin-bottom: 10px;
    white-space: pre-wrap;
}

.mark-noted-btn {
    padding: 3px 8px;
    font-size: 0.85rem;
}

.mark-noted-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

</style>
</head>
<body>

<div class="sidebar">
<h4>Chef Menu</h4>
<a href="#" onclick="showSection('inventory')">📦 Inventory</a>
<a href="#" onclick="showSection('activeOrders')">🔥 Active Orders</a>
<a href="#" onclick="showSection('completedOrders')">✅ Completed Orders</a>
<a href="#" onclick="showSection('announcements')">📣 Announcements
<?php if($unread_count>0): ?>
<span class="badge bg-danger badge-ann"><?= $unread_count ?></span>
<?php endif; ?>
</a>
<a href="logout.php">🔓 Logout</a>

</div>

<div class="main-content">
<h2>Chef Dashboard</h2>

<!-- Inventory Section -->
<div id="inventory">
<h4>Inventory</h4>
<form method="get" class="mb-3">
<input type="text" name="inventory_search" value="<?= htmlspecialchars($inventory_search) ?>" class="form-control" placeholder="Search inventory items">
</form>
<table class="table table-dark table-hover">
<tr><th>Item</th><th>Qty</th><th>Unit</th><th>Status</th><th>Restocked</th><th>Actions</th></tr>
<?php foreach(array_merge($normal_items, $alert_items) as $i): ?>
<tr>
<td><?= htmlspecialchars($i['item_name']) ?></td>
<td><?= $i['quantity'] ?></td>
<td><?= $i['unit'] ?></td>
<td class="<?= $i['status']=='normal'?'status-normal':($i['status']=='low'?'status-low':'status-out') ?>"><?= ucfirst($i['status']) ?></td>
<td><?= $i['restocked_amount'] ?> (<?= $i['restocked_at'] ?: '-' ?>)</td>
<td>
<?php if($i['status']=='normal'): ?>
<a href="?inventory_id=<?= $i['id'] ?>&status=low" class="btn btn-warning btn-sm">Low Stock</a>
<a href="?inventory_id=<?= $i['id'] ?>&status=out" class="btn btn-danger btn-sm">Out of Stock</a>
<?php else: ?>
<button class="btn btn-secondary btn-sm" disabled>Marked</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Active Orders Section -->
<div id="activeOrders" class="hidden">
<h4>Active Orders</h4>
<form method="get" class="row g-2 mb-3">
<div class="col-md-3"><input type="text" name="table" value="<?= htmlspecialchars($search_table) ?>" class="form-control" placeholder="Filter by Table"></div>
<div class="col-md-3"><input type="text" name="user" value="<?= htmlspecialchars($search_user) ?>" class="form-control" placeholder="Filter by User"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
</form>
<table class="table table-dark table-hover">
<tr><th>ID</th><th>User</th><th>Table</th><th>Food</th><th>Total</th><th>Status</th><th>Action</th></tr>
<?php 
$active_orders->data_seek(0);
while($o = $active_orders->fetch_assoc()): ?>
<tr class="<?= $o['status']=='pending'?'table-warning':'' ?> <?= $o['status']=='ready'?'table-success':'' ?>">
<td><?= $o['id'] ?></td>
<td><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></td>
<td><?= $o['table_number'] ?? '-' ?></td>
<td><?= htmlspecialchars($o['food_names'] ?? '-') ?></td>
<td><?= $o['total_amount'] ?></td>
<td><?= ucfirst($o['status']) ?></td>
<td>
<form method="post">
<input type="hidden" name="order_id" value="<?= $o['id'] ?>">
<select name="order_status" class="form-select form-select-sm mb-1">
<option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>Pending</option>
<option value="cooking" <?= $o['status']=='cooking'?'selected':'' ?>>Cooking</option>
<option value="ready" <?= $o['status']=='ready'?'selected':'' ?>>Ready</option>

</select>
<button type="submit" class="btn btn-primary btn-sm">Update</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- Completed Orders Section -->
<div id="completedOrders" class="hidden">
<h4>Completed Orders</h4>
<table class="table table-dark table-hover">
<tr><th>ID</th><th>User</th><th>Table</th><th>Total</th><th>Status</th><th>Completed At</th></tr>
<?php 
$completed_orders->data_seek(0);
while($o = $completed_orders->fetch_assoc()): ?>
<tr class="table-secondary">
<td><?= $o['id'] ?></td>
<td><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></td>
<td><?= $o['table_number'] ?? '-' ?></td>
<td><?= $o['total_amount'] ?></td>
<td><?= ucfirst($o['status']) ?></td>
<td><?= $o['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div id="announcements" class="hidden">
<div class="announcements-container">
<?php while($ann=$announcements->fetch_assoc()):
    $noted = json_decode($ann['noted_by'] ?? '[]', true);
    $isNoted = in_array($user_id, $noted);
?>
<div class="announcement-card <?= $isNoted ? 'noted' : 'unread' ?>">
    <div class="announcement-header">
        <strong><?= htmlspecialchars($ann['title'] ?? 'Announcement') ?></strong>
        <span class="text-muted" style="font-size:0.8em"><?= date('d M Y H:i', strtotime($ann['created_at'])) ?></span>
    </div>
    <div class="announcement-body"><?= nl2br(htmlspecialchars($ann['message'])) ?></div>
    <button class="btn btn-sm mark-noted-btn <?= $isNoted ? 'btn-secondary' : 'btn-success' ?>" 
            data-id="<?= $ann['id'] ?>" <?= $isNoted ? 'disabled' : '' ?>>
        <?= $isNoted ? 'Noted' : 'Mark as Noted' ?>
    </button>
</div>
<?php endwhile; ?>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showSection(id){
    document.querySelectorAll('.main-content > div').forEach(d=>d.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}
</script>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
$(document).ready(function(){
    $('.mark-noted-btn').click(function(){
        let btn = $(this);
        let id = btn.data('id');
        $.post('mark_as_noted.php', {id:id}, function(resp){
            btn.text('Noted');
            btn.prop('disabled', true);
        });
    });
});
</script>

</body>
</html>
