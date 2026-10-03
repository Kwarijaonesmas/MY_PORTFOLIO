<?php
// Use environment variables in hosted environments and XAMPP defaults locally.
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'mydb';
$db_port = (int) (getenv('DB_PORT') ?: 3306);

// First attempt connecting to MySQL server
$conn = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif; padding:20px; background:#f8d7da; color:#721c24; border-radius:8px; margin:30px;'>
            <h3>Database Connection Failed</h3>
            <p>Could not connect to MySQL server at <code>$db_host</code>.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
            <p>Please ensure XAMPP MySQL server is running.</p>
         </div>");
}

// Select database if it exists, otherwise provide easy link to run setup/seed
if (!@$conn->select_db($db_name)) {
    // If database does not exist yet, allow redirection to seed script if running in browser
    if (basename($_SERVER['PHP_SELF']) !== 'seed.php') {
        echo "<div style='font-family:sans-serif; padding:20px; background:#fff3cd; color:#856404; border-radius:8px; margin:30px; border:1px solid #ffeeba;'>
                <h3>Database '<code>$db_name</code>' Not Found</h3>
                <p>The database has not been initialized yet.</p>
                <p><a href='seed.php' style='display:inline-block; padding:10px 20px; background:#1a73e8; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;'>Click Here to Initialize Database & Seed Data</a></p>
              </div>";
        exit();
    }
}

// Ensure UTF-8 character set
$conn->set_charset("utf8mb4");
?>
