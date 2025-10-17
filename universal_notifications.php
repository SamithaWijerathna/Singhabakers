<?php
// universal_notifications.php
if(session_status() == PHP_SESSION_NONE) session_start();

$host="localhost"; $db="singhabakers"; $user="root"; $pass="";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// --- AJAX fetch ---
if(isset($_GET['fetch']) && $_GET['fetch']==1){
    $notifications = [];
    $last_check = $_SESSION['last_check'] ?? '1970-01-01 00:00:00';

    // New pending orders
    $sql_orders = "SELECT id FROM orders WHERE status='pending' AND created_at > '$last_check' ORDER BY created_at DESC";
    $res_orders = $conn->query($sql_orders);
    $count_orders = $res_orders->num_rows;
    if($count_orders > 0){
        $notifications[] = [
            'icon'=>'🛎️',
            'message'=>"New pending orders: $count_orders",
            'color'=>'#ffc107',
            'sound'=>true,
            'target'=>'activeOrders'
        ];
    }

    // Low / Out stock inventory
    $sql_stock = "SELECT item_name, status FROM inventory WHERE status IN ('low','out')";
    $res_stock = $conn->query($sql_stock);
    while($i = $res_stock->fetch_assoc()){
        $icon = $i['status']=='low'?'⚠️':'❌';
        $color = $i['status']=='low'?'#ffcc00':'#ff4444';
        $notifications[] = [
            'icon'=>$icon,
            'message'=>"Inventory {$i['item_name']} is {$i['status']}",
            'color'=>$color,
            'sound'=>false,
            'target'=>'inventory'
        ];
    }

    $_SESSION['last_check'] = date('Y-m-d H:i:s');
    header('Content-Type: application/json');
    echo json_encode(['notifications'=>$notifications]);
    exit;
}
?>

<!-- Universal Notification Bar -->
<div id="universalNotification" style="
    position:fixed;
    top:0;
    left:0;
    width:100%;
    background:#000000b9;
    color:#fff;
    padding:10px 20px;
    font-weight:bold;
    z-index:9999;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.25);
    font-family: 'Segoe UI', sans-serif;
">
<div id="notificationList" style="display:flex; gap:15px; flex-wrap:wrap;">No notifications</div>
<div id="currentTime" style="font-size:0.9em; opacity:0.85;"></div>
</div>

<audio id="notificationSound" src="https://www.soundjay.com/buttons/beep-07.wav" preload="auto"></audio>

<script>
// --- Live clock ---
function updateClock(){
    const now = new Date();
    const options = {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit'};
    document.getElementById('currentTime').innerText = now.toLocaleString('en-US', options);
}
setInterval(updateClock, 1000);
updateClock();

// --- Update notifications ---
function updateNotifications(notifs){
    const list = document.getElementById('notificationList');
    if(notifs.length===0){
        list.innerHTML='No notifications';
        document.getElementById('universalNotification').style.background='#000000b9';
        return;
    }

    list.innerHTML='';
    notifs.forEach(n=>{
        const item = document.createElement('div');
        item.style.display='flex';
        item.style.alignItems='center';
        item.style.background=n.color+'33';
        item.style.padding='5px 12px';
        item.style.borderRadius='8px';
        item.style.minWidth='180px';
        item.style.fontSize='0.95em';
        item.style.cursor='pointer';
        item.style.boxShadow='0 2px 5px rgba(0,0,0,0.2)';
        item.style.transition='transform 0.2s, box-shadow 0.2s';

        item.innerHTML=`<span style="margin-right:6px;">${n.icon}</span>
                        <span style="flex:1;">${n.message}</span>`;

        // Hover effect
        item.onmouseover = ()=>{ item.style.transform='translateY(-2px)'; item.style.boxShadow='0 4px 10px rgba(0,0,0,0.3)'; }
        item.onmouseout = ()=>{ item.style.transform='translateY(0)'; item.style.boxShadow='0 2px 5px rgba(0,0,0,0.2)'; }

        // Navigate to corresponding section
        item.onclick = ()=> {
            if(n.target && document.getElementById(n.target)){
                document.querySelectorAll('.main-content > div').forEach(d=>d.classList.add('hidden'));
                document.getElementById(n.target).classList.remove('hidden');
                window.scrollTo({top:100, behavior:'smooth'});
            }
        };

        list.appendChild(item);

        if(n.sound) document.getElementById('notificationSound').play();
    });
}

// --- Poll server every 5 seconds ---
setInterval(()=>{
    fetch('universal_notifications.php?fetch=1')
    .then(res=>res.json())
    .then(data=>updateNotifications(data.notifications));
},5000);

// --- Add top margin to dashboard sections dynamically ---
document.addEventListener('DOMContentLoaded', ()=>{
    const barHeight = document.getElementById('universalNotification').offsetHeight;
    document.querySelectorAll('.main-content > div').forEach(d=>{
        d.style.marginTop = (barHeight + 15) + 'px';
    });
});
</script>

<style>
/* Ensure content is visible below notification bar */
body { transition: padding-top 0.3s ease; }
</style>
