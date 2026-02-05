<?php
$host = "localhost";
$dbname = "little_lemon";
$username = "root";
$password = "Hong2007";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tables exist, create them if not
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tables == 0) {
        // Create tables from schema
        $schema = file_get_contents('schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>