<link rel="stylesheet" href="style_dashboard.css">


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

include 'universal_notifications.php';



if(isset($_POST['send_msg'])){
    $type = $_POST['type'];
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $message = $conn->real_escape_string($_POST['message']);
    $scheduled_at = !empty($_POST['scheduled_at']) ? "'".$_POST['scheduled_at']."'" : "NULL";

    $conn->query("INSERT INTO admin_announcements (type,title,message,scheduled_at) 
                  VALUES ('$type','$title','$message',$scheduled_at)");
    echo "<script>alert('Message sent successfully'); window.location='admin.php?tab=chat_tab';</script>";
    exit;
}




// --- Password change ---
$msg = "";
if(isset($_POST['change_password'])){
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $id = $_SESSION['admin_id'];
    $check = $conn->query("SELECT * FROM adminlogin WHERE id=$id AND password='$old'");
    if($check->num_rows>0){
        $conn->query("UPDATE adminlogin SET password='$new' WHERE id=$id");
        $msg = "✅ Password updated successfully.";
    } else { $msg = "❌ Incorrect old password."; }
}



// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$category_count = $categories->num_rows;

// Fetch foods
$foods = $conn->query("SELECT f.*, c.name as category_name 
                       FROM foods f 
                       LEFT JOIN categories c ON f.category_id=c.id 
                       ORDER BY f.name ASC");
$food_count = $foods->num_rows;

// Count foods per category
$food_per_category = [];
$cats = $conn->query("SELECT id, name FROM categories");
while($c = $cats->fetch_assoc()){
    $count = $conn->query("SELECT COUNT(*) as total FROM foods WHERE category_id=".$c['id'])->fetch_assoc()['total'];
    $food_per_category[$c['name']] = $count;
}



// --- Date filter ---
$filter_date = $_GET['date'] ?? date('Y-m-d');

// --- Fetch orders for the selected date ---
$orders = $conn->query("
    SELECT o.*, u.name AS user_name, t.table_number 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN tables_status t ON o.table_id = t.id
    WHERE DATE(o.created_at)='$filter_date'
    ORDER BY o.created_at DESC
");

// --- Calculate total income for the day ---
$total_income = $conn->query("
    SELECT SUM(total_amount) AS total FROM orders
    WHERE DATE(created_at)='$filter_date' AND status='completed'
")->fetch_assoc()['total'] ?? 0;

// --- Fetch past 7 days income for chart ---
$past7 = $conn->query("
    SELECT DATE(created_at) AS day, SUM(total_amount) AS total
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status='completed'
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$chart_labels = [];
$chart_data = [];
while($p = $past7->fetch_assoc()){
    $chart_labels[] = $p['day'];
    $chart_data[] = $p['total'];
}


// Fetch users with order stats
$users = $conn->query("
    SELECT u.*, 
        COALESCE((SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id), 0) AS total_orders,
        COALESCE((SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id), 0) AS total_spend
    FROM users u
    ORDER BY u.created_at DESC
");

// Delete User
if(isset($_GET['delete_user'])){
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id=$id");
    echo "<script>alert('User deleted successfully'); window.location='admin.php?tab=users_tab';</script>";
    exit;
}

// Count total users
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];


// --- Fetch Data ---
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$foods = $conn->query("SELECT foods.*, categories.name as category_name FROM foods LEFT JOIN categories ON foods.category_id=categories.id");
$tables = $conn->query("SELECT * FROM tables");
$orders = $conn->query("
    SELECT o.*, u.name AS user_name, t.id AS table_number
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN tables t ON o.table_id = t.id
    ORDER BY CASE WHEN o.status='pending' THEN 0 ELSE 1 END, o.id DESC
");



$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$messages = $conn->query("SELECT messages.*, users.name as user_name FROM messages LEFT JOIN users ON messages.user_id=users.id ORDER BY messages.id DESC LIMIT 50");

// --- Dashboard stats ---
$totalFoods = $conn->query("SELECT COUNT(*) as count FROM foods")->fetch_assoc()['count'];
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$bookedTables = $conn->query("SELECT COUNT(*) as count FROM tables WHERE status='booked'")->fetch_assoc()['count'];
$availableTables = $conn->query("SELECT COUNT(*) as count FROM tables WHERE status='available'")->fetch_assoc()['count'];

// --- Revenue chart ---
$revenueData = [];
$res = $conn->query("SELECT DATE(created_at) as date, SUM(total_amount) as revenue 
                     FROM orders 
                     GROUP BY DATE(created_at) 
                     ORDER BY DATE(created_at) DESC 
                     LIMIT 30");
while($r = $res->fetch_assoc()){
    $revenueData[] = $r;
}
$revenueData = array_reverse($revenueData);
$revenueDates = array_map(fn($r)=>$r['date'], $revenueData);
$revenueValues = array_map(fn($r)=>$r['revenue'], $revenueData);


// --- Discounts with Food Details ---
$discounts = $conn->query("
    SELECT d.*, f.name AS food_actual_name, f.price AS food_price
    FROM discounts d
    LEFT JOIN foods f ON d.food_id = f.id
    ORDER BY d.id DESC
");

// --- Fetch inventory data for analytics ---
// --- Fetch inventory data for analytics ---
$inventory = $conn->query("SELECT * FROM inventory");

// Initialize counts
$total_items = 0;
$low_stock_items = 0;
$out_stock_items = 0;
$normal_stock_items = 0;

$chart_labels = [];
$chart_values = [];

while($i = $inventory->fetch_assoc()){
    $total_items++;
    if($i['status'] == 'low') $low_stock_items++;
    elseif($i['status'] == 'out') $out_stock_items++;
    else $normal_stock_items++;

    $chart_labels[] = $i['item_name'];
    $chart_values[] = $i['quantity'];
}

// Low stock rate %
$low_stock_rate = $total_items ? round(($low_stock_items/$total_items)*100,2) : 0;

// Pie chart data
$pie_labels = ['Normal', 'Low Stock', 'Out of Stock'];
$pie_values = [$normal_stock_items, $low_stock_items, $out_stock_items];
?>



?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Singha Bakers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#1c1c1c; color:#fff;   padding-top: 40px; /* e.g., 60-80px depending on your bar */
    transition: padding-top 0.3s ease; /* smooth adjustment if bar resizes */ }
.sidebar {
    width: 220px;
    background: linear-gradient(180deg, #1f1f1f, #2c2c2c);
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    padding-top: 30px;
    box-shadow: 3px 0 10px rgba(0,0,0,0.5);
    border-right: 1px solid #333;
      padding-top: 60px; /* e.g., 60-80px depending on your bar */
    transition: padding-top 0.3s ease; /* smooth adjustment if bar resizes */

}
.sidebar h4 {
    color: #fff;
    text-align: center;
    margin-bottom: 30px;
}
.sidebar a {
    display: block;
    color: #ccc;
    padding: 12px 20px;
    margin: 5px 10px;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.3s;
}
.sidebar a:hover {
    background: #007bff;
    color: #fff;
    transform: translateX(5px);
}

/* Main Content */
.main-content {
    margin-left: 240px;
    padding: 25px;
}

/* Headings */
h2 { margin-bottom:20px; }
h4 { margin-top:20px; }


.sidebar a { display:block; color:#fff; padding:10px; text-decoration:none; margin-bottom:5px; border-radius:6px;}
.content { margin-left:230px; padding:20px; }
.card { background:#2c2c2c; color:#fff; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
.table-dark.table-hover tbody tr:hover { background:#3a3a3a; cursor:pointer; }
.table-gradient thead { background: linear-gradient(90deg,#007bff,#6610f2); }
.btn-primary:hover, .btn-success:hover, .btn-warning:hover, .btn-danger:hover { opacity:0.85; transition:0.3s; }
#chatbox { height:300px; overflow-y:auto; background:#2c2c2c; padding:10px; border:1px solid #555; }
.card { background:#2c2c2c; padding:15px; margin-top:20px; }
.table-dark th, .table-dark td { vertical-align: middle; }
</style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center">Admin Panel</h4>
    <a href="#" class="active" onclick="showTab('dashboard')">Dashboard</a>
    <a href="#" onclick="showTab('foods_tab')">Foods</a>

    <a href="#" onclick="showTab('orders_tab')">Orders</a>
    
    <a href="#" onclick="showTab('tables_tab')">Tables</a>
    <a href="#" onclick="showTab('inventory_tab')">Inventory</a>
   <a href="#" onclick="showTab('discounts_tab')">Running Discounts</a>



    <a href="#" onclick="showTab('users_tab')">Users</a>
    <a href="#" onclick="showTab('chat_tab')">Chat</a>
    <a href="#" onclick="showTab('export_tab')">Export Data</a>
    <a href="#" onclick="showTab('password_tab')">Change Password</a>
    <a href="logout.php" class="mt-3 btn btn-danger w-70">Logout</a>
</div>

<div class="content">

<!-- Dashboard -->
<div id="dashboard" class="tab-content active">
    <h3>Dashboard</h3>
    <div class="row mb-3">
        <div class="col-md-3"><div class="card p-3 text-center"><h5>Total Foods</h5><h3><?= $totalFoods ?></h3></div></div>
        <div class="col-md-3"><div class="card p-3 text-center"><h5>Total Categories</h5><h3><?= $totalCategories ?></h3></div></div>
        <div class="col-md-3"><div class="card p-3 text-center"><h5>Total Orders</h5><h3><?= $totalOrders ?></h3></div></div>
        <div class="col-md-3"><div class="card p-3 text-center"><h5>Booked Tables</h5><h3><?= $bookedTables ?></h3></div></div>
    </div>
    <canvas id="ordersChart" height="100"></canvas>
    <canvas id="revenueChart" height="100" class="mt-4"></canvas>
</div>

<!-- Foods tab -->
<div id="foods_tab" class="tab-content" style="display:none;">
    <h3>Foods</h3>
   <!-- Summary Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card text-center">
            <h4>Total Categories</h4>
            <p style="font-size:24px;"><?= $category_count ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <h4>Total Foods</h4>
            <p style="font-size:24px;"><?= $food_count ?></p>
        </div>
    </div>
</div>

<!-- Foods per Category -->
<div class="card mt-4">
    <h4>Foods Per Category</h4>
    <table class="table table-dark table-hover">
        <tr><th>Category</th><th>Number of Foods</th></tr>
        <?php foreach($food_per_category as $cat => $count): ?>
        <tr>
            <td><?= htmlspecialchars($cat) ?></td>
            <td><?= $count ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Foods List -->
<div class="card mt-4">
    <h4>All Foods</h4>
    <table class="table table-dark table-hover">
        <tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Category</th><th>Image</th></tr>
        <?php while($f = $foods->fetch_assoc()): ?>
        <tr>
            <td><?= $f['id'] ?></td>
            <td><?= htmlspecialchars($f['name']) ?></td>
            <td><?= htmlspecialchars($f['description']) ?></td>
            <td><?= $f['price'] ?></td>
            <td><?= htmlspecialchars($f['category_name']) ?></td>
            <td>
                <?php if($f['image']): ?>
                    <img src="<?= $f['image'] ?>" width="50">
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Categories List -->
<div class="card mt-4">
    <h4>All Categories</h4>
    <table class="table table-dark table-hover">
        <tr><th>ID</th><th>Name</th></tr>
        <?php $categories->data_seek(0); while($c = $categories->fetch_assoc()): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</div>



<!-- Orders tab -->
<div id="orders_tab" class="tab-content" style="display:none;">
    <h3>Orders (<?= htmlspecialchars($filter_date) ?>)</h3>

    <!-- Date filter -->
    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="date" name="date" class="form-control form-control-sm" value="<?= $filter_date ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
    </form>

    <!-- Total income -->
    <div class="mb-3">
        <strong>Total Income for <?= htmlspecialchars($filter_date) ?>:</strong> $<?= number_format($total_income, 2) ?>
    </div>

    <!-- Orders Table -->
    <table class="table table-bordered table-dark table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Table</th>
                <th>Food Items</th>
                <th>Quantities</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($o = $orders->fetch_assoc()): ?>
                <?php 
                    // Highlight pending orders
                    $rowClass = ($o['status']=='pending') ? 'table-warning' : '';
                    // Fetch order items
                    $itemsRes = $conn->query("
                        SELECT oi.*, f.name AS food_name
                        FROM order_items oi
                        LEFT JOIN foods f ON oi.food_id = f.id
                        WHERE oi.order_id=".$o['id']
                    );
                    $foodNames = [];
                    $quantities = [];
                    while($item = $itemsRes->fetch_assoc()){
                        $foodNames[] = htmlspecialchars($item['food_name']);
                        $quantities[] = $item['quantity'];
                    }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['user_name']) ?></td>
                    <td><?= $o['table_number'] ?? '-' ?></td>
                    <td><?= implode('<br>', $foodNames) ?></td>
                    <td><?= implode('<br>', $quantities) ?></td>
                    <td><?= $o['total_amount'] ?></td>
                    <td><?= ucfirst($o['status']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Past 7 days income chart -->
    <div class="mt-4">
        <canvas id="incomeChart" style="background:#fff;padding:10px;"></canvas>
    </div>
</div>


<!-- Inventory Analytics Tab -->
<div id="inventory_tab" class="tab-content" style="display:none;">
    <h3>Inventory Analytics</h3>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center" style="background:#2c2c2c;">
                <h5>Total Items</h5>
                <h3><?= $total_items ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center" style="background:#2c2c2c;">
                <h5>Low Stock Items</h5>
                <h3><?= $low_stock_items ?> (<?= $low_stock_rate ?>%)</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center" style="background:#2c2c2c;">
                <h5>Out of Stock Items</h5>
                <h3><?= $out_stock_items ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <canvas id="inventoryBarChart" height="100"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="inventoryPieChart" height="100"></canvas>
        </div>
    </div>
</div>



<!-- Discounts tab -->
<div id="discounts_tab" class="tab-content" style="display:none;">
    <h3>Available Discounts</h3>
    <table class="table table-bordered table-dark table-hover">
        <thead class="table-gradient">
            <tr>
                <th>ID</th>
                <th>Food Name</th>
                <th>Original Price</th>
                <th>Discount Percent</th>
                <th>Discounted Price</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        <?php while($d = $discounts->fetch_assoc()): 
            $food_name = $d['food_actual_name'] ?? $d['food_name'];
            $food_price = $d['food_price'] ?? 0;
            $discounted_price = $food_price * (1 - $d['discount_percent']/100);
        ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><?= htmlspecialchars($food_name) ?></td>
            <td>$<?= number_format($food_price,2) ?></td>
            <td contenteditable="true"
                onBlur="updateField(<?= $d['id'] ?>,'discounts','discount_percent',this.innerText)">
                <?= $d['discount_percent'] ?>%
            </td>
            <td>$<?= number_format($discounted_price,2) ?></td>
            <td><?= $d['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Tables tab -->
<div id="tables_tab" class="tab-content" style="display:none;">
    <h3>Tables</h3>
    <table class="table table-bordered table-dark table-hover text-center">
        <thead class="table-gradient">
            <tr>
                <th>ID</th>
                <th>Table Number</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $table_status = $conn->query("SELECT * FROM tables_status ORDER BY table_number ASC");
        while($t = $table_status->fetch_assoc()):
            $is_available = $t['is_available'] == 1;
        ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td>Table <?= $t['table_number'] ?></td>
                <td>
                    <?php if($is_available): ?>
                        <span class="text-success">Available</span>
                    <?php else: ?>
                        <span class="text-danger">Booked</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!$is_available): ?>
                        <a href="?release=<?= $t['id'] ?>" class="btn btn-warning btn-sm">Release</a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


<!-- Users Tab -->
<div id="users_tab" class="tab-content" style="display:none;">
    <h3>Registered Users (Total: <?= $total_users ?>)</h3>
    <table class="table table-bordered table-dark table-hover">
        <thead class="table-gradient">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Registered At</th>

                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= $u['created_at'] ?></td>

                <td>
                    <a href="?delete_user=<?= $u['id'] ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Are you sure you want to delete this user?');">
                       Remove
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


<div id="chat_tab" class="tab-content" style="display:none;">

<h4>Send Announcement / Chat</h4>
<form method="post" id="adminMessageForm">
    <div class="mb-2">
        <label>Type</label>
        <select name="type" id="msgType" class="form-select" required>
            <option value="chat">Chat Message</option>
            <option value="meeting">Schedule Meeting</option>
            <option value="alert">Important Alert</option>
        </select>
    </div>
    <div class="mb-2" id="titleDiv" style="display:none;">
        <label>Title</label>
        <input type="text" name="title" class="form-control" placeholder="Meeting / Alert Title">
    </div>
    <div class="mb-2">
        <label>Message</label>
        <textarea name="message" class="form-control" required></textarea>
    </div>
    <div class="mb-2" id="scheduleDiv" style="display:none;">
        <label>Schedule Date & Time</label>
        <input type="datetime-local" name="scheduled_at" class="form-control">
    </div>
    <button type="submit" name="send_msg" class="btn btn-primary">Send</button>
</form>
</div>


<!-- Export Data -->
<div id="export_tab" class="tab-content" style="display:none;">
    <h3>Export Data</h3>
    <a href="export.php?type=foods" class="btn btn-primary mb-2">Export Foods CSV</a>
    <a href="export.php?type=orders" class="btn btn-warning mb-2">Export Orders CSV</a>
    <a href="export.php?type=tables" class="btn btn-success mb-2">Export Tables CSV</a>
</div>

<!-- Password Change -->
<div id="password_tab" class="tab-content" style="display:none;">
    <h3>Change Password</h3>
    <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <form method="post">
        <input type="password" name="old_password" class="form-control mb-2" placeholder="Old Password" required>
        <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
        <button type="submit" name="change_password" class="btn btn-danger w-100">Update Password</button>
    </form>
</div>

</div>

<script>
function showTab(tabId){
    document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');
    document.getElementById(tabId).style.display='block';
    document.querySelectorAll('.sidebar a').forEach(a=>a.classList.remove('active'));
    document.querySelector(`.sidebar a[onclick="showTab('${tabId}')"]`).classList.add('active');
}

// Chart.js Orders Chart
const ctxOrders = document.getElementById('ordersChart').getContext('2d');
new Chart(ctxOrders, {
    type: 'bar',
    data: {
        labels: ['Foods','Categories','Orders','Booked Tables'],
        datasets: [{
            label: 'Counts',
            data: [<?= $totalFoods ?>, <?= $totalCategories ?>, <?= $totalOrders ?>, <?= $bookedTables ?>],
            backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545']
        }]
    },
    options: { responsive:true }
});

const ctx = document.getElementById('incomeChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Income Last 7 Days',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero:true } }
    }
});

// Revenue Chart
const ctxRev = document.getElementById('revenueChart').getContext('2d');
new Chart(ctxRev, {
    type:'line',
    data:{
        labels: <?= json_encode($revenueDates) ?>,
        datasets:[{
            label:'Revenue',
            data: <?= json_encode($revenueValues) ?>,
            backgroundColor:'rgba(0,123,255,0.2)',
            borderColor:'#007bff',
            fill:true,
            tension:0.4
        }]
    },
    options:{ responsive:true }
});

// Inline editing AJAX
function updateField(id,table,field,value){
    fetch('update_field.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id+'&table='+table+'&field='+field+'&value='+encodeURIComponent(value)
    }).then(res=>res.text()).then(r=>console.log(r));
}

// AJAX auto-refresh chat
setInterval(()=>{
    fetch('admin_refresh.php?type=chat').then(res=>res.text()).then(data=>{
        document.getElementById('chatbox').innerHTML=data;
    });
},5000);


document.getElementById('msgType').addEventListener('change', function(){
    const type = this.value;
    document.getElementById('titleDiv').style.display = (type=='meeting'||type=='alert')?'block':'none';
    document.getElementById('scheduleDiv').style.display = (type=='meeting')?'block':'none';
});





// --- Bar chart: Quantity per item ---
const ctxBar = document.getElementById('inventoryBarChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Quantity',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(0,123,255,0.7)',
            borderColor: '#007bff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Inventory Quantity per Item' }
        },
        scales: { y: { beginAtZero: true } }
    }
});

// --- Pie chart: Stock status proportion ---
const ctxPie = document.getElementById('inventoryPieChart').getContext('2d');
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: <?= json_encode($pie_labels) ?>,
        datasets: [{
            data: <?= json_encode($pie_values) ?>,
            backgroundColor: ['#28a745','#ffc107','#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Stock Status Proportion' }
        }
    }
});

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
