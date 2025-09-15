<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

function sendJsonResponse($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

if (!isset($_GET['action'])) {
    sendJsonResponse(false, 'Action not specified.', [], 400);
}

$action = $_GET['action'];

$allowed_actions = ['recharge', 'register', 'login', 'transaction', 'logout', 'admin_login', 'get_all_users', 'update_user', 'delete_user', 'get_user_transactions', 'toggle_user_status','check_account_status'];
if (!in_array($action, $allowed_actions)) {
    sendJsonResponse(false, 'Invalid action.', [], 400);
}

switch ($action) {
    case 'recharge':
        handleRecharge($pdo);
        break;
    case 'register':
        handleRegister($pdo);
        break;
    case 'login':
        handleLogin($pdo);
        break;
    case 'transaction':
        handleTransaction($pdo);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'admin_login':
        handleAdminLogin($pdo);
        break;
    case 'get_all_users':
        handleGetAllUsers($pdo);
        break;
    case 'update_user':
        handleUpdateUser($pdo);
        break;
    case 'delete_user':
        handleDeleteUser($pdo);
        break;
    case 'get_user_transactions':
        handleGetUserTransactions($pdo);
        break;
    case 'toggle_user_status':
        handleToggleUserStatus($pdo);
        break;
        case 'check_account_status':
        handleCheckUserStatus($pdo);
        break;
    default:
        sendJsonResponse(false, 'Invalid action.', [], 400);
        break;
}

/**
 * Handles wallet recharge via Paystack.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleRecharge($pdo) {
    // Get the request body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['reference']) || !isset($data['amount'])) {
        sendJsonResponse(false, 'Invalid request data.', [], 400);
    }

    $reference = $data['reference'];
    $amount_from_client = $data['amount'];

    // Paystack verification URL
    $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status != 200 || !$response) {
        sendJsonResponse(false, 'Payment verification failed. Please try again.', [], 500);
    }

    $paystack_response = json_decode($response, true);

    if (isset($paystack_response['status']) && $paystack_response['status'] === true) {
        $transaction_data = $paystack_response['data'];
        
        if ($transaction_data['status'] === 'success' && $transaction_data['amount'] === (int)$amount_from_client) {
            if (!isset($_SESSION['user_id'])) {
                sendJsonResponse(false, 'User session not found.', [], 401);
            }
            $user_id = $_SESSION['user_id'];
            $amount_to_add = $amount_from_client / 100;
            try {
                $sql_update = "UPDATE users SET balance = balance + ? WHERE id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$amount_to_add, $user_id]);
                $sql_insert = "INSERT INTO transactions (user_id, type, amount, description, created_at) VALUES (?, 'credit', ?, 'Wallet Recharge', NOW())";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([$user_id, $amount_to_add]);

                sendJsonResponse(true, 'Wallet successfully recharged.');
            } catch (PDOException $e) {
                sendJsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
            }
        } else {
            sendJsonResponse(false, 'Payment status is not successful or amount mismatch.', [], 400);
        }
    } else {
        sendJsonResponse(false, 'Transaction verification failed.', [], 400);
    }
}

/**
 * Generates a unique 10-digit account number.
 * It checks the database to ensure the number is not already in use.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return string The unique 10-digit account number.
 */
function generateUniqueAccountNumber($pdo) {
    do {
        $account_number = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
        $sql = "SELECT account_number FROM users WHERE account_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$account_number]);
        $exists = $stmt->fetch();
        
    } while ($exists);
    
    return $account_number;
}

function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        return;
    }

    try {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$email]);
        if ($stmt_check->rowCount() > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'User with this email already exists.']);
            return;
        }
        $account_number = generateUniqueAccountNumber($pdo);
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO users (name, email, password, account_number) VALUES (?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$name, $email, $hashed_password, $account_number]);

        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handles user login.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleLogin($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email'], $data['password'])) {
        sendJsonResponse(false, 'Email and password are required.', [], 400);
    }

    $email = $data['email'];
    $password = $data['password'];

    $sql = "SELECT id, name, email, password, balance, is_active FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_active'] == 0) {
            sendJsonResponse(false, 'Account has been suspended.', [], 403);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        sendJsonResponse(true, 'Login successful.', ['user' => [
            'id' => $user['id'], 
            'name' => $user['name'],
            'email' => $user['email'],
            'balance' => $user['balance']
        ]]);
    } else {
        sendJsonResponse(false, 'Invalid credentials.', [], 401);
    }
}

/**
 * Handles a financial transaction.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleTransaction($pdo) {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'User not logged in.', [], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    
    if (!isset($data['amount'], $data['description'])) {
        sendJsonResponse(false, 'Missing transaction data.', [], 400);
    }

    $amount = (float)$data['amount'];
    $description = $data['description'];
    $transaction_type = 'debit';
    $recipient =$data['recipient'];

    if(empty($recipient) || strlen($recipient) != 10) {
        sendJsonResponse(false, 'Please enter a valid Account Number.', [], 400);
    }
    $sql_check = "SELECT id FROM users WHERE account_number = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$recipient]);
    if ($stmt_check->rowCount() == 0) {
        sendJsonResponse(false, 'Account number does not exist. check and try again', [], 400);
    }
    $pdo->beginTransaction();
$receiver_data = $stmt_check->fetch();
    try {
        $sql = "SELECT balance FROM users WHERE id = ? AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            sendJsonResponse(false, 'User does not exist or is inactive.', [], 404);
        }

        $current_balance = $user['balance'];
        $new_balance = $current_balance - $amount;

        if ($new_balance < 0) {
            $pdo->rollBack();
            sendJsonResponse(false, 'Insufficient funds.', [], 400);
        }

        $sql_update = "UPDATE users SET balance = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$new_balance, $user_id]);

        $sql_insert_tx = "INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)";
        $stmt_insert_tx = $pdo->prepare($sql_insert_tx);
        $stmt_insert_tx->execute([$user_id, $transaction_type, $amount, $description]);

        $sql_update_recipient = "UPDATE users SET balance = balance + ? WHERE account_number = ?";
        $stmt_update_recipient = $pdo->prepare($sql_update_recipient);
        $stmt_update_recipient->execute([$amount, $recipient]);
        //create transaction history for the receiver
        $sql_insert_tx_recipient = "INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)";
        $stmt_insert_tx_recipient = $pdo->prepare($sql_insert_tx_recipient);
        $transaction_type = 'credit';
        $stmt_insert_tx_recipient->execute([$receiver_data['id'], $transaction_type, $amount, $description]);
        $pdo->commit();

        sendJsonResponse(true, 'Transaction successful.', ['new_balance' => $new_balance]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Transaction failed: ' . $e->getMessage(), [], 500);
    }
}

/**
 * Handles user and admin logout.
 */
