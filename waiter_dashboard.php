<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$host = "localhost"; $db = "singhabakers"; $user = "root"; $pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB connection failed: ".$conn->connect_error);

// Waiter session check
$waiter_id = $_SESSION['staff_id'] ?? 0;
$waiter_name = $_SESSION['staff_name'] ?? 'Waiter';
if(!$waiter_id){
    die("Invalid session. Please login.");
}

// Mark order as completed
if(isset($_GET['complete_order'])){
    $order_id = intval($_GET['complete_order']);
    $conn->query("UPDATE orders SET status='completed' WHERE id=$order_id");

    // Release table
    $table_id = $conn->query("SELECT table_id FROM orders WHERE id=$order_id")->fetch_assoc()['table_id'];
    if($table_id) $conn->query("UPDATE tables_status SET is_available=1 WHERE id=$table_id");

    header("Location: waiter_dashboard.php");
    exit;
}

// Fetch Cooking Orders
$cooking_orders = $conn->query("
    SELECT o.id, t.table_number
    FROM orders o
    LEFT JOIN tables_status t ON o.table_id=t.id
    WHERE o.status='cooking' AND o.order_type='Dine-In'
    ORDER BY o.created_at ASC
");

// Fetch Ready Orders
$ready_orders = $conn->query("
    SELECT o.id, t.table_number, GROUP_CONCAT(f.name SEPARATOR ', ') as foods
    FROM orders o
    LEFT JOIN tables_status t ON o.table_id=t.id
    LEFT JOIN order_items oi ON oi.order_id=o.id
    LEFT JOIN foods f ON f.id=oi.food_id
    WHERE o.status='ready' AND o.order_type='Dine-In'
    GROUP BY o.id
    ORDER BY o.created_at ASC
");

// Fetch Completed Orders (History)
$completed_orders = $conn->query("
    SELECT o.id, t.table_number, GROUP_CONCAT(f.name SEPARATOR ', ') as foods, o.created_at
    FROM orders o
    LEFT JOIN tables_status t ON o.table_id=t.id
    LEFT JOIN order_items oi ON oi.order_id=o.id
    LEFT JOIN foods f ON f.id=oi.food_id
    WHERE o.status='completed' AND o.order_type='Dine-In'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

// Fetch Announcements
$announcements = $conn->query("SELECT * FROM admin_announcements ORDER BY created_at DESC");

// Top summary counts
$today = date('Y-m-d');
$total_today = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(created_at)='$today'")->fetch_assoc()['c'];
$total_cooking = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='cooking' AND order_type='Dine-In'")->fetch_assoc()['c'];
$total_ready = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='ready' AND order_type='Dine-In'")->fetch_assoc()['c'];

// Table availability summary
$available_tables = $conn->query("SELECT COUNT(*) AS c FROM tables_status WHERE is_available=1")->fetch_assoc()['c'];
$occupied_tables = $conn->query("SELECT COUNT(*) AS c FROM tables_status WHERE is_available=0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Waiter Dashboard | Singha Bakers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<style>
/* === Global Base === */
body {
    background: radial-gradient(circle at top left, #1a1a1a, #0d0d0d);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    min-height: 100vh;
    overflow-x: hidden;
}

/* === 3D Animated Background Glow === */
body::before {
    content: "";
    position: fixed;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(0, 230, 118, 0.2), transparent 70%),
                radial-gradient(circle at bottom right, rgba(0, 200, 255, 0.2), transparent 70%);
    animation: glowmove 12s infinite alternate;
    z-index: -1;
}
@keyframes glowmove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(10%, 10%); }
}

/* === Sidebar (Modern Neon Glass Style) === */
.sidebar {
    width: 230px;
    background: linear-gradient(180deg, rgba(18,18,18,0.98), rgba(30,30,30,0.95));
    backdrop-filter: blur(12px);
    box-shadow: 8px 0 20px rgba(0, 0, 0, 0.6);
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    padding-top: 60px;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
    z-index: 10;
}

/* Sidebar title/logo */
.sidebar h4 {
    text-align: center;
    color: #00ffc8;
    font-weight: 600;
    letter-spacing: 1px;
    margin-bottom: 25px;
    text-shadow: 0 0 8px rgba(0,255,200,0.5);
}

/* Menu container for better structure */
.sidebar .menu {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 15px;
}

/* Menu button-like style */
.sidebar a {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    color: #bbb;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 10px 15px;
    text-decoration: none;
    font-size: 0.95em;
    transition: all 0.25s ease;
}

/* Hover & active effects */
.sidebar a:hover {
    background: linear-gradient(90deg, rgba(0,230,118,0.3), rgba(0,255,200,0.15));
    color: #fff;
    box-shadow: 0 0 10px rgba(0,230,118,0.3);
    transform: translateX(5px);
}

.sidebar a.active {
    background: linear-gradient(90deg, rgba(0,230,118,0.45), rgba(0,255,200,0.25));
    color: #fff;
    border-color: rgba(0,230,118,0.5);
    box-shadow: 0 0 15px rgba(0,230,118,0.4);
}

/* Add icons if needed */
.sidebar a i {
    font-size: 1.1em;
    color: #00e676;
    transition: all 0.25s ease;
}
.sidebar a:hover i {
    color: #00ffc8;
    text-shadow: 0 0 6px rgba(0,255,200,0.6);
}


/* === Main Area === */
.main-content {
    margin-left: 250px;
    padding: 25px;
    transition: margin-left 0.3s;
}
.main-content h2 {
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* === Summary Cards (Top Boxes) === */
.summary-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}
.summary-box {
    background: rgba(0, 0, 0, 0.38);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(22, 22, 22, 0.15);
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}
.summary-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(255, 255, 255, 0.06);
}
.summary-box h5 {
    color: #ffffffff;
    margin-bottom: 8px;
    font-weight: 600;
}
.summary-box span {
    font-size: 1.8em;
    font-weight: bold;
    color: #06c7eeff;
}

/* === Cards === */
.card {
    background: rgba(5, 5, 5, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(8px);
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(8, 8, 8, 0.88);
}
.card h4 {
    color: #ffffffff;
    font-weight: 600;
}

/* === Buttons === */
.btn-success {
    background: linear-gradient(135deg, #00c0e6ff, #007fc8ff);
    border: none;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 230, 118, 0.3);
    transition: all 0.2s ease-in-out;
}
.btn-success:hover {
    background: linear-gradient(135deg, #03843dff, #049143ff);
    transform: translateY(-2px);

}

/* === Status Tags === */
.status-cooking, .status-ready, .status-completed {
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 500;
}
.status-cooking { background:#ffa500; color:#000; }
.status-ready { background:#00e676; color:#000; }
.status-completed { background:#555; color:#fff; }

/* === Lists & Inputs === */
ul { list-style: none; padding: 0; margin: 0; }
li {
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}
input.search {
    background: rgba(255,255,255,0.05);
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px;
    width: 100%;
    margin-bottom: 12px;
    transition: all 0.3s ease;
}
input.search:focus {
    border-color: #00e676;
    box-shadow: 0 0 10px rgba(0,230,118,0.3);
    outline: none;
}

/* === Notification Badge === */
.badge-notify {
    background: #ff5252;
    color: white;
    border-radius: 50%;
    padding: 3px 7px;
    font-size: 0.75em;
    font-weight: 600;
    margin-left: 5px;
    box-shadow: 0 0 8px rgba(255, 82, 82, 0.6);
}

/* === Scrollbar Custom === */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #00e676, #00c853);
    border-radius: 10px;
}
::-webkit-scrollbar-track {
    background: #1a1a1a;
}

</style>
</head>
<body>

<div class="sidebar">
<h4 class="text-center text-light">Waiter Menu</h4>
<a href="#" onclick="showSection('serving')">🍽️ Ready to serve <span id="notifyBadge" class="badge-notify" style="display:none">0</span></a>
<a href="#" onclick="showSection('cooking')">🍳 Upcoming..</a>
<a href="#" onclick="showSection('history')">📜 Order History</a>
<a href="#" onclick="showSection('announcements')">📣 Announcements</a>
<a href="logout.php">🔓 Logout</a>
</div>

<div class="main-content">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2>👨‍🍳 Waiter Dashboard</h2>
</div>

<!-- TOP SUMMARY BAR -->
<div class="summary-bar">
    <div class="summary-box">
        <h5>Today's Orders</h5>
        <span><?= $total_today ?></span>
    </div>
    <div class="summary-box">
        <h5>Upcoming Orders</h5>
        <span><?= $total_cooking ?></span>
    </div>
    <div class="summary-box">
        <h5>Serving Orders</h5>
        <span><?= $total_ready ?></span>
    </div>
    <div class="summary-box">
        <h5>Available Tables</h5>
        <span><?= $available_tables ?></span>
    </div>
    <div class="summary-box">
        <h5>Occupied Tables</h5>
        <span><?= $occupied_tables ?></span>
    </div>
</div>

<!-- Serving Section -->
<div id="serving" class="section">
<div class="card p-3">
<h4>🍽️ Ready to serve</h4>
<input type="text" class="search" placeholder="Search Table..." onkeyup="filterList('servingList', this.value)">
<ul id="servingList">
<?php while($s=$ready_orders->fetch_assoc()): ?>
<li>
<span>Table <?= $s['table_number'] ?> - <?= $s['foods'] ?></span>
<div>
<span class="status-ready">Ready</span>
<a href="?complete_order=<?= $s['id'] ?>" class="btn btn-success btn-sm ms-2">Completed</a>
</div>
</li>
<?php endwhile; ?>
<?php if($ready_orders->num_rows==0) echo "<li>No orders ready for serving</li>"; ?>
</ul>
</div>
</div>

<!-- Cooking Section -->
<div id="cooking" class="section" style="display:none">
<div class="card p-3">
<h4>🍳 Upcoming..</h4>
<input type="text" class="search" placeholder="Search Table..." onkeyup="filterList('cookingList', this.value)">
<ul id="cookingList">
<?php while($c=$cooking_orders->fetch_assoc()): ?>
<li>Table <?= $c['table_number'] ?> - Order #<?= $c['id'] ?> <span class="status-cooking">Cooking</span></li>
<?php endwhile; ?>
<?php if($cooking_orders->num_rows==0) echo "<li>No upcoming orders</li>"; ?>
</ul>
</div>
</div>

<!-- Order History Section -->
<div id="history" class="section" style="display:none">
<div class="card p-3">
<h4>📜 Order History</h4>
<input type="text" class="search" placeholder="Search Table..." onkeyup="filterList('historyList', this.value)">
<ul id="historyList">
<?php while($h=$completed_orders->fetch_assoc()): ?>
<li>Table <?= $h['table_number'] ?> - <?= $h['foods'] ?> <span class="status-completed">Completed</span></li>
<?php endwhile; ?>
<?php if($completed_orders->num_rows==0) echo "<li>No completed orders yet</li>"; ?>
</ul>
</div>
</div>

<!-- Announcements Section -->
<div id="announcements" class="section" style="display:none">
<div class="card p-3">
<h4>📣 Announcements</h4>
<ul>
<?php while($ann=$announcements->fetch_assoc()): ?>
<li>
<strong><?= htmlspecialchars($ann['title']) ?></strong> - <?= nl2br(htmlspecialchars($ann['message'])) ?>
<span class="text-muted" style="font-size:0.8em"><?= date('d M Y H:i', strtotime($ann['created_at'])) ?></span>
</li>
<?php endwhile; ?>
<?php if($announcements->num_rows==0) echo "<li>No announcements</li>"; ?>
</ul>
</div>
</div>
</div>

<script>
function showSection(id){
    document.querySelectorAll('.section').forEach(s=>s.style.display='none');
    document.getElementById(id).style.display='block';
}

// Filter function for search boxes
function filterList(listId, query){
    query = query.toLowerCase();
    document.querySelectorAll('#'+listId+' li').forEach(li=>{
        li.style.display = li.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

// Live notification for ready orders
function fetchReadyCount(){
    $.get('waiter_ready_count.php', function(data){
        const count = parseInt(data);
        const badge = document.getElementById('notifyBadge');
        if(count>0){
            badge.style.display='inline-block';
            badge.textContent=count;
            if(!badge.dataset.notified){
                alert(`🔔 ${count} orders are ready for serving!`);
                badge.dataset.notified = true;
            }
        } else {
            badge.style.display='none';
            badge.dataset.notified = false;
        }
    });
}
setInterval(fetchReadyCount, 5000);
fetchReadyCount();
</script>

</body>
</html>
