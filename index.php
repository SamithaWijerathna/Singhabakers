<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "singhabakers";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Database connection failed: " . $conn->connect_error);



// --- Handle Add to Cart ---
if (isset($_GET['add_to_cart'])) {
    $food_id = intval($_GET['add_to_cart']);
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = "index.php?add_to_cart=" . $food_id;
        header("Location: login.php");
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $check = $conn->query("SELECT * FROM cart WHERE user_id=$user_id AND food_id=$food_id");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id=$user_id AND food_id=$food_id");
    } else {
        $conn->query("INSERT INTO cart (user_id, food_id, quantity) VALUES ($user_id, $food_id, 1)");
    }
    header("Location: index.php?added=1");
    exit;
}

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
$userName = $_SESSION['user_name'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Singha Bakers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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

/* === Hero Section === */
#hero {
    height: 100vh;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}
#hero video {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    z-index: -2;
}
#hero .overlay {
    position: absolute;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.6);
    z-index:-1;
}
.hero-content h1 {
    font-size: 3rem;
    font-weight: 700;
    text-shadow: 0 0 15px rgba(0,230,118,0.4);
}
.hero-content p {
    font-size: 1.2rem;
    color: #ccc;
}
.hero-content .btn {
    background: linear-gradient(135deg, #00c0e6ff, #007fc8ff);
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 10px 25px;
    margin-top: 20px;
    transition: 0.3s;
}
.hero-content .btn:hover {
    background: linear-gradient(135deg, #00e676, #00c853);
    transform: scale(1.05);
}

/* === Section Titles === */
.section-title {
    font-size: 2.3rem;
    font-weight: 700;
    text-align: center;
    color: #ffffffff;
    margin-bottom: 2rem;
}

/* === Cards === */
.card {
    background: rgba(5, 5, 5, 0.8);
    border-radius: 15px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transition: all 0.3s;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,230,118,0.25);
}
.card img {
    border-radius: 12px 12px 0 0;
    height: 220px;
    object-fit: cover;
}
.card-title {
    color: #00ffc8;
}
.card-text {
    color: #ccc;
}

/* === Buttons === */
.btn-outline-success {
    border-color: #00ffc8;
    color: #00ffc8;
}
.btn-outline-success:hover {
    background: #00ffc8;
    color: #000;
}

/* === Footer === */
footer {
    background: linear-gradient(180deg, #0d0d0d, #111);
    text-align: center;
    padding: 25px;
    color: #aaa;
    border-top: 1px solid rgba(255,255,255,0.08);
}
footer a { color: #00ffc8; text-decoration: none; }
footer a:hover { text-shadow: 0 0 8px #00ffc8; }

/* === Animations === */
[data-aos] {
    transition: all 0.6s ease;
}
.description-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.read-more-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 0.9rem;
    color: #00c853; /* bright green */
    text-decoration: none;
    cursor: pointer;
    transition: color 0.3s, transform 0.2s;
}

.read-more-link:hover {
    color: #00e676; /* lighter green on hover */
    transform: translateY(-2px);
}

/* About Section */
#about {

    padding: 60px 0;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.43);
    color: #fff;
}

#about img {
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s, box-shadow 0.3s;
}

#about img:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

#about p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #ddd;
}

#about ul {
    list-style: none;
    padding: 0;
    margin-top: 15px;
}

#about ul li {
    margin-bottom: 8px;
    font-size: 1rem;
    color: #00ffc8;
    position: relative;
    padding-left: 25px;
}

#about ul li::before {
    content: "✔";
    position: absolute;
    left: 0;
    color: #00e676;
    font-weight: bold;
}

#about .btn-success {
    background: linear-gradient(135deg, #00c0e6ff, #007fc8ff);
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    transition: transform 0.3s, background 0.3s;
}

