<?php
/**
 * Script Backup Database untuk Laravel
 * Jalankan: php backup_database.php
 * 
 * File backup akan disimpan di folder: storage/backups/
 */

// Load Laravel environment
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseBackup
{
    private $backupPath;
    private $filename;

    public function __construct()
    {
        $this->backupPath = storage_path('backups');
        $this->filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Buat folder jika belum ada
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function run()
    {
        echo "===========================================\n";
        echo "   SIMBAR Database Backup Tool\n";
        echo "===========================================\n\n";

        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            return $this->backupSQLite();
        } elseif ($connection === 'mysql') {
            return $this->backupMySQL();
        } elseif ($connection === 'pgsql') {
            return $this->backupPostgreSQL();
        }
        
        echo "Error: Database connection '$connection' not supported.\n";
        return false;
    }

    private function backupSQLite()
    {
        echo "Backing up SQLite database...\n";
        
        $dbPath = config('database.connections.sqlite.database');
        $backupFile = $this->backupPath . '/' . str_replace('.sql', '.sqlite', $this->filename);
        
        if (copy($dbPath, $backupFile)) {
            echo "✓ Backup saved to: $backupFile\n";
            echo "✓ Size: " . $this->formatBytes(filesize($backupFile)) . "\n";
            return true;
        }
        
        echo "✗ Backup failed!\n";
        return false;
    }

    private function backupMySQL()
    {
        echo "Backing up MySQL database...\n";
        
        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $port = config('database.connections.mysql.port', 3306);
        
        $backupFile = $this->backupPath . '/' . $this->filename;
        
        // Coba menggunakan mysqldump
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            echo "✓ Backup saved to: $backupFile\n";
            echo "✓ Size: " . $this->formatBytes(filesize($backupFile)) . "\n";
            return true;
        }
        
        // Fallback: PHP-based backup
        echo "mysqldump not available, using PHP fallback...\n";
        return $this->backupMySQLPHP($backupFile);
    }

    private function backupMySQLPHP($backupFile)
    {
        $sql = "-- SIMBAR Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- ------------------------------------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_$dbName";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            echo "  Exporting: $tableName\n";
            
            // Get CREATE TABLE statement
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
            
            // Get data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        if (is_null($value)) return 'NULL';
                        return "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sql .= "INSERT INTO `$tableName` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($backupFile, $sql)) {
            echo "✓ Backup saved to: $backupFile\n";
            echo "✓ Size: " . $this->formatBytes(filesize($backupFile)) . "\n";
            return true;
        }

        echo "✗ Backup failed!\n";
        return false;
    }

    private function backupPostgreSQL()
    {
        echo "Backing up PostgreSQL database...\n";
        
        $host = config('database.connections.pgsql.host');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');
        $port = config('database.connections.pgsql.port', 5432);
        
        $backupFile = $this->backupPath . '/' . $this->filename;
        
        putenv("PGPASSWORD=$password");
        
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s -f %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($backupFile)) {
            echo "✓ Backup saved to: $backupFile\n";
            echo "✓ Size: " . $this->formatBytes(filesize($backupFile)) . "\n";
            return true;
        }
        
        echo "✗ Backup failed! pg_dump may not be available.\n";
        return false;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Run backup
$backup = new DatabaseBackup();
$backup->run();

echo "\n===========================================\n";
echo "   Backup Complete!\n";
echo "===========================================\n";
