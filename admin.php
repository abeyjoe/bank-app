<?php
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php?error=unauthorized");
    exit();
}
require_once 'db_connect.php';
try {
    $sql_users = "SELECT * FROM users";
    $stmt_users = $pdo->query($sql_users);
    $users = $stmt_users->fetchAll();

} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME;?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --primary-color: #0d47a1;
            --text-color: #495057;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        .btn-action {
            width: 80px;
        }
        .table {
            background-color: #fff;
        }
        .message-modal, .transactions-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .message-modal-content, .transactions-modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            text-align: center;
        }
        .transactions-modal-content {
            max-width: 700px;
            margin: 5% auto;
        }
        .modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="javascript:void(0);">
                <i class="fa-solid fa-user-gear"></i> Admin Dashboard
            </a>
            <div class="collapse navbar-collapse justify-content-end">
                <a class="btn btn-danger" href="signout.php">
                    <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <main class="container my-5">
        <h2 class="fw-bold mb-4">Manage User Accounts</h2>
        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Account Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Balance (&#8358;)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>">
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td class="fw-bold"><?php echo $user['account_number']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo number_format($user['balance'], 2); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?>
                                        </span>
                                    </td>
                                    <td class="d-flex justify-content-center">
                                        <button class="btn btn-sm btn-info text-white me-1 btn-action" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', <?php echo $user['balance']; ?>, <?php echo $user['is_active']; ?>)">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-primary me-1 btn-action" onclick="viewTransactions(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')">
                                            <i class="fa-solid fa-exchange-alt"></i> History
                                        </button>
                                        <button class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-action" onclick="toggleSuspend(<?php echo $user['id']; ?>, <?php echo $user['is_active']; ?>)">
                                            <i class="fa-solid fa-ban"></i> <?php echo $user['is_active'] ? 'Suspend' : 'Unsuspend'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <!-- Edit User Modal -->
    <div id="editModal" class="message-modal">
        <div class="message-modal-content">
            <span class="modal-close" id="closeEditModal">&times;</span>
            <h5 class="fw-bold mb-3">Edit User Account</h5>
            <form id="edit-form">
                <input type="hidden" id="editUserId">
                <div class="mb-3">
                    <label for="editName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="editName" required>
                </div>
                <div class="mb-3">
                    <label for="editEmail" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="editEmail" required>
                </div>
                <div class="mb-3">
                    <label for="editBalance" class="form-label">Balance (&#8358;)</label>
                    <input type="number" class="form-control" id="editBalance" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="editStatus" class="form-label">Status</label>
                    <select class="form-select" id="editStatus" required>
                        <option value="1">Active</option>
                        <option value="0">Suspended</option>
                    </select>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="transactionsModal" class="transactions-modal">
        <div class="transactions-modal-content">
            <span class="modal-close" id="closeTransactionsModal">&times;</span>
            <h5 class="fw-bold mb-3">Transaction History for <span id="transaction-user-name"></span></h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount (&#8358;)</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-history-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="messageModal" class="message-modal">
        <div class="message-modal-content">
            <span class="modal-close" id="closeMessageModal">&times;</span>
            <div id="modal-body" class="p-3"></div>
        </div>
    </div>
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const API_URL = 'api.php';
            const editModal = $('#editModal');
            const messageModal = $('#messageModal');
            const transactionsModal = $('#transactionsModal');
            const modalBody = $('#modal-body');
            let pendingAction = null;

            // --- Custom Modal Functions ---
            $('#closeEditModal').on('click', hideEditModal);
            $('#closeMessageModal').on('click', hideMessageModal);
            $('#closeTransactionsModal').on('click', hideTransactionsModal);

            $(window).on('click', function(event) {
                if ($(event.target).is(editModal)) {
                    hideEditModal();
                }
                if ($(event.target).is(messageModal)) {
                    hideMessageModal();
                }
                if ($(event.target).is(transactionsModal)) {
                    hideTransactionsModal();
                }
            });

            function showMessageModal(message, isConfirmation = false) {
                let content = `<p class="fw-bold">${message}</p>`;
                if (isConfirmation) {
                    content += `
                        <div class="d-flex justify-content-center mt-3">
                            <button class="btn btn-danger me-2" id="confirmActionBtn">Confirm</button>
                            <button class="btn btn-secondary" onclick="hideMessageModal()">Cancel</button>
                        </div>
                    `;
                }
                modalBody.html(content);
                messageModal.show();
            }

            function hideMessageModal() {
                messageModal.hide();
                pendingAction = null;
            }

            function showEditModal(userId, name, email, balance, status) {
                $('#editUserId').val(userId);
                $('#editName').val(name);
                $('#editEmail').val(email);
                $('#editBalance').val(balance);
                $('#editStatus').val(status);
                editModal.show();
            }

            function hideEditModal() {
                editModal.hide();
            }

            function showTransactionsModal() {
                transactionsModal.show();
            }

            function hideTransactionsModal() {
                transactionsModal.hide();
            }
            window.viewTransactions = async function(userId, userName) {
                $('#transaction-user-name').text(userName);
                const tableBody = $('#transaction-history-table-body');
                tableBody.html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
                
                const response = await fetch(`${API_URL}?action=get_user_transactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const result = await response.json();

                if (result.success) {
                    tableBody.empty();
                    if (result.transactions.length > 0) {
                        result.transactions.forEach(tx => {
                            const row = `
                                <tr>
                                    <td>${new Date(tx.created_at).toLocaleDateString()}</td>
                                    <td>${tx.description}</td>
                                    <td class="${tx.type === 'credit' ? 'text-success' : 'text-danger'}">
                                        ${tx.type === 'credit' ? '+' : '-'}${parseFloat(tx.amount).toFixed(2)}
                                    </td>
                                    <td><span class="badge bg-secondary">${tx.type}</span></td>
                                </tr>
                            `;
                            tableBody.append(row);
                        });
                    } else {
                        tableBody.html('<tr><td colspan="4" class="text-center text-muted">No transactions found.</td></tr>');
                    }
                } else {
                    tableBody.html(`<tr><td colspan="4" class="text-center text-danger">${result.message}</td></tr>`);
                }

                showTransactionsModal();
            };

            window.toggleSuspend = async function(userId, currentStatus) {
                const newStatus = currentStatus ? 0 : 1;
                const actionText = newStatus ? 'Unsuspend' : 'Suspend';
                
                const confirmed = confirm(`Are you sure you want to ${actionText.toLowerCase()} this account?`);
                if (!confirmed) {
                    return;
                }

                const response = await fetch(`${API_URL}?action=toggle_user_status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, is_active: newStatus })
                });
                const result = await response.json();

                showMessageModal(`<p class="${result.success ? 'text-success' : 'text-danger'} fw-bold">${result.message}</p>`);
                
                if (result.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            };

            window.editUser = function(id, name, email, balance, status) {
                showEditModal(id, name, email, balance, status);
            };

            $('#edit-form').on('submit', async function(e) {
                e.preventDefault();
                
                const userId = $('#editUserId').val();
                const name = $('#editName').val();
                const email = $('#editEmail').val();
                const balance = parseFloat($('#editBalance').val());
                const status = parseInt($('#editStatus').val());

                const response = await fetch(`${API_URL}?action=update_user`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: userId,
                        name: name,
                        email: email,
                        balance: balance,
                        is_active: status
                    })
                });
                const result = await response.json();

                hideEditModal();
                showMessageModal(`<p class="${result.success ? 'text-success' : 'text-danger'} fw-bold">${result.message}</p>`);
                
                if (result.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            });
        })();
    </script>
</body>
</html>
