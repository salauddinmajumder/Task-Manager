<?php
// --- API Endpoint for TaskMaster ---

require_once 'db_config.php'; // Include DB connection

header('Content-Type: application/json'); // Set response type

$action = null;
$userId = null;
$requestData = []; // Combined data source

// Check Content-Type and decode payload accordingly
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $jsonPayload = file_get_contents('php://input');
    $requestData = json_decode($jsonPayload, true) ?: []; // Use empty array on decode error
    $action = $requestData['action'] ?? '';
    $userId = filter_var($requestData['userId'] ?? null, FILTER_VALIDATE_INT);
} else {
    // Assume form data (GET or POST)
    $requestData = $_REQUEST; // Use $_REQUEST for combined GET/POST
    $action = $requestData['action'] ?? '';
    $userId = filter_var($requestData['userId'] ?? null, FILTER_VALIDATE_INT);
}

// --- Helper function to get or create user ---
function getOrCreateUser($pdo, $username) {
    $username = trim($username);
    if (empty($username)) {
        error_log("Attempt to get/create user with empty username.");
        return null; // Cannot operate without username
    }
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            return $user['user_id'];
        } else {
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
            $stmt->execute([$username]);
            return $pdo->lastInsertId(); // Return the new user ID
        }
    } catch (PDOException $e) {
        error_log("User Get/Create Error for username '{$username}': " . $e->getMessage());
        return null; // Indicate error
    }
}