#about .btn-success:hover {
    background: linear-gradient(135deg, #00e676, #00c853);
    transform: scale(1.05);
}

 .map-responsive {
    position: relative;
    overflow: hidden;
  }
  .map-responsive iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
  #contact .section-title {
    color: #04d4a7ff;
  }
  #contact i {
    color: #04d4a7ff;
  }
  #contact .bg-secondary {
    background: rgba(0,0,0,0.6) !important;
  }

</style>

</head>
<body>



<?php include 'navbar.php'; ?>

<!-- Hero -->
<section id="hero">
  <video autoplay muted loop>
    <source src="assets/video/2.mp4" type="video/mp4">
  </video>
  <div class="overlay"></div>
  <div class="container hero-content" data-aos="fade-up">
    <h1>Welcome to Singha Bakers</h1>
    <p>Freshly baked goodness, straight from our oven to your heart.</p>
    <a href="#menu" class="btn btn-success btn-lg mt-3">View Our Menu</a>
  </div>
</section>

<!-- About -->
<section id="about" class="py-5" data-aos="fade-up">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6"><img src="assets/img/about.jpg" class="img-fluid shadow"></div>
      <div class="col-md-6 mt-4 mt-md-0">
        <h2 class="section-title">About Singha Bakers</h2>
        <p>Since 1998, <strong>Singha Bakers</strong> has been delighting customers with fresh, handmade bakery creations. Our secret lies in using locally sourced ingredients and time-tested recipes that make every bite unforgettable.</p>
        <ul>
          <li>✔ 100% fresh ingredients</li>
          <li>✔ Daily baked bread and cakes</li>
          <li>✔ Custom cake orders and catering available</li>
        </ul>
        <a href="#menu" class="btn btn-success mt-3">Explore Menu</a>
      </div>
    </div>
  </div>
</section>

<!-- Menu -->
<section id="menu" class="py-5" data-aos="fade-up">
  <div class="container">
    <h2 class="section-title text-center">Our Menu</h2>
    <div class="row">
      <?php
      $query = "SELECT * FROM foods ORDER BY created_at DESC LIMIT 9";
      $result = $conn->query($query);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()):
          $imagePath = htmlspecialchars($row['image']);
      ?>
      <div class="col-md-4 mb-4" data-aos="zoom-in">
        <div class="card shadow">
          <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= htmlspecialchars($row['name']) ?>">
          <div class="card-body text-center">
            <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
            <p class="card-text text-white fw-bold">LKR <?= number_format($row['price']) ?></p>
<p class="card-text text-white description-clamp" id="desc-<?= $row['id'] ?>">
    <?= htmlspecialchars($row['description']) ?>
</p>
<a href="javascript:void(0);" class="read-more-link" onclick="toggleDescription(<?= $row['id'] ?>)" id="toggle-<?= $row['id'] ?>">Read More</a>

            <button class="btn btn-outline-success btn-sm" onclick="addToCart(<?= $row['id'] ?>)">
              <i class="fa fa-cart-plus"></i> Add to Cart
            </button>
          </div>
        </div>
      </div>
      <?php endwhile; } ?>
    </div>
  </div>
</section>

