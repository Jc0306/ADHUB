<?php
session_start();
include('db_connection.php');

// Role-based dashboard redirect
$dashboard_link = '';
if (isset($_SESSION['user_id'])) {
    $dashboard_link = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'client_dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdHub | Professional Design & Web Dev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --adhub-bg-deep: #0a121d;
            --adhub-bg-top: #132238;
            --adhub-text-primary: #FFFFFF;
            --adhub-text-secondary: #ffffff;
            --adhub-accent: #1b3252;
            --adhub-border: rgba(255, 255, 255, 0.1);
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--adhub-bg-deep);
            background: radial-gradient(circle at top center, var(--adhub-bg-top) 0%, var(--adhub-bg-deep) 100%);
            color: var(--adhub-text-primary);
            min-height: 100vh;
        }
        h1, h2, h3, h4, .fw-bold { color: var(--adhub-text-primary); }
        .text-white-75 { color: rgba(255, 255, 255, 0.75) !important; }
        .navbar { background-color: var(--adhub-bg-top); border-bottom: 1px solid var(--adhub-border); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; cursor: default; }
        .nav-link { color: white !important; font-weight: 400; transition: opacity 0.2s; }
        .nav-link:hover { opacity: 0.7; }
        .body-section { padding: 140px 0; text-align: center; }
        .body-title { font-size: 3.5rem; font-weight: 700; margin-bottom: 25px; letter-spacing: -1px; }
        .body-sub { font-size: 1.15rem; color: white; max-width: 600px; margin: 0 auto; }
        .btn-adhub { background-color: var(--adhub-accent); border: 1px solid rgba(255,255,255,0.15); color: var(--adhub-text-primary); border-radius: 50px; padding: 10px 30px; font-weight: 600; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-adhub:hover { background-color: #23406a; color: var(--adhub-text-primary); border-color: rgba(255,255,255,0.3); }
        .modal-content { background-color: var(--adhub-bg-top); border: 1px solid var(--adhub-border); color: white; }
        .form-control { background: rgba(255,255,255,0.05); border: 1px solid var(--adhub-border); color: white; }
        .form-control:focus { background: rgba(255,255,255,0.1); color: white; border-color: var(--adhub-accent); box-shadow: none; }
        .adhub-card { background: rgba(255,255,255,0.03); border: 1px solid var(--adhub-border); border-radius: 12px; transition: transform 0.3s ease; }
        .adhub-card:hover { transform: translateY(-8px); }
        .service-icon { color: white; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-5">
            <span class="navbar-brand fw-bold">ADHUB</span>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#how-it-works">How it Works</a></li>
                    <li class="nav-item ms-lg-3">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo $dashboard_link; ?>" class="btn btn-adhub px-4">Go to Dashboard</a>
                        <?php else: ?>
                            <button class="btn btn-adhub px-4" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="body-section">
        <div class="container">
            <h1 class="body-title text-white">
                <?php echo isset($_SESSION['full_name']) ? "Welcome, " . htmlspecialchars($_SESSION['full_name']) : "Manage Your Campaign Smarter"; ?>
            </h1>
            <p class="body-sub mb-5 text-white">Track performance, manage clients, and grow your business with AdHub.</p>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <button class="btn btn-adhub btn-lg px-5" data-bs-toggle="modal" data-bs-target="#loginModal">Start a Request</button>
            <?php else: ?>
                <a href="<?php echo $dashboard_link; ?>" class="btn btn-adhub btn-lg px-5">Start a Request</a>
            <?php endif; ?>
        </div>
    </header>

    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-white">Our Expertise</h2>
                <p class="text-white">High-quality digital assets for your business</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card adhub-card h-100 p-4 text-center">
                        <div class="mb-3 service-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/></svg></div>
                        <h4 class="fw-bold fs-5 text-white">Poster Design</h4>
                        <p class="text-white small">Eye-catching layouts for marketing and social media campaigns.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card adhub-card h-100 p-4 text-center">
                        <div class="mb-3 service-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z"/></svg></div>
                        <h4 class="fw-bold fs-5 text-white">Slogan</h4>
                        <p class="text-white small">Eye-catching phrase to help people recognize your brand</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card adhub-card h-100 p-4 text-center">
                        <div class="mb-3 service-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/></svg></div>
                        <h4 class="fw-bold fs-5 text-white">Real-time Feedback</h4>
                        <p class="text-white small">Communicate directly with our designers through our client portal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5" style="background: rgba(255,255,255,0.02);">
        <div class="container py-5 text-center">
            <h2 class="fw-bold text-white mb-5">How It Works</h2>
            <div class="row g-5">
                <div class="col-md-4"><div class="display-4 fw-bold text-white opacity-25 mb-3">01</div><h5 class="text-white">Login</h5><p class="text-white-75">Create account to instantly access your dashboard</p></div>
                <div class="col-md-4"><div class="display-4 fw-bold text-white opacity-25 mb-3">02</div><h5 class="text-white">Request</h5><p class="text-white-75">Submit as many design or dev tasks as you need.</p></div>
                <div class="col-md-4"><div class="display-4 fw-bold text-white opacity-25 mb-3">03</div><h5 class="text-white">Receive</h5><p class="text-white-75">Get high-quality deliverables in just a few days.</p></div>
            </div>
        </div>
    </section>

    <!-- LOGIN MODAL -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-white">Client Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if(isset($_SESSION['register_success'])): ?>
                        <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($_SESSION['register_success']); unset($_SESSION['register_success']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div>
                    <?php endif; ?>
                    <form action="login_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-white">Email Address (Gmail Only)</label>
                            <input type="email" name="email" class="form-control" placeholder="example@gmail.com" pattern=".+@gmail\.com" title="Please use a valid @gmail.com address" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-white">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="text-end mb-4">
                            <a href="#" class="small text-white text-decoration-none opacity-75" data-bs-toggle="modal" data-bs-target="#forgotModal" data-bs-dismiss="modal">Forgot password?</a>
                        </div>
                        <button type="submit" name="login_btn" class="btn btn-adhub w-100 mb-3">Sign In</button>
                        <p class="text-center small mb-0">Don't have an account? <a href="#" class="text-white fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Create Account</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FORGOT PASSWORD MODAL -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-white">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-white-75 small mb-4">Enter your email and we'll send a reset link.</p>
                    <form action="reset_logic.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label small text-white">Email Address</label>
                            <input type="email" name="reset_email" class="form-control" placeholder="name@company.com" required>
                        </div>
                        <button type="submit" class="btn btn-adhub w-100 mb-3">Send Reset Link</button>
                        <div class="text-center"><a href="#" class="small text-white text-decoration-none opacity-75" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Back to Login</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- REGISTER MODAL -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-white">Create Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if(isset($_SESSION['register_error'])): ?>
                        <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($_SESSION['register_error']); unset($_SESSION['register_error']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['register_success'])): ?>
                        <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($_SESSION['register_success']); unset($_SESSION['register_success']); ?></div>
                    <?php endif; ?>
                    <form action="register_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-white">Full Name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white">Email Address (Gmail Only)</label>
                            <input type="email" name="email" class="form-control" placeholder="example@gmail.com" pattern=".+@gmail\.com" title="Please use a valid @gmail.com address" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-white">Company Name</label>
                            <input type="text" name="company_name" class="form-control" placeholder="Optional">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-white">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" name="register_btn" class="btn btn-adhub w-100 mb-3">Register</button>
                        <p class="text-center small mb-0">Already registered? <a href="#" class="text-white fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login here</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- open modal if there's an error/success flash message -->
    <?php if(isset($_GET['modal'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var m = '<?php echo htmlspecialchars($_GET['modal']); ?>';
            if (m === 'login' || m === 'register') {
                new bootstrap.Modal(document.getElementById(m + 'Modal')).show();
            }
        });
    </script>
    <?php endif; ?>

    <footer class="py-5 mt-auto border-top" style="border-color: var(--adhub-border) !important;">
        <div class="container text-center">
            <p class="text-white mb-0 small">&copy; 2026 AdHub Project.</p>
        </div>
    </footer>
</body>
</html>