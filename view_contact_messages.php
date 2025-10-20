<?php
// View contact messages from database and file
include 'includes/db_connect.php';

echo "<h2>📧 Contact Messages Viewer</h2>";

// View messages from database
echo "<h3>📊 Messages from Database:</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($messages)) {
        echo "<p>No messages found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['id']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['name']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 100)) . "...</td>";
            echo "<td>" . htmlspecialchars($msg['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// View messages from file
echo "<h3>📄 Messages from File:</h3>";
$file = 'contact_messages.txt';
if (file_exists($file)) {
    $file_content = file_get_contents($file);
    if (!empty($file_content)) {
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; white-space: pre-wrap;'>";
        echo htmlspecialchars($file_content);
        echo "</pre>";
    } else {
        echo "<p>No messages found in file.</p>";
    }
} else {
    echo "<p>Contact messages file not found.</p>";
}

// Test the contact form
echo "<h3>🧪 Test Contact Form:</h3>";
if (isset($_POST['test_contact'])) {
    $test_name = $_POST['test_name'] ?? 'Test User';
    $test_email = $_POST['test_email'] ?? 'test@example.com';
    $test_message = $_POST['test_message'] ?? 'This is a test message.';
    
    // Simulate the contact form processing
    $timestamp = date('Y-m-d H:i:s');
    $email_content = "
=== NEW CONTACT MESSAGE ===
Time: $timestamp
Name: $test_name
Email: $test_email
Message: $test_message
===========================

";
    
    // Save to file
    file_put_contents('contact_messages.txt', $email_content, FILE_APPEND | LOCK_EX);
    
    // Save to database
    try {
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$test_name, $test_email, $test_message]);
        echo "<p style='color: green;'>✅ Test message saved to both database and file!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='view_contact_messages.php'>Refresh page to see the new message</a></p>";
}

echo "<form method='POST' style='background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;'>";
echo "<h4>Send Test Message:</h4>";
echo "<p><label>Name: <input type='text' name='test_name' value='Test User' required></label></p>";
echo "<p><label>Email: <input type='email' name='test_email' value='test@example.com' required></label></p>";
echo "<p><label>Message: <textarea name='test_message' rows='3' required>This is a test message.</textarea></label></p>";
echo "<p><button type='submit' name='test_contact' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Send Test Message</button></p>";
echo "</form>";

echo "<h3>📧 Email Status:</h3>";
echo "<p><strong>PHPMailer (Gmail):</strong> ❌ Not working (authentication failed)</p>";
echo "<p><strong>PHP mail():</strong> ❌ Not working (no mail server configured)</p>";
echo "<p><strong>Database logging:</strong> ✅ Working</p>";
echo "<p><strong>File logging:</strong> ✅ Working</p>";

echo "<h3>🔧 To Fix Email:</h3>";
echo "<ol>";
echo "<li><strong>Fix Gmail:</strong> Update App Password in mail.php</li>";
echo "<li><strong>Configure XAMPP Mail:</strong> Set up local mail server</li>";
echo "<li><strong>Use Different Service:</strong> Try Outlook/Yahoo SMTP</li>";
echo "<li><strong>Use Webhook:</strong> Use Formspree or similar service</li>";
echo "</ol>";
?>
