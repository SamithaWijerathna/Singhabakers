<link rel="stylesheet" href="style_dashboard.css">
<?php


session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Ensure user_id is set in session
if(!isset($_SESSION['user_id'])){
    // Replace 1 with the actual logged-in manager ID if needed
    $_SESSION['user_id'] = 1;
}

$user_id = $_SESSION['user_id'];

include 'universal_notifications.php';




// --- Include universal notifications / announcements ---
$announcements = $conn->query("
    SELECT * FROM admin_announcements
    ORDER BY created_at DESC
");

// --- Get unread announcements for this user ---
$unread_ann = $conn->query("
    SELECT * FROM admin_announcements 
    WHERE NOT JSON_CONTAINS(noted_by, JSON_QUOTE('$user_id'))
    ORDER BY created_at DESC
");
$unread_count = $unread_ann->num_rows;

// --- Discount Handlers ---
if(isset($_POST['add_discount'])){
    $food_id = intval($_POST['food_id']);
    $discount = floatval($_POST['discount_percent']);
    $food_name = $conn->query("SELECT name FROM foods WHERE id=$food_id")->fetch_assoc()['name'] ?? '';
    if($food_name){
        // Check if discount exists
        $check = $conn->query("SELECT * FROM discounts WHERE food_id=$food_id");
        if($check->num_rows > 0){
            $conn->query("UPDATE discounts SET discount_percent=$discount, updated_at=NOW() WHERE food_id=$food_id");
        } else {
            $conn->query("INSERT INTO discounts(food_id, food_name, discount_percent) VALUES($food_id,'$food_name',$discount)");
        }
    }
}

$discounts = $conn->query("SELECT * FROM discounts ORDER BY updated_at DESC");


// --- Inventory Handlers ---
if(isset($_POST['add_inventory'])){
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $quantity = intval($_POST['quantity']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $low_stock = intval($_POST['low_stock_threshold']);
    $conn->query("INSERT INTO inventory(item_name, quantity, unit, low_stock_threshold, status, created_at) 
                  VALUES('$item_name', $quantity, '$unit', $low_stock, 'normal', NOW())");
}
if(isset($_POST['restock_id'])){
    $id = intval($_POST['restock_id']);
    $amount = intval($_POST['restock_amount']);
    $conn->query("UPDATE inventory SET quantity = quantity + $amount, restocked_amount = $amount, restocked_at = NOW(), status='normal' WHERE id=$id");
}
$inventory = $conn->query("SELECT * FROM inventory ORDER BY created_at DESC");

// --- Categories Handlers ---
if(isset($_POST['add_category'])){
    $name = $conn->real_escape_string($_POST['category_name']);
    $conn->query("INSERT INTO categories(name) VALUES('$name')");
}
if(isset($_POST['edit_category'])){
    $id = intval($_POST['category_id']);
    $name = $conn->real_escape_string($_POST['category_name']);
    $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
}
if(isset($_GET['delete_category'])){
    $id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE id=$id");
}
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// --- Foods Handlers ---
if(isset($_POST['add_food'])){
    $name = $conn->real_escape_string($_POST['food_name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $category = intval($_POST['category_id']);
    $imagePath = "";

    if(!empty($_FILES['image']['name'])){
        $fileName = time().'_'.basename($_FILES['image']['name']);
        $target = "uploads/".$fileName;
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
            $imagePath = $target;
        }
    }

    $conn->query("INSERT INTO foods(name, description, price, category_id, image) VALUES('$name','$desc','$price',$category,'$imagePath')");
    header("Location: manager_dashboard.php?section=foods&msg=added");
    exit;
}

if(isset($_POST['edit_food'])){
    $id = intval($_POST['food_id']);
    $name = $conn->real_escape_string($_POST['food_name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $category = intval($_POST['category_id']);
    $imgSQL = "";

    if(!empty($_FILES['image']['name'])){
        $fileName = time().'_'.basename($_FILES['image']['name']);
        $target = "uploads/".$fileName;
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
            $imgSQL = ", image='$target'";
        }
    }

    $conn->query("UPDATE foods SET name='$name', description='$desc', price=$price, category_id=$category $imgSQL WHERE id=$id");
    header("Location: manager_dashboard.php?section=foods&msg=updated");
    exit;
}

if (isset($_GET['delete_food'])) {
    $id = intval($_GET['delete_food']);
    echo "Deleting food ID: $id<br>";

    // Delete related order items (if any)
    $conn->query("DELETE FROM order_items WHERE food_id=$id");

    // Delete food image file
    $res = $conn->query("SELECT image FROM foods WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
    }

    // Delete main food record
    if (!$conn->query("DELETE FROM foods WHERE id=$id")) {
        die("SQL Error: " . $conn->error);
    }

    header("Location: manager_dashboard.php?section=foods&msg=deleted");
    exit;
}


$foods = $conn->query("SELECT * FROM foods ORDER BY name ASC");


// Fetch categories again for the foods dropdown
$all_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");



?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manager Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#121212; color:#f1f1f1; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;   padding-top: 40px; /* e.g., 60-80px depending on your bar */
    /* smooth adjustment if bar resizes */ }
.sidebar { width:220px; position:fixed; top:60px; left:0; height:calc(100% - 60px);
          background:linear-gradient(180deg,#1f1f1f,#2c2c2c); padding-top:30px; border-right:1px solid #333;
          box-shadow:3px 0 10px rgba(0,0,0,0.5); }
.sidebar h4 { text-align:center; margin-bottom:30px; color:#fff; }
.sidebar a { display:block; color:#ccc; padding:12px 20px; margin:5px 10px; border-radius:8px; text-decoration:none; transition:0.3s; }
.sidebar a:hover { background:#007bff; color:#fff; transform:translateX(5px); }

.main-content { margin-left:240px; padding:25px; margin-top:20px; transition:0.3s; }

.card { background:#1e1e1e; border-radius:12px; padding:20px; margin-bottom:20px;
       box-shadow:0 4px 8px rgba(0,0,0,0.3); transition:0.3s; }
.card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.4); }

.table-dark th, .table-dark td { vertical-align: middle; }
.table-dark tr:hover { background:#333; }

#currentTime { position:fixed; top:15px; right:20px; font-weight:bold; color:#fff; }
.hidden { display:none; }
.announcements-container {
    position: relative; /* changed from fixed */
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding: 10px;
    z-index: 1; /* lower z-index since no need to be above everything */
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

<div id="currentTime"></div>

<div class="sidebar">
<h4>Manager Menu</h4>
<a href="#" onclick="showSection('inventory')">📦 Inventory</a>
<a href="#" onclick="showSection('categories')">🏷 Categories</a>
<a href="#" onclick="showSection('foods')">🍔 Foods</a>
<a href="#" onclick="showSection('discounts')">💸 Launch Discount</a>
<a href="#" onclick="showSection('announcements')">📣 Announcements
<?php if($unread_count>0): ?>
<span class="badge bg-danger badge-ann"><?= $unread_count ?></span>
<?php endif; ?>
</a>
<a href="logout.php">🔓 Logout</a>

</div>

<div class="main-content">
<h2>Manager Dashboard</h2>

<!-- Inventory Section -->
<div id="inventory">
<div class="card">
<h4>Inventory</h4>
<form method="post" class="row g-2 mb-3">
<div class="col-md-3"><input type="text" name="item_name" class="form-control" placeholder="Item Name" required></div>
<div class="col-md-2"><input type="number" name="quantity" class="form-control" placeholder="Quantity" required></div>
<div class="col-md-2"><input type="text" name="unit" class="form-control" placeholder="Unit" required></div>
<div class="col-md-2"><input type="number" name="low_stock_threshold" class="form-control" placeholder="Low Stock Threshold" required></div>
<div class="col-md-3"><button type="submit" name="add_inventory" class="btn btn-primary w-100">Add Item</button></div>
</form>

<table class="table table-dark table-hover">
<tr><th>Item</th><th>Qty</th><th>Unit</th><th>Status</th><th>Restock</th><th>Action</th></tr>
<?php while($i=$inventory->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($i['item_name']) ?></td>
<td><?= $i['quantity'] ?></td>
<td><?= $i['unit'] ?></td>
<td><?= ucfirst($i['status']) ?></td>
<td>
<form method="post" class="d-flex gap-1">
<input type="number" name="restock_amount" class="form-control form-control-sm" required>
<input type="hidden" name="restock_id" value="<?= $i['id'] ?>">
</td>
<td><button type="submit" class="btn btn-success btn-sm">Restock</button></td>
</form>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<!-- Categories Section -->
<div id="categories" class="hidden">
<div class="card">
<h4>Categories</h4>
<form method="post" class="row g-2 mb-3">
<div class="col-md-6"><input type="text" name="category_name" class="form-control" placeholder="Category Name" required></div>
<div class="col-md-6"><button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button></div>
</form>
<table class="table table-dark table-hover">
<tr><th>ID</th><th>Name</th><th>Actions</th></tr>
<?php
$categories->data_seek(0); // Reset pointer for safe looping
while($c=$categories->fetch_assoc()):
?>
<tr>
<td><?= $c['id'] ?></td>
<td><?= htmlspecialchars($c['name']) ?></td>
<td>
<form method="post" class="d-flex gap-1">
<input type="hidden" name="category_id" value="<?= $c['id'] ?>">
<input type="text" name="category_name" class="form-control form-control-sm" value="<?= htmlspecialchars($c['name']) ?>" required>
<button type="submit" name="edit_category" class="btn btn-success btn-sm">Update</button>
<a href="?delete_category=<?= $c['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<!-- Foods Section -->
<div id="foods" class="hidden">
<div class="card">
<h4>Foods</h4>
<form method="post" enctype="multipart/form-data" class="row g-2 mb-3">
<div class="col-md-3"><input type="text" name="food_name" class="form-control" placeholder="Food Name" required></div>
<div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description"></div>
<div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required></div>
<div class="col-md-2">
<select name="category_id" class="form-control" required>
<option value="">Select Category</option>
<?php while($cat=$all_categories->fetch_assoc()): ?>
<option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="col-md-2"><input type="file" name="image" class="form-control"></div>
<div class="col-md-12 mt-2"><button type="submit" name="add_food" class="btn btn-primary w-100">Add Food</button></div>
</form>

<table class="table table-dark table-hover">
<tr><th>ID</th><th>Name</th><th>Desc</th><th>Price</th><th>Category</th><th>Image</th><th>Actions</th></tr>
<?php
$foods->data_seek(0); // Reset pointer
while($f=$foods->fetch_assoc()):
$all_categories->data_seek(0); // reset for dropdown
?>
<tr>
<td><?= $f['id'] ?></td>
<td>
<form method="post" enctype="multipart/form-data" class="d-flex gap-1">
<input type="hidden" name="food_id" value="<?= $f['id'] ?>">
<input type="text" name="food_name" class="form-control form-control-sm" value="<?= htmlspecialchars($f['name']) ?>" required>
</td>
<td><input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars($f['description']) ?>"></td>
<td><input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?= $f['price'] ?>" required></td>
<td>
<select name="category_id" class="form-control form-control-sm" required>
<?php while($cat2=$all_categories->fetch_assoc()): ?>
<option value="<?= $cat2['id'] ?>" <?= $cat2['id']==$f['category_id'] ? "selected" : "" ?>><?= htmlspecialchars($cat2['name']) ?></option>
<?php endwhile; ?>
</select>
</td>
<td>
<?php if($f['image']): ?><img src="<?= $f['image'] ?>" width="50"><?php endif; ?>
<input type="file" name="image" class="form-control form-control-sm mt-1">
</td>
<td>
<button type="submit" name="edit_food" class="btn btn-success btn-sm mb-1">Update</button>
<a href="manager_dashboard.php?section=foods&delete_food=<?= $f['id'] ?>"
   onclick="return confirm('Are you sure you want to delete this food?');"
   class="btn btn-danger btn-sm">Delete</a>

</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>


<!-- Discounts Section -->
<div id="discounts" class="hidden">
<div class="card">
<h4>Launch Discount</h4>
<table class="table table-dark table-hover">
<tr><th>Food ID</th><th>Food Name</th><th>Discount %</th><th>Action</th></tr>
<?php while($d=$discounts->fetch_assoc()): ?>
<tr>
<td><?= $d['food_id'] ?></td>
<td><?= htmlspecialchars($d['food_name']) ?></td>
<td>
<form method="post" class="d-flex gap-2">
<input type="hidden" name="food_id" value="<?= $d['food_id'] ?>">
<input type="number" step="0.01" name="discount_percent" class="form-control form-control-sm" value="<?= $d['discount_percent'] ?>" required>
</td>
<td><button type="submit" name="add_discount" class="btn btn-success btn-sm">Save</button></td>
</form>
</tr>
<?php endwhile; ?>
</table>

<h5 class="mt-4">Add Discount for Food</h5>
<form method="post" class="row g-2 mb-3">
<div class="col-md-4">
<select name="food_id" class="form-control" required>
<option value="">Select Food</option>
<?php
$foods->data_seek(0);
while($f=$foods->fetch_assoc()):
?>
<option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="col-md-2"><input type="number" step="0.01" name="discount_percent" class="form-control" placeholder="Discount %" required></div>
<div class="col-md-2"><button type="submit" name="add_discount" class="btn btn-primary w-100">Add Discount</button></div>
</form>
</div>
</div>

<div id="announcements" class="hidden">
  <div class="announcements-container"></div>
<?php
$user_id = $_SESSION['user_id'] ?? 0;

$announcements = $conn->query("SELECT * FROM admin_announcements ORDER BY created_at DESC");
?>

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

<script>

// Sidebar section switching
function showSection(id){
    document.querySelectorAll('.main-content > div').forEach(d=>d.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

// Live clock
function updateClock(){
    const now = new Date();
    const t = now.toLocaleTimeString();
    const d = now.toLocaleDateString();
    document.getElementById('currentTime').innerText = t + ' | ' + d;
}
setInterval(updateClock,1000);
updateClock();

// Mark announcement as noted
function markNoted(id){
    $.post('mark_as_noted.php',{id:id}, function(){
        location.reload();
    });
}
</script>

</body>
</html>
