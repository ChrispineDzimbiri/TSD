<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Initialize variables with default values to prevent undefined warnings
$action = $_GET['action'] ?? 'list';
$message = '';
$products = [];
$categories = [];
$all_categories = [];
$product = [];

// Handle category operations
if (isset($_POST['category_action'])) {
    if ($_POST['category_action'] == 'add') {
        $category_name = sanitize($_POST['new_category_name']);
        $category_description = sanitize($_POST['new_category_description']);
        
        if (!empty($category_name)) {
            $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $category_name);
            $stmt->bindParam(':description', $category_description);
            
            if ($stmt->execute()) {
                $message = "Category added successfully!";
            } else {
                $message = "Error adding category!";
            }
        } else {
            $message = "Category name is required!";
        }
    } elseif ($_POST['category_action'] == 'delete' && isset($_POST['category_id'])) {
        $category_id = $_POST['category_id'];
        
        // Check if category has products
        $check_query = "SELECT COUNT(*) FROM products WHERE category_id = :category_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':category_id', $category_id);
        $check_stmt->execute();
        $product_count = $check_stmt->fetchColumn();
        
        if ($product_count == 0) {
            $query = "DELETE FROM categories WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $category_id);
            
            if ($stmt->execute()) {
                $message = "Category deleted successfully!";
            } else {
                $message = "Error deleting category!";
            }
        } else {
            $message = "Cannot delete category that has products assigned!";
        }
    }
}

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['category_action'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = sanitize($_POST['price']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $badge = sanitize($_POST['badge']);
    
    if ($action == 'add') {
        if (!empty($_FILES['image']['name'])) {
            $image_result = uploadImage($_FILES['image']);
            if ($image_result['success']) {
                $query = "INSERT INTO products (name, description, price, category_id, image, featured, badge) 
                         VALUES (:name, :description, :price, :category_id, :image, :featured, :badge)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':image', $image_result['filename']);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':badge', $badge);
                
                if ($stmt->execute()) {
                    $message = "Product added successfully!";
                    $action = 'list';
                } else {
                    $message = "Error adding product!";
                }
            } else {
                $message = $image_result['message'];
            }
        } else {
            $message = "Product image is required!";
        }
    } else if ($action == 'edit') {
        $id = $_POST['id'];
        $image_update = "";
        $image_result = [];
        
        if (!empty($_FILES['image']['name'])) {
            $image_result = uploadImage($_FILES['image']);
            if ($image_result['success']) {
                $image_update = ", image = :image";
            } else {
                $message = $image_result['message'];
            }
        }
        
        $query = "UPDATE products SET name = :name, description = :description, price = :price, 
                 category_id = :category_id, featured = :featured, badge = :badge";
        $query .= !empty($image_update) ? $image_update : "";
        $query .= " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':featured', $featured);
        $stmt->bindParam(':badge', $badge);
        $stmt->bindParam(':id', $id);
        if (!empty($image_update)) {
            $stmt->bindParam(':image', $image_result['filename']);
        }
        
        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            $action = 'list';
        } else {
            $message = "Error updating product!";
        }
    }
}

// Handle product delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $message = "Product deleted successfully!";
    } else {
        $message = "Error deleting product!";
    }
}

// Get products for listing
if ($action == 'list') {
    try {
        $query = "SELECT p.*, c.name as category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error loading products: " . $e->getMessage();
    }
}

// Get categories for dropdown
try {
    $categories_query = "SELECT * FROM categories ORDER BY name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error loading categories: " . $e->getMessage();
}

// Get all categories for management
try {
    $all_categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                            FROM categories c 
                            LEFT JOIN products p ON c.id = p.category_id 
                            GROUP BY c.id 
                            ORDER BY c.name";
    $all_categories_stmt = $db->prepare($all_categories_query);
    $all_categories_stmt->execute();
    $all_categories = $all_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error loading categories: " . $e->getMessage();
}

