<?php
session_start();

$host = "localhost";
$db = "singhabakers";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8");

try {
    // --- Validate cart ---
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        throw new Exception("Cart is empty!");
    }

    $userId = $_SESSION['user_id'] ?? 0;
    $orderType = $_POST['order_type'] ?? 'Takeaway';
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';
    $status = 'pending';
    $tableId = null;
    $tableInfo = null;

    // --- Handle Dine-In ---
    if ($orderType === 'Dine-In') {
        // Get one available table from table_status table
        $res = $conn->query("SELECT * FROM tables_status WHERE is_available = 1 LIMIT 1");
        if ($t = $res->fetch_assoc()) {
            $tableId = $t['id'];
            $tableInfo = "Table " . $t['table_number'];

            // Mark that table as unavailable (booked)
            $conn->query("UPDATE tables_status SET is_available = 0 WHERE id = $tableId");
        } else {
            // No available tables
            echo "<script>alert('All the tables are booked! Please choose Takeaway.'); window.history.back();</script>";
            exit;
        }
    } else {
        // Takeaway order
        $tableInfo = "Takeaway";
    }

    // --- Calculate total amount ---
    $totalAmount = 0;
    foreach ($cart as $item) {
        $foodId = intval($item['id']);
        $qty = intval($item['qty']);
        $food = $conn->query("SELECT price FROM foods WHERE id=$foodId")->fetch_assoc();
        if (!$food) {
            throw new Exception("Food ID $foodId not found.");
        }
        $totalAmount += $food['price'] * $qty;
    }

    // --- Apply available discount ---
$discountRow = $conn->query("SELECT id, discount_amount FROM user_discounts WHERE user_id=$userId ORDER BY created_at ASC LIMIT 1")->fetch_assoc();
$discountToUse = 0;
if($discountRow && $discountRow['discount_amount'] > 0){
    $discountToUse = min($discountRow['discount_amount'], $totalAmount); // cannot exceed order total
    $totalAmount -= $discountToUse;

    // Reduce discount in DB
    $newAmount = $discountRow['discount_amount'] - $discountToUse;
    if($newAmount > 0){
        $conn->query("UPDATE user_discounts SET discount_amount=$newAmount WHERE id=".$discountRow['id']);
    } else {
        $conn->query("DELETE FROM user_discounts WHERE id=".$discountRow['id']);
    }
}


    // --- Insert into orders table ---
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, order_type, payment_method, status, table_id, created_at, table_info)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->bind_param("idsssis", $userId, $totalAmount, $orderType, $paymentMethod, $status, $tableId, $tableInfo);
    $stmt->execute();
    $orderId = $stmt->insert_id;

    // --- Insert order items ---
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $foodId = intval($item['id']);
        $qty = intval($item['qty']);
        $price = $conn->query("SELECT price FROM foods WHERE id=$foodId")->fetch_assoc()['price'];

        $itemStmt->bind_param("iiid", $orderId, $foodId, $qty, $price);
        $itemStmt->execute();
    }

    // --- Clear cart ---
    unset($_SESSION['cart']);

    echo "<script>alert('Order placed successfully!'); window.location='foods.php';</script>";
} catch (Exception $e) {
    echo "<h3>Error placing order:</h3><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
