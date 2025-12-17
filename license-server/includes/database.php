<?php
/**
 * License Server - Database Manager
 * Version 2.0 - Sauber mit 5 Preismodellen
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

class LicenseDB {
    private static $instance = null;
    private $conn = null;
    
    // 5 Lizenz-Typen
    const TYPE_FREE = 'free';
    const TYPE_FREE_PLUS = 'free_plus';
    const TYPE_PRO = 'pro';
    const TYPE_PRO_PLUS = 'pro_plus';
    const TYPE_ULTIMATE = 'ultimate';
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
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
    
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Tabellen erstellen
     */
    public function createTables() {
        if (!$this->conn) return false;
        
        try {
            // Config
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS config (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    config_key VARCHAR(100) UNIQUE NOT NULL,
                    config_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Lizenzen
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
            
            // Pricing
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS pricing (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    package_type VARCHAR(50) UNIQUE NOT NULL,
                    price INT DEFAULT 0,
                    currency VARCHAR(10) DEFAULT '€',
                    label VARCHAR(100),
                    max_items INT DEFAULT 20,
                    features TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Logs
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
            
            // Standard-Pricing einfügen falls leer
            $this->initDefaultPricing();
            
            return true;
        } catch (PDOException $e) {
            error_log('Failed to create tables: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Standard-Pricing initialisieren
     */
    private function initDefaultPricing() {
        if (!$this->conn) return;
        
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM pricing");
            $row = $stmt->fetch();
            
            if ($row['count'] > 0) {
                return; // Bereits vorhanden
            }
            
            $defaults = array(
                array(
                    'type' => self::TYPE_FREE,
                    'price' => 0,
                    'currency' => '€',
                    'label' => 'FREE',
                    'max_items' => 20,
                    'features' => json_encode(array()),
                ),
                array(
                    'type' => self::TYPE_FREE_PLUS,
                    'price' => 15,
                    'currency' => '€',
                    'label' => 'FREE+',
                    'max_items' => 60,
                    'features' => json_encode(array()),
                ),
                array(
                    'type' => self::TYPE_PRO,
                    'price' => 29,
                    'currency' => '€',
                    'label' => 'PRO',
                    'max_items' => 200,
                    'features' => json_encode(array()),
                ),
                array(
                    'type' => self::TYPE_PRO_PLUS,
                    'price' => 49,
                    'currency' => '€',
                    'label' => 'PRO+',
                    'max_items' => 200,
                    'features' => json_encode(array('dark_mode', 'cart')),
                ),
                array(
                    'type' => self::TYPE_ULTIMATE,
                    'price' => 79,
                    'currency' => '€',
                    'label' => 'ULTIMATE',
                    'max_items' => 900,
                    'features' => json_encode(array('dark_mode', 'cart', 'unlimited_items')),
                ),
            );
            
            $stmt = $this->conn->prepare("
                INSERT INTO pricing (package_type, price, currency, label, max_items, features) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($defaults as $pricing) {
                $stmt->execute([
                    $pricing['type'],
                    $pricing['price'],
                    $pricing['currency'],
                    $pricing['label'],
                    $pricing['max_items'],
                    $pricing['features'],
                ]);
            }
        } catch (PDOException $e) {
            error_log('Failed to init default pricing: ' . $e->getMessage());
        }
    }
    
    /**
     * Pricing laden
     */
    public function getPricing() {
        if (!$this->conn) {
            return $this->getFallbackPricing();
        }
        
        try {
            $stmt = $this->conn->query("SELECT * FROM pricing ORDER BY price ASC");
            $pricing = array();
            
            while ($row = $stmt->fetch()) {
                $pricing[$row['package_type']] = array(
                    'price' => (int)$row['price'],
                    'currency' => $row['currency'],
                    'label' => $row['label'],
                    'max_items' => (int)$row['max_items'],
                    'features' => json_decode($row['features'], true) ?: array(),
                );
            }
            
            // Fallback wenn leer
            if (empty($pricing)) {
                return $this->getFallbackPricing();
            }
            
            return $pricing;
        } catch (PDOException $e) {
            return $this->getFallbackPricing();
        }
    }
    
    /**
     * Fallback Pricing
     */
    private function getFallbackPricing() {
        return array(
            self::TYPE_FREE => array(
                'price' => 0,
                'currency' => '€',
                'label' => 'FREE',
                'max_items' => 20,
                'features' => array(),
            ),
            self::TYPE_FREE_PLUS => array(
                'price' => 15,
                'currency' => '€',
                'label' => 'FREE+',
                'max_items' => 60,
                'features' => array(),
            ),
            self::TYPE_PRO => array(
                'price' => 29,
                'currency' => '€',
                'label' => 'PRO',
                'max_items' => 200,
                'features' => array(),
            ),
            self::TYPE_PRO_PLUS => array(
                'price' => 49,
                'currency' => '€',
                'label' => 'PRO+',
                'max_items' => 200,
                'features' => array('dark_mode', 'cart'),
            ),
            self::TYPE_ULTIMATE => array(
                'price' => 79,
                'currency' => '€',
                'label' => 'ULTIMATE',
                'max_items' => 900,
                'features' => array('dark_mode', 'cart', 'unlimited_items'),
            ),
        );
    }
    
    /**
     * Pricing speichern
     */
    public function savePricing($pricing) {
        if (!$this->conn) return false;
        
        try {
            foreach ($pricing as $type => $data) {
                $stmt = $this->conn->prepare("
                    INSERT INTO pricing (package_type, price, currency, label, max_items, features) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        price = VALUES(price),
                        currency = VALUES(currency),
                        label = VALUES(label),
                        max_items = VALUES(max_items),
                        features = VALUES(features)
                ");
                
                $stmt->execute([
                    $type,
                    $data['price'],
                    $data['currency'],
                    $data['label'],
                    $data['max_items'],
                    json_encode($data['features'] ?? array()),
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log('Failed to save pricing: ' . $e->getMessage());
            return false;
        }
    }
    
    // [Rest der Methoden bleiben gleich - getLicense, saveLicense, etc.]
    
    public function getAllLicenses() {
        if (!$this->conn) return array();
        
        try {
            $stmt = $this->conn->query("SELECT * FROM licenses ORDER BY created_at DESC");
            $licenses = array();
            
            while ($row = $stmt->fetch()) {
                $licenses[$row['license_key']] = array(
                    'type' => $row['type'],
                    'domain' => $row['domain'],
                    'max_items' => (int)$row['max_items'],
                    'expires' => $row['expires'],
                    'features' => json_decode($row['features'], true) ?: array(),
                    'created_at' => $row['created_at'],
                );
            }
            
            return $licenses;
        } catch (PDOException $e) {
            return array();
        }
    }
    
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
                'features' => json_decode($row['features'], true) ?: array(),
            );
        } catch (PDOException $e) {
            return null;
        }
    }
    
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
                $data['domain'] ?? '',
                $data['max_items'] ?? 20,
                $data['expires'] ?? 'lifetime',
                json_encode($data['features'] ?? array()),
            ]);
        } catch (PDOException $e) {
            error_log('Failed to save license: ' . $e->getMessage());
            return false;
        }
    }
    
    public function deleteLicense($key) {
        if (!$this->conn) return false;
        
        try {
            $stmt = $this->conn->prepare("DELETE FROM licenses WHERE license_key = ?");
            return $stmt->execute([strtoupper($key)]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
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