// Get product for editing
if ($action == 'edit' && isset($_GET['id'])) {
    try {
        $query = "SELECT * FROM products WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $message = "Product not found!";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $message = "Error loading product: " . $e->getMessage();
        $action = 'list';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - TSD Security</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Complete Admin CSS - No dependencies on external variables */
        :root {
            --primary: #0350ff;
            --primary-dark: #0039b3;
            --secondary: #007b5e;
            --secondary-dark: #005f46;
            --dark: #333;
            --light: #fff;
            --gray: #f8f8f8;
            --gray-dark: #eee;
            --shadow: 0 5px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
            font-family: 'Montserrat', sans-serif;
        }

        .sidebar {
            width: 250px;
            background: #0350ff;
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            margin: 0;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar nav li {
            margin-bottom: 5px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .admin-header h1 {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
        }

        .admin-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0350ff;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #0039b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #007b5e;
        }

        .btn-secondary:hover {
            background: #005f46;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-danger:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .admin-tabs {
            display: flex;
            border-bottom: 2px solid #0350ff;
            margin-bottom: 30px;
        }

        .admin-tab {
            padding: 15px 30px;
            background: #f8f8f8;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .admin-tab.active {
            background: #0350ff;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0350ff;
            display: block;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .category-management {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .category-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }

        .category-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .category-info span {
            font-size: 0.9rem;
            color: #666;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .admin-form {
            max-width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0350ff;
            box-shadow: 0 0 0 2px rgba(3, 80, 255, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
        }

        .current-image {
            margin-top: 10px;
            text-align: center;
        }

        .current-image img {
            max-width: 200px;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-full {
            width: 100%;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: fixed;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .admin-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: white;
                z-index: 999;
                padding: 15px 20px;
                margin-bottom: 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .admin-tabs {
                flex-direction: column;
            }
            
            .admin-tab {
                text-align: left;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .admin-main {
                padding: 15px;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .admin-header h1 {
                font-size: 1.5rem;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .stat-box {
                padding: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
        }

        /* Additional utility classes */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .p-20 {
            padding: 20px;
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: white;
                padding: 10px;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
        }
    </style>
</head>
<body>
    <!-- Add mobile menu button for small screens -->
    <div class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="admin-dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> TSD Admin</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products & Categories</a></li>
                    <li><a href="../index.php"><i class="fas fa-home"></i> View Site</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Product & Category Management</h1>
                <div class="admin-actions">
                    <a href="products.php?action=add" class="btn"><i class="fas fa-plus"></i> Add Product</a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <div class="admin-tabs">
                    <button class="admin-tab active" onclick="switchTab('products')">Products</button>
                    <button class="admin-tab" onclick="switchTab('categories')">Categories</button>
                </div>

                <div id="products-tab" class="tab-content active">
                    <div class="quick-stats">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo count($products); ?></span>
                            <span>Total Products</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo count(array_filter($products, function($p) { return $p['featured']; })); ?></span>
                            <span>Featured Products</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo count($all_categories); ?></span>
                            <span>Categories</span>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($products) > 0): ?>
                                    <?php foreach ($products as $prod): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($prod['image'])): ?>
                                                <img src="../images/uploads/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                    <i class="fas fa-image" style="color: #999;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                        <td><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>K<?php echo number_format($prod['price'], 2); ?></td>
                                        <td><?php echo $prod['featured'] ? '<span style="color: green; font-weight: bold;">✓</span>' : '<span style="color: #ccc;">✗</span>'; ?></td>
                                        <td>
                                            <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-small">Edit</a>
                                            <a href="products.php?delete=<?php echo $prod['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <p style="margin: 0 0 15px 0; color: #666;">No products found.</p>
                                            <a href="products.php?action=add" class="btn">Add Your First Product</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="categories-tab" class="tab-content">
                    <div class="category-management">
                        <h3 style="margin-top: 0;">Manage Categories</h3>
                        <div class="categories-grid">
                            <div>
                                <h4>Add New Category</h4>
                                <form method="POST" class="admin-form">
                                    <input type="hidden" name="category_action" value="add">
                                    <div class="form-group">
                                        <label>Category Name *</label>
                                        <input type="text" name="new_category_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="new_category_description" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn">Add Category</button>
                                </form>
                            </div>
                            <div>
                                <h4>Existing Categories</h4>
                                <div class="category-list">
                                    <?php if (count($all_categories) > 0): ?>
                                        <?php foreach ($all_categories as $cat): ?>
                                        <div class="category-item">
                                            <div class="category-info">
                                                <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                                <span style="font-size: 0.9rem; color: #666;"><?php echo $cat['product_count']; ?> products</span>
                                            </div>
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="category_action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-danger" <?php echo $cat['product_count'] > 0 ? 'disabled title="Cannot delete category with products"' : ''; ?>>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="color: #666; text-align: center; padding: 20px;">No categories found. Add your first category.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <?php if ($action === 'edit' && isset($product['id'])): ?>
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Price (K) *</label>
                                <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="description" rows="4" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">-- No Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color: #666;">Or <a href="javascript:void(0)" onclick="switchTab('categories')" style="color: #0350ff;">add a new category</a></small>
                            </div>
                            
                            <div class="form-group">
                                <label>Badge</label>
                                <select name="badge">
                                    <option value="">No Badge</option>
                                    <option value="Featured" <?php echo (isset($product['badge']) && $product['badge'] == 'Featured') ? 'selected' : ''; ?>>Featured</option>
                                    <option value="Best Seller" <?php echo (isset($product['badge']) && $product['badge'] == 'Best Seller') ? 'selected' : ''; ?>>Best Seller</option>
                                    <option value="New" <?php echo (isset($product['badge']) && $product['badge'] == 'New') ? 'selected' : ''; ?>>New</option>
                                    <option value="Popular" <?php echo (isset($product['badge']) && $product['badge'] == 'Popular') ? 'selected' : ''; ?>>Popular</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Image <?php echo $action === 'add' ? '*' : ''; ?></label>
                            <input type="file" name="image" <?php echo $action === 'add' ? 'required' : ''; ?> accept="image/*">
                            <?php if ($action === 'edit' && !empty($product['image'])): ?>
                                <div class="current-image">
                                    <p style="margin-bottom: 10px; color: #666;">Current Image:</p>
                                    <img src="../images/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured" value="1" <?php echo (isset($product['featured']) && $product['featured']) ? 'checked' : ''; ?>>
                                Mark as Featured Product
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn">
                                <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                            </button>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected tab
            event.target.classList.add('active');
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const mobileBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && !mobileBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        });
    </script>
</body>
</html>