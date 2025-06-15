<?php
// Database connection
$host = "localhost";
$dbname = "gusturi_romanesti";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Conexiunea la baza de date a eșuat: " . $e->getMessage());
}

// Initialize variables
$email = isset($_GET['email']) ? trim($_GET['email']) : "";
$code = isset($_GET['code']) ? trim($_GET['code']) : "";
$error = "";
$success = "";
$valid_token = false;

// Validate token
if (!empty($email) && !empty($code)) {
    $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email AND cod_parola = :cod_parola AND data_expirare_token > NOW() AND status = 'activ'");
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":cod_parola", $code);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $valid_token = true;
    } else {
        $error = "Link-ul de resetare este invalid sau a expirat.";
    }
} else {
    $error = "Link-ul de resetare este invalid.";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    // Get form data
    $parola = trim($_POST["parola"]);
    $parola_confirmare = trim($_POST["parola_confirmare"]);
    
    // Validate form data
    if (empty($parola) || empty($parola_confirmare)) {
        $error = "Te rugăm să completezi toate câmpurile.";
    } elseif ($parola !== $parola_confirmare) {
        $error = "Parolele nu se potrivesc.";
    } elseif (strlen($parola) < 6) {
        $error = "Parola trebuie să aibă cel puțin 6 caractere.";
    } else {
        // Hash password
        $parola_hash = md5($parola);
        
        // Update user password
        $stmt = $conn->prepare("UPDATE utilizatori SET parola = :parola, cod_parola = NULL, data_expirare_token = NULL WHERE email = :email AND cod_parola = :cod_parola");
        $stmt->bindParam(":parola", $parola_hash);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":cod_parola", $code);
        
        if ($stmt->execute()) {
            $success = "Parola a fost resetată cu succes! Acum te poți <a href='login.php'>autentifica</a> cu noua parolă.";
            $valid_token = false; // Hide form after successful reset
        } else {
            $error = "A apărut o eroare la resetarea parolei. Încearcă din nou.";
        }
    }
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
    <title>Resetare Parolă - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-reset {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #8B0000;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #8B0000;
            border-color: #8B0000;
        }
        .btn-primary:hover {
            background-color: #6B0000;
            border-color: #6B0000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-reset">
            <div class="card">
                <div class="card-header text-center py-3">
                    <h3 class="mb-0">Resetare Parolă</h3>
                    <p class="mb-0">Setează o parolă nouă pentru contul tău</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo escape($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($valid_token): ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?email=" . urlencode($email) . "&code=" . urlencode($code)); ?>">
                            <div class="mb-3">
                                <label for="parola" class="form-label">Parolă nouă</label>
                                <input type="password" class="form-control" id="parola" name="parola" required>
                                <div class="form-text">Parola trebuie să aibă minim 6 caractere.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="parola_confirmare" class="form-label">Confirmă parola nouă</label>
                                <input type="password" class="form-control" id="parola_confirmare" name="parola_confirmare" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Resetează parola</button>
                        </form>
                    <?php elseif (empty($success)): ?>
                        <div class="text-center">
                            <p>Link-ul de resetare este invalid sau a expirat.</p>
                            <a href="reset-password.php" class="btn btn-primary mt-3">Solicită un nou link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">Înapoi la autentificare</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>