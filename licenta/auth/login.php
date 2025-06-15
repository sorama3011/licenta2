<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
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

// Initialize variables
$email = "";
$error = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST["email"]);
    $parola = trim($_POST["parola"]);
    
    // Validate form data
    if (empty($email) || empty($parola)) {
        $error = "Te rugăm să completezi toate câmpurile.";
    } else {
        // Check credentials
        $stmt = $conn->prepare("SELECT id, nume, parola, tip, status FROM utilizatori WHERE email = :email");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account is active
            if ($user['status'] !== 'activ') {
                $error = "Acest cont este inactiv. Te rugăm să contactezi administratorul.";
            } else {
                // Verify password (using MD5 as specified)
                $hashed_password = md5($parola);
                
                if ($hashed_password === $user['parola']) {
                    // Start session and store user data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nume'];
                    $_SESSION['user_type'] = $user['tip'];
                    
                    // Update last login time
                    $update_stmt = $conn->prepare("UPDATE utilizatori SET ultima_autentificare = NOW() WHERE id = :id");
                    $update_stmt->bindParam(":id", $user['id']);
                    $update_stmt->execute();
                    
                    // Log login
                    $log_stmt = $conn->prepare("INSERT INTO jurnalizare (id_utilizator, actiune, detalii, ip) VALUES (:id_utilizator, 'login', 'Autentificare reușită', :ip)");
                    $log_stmt->bindParam(":id_utilizator", $user['id']);
                    $log_stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
                    $log_stmt->execute();
                    
                    // Redirect to account page
                    header("Location: ../index.html");
                    exit;
                } else {
                    $error = "Email sau parolă incorectă.";
                }
            }
        } else {
            $error = "Email sau parolă incorectă.";
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
    <title>Autentificare - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signin {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
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
        <div class="form-signin">
            <div class="card">
                <div class="card-header text-center py-3">
                    <h3 class="mb-0">Autentificare</h3>
                    <p class="mb-0">Intră în contul tău Gusturi Românești</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo escape($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parola" class="form-label">Parola</label>
                            <input type="password" class="form-control" id="parola" name="parola" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ține-mă minte</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Autentificare</button>
                        
                        <div class="text-center mt-3">
                            <p><a href="reset-password.php">Ai uitat parola?</a></p>
                            <p>Nu ai cont? <a href="signup.php">Înregistrează-te</a></p>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="../index.html" class="text-decoration-none">&larr; Înapoi la pagina principală</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>