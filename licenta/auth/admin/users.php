<?php
// Start session
session_start();

// Include database configuration
require_once '../db-config.php';

// Check if user is logged in and is admin
requireAdmin();

// Initialize variables
$users = [];
$error = "";
$success = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = [];

// Process actions
if ($action === 'view' || $action === 'edit') {
    // Get user details
    try {
        $stmt = $conn->prepare("SELECT * FROM utilizatori WHERE id = :id");
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Utilizatorul nu a fost găsit.";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
        $action = 'list';
    }
} elseif ($action === 'update' && $_SERVER["REQUEST_METHOD"] == "POST") {
    // Update user
    try {
        $nume = trim($_POST['nume']);
        $email = trim($_POST['email']);
        $telefon = trim($_POST['telefon']);
        $adresa = trim($_POST['adresa']);
        $oras = trim($_POST['oras']);
        $judet = trim($_POST['judet']);
        $cod_postal = trim($_POST['cod_postal']);
        $status = $_POST['status'];
        $rol = $_POST['rol'];
        
        // Validate required fields
        if (empty($nume) || empty($email)) {
            $error = "Numele și email-ul sunt obligatorii.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresa de email nu este validă.";
        } else {
            // Check if email is already used by another user
            $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email AND id != :id");
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":id", $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Această adresă de email este deja folosită de alt utilizator.";
            } else {
                // Update user
                $stmt = $conn->prepare("
                    UPDATE utilizatori SET 
                        nume = :nume,
                        email = :email,
                        telefon = :telefon,
                        adresa = :adresa,
                        oras = :oras,
                        judet = :judet,
                        cod_postal = :cod_postal,
                        status = :status,
                        tip = :tip
                    WHERE id = :id
                ");
                $stmt->bindParam(":nume", $nume);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":telefon", $telefon);
                $stmt->bindParam(":adresa", $adresa);
                $stmt->bindParam(":oras", $oras);
                $stmt->bindParam(":judet", $judet);
                $stmt->bindParam(":cod_postal", $cod_postal);
                $stmt->bindParam(":status", $status);
                $stmt->bindParam(":tip", $rol);
                $stmt->bindParam(":id", $user_id);
                
                if ($stmt->execute()) {
                    $success = "Utilizatorul a fost actualizat cu succes!";
                    
                    // Log the action
                    logAction($conn, 'user_update', "Actualizare utilizator ID: $user_id");
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM utilizatori WHERE id = :id");
                    $stmt->bindParam(":id", $user_id);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "A apărut o eroare la actualizarea utilizatorului.";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    // Delete user
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, nume FROM utilizatori WHERE id = :id");
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Don't allow deleting the current admin
            if ($user_id == $_SESSION['user_id']) {
                $error = "Nu poți șterge propriul cont de administrator.";
            } else {
                // Delete user
                $stmt = $conn->prepare("DELETE FROM utilizatori WHERE id = :id");
                $stmt->bindParam(":id", $user_id);
                
                if ($stmt->execute()) {
                    $success = "Utilizatorul a fost șters cu succes!";
                    
                    // Log the action
                    logAction($conn, 'user_delete', "Ștergere utilizator ID: $user_id, Nume: " . $user['nume']);
                } else {
                    $error = "A apărut o eroare la ștergerea utilizatorului.";
                }
            }
        } else {
            $error = "Utilizatorul nu a fost găsit.";
        }
    } catch (PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
    
    // Redirect to list after delete
    $action = 'list';
}

// Get users list for main view
if ($action === 'list') {
    try {
        $stmt = $conn->query("
            SELECT id, nume, email, telefon, tip, status, data_inregistrare, ultima_autentificare
            FROM utilizatori
            ORDER BY data_inregistrare DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare Utilizatori - Gusturi Românești</title>
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
                            <a class="nav-link" href="index.php">
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
                            <a class="nav-link active" href="users.php">
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
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Administrare Utilizatori</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo escape($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo escape($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <!-- Users List -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Lista Utilizatorilor</h5>
                                <a href="users.php?action=add" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Adaugă Utilizator
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nume</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>Rol</th>
                                            <th>Status</th>
                                            <th>Data Înregistrării</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td><?php echo escape($u['id']); ?></td>
                                                <td><?php echo escape($u['nume']); ?></td>
                                                <td><?php echo escape($u['email']); ?></td>
                                                <td><?php echo escape($u['telefon'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($u['tip'] === 'Administrator'): ?>
                                                        <span class="badge bg-danger">Administrator</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary">Client</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($u['status'] === 'activ'): ?>
                                                        <span class="badge bg-success">Activ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactiv</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d.m.Y', strtotime($u['data_inregistrare'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="users.php?action=view&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                            <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ești sigur că vrei să ștergi acest utilizator?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <!-- View User -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Detalii Utilizator</h5>
                                <div>
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> Editează
                                    </a>
                                    <a href="users.php" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-arrow-left"></i> Înapoi
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informații Personale</h6>
                                    <table class="table">
                                        <tr>
                                            <th>ID:</th>
                                            <td><?php echo escape($user['id']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Nume:</th>
                                            <td><?php echo escape($user['nume']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?php echo escape($user['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Telefon:</th>
                                            <td><?php echo escape($user['telefon'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Adresa:</th>
                                            <td><?php echo escape($user['adresa'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Oraș:</th>
                                            <td><?php echo escape($user['oras'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Județ:</th>
                                            <td><?php echo escape($user['judet'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Cod Poștal:</th>
                                            <td><?php echo escape($user['cod_postal'] ?? 'N/A'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Informații Cont</h6>
                                    <table class="table">
                                        <tr>
                                            <th>Rol:</th>
                                            <td>
                                                <?php if ($user['tip'] === 'Administrator'): ?>
                                                    <span class="badge bg-danger">Administrator</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Client</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>
                                                <?php if ($user['status'] === 'activ'): ?>
                                                    <span class="badge bg-success">Activ</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiv</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Data Înregistrării:</th>
                                            <td><?php echo date('d.m.Y H:i', strtotime($user['data_inregistrare'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Ultima Autentificare:</th>
                                            <td>
                                                <?php echo $user['ultima_autentificare'] ? date('d.m.Y H:i', strtotime($user['ultima_autentificare'])) : 'Niciodată'; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'edit'): ?>
                    <!-- Edit User -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Editare Utilizator</h5>
                                <a href="users.php" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-arrow-left"></i> Înapoi
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="users.php?action=update&id=<?php echo $user['id']; ?>" method="post">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nume" class="form-label">Nume *</label>
                                        <input type="text" class="form-control" id="nume" name="nume" value="<?php echo escape($user['nume']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefon" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo escape($user['telefon'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="activ" <?php echo $user['status'] === 'activ' ? 'selected' : ''; ?>>Activ</option>
                                            <option value="inactiv" <?php echo $user['status'] === 'inactiv' ? 'selected' : ''; ?>>Inactiv</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="rol" class="form-label">Rol *</label>
                                        <select class="form-select" id="rol" name="rol" required>
                                            <option value="Client" <?php echo $user['tip'] === 'Client' ? 'selected' : ''; ?>>Client</option>
                                            <option value="Administrator" <?php echo $user['tip'] === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="adresa" class="form-label">Adresa</label>
                                    <textarea class="form-control" id="adresa" name="adresa" rows="2"><?php echo escape($user['adresa'] ?? ''); ?></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="oras" class="form-label">Oraș</label>
                                        <input type="text" class="form-control" id="oras" name="oras" value="<?php echo escape($user['oras'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="judet" class="form-label">Județ</label>
                                        <input type="text" class="form-control" id="judet" name="judet" value="<?php echo escape($user['judet'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cod_postal" class="form-label">Cod Poștal</label>
                                        <input type="text" class="form-control" id="cod_postal" name="cod_postal" value="<?php echo escape($user['cod_postal'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="users.php" class="btn btn-secondary">Anulează</a>
                                    <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>