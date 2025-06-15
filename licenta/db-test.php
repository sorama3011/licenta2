<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    // Try both localhost and 127.0.0.1
    $host = "localhost"; // Try with localhost first
    $dbname = "gusturi_romanesti";
    $username = "root";
    $password = "";
    
    echo "Attempting to connect to database: $dbname on $host...<br>";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<span style='color:green'>✓ Connection successful!</span><br>";
} catch(PDOException $e) {
    echo "<span style='color:red'>✗ Connection failed: " . $e->getMessage() . "</span><br>";
    
    // Try with 127.0.0.1 if localhost fails
    try {
        $host = "127.0.0.1";
        echo "Attempting to connect with IP address ($host) instead...<br>";
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<span style='color:green'>✓ Connection with IP address successful!</span><br>";
    } catch(PDOException $e2) {
        echo "<span style='color:red'>✗ Connection with IP address also failed: " . $e2->getMessage() . "</span><br>";
        die("Cannot proceed with further tests without database connection.");
    }
}

// Test 2: Check if tables exist
echo "<h2>Test 2: Check Tables</h2>";
try {
    $tables = ['utilizatori', 'produse', 'categorii', 'regiuni'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<span style='color:green'>✓ Table '$table' exists</span><br>";
        } else {
            echo "<span style='color:red'>✗ Table '$table' does not exist!</span><br>";
        }
    }
} catch(PDOException $e) {
    echo "<span style='color:red'>✗ Error checking tables: " . $e->getMessage() . "</span><br>";
}

// Test 3: Test INSERT operation
echo "<h2>Test 3: Test INSERT Operation</h2>";
try {
    // Create a test user with a unique email
    $test_email = "test_" . time() . "@example.com";
    $test_name = "Test User";
    $test_password = md5("testpassword"); // Using MD5 as in your original code
    
    echo "Attempting to insert a test user with email: $test_email<br>";
    
    $stmt = $conn->prepare("
        INSERT INTO utilizatori (email, parola, nume, status, tip, data_inregistrare) 
        VALUES (:email, :parola, :nume, 'activ', 'Client', NOW())
    ");
    
    $stmt->bindParam(":email", $test_email);
    $stmt->bindParam(":parola", $test_password);
    $stmt->bindParam(":nume", $test_name);
    
    if ($stmt->execute()) {
        $user_id = $conn->lastInsertId();
        echo "<span style='color:green'>✓ Test user inserted successfully with ID: $user_id</span><br>";
        
        // Clean up - delete the test user
        $delete_stmt = $conn->prepare("DELETE FROM utilizatori WHERE id = :id");
        $delete_stmt->bindParam(":id", $user_id);
        $delete_stmt->execute();
        echo "<span style='color:blue'>ℹ Test user deleted for cleanup</span><br>";
    } else {
        echo "<span style='color:red'>✗ Failed to insert test user</span><br>";
    }
} catch(PDOException $e) {
    echo "<span style='color:red'>✗ Error during INSERT test: " . $e->getMessage() . "</span><br>";
    
    // Show table structure to help diagnose the issue
    try {
        echo "<h3>Table Structure:</h3>";
        $stmt = $conn->query("DESCRIBE utilizatori");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } catch(PDOException $e2) {
        echo "<span style='color:red'>✗ Error getting table structure: " . $e2->getMessage() . "</span><br>";
    }
}

// Test 4: Check form data handling
echo "<h2>Test 4: Form Data Handling</h2>";
echo "POST data received:<br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<p>Test form:</p>";
echo "<form method='post' action=''>";
echo "<input type='text' name='test_name' placeholder='Test Name' value='Test User'><br>";
echo "<input type='email' name='test_email' placeholder='Test Email' value='test@example.com'><br>";
echo "<input type='password' name='test_password' placeholder='Test Password' value='password123'><br>";
echo "<button type='submit'>Submit Test Form</button>";
echo "</form>";

// Test 5: Check signup.php file
echo "<h2>Test 5: Check signup.php File</h2>";
$signup_path = __DIR__ . '/auth/signup.php';
if (file_exists($signup_path)) {
    echo "<span style='color:green'>✓ signup.php file exists at: $signup_path</span><br>";
    
    // Check if the file is readable
    if (is_readable($signup_path)) {
        echo "<span style='color:green'>✓ signup.php is readable</span><br>";
        
        // Display the first few lines of the file
        echo "<p>First 20 lines of signup.php:</p>";
        $lines = file($signup_path);
        echo "<pre>";
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]);
        }
        echo "</pre>";
    } else {
        echo "<span style='color:red'>✗ signup.php is not readable</span><br>";
    }
} else {
    echo "<span style='color:red'>✗ signup.php file not found at: $signup_path</span><br>";
    
    // Try to find it elsewhere
    echo "Searching for signup.php in the project...<br>";
    $found = false;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__)) as $file) {
        if (basename($file) === 'signup.php') {
            echo "<span style='color:blue'>ℹ Found at: " . $file->getPathname() . "</span><br>";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "<span style='color:red'>✗ signup.php not found anywhere in the project</span><br>";
    }
}

echo "<h2>Summary</h2>";
echo "<p>If all tests passed, your database connection is working correctly. If you're still having issues with your forms, check:</p>";
echo "<ol>";
echo "<li>Form action URL - make sure it points to the correct PHP file</li>";
echo "<li>Form method - ensure it's set to POST</li>";
echo "<li>Field names - ensure they match what your PHP code expects</li>";
echo "<li>PHP error logging - check your server logs for any hidden errors</li>";
echo "</ol>";
?>