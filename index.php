<?php
session_start();
require_once 'config/db.php';

// Ambil kategori
$categories = $conn->query("SELECT * FROM categories WHERE is_active=1");

// Ambil produk featured
$featured = $conn->query("SELECT p.*, c.name as cat_name FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active=1 AND p.is_featured=1 LIMIT 8");

// Filter kategori
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
if ($cat_filter > 0) {
    $all_products = $conn->query("SELECT p.*, c.name as cat_name FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active=1 AND p.category_id=$cat_filter");
} else {
    $all_products = $conn->query("SELECT p.*, c.name as cat_name FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active=1");
}

// Jumlah keranjang
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MakanEnak - Pesan Makanan Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">Makan<span>Enak</span></div>
    <ul class="nav-links">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="index.php#menu">Menu</a></li>
        <li><a href="promo.php">Promo</a></li>
    </ul>
    <div class="nav-right">
        <a href="cart.php" class="btn-cart">
            🛒 Keranjang
            <span class="cart-badge"><?= $cart_count ?></span>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="btn-outline-sm">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-outline-sm">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-text">
        <h1>Makanan <span>Lezat</span><br>Diantar ke<br>Rumahmu</h1>
        <p>Temukan ratusan pilihan makanan dan minuman segar. Pesan sekarang, nikmati dalam hitungan menit!</p>
        <div class="hero-btns">
            <a href="#menu" class="btn-primary">Pesan Sekarang</a>
            <a href="#menu" class="btn-outline">Lihat Menu</a>
        </div>
    </div>
    <div class="hero-image">
        🍱
        <div class="hero-badge">
            <div class="hero-badge-icon">⭐</div>
            <div>
                <div class="hero-badge-num">4.9 / 5.0</div>
                <div class="hero-badge-text">Rating pelanggan</div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats">
    <div class="stat-item"><div class="stat-num">200+</div><div class="stat-label">Menu Tersedia</div></div>
    <div class="stat-item"><div class="stat-num">5.000+</div><div class="stat-label">Pelanggan Puas</div></div>
    <div class="stat-item"><div class="stat-num">30 Mnt</div><div class="stat-label">Estimasi Antar</div></div>
    <div class="stat-item"><div class="stat-num">24/7</div><div class="stat-label">Layanan Aktif</div></div>
</div>

<!-- KATEGORI -->
<section class="section">
    <div class="section-header">
        <div class="section-title">Kategori Makanan</div>
    </div>
    <div class="categories">
        <a href="index.php" class="cat-chip <?= $cat_filter==0?'active':'' ?>">🍽️ Semua</a>
        <?php while($cat = $categories->fetch_assoc()): ?>
            <a href="?cat=<?= $cat['id'] ?>" class="cat-chip <?= $cat_filter==$cat['id']?'active':'' ?>">
                <?= $cat['icon'].' '.$cat['name'] ?>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<!-- PRODUK -->
<section class="section" id="menu" style="padding-top:0">
    <div class="section-header">
        <div class="section-title">Menu <?= $cat_filter ? '' : 'Populer' ?></div>
    </div>
    <div class="products">
        <?php while($p = $all_products->fetch_assoc()): ?>
        <div class="product-card">
            <div class="product-img" style="background:#FAECE7">
                <?php if($p['image'] && file_exists('assets/images/'.$p['image'])): ?>
                    <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                    🍽️
                <?php endif; ?>
                <?php if($p['is_featured']): ?>
                    <span class="product-badge">Terlaris</span>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="product-desc"><?= htmlspecialchars(substr($p['description'],0,60)) ?>...</div>
                <div class="product-footer">
                    <div class="product-price">Rp <?= number_format($p['price'],0,',','.') ?></div>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn-add">+</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- PROMO BANNER -->
<div class="promo">
    <div>
        <h2>Diskon 20% Hari Ini!</h2>
        <p>Gunakan kode promo <strong>MAKANHEMAT</strong> saat checkout.<br>Berlaku untuk semua menu pilihan.</p>
        <a href="#menu" class="btn-promo">Pesan Sekarang</a>
    </div>
    <div class="promo-emoji">🎉</div>
</div>

<footer>
    &copy; <?= date('Y') ?> <span>MakanEnak</span> — Dibuat dengan ❤️
</footer>

</body>
</html>
