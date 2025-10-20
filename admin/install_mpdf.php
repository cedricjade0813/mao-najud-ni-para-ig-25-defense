<?php
// mPDF Installation Helper for Clinic Management System
// This script helps install mPDF via Composer

echo "<h2>mPDF Installation Helper</h2>";

// Check if Composer is available
if (file_exists('../composer.json') || file_exists('../../composer.json')) {
    echo "<p>✅ Composer is available in your project.</p>";
} else {
    echo "<p>❌ Composer not found. Please install Composer first.</p>";
    echo "<p><strong>To install Composer:</strong></p>";
    echo "<ol>";
    echo "<li>Download from <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></li>";
    echo "<li>Follow the installation instructions for your system</li>";
    echo "</ol>";
}

// Check if mPDF is already installed
if (class_exists('Mpdf\Mpdf')) {
    echo "<p>✅ mPDF is already installed and ready to use!</p>";
    echo "<p>Your PDF export feature is fully functional.</p>";
} else {
    echo "<p>❌ mPDF is not installed yet.</p>";
    echo "<p><strong>To install mPDF:</strong></p>";
    echo "<ol>";
    echo "<li>Open your terminal/command prompt</li>";
    echo "<li>Navigate to your project root directory</li>";
    echo "<li>Run: <code>composer require mpdf/mpdf</code></li>";
    echo "<li>Wait for installation to complete</li>";
    echo "</ol>";
    
    echo "<p><strong>Alternative Manual Installation:</strong></p>";
    echo "<ol>";
    echo "<li>Download mPDF from <a href='https://github.com/mpdf/mpdf/releases' target='_blank'>GitHub</a></li>";
    echo "<li>Extract to your project directory</li>";
    echo "<li>Include the autoload file in your PHP scripts</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>Current Status</h3>";

// Test current PDF functionality
if (class_exists('Mpdf\Mpdf')) {
    echo "<p>✅ <strong>PDF Export:</strong> Full functionality with mPDF</p>";
} else {
    echo "<p>⚠️ <strong>PDF Export:</strong> Using browser print-to-PDF fallback</p>";
    echo "<p><em>Note: The fallback method works but has limited formatting options.</em></p>";
}

echo "<p>✅ <strong>CSV Export:</strong> Fully functional</p>";

echo "<hr>";
echo "<h3>Test PDF Export</h3>";
echo "<p><a href='reports.php?report_type=overview' class='btn btn-primary'>Go to Reports</a></p>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
.btn { display: inline-block; padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 5px; }
.btn:hover { background: #2563eb; }
ol, ul { margin-left: 20px; }
hr { margin: 20px 0; border: 1px solid #eee; }
</style>
