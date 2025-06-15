<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$nume = $prenume = $email = $parola = $parola_confirmare = $adresa = $oras = $judet = $cod_postal = $telefon = "";
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $nume = trim($_POST["nume"]);
    $prenume = trim($_POST["prenume"]);
    $email = trim($_POST["email"]);
    $parola = trim($_POST["parola"]);
    $parola_confirmare = trim($_POST["parola_confirmare"]);
    $adresa = trim($_POST["adresa"]);
    $oras = trim($_POST["oras"]);
    $judet = trim($_POST["judet"]);
    $cod_postal = trim($_POST["cod_postal"]);
    $telefon = trim($_POST["telefon"]);
    
    // Validate form data
    if (empty($nume) || empty($prenume) || empty($email) || empty($parola) || empty($parola_confirmare)) {
        $error = "Toate câmpurile obligatorii trebuie completate.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
    } elseif ($parola !== $parola_confirmare) {
        $error = "Parolele nu se potrivesc.";
    } elseif (strlen($parola) < 6) {
        $error = "Parola trebuie să aibă cel puțin 6 caractere.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Această adresă de email este deja înregistrată.";
        } else {
            // Hash password with bcrypt
            $parola_hash = password_hash($parola, PASSWORD_DEFAULT);
            
            try {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO utilizatori (nume, prenume, email, parola, adresa, oras, judet, cod_postal, telefon, rol, activ) VALUES (:nume, :prenume, :email, :parola, :adresa, :oras, :judet, :cod_postal, :telefon, 'Client', TRUE)");
                
                $stmt->bindParam(":nume", $nume);
                $stmt->bindParam(":prenume", $prenume);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":parola", $parola_hash);
                $stmt->bindParam(":adresa", $adresa);
                $stmt->bindParam(":oras", $oras);
                $stmt->bindParam(":judet", $judet);
                $stmt->bindParam(":cod_postal", $cod_postal);
                $stmt->bindParam(":telefon", $telefon);
                
                if ($stmt->execute()) {
                    $success = "Contul a fost creat cu succes! Acum te poți <a href='login.php'>autentifica</a>.";
                    // Reset form fields
                    $nume = $prenume = $email = $parola = $parola_confirmare = $adresa = $oras = $judet = $cod_postal = $telefon = "";
                } else {
                    $error = "A apărut o eroare la crearea contului: " . implode(", ", $stmt->errorInfo());
                }
            } catch (PDOException $e) {
                $error = "Eroare la creare cont: " . $e->getMessage();
            }
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
    <title>Înregistrare - Gusturi Românești</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signup {
            max-width: 500px;
            margin: 0 auto;
            padding: 15px;
        }
        .form-signup .form-floating:focus-within {
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
        <div class="form-signup">
            <div class="card">
                <div class="card-header text-center py-3">
                    <h3 class="mb-0">Înregistrare</h3>
                    <p class="mb-0">Creează un cont nou pe Gusturi Românești</p>
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
                                <label for="nume" class="form-label">Nume *</label>
                                <input type="text" class="form-control" id="nume" name="nume" value="<?php echo escape($nume); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="prenume" class="form-label">Prenume *</label>
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo escape($prenume); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="parola" class="form-label">Parola *</label>
                                <input type="password" class="form-control" id="parola" name="parola" required>
                                <div class="form-text">Parola trebuie să aibă minim 6 caractere.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="parola_confirmare" class="form-label">Confirmă parola *</label>
                                <input type="password" class="form-control" id="parola_confirmare" name="parola_confirmare" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresa" class="form-label">Adresa *</label>
                                <textarea class="form-control" id="adresa" name="adresa" rows="2" required><?php echo escape($adresa); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="oras" class="form-label">Oraș *</label>
                                <input type="text" class="form-control" id="oras" name="oras" value="<?php echo escape($oras); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="judet" class="form-label">Județ *</label>
                                <input type="text" class="form-control" id="judet" name="judet" value="<?php echo escape($judet); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="cod_postal" class="form-label">Cod Poștal *</label>
                                <input type="text" class="form-control" id="cod_postal" name="cod_postal" value="<?php echo escape($cod_postal); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon *</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo escape($telefon); ?>" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">Sunt de acord cu <a href="#" target="_blank">Termenii și Condițiile</a> *</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Creează cont</button>
                            
                            <div class="text-center mt-3">
                                <p>Ai deja un cont? <a href="login.php">Autentifică-te</a></p>
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