<?php
// Start session
session_start();

// Include database configuration
require_once 'auth/db-config.php';

// Initialize variables
$success = $error = "";
$formData = [
    'prenume' => '',
    'nume' => '',
    'email' => '',
    'telefon' => '',
    'subiect' => '',
    'mesaj' => ''
];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $formData = [
        'prenume' => trim($_POST['firstName'] ?? ''),
        'nume' => trim($_POST['lastName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefon' => trim($_POST['phone'] ?? ''),
        'subiect' => trim($_POST['subject'] ?? ''),
        'mesaj' => trim($_POST['message'] ?? '')
    ];
    
    // Check privacy policy agreement
    if (!isset($_POST['privacy']) || $_POST['privacy'] != 'on') {
        $error = "Trebuie să fiți de acord cu Politica de Confidențialitate.";
    }
    // Validate required fields
    elseif (empty($formData['prenume']) || empty($formData['nume']) || empty($formData['email']) || 
            empty($formData['subiect']) || empty($formData['mesaj'])) {
        $error = "Toate câmpurile obligatorii trebuie completate.";
    }
    // Validate email
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
    }
    else {
        try {
            // Sanitize inputs
            foreach ($formData as $key => $value) {
                $formData[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            
            // Insert into database
            $stmt = $conn->prepare("
                INSERT INTO contacte (prenume, nume, email, telefon, subiect, mesaj)
                VALUES (:prenume, :nume, :email, :telefon, :subiect, :mesaj)
            ");
            
            $stmt->bindParam(":prenume", $formData['prenume']);
            $stmt->bindParam(":nume", $formData['nume']);
            $stmt->bindParam(":email", $formData['email']);
            $stmt->bindParam(":telefon", $formData['telefon']);
            $stmt->bindParam(":subiect", $formData['subiect']);
            $stmt->bindParam(":mesaj", $formData['mesaj']);
            
            if ($stmt->execute()) {
                // Log the action if user is logged in
                if (isset($_SESSION['user_id'])) {
                    logAction($conn, 'contact_submit', "Formular de contact trimis: " . $formData['subiect']);
                }
                
                $success = "Mesajul a fost trimis cu succes! Vă vom contacta în curând.";
                
                // Reset form data
                $formData = [
                    'prenume' => '',
                    'nume' => '',
                    'email' => '',
                    'telefon' => '',
                    'subiect' => '',
                    'mesaj' => ''
                ];
            } else {
                $error = "A apărut o eroare la trimiterea mesajului. Vă rugăm să încercați din nou.";
            }
        } catch(PDOException $e) {
            $error = "Eroare de bază de date: " . $e->getMessage();
        }
    }
}

// Escape output
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact - Gusturi Românești</title>
    <meta name="description" content="Contactează echipa Gusturi Românești. Suntem aici să te ajutăm cu întrebări despre produse, comenzi sau parteneriate.">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assistant-bot.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                🇷🇴 Gusturi Românești
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Acasă</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.html">Produse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.html">Oferte</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">Despre Noi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.html">
                            <i class="bi bi-person"></i> Cont
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.html">
                            <i class="bi bi-basket"></i> Coș
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" id="cart-count">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-primary text-white py-5" style="margin-top: 76px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold">Contactează-ne</h1>
                    <p class="lead">Suntem aici să te ajutăm! Trimite-ne un mesaj și îți vom răspunde cât mai curând posibil.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Trimite-ne un Mesaj</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <?php echo escape($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo escape($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form id="contactForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">Prenume *</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo escape($formData['prenume']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Nume *</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo escape($formData['nume']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($formData['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo escape($formData['telefon']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subiect *</label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="" <?php echo empty($formData['subiect']) ? 'selected' : ''; ?>>Selectează subiectul</option>
                                            <option value="Întrebări despre comandă" <?php echo $formData['subiect'] === 'Întrebări despre comandă' ? 'selected' : ''; ?>>Întrebări despre comandă</option>
                                            <option value="Întrebări despre produse" <?php echo $formData['subiect'] === 'Întrebări despre produse' ? 'selected' : ''; ?>>Întrebări despre produse</option>
                                            <option value="Parteneriat/Colaborare" <?php echo $formData['subiect'] === 'Parteneriat/Colaborare' ? 'selected' : ''; ?>>Parteneriat/Colaborare</option>
                                            <option value="Reclamație" <?php echo $formData['subiect'] === 'Reclamație' ? 'selected' : ''; ?>>Reclamație</option>
                                            <option value="Altele" <?php echo $formData['subiect'] === 'Altele' ? 'selected' : ''; ?>>Altele</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Mesaj *</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Descrie-ne cum te putem ajuta..."><?php echo escape($formData['mesaj']); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="privacy" name="privacy" required>
                                            <label class="form-check-label" for="privacy">
                                                Sunt de acord cu <a href="privacy.html" class="text-primary">Politica de Confidențialitate</a> *
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send"></i> Trimite Mesajul
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4">
                    <!-- Contact Details -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Informații de Contact</h5>
                        </div>
                        <div class="card-body">
                            <div class="contact-item mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt-fill text-primary me-3" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Adresa</h6>
                                        <p class="mb-0 text-muted">Strada Gusturilor Nr. 25<br>Sector 1, București 010101</p>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="contact-item mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-telephone-fill text-primary me-3" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Telefon</h6>
                                        <p class="mb-0 text-muted">+40 721 234 567</p>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="contact-item mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-envelope-fill text-primary me-3" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Email</h6>
                                        <p class="mb-0 text-muted">contact@gusturi-romanesti.ro</p>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="contact-item">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock-fill text-primary me-3" style="font-size: 1.2rem;"></i>
                                    <div>
                                        <h6 class="mb-1">Program</h6>
                                        <p class="mb-0 text-muted">Luni - Vineri: 9:00 - 18:00<br>Sâmbătă: 9:00 - 14:00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Întrebări Frecvente</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            Cum pot urmări comanda?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            După confirmarea comenzii, veți primi un email cu numărul de urmărire. Puteți verifica statusul comenzii în secțiunea "Contul Meu".
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            Care este timpul de livrare?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Livrăm în 24-48 de ore în București și în 2-4 zile lucrătoare în restul țării. Produsele perisabile sunt livrate cu transport refrigerat.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            Pot returna un produs?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Produsele alimentare nu pot fi returnate din motive de igienă. În cazul unor probleme de calitate, vă rugăm să ne contactați în 24 de ore.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Locația Noastră</h2>
            <div class="row">
                <div class="col-lg-12">
                    <!-- Embedded Google Map (fictional location) -->
                    <div class="map-container" style="height: 400px; background-color: #e9ecef; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <i class="bi bi-geo-alt-fill text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-2">Hartă Interactivă</h5>
                            <p class="text-muted">Strada Gusturilor Nr. 25, București</p>
                            <small class="text-muted">Harta Google Maps va fi integrată aici</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5>Gusturi Românești</h5>
                    <p>Cea mai mare platformă online de produse tradiționale românești.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6>Navigare</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-light text-decoration-none">Acasă</a></li>
                        <li><a href="products.html" class="text-light text-decoration-none">Produse</a></li>
                        <li><a href="offers.html" class="text-light text-decoration-none">Oferte</a></li>
                        <li><a href="about.html" class="text-light text-decoration-none">Despre Noi</a></li>
                        <li><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Cont</h6>
                    <ul class="list-unstyled">
                        <li><a href="login.html" class="text-light text-decoration-none">Autentificare</a></li>
                        <li><a href="cart.html" class="text-light text-decoration-none">Coșul Meu</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Informații</h6>
                    <ul class="list-unstyled">
                        <li><a href="privacy.html" class="text-light text-decoration-none">Politica de Confidențialitate</a></li>
                        <li><a href="terms.html" class="text-light text-decoration-none">Termeni și Condiții</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Contact</h6>
                    <p class="small mb-1">📍 București, România</p>
                    <p class="small mb-1">📞 +40 721 234 567</p>
                    <p class="small">✉️ contact@gusturi-romanesti.ro</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Gusturi Românești. Toate drepturile rezervate.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script src="assistant-bot.js"></script>
</body>
</html>