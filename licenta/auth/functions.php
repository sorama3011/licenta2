<?php
// Include database configuration
require_once 'db-config.php';

/**
 * Register a new user
 * 
 * @param PDO $conn Database connection
 * @param array $userData User data (nume, email, parola, adresa, telefon)
 * @return array Result with status and message
 */
function registerUser($conn, $userData) {
    // Validate required fields
    if (empty($userData['nume']) || empty($userData['email']) || empty($userData['parola'])) {
        return ['status' => 'error', 'message' => 'Toate câmpurile obligatorii trebuie completate.'];
    }
    
    // Validate email
    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Adresa de email nu este validă.'];
    }
    
    // Validate password length
    if (strlen($userData['parola']) < 6) {
        return ['status' => 'error', 'message' => 'Parola trebuie să aibă cel puțin 6 caractere.'];
    }
    
    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email");
        $stmt->bindParam(":email", $userData['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => 'error', 'message' => 'Această adresă de email este deja înregistrată.'];
        }
        
        // Hash password
        $parola_hash = md5($userData['parola']);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO utilizatori (
                nume, email, parola, adresa, telefon, status, tip, data_inregistrare
            ) VALUES (
                :nume, :email, :parola, :adresa, :telefon, 'activ', 'Client', NOW()
            )
        ");
        $stmt->bindParam(":nume", $userData['nume']);
        $stmt->bindParam(":email", $userData['email']);
        $stmt->bindParam(":parola", $parola_hash);
        $stmt->bindParam(":adresa", $userData['adresa']);
        $stmt->bindParam(":telefon", $userData['telefon']);
        
        if ($stmt->execute()) {
            $user_id = $conn->lastInsertId();
            
            // Log the registration
            logAction($conn, 'register', 'Înregistrare utilizator nou');
            
            return [
                'status' => 'success', 
                'message' => 'Contul a fost creat cu succes!',
                'user_id' => $user_id
            ];
        } else {
            return ['status' => 'error', 'message' => 'A apărut o eroare la crearea contului. Încearcă din nou.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Authenticate a user
 * 
 * @param PDO $conn Database connection
 * @param string $email User email
 * @param string $password User password
 * @return array Result with status and user data if successful
 */
function loginUser($conn, $email, $password) {
    // Validate required fields
    if (empty($email) || empty($password)) {
        return ['status' => 'error', 'message' => 'Te rugăm să completezi toate câmpurile.'];
    }
    
    try {
        // Check credentials
        $stmt = $conn->prepare("SELECT id, nume, parola, tip, status FROM utilizatori WHERE email = :email");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account is active
            if ($user['status'] !== 'activ') {
                return ['status' => 'error', 'message' => 'Acest cont este inactiv. Te rugăm să contactezi administratorul.'];
            }
            
            // Verify password (using MD5 as specified)
            $hashed_password = md5($password);
            
            if ($hashed_password === $user['parola']) {
                // Update last login time
                $update_stmt = $conn->prepare("UPDATE utilizatori SET ultima_autentificare = NOW() WHERE id = :id");
                $update_stmt->bindParam(":id", $user['id']);
                $update_stmt->execute();
                
                // Log login
                logAction($conn, 'login', 'Autentificare reușită');
                
                return [
                    'status' => 'success',
                    'user' => [
                        'id' => $user['id'],
                        'nume' => $user['nume'],
                        'tip' => $user['tip']
                    ]
                ];
            } else {
                return ['status' => 'error', 'message' => 'Email sau parolă incorectă.'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Email sau parolă incorectă.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Update user profile
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @param array $userData User data to update
 * @return array Result with status and message
 */
function updateUserProfile($conn, $user_id, $userData) {
    try {
        $stmt = $conn->prepare("
            UPDATE utilizatori SET 
                nume = :nume,
                telefon = :telefon,
                adresa = :adresa,
                oras = :oras,
                judet = :judet,
                cod_postal = :cod_postal
            WHERE id = :id
        ");
        $stmt->bindParam(":nume", $userData['nume']);
        $stmt->bindParam(":telefon", $userData['telefon']);
        $stmt->bindParam(":adresa", $userData['adresa']);
        $stmt->bindParam(":oras", $userData['oras']);
        $stmt->bindParam(":judet", $userData['judet']);
        $stmt->bindParam(":cod_postal", $userData['cod_postal']);
        $stmt->bindParam(":id", $user_id);
        
        if ($stmt->execute()) {
            // Log the update
            logAction($conn, 'profile_update', 'Actualizare profil utilizator');
            
            return ['status' => 'success', 'message' => 'Profilul a fost actualizat cu succes!'];
        } else {
            return ['status' => 'error', 'message' => 'A apărut o eroare la actualizarea profilului. Încearcă din nou.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Change user password
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Result with status and message
 */
function changePassword($conn, $user_id, $current_password, $new_password) {
    try {
        // Verify current password
        $stmt = $conn->prepare("SELECT parola FROM utilizatori WHERE id = :id");
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['status' => 'error', 'message' => 'Utilizator negăsit.'];
        }
        
        $current_password_hash = md5($current_password);
        
        if ($current_password_hash !== $user['parola']) {
            return ['status' => 'error', 'message' => 'Parola curentă este incorectă.'];
        }
        
        // Validate new password
        if (strlen($new_password) < 6) {
            return ['status' => 'error', 'message' => 'Parola nouă trebuie să aibă cel puțin 6 caractere.'];
        }
        
        // Update password
        $new_password_hash = md5($new_password);
        $stmt = $conn->prepare("UPDATE utilizatori SET parola = :parola WHERE id = :id");
        $stmt->bindParam(":parola", $new_password_hash);
        $stmt->bindParam(":id", $user_id);
        
        if ($stmt->execute()) {
            // Log the password change
            logAction($conn, 'password_change', 'Schimbare parolă');
            
            return ['status' => 'success', 'message' => 'Parola a fost schimbată cu succes!'];
        } else {
            return ['status' => 'error', 'message' => 'A apărut o eroare la schimbarea parolei. Încearcă din nou.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Request password reset
 * 
 * @param PDO $conn Database connection
 * @param string $email User email
 * @return array Result with status and message
 */
function requestPasswordReset($conn, $email) {
    try {
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
                // Log the reset request
                logAction($conn, 'password_reset_request', 'Solicitare resetare parolă');
                
                // In a real application, send email with reset link
                $reset_link = "reset-confirm.php?email=" . urlencode($email) . "&code=" . $reset_code;
                
                return [
                    'status' => 'success', 
                    'message' => 'Un email cu instrucțiuni pentru resetarea parolei a fost trimis la adresa ta de email.',
                    'reset_link' => $reset_link // For demonstration only
                ];
            } else {
                return ['status' => 'error', 'message' => 'A apărut o eroare la procesarea cererii. Încearcă din nou.'];
            }
        } else {
            // Don't reveal if email exists or not for security
            return ['status' => 'success', 'message' => 'Dacă adresa de email există în baza noastră de date, vei primi instrucțiuni pentru resetarea parolei.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Reset password with token
 * 
 * @param PDO $conn Database connection
 * @param string $email User email
 * @param string $code Reset code
 * @param string $new_password New password
 * @return array Result with status and message
 */
function resetPassword($conn, $email, $code, $new_password) {
    try {
        // Validate token
        $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = :email AND cod_parola = :cod_parola AND data_expirare_token > NOW() AND status = 'activ'");
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":cod_parola", $code);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validate new password
            if (strlen($new_password) < 6) {
                return ['status' => 'error', 'message' => 'Parola nouă trebuie să aibă cel puțin 6 caractere.'];
            }
            
            // Hash password
            $parola_hash = md5($new_password);
            
            // Update user password
            $update_stmt = $conn->prepare("UPDATE utilizatori SET parola = :parola, cod_parola = NULL, data_expirare_token = NULL WHERE email = :email AND cod_parola = :cod_parola");
            $update_stmt->bindParam(":parola", $parola_hash);
            $update_stmt->bindParam(":email", $email);
            $update_stmt->bindParam(":cod_parola", $code);
            
            if ($update_stmt->execute()) {
                // Log the password reset
                logAction($conn, 'password_reset', 'Resetare parolă reușită');
                
                return ['status' => 'success', 'message' => 'Parola a fost resetată cu succes! Acum te poți autentifica cu noua parolă.'];
            } else {
                return ['status' => 'error', 'message' => 'A apărut o eroare la resetarea parolei. Încearcă din nou.'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Link-ul de resetare este invalid sau a expirat.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Eroare de bază de date: ' . $e->getMessage()];
    }
}

/**
 * Get user orders
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Limit number of orders (0 for all)
 * @return array Orders
 */
function getUserOrders($conn, $user_id, $limit = 0) {
    try {
        $sql = "
            SELECT o.id, o.numar_comanda, o.data_plasare, o.status, o.total, 
                   COUNT(op.id) AS numar_produse
            FROM comenzi o
            JOIN comenzi_produse op ON o.id = op.id_comanda
            WHERE o.id_utilizator = :id_utilizator
            GROUP BY o.id
            ORDER BY o.data_plasare DESC
        ";
        
        if ($limit > 0) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id_utilizator", $user_id);
        
        if ($limit > 0) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get user loyalty points
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @return array Loyalty points data
 */
function getUserLoyaltyPoints($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT puncte_totale, puncte_folosite, 
                   (puncte_totale - puncte_folosite) AS puncte_disponibile
            FROM puncte_fidelitate
            WHERE id_utilizator = :id_utilizator
        ");
        $stmt->bindParam(":id_utilizator", $user_id);
        $stmt->execute();
        $points = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$points) {
            return [
                'puncte_totale' => 0,
                'puncte_folosite' => 0,
                'puncte_disponibile' => 0
            ];
        }
        
        return $points;
    } catch (PDOException $e) {
        return [
            'puncte_totale' => 0,
            'puncte_folosite' => 0,
            'puncte_disponibile' => 0
        ];
    }
}
?>