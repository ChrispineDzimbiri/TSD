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

// Get only categories that have products
$categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                    FROM categories c 
                    INNER JOIN products p ON c.id = p.category_id 
                    GROUP BY c.id 
                    HAVING product_count > 0
                    ORDER BY c.name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get one sample product image for each category
$category_images = [];
foreach ($categories as $category) {
    $image_query = "SELECT image FROM products WHERE category_id = :category_id AND image IS NOT NULL LIMIT 1";
    $image_stmt = $db->prepare($image_query);
    $image_stmt->bindParam(':category_id', $category['id']);
    $image_stmt->execute();
    $category_image = $image_stmt->fetch(PDO::FETCH_ASSOC);
    $category_images[$category['id']] = $category_image ? $category_image['image'] : 'cam1.jpg';
}
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
        <?php if (count($categories) > 0): ?>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-img">
                        <?php if (isset($category_images[$category['id']]) && $category_images[$category['id']] !== 'cam1.jpg'): ?>
                            <img src="../images/uploads/<?php echo htmlspecialchars($category_images[$category['id']]); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php else: ?>
                            <img src="../images/cam1.jpg" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="category-content">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="product-count"><?php echo $category['product_count']; ?> product<?php echo $category['product_count'] == 1 ? '' : 's'; ?></p>
                        <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-secondary">View Products</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-categories">
                <p>No categories with products available yet.</p>
                <a href="../admin/products.php?action=add" class="btn">Add Products in Admin</a>
            </div>
        <?php endif; ?>
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
                        <?php if (!empty($product['image']) && file_exists('../images/uploads/' . $product['image'])): ?>
                            <img src="../images/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="../images/cam1.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                        
                        <?php if ($product['badge']): ?>
                        <span class="product-badge"><?php echo htmlspecialchars($product['badge']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">K<?php echo number_format($product['price'], 2); ?></p>
                        <p class="product-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
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
                    <?php if (isset($_SESSION['admin_logged_in'])): ?>
                        <a href="../admin/products.php?action=add" class="btn">Add Featured Products</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>