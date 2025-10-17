<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />





<footer class="footer-3d text-light">
  <!-- Social Section -->
  <section class="d-flex justify-content-center justify-content-lg-between p-4 footer-top">
    <div class="me-5 d-none d-lg-block">
      <span>Get connected with us on social networks:</span>
    </div>

    <div>
      <a href="#" class="me-4 social-icon"><i class="fab fa-facebook-f"></i></a>
      <a href="#" class="me-4 social-icon"><i class="fab fa-twitter"></i></a>
      <a href="#" class="me-4 social-icon"><i class="fab fa-google"></i></a>
      <a href="#" class="me-4 social-icon"><i class="fab fa-instagram"></i></a>
    </div>
  </section>

  <!-- Links Section -->
  <section class="pt-5">
    <div class="container text-center text-md-start mt-5">
      <div class="row mt-3">

        <!-- Company Info -->
        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
          <h6 class="fw-bold mb-4">
            <i class="fas fa-gem me-3"></i>SinghaBakers
          </h6>
          <p>
            Freshly baked delights for every occasion. Earn rewards and enjoy exclusive discounts as a member!
          </p>
        </div>

        <!-- Products -->
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="fw-bold mb-4">Products</h6>
          <p><a href="#!" class="text-reset">Pizza</a></p>
          <p><a href="#!" class="text-reset">Burger</a></p>
          <p><a href="#!" class="text-reset">Pasta</a></p>
          <p><a href="#!" class="text-reset">Seafood</a></p>
        </div>

        <!-- Useful Links -->
        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="fw-bold mb-4">Useful Links</h6>
          <p><a href="#!" class="text-reset">Pricing</a></p>
          <p><a href="#!" class="text-reset">Settings</a></p>
          <p><a href="#!" class="text-reset">Orders</a></p>
          <p><a href="#!" class="text-reset">Help</a></p>
        </div>

        <!-- Contact -->
        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
          <h6 class="fw-bold mb-4">Contact</h6>
          <p><i class="fas fa-home me-3"></i> Kurunegala, NW 10000, LK</p>
          <p><i class="fas fa-envelope me-3"></i>singhabakers@gmail.com</p>
          <p><i class="fas fa-phone me-3"></i> +94 76 001 0001</p>
          <p><i class="fas fa-print me-3"></i> +94 76 001 0002</p>
        </div>

      </div>
    </div>
  </section>

  <!-- Copyright -->
  <div class="footer-bottom text-center p-4">
    &copy; <?= date('Y') ?> SinghaBakers. All rights reserved.
  </div>
</footer>

<style>
/* === 3D Dark Footer Styling === */
.footer-3d {
  background: rgba(15,15,15,0.9);
  backdrop-filter: blur(8px);
  box-shadow:
    inset 0 2px 3px rgba(255,255,255,0.05),
    0 -5px 15px rgba(0,0,0,0.6),
    0 -10px 30px rgba(0,0,0,0.4);
  border-top: 1px solid rgba(255,255,255,0.05);
  transform-style: preserve-3d;
  perspective: 1000px;
  position: relative;
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Social section (top) */
.footer-top {
  border-bottom: 1px solid rgba(255,255,255,0.05);
  text-shadow: 0 1px 1px rgba(0,0,0,0.6);
}
.footer-top .social-icon {
  color: #ccc;
  transition: all 0.3s ease;
  font-size: 1.2rem;
}
.footer-top .social-icon:hover {
  color: #fff;
  transform: translateY(-3px) scale(1.1);
}

/* Section text */
.footer-3d h6 {
  color: #f0f0f0;
  letter-spacing: 1px;
  text-shadow: 0 1px 2px rgba(0,0,0,0.7);
}
.footer-3d p, .footer-3d a {
  color: #bbb;
  font-size: 0.9rem;
  transition: color 0.3s;
}
.footer-3d a.text-reset:hover {
  color: #fff !important;
  text-decoration: none;
}

/* Copyright strip */
.footer-bottom {
  background: rgba(255,255,255,0.05);
  font-size: 0.9rem;
  border-top: 1px solid rgba(255,255,255,0.05);
  box-shadow: inset 0 1px 2px rgba(255,255,255,0.05);
}

/* Subtle glossy reflection */
.footer-3d::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, rgba(255,255,255,0.06), rgba(255,255,255,0.15), rgba(255,255,255,0.06));
}

/* Responsive */
@media(max-width:768px){
  .d-none.d-lg-block { display:none !important; }
  .footer-3d { text-align: center; }
}
</style>


<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
