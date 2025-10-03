<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get counts for dashboard
$product_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$category_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$featured_count = $db->query("SELECT COUNT(*) FROM products WHERE featured = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSD Security - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
        }
        .sidebar {
            width: 250px;
            background: var(--primary);
            color: var(--light);
            padding: 20px 0;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar nav ul {
            list-style: none;
            padding: 0;
        }
        .sidebar nav a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(255,255,255,0.1);
        }
        .admin-main {
            flex: 1;
            padding: 20px;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--light);
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> TSD Admin</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="../index.php"><i class="fas fa-home"></i> View Site</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Dashboard Overview</h1>
                <div class="admin-user">
                    Welcome, <?php echo $_SESSION['admin_username']; ?>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $product_count; ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $category_count; ?></h3>
                    <p>Categories</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $featured_count; ?></h3>
                    <p>Featured Products</p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="products.php?action=add" class="btn"><i class="fas fa-plus"></i> Add New Product</a>
                <a href="categories.php?action=add" class="btn btn-secondary"><i class="fas fa-folder-plus"></i> Add Category</a>
            </div>
        </main>
    </div>
</body>
</html>