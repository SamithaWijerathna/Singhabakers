<link rel="stylesheet" href="style_dashboard.css">

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


$host="localhost"; 
$db="singhabakers"; 
$user="root"; 
$pass="";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$staff_id = $_SESSION['staff_id'] ?? 0;
if(!$staff_id){
    die("Invalid session. Please login.");
}

// Include universal notifications (fetches announcements for this user)
include 'universal_notifications.php';


// Fetch all announcements
$announcements = $conn->query("SELECT * FROM admin_announcements ORDER BY created_at DESC");

// Get unread announcements count
$unread_ann = $conn->query("
    SELECT * FROM admin_announcements 
    WHERE NOT JSON_CONTAINS(noted_by, JSON_QUOTE('$staff_id'))
    ORDER BY created_at DESC
");
$unread_count = $unread_ann->num_rows;

// --- Handle new order submission ---
$show_receipt_modal = false;
$receipt_data = [];

if(isset($_POST['submit_order'])){
    $food_ids = explode(',', $_POST['food_id']);
    $quantities = explode(',', $_POST['quantity']);
    $table_id = intval($_POST['table_id']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $order_type = $conn->real_escape_string($_POST['order_type']);

    // Create temporary guest user
    $username = "Guest_" . time();
    $email = "guest" . time() . "@example.com";
    $password = password_hash("guest123", PASSWORD_DEFAULT);
    $role = "customer";
    $conn->query("INSERT INTO users(name,email,password,role,created_at) VALUES('$username','$email','$password','$role',NOW())");
    $user_id = $conn->insert_id;

    // Calculate total and apply discounts
    $total_amount = 0;
    $order_items_data = [];
    foreach($food_ids as $i => $fid){
        $fid = intval($fid);
        $qty = intval($quantities[$i]);
        $res = $conn->query("SELECT name, price FROM foods WHERE id=$fid");
        $food = $res->fetch_assoc();
        $price = $food['price'] ?? 0;

        // Check for discount
        $discount_res = $conn->query("SELECT discount_percent FROM discounts WHERE food_id=$fid");
        $discount_percent = ($discount_res && $discount_res->num_rows>0) ? $discount_res->fetch_assoc()['discount_percent'] : 0;

        $discounted_price = $price;
        if($discount_percent>0){
            $discounted_price = $price * (1 - $discount_percent/100);
        }

        $total_amount += $discounted_price * $qty;
        $order_items_data[] = [
            'name'=>$food['name'],
            'qty'=>$qty,
            'price'=>$discounted_price,
            'original_price'=>$price,
            'discount'=>$discount_percent
        ];
    }

    // Insert order
    $conn->query("INSERT INTO orders(user_id,total_amount,order_type,payment_method,status,table_id,created_at)
                  VALUES($user_id,$total_amount,'$order_type','$payment_method','pending',$table_id,NOW())");
    $order_id = $conn->insert_id;

    // Insert order items
    foreach($order_items_data as $item){
        $conn->query("INSERT INTO order_items(order_id,food_id,quantity,price)
                      VALUES($order_id,(
                          SELECT id FROM foods WHERE name='".$conn->real_escape_string($item['name'])."'
                      ),".$item['qty'].",".$item['price'].")");
    }

    if($table_id>0){
        $conn->query("UPDATE tables_status SET is_available=0 WHERE id=$table_id");
    }

    $show_receipt_modal = true;
    $receipt_data = [
        'order_id'=>$order_id,
        'table_id'=>$table_id,
        'payment_method'=>$payment_method,
        'order_type'=>$order_type,
        'total_amount'=>$total_amount,
        'items'=>$order_items_data
    ];
}

// --- Cancel Order ---
if(isset($_GET['cancel_order'])){
    $order_id = intval($_GET['cancel_order']);
    $order_res = $conn->query("SELECT table_id FROM orders WHERE id=$order_id");
    $table_id = $order_res->fetch_assoc()['table_id'] ?? 0;
    $conn->query("UPDATE orders SET status='canceled' WHERE id=$order_id");
    if($table_id>0){
        $conn->query("UPDATE tables_status SET is_available=1 WHERE id=$table_id");
    }
    header("Location: cashier_dashboard.php");
    exit;
}

// --- Release Table ---
if(isset($_GET['release_table'])){
    $num = intval($_GET['release_table']);
    $conn->query("UPDATE tables_status SET is_available=1 WHERE table_number=$num");
    header("Location: cashier_dashboard.php?released=1");
    exit;
}

$foods = $conn->query("SELECT * FROM foods ORDER BY name ASC");
$tables = $conn->query("SELECT * FROM tables_status ORDER BY table_number ASC");
$orders = $conn->query("
    SELECT 
        o.*, 
        t.table_number,
        GROUP_CONCAT(f.name SEPARATOR ', ') AS food_names
    FROM orders o
    LEFT JOIN tables_status t ON o.table_id = t.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN foods f ON oi.food_id = f.id
    GROUP BY o.id
    ORDER BY o.id DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cashier Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body { background-color: #1c1c1c; color: white; font-family: Arial, sans-serif; }
.sidebar { width: 220px; background-color: #2c2c2c; position: fixed; top: 0; left: 0; height: 100%; padding-top: 80px; }
.sidebar a { display: block; color: #ddd; padding: 12px 20px; text-decoration: none; transition: 0.3s; }
.sidebar a:hover { background-color: #007bff; color: white; }
.main-content { margin-left: 240px; padding: 20px; }
.card { background-color: #2c2c2c; }
.hidden { display: none; }
.food-list { max-height: 350px; overflow-y: auto; }
.table-dark th, .table-dark td { vertical-align: middle; }
.cart-box { background: #2c2c2c; padding: 15px; border-radius: 10px; max-height: 250px; overflow-y: auto; position: fixed; bottom: 0; left: 240px; right: 0; }
.modal-content { background-color: #2c2c2c; color: white; }
.strikethrough { text-decoration: line-through; color: #bbb; }
.announcements-container {
    position: fixed;
    top: 80px; /* below top navigation if any */
    left: 240px; /* after sidebar */
    right: 20px;
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding: 10px;
    z-index: 2000; /* high enough to be above modals/cart */
}



.announcement-card {
    background: linear-gradient(135deg, #2a2a2a, #3a3a3a);
    color: #fff;
    border-left: 5px solid #007bff;
    border-radius: 10px;
    padding: 15px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
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
    <h4 class="text-center text-light mb-4">Cashier Menu</h4>
    <a href="#" onclick="showSection('newOrder')">🧾 New Order</a>
    <a href="#" onclick="showSection('ordersList')">📋 Existing Orders</a>
    <a href="#" onclick="showSection('releaseTable')">🪑 Release Table</a>
    <a href="#" onclick="showSection('announcements')">📣 Announcements
<?php if($unread_count>0): ?>
<span class="badge bg-danger badge-ann"><?= $unread_count ?></span>
<?php endif; ?>
</a>
<a href="logout.php">🔓 Logout</a>

</div>

<div class="main-content">
<?php if(isset($_GET['released'])): ?>
<div class="alert alert-warning">Table released successfully!</div>
<?php endif; ?>

<div id="newOrder">
<h4>🧾 Create New Order</h4>
<input type="text" id="foodSearch" class="form-control mb-2" placeholder="Search food...">

<div class="food-list mb-3">
    <table class="table table-dark table-hover" id="foodTable">
        <thead><tr><th>Food</th><th>Price</th><th>Action</th></tr></thead>
        <tbody>
            <?php $foods->data_seek(0); while($f=$foods->fetch_assoc()):
                $discount_res = $conn->query("SELECT discount_percent FROM discounts WHERE food_id=".$f['id']);
                $discount_percent = ($discount_res && $discount_res->num_rows>0) ? $discount_res->fetch_assoc()['discount_percent'] : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($f['name']) ?> <?= $discount_percent>0 ? "({$discount_percent}% off)" : "" ?></td>
                <td>
                <?php if($discount_percent>0): ?>
                    <span class="strikethrough">$<?= number_format($f['price'],2) ?></span> $<?= number_format($f['price']*(1-$discount_percent/100),2) ?>
                <?php else: ?>
                    $<?= number_format($f['price'],2) ?>
                <?php endif; ?>
                </td>
                <td><button type="button" class="btn btn-sm btn-success" 
                onclick="openQtyModal(<?= $f['id'] ?>,'<?= htmlspecialchars($f['name']) ?>',<?= $f['price'] ?>,<?= $discount_percent ?>)">
                    Add
                </button></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<form method="post" id="orderForm">
<input type="hidden" name="food_id" id="hiddenFoods">
<input type="hidden" name="quantity" id="hiddenQuantities">

<div class="row g-3">
    <div class="col-md-4">
        <label>Table</label>
        <select name="table_id" id="tableSelect" class="form-select" required>
        <?php
            $tbl2 = $conn->query("SELECT * FROM tables_status ORDER BY table_number ASC");
            $firstAvailable = false;
            while($t=$tbl2->fetch_assoc()):
        ?>
            <option value="<?= $t['id'] ?>" <?= (!$firstAvailable && $t['is_available']) ? 'selected' : '' ?>>
                Table <?= $t['table_number'] ?> <?= !$t['is_available'] ? '(Booked)' : '' ?>
            </option>
        <?php if($t['is_available'] && !$firstAvailable) $firstAvailable = true; endwhile; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label>Payment Method</label>
        <select name="payment_method" class="form-select" required>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
        </select>
    </div>
    <div class="col-md-4">
        <label>Order Type</label>
        <select name="order_type" class="form-select" required>
            <option value="dine_in">Dine In</option>
            <option value="takeaway">Takeaway</option>
        </select>
    </div>
</div>

<div class="text-end mt-4">
    <button type="submit" name="submit_order" class="btn btn-primary px-4">Submit Order</button>
</div>
</form>
</div>

<div id="ordersList" class="hidden">
<h5>Existing Orders</h5>
<table class="table table-dark table-hover">
<tr><th>ID</th><th>Table</th><th>Foods</th><th>Total</th><th>Status</th></tr>
<?php while($o=$orders->fetch_assoc()): ?>
<tr>
    <td><?= $o['id'] ?></td>
    <td><?= $o['table_number'] ?: '-' ?></td>
    <td><?= htmlspecialchars($o['food_names'] ?: '—') ?></td>
    <td>$<?= number_format($o['total_amount'],2) ?></td>
    <td><?= ucfirst($o['status']) ?></td>
  
</tr>
<?php endwhile; ?>

</table>
</div>

<div id="releaseTable" class="hidden">
<h5>Release Booked Tables</h5>
<table class="table table-dark table-hover">
<tr><th>Table</th><th>Status</th><th>Action</th></tr>
<?php
$tbl3 = $conn->query("SELECT * FROM tables_status ORDER BY table_number ASC");
while($row=$tbl3->fetch_assoc()):
?>
<tr>
    <td>Table <?= $row['table_number'] ?></td>
    <td><?= $row['is_available'] ? 'Available' : 'Booked' ?></td>
    <td>
        <?php if(!$row['is_available']): ?>
            <button class="btn btn-warning btn-sm" onclick="confirmRelease(<?= $row['table_number'] ?>)">Release</button>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>

<div class="cart-box">
<h6>🛒 Cart</h6>
<table class="table table-dark table-sm" id="cartTable">
<thead><tr><th>Food</th><th>Qty</th><th>Price</th><th></th></tr></thead>
<tbody></tbody>
</table>
<div class="text-end fw-bold">Total: $<span id="cartTotal">0.00</span></div>
</div>

<div class="modal fade" id="qtyModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Select Quantity</h5></div>
<div class="modal-body text-center">
<input type="number" id="qtyInput" class="form-control w-50 mx-auto" min="1" value="1">
<input type="hidden" id="modalFoodId">
<input type="hidden" id="modalFoodName">
<input type="hidden" id="modalFoodPrice">
<input type="hidden" id="modalFoodDiscount">
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-success" onclick="addFromModal()">Add</button>
</div>
</div>
</div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Receipt</h5></div>
<div class="modal-body">
<?php if($show_receipt_modal): ?>
<p>Order ID: <?= $receipt_data['order_id'] ?></p>
<p>Table: <?= $receipt_data['table_id'] ?></p>
<p>Order Type: <?= $receipt_data['order_type'] ?></p>
<p>Payment Method: <?= $receipt_data['payment_method'] ?></p>
<table class="table table-dark table-sm">
<thead><tr><th>Food</th><th>Qty</th><th>Price</th></tr></thead>
<tbody>
<?php foreach($receipt_data['items'] as $item): ?>
<tr>
<td><?= $item['name'] ?><?= $item['discount']>0 ? " ({$item['discount']}% off)" : "" ?></td>
<td><?= $item['qty'] ?></td>
<td>
<?php if($item['discount']>0): ?>
<span class="strikethrough">$<?= number_format($item['original_price']*$item['qty'],2) ?></span>
$<?= number_format($item['price']*$item['qty'],2) ?>
<?php else: ?>
$<?= number_format($item['price']*$item['qty'],2) ?>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="fw-bold">Total: $<?= number_format($receipt_data['total_amount'],2) ?></p>
<?php endif; ?>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
<button class="btn btn-primary" onclick="window.print()">Print</button>
</div>



</div>

</div>
</div>

<div id="announcements" class="hidden">
<div class="announcements-container">
<?php while($ann=$announcements->fetch_assoc()):
    $noted = json_decode($ann['noted_by'] ?? '[]', true);
    $isNoted = in_array($staff_id, $noted);
?>
<div class="announcement-card <?= $isNoted ? 'noted' : 'unread' ?>">
    <div class="announcement-header">
        <strong><?= htmlspecialchars($ann['title'] ?? 'Announcement') ?></strong>
        <span class="" style="font-size:0.8em"><?= date('d M Y H:i', strtotime($ann['created_at'])) ?></span>
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

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
function showSection(id){
    document.querySelectorAll('.main-content > div').forEach(d=>d.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');

    const annContainer = document.querySelector('.announcements-container');
    if(annContainer) annContainer.style.display = (id === 'announcements') ? 'flex' : 'none';

    const cartBox = document.querySelector('.cart-box');
    if(cartBox) cartBox.style.display = (id === 'newOrder') ? 'block' : 'none';
}



document.getElementById('foodSearch').addEventListener('keyup', function(){
    const val = this.value.toLowerCase();
    document.querySelectorAll('#foodTable tbody tr').forEach(row=>{
        const name = row.children[0].textContent.toLowerCase();
        row.style.display = name.includes(val) ? '' : 'none';
    });
});

let cart = [];

function openQtyModal(id, name, price, discount=0){
    document.getElementById('modalFoodId').value=id;
    document.getElementById('modalFoodName').value=name;
    document.getElementById('modalFoodPrice').value=price;
    document.getElementById('modalFoodDiscount').value=discount;
    document.getElementById('qtyInput').value=1;
    new bootstrap.Modal(document.getElementById('qtyModal')).show();
}

function addFromModal(){
    const id = parseInt(document.getElementById('modalFoodId').value);
    const name = document.getElementById('modalFoodName').value;
    let price = parseFloat(document.getElementById('modalFoodPrice').value);
    const discount = parseFloat(document.getElementById('modalFoodDiscount').value);
    const qty = parseInt(document.getElementById('qtyInput').value);

    if(discount>0) price = price * (1 - discount/100);

    let item = cart.find(i=>i.id===id);
    if(item){ item.qty += qty; }
    else{ cart.push({id,name,price,qty,discount}); }
    updateCart();
    bootstrap.Modal.getInstance(document.getElementById('qtyModal')).hide();
}

function removeFromCart(id){
    cart = cart.filter(i=>i.id!==id);
    updateCart();
}

function updateCart(){
    const tbody = document.querySelector('#cartTable tbody');
    tbody.innerHTML='';
    let total=0;
    cart.forEach(item=>{
        let linePrice = item.price * item.qty;
        total += linePrice;
        tbody.innerHTML += `
        <tr>
            <td>${item.name}${item.discount>0 ? ' ('+item.discount+'% off)' : ''}</td>
            <td>${item.qty}</td>
            <td>${item.discount>0 ? '<span class="strikethrough">$'+(item.price/(1-item.discount/100)*item.qty).toFixed(2)+'</span> $'+linePrice.toFixed(2) : '$'+linePrice.toFixed(2)}</td>
            <td><button class='btn btn-danger btn-sm' onclick='removeFromCart(${item.id})'>×</button></td>
        </tr>`;
    });
    document.getElementById('cartTotal').textContent = total.toFixed(2);
    document.getElementById('hiddenFoods').value = cart.map(i=>i.id).join(',');
    document.getElementById('hiddenQuantities').value = cart.map(i=>i.qty).join(',');
}

function confirmRelease(num){
    if(confirm(`Are you sure you want to release Table ${num}?`)){
        window.location.href=`?release_table=${num}`;
    }
}

<?php if($show_receipt_modal): ?>
new bootstrap.Modal(document.getElementById('receiptModal')).show();
<?php endif; ?>


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
