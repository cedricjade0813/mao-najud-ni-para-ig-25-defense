<?php
include 'includes/db_connect.php';
// FACULTY LOGIN LOGIC: Only faculty can log in (email + password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['password']) && $_POST['email'] !== '' && $_POST['password'] !== '') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        try {
            
            $stmt = $db->prepare('SELECT * FROM faculty WHERE email = ?');
            $stmt->execute([$email]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($faculty) { 
                // Verify password using password_verify for hashed passwords
                if (password_verify($password, $faculty['password'])) {
                    session_start();
                    $_SESSION['faculty_id'] = $faculty['faculty_id'];
                    $_SESSION['faculty_email'] = $faculty['email'];
                    $_SESSION['faculty_name'] = $faculty['full_name'];
                    $_SESSION['role'] = 'faculty';
                    $_SESSION['department'] = $faculty['department'];
                    header('Location: faculty/profile.php');
                    exit;
                } else {
                    $login_error = 'Incorrect password.';
                }
            } else {
                $login_error = 'Invalid email address.';
            }
        } catch (PDOException $e) {
            $login_error = 'Database error.';
        }
    } else {
        $login_error = 'Please enter both Email and Password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Login - Clinic Management System</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>tailwind.config = { theme: { extend: { colors: { primary: '#4F46E5', secondary: '#60A5FA' }, borderRadius: { 'none': '0px', 'sm': '4px', DEFAULT: '8px', 'md': '12px', 'lg': '16px', 'xl': '20px', '2xl': '24px', '3xl': '32px', 'full': '9999px', 'button': '8px' } } } }</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <style>
    :where([class^="ri-"])::before {
      content: "\f3c2";
    }

    body {
      font-family: 'Inter', sans-serif;
    }

    .stat-counter {
      display: inline-block;
    }

    .nav-link {
      position: relative;
    }

    .nav-link::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -4px;
      left: 0;
      background-color: #4F46E5;
      transition: width 0.3s;
    }

    .nav-link:hover::after {
      width: 100%;
    }

    .custom-checkbox {
      appearance: none;
      -webkit-appearance: none;
      width: 20px;
      height: 20px;
      border: 2px solid #d1d5db;
      border-radius: 4px;
      outline: none;
      transition: all 0.2s;
      position: relative;
      cursor: pointer;
    }

    .custom-checkbox:checked {
      background-color: #4F46E5;
      border-color: #4F46E5;
    }

    .custom-checkbox:checked::after {
      content: '';
      position: absolute;
      top: 2px;
      left: 6px;
      width: 5px;
      height: 10px;
      border: solid white;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
  </style>
</head>

<body class="bg-white">
  <!-- Header Section -->
  <header class="w-full bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <a href="#" class="text-2xl font-['Pacifico'] text-primary mr-12">Clinic Management</a>
        <nav class="hidden md:flex space-x-8">
          <a href="index.php" class="nav-link text-gray-800 font-medium hover:text-primary transition-colors">Home</a>
          <a href="index_user.php" class="nav-link text-gray-800 font-medium hover:text-primary transition-colors">Student Login</a>
          <a href="faculty_login.php" class="nav-link text-primary font-medium">Faculty Login</a>
        </nav>
      </div>
      <div class="flex items-center space-x-4">
        <a href="index.php"
          class="bg-primary text-white px-5 py-2.5 !rounded-button font-medium hover:bg-opacity-90 transition-colors whitespace-nowrap flex items-center">
          <span class="w-5 h-5 flex items-center justify-center mr-2">
            <i class="ri-home-line"></i>
          </span>
          Back to Home
        </a>
        <button class="md:hidden w-10 h-10 flex items-center justify-center text-gray-700">
          <i class="ri-menu-line text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="pt-20">
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-primary/10 via-white to-secondary/10 py-20">
      <div class="container mx-auto px-6 text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-6">
          Faculty <span class="text-primary">Login</span>
        </h1>
        <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
          Access your faculty account using your email and password
        </p>
      </div>
    </section>

    <!-- Login Form Section -->
    <section class="py-20">
      <div class="container mx-auto px-6">
        <div class="max-w-md mx-auto">
          <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <div class="text-center mb-8">
              <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-user-line text-2xl text-primary"></i>
              </div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">Faculty Login</h2>
              <p class="text-gray-600">Enter your credentials to access your account</p>
            </div>

            <?php if (isset($login_error)): ?>
              <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                  <i class="ri-error-warning-line text-red-500 mr-2"></i>
                  <span class="text-red-700 text-sm"><?php echo htmlspecialchars($login_error); ?></span>
                </div>
              </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST" action="">
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <div class="relative">
                  <input type="email" id="email" name="email" required
                    class="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="Enter your email address">
                </div>
              </div>
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                  <input type="password" id="password" name="password" required
                    class="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="Enter your password">
                </div>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <input type="checkbox" id="remember" class="custom-checkbox">
                  <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>
                <a href="#" class="text-sm text-primary hover:text-opacity-80">Forgot password?</a>
              </div>
              <button type="submit"
                class="w-full bg-primary text-white py-2 !rounded-button font-medium hover:bg-opacity-90 transition-colors">Login</button>
            </form>

            <div class="mt-8 text-center">
              <p class="text-sm text-gray-600">
                Not a faculty member? 
                <a href="index_user.php" class="text-primary hover:text-opacity-80 font-medium">Student Login</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-lg font-semibold mb-4">Clinic Management</h3>
          <p class="text-gray-400 text-sm">
            Advanced healthcare management system for educational institutions.
          </p>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2 text-sm text-gray-400">
            <li><a href="index.php" class="hover:text-white transition-colors">Home</a></li>
            <li><a href="index_user.php" class="hover:text-white transition-colors">Student Login</a></li>
            <li><a href="faculty_login.php" class="hover:text-white transition-colors">Faculty Login</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Support</h4>
          <ul class="space-y-2 text-sm text-gray-400">
            <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
            <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
            <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Contact Info</h4>
          <div class="text-sm text-gray-400 space-y-2">
            <p><i class="ri-phone-line mr-2"></i>+1 (555) 123-4567</p>
            <p><i class="ri-mail-line mr-2"></i>info@clinic.com</p>
            <p><i class="ri-map-pin-line mr-2"></i>123 Health St, City</p>
          </div>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
        <p>&copy; 2024 Clinic Management System. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    // Simple form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');

      form.addEventListener('submit', function(e) {
        let isValid = true;

        // Email validation
        if (!emailInput.value.trim()) {
          showFieldError(emailInput, 'Email is required');
          isValid = false;
        } else if (!isValidEmail(emailInput.value)) {
          showFieldError(emailInput, 'Please enter a valid email address');
          isValid = false;
        } else {
          clearFieldError(emailInput);
        }

        // Password validation
        if (!passwordInput.value.trim()) {
          showFieldError(passwordInput, 'Password is required');
          isValid = false;
        } else if (passwordInput.value.length < 6) {
          showFieldError(passwordInput, 'Password must be at least 6 characters');
          isValid = false;
        } else {
          clearFieldError(passwordInput);
        }

        if (!isValid) {
          e.preventDefault();
        }
      });

      function showFieldError(input, message) {
        clearFieldError(input);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'absolute left-0 right-0 mt-1 text-xs text-red-600';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
        input.classList.add('border-red-500');
      }

      function clearFieldError(input) {
        const errorDiv = input.parentNode.querySelector('.text-red-600');
        if (errorDiv) {
          errorDiv.remove();
        }
        input.classList.remove('border-red-500');
      }

      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      }
    });
  </script>
</body>

</html>
