<?php
session_start();
$conn = mysqli_connect("localhost","root","","food_ordering");
if (!$conn) die("DB Error: ".mysqli_connect_error());
mysqli_set_charset($conn,"utf8");

if (empty($_SESSION['cart'])) { header("Location: index.php"); exit; }

$success  = false;
$errors   = [];
$order_id = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name       = trim($_POST['name']        ?? '');
    $phone      = trim($_POST['phone']       ?? '');
    $order_type = $_POST['order_type'] === 'takeout' ? 'takeout' : 'dine-in';
    $table_no   = trim($_POST['table_number'] ?? '');
    $address    = trim($_POST['address']      ?? '');

    if (!$name)  $errors[] = "Customer name is required.";
    if (!preg_match('/^[0-9]{10}$/',$phone)) $errors[] = "Enter a valid 10-digit phone number.";
    if ($order_type==='dine-in'  && !$table_no) $errors[] = "Table number is required for dine-in.";
    if ($order_type==='takeout'  && !$address)  $errors[] = "Pickup/delivery address is required for takeout.";

    if (!$errors) {
        $cart = $_SESSION['cart'];
        $ids  = implode(',',array_keys($cart));
        $res  = mysqli_query($conn,"SELECT id,price FROM food_items WHERE id IN ($ids)");
        $prices = [];
        while ($r=mysqli_fetch_assoc($res)) $prices[$r['id']]=$r['price'];
        $total = 0;
        foreach ($cart as $fid=>$q) $total += ($prices[$fid]??0)*$q;

        $n  = mysqli_real_escape_string($conn,$name);
        $ph = mysqli_real_escape_string($conn,$phone);
        $ot = mysqli_real_escape_string($conn,$order_type);
        $tn = mysqli_real_escape_string($conn,$table_no);
        $ad = mysqli_real_escape_string($conn,$address);

        mysqli_query($conn,
            "INSERT INTO orders (customer_name,phone,order_type,table_number,address,total_amount)
             VALUES ('$n','$ph','$ot','$tn','$ad',$total)");
        $order_id = mysqli_insert_id($conn);

        foreach ($cart as $fid=>$q) {
            if (!isset($prices[$fid])) continue;
            $p = $prices[$fid];
            mysqli_query($conn,"INSERT INTO order_items (order_id,food_item_id,quantity,unit_price) VALUES ($order_id,$fid,$q,$p)");
        }
        unset($_SESSION['cart']);
        $success = true;
    }
}

// Cart summary
$cart    = $_SESSION['cart'] ?? [];
$details = [];
$total   = 0;
if (!empty($cart)) {
    $ids = implode(',',array_keys($cart));
    $res = mysqli_query($conn,"SELECT * FROM food_items WHERE id IN ($ids)");
    while ($row=mysqli_fetch_assoc($res)) {
        $row['qty']=$cart[$row['id']];
        $row['subtotal']=$row['price']*$row['qty'];
        $total+=$row['subtotal'];
        $details[]=$row;
    }
}
$order_type_sel = $_POST['order_type'] ?? 'dine-in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout – Savor</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f7f4;color:#2d2d2d;}
.topbar{background:#fff;border-bottom:1px solid #e8e8e8;padding:0 40px;
        display:flex;align-items:center;justify-content:space-between;height:64px;
        position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,.06);}
