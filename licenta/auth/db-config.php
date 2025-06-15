<?php
// Database configuration
$host = "localhost";
$dbname = "gusturi_romanesti";
$username = "root";
$password = "";

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Conexiunea la baza de date a eșuat: " . $e->getMessage());
}

// Helper functions
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Administrator';
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: login.php");
        exit;
    }
}

function logAction($conn, $action, $details = '') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO jurnalizare (id_utilizator, actiune, detalii, ip, user_agent) VALUES (:id_utilizator, :actiune, :detalii, :ip, :user_agent)");
    $stmt->bindParam(":id_utilizator", $user_id);
    $stmt->bindParam(":actiune", $action);
    $stmt->bindParam(":detalii", $details);
    $stmt->bindParam(":ip", $ip);
    $stmt->bindParam(":user_agent", $user_agent);
    $stmt->execute();
}
?>