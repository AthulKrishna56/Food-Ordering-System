<?php
session_start();
$conn = mysqli_connect("localhost","root","","food_ordering");
if (!$conn) die("DB Error: " . mysqli_connect_error());
mysqli_set_charset($conn,"utf8");

$cats_res   = mysqli_query($conn,"SELECT * FROM categories ORDER BY id");
$cat_filter = isset($_GET['cat'])    ? (int)$_GET['cat']                                          : 0;
$search     = isset($_GET['search']) ? mysqli_real_escape_string($conn,trim($_GET['search']))      : '';

$where = "WHERE fi.is_available=1";
if ($cat_filter) $where .= " AND fi.category_id=$cat_filter";
if ($search)     $where .= " AND fi.name LIKE '%$search%'";

$items = mysqli_query($conn,
    "SELECT fi.*,c.name AS cat_name,c.icon AS cat_icon
     FROM food_items fi JOIN categories c ON fi.category_id=c.id
     $where ORDER BY c.id,fi.id");

$cart_count = 0;
if (!empty($_SESSION['cart'])) foreach($_SESSION['cart'] as $q) $cart_count += $q;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Savor Restaurant – Menu</title>
<style>
/* ── Reset ── */
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f7f4;color:#2d2d2d;min-height:100vh;}

/* ── Topbar ── */
.topbar{background:#fff;border-bottom:1px solid #e8e8e8;padding:0 40px;
        display:flex;align-items:center;justify-content:space-between;height:64px;
        position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,.06);}
