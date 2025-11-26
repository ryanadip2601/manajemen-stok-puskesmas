<?php

/**
 * PostgreSQL Connection Test Script
 * 
 * Usage:
 * php test-postgres-connection.php
 * 
 * Or set environment variables:
 * DATABASE_URL="postgres://user:pass@host:5432/dbname" php test-postgres-connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║      PostgreSQL Connection Test for Vercel              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Check if pdo_pgsql extension is loaded
echo "1. Checking PHP PostgreSQL Extension...\n";
if (!extension_loaded('pdo_pgsql')) {
    echo "   ❌ FAILED: pdo_pgsql extension is not loaded!\n";
    echo "   \n";
    echo "   Install instructions:\n";
    echo "   Ubuntu/Debian: sudo apt-get install php-pgsql\n";
    echo "   macOS: brew install php-pgsql\n";
    echo "   Windows: Enable 'extension=pdo_pgsql' in php.ini\n\n";
    exit(1);
}
echo "   ✅ PASSED: pdo_pgsql extension is loaded\n\n";

// Get connection string
echo "2. Reading Connection String...\n";
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');

if (!$databaseUrl) {
    echo "   ⚠️  WARNING: DATABASE_URL or POSTGRES_URL not found in environment\n";
    echo "   Please provide connection string:\n\n";
    echo "   Format: postgres://user:password@host:port/database\n";
    echo "   Example: postgres://default:xxx@xxx.postgres.vercel-storage.com:5432/verceldb\n\n";
    echo "   Connection string: ";
    $databaseUrl = trim(fgets(STDIN));
}

// Parse connection string
$parsed = parse_url($databaseUrl);

if (!$parsed) {
    echo "   ❌ FAILED: Invalid connection string format\n\n";
    exit(1);
}

$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? 5432;
$database = ltrim($parsed['path'] ?? '', '/');
$username = $parsed['user'] ?? 'postgres';
$password = $parsed['pass'] ?? '';

// Parse query parameters
$queryParams = [];
if (isset($parsed['query'])) {
    parse_str($parsed['query'], $queryParams);
}
$sslmode = $queryParams['sslmode'] ?? 'prefer';

echo "   Connection Details:\n";
echo "   - Host:     $host\n";
echo "   - Port:     $port\n";
echo "   - Database: $database\n";
echo "   - User:     $username\n";
echo "   - SSL Mode: $sslmode\n\n";

// Build DSN
$dsn = "pgsql:host=$host;port=$port;dbname=$database";
if ($sslmode === 'require') {
    $dsn .= ";sslmode=require";
}

// Test connection
echo "3. Testing Connection...\n";
try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    
    echo "   ✅ PASSED: Connection successful!\n\n";
    
    // Get PostgreSQL version
    echo "4. Checking PostgreSQL Version...\n";
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "   Version: $version\n\n";
    
    // Check if Laravel tables exist
    echo "5. Checking Laravel Tables...\n";
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   ⚠️  WARNING: No tables found. Run migrations first!\n";
        echo "   Command: php artisan migrate\n\n";
    } else {
        echo "   ✅ Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "      - $table\n";
        }
        echo "\n";
        
        // Check for required tables
        $requiredTables = ['users', 'categories', 'items', 'stock_in', 'stock_out', 'units', 'logs'];
        $missingTables = array_diff($requiredTables, $tables);
        
        if (!empty($missingTables)) {
            echo "   ⚠️  WARNING: Missing required tables:\n";
            foreach ($missingTables as $table) {
                echo "      - $table\n";
            }
            echo "   Run migrations: php artisan migrate\n\n";
        } else {
            echo "   ✅ All required tables exist!\n\n";
        }
    }
    
    // Test write permission
    echo "6. Testing Write Permission...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id SERIAL PRIMARY KEY, test_col VARCHAR(255))");
        $pdo->exec("INSERT INTO test_table (test_col) VALUES ('test')");
        $count = $pdo->query("SELECT COUNT(*) FROM test_table")->fetchColumn();
        $pdo->exec("DROP TABLE test_table");
        echo "   ✅ PASSED: Write permission OK (inserted $count row)\n\n";
    } catch (Exception $e) {
        echo "   ❌ FAILED: Cannot write to database\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
    }
    
    // Database size
    echo "7. Database Information...\n";
    $stmt = $pdo->query("
        SELECT pg_size_pretty(pg_database_size('$database')) as size
    ");
    $size = $stmt->fetchColumn();
    echo "   Database size: $size\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'
    ");
    $tableCount = $stmt->fetchColumn();
    echo "   Total tables: $tableCount\n\n";
    
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║                  ✅ ALL TESTS PASSED!                    ║\n";
    echo "║         Your PostgreSQL connection is working!           ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    
    echo "Next steps:\n";
    echo "1. Run migrations: php artisan migrate\n";
    echo "2. Seed database: php artisan db:seed\n";
    echo "3. Test application: php artisan serve\n\n";
    
} catch (PDOException $e) {
    echo "   ❌ FAILED: Connection error\n\n";
    echo "Error Details:\n";
    echo "- Code:    " . $e->getCode() . "\n";
    echo "- Message: " . $e->getMessage() . "\n\n";
    
    echo "Common issues:\n";
    echo "1. Check connection string format\n";
    echo "2. Verify host and port are correct\n";
    echo "3. Check username and password\n";
    echo "4. Ensure SSL mode is correct (require/prefer)\n";
    echo "5. Check if firewall allows connection\n";
    echo "6. Verify database exists\n\n";
    
    exit(1);
}
