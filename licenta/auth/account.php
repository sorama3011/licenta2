<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$host = "localhost";
$dbname = "proiect_licenta";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Conexiunea la baza de date a eșuat: " . $e->getMessage());
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM utilizatori WHERE id = :id");
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's orders
$orders_stmt = $conn->prepare("
    SELECT o.id, o.numar_comanda, o.data_plasare, o.status, o.total, 
           COUNT(op.id) AS numar_produse
    FROM comenzi o
    JOIN comenzi_produse op ON o.id = op.id_comanda
    WHERE o.id_utilizator = :id_utilizator
    GROUP BY o.id
    ORDER BY o.data_plasare DESC
    LIMIT 5
");
$orders_stmt->bindParam(":id_utilizator", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get loyalty points
$points_stmt = $conn->prepare("
    SELECT puncte_totale, puncte_folosite, 
           (puncte_totale - puncte_folosite) AS puncte_disponibile
    FROM puncte_fidelitate
    WHERE id_utilizator = :id_utilizator
");
$points_stmt->bindParam(":id_utilizator", $user_id);
$points_stmt->execute();
$points = $points_stmt->fetch(PDO::FETCH_ASSOC);

if (!$points) {
    $points = [
        'puncte_totale' => 0,
        'puncte_folosite' => 0,
        'puncte_disponibile' => 0
    ];
}

// Escape output
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contul Meu - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .account-header {
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
        .loyalty-card {
            background: linear-gradient(135deg, #8B0000, #DAA520);
            color: white;
        }
        .nav-pills .nav-link.active {
            background-color: #8B0000;
        }
        .nav-pills .nav-link {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="account-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3">Bun venit, <?php echo escape($user['nume']); ?>!</h1>
                        <p class="mb-0">Contul tău Gusturi Românești</p>
                    </div>
                    <div>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="bi bi-box-arrow-right"></i> Deconectare
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="mt-3">
                                <h4><?php echo escape($user['nume']); ?></h4>
                                <p class="text-muted mb-1"><?php echo escape($user['email']); ?></p>
                                <p class="text-muted mb-1">
                                    <span class="badge bg-primary"><?php echo escape($user['tip']); ?></span>
                                </p>
                                <p class="text-muted mb-1">Membru din <?php echo date('F Y', strtotime($user['data_inregistrare'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="list-group mt-4">
                    <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-bag me-2"></i> Comenzile Mele
                    </a>
                    <a href="#profile" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-person me-2"></i> Profilul Meu
                    </a>
                    <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-geo-alt me-2"></i> Adresele Mele
                    </a>
                    <a href="#wishlist" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-heart me-2"></i> Lista de Dorințe
                    </a>
                    <a href="#reviews" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="bi bi-star me-2"></i> Recenziile Mele
                    </a>
                </div>
                
                <div class="card mt-4 loyalty-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Puncte de Fidelitate</h5>
                        <h2 class="display-4"><?php echo $points['puncte_disponibile']; ?></h2>
                        <p class="card-text">Puncte disponibile</p>
                        <p class="card-text small">Total acumulat: <?php echo $points['puncte_totale']; ?> puncte</p>
                        <a href="#" class="btn btn-light btn-sm">Folosește punctele</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Dashboard</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card text-center mb-3">
                                            <div class="card-body">
                                                <h1 class="display-4"><?php echo count($orders); ?></h1>
                                                <p class="card-text">Comenzi</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-center mb-3">
                                            <div class="card-body">
                                                <h1 class="display-4"><?php echo $points['puncte_disponibile']; ?></h1>
                                                <p class="card-text">Puncte</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-center mb-3">
                                            <div class="card-body">
                                                <h1 class="display-4">0</h1>
                                                <p class="card-text">Recenzii</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h5 class="mt-4">Comenzi Recente</h5>
                                <?php if (count($orders) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Comandă</th>
                                                    <th>Data</th>
                                                    <th>Status</th>
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
                                                            <a href="#" class="btn btn-sm btn-outline-primary">Detalii</a>
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
                                
                                <div class="text-center mt-3">
                                    <a href="#orders" class="btn btn-primary" data-bs-toggle="list">Vezi toate comenzile</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders -->
                    <div class="tab-pane fade" id="orders">
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
                                                        <td><?php echo escape($order['numar_produse']); ?></td>
                                                        <td><?php echo number_format($order['total'], 2); ?> RON</td>
                                                        <td>
                                                            <a href="#" class="btn btn-sm btn-outline-primary">Detalii</a>
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
                    </div>
                    
                    <!-- Profile -->
                    <div class="tab-pane fade" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profilul Meu</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nume" class="form-label">Nume</label>
                                            <input type="text" class="form-control" id="nume" value="<?php echo escape($user['nume']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo escape($user['email']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="telefon" class="form-label">Telefon</label>
                                            <input type="tel" class="form-control" id="telefon" value="<?php echo escape($user['telefon']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="data_inregistrare" class="form-label">Data înregistrării</label>
                                            <input type="text" class="form-control" id="data_inregistrare" value="<?php echo date('d.m.Y', strtotime($user['data_inregistrare'])); ?>" readonly>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Salvează modificările</button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5>Schimbă parola</h5>
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="parola_curenta" class="form-label">Parola curentă</label>
                                            <input type="password" class="form-control" id="parola_curenta">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="parola_noua" class="form-label">Parola nouă</label>
                                            <input type="password" class="form-control" id="parola_noua">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="parola_confirmare" class="form-label">Confirmă parola nouă</label>
                                            <input type="password" class="form-control" id="parola_confirmare">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Schimbă parola</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Addresses -->
                    <div class="tab-pane fade" id="addresses">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Adresele Mele</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Adresa principală</h6>
                                                <p class="card-text">
                                                    <?php echo !empty($user['adresa']) ? escape($user['adresa']) : 'Nicio adresă salvată'; ?>
                                                    <br>
                                                    <?php if (!empty($user['oras']) && !empty($user['judet'])): ?>
                                                        <?php echo escape($user['oras']); ?>, <?php echo escape($user['judet']); ?>
                                                        <br>
                                                        <?php echo !empty($user['cod_postal']) ? escape($user['cod_postal']) : ''; ?>
                                                    <?php endif; ?>
                                                </p>
                                                <div class="mt-3">
                                                    <a href="#" class="btn btn-sm btn-outline-primary">Editează</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3 border-dashed">
                                            <div class="card-body text-center">
                                                <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                                                <h6 class="card-title mt-3">Adaugă o adresă nouă</h6>
                                                <a href="#" class="btn btn-sm btn-outline-primary mt-2">Adaugă</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wishlist -->
                    <div class="tab-pane fade" id="wishlist">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Lista de Dorințe</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    Nu ai produse în lista de dorințe.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews -->
                    <div class="tab-pane fade" id="reviews">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recenziile Mele</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    Nu ai scris încă nicio recenzie.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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