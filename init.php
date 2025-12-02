<?php


require_once 'config.php';
require_once 'db.php';

$run_init = isset($_GET['init']) && $_GET['init'] === 'true';
$force = isset($_GET['force']) && $_GET['force'] === 'true';

$allowed = false;
$secret_key = 'YOUR_SECRET_KEY_HERE'; 

if ($run_init) {
    if (isset($_GET['key']) && $_GET['key'] === $secret_key) {
        $allowed = true;
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
        $allowed = true;
    } elseif (defined('ON_NEOCITIES') && ON_NEOCITIES && !IS_PRODUCTION) {
        $allowed = true;
    }
}

if (!$run_init || !$allowed) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Secure Inventory System</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
            .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; }
            .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Secure Inventory System</h1>
            <div class='alert'>
                <strong>Note:</strong> Database initialization requires a secret key.
            </div>
            <p>
                <a href='index.php' class='btn'>Go to Home</a>
                <a href='login.php' class='btn'>Login</a>
                <a href='register.php' class='btn'>Register</a>
            </p>
        </div>
    </body>
    </html>";
    exit;
}


try {
    $db = getDB();
    
    if (function_exists('backupDatabase')) {
        if (backupDatabase()) {
            echo "<div style='color: green;'>✓ Database backed up successfully</div>";
        }
    }
    
    $existing_tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Database Initialization</h1>";
    echo "<div style='max-width: 800px; margin: 0 auto; padding: 20px;'>";
    
    if (!in_array('users', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            login_count INTEGER DEFAULT 0,
            locked_until DATETIME NULL,
            password_changed DATETIME DEFAULT CURRENT_TIMESTAMP,
            two_factor_secret TEXT NULL,
            two_factor_enabled INTEGER DEFAULT 0,
            favorite_movie TEXT NULL
        )");
        echo "<div style='color: green;'>✓ Created users table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Users table already exists</div>";
    }
    
    if (!in_array('products', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            description TEXT,
            sku TEXT UNIQUE,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        echo "<div style='color: green;'>✓ Created products table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Products table already exists</div>";
    }
    
    if (!in_array('security_logs', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS security_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            description TEXT NOT NULL,
            user_id INTEGER,
            ip_address TEXT NOT NULL,
            user_agent TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        echo "<div style='color: green;'>✓ Created security_logs table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Security_logs table already exists</div>";
    }
    
    if (!in_array('login_attempts', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            attempts INTEGER DEFAULT 1,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<div style='color: green;'>✓ Created login_attempts table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Login_attempts table already exists</div>";
    }
    
    if (!in_array('audit_logs', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            resource_type TEXT NOT NULL,
            resource_id INTEGER,
            old_values TEXT,
            new_values TEXT,
            ip_address TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        echo "<div style='color: green;'>✓ Created audit_logs table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Audit_logs table already exists</div>";
    }
    
    if (!in_array('rate_limits', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            identifier TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            timestamp INTEGER NOT NULL
        )");
        echo "<div style='color: green;'>✓ Created rate_limits table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Rate_limits table already exists</div>";
    }
    
    if (!in_array('password_history', $existing_tables) || $force) {
        $db->exec("CREATE TABLE IF NOT EXISTS password_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        echo "<div style='color: green;'>✓ Created password_history table</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Password_history table already exists</div>";
    }
    
    $indexes = [
        'idx_security_logs_timestamp' => 'security_logs(timestamp)',
        'idx_security_logs_type' => 'security_logs(type)',
        'idx_audit_logs_timestamp' => 'audit_logs(timestamp)',
        'idx_login_attempts_email' => 'login_attempts(email)',
        'idx_rate_limits_timestamp' => 'rate_limits(timestamp)',
        'idx_password_history_user' => 'password_history(user_id)',
        'idx_products_user_id' => 'products(user_id)',
        'idx_users_email' => 'users(email)'
    ];
    
    foreach ($indexes as $index_name => $index_def) {
        try {
            $db->exec("CREATE INDEX IF NOT EXISTS $index_name ON $index_def");
            echo "<div style='color: green;'>✓ Created index: $index_name</div>";
        } catch (Exception $e) {
            echo "<div style='color: orange;'>⚠ Could not create index $index_name: " . $e->getMessage() . "</div>";
        }
    }
    
    $admin_email = 'admin@secured-inventory.com';
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    $admin_exists = $stmt->fetch();
    
    if (!$admin_exists) {
        $password = password_hash('SecureAdmin123!', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Security Administrator', $admin_email, $password, 'admin']);
        
        $admin_id = $db->lastInsertId();
        $stmt = $db->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
        $stmt->execute([$admin_id, $password]);
        
        echo "<div style='color: green;'>✓ Created default admin user</div>";
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>Admin Credentials:</strong><br>";
        echo "Email: admin@secured-inventory.com<br>";
        echo "Password: SecureAdmin123!<br>";
        echo "<small>Change this password immediately after login!</small>";
        echo "</div>";
    } else {
        echo "<div style='color: blue;'>ⓘ Admin user already exists</div>";
    }
    
    $final_tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $expected_tables = ['users', 'products', 'security_logs', 'login_attempts', 'audit_logs', 'rate_limits', 'password_history'];
    
    echo "<hr>";
    echo "<h3>Database Status</h3>";
    echo "<div style='background: #e7f7ff; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Existing Tables:</strong> " . implode(', ', $final_tables) . "<br>";
    
    $missing = array_diff($expected_tables, $final_tables);
    if (empty($missing)) {
        echo "<div style='color: green;'><strong>✓ All required tables are present</strong></div>";
    } else {
        echo "<div style='color: red;'><strong>✗ Missing tables:</strong> " . implode(', ', $missing) . "</div>";
    }
    echo "</div>";
    
    echo "<h3>Database Statistics</h3>";
    $stats = [];
    foreach ($final_tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM " . $table)->fetchColumn();
        $stats[$table] = $count;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Records</th></tr>";
    foreach ($stats as $table => $count) {
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>✓ Database Initialization Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='login.php'>Login to your account</a></li>";
    echo "<li><a href='register.php'>Register a new account</a></li>";
    echo "<li><a href='index.php'>Go to dashboard</a></li>";
    echo "</ol>";
    echo "<p><strong>Security Note:</strong> Delete or rename this init.php file after setup.</p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red;'>";
    echo "<h3>Initialization Failed!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    if (DEBUG_MODE) {
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "<p>Please check your database file permissions.</p>";
    echo "</div>";
}
?>
