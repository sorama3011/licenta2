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
$added_products = 0;
$out_of_stock_products = [];

// Check if order_id is provided
if ($order_id <= 0) {
    $error = "ID-ul comenzii lipsește sau este invalid.";
} else {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Check if order exists and belongs to the current user
        $stmt = $conn->prepare("
            SELECT id, numar_comanda 
            FROM comenzi 
            WHERE id = :id AND id_utilizator = :id_utilizator
        ");
        $stmt->bindParam(":id", $order_id);
        $stmt->bindParam(":id_utilizator", $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get order items
            $items_stmt = $conn->prepare("
                SELECT cp.id_produs, cp.cantitate, p.denumire, p.stoc
                FROM comenzi_produse cp
                JOIN produse p ON cp.id_produs = p.id
                WHERE cp.id_comanda = :id_comanda
            ");
            $items_stmt->bindParam(":id_comanda", $order_id);
            $items_stmt->execute();
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process each product
            foreach ($order_items as $item) {
                // Check if product is in stock
                if ($item['stoc'] < $item['cantitate']) {
                    // Product is out of stock or has insufficient quantity
                    $out_of_stock_products[] = [
                        'name' => $item['denumire'],
                        'requested' => $item['cantitate'],
                        'available' => $item['stoc']
                    ];
                    
                    // If some quantity is available, add that to cart
                    if ($item['stoc'] > 0) {
                        addToCart($conn, $_SESSION['user_id'], $item['id_produs'], $item['stoc']);
                        $added_products++;
                    }
                } else {
                    // Product is in stock, add to cart
                    addToCart($conn, $_SESSION['user_id'], $item['id_produs'], $item['cantitate']);
                    $added_products++;
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            if ($added_products > 0) {
                $success = "Produsele au fost adăugate în coș.";
                
                // Add warning about out of stock products
                if (count($out_of_stock_products) > 0) {
                    $success .= " Unele produse nu mai sunt disponibile în cantitatea dorită.";
                }
            } else {
                $error = "Nu s-a putut adăuga niciun produs în coș. Toate produsele sunt indisponibile.";
            }
            
            // Log the action
            logAction($conn, 'reorder', "Comandă din nou: " . $order['numar_comanda']);
            
        } else {
            $error = "Comanda nu a fost găsită sau nu aveți acces la această comandă.";
        }
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Eroare de bază de date: " . $e->getMessage();
    }
}

// Function to add product to cart
function addToCart($conn, $user_id, $product_id, $quantity) {
    // Check if product already exists in cart
    $check_stmt = $conn->prepare("
        SELECT id, cantitate 
        FROM cos_cumparaturi 
        WHERE id_utilizator = :id_utilizator AND id_produs = :id_produs
    ");
    $check_stmt->bindParam(":id_utilizator", $user_id);
    $check_stmt->bindParam(":id_produs", $product_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Product exists in cart, update quantity
        $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $new_quantity = $cart_item['cantitate'] + $quantity;
        
        $update_stmt = $conn->prepare("
            UPDATE cos_cumparaturi 
            SET cantitate = :cantitate 
            WHERE id = :id
        ");
        $update_stmt->bindParam(":cantitate", $new_quantity);
        $update_stmt->bindParam(":id", $cart_item['id']);
        $update_stmt->execute();
    } else {
        // Product doesn't exist in cart, insert new item
        $insert_stmt = $conn->prepare("
            INSERT INTO cos_cumparaturi (id_utilizator, id_produs, cantitate) 
            VALUES (:id_utilizator, :id_produs, :cantitate)
        ");
        $insert_stmt->bindParam(":id_utilizator", $user_id);
        $insert_stmt->bindParam(":id_produs", $product_id);
        $insert_stmt->bindParam(":cantitate", $quantity);
        $insert_stmt->execute();
    }
}

// Redirect to cart page with message
if (!empty($error)) {
    header("Location: ../cart.html?error=" . urlencode($error));
} else {
    header("Location: ../cart.html?success=" . urlencode($success));
}
exit;
?>