function handleLogout() {
    session_destroy();
    sendJsonResponse(true, 'Logged out successfully.');
}

/**
 * Handles administrator login.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleAdminLogin($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $sql = "SELECT id, email, password FROM admins WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['is_admin'] = true;
        sendJsonResponse(true, 'Admin login successful.');
    } else {
        sendJsonResponse(false, 'Invalid credentials.', [], 401);
    }
}

/**
 * Fetches all user accounts. Requires admin privileges.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleGetAllUsers($pdo) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendJsonResponse(false, 'Unauthorized access.', [], 401);
    }

    try {
        $sql = "SELECT id, name, email, balance, is_active FROM users";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll();
        sendJsonResponse(true, 'Users fetched successfully.', ['users' => $users]);
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Failed to fetch users: ' . $e->getMessage(), [], 500);
    }
}

/**
 * Updates a user account. Requires admin privileges.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleUpdateUser($pdo) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendJsonResponse(false, 'Unauthorized access.', [], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'], $data['name'], $data['email'], $data['balance'], $data['is_active'])) {
        sendJsonResponse(false, 'Missing user data.', [], 400);
    }

    try {
        $sql = "UPDATE users SET name = ?, email = ?, balance = ?, is_active = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['name'], $data['email'], $data['balance'], $data['is_active'], $data['id']]);
        sendJsonResponse(true, 'User updated successfully.');
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Failed to update user: ' . $e->getMessage(), [], 500);
    }
}

/**
 * Deletes a user account. Requires admin privileges.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleDeleteUser($pdo) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendJsonResponse(false, 'Unauthorized access.', [], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        sendJsonResponse(false, 'Missing user ID.', [], 400);
    }

    try {
        $pdo->beginTransaction();
        
        $sql_tx = "DELETE FROM transactions WHERE user_id = ?";
        $stmt_tx = $pdo->prepare($sql_tx);
        $stmt_tx->execute([$data['id']]);

        $sql_user = "DELETE FROM users WHERE id = ?";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$data['id']]);

        $pdo->commit();
        sendJsonResponse(true, 'User deleted successfully.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Failed to delete user: ' . $e->getMessage(), [], 500);
    }
}

/**
 * Gets a user's transaction history. Requires admin privileges.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleGetUserTransactions($pdo) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendJsonResponse(false, 'Unauthorized access.', [], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    
    if (!$user_id) {
        sendJsonResponse(false, 'User ID not specified.', [], 400);
    }
    
    try {
        $sql = "SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll();
        
        sendJsonResponse(true, 'Transactions fetched successfully.', ['transactions' => $transactions]);
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Failed to fetch transactions: ' . $e->getMessage(), [], 500);
    }
}

/**
 * Toggles a user's active status. Requires admin privileges.
 *
 * @param PDO $pdo The PDO database connection object.
 */
function handleToggleUserStatus($pdo) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendJsonResponse(false, 'Unauthorized access.', [], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    $is_active = $data['is_active'] ?? null;

    if ($user_id === null || $is_active === null) {
        sendJsonResponse(false, 'Missing user ID or status.', [], 400);
    }

    try {
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$is_active, $user_id]);
        
        $message = $is_active ? 'Account has been unsuspended.' : 'Account has been suspended.';
        sendJsonResponse(true, $message);
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Failed to update user status: ' . $e->getMessage(), [], 500);
    }
}

    //handleCheckUserStatus
function handleCheckUserStatus($pdo) {
   header("Content-Type: application/json");
    // Get user_id from the session
    $user_id = $_SESSION['user_id'] ?? null;
    // Check if the user is logged in.
    if ($user_id === null) {
        echo json_encode([
            'success' => false, 
            'message' => 'User ID not found in session.', 
            'is_active' => false, 
            'status' => 'suspended'
        ]);
        exit(); // Crucial: Stop execution here
    }

     // Fetch the is_active status from the database
        $sql = "SELECT is_active FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $is_active = (bool)$user['is_active'];
            $status = $is_active ? 'active' : 'suspended';
            echo json_encode([
                'success' => true, 
                'message' => 'User status fetched successfully.', 
                'is_active' => $is_active, 
                'status' => $status
            ]);
            exit(); // Stop execution after sending valid JSON
        } else {
            // User ID exists in session but not in the database
            echo json_encode([
                'success' => false, 
                'message' => 'User not found in the database.', 
                'is_active' => false, 
                'status' => 'suspended'
            ]);
            exit(); // Stop execution
        }

}