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
$email = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST["email"]);
    
    // Validate form data
    if (empty($email)) {
        $error = "Te rugăm să introduci adresa de email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email AND status = 'activ'");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Generate reset code
            $reset_code = md5(uniqid(rand(), true));
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update user with reset code
            $update_stmt = $conn->prepare("UPDATE utilizatori SET cod_parola = :cod_parola, data_expirare_token = :data_expirare WHERE email = :email");
            $update_stmt->bindParam(":cod_parola", $reset_code);
            $update_stmt->bindParam(":data_expirare", $expiry_time);
            $update_stmt->bindParam(":email", $email);
            
            if ($update_stmt->execute()) {
                // In a real application, send email with reset link
                // For this example, we'll just show the reset code
                $reset_link = "reset-confirm.php?email=" . urlencode($email) . "&code=" . $reset_code;
                
                $success = "Un email cu instrucțiuni pentru resetarea parolei a fost trimis la adresa ta de email.";
                
                // For demonstration purposes only - in a real app, this would be sent via email
                $success .= "<br><br><strong>Link de resetare (doar pentru demonstrație):</strong><br>";
                $success .= "<a href='$reset_link'>$reset_link</a>";
                
                // Reset form field
                $email = "";
            } else {
                $error = "A apărut o eroare la procesarea cererii. Încearcă din nou.";
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = "Dacă adresa de email există în baza noastră de date, vei primi instrucțiuni pentru resetarea parolei.";
            $email = "";
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
                    <p class="mb-0">Introdu adresa de email pentru a reseta parola</p>
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
                    <?php else: ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Trimite link de resetare</button>
                            
                            <div class="text-center mt-3">
                                <p><a href="login.php">Înapoi la autentificare</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
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