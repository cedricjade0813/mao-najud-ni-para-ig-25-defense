<?php
// Network Status Checker for ComLab School Server
// Monitors server health and network connectivity

function getServerStatus() {
    $status = [];
    
    // Check Apache status
    $apacheRunning = false;
    $processes = shell_exec('tasklist /FI "IMAGENAME eq httpd.exe"');
    if (strpos($processes, 'httpd.exe') !== false) {
        $apacheRunning = true;
    }
    
    // Check MySQL status
    $mysqlRunning = false;
    $processes = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe"');
    if (strpos($processes, 'mysqld.exe') !== false) {
        $mysqlRunning = true;
    }
    
    // Check disk space
    $diskFree = disk_free_space('C:');
    $diskTotal = disk_total_space('C:');
    $diskUsed = $diskTotal - $diskFree;
    $diskPercent = round(($diskUsed / $diskTotal) * 100, 2);
    
    // Check memory usage
    $memoryInfo = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value');
    $memoryLines = explode("\n", $memoryInfo);
    $totalMemory = 0;
    $freeMemory = 0;
    
    foreach ($memoryLines as $line) {
        if (strpos($line, 'TotalVisibleMemorySize=') === 0) {
            $totalMemory = intval(str_replace('TotalVisibleMemorySize=', '', trim($line)));
        }
        if (strpos($line, 'FreePhysicalMemory=') === 0) {
            $freeMemory = intval(str_replace('FreePhysicalMemory=', '', trim($line)));
        }
    }
    
    $usedMemory = $totalMemory - $freeMemory;
    $memoryPercent = $totalMemory > 0 ? round(($usedMemory / $totalMemory) * 100, 2) : 0;
    
    return [
        'apache' => $apacheRunning,
        'mysql' => $mysqlRunning,
        'disk_percent' => $diskPercent,
        'memory_percent' => $memoryPercent,
        'timestamp' => date('Y-m-d H:i:s'),
        'uptime' => getUptime()
    ];
}

function getUptime() {
    $uptime = shell_exec('wmic os get lastbootuptime /value');
    $lines = explode("\n", $uptime);
    foreach ($lines as $line) {
        if (strpos($line, 'LastBootUpTime=') === 0) {
            $bootTime = str_replace('LastBootUpTime=', '', trim($line));
            $bootTimestamp = strtotime($bootTime);
            $uptimeSeconds = time() - $bootTimestamp;
            $hours = floor($uptimeSeconds / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }
    return "Unknown";
}

function getNetworkConnections() {
    $connections = [];
    
    // Get active network connections
    $netstat = shell_exec('netstat -an | findstr :80');
    $lines = explode("\n", $netstat);
    
    $activeConnections = 0;
    foreach ($lines as $line) {
        if (strpos($line, 'LISTENING') !== false && strpos($line, ':80') !== false) {
            $activeConnections++;
        }
    }
    
    return [
        'active_connections' => $activeConnections,
        'port_80_listening' => $activeConnections > 0
    ];
}

$serverStatus = getServerStatus();
$networkInfo = getNetworkConnections();
$serverIP = getServerIP();

function getServerIP() {
    if (isset($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }
    return 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComLab Server Status Monitor</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border-left: 5px solid #007bff;
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-online {
            background: #28a745;
        }
        
        .status-offline {
            background: #dc3545;
        }
        
        .status-warning {
            background: #ffc107;
        }
        
        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .metric:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            font-weight: bold;
            color: #333;
        }
        
        .metric-value {
            color: #007bff;
            font-family: monospace;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .progress-warning {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }
        
        .progress-danger {
            background: linear-gradient(90deg, #dc3545, #e83e8c);
        }
        
        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1em;
            margin: 20px;
            transition: background 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: #0056b3;
        }
        
        .auto-refresh {
            text-align: center;
            padding: 20px;
            background: #e8f4fd;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏫 ComLab School Server Monitor</h1>
            <p>Real-time server status and network information</p>
        </div>
        
        <div class="status-grid">
            <div class="status-card">
                <h3>
                    <span class="status-indicator <?php echo $serverStatus['apache'] ? 'status-online' : 'status-offline'; ?>"></span>
                    Apache Web Server
                </h3>
                <div class="metric">
                    <span class="metric-label">Status:</span>
                    <span class="metric-value"><?php echo $serverStatus['apache'] ? 'Online' : 'Offline'; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Port 80:</span>
                    <span class="metric-value"><?php echo $networkInfo['port_80_listening'] ? 'Listening' : 'Not Listening'; ?></span>
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-indicator <?php echo $serverStatus['mysql'] ? 'status-online' : 'status-offline'; ?>"></span>
                    MySQL Database
                </h3>
                <div class="metric">
                    <span class="metric-label">Status:</span>
                    <span class="metric-value"><?php echo $serverStatus['mysql'] ? 'Online' : 'Offline'; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Active Connections:</span>
                    <span class="metric-value"><?php echo $networkInfo['active_connections']; ?></span>
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-indicator status-online"></span>
                    System Resources
                </h3>
                <div class="metric">
                    <span class="metric-label">Disk Usage:</span>
                    <span class="metric-value"><?php echo $serverStatus['disk_percent']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $serverStatus['disk_percent'] > 80 ? 'progress-danger' : ($serverStatus['disk_percent'] > 60 ? 'progress-warning' : ''); ?>" 
                         style="width: <?php echo $serverStatus['disk_percent']; ?>%"></div>
                </div>
                <div class="metric">
                    <span class="metric-label">Memory Usage:</span>
                    <span class="metric-value"><?php echo $serverStatus['memory_percent']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $serverStatus['memory_percent'] > 80 ? 'progress-danger' : ($serverStatus['memory_percent'] > 60 ? 'progress-warning' : ''); ?>" 
                         style="width: <?php echo $serverStatus['memory_percent']; ?>%"></div>
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-indicator status-online"></span>
                    Network Information
                </h3>
                <div class="metric">
                    <span class="metric-label">Server IP:</span>
                    <span class="metric-value"><?php echo $serverIP; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Uptime:</span>
                    <span class="metric-value"><?php echo $serverStatus['uptime']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Last Check:</span>
                    <span class="metric-value"><?php echo $serverStatus['timestamp']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="auto-refresh">
            <p>🔄 Auto-refreshing every 30 seconds</p>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Now</button>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
