<?php
session_start();
require_once 'config/db.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { header('Location: cart.php'); exit; }

// Hitung total
$ids = implode(',', array_keys($cart));
$res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
$items = []; $subtotal = 0;
while ($p = $res->fetch_assoc()) {
    $qty = $cart[$p['id']];
    $total = $p['price'] * $qty;
    $subtotal += $total;
    $items[] = array_merge($p, ['qty' => $qty, 'total' => $total]);
}
$shipping = 10000;
$discount = 0;
$grand_total = $subtotal + $shipping - $discount;

$success = false;
$error   = '';

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $conn->real_escape_string(trim($_POST['name']));
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $phone   = $conn->real_escape_string(trim($_POST['phone']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $city    = $conn->real_escape_string(trim($_POST['city']));
    $payment = $conn->real_escape_string($_POST['payment']);
    $notes   = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $promo   = strtoupper(trim($_POST['promo'] ?? ''));

    // Cek promo
    if ($promo) {
        $p_res = $conn->query("SELECT * FROM promos WHERE code='$promo' AND is_active=1 AND (end_date IS NULL OR end_date >= CURDATE())");
        if ($p_res->num_rows > 0) {
            $promo_data = $p_res->fetch_assoc();
            if ($promo_data['type'] === 'percent') {
                $discount = min($subtotal * $promo_data['value'] / 100, $promo_data['max_discount'] ?? PHP_INT_MAX);
            } else {
                $discount = $promo_data['value'];
            }
            $grand_total = $subtotal + $shipping - $discount;
        } else {
            $error = 'Kode promo tidak valid atau sudah kadaluarsa.';
        }
    }

    if (!$error && $name && $email && $phone && $address && $city) {
        $order_code = 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(),0,5));
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO orders (order_code, customer_name, customer_email, customer_phone, shipping_address, shipping_city, shipping_cost, subtotal, discount, total, payment_method, promo_code, notes)
                VALUES ('$order_code','$name','$email','$phone','$address','$city',$shipping,$subtotal,$discount,$grand_total,'$payment','$promo','$notes')");
            $order_id = $conn->insert_id;

            foreach ($items as $item) {
                $pname = $conn->real_escape_string($item['name']);
                $conn->query("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                    VALUES ($order_id,{$item['id']},'$pname',{$item['price']},{$item['qty']},{$item['total']})");
                $conn->query("UPDATE products SET stock = stock - {$item['qty']} WHERE id = {$item['id']}");
            }
            $conn->commit();
            unset($_SESSION['cart']);
            $success = true;
            $_SESSION['last_order'] = $order_code;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    } elseif (!$error) {
        $error = 'Semua field wajib diisi.';
    }
}

$cart_count = array_sum($cart);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - MakanEnak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">Makan<span>Enak</span></a>
    <div class="nav-right">
        <a href="cart.php" class="btn-cart">🛒 Keranjang <span class="cart-badge"><?= $cart_count ?></span></a>
    </div>
</nav>

<div class="container" style="max-width:900px;margin:40px auto;padding:0 20px">
    <h1 class="page-title">📦 Checkout</h1>

    <?php if ($success): ?>
    <div class="success-box">
        <div style="font-size:60px">🎉</div>
        <h2>Pesanan Berhasil!</h2>
        <p>Kode pesanan kamu: <strong><?= $_SESSION['last_order'] ?></strong></p>
        <p style="color:var(--muted);margin-top:8px">Kami akan segera memproses pesananmu.</p>
        <a href="index.php" class="btn-primary" style="margin-top:20px;display:inline-block;text-decoration:none">Pesan Lagi</a>
    </div>
    <?php else: ?>

    <?php if ($error): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="checkout-layout">
        <!-- FORM -->
        <form method="POST" class="checkout-form">
            <h3>Data Pengiriman</h3>
            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="name" placeholder="Nama kamu" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="email@kamu.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>No. HP *</label>
                    <input type="text" name="phone" placeholder="08xxxxxxxxxx" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap *</label>
                <textarea name="address" placeholder="Jl. Contoh No. 1, RT/RW..." rows="3" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Kota *</label>
                <input type="text" name="city" placeholder="Jakarta Selatan" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Metode Pembayaran *</label>
                <select name="payment">
                    <option value="transfer">Transfer Bank</option>
                    <option value="cod">COD (Bayar di Tempat)</option>
                    <option value="ewallet">E-Wallet (GoPay/OVO)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kode Promo</label>
                <input type="text" name="promo" placeholder="MAKANHEMAT" value="<?= htmlspecialchars($_POST['promo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="notes" placeholder="Tambah catatan untuk penjual..." rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:15px">
                ✅ Buat Pesanan
            </button>
        </form>

        <!-- SUMMARY -->
        <div class="cart-summary">
            <h3>Ringkasan Pesanan</h3>
            <?php foreach ($items as $item): ?>
            <div class="summary-item">
                <span><?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></span>
                <span>Rp <?= number_format($item['total'],0,',','.') ?></span>
            </div>
            <?php endforeach; ?>
            <hr style="border:none;border-top:0.5px solid #eee;margin:12px 0">
            <div class="summary-row"><span>Subtotal</span><span>Rp <?= number_format($subtotal,0,',','.') ?></span></div>
            <div class="summary-row"><span>Ongkos Kirim</span><span>Rp <?= number_format($shipping,0,',','.') ?></span></div>
            <?php if ($discount > 0): ?>
            <div class="summary-row" style="color:#1D9E75"><span>Diskon</span><span>-Rp <?= number_format($discount,0,',','.') ?></span></div>
            <?php endif; ?>
            <div class="summary-row total"><span>Total</span><span>Rp <?= number_format($grand_total,0,',','.') ?></span></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer>&copy; <?= date('Y') ?> <span>MakanEnak</span></footer>
</body>
</html>
