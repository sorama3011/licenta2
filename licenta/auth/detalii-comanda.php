<?php
// Start session
session_start();

// Include database configuration
require_once 'db-config.php';

// Check if user is logged in
requireLogin();

// Initialize variables
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$order_items = [];
$error = "";

// Check if order_id is provided
if ($order_id <= 0) {
    $error = "ID-ul comenzii lipsește sau este invalid.";
} else {
    try {
        // Get order details
        $stmt = $conn->prepare("
            SELECT c.*, u.nume, u.email, u.telefon, u.adresa, u.oras, u.judet, u.cod_postal,
                   v.cod AS voucher_cod, v.valoare AS voucher_valoare, v.tip AS voucher_tip
            FROM comenzi c
            LEFT JOIN utilizatori u ON c.id_utilizator = u.id
            LEFT JOIN vouchere v ON c.id_voucher = v.id
            WHERE c.id = :id AND c.id_utilizator = :id_utilizator
        ");
        $stmt->bindParam(":id", $order_id);
        $stmt->bindParam(":id_utilizator", $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get order items
            $items_stmt = $conn->prepare("
                SELECT cp.*, p.imagine
                FROM comenzi_produse cp
                LEFT JOIN produse p ON cp.id_produs = p.id
                WHERE cp.id_comanda = :id_comanda
            ");
            $items_stmt->bindParam(":id_comanda", $order_id);
            $items_stmt->execute();
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Comanda nu a fost găsită sau nu aveți acces la această comandă.";
        }
    } catch(PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
}

// Escape output
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
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
    <title>Detalii Comandă - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .order-header {
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
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #8B0000;
            border: 3px solid #fff;
            box-shadow: 0 0 0 1px #8B0000;
        }
        .timeline-item.inactive::before {
            background: #adb5bd;
            box-shadow: 0 0 0 1px #adb5bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo escape($error); ?>
            </div>
            <div class="text-center mt-4">
                <a href="account.html" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Înapoi la Contul Meu
                </a>
            </div>
        <?php elseif ($order): ?>
            <div class="order-header">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3">Comanda #<?php echo escape($order['numar_comanda']); ?></h1>
                            <p class="mb-0">Plasată pe <?php echo formatDate($order['data_plasare']); ?></p>
                        </div>
                        <div>
                            <span class="badge <?php echo getStatusBadgeClass($order['status']); ?> fs-6">
                                <?php echo escape($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Order Details -->
                <div class="col-lg-8">
                    <!-- Products -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Produse Comandate</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($order_items) > 0): ?>
                                <?php foreach ($order_items as $item): ?>
                                    <div class="d-flex mb-3 pb-3 border-bottom">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($item['imagine'])): ?>
                                                <img src="<?php echo escape($item['imagine']); ?>" alt="<?php echo escape($item['denumire_produs']); ?>" class="product-img">
                                            <?php else: ?>
                                                <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-box text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1"><?php echo escape($item['denumire_produs']); ?></h6>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <p class="mb-0 text-muted">
                                                        <?php echo escape($item['pret_unitar']); ?> RON x <?php echo escape($item['cantitate']); ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <strong><?php echo number_format($item['subtotal'], 2); ?> RON</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Nu există produse în această comandă.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Status Timeline -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Statusul Comenzii</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php
                                $statuses = ['Plasată', 'Confirmată', 'În procesare', 'Expediată', 'Livrată'];
                                $currentStatusIndex = array_search($order['status'], $statuses);
                                if ($currentStatusIndex === false) $currentStatusIndex = -1; // For 'Anulată'
                                
                                foreach ($statuses as $index => $status):
                                    $isActive = $index <= $currentStatusIndex;
                                    $date = null;
                                    
                                    switch($status) {
                                        case 'Plasată': $date = $order['data_plasare']; break;
                                        case 'Confirmată': $date = $order['data_confirmare']; break;
                                        case 'Expediată': $date = $order['data_expediere']; break;
                                        case 'Livrată': $date = $order['data_livrare']; break;
                                    }
                                ?>
                                    <div class="timeline-item <?php echo $isActive ? '' : 'inactive'; ?>">
                                        <h6><?php echo $status; ?></h6>
                                        <?php if ($isActive && $date): ?>
                                            <p class="text-muted mb-0"><?php echo formatDate($date); ?></p>
                                        <?php elseif ($isActive): ?>
                                            <p class="text-muted mb-0">În curs</p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">În așteptare</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ($order['status'] === 'Anulată'): ?>
                                    <div class="timeline-item">
                                        <h6 class="text-danger">Comandă Anulată</h6>
                                        <p class="text-muted mb-0">Comanda a fost anulată</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($order['status'] === 'Expediată' && !empty($order['numar_awb'])): ?>
                                <div class="alert alert-info mt-3">
                                    <strong>Număr AWB:</strong> <?php echo escape($order['numar_awb']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <!-- Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Sumar Comandă</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($order['subtotal'], 2); ?> RON</span>
                            </div>
                            
                            <?php if ($order['valoare_reducere'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Reducere<?php echo !empty($order['voucher_cod']) ? ' (' . escape($order['voucher_cod']) . ')' : ''; ?>:</span>
                                    <span>-<?php echo number_format($order['valoare_reducere'], 2); ?> RON</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Transport:</span>
                                <span>
                                    <?php if ($order['cost_transport'] > 0): ?>
                                        <?php echo number_format($order['cost_transport'], 2); ?> RON
                                    <?php else: ?>
                                        <span class="text-success">Gratuit</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span><?php echo number_format($order['total'], 2); ?> RON</span>
                            </div>
                            
                            <div class="mt-3">
                                <p class="mb-1"><strong>Metodă de plată:</strong> <?php echo escape($order['metoda_plata']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Details -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Detalii Livrare</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Nume:</strong> <?php echo escape($order['nume_livrare']); ?></p>
                            <p class="mb-1"><strong>Telefon:</strong> <?php echo escape($order['telefon_livrare']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo escape($order['email_livrare']); ?></p>
                            <p class="mb-1"><strong>Adresa:</strong> <?php echo escape($order['adresa_livrare']); ?></p>
                            <p class="mb-1"><strong>Oraș:</strong> <?php echo escape($order['oras_livrare']); ?></p>
                            <p class="mb-1"><strong>Județ:</strong> <?php echo escape($order['judet_livrare']); ?></p>
                            <p class="mb-0"><strong>Cod poștal:</strong> <?php echo escape($order['cod_postal_livrare']); ?></p>
                            
                            <?php if (!empty($order['observatii'])): ?>
                                <hr>
                                <p class="mb-0"><strong>Observații:</strong> <?php echo escape($order['observatii']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-grid gap-2 mt-4">
                        <a href="account.html" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Înapoi la Contul Meu
                        </a>
                        <?php if (in_array($order['status'], ['Plasată', 'Confirmată'])): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmCancel(<?php echo $order_id; ?>)">
                                <i class="bi bi-x-circle"></i> Anulează Comanda
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancel(orderId) {
            if (confirm('Ești sigur că vrei să anulezi această comandă?')) {
                window.location.href = 'cancel-order.php?id=' + orderId;
            }
        }
    </script>
</body>
</html>