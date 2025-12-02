<?php
/**
 * One-time setup script for Neocities
 * Run this once, then delete it
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Neocities Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .step { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Neocities Setup Wizard</h1>";

// Step 1: Check PHP version
echo "<div class='step'>";
echo "<h3>Step 1: PHP Version Check</h3>";
$php_version = phpversion();
echo "PHP Version: <strong>$php_version</strong><br>";
if (version_compare($php_version, '7.4.0', '>=')) {
    echo "<span class='success'>✓ Compatible with Neocities</span>";
} else {
    echo "<span class='warning'>⚠ Neocities may use PHP 7.4 or higher</span>";
}
echo "</div>";

// Step 2: Check file permissions
echo "<div class='step'>";
echo "<h3>Step 2: File Permissions</h3>";
$files_to_check = [
    '.' => 'Current directory',
    'database.sqlite' => 'Database file',
    'php_errors.log' => 'Error log'
];

foreach ($files_to_check as $file => $desc) {
    if (file_exists($file)) {
        $writable = is_writable($file);
        echo "$desc: " . ($writable ? 
            "<span class='success'>✓ Writable</span>" : 
            "<span class='error'>✗ Not writable</span>") . "<br>";
    } else {
        echo "$desc: <span class='warning'>⚠ Does not exist</span><br>";
    }
}
echo "</div>";

// Step 3: Check SQLite support
echo "<div class='step'>";
echo "<h3>Step 3: SQLite Support</h3>";
if (extension_loaded('pdo_sqlite')) {
    echo "<span class='success'>✓ PDO_SQLite extension loaded</span><br>";
    
    // Test SQLite connection
    try {
        $test_db = new PDO('sqlite::memory:');
        echo "<span class='success'>✓ SQLite connection test successful</span>";
    } catch (Exception $e) {
        echo "<span class='error'>✗ SQLite connection failed: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
} else {
    echo "<span class='error'>✗ PDO_SQLite extension not loaded</span>";
}
echo "</div>";

// Step 4: Create necessary files
echo "<div class='step'>";
echo "<h3>Step 4: Create Required Files</h3>";

// Create database.sqlite if not exists
if (!file_exists('database.sqlite')) {
    if (touch('database.sqlite')) {
        chmod('database.sqlite', 0644);
        echo "<span class='success'>✓ Created database.sqlite</span><br>";
    } else {
        echo "<span class='error'>✗ Failed to create database.sqlite</span><br>";
    }
} else {
    echo "<span class='success'>✓ database.sqlite already exists</span><br>";
}

// Create php_errors.log if not exists
if (!file_exists('php_errors.log')) {
    if (touch('php_errors.log')) {
        chmod('php_errors.log', 0644);
        echo "<span class='success'>✓ Created php_errors.log</span><br>";
    } else {
        echo "<span class='error'>✗ Failed to create php_errors.log</span><br>";
    }
} else {
    echo "<span class='success'>✓ php_errors.log already exists</span><br>";
}

// Create security.log if not exists
if (!file_exists('security.log')) {
    if (touch('security.log')) {
        chmod('security.log', 0644);
        echo "<span class='success'>✓ Created security.log</span>";
    } else {
        echo "<span class='error'>✗ Failed to create security.log</span>";
    }
} else {
    echo "<span class='success'>✓ security.log already exists</span>";
}
echo "</div>";

// Step 5: Initialize database
echo "<div class='step'>";
echo "<h3>Step 5: Initialize Database</h3>";
echo "<p>Click the button below to initialize your database tables.</p>";
echo "<form method='get' action='init.php'>";
echo "<input type='hidden' name='init' value='true'>";
echo "<input type='hidden' name='key' value='neocities_setup_123'>"; // Temporary key
echo "<button type='submit' class='btn'>Initialize Database</button>";
echo "</form>";
echo "</div>";

// Step 6: Test the system
echo "<div class='step'>";
echo "<h3>Step 6: Test Your Installation</h3>";
echo "<ul>";
echo "<li><a href='login.php' target='_blank'>Test Login Page</a></li>";
echo "<li><a href='register.php' target='_blank'>Test Registration Page</a></li>";
echo "<li><a href='index.php' target='_blank'>Test Dashboard (will redirect to login)</a></li>";
echo "</ul>";
echo "</div>";

// Final instructions
echo "<div class='step' style='background: #d4edda;'>";
echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Important Next Steps:</strong></p>";
echo "<ol>";
echo "<li>After initializing the database, <strong>delete or rename</strong> this setup_neocities.php file</li>";
echo "<li>Delete or rename init.php after database setup</li>";
echo "<li>Change the default admin password immediately</li>";
echo "<li>Update the secret key in config.php</li>";
echo "</ol>";
echo "<p>Your site is now ready at: <strong>https://" . ($_SERVER['HTTP_HOST'] ?? 'yourusername') . ".neocities.org</strong></p>";
echo "</div>";

echo "</body></html>";
?>
