<?php
session_start();

// DB connection
$host = "localhost"; $db = "singhabakers"; $user = "root"; $pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Cart Count ---
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;

// --- Categories ---
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// --- Category Filter ---
$filter = isset($_GET['cat']) ? $_GET['cat'] : '';
$sql = "SELECT foods.*, categories.name as category_name 
        FROM foods 
        LEFT JOIN categories ON foods.category_id=categories.id";
if ($filter) $sql .= " WHERE categories.name='$filter'";
$foods = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Our Foods</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<style>
.card img { height: 200px; object-fit: cover; }
.modal-img { width:100%; height:300px; object-fit:cover; }
.category-btn.active { background-color: #0d6efd; color: white; }
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
    background: rgba(0, 0, 0, 0.77);
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
</style>
</head>
<body class="bg-light">

<!-- Alert container -->
<div id="cartAlert" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050; display: none;">
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    Item added to cart successfully!
    <button type="button" class="btn-close" onclick="hideAlert()"></button>
  </div>
</div>




<!-- Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-4">

  <!-- Category Filter -->
  <div class="d-flex flex-wrap mb-4 gap-2">
    <a href="foods.php" class="btn btn-outline-primary category-btn <?= $filter==''?'active':'' ?>">All</a>
    <?php while($cat=$categories->fetch_assoc()): ?>
      <a href="?cat=<?= urlencode($cat['name']) ?>" class="btn btn-outline-primary category-btn <?= $filter==$cat['name']?'active':'' ?>">
        <?= htmlspecialchars($cat['name']) ?>
      </a>
    <?php endwhile; ?>
  </div>

  <!-- Food Cards -->
  <div class="row">
    <?php while($f = $foods->fetch_assoc()): ?>
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm">
        <?php if($f['image']): ?>
        <img src="<?= htmlspecialchars($f['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($f['name']) ?>">
        <?php endif; ?>
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($f['name']) ?></h5>
          <p class="card-text text-white"><?= htmlspecialchars($f['category_name']) ?></p>
          <p class="card-text fw-bold">$<?= $f['price'] ?></p>
          <div class="d-flex justify-content-between">
     <button class="btn btn-sm btn-primary"
        onclick="viewFood(
          '<?= addslashes($f['name']) ?>',
          '<?= addslashes($f['description']) ?>',
          '<?= $f['price'] ?>',
          '<?= $f['image'] ?>',
          '<?= addslashes($f['category_name']) ?>',
          <?= $f['id'] ?>
        )">
  View More
</button>



            <button class="btn btn-sm btn-success" onclick="addToCart(<?= $f['id'] ?>)">Add to Cart</button>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Food Detail Modal -->
<div class="modal fade" id="foodModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="foodTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 text-center">
            <img id="foodImage" class="img-fluid mb-3" src="" alt="">
          </div>
          <div class="col-md-6">
            <p id="foodDesc"></p>
            <p class="fw-bold" id="foodPrice"></p>
            <p><strong>Category:</strong> <span id="foodCategory"></span></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" id="addCartBtn">Add to Cart</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>
let selectedFoodId = 0; // global

function viewFood(name, description, price, image, category, id) {
  selectedFoodId = id; // store globally

  document.getElementById('foodTitle').textContent = name;
  document.getElementById('foodImage').src = image;
  document.getElementById('foodImage').alt = name;
  document.getElementById('foodDesc').textContent = description;
  document.getElementById('foodPrice').textContent = "$" + price;
  document.getElementById('foodCategory').textContent = category;

  const addBtn = document.getElementById('addCartBtn');
  addBtn.onclick = function() {
    addToCart(selectedFoodId); // always uses correct id
    const modal = bootstrap.Modal.getInstance(document.getElementById('foodModal'));
    modal.hide(); // close modal after add
  };

  const modal = new bootstrap.Modal(document.getElementById('foodModal'));
  modal.show();
}



function addToCart(foodId) {
  const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

  if (!isLoggedIn) {
    localStorage.setItem('pending_add', foodId);
    alert('Please login to add items.');
    window.location = 'login.php';
    return;
  }

  $.post('update_cart.php', { action: 'add', food_id: foodId }, function(res) {
    if (res === 'success') {
      showAlert(); // Show success alert
    } else {
      alert(res); // fallback for errors
    }
  });
}

// Show success alert
function showAlert() {
  const alertBox = document.getElementById('cartAlert');
  alertBox.style.display = 'block';
  setTimeout(() => {
    hideAlert();
  }, 3000); // auto hide after 3s
}

// Hide alert
function hideAlert() {
  const alertBox = document.getElementById('cartAlert');
  alertBox.style.display = 'none';
}



</script>
<?php include 'footer.php'; ?>
</body>
</html>
