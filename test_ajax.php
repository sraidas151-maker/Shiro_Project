<?php
// test_ajax.php - Place this in your project root and run it
require_once 'db.php';

// Test if PHP can parse ajax_handler.php without executing it
$lint_output = [];
$lint_return = 0;
exec('php -l ajax_handler.php 2>&1', $lint_output, $lint_return);

if ($lint_return !== 0) {
    echo "<h2>❌ SYNTAX ERRORS FOUND in ajax_handler.php:</h2>";
    echo "<pre>";
    print_r($lint_output);
    echo "</pre>";
} else {
    echo "<h2>✅ No syntax errors in ajax_handler.php</h2>";
}

// Test database connection
if (isset($conn) && $conn->ping()) {
    echo "<p>✅ Database connected</p>";
    
    // Check if saved_posts table exists
    $result = $conn->query("SHOW TABLES LIKE 'saved_posts'");
    if ($result->num_rows > 0) {
        echo "<p>✅ saved_posts table exists</p>";
    } else {
        echo "<p>❌ saved_posts table MISSING - Create it now!</p>";
    }
} else {
    echo "<p>❌ Database NOT connected</p>";
}
?>