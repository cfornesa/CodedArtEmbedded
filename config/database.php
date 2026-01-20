<?php
/**
 * Database Connection Handler
 *
 * Provides PDO database connection with error handling and security features.
 * Supports both MySQL (production) and SQLite (development fallback).
 *
 * @package CodedArt
 * @subpackage Config
 */

// Ensure config is loaded
if (!defined('DB_HOST') && !defined('DB_NAME')) {
    die('Configuration not loaded. Please ensure config.php is included before database.php');
}

/**
 * Get database connection
 *
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    static $pdo = null;

    // Return existing connection if available (singleton pattern)
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Validate DB_TYPE is properly configured
        if (!defined('DB_TYPE')) {
            die('CONFIGURATION ERROR: DB_TYPE not defined in config.php. Please set DB_TYPE to "sqlite" or "mysql".');
        }

        // Build DSN based on database type
        if (DB_TYPE === 'sqlite') {
            // SQLite connection (for Replit development)

            // Safeguard: Warn if SQLite is used in production environment
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                error_log('WARNING: SQLite is being used in PRODUCTION environment. This should use MySQL instead!');
                if (defined('FORCE_SQLITE_IN_PRODUCTION') && FORCE_SQLITE_IN_PRODUCTION === true) {
                    error_log('FORCE_SQLITE_IN_PRODUCTION is enabled. Proceeding with SQLite.');
                } else {
                    die('CONFIGURATION ERROR: SQLite cannot be used in production. Please set DB_TYPE to "mysql" in config.php and configure MySQL credentials.');
                }
            }

            // Use DB_PATH if defined, otherwise fall back to DB_NAME
            $dbPath = defined('DB_PATH') ? DB_PATH : DB_NAME;

            if (empty($dbPath)) {
                die('CONFIGURATION ERROR: DB_PATH must be defined for SQLite. Example: DB_PATH = __DIR__ . "/../codedart.db"');
            }

            $dsn = 'sqlite:' . $dbPath;
            $pdo = new PDO($dsn);

            error_log('Database: Connected to SQLite at ' . $dbPath);

        } elseif (DB_TYPE === 'mysql') {
            // MySQL connection (for Hostinger production)

            // Safeguard: Check required MySQL credentials are set
            if (empty(DB_HOST) || empty(DB_NAME) || empty(DB_USER)) {
                die('CONFIGURATION ERROR: MySQL requires DB_HOST, DB_NAME, DB_USER, and DB_PASS to be configured in config.php.');
            }

            // Safeguard: Warn if MySQL is used in development with default credentials
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                error_log('INFO: MySQL is being used in DEVELOPMENT environment. Normally Replit uses SQLite.');
            }

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET ?? 'utf8mb4'
            );

            $port = defined('DB_PORT') ? DB_PORT : 3306;
            if ($port != 3306) {
                $dsn .= ';port=' . $port;
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . (DB_CHARSET ?? 'utf8mb4')
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            error_log('Database: Connected to MySQL at ' . DB_HOST . '/' . DB_NAME);

        } else {
            die('CONFIGURATION ERROR: DB_TYPE must be either "sqlite" or "mysql". Current value: ' . DB_TYPE);
        }

        // Set additional PDO attributes
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;

    } catch (PDOException $e) {
        // Log error
        error_log('Database Connection Error: ' . $e->getMessage());

        // Show user-friendly message (don't expose credentials)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die('Database connection failed: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please contact the administrator.');
        }
    }
}

/**
 * Execute a prepared SQL query
 *
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement Executed statement
 */
function dbQuery($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Database Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
        throw $e;
    }
}

/**
 * Fetch all rows from a query
 *
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array Array of results
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Fetch single row from a query
 *
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|false Single row or false if not found
 */
function dbFetchOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Insert a record and return the last insert ID
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int Last insert ID
 */
function dbInsert($table, $data) {
    $columns = array_keys($data);
    $values = array_values($data);

    $columnList = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";

    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    return $pdo->lastInsertId();
}

/**
 * Update records in a table
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param string $where WHERE clause (with placeholders)
 * @param array $whereParams Parameters for WHERE clause
 * @return int Number of affected rows
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    $setParts = [];
    $values = [];

    foreach ($data as $column => $value) {
        $setParts[] = "{$column} = ?";
        $values[] = $value;
    }

    $setClause = implode(', ', $setParts);
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

    // Merge data values with where parameters
    $allParams = array_merge($values, $whereParams);

    $stmt = dbQuery($sql, $allParams);
    return $stmt->rowCount();
}

/**
 * Delete records from a table
 *
 * @param string $table Table name
 * @param string $where WHERE clause (with placeholders)
 * @param array $whereParams Parameters for WHERE clause
 * @return int Number of affected rows
 */
function dbDelete($table, $where, $whereParams = []) {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = dbQuery($sql, $whereParams);
    return $stmt->rowCount();
}

/**
 * Check if a table exists
 *
 * @param string $table Table name
 * @return bool True if table exists
 */
function dbTableExists($table) {
    try {
        $pdo = getDBConnection();
        $result = $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Begin a database transaction
 */
function dbBeginTransaction() {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
}

/**
 * Commit a database transaction
 */
function dbCommit() {
    $pdo = getDBConnection();
    $pdo->commit();
}

/**
 * Rollback a database transaction
 */
function dbRollback() {
    $pdo = getDBConnection();
    $pdo->rollBack();
}

/**
 * Escape a value for use in SQL LIKE clause
 *
 * @param string $value Value to escape
 * @return string Escaped value
 */
function dbEscapeLike($value) {
    return str_replace(['%', '_'], ['\\%', '\\_'], $value);
}

/**
 * Sanitize table/column name (prevents SQL injection in dynamic queries)
 *
 * @param string $name Table or column name
 * @return string Sanitized name
 */
function dbSanitizeName($name) {
    // Only allow alphanumeric characters and underscores
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

/**
 * Get database statistics
 *
 * @return array Statistics about the database
 */
function dbGetStats() {
    $stats = [];

    try {
        $tables = ['aframe_art', 'c2_art', 'p5_art', 'threejs_art', 'users', 'activity_log'];

        foreach ($tables as $table) {
            if (dbTableExists($table)) {
                $result = dbFetchOne("SELECT COUNT(*) as count FROM {$table}");
                $stats[$table] = $result['count'] ?? 0;
            } else {
                $stats[$table] = 'Not created';
            }
        }

        return $stats;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Close database connection
 * (Usually not needed as PHP closes automatically, but provided for completeness)
 */
function dbClose() {
    global $pdo;
    $pdo = null;
}
