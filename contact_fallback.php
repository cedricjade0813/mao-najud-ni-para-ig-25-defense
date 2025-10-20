<?php
// Fallback contact form handler - logs to database instead of email
include 'includes/db_connect.php';

// Create contact_messages table if it doesn't exist
try {
    $createTable = $db->prepare("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('new', 'read', 'replied') DEFAULT 'new'
        )
    ");
    $createTable->execute();
    echo "<p style='color: green;'>✅ Contact messages table created/verified</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error creating table: " . $e->getMessage() . "</p>";
}

// Test database logging
if (isset($_POST['test_logging'])) {
    $test_name = "Test User";
    $test_email = "test@example.com";
    $test_message = "This is a test contact form submission.";
    
    try {
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$test_name, $test_email, $test_message]);
        echo "<p style='color: green;'>✅ Test message logged to database successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error logging message: " . $e->getMessage() . "</p>";
    }
}

// Show recent contact messages
try {
    $stmt = $db->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Contact Messages:</h3>";
    if (empty($messages)) {
        echo "<p>No contact messages found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th><th>Status</th></tr>";
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['id']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['name']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($msg['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error fetching messages: " . $e->getMessage() . "</p>";
}
?>

<form method="POST">
    <button type="submit" name="test_logging" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Test Database Logging
    </button>
</form>

<h3>📋 Implementation Steps:</h3>
<ol>
    <li>Update <code>index.php</code> to use database logging as fallback</li>
    <li>Create admin interface to view contact messages</li>
    <li>Set up email notifications for new messages</li>
    <li>Configure proper SMTP settings later</li>
</ol>