// --- API Actions ---
switch ($action) {
    case 'getUserAndTasks':
        // This action specifically uses GET parameter
        $username = trim($_GET['username'] ?? '');
        if (empty($username)) {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Username is required.']);
             exit();
        }

        $currentUserId = getOrCreateUser($pdo, $username);

        if (!$currentUserId) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not retrieve or create user.']);
            exit();
        }

        try {
            // Fetch tasks for this user, ordered by sort_order then creation date
            $stmt = $pdo->prepare("SELECT task_id as id, text, priority, completed, created_at, sort_order
                                   FROM tasks
                                   WHERE user_id = ?
                                   ORDER BY sort_order ASC, created_at DESC");
            $stmt->execute([$currentUserId]);
            $tasks = $stmt->fetchAll();

            // Convert 'completed' from 0/1 to boolean for JS
            $tasks = array_map(function($task) {
                $task['completed'] = (bool)$task['completed'];
                // Ensure id is string for consistency with previous JS (Date.now().toString())
                $task['id'] = (string)$task['id'];
                return $task;
            }, $tasks);


            echo json_encode(['success' => true, 'userId' => $currentUserId, 'tasks' => $tasks]);

        } catch (PDOException $e) {
            error_log("Get Tasks Error for user ID {$currentUserId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching tasks.']);
        }
        break;

    case 'addTask':
        // Uses $userId (from top) and $requestData
        if (!$userId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'User ID required.']); exit(); }
        $text = trim($requestData['text'] ?? '');
        $priority = $requestData['priority'] ?? 'medium';
        if (empty($text)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Task text cannot be empty.']); exit(); }
        if (!in_array($priority, ['low', 'medium', 'high'])) { $priority = 'medium'; } // Validate priority

        try {
             // Find the current highest sort order for this user
             $stmt_order = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM tasks WHERE user_id = ?");
             $stmt_order->execute([$userId]);
             $result = $stmt_order->fetch(); // Fetch the row
             $max_order = ($result && $result['max_order'] !== null) ? (int)$result['max_order'] : -1; // Handle null case correctly
             $new_sort_order = $max_order + 1; // Start at 0 if no tasks, else increment

            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, text, priority, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $text, $priority, $new_sort_order]);
            $newTaskId = $pdo->lastInsertId();

             // Fetch the created task to get the timestamp
             $stmt_fetch = $pdo->prepare("SELECT task_id as id, text, priority, completed, created_at, sort_order FROM tasks WHERE task_id = ?");
             $stmt_fetch->execute([$newTaskId]);
             $newTask = $stmt_fetch->fetch();

             if ($newTask) {
                $newTask['id'] = (string)$newTask['id'];
                $newTask['completed'] = (bool)$newTask['completed'];
                 echo json_encode(['success' => true, 'task' => $newTask]);
             } else {
                 throw new Exception("Failed to fetch newly created task.");
             }

        } catch (PDOException | Exception $e) {
            error_log("Add Task Error for user ID {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error adding task.']);
        }
        break;

    case 'updateTask':
         // Uses $userId (from top) and $requestData
         if (!$userId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'User ID required.']); exit(); }
        $taskId = filter_var($requestData['taskId'] ?? null, FILTER_VALIDATE_INT);
        if (!$taskId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Task ID required.']); exit(); }

        $updates = [];
        $params = ['task_id' => $taskId, 'user_id' => $userId];

        // Check which fields are being updated
        if (array_key_exists('text', $requestData)) {
            $text = trim($requestData['text']);
            if (!empty($text)) {
                $updates[] = "text = :text";
                $params['text'] = $text;
            } else {
                 http_response_code(400); echo json_encode(['success' => false, 'message' => 'Task text cannot be empty.']); exit();
            }
        }
        if (array_key_exists('priority', $requestData) && in_array($requestData['priority'], ['low', 'medium', 'high'])) {
            $updates[] = "priority = :priority";
            $params['priority'] = $requestData['priority'];
        }
        if (array_key_exists('completed', $requestData)) {
            $completed = filter_var($requestData['completed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($completed !== null) {
                 $updates[] = "completed = :completed";
                 $params['completed'] = $completed ? 1 : 0;
                 // Also update completed_at timestamp
                 $updates[] = "completed_at = :completed_at";
                 $params['completed_at'] = $completed ? date('Y-m-d H:i:s') : null;
            }
        }
         if (array_key_exists('sort_order', $requestData)) {
             $sort_order = filter_var($requestData['sort_order'], FILTER_VALIDATE_INT);
             // Allow 0 for sort order
             if ($sort_order !== false && $sort_order >= 0) {
                 $updates[] = "sort_order = :sort_order";
                 $params['sort_order'] = $sort_order;
             }
         }

        if (empty($updates)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'No valid update fields provided.']); exit();
        }

        try {
            $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE task_id = :task_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
            } else if ($stmt->rowCount() === 0) {
                // Task might not exist or belong to the user, or no actual change occurred
                 http_response_code(404); echo json_encode(['success' => false, 'message' => 'Task not found or no changes detected.']);
            }
            else {
                 http_response_code(500); echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
            }

        } catch (PDOException $e) {
            error_log("Update Task Error for task ID {$taskId}, user ID {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating task.']);
        }
        break;

     case 'reorderTasks':
         // Uses $userId (from top) and $requestData (which should be the decoded JSON)
         if (!$userId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'User ID required.']); exit(); }
         $orderedIds = $requestData['orderedIds'] ?? null; // Extract from JSON data

         if (!is_array($orderedIds)) { // Allow empty array if user clears all tasks via drag? No, should have at least one.
             http_response_code(400); echo json_encode(['success' => false, 'message' => 'Valid ordered task IDs array required.']); exit();
         }

         try {
             $pdo->beginTransaction();
             // Prepare statement outside the loop for efficiency
             $stmt = $pdo->prepare("UPDATE tasks SET sort_order = ? WHERE task_id = ? AND user_id = ?");
             foreach ($orderedIds as $index => $taskId) {
                 $filteredTaskId = filter_var($taskId, FILTER_VALIDATE_INT);
                 if ($filteredTaskId) {
                     $stmt->execute([$index, $filteredTaskId, $userId]);
                 } else {
                     // Log invalid ID in the array if necessary
                     error_log("Invalid task ID '{$taskId}' found during reorder for user ID {$userId}");
                 }
             }
             $pdo->commit();
             echo json_encode(['success' => true, 'message' => 'Tasks reordered successfully.']);

         } catch (PDOException $e) {
             $pdo->rollBack();
             error_log("Reorder Tasks Error for user ID {$userId}: " . $e->getMessage());
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error reordering tasks.']);
         }
         break;

    case 'deleteTask':
         // Uses $userId (from top) and $requestData
         if (!$userId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'User ID required.']); exit(); }
         $taskId = filter_var($requestData['taskId'] ?? null, FILTER_VALIDATE_INT);
         if (!$taskId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Task ID required.']); exit(); }

        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
            } else {
                // Task might not exist or belong to the user
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found or already deleted.']);
            }
        } catch (PDOException $e) {
            error_log("Delete Task Error for task ID {$taskId}, user ID {$userId}: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting task.']);
        }
        break;

    // Optional: Add an action to clear all tasks for a user during reset
    // case 'deleteAllUserTasks':
    //     if (!$userId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'User ID required.']); exit(); }
    //     try {
    //         $stmt = $pdo->prepare("DELETE FROM tasks WHERE user_id = ?");
    //         $stmt->execute([$userId]);
    //         echo json_encode(['success' => true, 'message' => 'All tasks for user deleted.', 'deletedCount' => $stmt->rowCount()]);
    //     } catch (PDOException $e) {
    //         error_log("Delete All Tasks Error for user ID {$userId}: " . $e->getMessage());
    //         http_response_code(500);
    //         echo json_encode(['success' => false, 'message' => 'Error deleting all user tasks.']);
    //     }
    //     break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

// Close connection explicitly (optional, PHP usually handles this)
$pdo = null;
?>