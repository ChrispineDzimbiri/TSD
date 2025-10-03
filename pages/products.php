<?php
$page_title = "Products";
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$category_id = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if (!empty($category_id)) {
    $query .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search_query)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search_query%";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for sidebar
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container">
        <h1>Our Security Products</h1>
        <p>Discover our comprehensive range of security solutions</p>
    </div>
</section>

<!-- Search Bar -->
<section class="search-section">
    <div class="container">
        <div class="search-container">
            <form method="GET" action="products.php" class="search-form">
                <input type="text" name="search" placeholder="Search products..." class="search-bar" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="products-page">
    <div class="container">
        <div class="products-container">
            <aside class="sidebar">
                <h3>Filter by Category</h3>
                <ul class="category-filters">
                    <li><a href="products.php" class="<?php echo empty($category_id) ? 'active' : ''; ?>">All Products</a></li>
                    <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="products.php?category=<?php echo $category['id']; ?>" 
                           class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <div class="products-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <?php if (!empty($product['image']) && file_exists('../images/uploads/' . $product['image'])): ?>
                                <img src="../images/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <!-- Fallback image if product image doesn't exist -->
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
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                        <a href="products.php" class="btn">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>