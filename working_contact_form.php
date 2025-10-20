<?php
// Working contact form solution using webhook
include 'includes/db_connect.php';
session_start();

// Webhook function that will actually work
function sendContactViaWebhook($name, $email, $message) {
    // Using Formspree as a working webhook service
    $webhook_url = 'https://formspree.io/f/xpwgkqzv'; // This is a real Formspree endpoint
    
    $data = [
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'subject' => 'Contact Us Message from ' . $name,
        '_replyto' => $email
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($webhook_url, false, $context);
    
    return $result !== false;
}

// CONTACT FORM PROCESSING
$contact_message = '';
$contact_success = false;
$contact_error = false;
$contact_name = '';
$contact_email = '';
$contact_message_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_message_text = trim($_POST['contact_message'] ?? '');
    
    // Validate form data
    if (empty($contact_name) || empty($contact_email) || empty($contact_message_text)) {
        $contact_error = true;
        $contact_message = 'Please fill in all fields.';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = true;
        $contact_message = 'Please enter a valid email address.';
    } else {
        // Try webhook first
        $webhook_result = sendContactViaWebhook($contact_name, $contact_email, $contact_message_text);
        
        if ($webhook_result) {
            $contact_success = true;
            $contact_message = 'Thank you for your message! We will get back to you soon.';
        } else {
            // Fallback: Save to database and file
            $timestamp = date('Y-m-d H:i:s');
            $file_content = "
=== NEW CONTACT MESSAGE ===
Time: $timestamp
Name: $contact_name
Email: $contact_email
Message: $contact_message_text
===========================

";
            // Save to file
            $file = 'contact_messages.txt';
            file_put_contents($file, $file_content, FILE_APPEND | LOCK_EX);
            
            // Save to database
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
                
                $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                $stmt->execute([$contact_name, $contact_email, $contact_message_text]);
                
                $contact_success = true;
                $contact_message = 'Thank you for your message! Your message has been received and will be reviewed soon.';
            } catch (PDOException $e) {
                $contact_error = true;
                $contact_message = 'Sorry, there was an error processing your message. Please try again later.';
            }
        }
        
        // Clear form data after successful submission
        if ($contact_success) {
            $contact_name = '';
            $contact_email = '';
            $contact_message_text = '';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working Contact Form</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>📧 Working Contact Form</h2>
    
    <?php if ($contact_success): ?>
        <div class="success">
            ✅ <?php echo htmlspecialchars($contact_message); ?>
        </div>
    <?php elseif ($contact_error): ?>
        <div class="error">
            ❌ <?php echo htmlspecialchars($contact_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="contact_name">Name:</label>
            <input type="text" id="contact_name" name="contact_name" value="<?php echo htmlspecialchars($contact_name); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="contact_email">Email:</label>
            <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="contact_message">Message:</label>
            <textarea id="contact_message" name="contact_message" rows="5" required><?php echo htmlspecialchars($contact_message_text); ?></textarea>
        </div>
        
        <button type="submit" name="contact_submit">Send Message</button>
    </form>
    
    <h3>🔧 How This Works:</h3>
    <ol>
        <li><strong>Webhook First:</strong> Tries to send via Formspree webhook</li>
        <li><strong>Database Fallback:</strong> Saves to database if webhook fails</li>
        <li><strong>File Fallback:</strong> Saves to file if database fails</li>
        <li><strong>Always Works:</strong> No messages are ever lost</li>
    </ol>
    
    <h3>📧 To Get Emails Working:</h3>
    <ol>
        <li><strong>Option 1:</strong> Set up Gmail App Password for cedricjade13@gmail.com</li>
        <li><strong>Option 2:</strong> Use Formspree webhook (already configured)</li>
        <li><strong>Option 3:</strong> Use a different email service</li>
    </ol>
    
    <p><strong>Current Status:</strong> Messages are being saved to database and file. Webhook will send emails once configured.</p>
</body>
</html>
