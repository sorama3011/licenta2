<?php
// Start session
session_start();

// Include database configuration
require_once 'db-config.php';

// Check if user is logged in
requireLogin();

// Initialize variables
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = "";
$success = "";

// Check if order_id is provided
if ($order_id <= 0) {
    $error = "ID-ul comenzii lipsește sau este invalid.";
} else {
    try {
        // Check if order exists and belongs to the current user
        $stmt = $conn->prepare("
            SELECT id, numar_comanda, status 
            FROM comenzi 
            WHERE id = :id AND id_utilizator = :id_utilizator
        ");
        $stmt->bindParam(":id", $order_id);
        $stmt->bindParam(":id_utilizator", $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if order can be cancelled (only if status is 'Plasată' or 'Confirmată')
            if (in_array($order['status'], ['Plasată', 'Confirmată'])) {
                // Update order status to 'Anulată'
                $update_stmt = $conn->prepare("
                    UPDATE comenzi 
                    SET status = 'Anulată' 
                    WHERE id = :id
                ");
                $update_stmt->bindParam(":id", $order_id);
                
                if ($update_stmt->execute()) {
                    // Return products to stock
                    $stock_stmt = $conn->prepare("
                        UPDATE produse p
                        JOIN comenzi_produse cp ON p.id = cp.id_produs
                        SET p.stoc = p.stoc + cp.cantitate
                        WHERE cp.id_comanda = :id_comanda
                    ");
                    $stock_stmt->bindParam(":id_comanda", $order_id);
                    $stock_stmt->execute();
                    
                    // Log the action
                    logAction($conn, 'order_cancel', "Anulare comandă: " . $order['numar_comanda']);
                    
                    $success = "Comanda a fost anulată cu succes!";
                } else {
                    $error = "A apărut o eroare la anularea comenzii.";
                }
            } else {
                $error = "Această comandă nu mai poate fi anulată deoarece este deja " . strtolower($order['status']) . ".";
            }
        } else {
            $error = "Comanda nu a fost găsită sau nu aveți acces la această comandă.";
        }
    } catch(PDOException $e) {
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
}

// Redirect back to order details or account page
if (!empty($error)) {
    $_SESSION['error_message'] = $error;
} else {
    $_SESSION['success_message'] = $success;
}

header("Location: account.html");
exit;
?>