<?php
session_start();
$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB Error: ".$conn->connect_error);

$cart = $_SESSION['cart'] ?? [];
$total = 0;

// Handle quantity changes
if(isset($_POST['action'])){
    $id = $_POST['id'];
    if($_POST['action']=='inc') $_SESSION['cart'][$id]['qty']++;
    if($_POST['action']=='dec' && $_SESSION['cart'][$id]['qty']>1) $_SESSION['cart'][$id]['qty']--;
    if($_POST['action']=='remove') unset($_SESSION['cart'][$id]);
    header("Location: cart.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
.food-img { width:80px; height:80px; object-fit:cover; border-radius:10px; }
.bottom-menu { position:fixed; bottom:0; left:0; right:0; background:#fff; border-top:1px solid #ccc; padding:10px 0; }
.bottom-menu .item { text-align:center; flex:1; }
.strikethrough { text-decoration: line-through; color: #888; }

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

/* === Navbar === */
.navbar {
    background: rgba(10, 10, 10, 0.7);
    backdrop-filter: blur(10px);
    box-shadow: 0 5px 25px rgba(0, 230, 118, 0.2);
    padding: 12px 25px;
}
.navbar a.nav-link {
    color: #ddd;
    transition: 0.3s;
}
.navbar a.nav-link:hover {
    color: #048d6fff;

}

/* === Cards === */
.card {
    background: rgba(3, 165, 46, 0.31);
    border-radius: 15px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transition: all 0.3s;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(1, 1, 1, 0.25);
}
.card img {
    border-radius: 12px 12px 0 0;
    height: 220px;
    object-fit: cover;
}
.card-title {
    color: #ffffffff;
}
.card-text {
    color: #ccc;
}

.h5 {
    color: #ffffffff;
}

.order-modal {
  background: rgba(10, 10, 10, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 15px;
  box-shadow: 0 0 25px rgba(0, 255, 200, 0.1);
  backdrop-filter: blur(10px);
  color: #fff;
  transition: all 0.3s ease;
}
.order-modal .modal-header {
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.order-modal .modal-footer {
  border-top: 1px solid rgba(255,255,255,0.08);
}
.text-glow {
  color: #04d4a7;
  text-shadow: 0 0 10px rgba(4,212,167,0.4);
}
.form-dark {
  background: rgba(0,0,0,0.6);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
}
.form-dark:focus {
  border-color: #04d4a7;
  box-shadow: 0 0 8px rgba(4,212,167,0.4);
}
.custom-select {
  background: rgba(0,0,0,0.6);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.1);
}
.custom-select:focus {
  border-color: #04d4a7;
  box-shadow: 0 0 8px rgba(4,212,167,0.4);
}
.btn-success {
  background: linear-gradient(90deg, #00ffc8, #04d4a7);
  border: none;
  transition: 0.3s;
}
.btn-success:hover {
  background: linear-gradient(90deg, #04d4a7, #00ffc8);
  box-shadow: 0 0 15px rgba(4,212,167,0.5);
  color: #fff;
}
</style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-4 mb-5">
  <h3 class="mb-4">Your Cart</h3>

  <?php if(empty($cart)): ?>
    <div class="alert alert-warning">Your cart is empty!</div>
  <?php else: ?>
  <form method="post">
  <table class="table align-middle table-bordered">
    <thead class="table-secondary"><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
    <tbody>
    <?php foreach($cart as $item): 
        $res=$conn->query("SELECT * FROM foods WHERE id=".$item['id']);
        if($f=$res->fetch_assoc()):
            // Check discount
            $discount_res = $conn->query("SELECT discount_percent FROM discounts WHERE food_id=".$f['id']);
            $discount_percent = ($discount_res && $discount_res->num_rows>0) ? $discount_res->fetch_assoc()['discount_percent'] : 0;
            $price = $f['price'];
            $discounted_price = $price;
            if($discount_percent>0) $discounted_price = $price * (1 - $discount_percent/100);
            $sub = $discounted_price * $item['qty'];
            $total += $sub;
    ?>
    <tr>
      <td>
        <img src="<?= $f['image'] ?>" class="food-img me-2">
        <?= htmlspecialchars($f['name']) ?>
        <?php if($discount_percent>0): ?>
            <span class="text-success">(<?= $discount_percent ?>% off)</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if($discount_percent>0): ?>
            <span class="strikethrough">$<?= number_format($price,2) ?></span> $<?= number_format($discounted_price,2) ?>
        <?php else: ?>
            $<?= number_format($price,2) ?>
        <?php endif; ?>
      </td>
      <td>
        <div class="btn-group">
          <button name="action" value="dec" class="btn btn-sm btn-outline-secondary">-</button>
          <input type="hidden" name="id" value="<?= $f['id'] ?>">
          <span class="btn btn-sm btn-light"><?= $item['qty'] ?></span>
          <button name="action" value="inc" class="btn btn-sm btn-outline-secondary">+</button>
        </div>
      </td>
      <td>$<?= number_format($sub,2) ?></td>
      <td><button name="action" value="remove" class="btn btn-sm btn-danger">✕</button></td>
    </tr>
    <?php endif; endforeach; ?>
    </tbody>
  </table>
  </form>

  <div class="card shadow-sm">
    <div class="card-body text-end">
      <h5 class="h5">Total: $<?= number_format($total,2) ?></h5>
      <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#orderModal">Place Your Order</button>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- === Order Modal === -->
<div class="modal fade" id="orderModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content order-modal">
      <form method="post" action="place_order.php">
        <div class="modal-header border-0">
          <h5 class="modal-title text-glow">🍰 Place Your Order</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <label class="form-label text-light">Select Option:</label>
          <select class="form-select mb-4 custom-select" name="order_type" required onchange="toggleTable(this.value)">
            <option value="">-- Choose --</option>
            <option value="Takeaway">Takeaway</option>
            <option value="Dine-In">Dine-In</option>
          </select>

          <div id="card-section" class="mt-3">
            <h6 class="text-glow mb-3">💳 Payment Details</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Name on Card</label>
                <input type="text" name="card_name" class="form-control form-dark" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Card Number</label>
                <input type="text" name="card_number" class="form-control form-dark" maxlength="16" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Expiry</label>
                <input type="text" name="expiry" class="form-control form-dark" placeholder="MM/YY" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">CVV</label>
                <input type="password" name="cvv" class="form-control form-dark" maxlength="3" required>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0">
          <button type="submit" class="btn btn-success w-100 fw-bold py-2">
            <i class="fa fa-lock me-2"></i>Pay & Confirm
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
function addToCart(id){
  $.post('update_cart.php',{action:'add',food_id:id},()=>location.reload());
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>