.logo{font-size:1.45rem;font-weight:800;color:#c0392b;}
.logo span{color:#e67e22;}
.topbar a{text-decoration:none;color:#555;padding:8px 16px;border-radius:8px;font-size:.9rem;font-weight:500;}
.topbar a:hover{background:#fef3f0;color:#c0392b;}

.container{max-width:900px;margin:40px auto;padding:0 24px 80px;
           display:grid;grid-template-columns:1.15fr 1fr;gap:28px;align-items:start;}
@media(max-width:640px){.container{grid-template-columns:1fr;}}

.card{background:#fff;border-radius:14px;border:1px solid #ebebeb;
      padding:28px;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.card h2{font-size:1.25rem;font-weight:800;color:#1a1a1a;margin-bottom:22px;}

/* Type toggle */
.type-toggle{display:flex;gap:0;border:1px solid #ddd;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.type-toggle label{flex:1;text-align:center;padding:11px;font-size:.9rem;font-weight:600;
                   cursor:pointer;color:#666;transition:.15s;}
.type-toggle input{display:none;}
.type-toggle input:checked + label{background:#c0392b;color:#fff;}
.type-toggle label:first-of-type{border-right:1px solid #ddd;}

.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:#555;margin-bottom:5px;}
.form-group input,.form-group textarea{width:100%;padding:11px 14px;border:1px solid #ddd;
  border-radius:8px;font-size:.92rem;font-family:inherit;color:#2d2d2d;transition:.15s;}
.form-group input:focus,.form-group textarea:focus{outline:none;border-color:#c0392b;
  box-shadow:0 0 0 3px rgba(192,57,43,.08);}
textarea{resize:vertical;min-height:80px;}
.hint{font-size:.78rem;color:#bbb;margin-top:4px;}

.error-box{background:#fdedec;border:1px solid #f5b7b1;border-radius:8px;
           padding:12px 16px;margin-bottom:18px;}
.error-box p{color:#c0392b;font-size:.88rem;margin-bottom:3px;}

.btn-place{width:100%;padding:14px;background:#c0392b;color:#fff;border:none;
           border-radius:10px;font-size:1rem;font-weight:800;cursor:pointer;transition:.15s;margin-top:6px;}
.btn-place:hover{background:#a93226;}

/* Summary */
.sum-row{display:flex;justify-content:space-between;padding:9px 0;
         border-bottom:1px solid #f4f4f4;font-size:.9rem;color:#555;}
.sum-row:last-of-type{border:none;}
.sum-name{flex:1;font-weight:600;color:#333;}
.sum-qty{color:#aaa;font-size:.82rem;margin:0 12px;}
.sum-price{font-weight:700;color:#555;}
.sum-total{display:flex;justify-content:space-between;font-size:1.15rem;font-weight:900;
           color:#1a1a1a;margin-top:14px;padding-top:14px;border-top:2px solid #f0f0f0;}
.sum-total span:last-child{color:#c0392b;}

/* Success */
.success-wrap{grid-column:1/-1;background:#fff;border-radius:14px;border:1px solid #ebebeb;
              padding:60px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.success-wrap .icon{font-size:4.5rem;margin-bottom:20px;}
.success-wrap h2{font-size:1.8rem;font-weight:900;color:#27ae60;margin-bottom:10px;}
.success-wrap p{color:#666;margin-bottom:6px;font-size:.97rem;}
.success-wrap .order-id{font-size:1.1rem;font-weight:800;color:#c0392b;margin:12px 0;}
.btn-more{display:inline-block;margin-top:24px;padding:13px 30px;
          background:#c0392b;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;}
</style>
<script>
function toggleFields() {
    var type = document.querySelector('input[name="order_type"]:checked').value;
    document.getElementById('table-field').style.display   = type==='dine-in'  ? '' : 'none';
    document.getElementById('address-field').style.display = type==='takeout'  ? '' : 'none';
}
window.addEventListener('DOMContentLoaded', toggleFields);
</script>
</head>
<body>
<div class="topbar">
  <div class="logo">🍽 Sa<span>vor</span></div>
  <a href="cart.php">← Back to Cart</a>
</div>

<div class="container">
<?php if($success):?>
<div class="success-wrap">
  <div class="icon">🎉</div>
  <h2>Order Confirmed!</h2>
  <p>Thank you! Your order has been received by the kitchen.</p>
  <div class="order-id">Order ID: #<?=$order_id?></div>
  <p style="color:#aaa;font-size:.88rem;">Our team will have it ready for you shortly.</p>
  <a href="index.php" class="btn-more">Order More</a>
</div>

<?php else:?>
<!-- FORM -->
<div class="card">
  <h2>📋 Order Details</h2>

  <?php if($errors):?>
  <div class="error-box">
    <?php foreach($errors as $e):?><p>⚠ <?=$e?></p><?php endforeach;?>
  </div>
  <?php endif;?>

  <form method="POST">
    <!-- Order type toggle -->
    <div class="type-toggle">
      <input type="radio" name="order_type" id="dine" value="dine-in"
             <?=$order_type_sel==='dine-in'?'checked':''?> onchange="toggleFields()">
      <label for="dine">🍽 Dine-In</label>
      <input type="radio" name="order_type" id="take" value="takeout"
             <?=$order_type_sel==='takeout'?'checked':''?> onchange="toggleFields()">
      <label for="take">🛍 Takeout</label>
    </div>

    <div class="form-group">
      <label>Customer Name *</label>
      <input type="text" name="name" value="<?=htmlspecialchars($_POST['name']??'')?>" placeholder="Your full name" required>
    </div>
    <div class="form-group">
      <label>Phone Number *</label>
      <input type="tel" name="phone" value="<?=htmlspecialchars($_POST['phone']??'')?>" placeholder="10-digit mobile number" maxlength="10" required>
    </div>

    <div class="form-group" id="table-field">
      <label>Table Number *</label>
      <input type="text" name="table_number" value="<?=htmlspecialchars($_POST['table_number']??'')?>" placeholder="e.g. T-05">
      <div class="hint">Ask staff for your table number.</div>
    </div>

    <div class="form-group" id="address-field" style="display:none;">
      <label>Pickup / Delivery Address *</label>
      <textarea name="address" placeholder="House no, Street, City, Pincode"><?=htmlspecialchars($_POST['address']??'')?></textarea>
    </div>

    <button class="btn-place" type="submit">
      ✅ Place Order — ₹<?=number_format($total,2)?>
    </button>
  </form>
</div>

<!-- SUMMARY -->
<div class="card">
  <h2>🧾 Order Summary</h2>
  <?php foreach($details as $d):?>
  <div class="sum-row">
    <span class="sum-name"><?=htmlspecialchars($d['name'])?></span>
    <span class="sum-qty">×<?=$d['qty']?></span>
    <span class="sum-price">₹<?=number_format($d['subtotal'],2)?></span>
  </div>
  <?php endforeach;?>
  <div class="sum-total">
    <span>Total</span><span>₹<?=number_format($total,2)?></span>
  </div>
</div>
<?php endif;?>
</div>
</body>
</html>
<?php mysqli_close($conn);?>
