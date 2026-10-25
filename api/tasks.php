<?php
/**
 * RESTful API Endpoint for Tasks
 * Handles:
 *   - GET    /api/tasks.php            (Get all tasks or filter)
 *   - GET    /api/tasks.php?id={id}    (Get single task)
 *   - GET    /api/tasks.php?action=stats (Get dashboard metrics)
 *   - POST   /api/tasks.php            (Create a new task)
 *   - PUT    /api/tasks.php?id={id}    (Update an existing task)
 *   - PATCH  /api/tasks.php?id={id}    (Toggle status or partial update)
 *   - DELETE /api/tasks.php?id={id}    (Delete a task)
 */

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming request body
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        handleGet($pdo, $id);
        break;

    case 'POST':
        handlePost($pdo, $inputData);
        break;

    case 'PUT':
        handlePut($pdo, $id, $inputData);
        break;

    case 'PATCH':
        handlePatch($pdo, $id, $inputData);
        break;

    case 'DELETE':
        handleDelete($pdo, $id);
        break;

    default:
        sendResponse(405, false, "Method $method not allowed.");
        break;
}

// ----------------------------------------------------
// Handlers
// ----------------------------------------------------

/**
 * Handle GET requests
 */
function handleGet(PDO $pdo, ?int $id): void {
    // If asking for stats summary
    if (isset($_GET['action']) && $_GET['action'] === 'stats') {
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM tasks
        ");
        $stats = $statsStmt->fetch();
        sendResponse(200, true, 'Statistics retrieved successfully', [
            'total' => (int)($stats['total'] ?? 0),
            'completed' => (int)($stats['completed'] ?? 0),
            'in_progress' => (int)($stats['in_progress'] ?? 0),
            'pending' => (int)($stats['pending'] ?? 0),
        ]);
        return;
    }

    // Single item fetch
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $task = $stmt->fetch();

        if (!$task) {
            sendResponse(404, false, "Task with ID $id was not found.");
        }

        sendResponse(200, true, "Task retrieved successfully.", $task);
        return;
    }

    // List all with optional filters
    $sql = "SELECT * FROM tasks WHERE 1=1";
    $params = [];

    if (!empty($_GET['status']) && in_array($_GET['status'], ['pending', 'in_progress', 'completed'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }

    if (!empty($_GET['priority']) && in_array($_GET['priority'], ['low', 'medium', 'high'])) {
        $sql .= " AND priority = :priority";
        $params[':priority'] = $_GET['priority'];
    }

    if (!empty($_GET['category'])) {
        $sql .= " AND category = :category";
        $params[':category'] = trim($_GET['category']);
    }

    if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . trim($_GET['search']) . '%';
    }

    // Sorting
    $allowedSortFields = ['created_at', 'due_date', 'priority', 'title', 'status', 'id'];
    $sortBy = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSortFields)) ? $_GET['sort_by'] : 'created_at';
    $order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

    $sql .= " ORDER BY $sortBy $order";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    sendResponse(200, true, "Tasks fetched successfully.", [
        'count' => count($tasks),
        'tasks' => $tasks
    ]);
}

/**
 * Handle POST request (Create)
 */
function handlePost(PDO $pdo, array $data): void {
    $title = trim($data['title'] ?? '');
    if (empty($title)) {
        sendResponse(422, false, "Task title is required.");
    }

    $description = trim($data['description'] ?? '');
    $category = trim($data['category'] ?? 'General') ?: 'General';
    
    $priority = $data['priority'] ?? 'medium';
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'medium';
    }

    $status = $data['status'] ?? 'pending';
    if (!in_array($status, ['pending', 'in_progress', 'completed'])) {
        $status = 'pending';
    }

    $dueDate = !empty($data['due_date']) ? $data['due_date'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, category, priority, status, due_date, created_at, updated_at)
        VALUES (:title, :description, :category, :priority, :status, :due_date, datetime('now'), datetime('now'))
    ");

    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':priority' => $priority,
        ':status' => $status,
        ':due_date' => $dueDate
    ]);

    $newId = (int)$pdo->lastInsertId();

    $fetchStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetchStmt->execute([':id' => $newId]);
    $createdTask = $fetchStmt->fetch();

    sendResponse(201, true, "Task created successfully.", $createdTask);
}

/**
 * Handle PUT request (Full/Partial Update)
 */
function handlePut(PDO $pdo, ?int $id, array $data): void {
    if (!$id) {
        sendResponse(400, false, "Task ID is required for update.");
    }

    // Verify task exists
    $checkStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendResponse(404, false, "Task with ID $id not found.");
    }

    $title = isset($data['title']) ? trim($data['title']) : $existing['title'];
    if (empty($title)) {
        sendResponse(422, false, "Title cannot be empty.");
    }

    $description = isset($data['description']) ? trim($data['description']) : $existing['description'];
    $category = isset($data['category']) ? trim($data['category']) : $existing['category'];
    $priority = isset($data['priority']) && in_array($data['priority'], ['low', 'medium', 'high']) ? $data['priority'] : $existing['priority'];
    $status = isset($data['status']) && in_array($data['status'], ['pending', 'in_progress', 'completed']) ? $data['status'] : $existing['status'];
    $dueDate = array_key_exists('due_date', $data) ? (!empty($data['due_date']) ? $data['due_date'] : null) : $existing['due_date'];

    $updateStmt = $pdo->prepare("
        UPDATE tasks
        SET title = :title,
            description = :description,
            category = :category,
            priority = :priority,
            status = :status,
            due_date = :due_date,
            updated_at = datetime('now')
        WHERE id = :id
    ");

    $updateStmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':priority' => $priority,
        ':status' => $status,
        ':due_date' => $dueDate,
        ':id' => $id
    ]);

    $fetchStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetchStmt->execute([':id' => $id]);
    $updatedTask = $fetchStmt->fetch();

    sendResponse(200, true, "Task updated successfully.", $updatedTask);
}

/**
 * Handle PATCH request (Quick toggle status or field update)
 */
function handlePatch(PDO $pdo, ?int $id, array $data): void {
    if (!$id) {
        sendResponse(400, false, "Task ID is required.");
    }

    $checkStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendResponse(404, false, "Task with ID $id not found.");
    }

    $newStatus = $data['status'] ?? null;
    if ($newStatus && in_array($newStatus, ['pending', 'in_progress', 'completed'])) {
        $updateStmt = $pdo->prepare("
            UPDATE tasks
            SET status = :status,
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $updateStmt->execute([':status' => $newStatus, ':id' => $id]);
    }

    $fetchStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetchStmt->execute([':id' => $id]);
    $updatedTask = $fetchStmt->fetch();

    sendResponse(200, true, "Task status changed successfully.", $updatedTask);
}

/**
 * Handle DELETE request
 */
function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        sendResponse(400, false, "Task ID is required for deletion.");
    }

    $checkStmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :id");
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        sendResponse(404, false, "Task with ID $id was not found.");
    }

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $id]);

    sendResponse(200, true, "Task with ID $id deleted successfully.", ['id' => $id]);
}