<!-- Offers -->
<section id="offers" class="py-5" data-aos="fade-up">
  <div class="container">
    <h2 class="section-title text-center">Special Offers</h2>
    <div class="row">
      <div class="col-md-4 mb-4" data-aos="flip-left">
        <div class="card p-3 text-white text-center"><h5>Buy 1 Get 1 Free</h5><p>Pastries every Wednesday.</p></div>
      </div>
      <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="100">
        <div class="card p-3 text-white text-center"><h5>20% Off Cakes</h5><p>Orders above LKR 2000 this weekend.</p></div>
      </div>
      <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="200">
        <div class="card p-3 text-white text-center"><h5>Free Delivery</h5><p>Takeaway orders above LKR 1500.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-5" data-aos="fade-up">
  <div class="container">
    <h2 class="section-title text-center mb-4">Customer's Reviews</h2>
    <div class="row">
      <?php
      $reviews = $conn->query("
          SELECT r.*, u.name 
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          ORDER BY r.created_at DESC 
          LIMIT 6
      ");
      while($r = $reviews->fetch_assoc()):
      ?>
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card p-4 h-100 shadow-lg bg-dark text-white text-center rounded-4">
          <p class="mb-3 fst-italic">"<?= htmlspecialchars($r['comment']) ?>"</p>
          <p class="mb-2">
            <?php for($i=0; $i<$r['rating']; $i++) echo '⭐'; ?>
          </p>
          <b>- <?= htmlspecialchars($r['name']) ?></b>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>


<!-- Gallery -->
<section id="gallery" class="py-5" data-aos="fade-up">
  <div class="container">
    <h2 class="section-title text-center">Our Gallery</h2>
    <div class="row g-3">
      <?php $images = glob("assets/img/gallery/*.{jpg,png}", GLOB_BRACE); foreach($images as $i=>$img): ?>
      <div class="col-md-3" data-aos="zoom-in" data-aos-delay="<?= $i*100 ?>">
        <img src="<?= $img ?>" class="img-fluid">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Stats -->
<section id="stats" class="py-5 text-center">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4" data-aos="fade-up"><h3 class="display-4 counter">2500</h3><p>Orders Served</p></div>
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100"><h3 class="display-4 counter">150</h3><p>Cakes Baked</p></div>
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200"><h3 class="display-4 counter">1000</h3><p>Happy Customers</p></div>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="contact" class="py-5 bg-dark text-white" data-aos="fade-up">
  <div class="container-fluid px-5">
    <h2 class="section-title text-center mb-5">Contact Us</h2>
    <div class="row justify-content-center g-4">
      
      <!-- Contact Info -->
      <div class="col-md-4 text-center">
        <div class="p-4 bg-secondary rounded shadow-sm">
          <i class="fa fa-phone fa-2x mb-3"></i>
          <h5>Call Us</h5>
          <p class="mb-0">+94 70 001 0001</p>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <div class="p-4 bg-secondary rounded shadow-sm">
          <i class="fa fa-envelope fa-2x mb-3"></i>
          <h5>Email Us</h5>
          <p class="mb-0">singhabakers@gmail.com</p>
        </div>
      </div>
      
      <!-- Map -->
      <div class="col-12 mt-4">
        <div class="map-responsive rounded shadow-sm overflow-hidden" style="height:400px;">
          <iframe
            src="https://www.google.com/maps?q=kurunegala&output=embed"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</section>


<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init({duration:1000, once:true});</script>

<script>
// Add to cart
function addToCart(id){
  const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true':'false' ?>;
  if(!isLoggedIn){ localStorage.setItem('pending_add', id); alert('Please login to add items.'); window.location='login.php'; return; }
  $.post('update_cart.php',{action:'add',food_id:id},res=>{if(res==='success'){alert('Added!'); location.reload();} else alert(res);});
}

function toggleDescription(id) {
    const desc = document.getElementById(`desc-${id}`);
    const toggle = document.getElementById(`toggle-${id}`);
    
    if(desc.classList.contains('description-clamp')) {
        desc.classList.remove('description-clamp');
        toggle.textContent = 'Show Less';
    } else {
        desc.classList.add('description-clamp');
        toggle.textContent = 'Read More';
    }
}


// Counter Animation
document.addEventListener('DOMContentLoaded', ()=>{
  const counters = document.querySelectorAll('.counter');
  counters.forEach(counter=>{
    let target = +counter.textContent; counter.textContent=0;
    let step=()=>{let c=+counter.textContent+Math.ceil(target/100); if(c<target){counter.textContent=c; requestAnimationFrame(step);}else{counter.textContent=target;}}
    step();
  });

  const pending = localStorage.getItem('pending_add');
  if(pending){ localStorage.removeItem('pending_add'); $.post('update_cart.php',{action:'add',food_id:pending},()=>{window.location='cart.php';}); }
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
