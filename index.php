<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
ensureSession();
$loggedIn  = isset($_SESSION['user_id']);
$userName  = $loggedIn ? htmlspecialchars($_SESSION['user_name'],  ENT_QUOTES, 'UTF-8') : '';
$userEmail = $loggedIn ? htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VALET - Premium Home Services</title>
  <meta name="description" content="A comprehensive platform connecting premium residential communities with professional service providers.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Booking modal grid */
    .booking-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    @media(max-width:540px){.booking-form-grid{grid-template-columns:1fr}}
    /* Toast */
    .valet-toast{position:fixed;bottom:2rem;right:2rem;z-index:9999;background:#1a1a1a;color:#fff;padding:.875rem 1.5rem;border-radius:.75rem;font-size:.875rem;font-weight:500;box-shadow:0 10px 30px rgba(0,0,0,.2);transform:translateY(100px);opacity:0;transition:all .3s;max-width:320px;pointer-events:none}
    .valet-toast.show{transform:translateY(0);opacity:1}
    .valet-toast.success{background:#38a169}
    .valet-toast.error{background:#e53e3e}
    [dir=rtl] .valet-toast{right:auto;left:2rem}
    /* User menu */
    .user-menu{position:relative}
    .user-menu-btn{display:flex;align-items:center;gap:.5rem;background:var(--secondary);border:none;border-radius:.75rem;padding:.5rem 1rem;cursor:pointer;font-family:var(--font-sans);font-size:.875rem;font-weight:600;color:var(--foreground);transition:all .3s}
    .user-menu-btn:hover{background:var(--primary);color:var(--primary-foreground)}
    .user-menu-btn svg{width:1rem;height:1rem;flex-shrink:0}
    .user-dropdown{position:absolute;right:0;top:calc(100% + .5rem);background:var(--card);border:1px solid var(--border);border-radius:.75rem;padding:.5rem;min-width:160px;box-shadow:0 10px 30px rgba(0,0,0,.1);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s;z-index:50}
    .user-dropdown.open{opacity:1;visibility:visible;transform:translateY(0)}
    .user-dropdown a,.user-dropdown button{display:block;width:100%;text-align:left;padding:.6rem .875rem;border-radius:.5rem;font-size:.875rem;color:var(--foreground);background:none;border:none;cursor:pointer;font-family:var(--font-sans);transition:background .2s;text-decoration:none}
    .user-dropdown a:hover,.user-dropdown button:hover{background:var(--secondary)}
    .user-dropdown .ud-logout{color:#e53e3e}
    [dir=rtl] .user-dropdown{right:auto;left:0}
    [dir=rtl] .user-dropdown a,[dir=rtl] .user-dropdown button{text-align:right}
    /* Inline form error */
    .api-error{background:#fff0f0;color:#c53030;border:1px solid #feb2b2;border-radius:.5rem;padding:.75rem 1rem;font-size:.875rem;margin-bottom:.75rem;display:none}
    /* Booking select/time/date style fix */
    .booking-select{width:100%;padding:.875rem 1rem;background:var(--background);border:1px solid var(--border);border-radius:.75rem;color:var(--foreground);font-size:1rem;font-family:var(--font-sans);outline:none}
    .booking-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(var(--primary-rgb),.15)}
  </style>
</head>
<body>

<!-- Toast -->
<div class="valet-toast" id="valetToast"></div>

  <!-- Login Modal -->
  <div class="modal-overlay" id="loginModal">
    <div class="modal">
      <button class="modal-close" id="closeLoginModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
      <div class="modal-header">
        <div class="modal-logo">
          <div class="logo-icon">V</div>
          <span class="logo-text">VALET</span>
        </div>
        <h2 class="modal-title" data-i18n="auth.loginTitle">Welcome Back</h2>
        <p class="modal-subtitle" data-i18n="auth.loginSubtitle">Sign in to your account to continue</p>
      </div>
      <form class="auth-form" id="loginForm" novalidate>
        <div class="api-error" id="loginError"></div>
        <div class="form-group">
          <label data-i18n="auth.email">Email Address</label>
          <input type="email" id="loginEmail" placeholder="name@example.com" data-i18n-placeholder="auth.emailPlaceholder" required>
        </div>
        <div class="form-group">
          <label data-i18n="auth.password">Password</label>
          <div class="password-input">
            <input type="password" id="loginPassword" placeholder="Enter your password" data-i18n-placeholder="auth.passwordPlaceholder" required>
            <button type="button" class="toggle-password">
              <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox">
            <span data-i18n="auth.rememberMe">Remember me</span>
          </label>
          <a href="#" class="forgot-link" data-i18n="auth.forgotPassword">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary full-width" data-i18n="auth.signIn">Sign In</button>
      </form>
      <div class="auth-divider">
        <span data-i18n="auth.orContinue">or continue with</span>
      </div>
      <div class="social-auth">
        <button class="social-auth-btn">
          <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          <span>Google</span>
        </button>
        <button class="social-auth-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
          <span>GitHub</span>
        </button>
      </div>
      <p class="auth-footer">
        <span data-i18n="auth.noAccount">Don't have an account?</span>
        <a href="#" id="showSignup" data-i18n="auth.signUp">Sign up</a>
      </p>
    </div>
  </div>

  <!-- Signup Modal -->
  <div class="modal-overlay" id="signupModal">
    <div class="modal">
      <button class="modal-close" id="closeSignupModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
      <div class="modal-header">
        <div class="modal-logo">
          <div class="logo-icon">V</div>
          <span class="logo-text">VALET</span>
        </div>
        <h2 class="modal-title" data-i18n="auth.signupTitle">Create Account</h2>
        <p class="modal-subtitle" data-i18n="auth.signupSubtitle">Join us and enjoy premium home services</p>
      </div>
      <form class="auth-form" id="signupForm" novalidate>
        <div class="api-error" id="signupError"></div>
        <div class="form-group">
          <label data-i18n="auth.fullName">Full Name</label>
          <input type="text" id="signupName" placeholder="Enter your name" data-i18n-placeholder="auth.namePlaceholder" required>
        </div>
        <div class="form-group">
          <label data-i18n="auth.email">Email Address</label>
          <input type="email" id="signupEmail" placeholder="name@example.com" data-i18n-placeholder="auth.emailPlaceholder" required>
        </div>
        <div class="form-group">
          <label data-i18n="auth.phone">Phone Number</label>
          <input type="tel" id="signupPhone" placeholder="+20 xxx xxx xxxx" data-i18n-placeholder="auth.phonePlaceholder" required>
        </div>
        <div class="form-group">
          <label data-i18n="auth.password">Password</label>
          <div class="password-input">
            <input type="password" id="signupPassword" placeholder="Create a password" data-i18n-placeholder="auth.createPasswordPlaceholder" required>
            <button type="button" class="toggle-password">
              <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>
        <label class="checkbox-label terms-checkbox">
          <input type="checkbox" id="signupTerms" required>
          <span data-i18n="auth.agreeTerms">I agree to the Terms of Service and Privacy Policy</span>
        </label>
        <button type="submit" class="btn btn-primary full-width" data-i18n="auth.createAccount">Create Account</button>
      </form>
      <div class="auth-divider">
        <span data-i18n="auth.orContinue">or continue with</span>
      </div>
      <div class="social-auth">
        <button class="social-auth-btn">
          <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          <span>Google</span>
        </button>
        <button class="social-auth-btn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
          <span>GitHub</span>
        </button>
      </div>
      <p class="auth-footer">
        <span data-i18n="auth.haveAccount">Already have an account?</span>
        <a href="#" id="showLogin" data-i18n="auth.signIn">Sign in</a>
      </p>
    </div>
  </div>

  <!-- Booking Modal (NEW — added by PHP backend) -->
  <div class="modal-overlay" id="bookingModal">
    <div class="modal" style="max-width:38rem;">
      <button class="modal-close" id="closeBookingModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
      <div class="modal-header">
        <div class="modal-logo"><div class="logo-icon">V</div><span class="logo-text">VALET</span></div>
        <h2 class="modal-title">Book a Service</h2>
        <p class="modal-subtitle">Fill in your details — we confirm within 30 minutes</p>
      </div>
      <div id="bookingSuccess" style="display:none;text-align:center;padding:2rem 0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="#38a169" stroke-width="2" style="width:3rem;height:3rem;margin:0 auto 1rem;display:block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <p style="font-size:1.1rem;font-weight:600;color:#38a169;">Booking Received!</p>
        <p style="color:var(--muted-foreground);margin-top:.5rem;">We will contact you shortly to confirm your appointment.</p>
      </div>
      <form id="bookingForm" novalidate style="margin-top:.5rem;">
        <div class="api-error" id="bookingError"></div>
        <div class="booking-form-grid">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="bkName" placeholder="Your full name" value="<?= $userName ?>" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="bkEmail" placeholder="name@example.com" value="<?= $userEmail ?>" required>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" id="bkPhone" placeholder="+20 xxx xxx xxxx" required>
          </div>
          <div class="form-group">
            <label>Service Type</label>
            <select id="bkService" class="booking-select" required>
              <option value="">Select a service…</option>
              <option value="plumbing">Plumbing &amp; Maintenance</option>
              <option value="electrical">Electrical &amp; AC</option>
              <option value="cleaning">Home Cleaning</option>
              <option value="tutoring">Private Tutoring</option>
              <option value="babysitting">Babysitting</option>
              <option value="painting">Painting &amp; Decor</option>
              <option value="carwash">Car Wash</option>
              <option value="cooking">Home Cooking</option>
            </select>
          </div>
          <div class="form-group">
            <label>Preferred Date</label>
            <input type="date" id="bkDate" class="booking-select" required>
          </div>
          <div class="form-group">
            <label>Preferred Time</label>
            <input type="time" id="bkTime" class="booking-select" required>
          </div>
        </div>
        <div class="form-group">
          <label>Address</label>
          <input type="text" id="bkAddress" placeholder="Your full address" required>
        </div>
        <div class="form-group">
          <label>Notes <span style="opacity:.5;font-weight:400;">(optional)</span></label>
          <textarea id="bkNotes" rows="2" placeholder="Any extra details…" style="width:100%;padding:.875rem 1rem;background:var(--background);border:1px solid var(--border);border-radius:.75rem;color:var(--foreground);font-size:1rem;font-family:var(--font-sans);resize:vertical;outline:none;box-sizing:border-box;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary full-width" id="bkSubmit">Confirm Booking</button>
      </form>
    </div>
  </div>

  <!-- Navbar -->
  <nav class="navbar" id="navbar">
    <div class="navbar-container">
      <a href="index.php" class="logo">
        <div class="logo-icon">V</div>
        <span class="logo-text">VALET</span>
      </a>

      <div class="nav-links" id="navLinks">
        <a href="#" class="nav-link" data-i18n="nav.home">Home</a>
        <div class="nav-dropdown">
          <button class="nav-link dropdown-trigger" id="servicesDropdown">
            <span data-i18n="nav.services">Services</span>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div class="dropdown-menu" id="dropdownMenu">
            <a href="#services" data-i18n="nav.plumbing">Plumbing & Maintenance</a>
            <a href="#services" data-i18n="nav.electrical">Electrical & AC</a>
            <a href="#services" data-i18n="nav.tutoring">Private Tutoring</a>
            <a href="#services" data-i18n="nav.cleaning">Home Cleaning</a>
            <a href="#services" data-i18n="nav.babysitting">Babysitting</a>
          </div>
        </div>
        <a href="#how-it-works" class="nav-link" data-i18n="nav.howItWorks">How It Works</a>
        <a href="#testimonials" class="nav-link" data-i18n="nav.testimonials">Testimonials</a>
        <a href="#contact" class="nav-link" data-i18n="nav.contact">Contact</a>
      </div>

      <div class="nav-actions">
        <button class="icon-btn" id="langToggle" title="Toggle Language">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
            <path d="M2 12h20"/>
          </svg>
        </button>
        <button class="icon-btn" id="themeToggle" title="Toggle Theme">
          <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
          </svg>
          <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </button>

        <?php if ($loggedIn): ?>
        <!-- Logged-in user menu -->
        <div class="user-menu" id="userMenu">
          <button class="user-menu-btn" id="userMenuBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= $userName ?>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <button class="ud-logout" id="logoutBtn">Sign Out</button>
          </div>
        </div>
        <?php else: ?>
        <button class="btn btn-ghost login-btn" id="openLoginBtn" data-i18n="nav.login">Sign In</button>
        <?php endif; ?>

        <button class="btn btn-primary" id="navBookNow" data-i18n="nav.bookNow">Book Now</button>
      </div>

      <button class="mobile-menu-btn" id="mobileMenuBtn">
        <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 12h16M4 6h16M4 18h16"/>
        </svg>
        <svg class="close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
      <a href="#" class="mobile-link" data-i18n="nav.home">Home</a>
      <a href="#services" class="mobile-link" data-i18n="nav.services">Services</a>
      <a href="#how-it-works" class="mobile-link" data-i18n="nav.howItWorks">How It Works</a>
      <a href="#testimonials" class="mobile-link" data-i18n="nav.testimonials">Testimonials</a>
      <a href="#contact" class="mobile-link" data-i18n="nav.contact">Contact</a>
      <div class="mobile-actions">
        <?php if ($loggedIn): ?>
        <button class="btn btn-ghost" id="logoutBtnMobile">Sign Out</button>
        <?php else: ?>
        <button class="btn btn-ghost login-btn" id="openLoginBtnMobile" data-i18n="nav.login">Sign In</button>
        <?php endif; ?>
        <button class="btn btn-primary" id="mobileBookNow" data-i18n="nav.bookNow">Book Now</button>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-blur-1"></div>
    <div class="hero-blur-2"></div>

    <div class="container hero-content">
      <div class="hero-grid">
        <div class="hero-text">
          <div class="hero-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
            </svg>
            <span data-i18n="hero.badge">The Premier Platform for Luxury Home Services</span>
          </div>

          <h1 class="hero-title">
            <span class="title-line1" data-i18n="hero.title1">Premium Home</span>
            <span class="title-line2" data-i18n="hero.title2">Services</span>
          </h1>

          <p class="hero-description" data-i18n="hero.description">
            A comprehensive platform connecting premium residential communities with professional service providers. Plumbing, electrical, cleaning, tutoring, and more - all in one place.
          </p>

          <div class="hero-buttons">
            <button class="btn btn-primary btn-lg" id="heroBookNow">
              <span data-i18n="hero.cta">Book Your Service</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
            </button>
            <button class="btn btn-outline btn-lg" data-i18n="hero.browse">Browse Services</button>
          </div>

          <div class="hero-badges">
            <div class="badge-item">
              <div class="badge-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
              </div>
              <div>
                <p class="badge-title" data-i18n="hero.quality">Quality Guaranteed</p>
                <p class="badge-desc" data-i18n="hero.qualityDesc">100% Trusted Services</p>
              </div>
            </div>
            <div class="badge-item">
              <div class="badge-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <div>
                <p class="badge-title" data-i18n="hero.response">Fast Response</p>
                <p class="badge-desc" data-i18n="hero.responseDesc">Within 30 Minutes</p>
              </div>
            </div>
          </div>
        </div>

        <div class="hero-cards">
          <!-- Hero Image Placeholder -->
          <div class="hero-image-container">
            <img src="images/hero-bg.jpg" alt="Professional home services" class="hero-main-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder hero-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Hero Image (800x600px)</span>
            </div>
          </div>

          <div class="floating-card card-1">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <div>
              <p class="card-value">+5,000</p>
              <p class="card-label" data-i18n="hero.clients">Happy Clients</p>
            </div>
          </div>

          <div class="floating-card card-2">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
              </svg>
            </div>
            <div>
              <p class="card-value">4.9</p>
              <p class="card-label" data-i18n="hero.rating">Client Rating</p>
            </div>
          </div>

          <div class="support-badge">
            <div class="support-circle">
              <span class="support-time">24/7</span>
              <span class="support-label" data-i18n="hero.support">Support</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="services" class="services">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="services.badge">Our Premium Services</span>
        <h2 class="section-title">
          <span data-i18n="services.title">Everything You Need</span>
          <span class="highlight" data-i18n="services.titleHighlight">in One Place</span>
        </h2>
        <p class="section-description" data-i18n="services.description">
          We offer a complete range of high-quality home services, with guaranteed reliability and professionalism in every service.
        </p>
      </div>

      <div class="services-grid">
        <!-- Service 1 - Plumbing -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/plumbing.jpg" alt="Plumbing services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Plumbing Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.plumbing">Plumbing & Maintenance</h3>
            <p data-i18n="services.plumbingDesc">Professional technicians for all plumbing and general maintenance issues with top quality and fastest time.</p>
            <ul>
              <li data-i18n="services.plumbingF1">Leak repairs</li>
              <li data-i18n="services.plumbingF2">Fixture installation</li>
              <li data-i18n="services.plumbingF3">Regular maintenance</li>
            </ul>
            <button class="service-btn" data-service="plumbing" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 2 - Electrical -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/electrical.jpg" alt="Electrical services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Electrical Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.electrical">Electrical & AC</h3>
            <p data-i18n="services.electricalDesc">Certified electrical and AC experts to ensure your home's safety and your family's comfort.</p>
            <ul>
              <li data-i18n="services.electricalF1">Electrical wiring</li>
              <li data-i18n="services.electricalF2">AC maintenance</li>
              <li data-i18n="services.electricalF3">Lighting installation</li>
            </ul>
            <button class="service-btn" data-service="electrical" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 3 - Cleaning -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/cleaning.jpg" alt="Cleaning services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Cleaning Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.cleaning">Home Cleaning</h3>
            <p data-i18n="services.cleaningDesc">Professional cleaning team that transforms your home into an oasis of cleanliness and freshness.</p>
            <ul>
              <li data-i18n="services.cleaningF1">Deep cleaning</li>
              <li data-i18n="services.cleaningF2">Carpet cleaning</li>
              <li data-i18n="services.cleaningF3">Floor polishing</li>
            </ul>
            <button class="service-btn" data-service="cleaning" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 4 - Tutoring -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/tutoring.jpg" alt="Tutoring services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Tutoring Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.tutoring">Private Tutoring</h3>
            <p data-i18n="services.tutoringDesc">Distinguished teachers in all subjects to help your children excel academically.</p>
            <ul>
              <li data-i18n="services.tutoringF1">All grades</li>
              <li data-i18n="services.tutoringF2">Foreign languages</li>
              <li data-i18n="services.tutoringF3">Science & Math</li>
            </ul>
            <button class="service-btn" data-service="tutoring" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 5 - Babysitting -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/babysitting.jpg" alt="Babysitting services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Babysitting Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 12h.01"/>
                <path d="M15 12h.01"/>
                <path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/>
                <path d="M19 6.3a9 9 0 0 1 1.8 3.9 2 2 0 0 1 0 3.6 9 9 0 0 1-17.6 0 2 2 0 0 1 0-3.6A9 9 0 0 1 12 3c2 0 3.5 1.1 3.5 2.5s-.9 2.5-2 2.5c-.8 0-1.5-.4-1.5-1"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.babysitting">Babysitting</h3>
            <p data-i18n="services.babysittingDesc">Trusted and trained babysitters to care for your children with attention and love.</p>
            <ul>
              <li data-i18n="services.babysittingF1">Daily care</li>
              <li data-i18n="services.babysittingF2">Evening sitting</li>
              <li data-i18n="services.babysittingF3">Fun activities</li>
            </ul>
            <button class="service-btn" data-service="babysitting" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 6 - Painting -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/painting.jpg" alt="Painting services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Painting Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m18.37 2.63-1.68 1.68a6 6 0 0 0-8.48 0L6.63 2.63a9 9 0 0 1 11.74 0z"/>
                <path d="m16.7 4.3-1.4 1.4a4 4 0 0 0-5.6 0L8.3 4.3a6 6 0 0 1 8.4 0z"/>
                <path d="m14.5 6.5-1.4 1.4a2 2 0 0 0-2.1 0L9.5 6.5a4 4 0 0 1 5 0z"/>
                <line x1="2" x2="22" y1="22" y2="22"/>
                <line x1="6" x2="6" y1="22" y2="11"/>
                <line x1="18" x2="18" y1="22" y2="11"/>
                <path d="M6 11a4 4 0 0 1 8 0 4 4 0 0 1 8 0"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.painting">Painting & Decor</h3>
            <p data-i18n="services.paintingDesc">Artists in painting and decoration to transform your spaces into artistic masterpieces.</p>
            <ul>
              <li data-i18n="services.paintingF1">Interior painting</li>
              <li data-i18n="services.paintingF2">Wallpaper</li>
              <li data-i18n="services.paintingF3">Gypsum decor</li>
            </ul>
            <button class="service-btn" data-service="painting" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 7 - Car Wash -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/carwash.jpg" alt="Car wash services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Car Wash Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                <circle cx="7" cy="17" r="2"/>
                <path d="M9 17h6"/>
                <circle cx="17" cy="17" r="2"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.carWash">Car Wash</h3>
            <p data-i18n="services.carWashDesc">Mobile car washing and polishing service that comes to you wherever you are.</p>
            <ul>
              <li data-i18n="services.carWashF1">Exterior wash</li>
              <li data-i18n="services.carWashF2">Interior cleaning</li>
              <li data-i18n="services.carWashF3">Polish & protect</li>
            </ul>
            <button class="service-btn" data-service="carwash" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>

        <!-- Service 8 - Cooking -->
        <div class="service-card">
          <div class="service-image">
            <img src="images/services/cooking.jpg" alt="Cooking services" class="service-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder service-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>Cooking Image (400x300px)</span>
            </div>
            <div class="service-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                <path d="M7 2v20"/>
                <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
              </svg>
            </div>
          </div>
          <div class="service-content">
            <h3 data-i18n="services.cooking">Home Cooking</h3>
            <p data-i18n="services.cookingDesc">Professional chefs to prepare the most delicious dishes at your home for special occasions.</p>
            <ul>
              <li data-i18n="services.cookingF1">Special events</li>
              <li data-i18n="services.cookingF2">Daily meals</li>
              <li data-i18n="services.cookingF3">Desserts</li>
            </ul>
            <button class="service-btn" data-service="cooking" data-i18n="services.bookNow">Book Now</button>
          </div>
        </div>
      </div>

      <div class="section-cta">
        <button class="btn btn-outline btn-lg" data-i18n="services.viewAll">View All Services</button>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="features.badge">Our Advantages</span>
        <h2 class="section-title">
          <span data-i18n="features.title">Why Choose</span>
          <span class="highlight">Valet</span>
        </h2>
        <p class="section-description" data-i18n="features.description">
          We offer you an exceptional experience combining quality, reliability, and comfort
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <h3 data-i18n="features.certified">Certified Professionals</h3>
          <p data-i18n="features.certifiedDesc">All service providers undergo thorough screening and professional training before joining.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <h3 data-i18n="features.instant">Instant Booking</h3>
          <p data-i18n="features.instantDesc">Book your service anytime and get instant confirmation with flexible schedules.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect width="20" height="14" x="2" y="5" rx="2"/>
              <line x1="2" x2="22" y1="10" y2="10"/>
            </svg>
          </div>
          <h3 data-i18n="features.transparent">Transparent Pricing</h3>
          <p data-i18n="features.transparentDesc">No hidden fees. Know the price before booking with best price guarantee.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </div>
          <h3 data-i18n="features.support">24/7 Support</h3>
          <p data-i18n="features.supportDesc">Dedicated support team ready to help you anytime, 7 days a week.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <h3 data-i18n="features.guarantee">Quality Guarantee</h3>
          <p data-i18n="features.guaranteeDesc">We guarantee service quality. If not satisfied, we refund your money.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h3 data-i18n="features.reviews">Real Reviews</h3>
          <p data-i18n="features.reviewsDesc">Read real customer reviews and ratings to make the best decision.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="how-it-works">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="howItWorks.badge">How It Works</span>
        <h2 class="section-title">
          <span data-i18n="howItWorks.title">Simple</span>
          <span class="highlight" data-i18n="howItWorks.titleHighlight">& Easy Steps</span>
        </h2>
        <p class="section-description" data-i18n="howItWorks.description">
          Get the service you need in just four simple steps
        </p>
      </div>

      <div class="steps-grid">
        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
              </svg>
            </div>
            <div class="step-number">01</div>
          </div>
          <h3 data-i18n="howItWorks.step1">Search for Service</h3>
          <p data-i18n="howItWorks.step1Desc">Browse available services and choose what suits your needs from hundreds of professional providers.</p>
        </div>

        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <polyline points="16 11 18 13 22 9"/>
              </svg>
            </div>
            <div class="step-number">02</div>
          </div>
          <h3 data-i18n="howItWorks.step2">Choose Provider</h3>
          <p data-i18n="howItWorks.step2Desc">Review ratings and previous customer reviews and choose the most suitable provider based on experience and price.</p>
        </div>

        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </div>
            <div class="step-number">03</div>
          </div>
          <h3 data-i18n="howItWorks.step3">Book Appointment</h3>
          <p data-i18n="howItWorks.step3Desc">Set your preferred time and confirm your booking easily. We'll contact you to confirm details.</p>
        </div>

        <div class="step-card">
          <div class="step-icon-wrapper">
            <div class="step-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
              </svg>
            </div>
            <div class="step-number">04</div>
          </div>
          <h3 data-i18n="howItWorks.step4">Rate Experience</h3>
          <p data-i18n="howItWorks.step4Desc">After service completion, share your feedback and help others choose the best providers.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Community Section -->
  <section class="community">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="community.badge">Resident Community</span>
        <h2 class="section-title">
          <span data-i18n="community.title">More Than Just</span>
          <span class="highlight" data-i18n="community.titleHighlight">Services</span>
        </h2>
        <p class="section-description" data-i18n="community.description">
          A comprehensive platform bringing your neighborhood residents together in one organized and safe community
        </p>
      </div>

      <div class="community-grid">
        <div class="community-card">
          <div class="community-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
          <div>
            <h3 data-i18n="community.forum">Resident Forum</h3>
            <p data-i18n="community.forumDesc">Connect with your neighbors, share news and information, and ask questions in a safe and organized community.</p>
            <button class="community-btn" data-i18n="community.discover">Discover More</button>
          </div>
        </div>

        <div class="community-card">
          <div class="community-icon alt">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="9" cy="21" r="1"/>
              <circle cx="20" cy="21" r="1"/>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
          </div>
          <div>
            <h3 data-i18n="community.marketplace">Marketplace</h3>
            <p data-i18n="community.marketplaceDesc">List your used items for sale or browse neighbors' offers. Safe trading among residents.</p>
            <button class="community-btn" data-i18n="community.discover">Discover More</button>
          </div>
        </div>

        <div class="community-card">
          <div class="community-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
              <line x1="12" y1="9" x2="12" y2="13"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
          <div>
            <h3 data-i18n="community.complaints">Complaints & Suggestions</h3>
            <p data-i18n="community.complaintsDesc">Submit your complaints or suggestions to area management and track their status. Your voice is heard.</p>
            <button class="community-btn" data-i18n="community.discover">Discover More</button>
          </div>
        </div>

        <div class="community-card">
          <div class="community-icon alt">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m3 11 18-5v12L3 14v-3z"/>
              <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
            </svg>
          </div>
          <div>
            <h3 data-i18n="community.announcements">Area Announcements</h3>
            <p data-i18n="community.announcementsDesc">Stay updated with the latest announcements and events in your residential area.</p>
            <button class="community-btn" data-i18n="community.discover">Discover More</button>
          </div>
        </div>
      </div>

      <div class="section-cta">
        <button class="btn btn-primary btn-lg" data-i18n="community.join">Join Your Community Now</button>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="stats-content">
        <div class="stats-text">
          <span class="section-badge" data-i18n="stats.badge">Why Us</span>
          <h2 class="section-title left">
            <span data-i18n="stats.title">Trusted by</span>
            <span class="highlight" data-i18n="stats.titleHighlight">Thousands of Residents</span>
          </h2>
          <p class="stats-description" data-i18n="stats.description">
            With over five years of experience in providing high-quality home services, we take pride in our customers' trust and reliance on us. Our team of professionals, engineers, and technicians is committed to providing the best possible service with your complete satisfaction guaranteed.
          </p>
          <button class="btn btn-primary btn-lg" data-i18n="stats.learnMore">Learn More About Us</button>
        </div>

        <div class="stats-grid-wrapper">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <p class="stat-value">100+</p>
              <p class="stat-label" data-i18n="stats.providers">Service Providers</p>
              <p class="stat-sublabel" data-i18n="stats.providersLabel">Certified professionals</p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
              </div>
              <p class="stat-value">8,000</p>
              <p class="stat-label" data-i18n="stats.completed">Completed Services</p>
              <p class="stat-sublabel" data-i18n="stats.completedLabel">Successfully</p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <p class="stat-value">30</p>
              <p class="stat-label" data-i18n="stats.minutes">Minutes</p>
              <p class="stat-sublabel" data-i18n="stats.minutesLabel">Average response</p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="8" r="6"/>
                  <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                </svg>
              </div>
              <p class="stat-value">98%</p>
              <p class="stat-label" data-i18n="stats.satisfaction">Customer Satisfaction</p>
              <p class="stat-sublabel" data-i18n="stats.satisfactionLabel">Positive rating</p>
            </div>

            <div class="stat-card">
              <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
              </div>
              <p class="stat-value" data-i18n="stats.support247">24/7</p>
              <p class="stat-label" data-i18n="stats.supportLabel">Continuous Support</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section id="testimonials" class="testimonials">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="testimonials.badge">Customer Reviews</span>
        <h2 class="section-title">
          <span data-i18n="testimonials.title">What Our</span>
          <span class="highlight" data-i18n="testimonials.titleHighlight">Clients Say</span>
        </h2>
        <p class="section-description" data-i18n="testimonials.description">
          We're proud of our customers' trust and their positive reviews that drive us to continue delivering the best
        </p>
      </div>

      <div class="testimonials-grid">
        <div class="testimonial-card">
          <div class="quote-icon">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21c0 1 0 1 1 1z"/>
              <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
            </svg>
          </div>
          <div class="rating">
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          </div>
          <p class="testimonial-text" data-i18n="testimonials.review1">"Excellent service! I requested an electrician and they arrived within an hour. Professional work and reasonable prices."</p>
          <div class="testimonial-author">
            <img src="images/testimonials/avatar1.jpg" alt="Ahmed Mahmoud" class="author-avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="author-avatar">AM</div>
            <div>
              <p class="author-name" data-i18n="testimonials.review1Name">Ahmed Mahmoud</p>
              <p class="author-role" data-i18n="testimonials.review1Role">Resident at Madinaty Compound</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="quote-icon">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21c0 1 0 1 1 1z"/>
              <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
            </svg>
          </div>
          <div class="rating">
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          </div>
          <p class="testimonial-text" data-i18n="testimonials.review2">"I tried the cleaning service and it was more than excellent. The team was respectful and professional and the house became perfect. Thank you Valet!"</p>
          <div class="testimonial-author">
            <img src="images/testimonials/avatar2.jpg" alt="Sarah Ahmed" class="author-avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="author-avatar">SA</div>
            <div>
              <p class="author-name" data-i18n="testimonials.review2Name">Sarah Ahmed</p>
              <p class="author-role" data-i18n="testimonials.review2Role">Resident at Sheikh Zayed</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="quote-icon">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21c0 1 0 1 1 1z"/>
              <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
            </svg>
          </div>
          <div class="rating">
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          </div>
          <p class="testimonial-text" data-i18n="testimonials.review3">"The tutor they brought for my son was excellent. His math level improved significantly. Thanks for the distinguished service."</p>
          <div class="testimonial-author">
            <img src="images/testimonials/avatar3.jpg" alt="Mohamed Ali" class="author-avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="author-avatar">MA</div>
            <div>
              <p class="author-name" data-i18n="testimonials.review3Name">Mohamed Ali</p>
              <p class="author-role" data-i18n="testimonials.review3Role">Resident at Palm Hills</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section id="contact" class="contact">
    <div class="section-blur-1"></div>
    <div class="section-blur-2"></div>

    <div class="container">
      <div class="section-header">
        <span class="section-badge" data-i18n="contact.badge">Contact Us</span>
        <h2 class="section-title">
          <span data-i18n="contact.title">Get Your</span>
          <span class="highlight" data-i18n="contact.titleHighlight">Free Consultation</span>
        </h2>
        <p class="section-description" data-i18n="contact.description">
          Contact us now and we'll help you find the optimal solution for your needs
        </p>
      </div>

      <div class="contact-grid">
        <div class="contact-info">
          <div class="info-card">
            <h3 data-i18n="contact.info">Contact Information</h3>
            <div class="info-item">
              <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
              </div>
              <div>
                <p class="info-title" data-i18n="contact.callUs">Call Us</p>
                <p class="info-text">+20 123 456 7890</p>
              </div>
            </div>
            <div class="info-item">
              <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="2" y="4" width="20" height="16" rx="2"/>
                  <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
              </div>
              <div>
                <p class="info-title" data-i18n="contact.email">Email</p>
                <p class="info-text">info@valet-services.com</p>
              </div>
            </div>
            <div class="info-item">
              <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                  <circle cx="12" cy="10" r="3"/>
                </svg>
              </div>
              <div>
                <p class="info-title" data-i18n="contact.address">Address</p>
                <p class="info-text" data-i18n="contact.addressLine1">New Cairo, Fifth Settlement</p>
                <p class="info-text" data-i18n="contact.addressLine2">Egypt</p>
              </div>
            </div>
          </div>

          <div class="hours-card">
            <h3 data-i18n="contact.hours">Working Hours</h3>
            <div class="hours-row">
              <span data-i18n="contact.weekdays">Saturday - Thursday</span>
              <span>9:00 AM - 9:00 PM</span>
            </div>
            <div class="hours-row">
              <span data-i18n="contact.friday">Friday</span>
              <span>2:00 PM - 9:00 PM</span>
            </div>
            <div class="hours-row">
              <span data-i18n="contact.emergency">Emergency</span>
              <span>24/7</span>
            </div>
          </div>
        </div>

        <div class="form-card">
          <h3 data-i18n="contact.form">Send Us a Message</h3>
          <form id="contactForm" novalidate>
            <div class="api-error" id="contactError"></div>
            <div class="form-row">
              <div class="form-group">
                <label data-i18n="contact.name">Full Name</label>
                <input type="text" id="ctName" placeholder="Enter your name" data-i18n-placeholder="contact.namePlaceholder" value="<?= $userName ?>" required>
              </div>
              <div class="form-group">
                <label data-i18n="contact.phone">Phone Number</label>
                <input type="tel" id="ctPhone" placeholder="Enter your phone number" data-i18n-placeholder="contact.phonePlaceholder">
              </div>
            </div>
            <div class="form-group">
              <label data-i18n="contact.service">Service Type</label>
              <select id="ctService" required>
                <option value="" data-i18n="contact.servicePlaceholder">Select service type</option>
                <option value="plumbing" data-i18n="services.plumbing">Plumbing & Maintenance</option>
                <option value="electrical" data-i18n="services.electrical">Electrical & AC</option>
                <option value="cleaning" data-i18n="services.cleaning">Home Cleaning</option>
                <option value="tutoring" data-i18n="services.tutoring">Private Tutoring</option>
                <option value="babysitting" data-i18n="services.babysitting">Babysitting</option>
                <option value="other" data-i18n="contact.other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label data-i18n="contact.email">Email Address</label>
              <input type="email" id="ctEmail" placeholder="name@example.com" value="<?= $userEmail ?>" required>
            </div>
            <div class="form-group">
              <label data-i18n="contact.message">Your Message</label>
              <textarea id="ctMessage" rows="4" placeholder="Write your message here..." data-i18n-placeholder="contact.messagePlaceholder"></textarea>
            </div>
            <button type="submit" class="btn btn-primary full-width" data-i18n="contact.send">Send Message</button>
          </form>
          <div class="form-success" id="formSuccess" style="display: none;">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <p class="success-title" data-i18n="contact.thanks">Thank you for contacting us!</p>
            <p class="success-desc" data-i18n="contact.thanksDesc">We'll get back to you soon</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="logo">
            <div class="logo-icon">V</div>
            <span class="logo-text">VALET</span>
          </a>
          <p data-i18n="footer.description">Your trusted platform for home services. We connect you with the best service providers in your area.</p>
          <div class="social-links">
            <a href="#" class="social-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
            </a>
            <a href="#" class="social-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
              </svg>
            </a>
            <a href="#" class="social-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>
              </svg>
            </a>
          </div>
        </div>

        <div class="footer-links">
          <h4 data-i18n="footer.services">Services</h4>
          <ul>
            <li><a href="#" data-i18n="services.plumbing">Plumbing & Maintenance</a></li>
            <li><a href="#" data-i18n="services.electrical">Electrical & AC</a></li>
            <li><a href="#" data-i18n="services.cleaning">Home Cleaning</a></li>
            <li><a href="#" data-i18n="services.tutoring">Private Tutoring</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4 data-i18n="footer.company">Company</h4>
          <ul>
            <li><a href="#" data-i18n="footer.aboutUs">About Us</a></li>
            <li><a href="#" data-i18n="footer.faq">FAQ</a></li>
            <li><a href="#" data-i18n="footer.joinProvider">Join as Provider</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4 data-i18n="footer.support">Support</h4>
          <ul>
            <li><a href="#" data-i18n="footer.helpCenter">Help Center</a></li>
            <li><a href="#" data-i18n="footer.privacy">Privacy Policy</a></li>
            <li><a href="#" data-i18n="footer.terms">Terms & Conditions</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> VALET. <span data-i18n="footer.rights">All rights reserved.</span></p>
        <button class="scroll-top" id="scrollTop">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 15l-6-6-6 6"/>
          </svg>
        </button>
      </div>
    </div>
  </footer>

<script>
const VALET_SESSION = {
  loggedIn: <?= $loggedIn ? 'true' : 'false' ?>,
  userName: <?= json_encode($userName) ?>,
  userEmail: <?= json_encode($userEmail) ?>
};
</script>
<script src="script.js"></script>
</body>
</html>
