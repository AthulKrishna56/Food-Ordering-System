<?php
session_start();
$conn = mysqli_connect("localhost","root","","food_ordering");
if (!$conn) die("DB Error: ".mysqli_connect_error());
mysqli_set_charset($conn,"utf8");

$action  = $_POST['action']  ?? $_GET['action']  ?? '';
$item_id = (int)($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
$qty     = max(1,(int)($_POST['quantity'] ?? 1));

if ($action==='add' && $item_id) {
    $_SESSION['cart'][$item_id] = ($_SESSION['cart'][$item_id] ?? 0) + $qty;
    header("Location: cart.php"); exit;
}
if ($action==='remove' && $item_id) {
    unset($_SESSION['cart'][$item_id]);
    header("Location: cart.php"); exit;
}
if ($action==='update' && $item_id) {
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty>0) $_SESSION['cart'][$item_id]=$qty;
    else        unset($_SESSION['cart'][$item_id]);
    header("Location: cart.php"); exit;
}
if ($action==='clear') { unset($_SESSION['cart']); header("Location: cart.php"); exit; }

$cart    = $_SESSION['cart'] ?? [];
$details = [];
$total   = 0;
if (!empty($cart)) {
    $ids = implode(',',array_keys($cart));
    $res = mysqli_query($conn,"SELECT * FROM food_items WHERE id IN ($ids)");
    while ($row=mysqli_fetch_assoc($res)) {
        $row['qty']      = $cart[$row['id']];
        $row['subtotal'] = $row['price'] * $row['qty'];
        $total          += $row['subtotal'];
        $details[]       = $row;
    }
}
$cart_count = array_sum($cart);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cart – Savor</title>
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

.container{max-width:860px;margin:40px auto;padding:0 24px 80px;}
.page-title{font-size:1.7rem;font-weight:800;margin-bottom:28px;color:#1a1a1a;}

/* Cart table */
.cart-box{background:#fff;border-radius:14px;border:1px solid #ebebeb;
          overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:24px;}
table{width:100%;border-collapse:collapse;}
thead th{padding:13px 16px;text-align:left;font-size:.8rem;text-transform:uppercase;
         letter-spacing:.7px;color:#999;border-bottom:1px solid #f0f0f0;font-weight:600;}
td{padding:16px;border-bottom:1px solid #f8f8f8;vertical-align:middle;font-size:.92rem;}
tr:last-child td{border:none;}
.item-img{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #eee;}
.item-name{font-weight:700;color:#1a1a1a;}
.item-sub{font-size:.78rem;color:#aaa;margin-top:2px;}
.price-col{font-weight:700;color:#555;}
.sub-col{font-weight:800;color:#c0392b;}

.qty-form{display:flex;align-items:center;gap:6px;}
.qty-form input{width:60px;padding:7px;border:1px solid #ddd;border-radius:6px;
               text-align:center;font-size:.9rem;color:#2d2d2d;}
.qty-form input:focus{outline:none;border-color:#c0392b;}
.btn-upd{padding:7px 11px;background:#f0f0f0;border:1px solid #ddd;border-radius:6px;
         cursor:pointer;font-size:.8rem;font-weight:600;color:#555;}
.btn-upd:hover{background:#e8e8e8;}
.btn-del{padding:7px 11px;background:#fdedec;border:1px solid #f5b7b1;border-radius:6px;
         cursor:pointer;font-size:.82rem;font-weight:600;color:#e74c3c;}
.btn-del:hover{background:#fad7d4;}

/* Summary */
.summary-box{background:#fff;border-radius:14px;border:1px solid #ebebeb;
             padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.sum-row{display:flex;justify-content:space-between;padding:9px 0;
         border-bottom:1px solid #f4f4f4;font-size:.95rem;color:#555;}
.sum-row.total{border:none;font-size:1.25rem;font-weight:800;color:#1a1a1a;padding-top:14px;}
.sum-row span:last-child{font-weight:700;}
.sum-row.total span:last-child{color:#c0392b;}
.btn-checkout{display:block;width:100%;margin-top:18px;padding:15px;
              background:#c0392b;color:#fff;border:none;border-radius:10px;
              font-size:1rem;font-weight:800;cursor:pointer;text-align:center;
              text-decoration:none;transition:.15s;}
.btn-checkout:hover{background:#a93226;}
.clear-link{display:block;text-align:center;margin-top:11px;
            font-size:.84rem;color:#bbb;text-decoration:none;}
.clear-link:hover{color:#e74c3c;}

/* Empty */
.empty-state{background:#fff;border-radius:14px;border:1px solid #ebebeb;
             text-align:center;padding:80px 24px;}
.empty-state .icon{font-size:4rem;margin-bottom:16px;}
.empty-state h3{font-size:1.3rem;font-weight:800;margin-bottom:8px;color:#1a1a1a;}
.empty-state p{color:#aaa;margin-bottom:24px;font-size:.95rem;}
.btn-menu{display:inline-block;padding:12px 28px;background:#c0392b;color:#fff;
          border-radius:8px;text-decoration:none;font-weight:700;}
</style>
</head>
<body>
<div class="topbar">
  <div class="logo">🍽 Sa<span>vor</span></div>
  <a href="index.php">← Back to Menu</a>
</div>

<div class="container">
  <div class="page-title">🛒 Your Cart
    <?php if($cart_count>0):?>
    <span style="font-size:1rem;color:#aaa;font-weight:400;margin-left:8px;">(<?=$cart_count?> item<?=$cart_count>1?'s':''?>)</span>
    <?php endif;?>
  </div>

  <?php if(empty($details)):?>
  <div class="empty-state">
    <div class="icon">🛒</div>
    <h3>Your cart is empty</h3>
    <p>Browse our menu and add some delicious dishes.</p>
    <a href="index.php" class="btn-menu">Browse Menu</a>
  </div>

  <?php else:?>
  <div class="cart-box">
    <table>
      <thead><tr>
        <th></th><th>Dish</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach($details as $d):?>
      <tr>
        <td><img class="item-img"
                 src="<?=htmlspecialchars($d['image_url'])?>"
                 onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=120'"></td>
        <td>
          <div class="item-name"><?=htmlspecialchars($d['name'])?></div>
          <div class="item-sub"><?=$d['is_veg']?'● Veg':'● Non-Veg'?></div>
        </td>
        <td class="price-col">₹<?=number_format($d['price'],2)?></td>
        <td>
          <form class="qty-form" method="POST">
            <input type="hidden" name="action"  value="update">
            <input type="hidden" name="item_id" value="<?=$d['id']?>">
            <input type="number" name="quantity" value="<?=$d['qty']?>" min="0" max="20">
            <button class="btn-upd" type="submit">✔</button>
          </form>
        </td>
        <td class="sub-col">₹<?=number_format($d['subtotal'],2)?></td>
        <td>
          <form method="POST">
            <input type="hidden" name="action"  value="remove">
            <input type="hidden" name="item_id" value="<?=$d['id']?>">
            <button class="btn-del" type="submit">🗑 Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="summary-box">
    <div class="sum-row"><span>Subtotal</span><span>₹<?=number_format($total,2)?></span></div>
    <div class="sum-row"><span>Service Charge</span><span style="color:#27ae60;">Included</span></div>
    <div class="sum-row total"><span>Total Amount</span><span>₹<?=number_format($total,2)?></span></div>
    <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
    <a href="cart.php?action=clear" class="clear-link">Clear entire cart</a>
  </div>
  <?php endif;?>
</div>
</body>
</html>
<?php mysqli_close($conn);?>
