<?php
/**
 * Database Connection & Initialization
 * Uses SQLite by default for zero-config out-of-the-box operation.
 * Can be switched to MySQL easily by setting DB_DRIVER to 'mysql'.
 */

define('DB_DRIVER', 'sqlite'); // Options: 'sqlite' or 'mysql'

// MySQL configuration (if switched)
define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_PORT', '3306');
define('MYSQL_DB', 'task_manager');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');

function getDatabaseConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $dbDir = __DIR__ . '/../data';
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            $dbPath = $dbDir . '/tasks.db';
            $dsn = "sqlite:" . $dbPath;
            $pdo = new PDO($dsn);
        } else {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DB . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Auto-initialize tables
        initDatabase($pdo);

        return $pdo;
    } catch (PDOException $e) {
        require_once __DIR__ . '/response.php';
        sendResponse(500, false, "Database connection error: " . $e->getMessage());
        exit;
    }
}

function initDatabase(PDO $pdo): void {
    if (DB_DRIVER === 'sqlite') {
        $query = "
            CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                category TEXT DEFAULT 'General',
                priority TEXT DEFAULT 'medium',
                status TEXT DEFAULT 'pending',
                due_date TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ";
    } else {
        $query = "
            CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50) DEFAULT 'General',
                priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
                due_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }

    $pdo->exec($query);

    // Seed default sample tasks if table is empty
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $rowCount = (int)$countStmt->fetch()['count'];

    if ($rowCount === 0) {
        $seedStmt = $pdo->prepare("
            INSERT INTO tasks (title, description, category, priority, status, due_date)
            VALUES (:title, :description, :category, :priority, :status, :due_date)
        ");

        $sampleTasks = [
            [
                'title' => 'Design the application database schema',
                'description' => 'Structure SQLite & MySQL tables with indexes, types, and constraints.',
                'category' => 'Work',
                'priority' => 'high',
                'status' => 'completed',
                'due_date' => date('Y-m-d', strtotime('+1 day'))
            ],
            [
                'title' => 'Build RESTful API in PHP',
                'description' => 'Implement endpoints for GET, POST, PUT, PATCH, and DELETE operations with input validation.',
                'category' => 'Work',
                'priority' => 'high',
                'status' => 'in_progress',
                'due_date' => date('Y-m-d', strtotime('+2 days'))
            ],
            [
                'title' => 'Create a modern UI with responsive styling',
                'description' => 'Include dynamic counters, filter chips, modal windows, and toast alerts.',
                'category' => 'Design',
                'priority' => 'medium',
                'status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+3 days'))
            ],
            [
                'title' => 'Prepare project presentation and documentation',
                'description' => 'Write a clear setup guide and review all CRUD features for delivery.',
                'category' => 'General',
                'priority' => 'low',
                'status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+5 days'))
            ]
        ];

        foreach ($sampleTasks as $task) {
            $seedStmt->execute($task);
        }
    }
}
