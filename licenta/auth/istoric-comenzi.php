<?php
// Start session
session_start();

// Include database configuration
require_once 'db-config.php';

// Check if user is logged in
requireLogin();

// Get user orders
try {
    $stmt = $conn->prepare("
        SELECT o.id, o.numar_comanda, o.data_plasare, o.status, o.total, 
               COUNT(op.id) AS numar_produse
        FROM comenzi o
        JOIN comenzi_produse op ON o.id = op.id_comanda
        WHERE o.id_utilizator = :id_utilizator
        GROUP BY o.id
        ORDER BY o.data_plasare DESC
    ");
    $stmt->bindParam(":id_utilizator", $_SESSION['user_id']);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Eroare de bază de date: " . $e->getMessage();
    $orders = [];
}

// Escape output
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Plasată': return 'bg-info';
        case 'Confirmată': return 'bg-primary';
        case 'În procesare': return 'bg-warning';
        case 'Expediată': return 'bg-success';
        case 'Livrată': return 'bg-success';
        case 'Anulată': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Istoric Comenzi - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .page-header {
            background-color: #8B0000;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 10px;
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
        .btn-primary {
            background-color: #8B0000;
            border-color: #8B0000;
        }
        .btn-primary:hover {
            background-color: #6B0000;
            border-color: #6B0000;
        }
        .btn-outline-primary {
            color: #8B0000;
            border-color: #8B0000;
        }
        .btn-outline-primary:hover {
            background-color: #8B0000;
            border-color: #8B0000;
        }
        .btn-reorder {
            background-color: #8B0000;
            border-color: #8B0000;
            color: white;
        }
        .btn-reorder:hover {
            background-color: #6B0000;
            border-color: #6B0000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo escape($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo escape($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3">Istoric Comenzi</h1>
                        <p class="mb-0">Toate comenzile tale Gusturi Românești</p>
                    </div>
                    <div>
                        <a href="account.html" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left"></i> Înapoi la Cont
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Comenzile Mele</h5>
            </div>
            <div class="card-body">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Comandă</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Produse</th>
                                    <th>Total</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo escape($order['numar_comanda']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($order['data_plasare'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                                <?php echo escape($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escape($order['numar_produse']); ?></td>
                                        <td><?php echo number_format($order['total'], 2); ?> RON</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="detalii-comanda.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    Detalii
                                                </a>
                                                <a href="reorder.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-reorder">
                                                    <i class="bi bi-arrow-repeat"></i> Comandă Din Nou
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Nu ai plasat încă nicio comandă.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="../index.html" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Înapoi la magazin
            </a>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>