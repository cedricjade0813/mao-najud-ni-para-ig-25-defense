<?php
// School Lab Server Information Page
// Shows connection details for other students

function getServerIP() {
    $ip = '';
    
    // Method 1: Using $_SERVER
    if (isset($_SERVER['SERVER_ADDR'])) {
        $ip = $_SERVER['SERVER_ADDR'];
    }
    
    // Method 2: Using network interfaces
    if (empty($ip)) {
        $output = shell_exec('ipconfig');
        if (preg_match('/IPv4 Address.*?(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
            $ip = $matches[1];
        }
    }
    
    return $ip;
}

function getNetworkInfo() {
    $info = [];
    
    // Get IP address
    $info['ip'] = getServerIP();
    
    // Get computer name
    $info['computer_name'] = gethostname();
    
    // Get current time
    $info['time'] = date('Y-m-d H:i:s');
    
    // Get server status
    $info['server_status'] = 'Online';
    
    return $info;
}

$networkInfo = getNetworkInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComLab School Server - Connection Info</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.2em;
        }
        
        .server-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .info-value {
            color: #007bff;
            font-family: monospace;
            font-size: 1.1em;
        }
        
        .connection-url {
            background: #e8f4fd;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        
        .connection-url h3 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .url-display {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            font-family: monospace;
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .instructions h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        
        .instructions ol {
            color: #856404;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏫 ComLab School Server</h1>
            <p>Network Server Information</p>
        </div>
        
        <div class="server-info">
            <h3>📊 Server Status</h3>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-indicator"></span><?php echo $networkInfo['server_status']; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Computer Name:</span>
                <span class="info-value"><?php echo $networkInfo['computer_name']; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">IP Address:</span>
                <span class="info-value"><?php echo $networkInfo['ip']; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Current Time:</span>
                <span class="info-value"><?php echo $networkInfo['time']; ?></span>
            </div>
        </div>
        
        <div class="connection-url">
            <h3>🌐 Connection URL for Other Students</h3>
            <div class="url-display" id="connectionUrl">
                http://<?php echo $networkInfo['ip']; ?>
            </div>
            <button class="copy-btn" onclick="copyToClipboard()">📋 Copy URL</button>
        </div>
        
        <div class="instructions">
            <h3>📋 Instructions for Other Students</h3>
            <ol>
                <li><strong>Open your web browser</strong> (Chrome, Firefox, Edge, etc.)</li>
                <li><strong>Type the URL above</strong> in the address bar</li>
                <li><strong>Press Enter</strong> to access the ComLab system</li>
                <li><strong>Share this URL</strong> with other classmates</li>
            </ol>
        </div>
        
        <div class="instructions">
            <h3>🔧 Server Management</h3>
            <ol>
                <li><strong>To stop server:</strong> Close the setup window or run restore script</li>
                <li><strong>To restart:</strong> Run setup_school_lab_server.bat again</li>
                <li><strong>To check status:</strong> Visit this page anytime</li>
            </ol>
        </div>
        
        <div class="footer">
            <p>🚀 ComLab School Server - Ready for the entire computer lab!</p>
        </div>
    </div>
    
    <script>
        function copyToClipboard() {
            const url = document.getElementById('connectionUrl').textContent;
            navigator.clipboard.writeText(url).then(function() {
                alert('URL copied to clipboard! Share this with your classmates.');
            }, function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('URL copied to clipboard! Share this with your classmates.');
            });
        }
    </script>
</body>
</html>
