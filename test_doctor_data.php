<?php
// Simple test to check doctor data
include 'includes/db_connect.php';

echo "<h2>Testing Doctor Data with doctor_id</h2>";

// Check doctor_schedules table
echo "<h3>Doctor Schedules:</h3>";
$stmt = $db->prepare("SELECT * FROM doctor_schedules ORDER BY id DESC LIMIT 10");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($doctors, true) . "</pre>";

// Check appointments table
echo "<h3>Appointments:</h3>";
$stmt = $db->prepare("SELECT * FROM appointments ORDER BY date DESC LIMIT 10");
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($appointments, true) . "</pre>";

// Test join using doctor_id
echo "<h3>Test Join with doctor_id:</h3>";
$stmt = $db->prepare("SELECT a.id, a.date, a.student_id, a.doctor_id, ds.doctor_name FROM appointments a LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id ORDER BY a.date DESC LIMIT 5");
$stmt->execute();
$join_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($join_result, true) . "</pre>";

// Check if there are any matching doctor_ids
echo "<h3>Doctor ID Matching Test:</h3>";
$stmt = $db->prepare("SELECT DISTINCT a.doctor_id, ds.id as schedule_id, ds.doctor_name FROM appointments a LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id WHERE ds.doctor_name IS NOT NULL");
$stmt->execute();
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($matches, true) . "</pre>";

// Check appointments table structure
echo "<h3>Appointments Table Structure:</h3>";
$stmt = $db->prepare("DESCRIBE appointments");
$stmt->execute();
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($structure, true) . "</pre>";
?>
