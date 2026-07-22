<?php
session_start();
$conn = mysqli_connect("localhost","root","","food_ordering");
if (!$conn) die("DB Error: ".mysqli_connect_error());
mysqli_set_charset($conn,"utf8");

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT fi.*,c.name AS cat_name,c.icon AS cat_icon
     FROM food_items fi JOIN categories c ON fi.category_id=c.id
     WHERE fi.id=$id AND fi.is_available=1"));
if (!$item) { header("Location: index.php"); exit; }

$related = mysqli_query($conn,
    "SELECT * FROM food_items WHERE category_id={$item['category_id']} AND id!=$id AND is_available=1 LIMIT 4");

$cart_count = 0;
if (!empty($_SESSION['cart'])) foreach($_SESSION['cart'] as $q) $cart_count += $q;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($item['name'])?> – Savor</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f7f4;color:#2d2d2d;}
.topbar{background:#fff;border-bottom:1px solid #e8e8e8;padding:0 40px;
        display:flex;align-items:center;justify-content:space-between;height:64px;
        position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,.06);}
.logo{font-size:1.45rem;font-weight:800;color:#c0392b;}
.logo span{color:#e67e22;}
.nav-links{display:flex;align-items:center;gap:8px;}
.nav-links a{text-decoration:none;color:#555;padding:8px 16px;border-radius:8px;font-size:.9rem;font-weight:500;}
.nav-links a:hover{background:#fef3f0;color:#c0392b;}
.cart-btn{background:#c0392b!important;color:#fff!important;border-radius:8px!important;font-weight:700!important;display:flex;align-items:center;gap:6px;position:relative;}
.badge{background:#fff;color:#c0392b;border-radius:50%;width:18px;height:18px;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;}

.container{max-width:1000px;margin:40px auto;padding:0 24px 80px;}
.breadcrumb{font-size:.85rem;color:#999;margin-bottom:24px;}
.breadcrumb a{color:#c0392b;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}

.detail-wrap{display:grid;grid-template-columns:1.1fr 1fr;gap:36px;
             background:#fff;border-radius:16px;border:1px solid #ebebeb;
             overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.detail-img{width:100%;height:400px;object-fit:cover;display:block;}
.detail-body{padding:36px 36px 36px 0;display:flex;flex-direction:column;gap:14px;}
.cat-label{font-size:.78rem;text-transform:uppercase;letter-spacing:1px;
           color:#e67e22;font-weight:700;}
.item-name{font-size:2rem;font-weight:900;color:#1a1a1a;line-height:1.2;}
.veg-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;
           border-radius:6px;font-size:.8rem;font-weight:700;}
.veg-badge.veg{background:#eafaf1;color:#27ae60;border:1px solid #a9dfbf;}
.veg-badge.nonveg{background:#fdedec;color:#e74c3c;border:1px solid #f5b7b1;}
.item-desc{color:#666;line-height:1.7;font-size:.97rem;}
.price-tag{font-size:2rem;font-weight:900;color:#c0392b;}
.divider{height:1px;background:#f0f0f0;}

.qty-row{display:flex;align-items:center;gap:12px;}
.qty-row label{font-size:.88rem;color:#666;font-weight:600;}
.qty-input{width:72px;padding:9px;border:1px solid #ddd;border-radius:8px;
           font-size:.95rem;text-align:center;color:#2d2d2d;}
.qty-input:focus{outline:none;border-color:#c0392b;}
.btn-cart{padding:14px 28px;background:#c0392b;color:#fff;border:none;
          border-radius:10px;font-size:1rem;font-weight:800;cursor:pointer;
          width:100%;transition:.15s;margin-top:4px;}
.btn-cart:hover{background:#a93226;}

/* Related */
.rel-title{font-size:1.2rem;font-weight:800;color:#1a1a1a;margin:48px 0 18px;
           display:flex;align-items:center;gap:8px;}
.rel-title::after{content:'';flex:1;height:1px;background:#e8e8e8;margin-left:8px;}
.rel-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
@media(max-width:700px){.rel-grid{grid-template-columns:repeat(2,1fr);}}
.rel-card{background:#fff;border-radius:12px;border:1px solid #ebebeb;overflow:hidden;
          text-decoration:none;color:inherit;transition:.2s;display:block;}
.rel-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.08);transform:translateY(-3px);}
.rel-card img{width:100%;height:120px;object-fit:cover;}
.rel-info{padding:11px 12px;}
.rel-name{font-weight:700;font-size:.88rem;color:#1a1a1a;margin-bottom:4px;}
.rel-price{font-weight:800;color:#c0392b;font-size:.9rem;}

@media(max-width:680px){
  .detail-wrap{grid-template-columns:1fr;}
  .detail-body{padding:24px;}
  .detail-img{height:240px;}
}
</style>
</head>
<body>
<div class="topbar">
  <div class="logo">🍽 Sa<span>vor</span></div>
  <div class="nav-links">
    <a href="index.php">Menu</a>
    <a href="cart.php" class="cart-btn">
      🛒 Cart
      <?php if($cart_count>0):?><span class="badge"><?=$cart_count?></span><?php endif;?>
    </a>
  </div>
</div>

<div class="container">
  <div class="breadcrumb">
    <a href="index.php">Menu</a> / <?=htmlspecialchars($item['cat_name'])?> / <?=htmlspecialchars($item['name'])?>
  </div>

  <div class="detail-wrap">
    <img class="detail-img"
         src="<?=htmlspecialchars($item['image_url'])?>"
         alt="<?=htmlspecialchars($item['name'])?>"
         onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600'">
    <div class="detail-body">
      <div class="cat-label"><?=$item['cat_icon']?> <?=htmlspecialchars($item['cat_name'])?></div>
      <h1 class="item-name"><?=htmlspecialchars($item['name'])?></h1>
      <span class="veg-badge <?=$item['is_veg']?'veg':'nonveg'?>">
        <?=$item['is_veg']?'● Vegetarian':'● Non-Vegetarian'?>
      </span>
      <div class="divider"></div>
      <p class="item-desc"><?=htmlspecialchars($item['description'])?></p>
      <div class="price-tag">₹<?=number_format($item['price'],2)?></div>
      <div class="divider"></div>
      <form method="POST" action="cart.php">
        <input type="hidden" name="action"  value="add">
        <input type="hidden" name="item_id" value="<?=$item['id']?>">
        <div class="qty-row">
          <label>Quantity:</label>
          <input class="qty-input" type="number" name="quantity" value="1" min="1" max="20">
        </div>
        <button class="btn-cart" type="submit">🛒 Add to Cart</button>
      </form>
    </div>
  </div>

  <?php if(mysqli_num_rows($related)>0):?>
  <div class="rel-title">More from <?=htmlspecialchars($item['cat_name'])?></div>
  <div class="rel-grid">
    <?php while($r=mysqli_fetch_assoc($related)):?>
    <a href="item.php?id=<?=$r['id']?>" class="rel-card">
      <img src="<?=htmlspecialchars($r['image_url'])?>"
           alt="<?=htmlspecialchars($r['name'])?>"
           onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=300'">
      <div class="rel-info">
        <div class="rel-name"><?=htmlspecialchars($r['name'])?></div>
        <div class="rel-price">₹<?=number_format($r['price'],2)?></div>
      </div>
    </a>
    <?php endwhile;?>
  </div>
  <?php endif;?>
</div>
</body>
</html>
<?php mysqli_close($conn);?>
