<?php
session_start();
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "cbt_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Simple admin check (hardcoded for demo; use proper auth in production)
// define('ADMIN_USER', 'admin');
// define('ADMIN_PASS', 'password123');
