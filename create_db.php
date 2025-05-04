<?php
    $config = require __DIR__ . '/config.php';
    
    $host = $config['host'];
    $dbname = $config['dbname'];
    $user = $config['username'];
    $pass = $config['password'];
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
 
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            registered_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        echo "Database and table created successfully.";

    } catch (PDOException $e) {
        exit('Error: ' . $e->getMessage());
    }
