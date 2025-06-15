<?php
// Start session
session_start();

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    // Database connection
    $host = "localhost";
    $dbname = "proiect_licenta";
    $username = "root";
    $password = "";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Log logout
        $log_stmt = $conn->prepare("INSERT INTO jurnalizare (id_utilizator, actiune, detalii, ip) VALUES (:id_utilizator, 'logout', 'Deconectare', :ip)");
        $log_stmt->bindParam(":id_utilizator", $_SESSION['user_id']);
        $log_stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
    } catch(PDOException $e) {
        // Silently fail - logging should not prevent logout
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>