<?php
/**
 * License Server - Database Manager
 * Alle Daten in MySQL statt JSON-Files!
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

class LicenseDB {
    private static $instance = null;
    private $conn = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Datenbank-Verbindung
    private function connect() {
        $config = $this->load_db_config();
        
        if (!$config) {
            return false;
        }
        
        try {
            $this->conn = new PDO(
                "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
                $config['user'],
                $config['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
            return true;
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // DB-Config aus Datei laden (einzige File die bleibt!)
    private function load_db_config() {
        $file = __DIR__ . '/../db-config.php';
        if (!file_exists($file)) {
            return false;
        }
        
        include $file;
        
        return array(
            'host' => DB_HOST ?? '',
            'name' => DB_NAME ?? '',
            'user' => DB_USER ?? '',
            'pass' => DB_PASS ?? '',
        );
    }
    
    // Connection abrufen
    public function getConnection() {
        return $this->conn;
    }
    
    // Tabellen erstellen
    public function createTables() {
        if (!$this->conn) return false;
        
        try {
            // Config-Tabelle
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS config (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    config_key VARCHAR(100) UNIQUE NOT NULL,
                    config_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Lizenzen-Tabelle
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS licenses (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    license_key VARCHAR(100) UNIQUE NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    domain VARCHAR(255),
                    max_items INT DEFAULT 20,
                    expires VARCHAR(50),
                    features TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_key (license_key),
                    INDEX idx_type (type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Pricing-Tabelle
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS pricing (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    package_type VARCHAR(50) UNIQUE NOT NULL,
                    price INT DEFAULT 0,
                    currency VARCHAR(10) DEFAULT '€',
                    label VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Logs-Tabelle
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    log_type VARCHAR(50),
                    message TEXT,
                    ip_address VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_type (log_type),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            return true;
        } catch (PDOException $e) {
            error_log('Failed to create tables: ' . $e->getMessage());
            return false;
        }
    }
    
    // Config speichern
    public function setConfig($key, $value) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = ?
            ");
            
            $value_json = is_array($value) ? json_encode($value) : $value;
            
            return $stmt->execute([$key, $value_json, $value_json]);
        } catch (PDOException $e) {
            error_log('Failed to set config: ' . $e->getMessage());
            return false;
        }
    }
    
    // Config laden
    public function getConfig($key, $default = null) {
        if (!$this->conn) return $default;
        
        try {
            $stmt = $this->conn->prepare("SELECT config_value FROM config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if (!$result) return $default;
            
            // Versuche JSON zu dekodieren
            $decoded = json_decode($result['config_value'], true);
            return $decoded !== null ? $decoded : $result['config_value'];
        } catch (PDOException $e) {
            return $default;
        }
    }
    
    // Alle Lizenzen
    public function getAllLicenses() {
        if (!$this->conn) return [];
        
        try {
            $stmt = $this->conn->query("SELECT * FROM licenses ORDER BY created_at DESC");
            $licenses = [];
            
            while ($row = $stmt->fetch()) {
                $licenses[$row['license_key']] = array(
                    'type' => $row['type'],
                    'domain' => $row['domain'],
                    'max_items' => (int)$row['max_items'],
                    'expires' => $row['expires'],
                    'features' => json_decode($row['features'], true) ?: [],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                );
            }
            
            return $licenses;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Einzelne Lizenz
    public function getLicense($key) {
        if (!$this->conn) return null;
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM licenses WHERE license_key = ?");
            $stmt->execute([strtoupper($key)]);
            $row = $stmt->fetch();
            
            if (!$row) return null;
            
            return array(
                'type' => $row['type'],
                'domain' => $row['domain'],
                'max_items' => (int)$row['max_items'],
                'expires' => $row['expires'],
                'features' => json_decode($row['features'], true) ?: [],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            );
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Lizenz speichern
    public function saveLicense($key, $data) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO licenses (license_key, type, domain, max_items, expires, features) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    type = VALUES(type),
                    domain = VALUES(domain),
                    max_items = VALUES(max_items),
                    expires = VALUES(expires),
                    features = VALUES(features)
            ");
            
            return $stmt->execute([
                strtoupper($key),
                $data['type'],
                $data['domain'],
                $data['max_items'],
                $data['expires'],
                json_encode($data['features'] ?? []),
            ]);
        } catch (PDOException $e) {
            error_log('Failed to save license: ' . $e->getMessage());
            return false;
        }
    }
    
    // Lizenz löschen
    public function deleteLicense($key) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("DELETE FROM licenses WHERE license_key = ?");
            return $stmt->execute([strtoupper($key)]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Pricing laden
    public function getPricing() {
        if (!$this->conn) {
            return [
                'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
                'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
                'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
            ];
        }
        
        try {
            $stmt = $this->conn->query("SELECT * FROM pricing");
            $pricing = [];
            
            while ($row = $stmt->fetch()) {
                $pricing[$row['package_type']] = array(
                    'price' => (int)$row['price'],
                    'currency' => $row['currency'],
                    'label' => $row['label'],
                );
            }
            
            // Fallback wenn leer
            if (empty($pricing)) {
                return [
                    'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
                    'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
                    'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
                ];
            }
            
            return $pricing;
        } catch (PDOException $e) {
            return [
                'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
                'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
                'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
            ];
        }
    }
    
    // Pricing speichern
    public function savePricing($pricing) {
        if (!$this->conn) return false;
        
        try {
            foreach ($pricing as $type => $data) {
                $stmt = $this->conn->prepare("
                    INSERT INTO pricing (package_type, price, currency, label) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        price = VALUES(price),
                        currency = VALUES(currency),
                        label = VALUES(label)
                ");
                
                $stmt->execute([
                    $type,
                    $data['price'],
                    $data['currency'],
                    $data['label'],
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log('Failed to save pricing: ' . $e->getMessage());
            return false;
        }
    }
    
    // Log schreiben
    public function log($type, $message) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO logs (log_type, message, ip_address) 
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $type,
                $message,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Alte Logs löschen (> 30 Tage)
    public function cleanOldLogs($days = 30) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            return $stmt->execute([$days]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
