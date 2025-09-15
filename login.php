<?php include_once "config.php";?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME;?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --primary-color: #0d47a1; /* Dark Blue */
            --light-bg: #f0f2f5;
            --text-color: #495057;
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            border-radius: 50px;
            padding: 12px 24px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #0b3c8f;
            transform: translateY(-2px);
        }
        .logo-text {
            color: var(--primary-color);
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="text-center mb-4">
        <h1 class="logo-text fw-bold"><?php echo APP_NAME;?> <i class="fa-solid fa-gem"></i></h1>
        <p class="text-muted mt-2">Admin Panel</p>
    </div>
    <div class="card p-4">
        <h4 class="card-title text-center mb-4 fw-bold">Sign In to Your Admin Account</h4>
        
        <!-- Error Message Placeholder -->
        <div id="errorMessage" class="alert alert-danger d-none" role="alert"></div>

        <form id="admin-login-form">
            <div class="mb-3">
                <label for="adminEmail" class="form-label">Email address</label>
                <input type="email" class="form-control" id="adminEmail" name="email" placeholder="admin@admin.com" required>
            </div>
            <div class="mb-3">
                <label for="adminPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="adminPassword" name="password" placeholder="admin123" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg loader">Sign In</button>
                <a href="index.php" class="btn btn-outline-secondary mt-2">Back to Main Site</a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS cdn link -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('admin-login-form');
        const errorMessageDiv = document.getElementById('errorMessage');
        const loginButton = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('adminEmail').value;
            const password = document.getElementById('adminPassword').value;

            // Show loading spinner
            loginButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            loginButton.disabled = true;
            
            try {
                const response = await fetch('api.php?action=admin_login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const result = await response.json();

                if (result.success) {
                    errorMessageDiv.textContent = result.message;
                    errorMessageDiv.classList.remove('alert-danger', 'd-none');
                    errorMessageDiv.classList.add('alert-success');
                    setTimeout(() => {
                        window.location.href = 'admin.php';
                    }, 1500);
                } else {
                    errorMessageDiv.textContent = result.message;
                    errorMessageDiv.classList.remove('d-none');
                    errorMessageDiv.classList.add('alert-danger');
                }
            } catch (error) {
                console.error('Login failed:', error);
                errorMessageDiv.textContent = 'An error occurred. Please try again.';
                errorMessageDiv.classList.remove('d-none');
                errorMessageDiv.classList.add('alert-danger');
            } finally {
                // Reset button state
                loginButton.innerHTML = 'Sign In';
                loginButton.disabled = false;
            }
        });

        // URL parameter handling for login errors
        function handleUrlParameters() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');

            if (error) {
                errorMessageDiv.classList.remove('d-none');
                if (error === 'invalid_credentials') {
                    errorMessageDiv.textContent = 'Invalid email or password.';
                } else if (error === 'unauthorized') {
                    errorMessageDiv.textContent = 'Unauthorized access. Please log in.';
                }
            }
        }
        
        handleUrlParameters();
    });
</script>

</body>
</html>
