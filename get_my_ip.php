<?php
// Simple script to get your current IP address
// Run this in any network to find your IP

function getLocalIP() {
    // Get the local IP address
    $ip = '';
    
    // Method 1: Using $_SERVER
    if (isset($_SERVER['SERVER_ADDR'])) {
        $ip = $_SERVER['SERVER_ADDR'];
    }
    
    // Method 2: Using network interfaces (Windows)
    if (empty($ip)) {
        $output = shell_exec('ipconfig');
        if (preg_match('/IPv4 Address.*?(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
            $ip = $matches[1];
        }
    }
    
    return $ip;
}

$currentIP = getLocalIP();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Current IP Address</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .ip-display { 
            background: #f0f0f0; 
            padding: 20px; 
            border-radius: 5px; 
            font-size: 18px; 
            margin: 20px 0;
        }
        .url-display {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h1>🌐 Your Current Network Information</h1>
    
    <div class="ip-display">
        <strong>Your IP Address:</strong> <?php echo $currentIP; ?>
    </div>
    
    <div class="url-display">
        <strong>Access your website at:</strong><br>
        <a href="http://<?php echo $currentIP; ?>" target="_blank">http://<?php echo $currentIP; ?></a>
    </div>
    
    <h3>📝 Instructions:</h3>
    <ul>
        <li><strong>At Home:</strong> Use the IP address shown above</li>
        <li><strong>At School:</strong> Run this script again to get your school's IP</li>
        <li><strong>Any Network:</strong> This configuration works with any IP address!</li>
    </ul>
    
    <h3>🔧 How it works:</h3>
    <p>The virtual host configuration I set up uses <code>&lt;VirtualHost _default_:80&gt;</code> which catches requests from <strong>any IP address</strong>. This means:</p>
    <ul>
        <li>✅ Works at home (192.168.101.11)</li>
        <li>✅ Works at school (whatever IP you get there)</li>
        <li>✅ Works on any network</li>
        <li>✅ Still works with localhost</li>
    </ul>
    
    <p><strong>Note:</strong> Make sure XAMPP Apache is running for this to work!</p>
</body>
</html>
