<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$host = "localhost";
$db = "singhabakers";
$user = "root";
$pass = "";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB connection failed: ".$conn->connect_error);
$conn->set_charset("utf8");

// --- Handle AJAX Review Submission ---
if(isset($_POST['action']) && $_POST['action'] === 'submit_review'){
    $orderId = intval($_POST['order_id']);
    $rating = intval($_POST['rating']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $userId = $_SESSION['user_id'];

    // Check if order belongs to user and is completed
    $orderCheck = $conn->query("SELECT * FROM orders WHERE id=$orderId AND user_id=$userId AND status='completed'");
    if($orderCheck->num_rows === 0){
        echo "invalid";
        exit;
    }

    // Prevent duplicate review
    $checkReview = $conn->query("SELECT * FROM reviews WHERE order_id=$orderId AND user_id=$userId");
    if($checkReview->num_rows > 0){
        echo "exists";
        exit;
    }

    // Insert review
    $conn->query("INSERT INTO reviews (order_id, user_id, rating, comment, created_at) 
                  VALUES ($orderId, $userId, $rating, '$comment', NOW())");

    echo "success";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<style>
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
.progress { height: 25px; }
.card { transition: transform 0.3s; }
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,230,118,0.25);
}
.toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 9999; }
.star-rating .fa-star { cursor: pointer; color: #ddd; }
.star-rating .fa-star.checked { color: #049c9eff; }

/* === Navbar === */
.navbar {
    background: rgba(10, 10, 10, 0.7);
    backdrop-filter: blur(10px);
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    padding: 12px 25px;
}
.navbar a.nav-link {
    color: #ddd;
    transition: 0.3s;
}
.navbar a.nav-link:hover {
    color: #048d6fff;

}

</style>
</head>
<body class="bg-light">

<!-- Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-4">

  <!-- Top Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-center shadow p-3 bg-success text-white">
        <h5>Total Orders</h5>
        <h3 id="total_orders">0</h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center shadow p-3 bg-success text-dark">
        <h5>Active Orders</h5>
        <h3 id="active_orders">0</h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center shadow p-3 bg-success text-white">
        <h5>Total Spent (LKR)</h5>
        <h3 id="total_spent">0</h3>
      </div>
    </div>
  </div>

  <!-- Member Level & Progress -->
  <div class="card mb-4 shadow p-3">
    <h5>Member Level: <span id="member_level">Bronze 🟤</span></h5>
    <div class="progress mb-2">
      <div id="level_progress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width:0%">0%</div>
    </div>
    <p>Next Level Goal: <span id="next_goal">30,000</span> LKR</p>
    <p>Total Discount Earned: LKR <span id="total_discount">0</span></p>
  </div>

  <!-- Recent Orders -->
  <div class="card mb-4 shadow p-3">
    <h5>Recent Orders</h5>
    <table class="table table-striped" id="recent_orders">
      <thead>
        <tr><th>ID</th><th>Items</th><th>Total</th><th>Status</th><th>Dine-In Table</th><th>Action</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- User Profile -->
  <div class="card shadow p-3">
    <h5>Profile</h5>
    <p><b>Name:</b> <span id="profile_name"></span></p>
    <p><b>Email:</b> <span id="profile_email"></span></p>
    <p><b>Member Since:</b> <span id="profile_created"></span></p>
  </div>

</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Write a Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="review_order_id">
        <div class="mb-3">
          <label>Rating:</label>
          <div class="star-rating">
            <i class="fa fa-star" data-val="1"></i>
            <i class="fa fa-star" data-val="2"></i>
            <i class="fa fa-star" data-val="3"></i>
            <i class="fa fa-star" data-val="4"></i>
            <i class="fa fa-star" data-val="5"></i>
          </div>
        </div>
        <div class="mb-3">
          <label>Comment:</label>
          <textarea class="form-control" id="review_comment" rows="3"></textarea>
        </div>
        <button class="btn btn-success w-100" onclick="submitReview()">Submit Review</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container" id="toast_container"></div>

<script>
var selectedRating = 0;

// Star rating selection
$(document).on('mouseenter','.star-rating .fa-star', function(){
  var val = $(this).data('val');
  $('.star-rating .fa-star').each(function(){
    $(this).toggleClass('checked', $(this).data('val') <= val);
  });
});
$(document).on('click','.star-rating .fa-star', function(){
  selectedRating = $(this).data('val');
});
$(document).on('mouseleave','.star-rating', function(){
  $('.star-rating .fa-star').each(function(){
    $(this).toggleClass('checked', $(this).data('val') <= selectedRating);
  });
});

// Open Review Modal
function openReviewModal(orderId){
  $('#review_order_id').val(orderId);
  $('#review_comment').val('');
  selectedRating = 0;
  $('.star-rating .fa-star').removeClass('checked');
  $('#reviewModal').modal('show');
}

// Submit review via AJAX
function submitReview(){
  var orderId = $('#review_order_id').val();
  var comment = $('#review_comment').val();
  if(selectedRating === 0){ alert('Please select a rating'); return; }

  $.post('', {action:'submit_review', order_id:orderId, rating:selectedRating, comment:comment}, function(res){
    if(res === 'success'){
      alert('Review submitted!');
      $('#reviewModal').modal('hide');
      fetchDashboard();
    } else if(res === 'invalid'){
      alert('Invalid order.');
    } else if(res === 'exists'){
      alert('Review already exists.');
    } else {
      alert('Error: ' + res);
    }
  });
}

// Fetch dashboard data
function fetchDashboard(){
  $.getJSON('fetch_user_dashboard.php', function(res){
    // Top Summary
    $('#total_orders').text(res.summary.total_orders);
    $('#active_orders').text(res.summary.active_orders);
    $('#total_spent').text(res.summary.total_spent);

    // Profile
    $('#profile_name').text(res.profile.name);
    $('#profile_email').text(res.profile.email);
    $('#profile_created').text(res.profile.created_at);

    // Member Level
    $('#member_level').text(res.member_progress.level_name);
    $('#next_goal').text(res.member_progress.next_goal.toLocaleString());
    $('#total_discount').text(res.member_progress.total_discount.toLocaleString());
    $('#level_progress').css('width', res.member_progress.progress + '%').text(Math.floor(res.member_progress.progress)+'%');

    // Recent Orders
    var tbody = $('#recent_orders tbody'); tbody.empty();
    res.orders.forEach(function(o){
      var items = '';
      o.items.forEach(function(i){ items += i.name + ' x ' + i.quantity + '<br>'; });
      var dine = o.table_number ? o.table_number : '-';

      var actionBtn = '';
      if(o.status === 'completed' && !o.reviewed){
        actionBtn = '<button class="btn btn-sm btn-success" onclick="openReviewModal('+o.id+')">Write Review</button>';
      } else if(o.reviewed){
        actionBtn = '<span class="text-success">Reviewed</span>';
      }

      tbody.append('<tr><td>'+o.id+'</td><td>'+items+'</td><td>'+o.total_amount+'</td><td>'+o.status+'</td><td>'+dine+'</td><td>'+actionBtn+'</td></tr>');
    });

    // Toasts
    $('#toast_container').empty();
    res.notifications.forEach(function(n){
      var t = $('<div class="toast align-items-center text-white bg-primary border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">'+n.message+'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
      $('#toast_container').append(t); t.toast({delay:5000}); t.toast('show');
    });
  });
}

$(document).ready(function(){
  fetchDashboard();
  setInterval(fetchDashboard, 10000);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>