.logo{font-size:1.45rem;font-weight:800;color:#c0392b;letter-spacing:.5px;}
.logo span{color:#e67e22;}
.nav-links{display:flex;align-items:center;gap:8px;}
.nav-links a{text-decoration:none;color:#555;padding:8px 16px;border-radius:8px;
             font-size:.9rem;font-weight:500;transition:.15s;}
.nav-links a:hover{background:#fef3f0;color:#c0392b;}
.cart-btn{background:#c0392b!important;color:#fff!important;border-radius:8px!important;
          font-weight:700!important;display:flex;align-items:center;gap:6px;position:relative;}
.badge{background:#fff;color:#c0392b;border-radius:50%;width:18px;height:18px;
       font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;}

/* ── Hero banner ── */
.hero{background:linear-gradient(120deg,#c0392b,#e67e22);
      color:#fff;padding:52px 40px;position:relative;overflow:hidden;}
.hero::after{content:'🍽️';position:absolute;right:60px;top:50%;transform:translateY(-50%);
             font-size:7rem;opacity:.15;}
.hero h1{font-size:2.4rem;font-weight:900;margin-bottom:8px;}
.hero p{font-size:1.05rem;opacity:.88;margin-bottom:28px;}
.search-bar{display:flex;gap:0;max-width:460px;}
.search-bar input{flex:1;padding:13px 18px;border:none;border-radius:10px 0 0 10px;
                  font-size:.95rem;outline:none;color:#2d2d2d;}
.search-bar button{padding:13px 22px;background:#2d2d2d;color:#fff;border:none;
                   border-radius:0 10px 10px 0;cursor:pointer;font-weight:700;font-size:.95rem;}
.search-bar button:hover{background:#111;}

/* ── Category strip ── */
.cat-strip{background:#fff;border-bottom:1px solid #eee;padding:0 40px;
           display:flex;gap:4px;overflow-x:auto;}
.cat-pill{padding:16px 18px;white-space:nowrap;text-decoration:none;color:#666;
          font-size:.88rem;font-weight:600;border-bottom:3px solid transparent;
          transition:.15s;display:flex;align-items:center;gap:6px;}
.cat-pill:hover{color:#c0392b;}
.cat-pill.active{color:#c0392b;border-bottom-color:#c0392b;}

/* ── Layout ── */
.page-body{max-width:1140px;margin:36px auto;padding:0 24px 80px;}
.section-title{font-size:1.35rem;font-weight:800;color:#1a1a1a;margin-bottom:20px;
               display:flex;align-items:center;gap:8px;}
.section-title::after{content:'';flex:1;height:1px;background:#e8e8e8;margin-left:10px;}

/* ── Grid ── */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px;margin-bottom:48px;}

/* ── Card ── */
.card{background:#fff;border-radius:14px;overflow:hidden;
      border:1px solid #ebebeb;transition:.2s;display:flex;flex-direction:column;}
.card:hover{box-shadow:0 8px 32px rgba(0,0,0,.1);transform:translateY(-4px);}
.card-img-wrap{position:relative;}
.card img{width:100%;height:185px;object-fit:cover;display:block;}
.veg-dot{position:absolute;top:10px;left:10px;background:#fff;border-radius:6px;
         padding:3px 8px;font-size:.72rem;font-weight:700;
         box-shadow:0 1px 4px rgba(0,0,0,.12);}
.veg-dot.veg{color:#27ae60;}
.veg-dot.nonveg{color:#e74c3c;}
.card-body{padding:15px 16px;flex:1;display:flex;flex-direction:column;gap:6px;}
.card-cat{font-size:.73rem;text-transform:uppercase;letter-spacing:.8px;color:#e67e22;font-weight:700;}
.card-name{font-size:1.02rem;font-weight:700;color:#1a1a1a;line-height:1.3;}
.card-desc{font-size:.82rem;color:#888;line-height:1.5;flex:1;}
.card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:10px;}
.price{font-size:1.18rem;font-weight:800;color:#c0392b;}
.btn-add{padding:8px 18px;background:#c0392b;color:#fff;border:none;border-radius:8px;
         font-weight:700;font-size:.85rem;cursor:pointer;transition:.15s;}
.btn-add:hover{background:#a93226;}

/* ── No results ── */
.no-result{grid-column:1/-1;text-align:center;padding:70px;color:#aaa;}
.no-result span{font-size:3rem;display:block;margin-bottom:12px;}
</style>
</head>
<body>

<!-- TOP NAV -->
<div class="topbar">
  <div class="logo">🍽 Sa<span>vor</span></div>
  <div class="nav-links">
    <a href="index.php">Menu</a>
    <a href="admin.php">Admin</a>
    <a href="cart.php" class="cart-btn">
      🛒 Cart
      <?php if($cart_count>0):?><span class="badge"><?=$cart_count?></span><?php endif;?>
    </a>
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <h1>Dine In. Take Out. Enjoy.</h1>
  <p>Fresh ingredients, bold flavours — order from our full restaurant menu.</p>
  <form class="search-bar" method="GET">
    <?php if($cat_filter):?><input type="hidden" name="cat" value="<?=$cat_filter?>"><?php endif;?>
    <input type="text" name="search" placeholder="Search dishes…"
           value="<?=htmlspecialchars($search)?>">
    <button type="submit">Search</button>
  </form>
</div>

<!-- CATEGORY STRIP -->
<div class="cat-strip">
  <a href="index.php" class="cat-pill <?=!$cat_filter?'active':''?>">🍽️ All Items</a>
  <?php mysqli_data_seek($cats_res,0); while($cat=mysqli_fetch_assoc($cats_res)):?>
  <a href="?cat=<?=$cat['id']?><?=$search?'&search='.urlencode($search):''?>"
     class="cat-pill <?=$cat_filter==$cat['id']?'active':''?>">
    <?=$cat['icon']?> <?=htmlspecialchars($cat['name'])?>
  </a>
  <?php endwhile;?>
</div>

<!-- FOOD GRID -->
<div class="page-body">
  <?php if($search||$cat_filter):?>
  <div class="section-title">
    <?=$search?"Results for \"".htmlspecialchars($search)."\"":"".htmlspecialchars(
        mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM categories WHERE id=$cat_filter"))['name']??'Items')?>
  </div>
  <?php else:?>
  <div class="section-title">Our Full Menu</div>
  <?php endif;?>

  <div class="grid">
  <?php if(mysqli_num_rows($items)==0):?>
    <div class="no-result"><span>🔍</span>No dishes found. Try a different search.</div>
  <?php else: while($item=mysqli_fetch_assoc($items)):?>
    <div class="card">
      <div class="card-img-wrap">
        <a href="item.php?id=<?=$item['id']?>">
          <img src="<?=htmlspecialchars($item['image_url'])?>"
               alt="<?=htmlspecialchars($item['name'])?>"
               onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500'">
        </a>
        <span class="veg-dot <?=$item['is_veg']?'veg':'nonveg'?>">
          <?=$item['is_veg']?'● VEG':'● NON-VEG'?>
        </span>
      </div>
      <div class="card-body">
        <div class="card-cat"><?=$item['cat_icon']?> <?=htmlspecialchars($item['cat_name'])?></div>
        <div class="card-name"><?=htmlspecialchars($item['name'])?></div>
        <div class="card-desc"><?=htmlspecialchars(mb_substr($item['description'],0,75))?>…</div>
        <div class="card-footer">
          <span class="price">₹<?=number_format($item['price'],2)?></span>
          <form method="POST" action="cart.php">
            <input type="hidden" name="action"  value="add">
            <input type="hidden" name="item_id" value="<?=$item['id']?>">
            <button class="btn-add" type="submit">+ Add</button>
          </form>
        </div>
      </div>
    </div>
  <?php endwhile; endif;?>
  </div>
</div>

</body>
</html>
<?php mysqli_close($conn);?>
