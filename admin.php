<?php
session_start();
$conn = mysqli_connect("localhost","root","","food_ordering");
if (!$conn) die("DB Error: ".mysqli_connect_error());
mysqli_set_charset($conn,"utf8");

/* ── Admin login ─────────────────────────────────────────── */
$ADMIN_PASS = "admin123"; // Change this!
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['pass']??'')===$ADMIN_PASS) {
        $_SESSION['admin'] = true;
    } else {
        ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Admin Login – Savor</title>
        <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f8f7f4;display:flex;
             align-items:center;justify-content:center;min-height:100vh;}
        .box{background:#fff;border-radius:16px;border:1px solid #e8e8e8;
             padding:44px 38px;width:360px;text-align:center;
             box-shadow:0 4px 24px rgba(0,0,0,.08);}
        .icon{font-size:2.5rem;margin-bottom:12px;}
        h2{font-size:1.5rem;font-weight:800;color:#1a1a1a;margin-bottom:6px;}
        p{color:#999;font-size:.88rem;margin-bottom:26px;}
        input{width:100%;padding:12px 14px;border:1px solid #ddd;border-radius:8px;
              font-size:.95rem;color:#2d2d2d;margin-bottom:14px;}
        input:focus{outline:none;border-color:#c0392b;}
        button{width:100%;padding:13px;background:#c0392b;color:#fff;border:none;
               border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;}
        button:hover{background:#a93226;}
        .err{color:#e74c3c;font-size:.85rem;margin-bottom:12px;}
        a{display:block;margin-top:16px;color:#aaa;font-size:.84rem;text-decoration:none;}
        </style></head><body>
        <div class="box">
          <div class="icon">🔐</div>
          <h2>Admin Login</h2>
          <p>Savor Restaurant Management</p>
          <?php if($_SERVER['REQUEST_METHOD']==='POST'):?><div class="err">⚠ Incorrect password.</div><?php endif;?>
          <form method="POST">
            <input type="password" name="pass" placeholder="Admin password" autofocus>
            <button type="submit">Login</button>
          </form>
          <a href="index.php">← Back to site</a>
        </div></body></html><?php exit;
    }
}
if (isset($_GET['logout'])) { unset($_SESSION['admin']); header("Location: admin.php"); exit; }

/* ── Fetch categories ──────────────────────────────────────── */
$cats_res = mysqli_query($conn,"SELECT * FROM categories ORDER BY id");
$cats = [];
while ($c=mysqli_fetch_assoc($cats_res)) $cats[$c['id']]=$c;

/* ── Handle POST ──────────────────────────────────────────── */
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act=$_POST['act']??'';

    if ($act==='add_item') {
        $n=mysqli_real_escape_string($conn,trim($_POST['name']));
        $d=mysqli_real_escape_string($conn,trim($_POST['description']));
        $p=(float)$_POST['price'];
        $cat=(int)$_POST['category_id'];
        $img=mysqli_real_escape_string($conn,trim($_POST['image_url']));
        $veg=isset($_POST['is_veg'])?1:0;
        $avl=isset($_POST['is_available'])?1:0;
        mysqli_query($conn,"INSERT INTO food_items (category_id,name,description,price,image_url,is_veg,is_available)
                             VALUES ($cat,'$n','$d',$p,'$img',$veg,$avl)");
        $msg='✅ Item added successfully.';
    }
    if ($act==='edit_item') {
        $id=(int)$_POST['id'];
        $n=mysqli_real_escape_string($conn,trim($_POST['name']));
        $d=mysqli_real_escape_string($conn,trim($_POST['description']));
        $p=(float)$_POST['price'];
        $cat=(int)$_POST['category_id'];
        $img=mysqli_real_escape_string($conn,trim($_POST['image_url']));
        $veg=isset($_POST['is_veg'])?1:0;
        $avl=isset($_POST['is_available'])?1:0;
        mysqli_query($conn,"UPDATE food_items SET category_id=$cat,name='$n',description='$d',
                             price=$p,image_url='$img',is_veg=$veg,is_available=$avl WHERE id=$id");
        $msg='✅ Item updated.';
    }
    if ($act==='delete_item') {
        $id=(int)$_POST['id'];
        mysqli_query($conn,"DELETE FROM food_items WHERE id=$id");
        $msg='🗑 Item deleted.';
    }
    if ($act==='update_order') {
        $oid=(int)$_POST['order_id'];
        $status=mysqli_real_escape_string($conn,$_POST['status']);
        mysqli_query($conn,"UPDATE orders SET status='$status' WHERE id=$oid");
        $msg='✅ Order #'.$oid.' status updated.';
    }
}

$tab = $_GET['tab'] ?? 'items';

/* ── Data fetches ─────────────────────────────────────────── */
$items = mysqli_query($conn,
    "SELECT fi.*,c.name AS cat_name FROM food_items fi
     JOIN categories c ON fi.category_id=c.id ORDER BY fi.id DESC");

$orders = mysqli_query($conn,
    "SELECT o.*,
       GROUP_CONCAT(fi.name ORDER BY oi.id SEPARATOR ', ') AS item_names,
       GROUP_CONCAT(oi.quantity ORDER BY oi.id SEPARATOR ', ') AS quantities
     FROM orders o
     LEFT JOIN order_items oi ON o.id=oi.order_id
     LEFT JOIN food_items fi ON oi.food_item_id=fi.id
     GROUP BY o.id ORDER BY o.id DESC");

/* Sales report queries */
$daily_sales = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(total_amount) AS rev
     FROM orders WHERE status!='cancelled' AND created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)
     GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14");

$top_items = mysqli_query($conn,
    "SELECT fi.name, SUM(oi.quantity) AS sold, SUM(oi.quantity*oi.unit_price) AS revenue
     FROM order_items oi JOIN food_items fi ON oi.food_item_id=fi.id
     GROUP BY oi.food_item_id ORDER BY sold DESC LIMIT 8");

$by_type = mysqli_query($conn,
    "SELECT order_type, COUNT(*) AS cnt, SUM(total_amount) AS rev
     FROM orders WHERE status!='cancelled' GROUP BY order_type");

// Stats
$s_items  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM food_items"))[0];
$s_orders = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$s_pending= mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='pending'"))[0];
$s_rev    = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status!='cancelled'"))[0];
$s_today  = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status!='cancelled'"))[0];

// Edit mode
$edit_item=null;
if (isset($_GET['edit'])) {
    $eid=(int)$_GET['edit'];
    $edit_item=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM food_items WHERE id=$eid"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin – Savor</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f5f7;color:#2d2d2d;}

/* Sidebar layout */
.layout{display:flex;min-height:100vh;}
.sidebar{width:220px;background:#1a1a1a;color:#ccc;flex-shrink:0;
         display:flex;flex-direction:column;position:sticky;top:0;height:100vh;}
.sidebar-logo{padding:24px 20px 16px;font-size:1.3rem;font-weight:800;color:#fff;
              border-bottom:1px solid #2e2e2e;}
.sidebar-logo span{color:#e67e22;}
.sidebar nav{padding:16px 0;flex:1;}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:12px 20px;
               color:#aaa;text-decoration:none;font-size:.9rem;font-weight:500;transition:.15s;}
.sidebar nav a:hover,.sidebar nav a.active{background:#2e2e2e;color:#fff;}
.sidebar nav a.active{border-right:3px solid #c0392b;}
.sidebar-footer{padding:16px 20px;border-top:1px solid #2e2e2e;}
.sidebar-footer a{color:#888;font-size:.82rem;text-decoration:none;}
.sidebar-footer a:hover{color:#e74c3c;}

.main{flex:1;overflow-y:auto;}
.topbar{background:#fff;padding:0 28px;height:58px;display:flex;align-items:center;
        justify-content:space-between;border-bottom:1px solid #e8e8e8;
        box-shadow:0 1px 6px rgba(0,0,0,.05);position:sticky;top:0;z-index:50;}
.topbar h1{font-size:1.1rem;font-weight:700;color:#1a1a1a;}
.topbar a{font-size:.84rem;color:#999;text-decoration:none;padding:7px 14px;
          border-radius:6px;border:1px solid #e8e8e8;}
.topbar a:hover{border-color:#c0392b;color:#c0392b;}

.content{padding:28px;}

/* Stats strip */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:28px;}
@media(max-width:900px){.stats{grid-template-columns:repeat(3,1fr);}}
.stat-card{background:#fff;border-radius:12px;border:1px solid #e8e8e8;
           padding:18px 20px;box-shadow:0 1px 6px rgba(0,0,0,.04);}
.stat-card .lbl{font-size:.75rem;text-transform:uppercase;letter-spacing:.7px;color:#aaa;font-weight:600;margin-bottom:6px;}
.stat-card .val{font-size:1.6rem;font-weight:900;color:#1a1a1a;}
.stat-card.red .val{color:#c0392b;}
.stat-card.green .val{color:#27ae60;}

/* Msg */
.msg{background:#eafaf1;border:1px solid #a9dfbf;border-radius:8px;
     padding:11px 16px;margin-bottom:20px;font-size:.9rem;color:#1e8449;font-weight:600;}

/* Card panel */
.panel{background:#fff;border-radius:12px;border:1px solid #e8e8e8;
       box-shadow:0 1px 6px rgba(0,0,0,.04);margin-bottom:24px;overflow:hidden;}
.panel-header{padding:16px 22px;border-bottom:1px solid #f0f0f0;
              font-size:1rem;font-weight:700;color:#1a1a1a;}

/* Form */
.form-body{padding:22px;}
.fg-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:600px){.fg-grid{grid-template-columns:1fr;}}
.fg{display:flex;flex-direction:column;gap:4px;}
.fg.full{grid-column:1/-1;}
.fg label{font-size:.79rem;font-weight:600;color:#555;}
.fg input,.fg select,.fg textarea{padding:9px 12px;border:1px solid #ddd;border-radius:7px;
  font-size:.88rem;font-family:inherit;color:#2d2d2d;transition:.15s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#c0392b;
  box-shadow:0 0 0 3px rgba(192,57,43,.08);}
.fg textarea{resize:vertical;min-height:65px;}
.chk-row{display:flex;gap:20px;margin-top:2px;flex-wrap:wrap;}
.chk-row label{display:flex;align-items:center;gap:6px;font-size:.88rem;color:#555;cursor:pointer;}
.btn-row{display:flex;gap:10px;margin-top:18px;}
.btn{padding:9px 22px;border-radius:7px;border:none;cursor:pointer;font-weight:700;font-size:.88rem;transition:.15s;}
.btn-primary{background:#c0392b;color:#fff;}
.btn-primary:hover{background:#a93226;}
.btn-sec{background:#f4f5f7;color:#555;border:1px solid #ddd;}
.btn-sec:hover{background:#e8e8e8;}
.btn-danger{background:#fdedec;color:#e74c3c;border:1px solid #f5b7b1;}
.btn-danger:hover{background:#fad7d4;}
.btn-sm{padding:5px 12px;font-size:.78rem;border-radius:5px;}

/* Table */
.tbl-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead th{padding:11px 14px;text-align:left;font-size:.76rem;text-transform:uppercase;
         letter-spacing:.7px;color:#aaa;font-weight:600;border-bottom:1px solid #f0f0f0;}
td{padding:13px 14px;border-bottom:1px solid #f8f8f8;font-size:.88rem;vertical-align:middle;}
tbody tr:hover td{background:#fafafa;}
tbody tr:last-child td{border:none;}
.tbl-img{width:44px;height:44px;object-fit:cover;border-radius:7px;border:1px solid #eee;}
.avail-yes{color:#27ae60;font-weight:700;}
.avail-no{color:#e74c3c;font-weight:700;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.76rem;font-weight:700;}
.s-pending   {background:#fef9e7;color:#f39c12;border:1px solid #f9e4a0;}
.s-preparing {background:#eaf3fb;color:#2980b9;border:1px solid #aed6f1;}
.s-ready     {background:#e8f8f5;color:#1abc9c;border:1px solid #a2d9ce;}
.s-delivered {background:#eafaf1;color:#27ae60;border:1px solid #a9dfbf;}
.s-cancelled {background:#fdedec;color:#e74c3c;border:1px solid #f5b7b1;}

/* Reports */
.rep-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:22px;}
@media(max-width:680px){.rep-grid{grid-template-columns:1fr;}}
.rep-card{background:#f8f7f4;border-radius:10px;border:1px solid #eee;padding:18px;}
.rep-card h3{font-size:.9rem;font-weight:700;color:#555;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px;}
.rep-row{display:flex;justify-content:space-between;align-items:center;
         padding:7px 0;border-bottom:1px solid #eee;font-size:.88rem;}
.rep-row:last-child{border:none;}
.rep-row .name{color:#333;font-weight:500;}
.rep-row .val{font-weight:700;color:#1a1a1a;}
.bar-wrap{flex:1;margin:0 12px;height:6px;background:#eee;border-radius:3px;overflow:hidden;}
.bar{height:100%;background:linear-gradient(90deg,#c0392b,#e67e22);border-radius:3px;}
.full-table{padding:0;}
</style>
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">🍽 Sa<span>vor</span> <span style="font-size:.7rem;color:#666;font-weight:400;">Admin</span></div>
  <nav>
    <a href="admin.php?tab=items"   class="<?=$tab==='items'?'active':''?>">📋 Menu Items</a>
    <a href="admin.php?tab=orders"  class="<?=$tab==='orders'?'active':''?>">🧾 Orders</a>
    <a href="admin.php?tab=reports" class="<?=$tab==='reports'?'active':''?>">📊 Sales Reports</a>
    <a href="index.php" style="margin-top:12px;">🌐 View Site</a>
  </nav>
  <div class="sidebar-footer">
    <a href="admin.php?logout=1">⬅ Logout</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
<div class="topbar">
  <h1>
    <?php if($tab==='items') echo '📋 Menu Management';
    elseif($tab==='orders') echo '🧾 Order Management';
    else echo '📊 Sales Reports'; ?>
  </h1>
  <a href="index.php">View Live Site</a>
</div>

<div class="content">

<!-- STATS -->
<div class="stats">
  <div class="stat-card"><div class="lbl">Menu Items</div><div class="val"><?=$s_items?></div></div>
  <div class="stat-card"><div class="lbl">Total Orders</div><div class="val"><?=$s_orders?></div></div>
  <div class="stat-card red"><div class="lbl">Pending</div><div class="val"><?=$s_pending?></div></div>
  <div class="stat-card green"><div class="lbl">Today's Sales</div><div class="val">₹<?=number_format($s_today,0)?></div></div>
  <div class="stat-card"><div class="lbl">Total Revenue</div><div class="val">₹<?=number_format($s_rev,0)?></div></div>
</div>

<?php if($msg):?><div class="msg"><?=$msg?></div><?php endif;?>

<!-- ══ ITEMS TAB ═════════════════════════════════════════ -->
<?php if($tab==='items'):?>

<div class="panel">
  <div class="panel-header"><?=$edit_item?'✏️ Edit Item':'➕ Add New Item'?></div>
  <div class="form-body">
    <form method="POST">
      <input type="hidden" name="act" value="<?=$edit_item?'edit_item':'add_item'?>">
      <?php if($edit_item):?><input type="hidden" name="id" value="<?=$edit_item['id']?>"><?php endif;?>
      <div class="fg-grid">
        <div class="fg">
          <label>Item Name *</label>
          <input type="text" name="name" required value="<?=htmlspecialchars($edit_item['name']??'')?>">
        </div>
        <div class="fg">
          <label>Category *</label>
          <select name="category_id">
            <?php foreach($cats as $cat):?>
            <option value="<?=$cat['id']?>" <?=($edit_item&&$edit_item['category_id']==$cat['id'])?'selected':''?>>
              <?=$cat['icon']?> <?=htmlspecialchars($cat['name'])?>
            </option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="fg">
          <label>Price (₹) *</label>
          <input type="number" name="price" min="0" step="0.01" required value="<?=$edit_item['price']??''?>">
        </div>
        <div class="fg">
          <label>Image URL</label>
          <input type="text" name="image_url" placeholder="https://…" value="<?=htmlspecialchars($edit_item['image_url']??'')?>">
        </div>
        <div class="fg full">
          <label>Description</label>
          <textarea name="description"><?=htmlspecialchars($edit_item['description']??'')?></textarea>
        </div>
        <div class="fg full">
          <div class="chk-row">
            <label><input type="checkbox" name="is_veg" <?=(!$edit_item||$edit_item['is_veg'])?'checked':''?>> 🟢 Vegetarian</label>
            <label><input type="checkbox" name="is_available" <?=(!$edit_item||$edit_item['is_available'])?'checked':''?>> ✅ Available on menu</label>
          </div>
        </div>
      </div>
      <div class="btn-row">
        <button class="btn btn-primary" type="submit"><?=$edit_item?'💾 Update Item':'➕ Add Item'?></button>
        <?php if($edit_item):?><a href="admin.php?tab=items" class="btn btn-sec">Cancel</a><?php endif;?>
      </div>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-header">All Menu Items</div>
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th></th><th>Name</th><th>Category</th><th>Price</th><th>Type</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php while($item=mysqli_fetch_assoc($items)):?>
      <tr>
        <td><img class="tbl-img" src="<?=htmlspecialchars($item['image_url'])?>"
                 onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=80'"></td>
        <td style="font-weight:600;"><?=htmlspecialchars($item['name'])?></td>
        <td style="color:#777;"><?=htmlspecialchars($item['cat_name'])?></td>
        <td style="font-weight:700;">₹<?=number_format($item['price'],2)?></td>
        <td><?=$item['is_veg']?'🟢 Veg':'🔴 Non-Veg'?></td>
        <td><?=$item['is_available']?'<span class="avail-yes">Available</span>':'<span class="avail-no">Hidden</span>'?></td>
        <td style="display:flex;gap:8px;flex-wrap:wrap;">
          <a href="admin.php?tab=items&edit=<?=$item['id']?>" class="btn btn-sm btn-sec">✏️ Edit</a>
          <form method="POST" onsubmit="return confirm('Delete this item?')">
            <input type="hidden" name="act" value="delete_item">
            <input type="hidden" name="id"  value="<?=$item['id']?>">
            <button class="btn btn-sm btn-danger" type="submit">🗑</button>
          </form>
        </td>
      </tr>
      <?php endwhile;?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ ORDERS TAB ════════════════════════════════════════ -->
<?php elseif($tab==='orders'):?>
<div class="panel">
  <div class="panel-header">All Orders</div>
  <div class="tbl-wrap">
    <table>
      <thead><tr>
        <th>#</th><th>Customer</th><th>Phone</th><th>Type</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Update</th>
      </tr></thead>
      <tbody>
      <?php while($o=mysqli_fetch_assoc($orders)):
        $names=explode(', ',$o['item_names']??'');
        $qtys=explode(', ',$o['quantities']??'');
      ?>
      <tr>
        <td style="font-weight:800;color:#c0392b;">#<?=$o['id']?></td>
        <td style="font-weight:600;"><?=htmlspecialchars($o['customer_name'])?></td>
        <td style="color:#777;"><?=htmlspecialchars($o['phone'])?></td>
        <td>
          <?php if($o['order_type']==='dine-in'):?>
            <span style="color:#2980b9;font-weight:600;">🍽 Dine-In</span>
            <span style="display:block;font-size:.76rem;color:#aaa;">Table: <?=htmlspecialchars($o['table_number'])?></span>
          <?php else:?>
            <span style="color:#27ae60;font-weight:600;">🛍 Takeout</span>
          <?php endif;?>
        </td>
        <td style="font-size:.82rem;color:#777;max-width:180px;">
          <?php for($i=0;$i<count($names);$i++) echo htmlspecialchars(trim($names[$i])).' ×'.trim($qtys[$i]??1).'<br>'; ?>
        </td>
        <td style="font-weight:800;color:#c0392b;">₹<?=number_format($o['total_amount'],2)?></td>
        <td><span class="status-badge s-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
        <td style="font-size:.78rem;color:#aaa;"><?=date('d M, g:i A',strtotime($o['created_at']))?></td>
        <td>
          <form method="POST" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="act"      value="update_order">
            <input type="hidden" name="order_id" value="<?=$o['id']?>">
            <select name="status" style="padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:.82rem;color:#333;">
              <?php foreach(['pending','preparing','ready','delivered','cancelled'] as $st):?>
              <option value="<?=$st?>" <?=$o['status']===$st?'selected':''?>><?=ucfirst($st)?></option>
              <?php endforeach;?>
            </select>
            <button class="btn btn-sm btn-primary" type="submit">✔</button>
          </form>
        </td>
      </tr>
      <?php endwhile;?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ REPORTS TAB ═══════════════════════════════════════ -->
<?php elseif($tab==='reports'):?>

<div class="rep-grid">

  <!-- Top selling items -->
  <div class="rep-card" style="grid-column:1/-1;">
    <h3>🏆 Top Selling Items</h3>
    <?php
    $rows=[];
    while($r=mysqli_fetch_assoc($top_items)) $rows[]=$r;
    $max_sold = $rows ? max(array_column($rows,'sold')) : 1;
    foreach($rows as $r):
      $pct = $max_sold>0 ? round(($r['sold']/$max_sold)*100) : 0;
    ?>
    <div class="rep-row">
      <span class="name" style="min-width:160px;"><?=htmlspecialchars($r['name'])?></span>
      <div class="bar-wrap"><div class="bar" style="width:<?=$pct?>%;"></div></div>
      <span class="val" style="min-width:60px;text-align:right;"><?=$r['sold']?> sold</span>
      <span style="color:#c0392b;font-weight:700;min-width:80px;text-align:right;">₹<?=number_format($r['revenue'],0)?></span>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Daily sales (last 14 days) -->
  <div class="rep-card">
    <h3>📅 Daily Sales (Last 14 Days)</h3>
    <?php mysqli_data_seek($daily_sales,0); while($r=mysqli_fetch_assoc($daily_sales)):?>
    <div class="rep-row">
      <span class="name"><?=date('d M',strtotime($r['day']))?></span>
      <span style="color:#777;font-size:.82rem;"><?=$r['cnt']?> orders</span>
      <span class="val">₹<?=number_format($r['rev'],0)?></span>
    </div>
    <?php endwhile;?>
  </div>

  <!-- By order type -->
  <div class="rep-card">
    <h3>📊 Revenue by Order Type</h3>
    <?php while($r=mysqli_fetch_assoc($by_type)):?>
    <div class="rep-row">
      <span class="name"><?=$r['order_type']==='dine-in'?'🍽 Dine-In':'🛍 Takeout'?></span>
      <span style="color:#777;"><?=$r['cnt']?> orders</span>
      <span class="val">₹<?=number_format($r['rev'],0)?></span>
    </div>
    <?php endwhile;?>

    <div style="margin-top:20px;padding-top:14px;border-top:1px solid #eee;">
      <div class="rep-row"><span class="name" style="font-weight:700;">Total Revenue</span>
        <span class="val" style="color:#c0392b;font-size:1.1rem;">₹<?=number_format($s_rev,0)?></span></div>
      <div class="rep-row"><span class="name">Total Orders</span><span class="val"><?=$s_orders?></span></div>
      <div class="rep-row"><span class="name">Avg Order Value</span>
        <span class="val">₹<?=$s_orders>0?number_format($s_rev/$s_orders,0):'0'?></span></div>
    </div>
  </div>

</div>
<?php endif;?>

</div><!-- /content -->
</div><!-- /main -->
</div><!-- /layout -->
</body>
</html>
<?php mysqli_close($conn);?>
