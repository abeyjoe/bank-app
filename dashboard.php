<?php
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email']; 

try {
    $sql_user = "SELECT balance FROM users WHERE id = ?";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();

    if (!$user) {
        header("Location: logout.php");
        exit();
    }
    $balance = $user['balance'];
    $sql_tx = "SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
    $stmt_tx = $pdo->prepare($sql_tx);
    $stmt_tx->execute([$user_id]);
    $transactions = $stmt_tx->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME;?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --primary-color: #0d47a1; /* Dark Blue */
            --secondary-color: #bbdefb; /* Light Blue */
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
            display: flex;
            align-items: center;
        }
        .navbar-brand .fa-gem {
            margin-right: 8px;
            color: var(--primary-color);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 50px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #0b3c8f;
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            border-radius: 50px;
            transition: color 0.3s ease, border-color 0.3s ease;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: #fff;
        }
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .form-control {
            border-radius: 8px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 71, 161, 0.25);
            border-color: var(--primary-color);
        }
        .page-section {
            padding-top: 80px;
        }
        .welcome-text {
            font-size: 1.25rem;
            font-weight: 500;
        }
        .balance-card {
            background-color: var(--primary-color);
            color: #fff;
        }
        .transaction-table {
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        .table thead th {
            background-color: var(--light-bg);
        }
        .message-modal {
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
        .message-modal-content {
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
        .message-modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .message-modal-close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="javascript:void(0);">
                <i class="fa-solid fa-gem"></i> <?php echo APP_NAME;?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav" id="nav-links">
                    <li class="nav-item">
                        <a class="btn btn-danger" href="logout.php">
                            <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-2">
        <section id="dashboard-page" class="page-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><span id="welcome-message" class="welcome-text">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span></h2>
                <a class="btn btn-danger" href="logout.php">
                    <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
            
           <div class="row g-4 mb-4">
    <div class="col-lg-8 col-md-8 offset-md-2">
        <div class="card balance-card text-center">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">Current Balance</h5>
                    <button class="btn btn-sm btn-link text-white p-0" id="toggle-balance-btn" title="Toggle Balance Visibility">
                        <i class="fa-solid fa-eye-slash fa-2x"></i>
                    </button>
                </div>
                <h1 class="display-3 fw-bold" id="account-balance" data-balance="<?php echo number_format($balance, 2); ?>">
                    *****
                </h1>
            </div>
        </div>
    </div>
</div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card p-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">Make a New Transaction</h5>
                            <form id="transaction-form">
                                <div class="mb-3">
                                    <label for="recipient" class="form-label">Recipient Account</label>
                                    <input type="text" class="form-control" name="recipient" id="recipient" required placeholder="e.g. 1234567890">
                                </div>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount (&#8358;)</label>
                                    <input type="number" class="form-control" id="amount" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" required placeholder="e.g., groceries, salary">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Send Money</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
 <div class="col-lg-6 col-md-6">
                    <div class="card p-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">Recharge Your Account</h5>
                            <form id="recharge-form">
                                <div class="mb-3">
                                    <label for="recharge-amount" class="form-label">Amount (&#8358;)</label>
                                    <input type="number" class="form-control" id="recharge-amount" min="100" required placeholder="e.g., 5000">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">Recharge via Paystack</button>
                                </div>
                            </form>
                            <div id="recharge-message" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card py-4 mt-4">
                <div class="card-header fw-bold">Recent Transactions</div>
                <div class="card-body p-0">
                    <div class="table-responsive transaction-table">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Amount (&#8358;)</th>
                                    <th scope="col">Type</th>
                                </tr>
                            </thead>
                            <tbody id="transaction-history">
                                <?php if (count($transactions) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No transactions found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($tx['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                        <td class="<?php echo ($tx['type'] === 'credit') ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($tx['type'] === 'credit') ? '+' : '-'; ?><?php echo number_format($tx['amount'], 2); ?>
                                        </td>
                                        <td><span class="badge <?php echo ($tx['type'] === 'credit') ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($tx['type']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <div id="messageModal" class="message-modal">
        <div class="message-modal-content">
            <span class="message-modal-close" id="closeModal">&times;</span>
            <div id="modal-body" class="p-3">
                </div>
        </div>
    </div>

    <footer class="bg-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0 text-muted">&copy; <?php echo date('Y') ?> <?php echo APP_NAME;?>. All Rights Reserved.</p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        (function() {
        window.toggleBalanceVisibility = function() {
            const balanceElement = document.getElementById('account-balance');
            const toggleBtnIcon = document.getElementById('toggle-balance-btn').querySelector('i');
            // Check if the balance is currently masked
            const isHidden = balanceElement.textContent.trim() === '*****';

            if (isHidden) {
                const actualBalance = balanceElement.getAttribute('data-balance');
                balanceElement.textContent = `₦${actualBalance}`;
                toggleBtnIcon.classList.remove('fa-eye-slash');
                toggleBtnIcon.classList.add('fa-eye');
            } else {
                balanceElement.textContent = '*****';
                toggleBtnIcon.classList.remove('fa-eye');
                toggleBtnIcon.classList.add('fa-eye-slash');
            }
        };
        document.getElementById('toggle-balance-btn').addEventListener('click', toggleBalanceVisibility);
    })();
        (function() {
            const API_URL = 'api.php';
            const messageModal = $('#messageModal');
            const modalBody = $('#modal-body');
            const rechargeForm = $('#recharge-form');
            const rechargeMessage = $('#recharge-message');
            // --- Custom Modal Functions ---
            $('#closeModal').on('click', hideMessageModal);

            $(window).on('click', function(event) {
                if ($(event.target).is(messageModal)) {
                    hideMessageModal();
                }
            });

            /**
             * Shows the custom modal with a message.
             * @param {string} message The HTML content to display in the modal body.
             */
            function showMessageModal(message) {
                modalBody.html(message);
                messageModal.show();
            }

            function hideMessageModal() {
                messageModal.hide();
            }

            async function checkAccountStatus() {
                try {
                    const response = await fetch(`${API_URL}?action=check_account_status`, {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    const result = await response.json();
                    
                    if (result.status === 'suspended') {
                        const message = `<p class="text-danger fw-bold">Your account has been suspended.</p>
                                         <p>You will be logged out automatically. Please contact customer support for assistance.</p>`;
                        showMessageModal(message);
                        setTimeout(() => {
                            window.location.href = 'logout.php';
                        }, 5000); // 5 seconds
                    }
                } catch (error) {
                    console.error('Error checking account status:', error);
                }
            }
            // Check every 30 seconds
            checkAccountStatus();
            setInterval(checkAccountStatus, 30000); // Changed to 30s interval

            $('#transaction-form').on('submit', async function(e) {
                e.preventDefault();
                
                const amount = parseFloat($('#amount').val());
                const recipient = $('#recipient').val();
                const description = $('#description').val();
                
                const response = await fetch(`${API_URL}?action=transaction`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipient: recipient,
                        amount: amount,
                        description: description
                    })
                });
                const result = await response.json();

                if (result.success) {
                    showMessageModal('<p class="text-success fw-bold">Transaction successful!</p>');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessageModal(`<p class="text-danger fw-bold">${result.message}</p>`);
                }
            });
            rechargeForm.on('submit', function(e) {
                e.preventDefault();
                const amount = parseFloat($('#recharge-amount').val());
                const userEmail = "<?php echo htmlspecialchars($user_email); ?>";
                const amountInKobo = Math.floor(amount * 100);

                if (amount <= 0 || !userEmail) {
                    rechargeMessage.html('<div class="alert alert-danger" role="alert">Please enter a valid amount.</div>');
                    return;
                }
                let handler = PaystackPop.setup({
                    key: '<?php echo PAYSTACK_PUBLIC_KEY;?>', 
                    email: userEmail,
                    amount: amountInKobo, 
                    currency: 'NGN',
                    ref: 'KBW' + Math.floor((Math.random() * 1000000000) + 1),
                    metadata: {
                        custom_fields: [
                            {
                                display_name: "Amount",
                                variable_name: "amount",
                                value: amount
                            },
                        ]
                    },
                    callback: function(response){
                        rechargeMessage.html('<div class="alert alert-info" role="alert">Verifying payment...</div>');
                        verifyPayment(response.reference, amountInKobo); 
                    },
                    onClose: function(){
                        rechargeMessage.html('<div class="alert alert-warning" role="alert">Payment window closed.</div>');
                    }
                });
                handler.openIframe();
            });

            async function verifyPayment(reference, amount) {
                try {
                    const response = await fetch(`${API_URL}?action=recharge`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ reference: reference, amount: amount })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        rechargeMessage.html('<div class="alert alert-success" role="alert">Wallet recharged successfully!</div>');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        rechargeMessage.html(`<div class="alert alert-danger" role="alert">${result.message}</div>`);
                    }
                } catch (error) {
                    console.error('Error verifying payment:', error);
                    rechargeMessage.html('<div class="alert alert-danger" role="alert">An error occurred during payment verification.</div>');
                }
            }
        })();
    </script>
</body>
</html>