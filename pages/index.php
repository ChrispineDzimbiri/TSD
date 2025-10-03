<?php
$page_title = "Home";
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get featured products
$featured_query = "SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT 6";
$featured_stmt = $db->prepare($featured_query);
$featured_stmt->execute();
$featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1>Discover the Best Security Solutions</h1>
        <p>A Secured Surrounding, A Peaceful Nation.</p>
        <a href="products.php" class="btn">Shop Now</a>
    </div>
</section>

<!-- Categories -->
<section class="categories">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-img">
                    <img src="images/cam1.jpg" alt="<?php echo $category['name']; ?>">
                </div>
                <div class="category-content">
                    <h3><?php echo $category['name']; ?></h3>
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-secondary">View Products</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="featured">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="product-grid">
            <?php if (count($featured_products) > 0): ?>
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <img src="images/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        <?php if ($product['badge']): ?>
                        <span class="product-badge"><?php echo $product['badge']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-price">K<?php echo number_format($product['price'], 2); ?></p>
                        <p class="product-desc"><?php echo substr($product['description'], 0, 100); ?>...</p>
                        <div class="product-actions">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                            <a href="https://wa.me/265991234567?text=Hi, I'm interested in <?php echo urlencode($product['name']); ?> - K<?php echo number_format($product['price'], 2); ?>" class="btn btn-secondary" target="_blank">WhatsApp</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    <p>No featured products available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>