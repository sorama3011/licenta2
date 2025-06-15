<?php
// Start session
session_start();

// Include database configuration
require_once '../db-config.php';

// Check if user is logged in and is admin
requireAdmin();

// Get dashboard statistics
try {
    // Total users
    $users_stmt = $conn->query("SELECT COUNT(*) AS total FROM utilizatori WHERE tip = 'Client'");
    $total_users = $users_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total orders
    $orders_stmt = $conn->query("SELECT COUNT(*) AS total FROM comenzi");
    $total_orders = $orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total products
    $products_stmt = $conn->query("SELECT COUNT(*) AS total FROM produse");
    $total_products = $products_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $revenue_stmt = $conn->query("SELECT SUM(total) AS total FROM comenzi WHERE status != 'Anulată'");
    $total_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Recent orders
    $recent_orders_stmt = $conn->query("
        SELECT o.id, o.numar_comanda, o.data_plasare, o.status, o.total,
               CONCAT(u.nume) AS client
        FROM comenzi o
        JOIN utilizatori u ON o.id_utilizator = u.id
        ORDER BY o.data_plasare DESC
        LIMIT 5
    ");
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock products
    $low_stock_stmt = $conn->query("
        SELECT id, cod_produs, denumire, stoc
        FROM produse
        WHERE stoc < 5 AND activ = TRUE
        ORDER BY stoc ASC
        LIMIT 5
    ");
    $low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Eroare de bază de date: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #d9d9d9;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #8B0000;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: #8B0000;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.125);
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.primary {
            border-left-color: #8B0000;
        }
        .stat-card.success {
            border-left-color: #28a745;
        }
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        .stat-card.info {
            border-left-color: #17a2b8;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">Gusturi Românești Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="../logout.php">Deconectare</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="bi bi-box-seam"></i>
                                Produse
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="bi bi-tags"></i>
                                Categorii
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="bi bi-bag"></i>
                                Comenzi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i>
                                Utilizatori
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="vouchers.php">
                                <i class="bi bi-ticket-perforated"></i>
                                Vouchere
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reviews.php">
                                <i class="bi bi-star"></i>
                                Recenzii
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-graph-up"></i>
                                Rapoarte
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i>
                                Setări
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Acțiuni rapide</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="products.php?action=add">
                                <i class="bi bi-plus-circle"></i>
                                Adaugă produs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="vouchers.php?action=add">
                                <i class="bi bi-plus-circle"></i>
                                Adaugă voucher
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="bi bi-calendar"></i>
                            Astăzi
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo escape($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="text-muted">Total Utilizatori</div>
                                        <h3><?php echo number_format($total_users); ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="text-muted">Total Comenzi</div>
                                        <h3><?php echo number_format($total_orders); ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-bag stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="text-muted">Total Produse</div>
                                        <h3><?php echo number_format($total_products); ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-box-seam stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card info">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="text-muted">Venituri Totale</div>
                                        <h3><?php echo number_format($total_revenue, 2); ?> RON</h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-currency-exchange stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Comenzi Recente</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_orders) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Comandă</th>
                                                    <th>Client</th>
                                                    <th>Data</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th>Acțiuni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><?php echo escape($order['numar_comanda']); ?></td>
                                                        <td><?php echo escape($order['client']); ?></td>
                                                        <td><?php echo date('d.m.Y', strtotime($order['data_plasare'])); ?></td>
                                                        <td>
                                                            <?php 
                                                                $status_class = '';
                                                                switch($order['status']) {
                                                                    case 'Plasată': $status_class = 'bg-info'; break;
                                                                    case 'Confirmată': $status_class = 'bg-primary'; break;
                                                                    case 'În procesare': $status_class = 'bg-warning'; break;
                                                                    case 'Expediată': $status_class = 'bg-success'; break;
                                                                    case 'Livrată': $status_class = 'bg-success'; break;
                                                                    case 'Anulată': $status_class = 'bg-danger'; break;
                                                                }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo escape($order['status']); ?></span>
                                                        </td>
                                                        <td><?php echo number_format($order['total'], 2); ?> RON</td>
                                                        <td>
                                                            <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">Detalii</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end">
                                        <a href="orders.php" class="btn btn-sm btn-primary">Vezi toate comenzile</a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Nu există comenzi recente.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Products -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Produse cu Stoc Limitat</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($low_stock_products) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Produs</th>
                                                    <th>Stoc</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($low_stock_products as $product): ?>
                                                    <tr>
                                                        <td><?php echo escape($product['denumire']); ?></td>
                                                        <td>
                                                            <?php if ($product['stoc'] == 0): ?>
                                                                <span class="badge bg-danger">Stoc epuizat</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark"><?php echo $product['stoc']; ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end">
                                        <a href="products.php?filter=low_stock" class="btn btn-sm btn-primary">Vezi toate produsele</a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        Toate produsele au stoc suficient.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>