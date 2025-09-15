<?php
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'config.php';
// Redirect to dashboard if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> & Wallet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
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
            display: none;
            padding-top: 80px;
        }
        #home-page {
            display: block;
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #42a5f5);
            color: #fff;
            padding: 80px 0;
            border-radius: var(--border-radius);
        }
        .feature-card {
            min-height: 200px;
            text-align: center;
            padding: 24px;
        }
        .feature-card .fa-solid {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
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

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#" onclick="showPage('home-page'); return false;">
                <i class="fa-solid fa-gem"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav" id="nav-links">
                    <li class="nav-item">
                        <a class="btn btn-outline-primary me-2" href="#" onclick="showPage('login-page'); return false;">Log In</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary" href="#" onclick="showPage('register-page'); return false;">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        
        <!-- Homepage Section -->
        <section id="home-page" class="page-section text-center">
            <div class="row align-items-center justify-content-center hero-section mb-5">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Your financial journey, simplified.</h1>
                    <p class="lead mb-4">Securely manage your money, pay bills, and invest with our intuitive digital platform.</p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <button class="btn btn-outline-light btn-lg px-4" onclick="showPage('register-page')">Get Started</button>
                        <button class="btn btn-light btn-lg px-4" onclick="showPage('login-page')">Log In Now</button>
                    </div>
                </div>
            </div>

            <h2 class="text-center mb-5 fw-bold">Why choose <?php echo APP_NAME; ?>?</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4 text-start">
                <div class="col">
                    <div class="card feature-card h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fa-solid fa-shield-halved text-primary"></i>
                            <h5 class="card-title fw-bold">Top-tier Security</h5>
                            <p class="card-text">Your financial data is protected with state-of-the-art encryption and fraud monitoring.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card feature-card h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fa-solid fa-mobile-alt text-primary"></i>
                            <h5 class="card-title fw-bold">Mobile First</h5>
                            <p class="card-text">Manage your accounts on the go with our seamless and responsive web application.</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card feature-card h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fa-solid fa-piggy-bank text-primary"></i>
                            <h5 class="card-title fw-bold">Smart Saving Tools</h5>
                            <p class="card-text">Automate your savings and reach your financial goals faster with smart insights.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Login Section -->
        <section id="login-page" class="page-section">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card p-4">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4 fw-bold">Customer Login</h3>

                            <!-- Message Placeholder -->
                            <div id="loginMessageContainer" class="alert alert-info d-none" role="alert"></div>
                            <div id="loginErrorMessage" class="alert alert-danger d-none" role="alert"></div>
                            
                            <form id="login-form">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" placeholder="name@example.com" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Enter your password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                                </div>
                            </form>
                            <p class="text-center mt-3">Don't have an account? <a href="#" onclick="showPage('register-page'); return false;">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Registration Section -->
        <section id="register-page" class="page-section">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card p-4">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4 fw-bold">Customer Registration</h3>
                            <form id="register-form">
                                <div class="mb-3">
                                    <label for="registerName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="registerName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="registerEmail" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="registerEmail" required>
                                </div>
                                <div class="mb-3">
                                    <label for="registerPassword" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="registerPassword" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                                </div>
                            </form>
                            <p class="text-center mt-3">Already have an account? <a href="#" onclick="showPage('login-page'); return false;">Log In here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Custom Modal for Messages -->
    <div id="messageModal" class="message-modal">
        <div class="message-modal-content">
            <span class="message-modal-close" id="closeModal">&times;</span>
            <div id="modal-body" class="p-3">
                <!-- Message content will be injected here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0 text-muted">&copy; <?php echo date('Y') ?> <?php echo APP_NAME; ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        (function() {
            const API_URL = 'api.php';
            const pageSections = $('.page-section');
            const messageModal = $('#messageModal');
            const modalBody = $('#modal-body');

            // --- UI and Page Navigation ---
            window.showPage = function(pageId) {
                pageSections.hide();
                $('#' + pageId).show();
                // Clear all message alerts when changing pages
                $('.alert').addClass('d-none').text('');
            };

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

            // --- Form Handlers ---
            $('#register-form').on('submit', async function(e) {
                e.preventDefault();
                const name = $('#registerName').val();
                const email = $('#registerEmail').val();
                const password = $('#registerPassword').val();
                
                const response = await fetch(`${API_URL}?action=register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, password })
                });
                const result = await response.json();

                showMessageModal(`<p class="${result.success ? 'text-success' : 'text-danger'} fw-bold">${result.message}</p>`);
                
                if (result.success) {
                    $('#register-form')[0].reset();
                    setTimeout(() => showPage('login-page'), 2000);
                }
            });

            $('#login-form').on('submit', async function(e) {
                e.preventDefault();
                const email = $('#loginEmail').val();
                const password = $('#loginPassword').val();

                // Show loading message
                const loginMessageContainer = $('#loginMessageContainer');
                loginMessageContainer.text('Logging in...').removeClass('alert-danger d-none').addClass('alert-info').show();

                const loginErrorMessageDiv = $('#loginErrorMessage');
                loginErrorMessageDiv.addClass('d-none').text('');

                try {
                    const response = await fetch(`${API_URL}?action=login`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });
                    const result = await response.json();

                    loginMessageContainer.hide();

                    if (result.success) {
                        loginMessageContainer.text('Login successful! Redirecting...').removeClass('alert-danger alert-info').addClass('alert-success').show();
                        setTimeout(() => window.location.href = 'dashboard.php', 1500);
                    } else {
                        loginErrorMessageDiv.text(result.message).removeClass('d-none').show();
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    loginMessageContainer.hide();
                    loginErrorMessageDiv.text('An unexpected error occurred. Please try again.').removeClass('d-none').show();
                }
            });


            // --- URL Parameter Handling for Login Errors ---
            // This function runs on page load and checks the URL for a specific error parameter
            function handleUrlParameters() {
                const urlParams = new URLSearchParams(window.location.search);
                const error = urlParams.get('error');

                if (error) {
                    showPage('login-page');
                    const loginErrorMessageDiv = $('#loginErrorMessage');
                    loginErrorMessageDiv.removeClass('d-none');

                    if (error === 'invalid_credentials') {
                        loginErrorMessageDiv.text('Invalid email or password.');
                    } else if (error === 'account_suspended') {
                        loginErrorMessageDiv.text('Your account has been suspended. Please contact customer support for assistance.');
                    }
                }
            }
            
            // Call the function on page load
            handleUrlParameters();
        })();
    </script>
</body>
</html>
