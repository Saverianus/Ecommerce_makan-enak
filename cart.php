<?php
session_start();
require_once 'config/db.php';

// Tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $action     = $_POST['action'] ?? '';

    if ($action === 'add') {
        $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
        header('Location: index.php');
        exit;
    }
    if ($action === 'remove') {
        unset($_SESSION['cart'][$product_id]);
    }
    if ($action === 'update') {
        $qty = (int)$_POST['qty'];
        if ($qty > 0) $_SESSION['cart'][$product_id] = $qty;
        else unset($_SESSION['cart'][$product_id]);
    }
    header('Location: cart.php');
    exit;
}

$cart  = $_SESSION['cart'] ?? [];
$items = [];
$subtotal = 0;

if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    $res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($p = $res->fetch_assoc()) {
        $qty   = $cart[$p['id']];
        $total = $p['price'] * $qty;
        $subtotal += $total;
        $items[] = array_merge($p, ['qty' => $qty, 'total' => $total]);
    }
}

$cart_count = array_sum($cart);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang - MakanEnak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">Makan<span>Enak</span></a>
    <ul class="nav-links">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="index.php#menu">Menu</a></li>
    </ul>
    <div class="nav-right">
        <a href="cart.php" class="btn-cart">🛒 Keranjang <span class="cart-badge"><?= $cart_count ?></span></a>
    </div>
</nav>

<div class="container" style="max-width:860px;margin:40px auto;padding:0 20px">
    <h1 class="page-title">🛒 Keranjang Belanja</h1>

    <?php if (empty($items)): ?>
        <div class="empty-cart">
            <div style="font-size:60px">🍽️</div>
            <p>Keranjang kamu masih kosong.</p>
            <a href="index.php" class="btn-primary">Lihat Menu</a>
        </div>
    <?php else: ?>
    <div class="cart-layout">
        <!-- ITEM LIST -->
        <div class="cart-items">
            <?php foreach ($items as $item): ?>
            <div class="cart-item">
                <div class="cart-item-img">🍽️</div>
                <div class="cart-item-info">
                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="cart-item-price">Rp <?= number_format($item['price'],0,',','.') ?></div>
                </div>
                <div class="cart-item-actions">
                    <form method="POST" style="display:flex;align-items:center;gap:8px">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit" name="qty" value="<?= $item['qty']-1 ?>" class="qty-btn">−</button>
                        <span class="qty-num"><?= $item['qty'] ?></span>
                        <button type="submit" name="qty" value="<?= $item['qty']+1 ?>" class="qty-btn">+</button>
                    </form>
                    <div class="cart-item-total">Rp <?= number_format($item['total'],0,',','.') ?></div>
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn-remove">🗑️</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- SUMMARY -->
        <div class="cart-summary">
            <h3>Ringkasan Pesanan</h3>
            <div class="summary-row"><span>Subtotal</span><span>Rp <?= number_format($subtotal,0,',','.') ?></span></div>
            <div class="summary-row"><span>Ongkos Kirim</span><span>Rp 10.000</span></div>
            <div class="summary-row total"><span>Total</span><span>Rp <?= number_format($subtotal+10000,0,',','.') ?></span></div>
            <a href="checkout.php" class="btn-primary" style="display:block;text-align:center;margin-top:16px;text-decoration:none">
                Lanjut Checkout →
            </a>
            <a href="index.php" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:var(--muted)">
                ← Tambah Menu Lagi
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer>&copy; <?= date('Y') ?> <span>MakanEnak</span></footer>
</body>
</html>
