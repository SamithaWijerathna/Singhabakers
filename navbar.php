<style>
/* === Elegant 3D Dark Navbar === */
.navbar {
  background: rgba(15, 15, 15, 0.9);
  backdrop-filter: blur(6px);
  box-shadow:
    inset 0 2px 3px rgba(255,255,255,0.05),
    0 5px 15px rgba(0,0,0,0.6),
    0 10px 30px rgba(0,0,0,0.4);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  transform-style: preserve-3d;
  perspective: 800px;
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: transform 0.3s ease;
}

/* Brand - subtle metallic depth */
.navbar-brand {
  font-weight: 700;
  color: #e6e6e6 !important;
  text-shadow: 0 1px 1px rgba(255,255,255,0.1),
               0 2px 4px rgba(0,0,0,0.6);
  letter-spacing: 0.5px;
  transform: translateZ(15px);
  transition: all 0.3s ease;
}
.navbar-brand:hover {
  color: #fff !important;
  text-shadow: 0 2px 5px rgba(255,255,255,0.15);
  transform: translateZ(25px);
}

/* Nav links */
.navbar-nav .nav-link {
  color: #ccc !important;
  margin: 0 6px;
  font-weight: 500;
  position: relative;
  transition: all 0.3s ease;
}
.navbar-nav .nav-link::before {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0%;
  height: 2px;
  background: rgba(3, 177, 3, 1);
  transition: width 0.3s ease;
}
.navbar-nav .nav-link:hover {
  color: #fff !important;
  transform: translateY(-2px);
}
.navbar-nav .nav-link:hover::before {
  width: 100%;
}

/* Active link */
.navbar-nav .nav-link.active {
  color: #fff !important;
  font-weight: 600;
}
.navbar-nav .nav-link.active::before {
  width: 100%;
  background: rgba(32, 196, 3, 1);
}

/* Dropdown menu */
.dropdown-menu {
  background: rgba(20, 20, 20, 0.95);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 8px 25px rgba(0,0,0,0.5);
  border-radius: 10px;
  transform: translateZ(10px);
}
.dropdown-item {
  color: #ccc;
  transition: 0.3s;
}
.dropdown-item:hover {
  background-color: rgba(255,255,255,0.08);
  color: #fff;
}

/* Cart badge (simple depth) */
#cartBadge {
  background-color: rgba(0,0,0,0.8);
  box-shadow: 0 0 10px rgba(255,255,255,0.1);
  transform: translateZ(15px);
}

/* Soft reflection line */
.navbar::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 1px;
  background: linear-gradient(90deg, rgba(255,255,255,0.05), rgba(255,255,255,0.15), rgba(255,255,255,0.05));
}

/* 3D hover effect on movement */
</style>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">Singha Bakers</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link <?= $current_page=='index.php'?'active':'' ?>" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= $current_page=='foods.php'?'active':'' ?>" href="foods.php">Menu</a></li>
         <li class="nav-item"><a class="nav-link <?= $current_page=='contact.php'?'active':'' ?>" href="contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link <?= $current_page=='staff_login.php'?'active':'' ?>" href="staff_login.php">Staff</a></li>

        <li class="nav-item ms-3">
          <a class="nav-link position-relative" href="cart.php">
            🛒 Cart
            <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
              <?= $cartCount ?>
            </span>
          </a>
        </li>

        <?php if($userName): ?>
          <li class="nav-item ms-3 dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userMenu" data-bs-toggle="dropdown">
              <i class="fa fa-user"></i> <?= htmlspecialchars($userName) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="user_dashboard.php">Dashboard</a></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item ms-3"><a class="nav-link" href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<script>
const navbar = document.querySelector('.navbar');
navbar.addEventListener('mousemove', e => {
  const x = (window.innerWidth / 2 - e.pageX) / 60;
  const y = (window.innerHeight / 2 - e.pageY) / 60;
  navbar.style.transform = `rotateY(${x}deg) rotateX(${y}deg)`;
});
navbar.addEventListener('mouseleave', () => {
  navbar.style.transform = 'rotateY(0deg) rotateX(0deg)';
});
</script>
