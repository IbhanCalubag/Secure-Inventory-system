<?php

function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            require_once 'config.php';
            
            if (!file_exists(DB_PATH)) {
                if (is_writable(dirname(DB_PATH))) {
                    touch(DB_PATH);
                    chmod(DB_PATH, 0644);
                    error_log("Created database file: " . DB_PATH);
                } else {
                    throw new Exception("Cannot create database file. Check permissions.");
                }
            }
            
            if (!is_writable(DB_PATH)) {
                chmod(DB_PATH, 0644);
            }
            
            $db = new PDO("sqlite:" . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $db->exec("PRAGMA foreign_keys = ON");
            $db->exec("PRAGMA journal_mode = WAL");
            $db->exec("PRAGMA synchronous = NORMAL");
            $db->exec("PRAGMA temp_store = MEMORY");
            $db->exec("PRAGMA mmap_size = 268435456");
            
            $db->setAttribute(PDO::ATTR_TIMEOUT, 30);
            
        } catch (PDOException $e) {
            if (defined('ON_NEOCITIES') && ON_NEOCITIES && !DEBUG_MODE) {
                error_log("Database error: " . $e->getMessage());
                die("Database connection issue. Please try again later or contact support.");
            } else {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed: " . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log("Database setup error: " . $e->getMessage());
            die("Database setup error: " . $e->getMessage());
        }
    }
    
    return $db;
}


function backupDatabase() {
    try {
        $db_file = DB_PATH;
        $backup_file = __DIR__ . '/backups/database_' . date('Y-m-d_H-i-s') . '.sqlite';
        
        if (!file_exists(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0755, true);
        }
        
        if (copy($db_file, $backup_file)) {
            $backups = glob(__DIR__ . '/backups/database_*.sqlite');
            if (count($backups) > 5) {
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                for ($i = 5; $i < count($backups); $i++) {
                    @unlink($backups[$i]);
                }
            }
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Backup failed: " . $e->getMessage());
        return false;
    }
}


function checkDatabaseIntegrity() {
    try {
        $db = getDB();
        $result = $db->query("PRAGMA integrity_check");
        return $result->fetchColumn() === 'ok';
    } catch (Exception $e) {
        error_log("Integrity check failed: " . $e->getMessage());
        return false;
    }
}

function getDatabaseStats() {
    try {
        $db = getDB();
        $stats = [];
        
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
        foreach ($tables as $table) {
            $count = $db->query("SELECT COUNT(*) FROM " . $table['name'])->fetchColumn();
            $stats[$table['name']] = intval($count);
        }
        
        $stats['file_size'] = filesize(DB_PATH);
        $stats['file_path'] = DB_PATH;
        $stats['last_modified'] = date('Y-m-d H:i:s', filemtime(DB_PATH));
        
        return $stats;
    } catch (Exception $e) {
        error_log("Stats error: " . $e->getMessage());
        return [];
    }
}
?>
