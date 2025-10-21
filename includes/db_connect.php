<?php
// Database connection for all includes
try {
	$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', '');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// Set timezone to Philippines for consistent date handling
	$db->exec("SET time_zone = '+08:00'");
} catch (Exception $e) {
	die('Database connection error: ' . $e->getMessage());
}
?>