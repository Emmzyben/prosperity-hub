<?php
session_start(); // Start session for storing messages
require 'send_email.php';
require './database/dbconfig.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['message'] = "Email address is required.";
        $_SESSION['messageType'] = "error";
        header("Location: auth-forgot-password-basic.php"); // Redirect back to the form
        exit();
    }

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['messageType'] = "error";
        header("Location: auth-forgot-password-basic.php");
        exit();
    }

    // Check if the email exists in the users table
    $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
    $stmtCheck = $conn->prepare($checkEmailQuery);
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows == 0) {
        $_SESSION['message'] = "No account found with this email.";
        $_SESSION['messageType'] = "error";
        header("Location: auth-forgot-password-basic.php");
        exit();
    }

    // Generate a secure token and calculate expiration time
    $token = bin2hex(random_bytes(32)); // 64-character token
    $expirationTime = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

    // Save the token and expiration time in the password_resets table
    $insertTokenQuery = "INSERT INTO password_resets (email, token, expiration) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($insertTokenQuery);
    $stmtInsert->bind_param("sss", $email, $token, $expirationTime);

    if ($stmtInsert->execute()) {
        // Generate the reset link
        $base_Url = "http://localhost/cashstack/passwordReset.php"; 
       $resetLink = $base_Url . "?token=" . $token . "&email=" . urlencode($email);


        // Email message
        $message = "
                    <p>Please click the link below to reset your password:</p>
                    <p><a href=\"$resetLink\">Reset Password</a></p>
                    <p>This link will expire in 1 hour. If you did not request this, please ignore this email.</p>";

        $subject = "Reset Password";

        // Send the email
        if (sendEmail("User", $email, $message, $subject)) {
            $_SESSION['message'] = "Password reset email sent. Please check your email.";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Failed to send email.";
            $_SESSION['messageType'] = "error";
        }
    } else {
        $_SESSION['message'] = "Failed to save reset token. Please try again.";
        $_SESSION['messageType'] = "error";
    }

    // Close the statement and connection
    $stmtInsert->close();
    $stmtCheck->close();
    $conn->close();

    // Redirect back to the form with message
    header("Location: auth-forgot-password-basic.php");
    exit();
}

if (isset($_SESSION['message'])) {
  $message = $_SESSION['message'];
  $messageType = $_SESSION['messageType'];
  
  unset($_SESSION['message']);
  unset($_SESSION['messageType']);
} else {
  $message = '';
  $messageType = '';
}
?>


<!DOCTYPE html>

<html
  lang="en"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Forgot Password - CashStack</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="./vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="./vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="./vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="./vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="./vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="./jss/config.js"></script>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="darkmode.css" />
    <link rel="stylesheet" href="loader.css" />
  </head>

  <body>
    <!-- Content -->

    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
          <!-- Forgot Password -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center" style="margin-left: -35px;">
                <a href="#" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                   <img src="picture/logo.png" alt="" style="width:100px">
                  </span>
                  <span class="app-brand-text demo text-body fw-bolder" style="margin-left: -30px;">ashStack</span>
                </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-2">Forgot Password? 🔒</h4>
              <p class="mb-4">Enter your email and we'll send you instructions to reset your password</p>
              <form id="formAuthentication" class="mb-3" action="" method="POST">
              <div id="popupOverlay" class="popup-overlay"></div>
    <div id="popupMessage" class="popup-message <?php echo $messageType == 'success' ? 'popup-success' : 'popup-error'; ?>">
      <?php echo $message; ?><br>
      <button id="closeButton" class="close-btn">Close</button>
    </div>
    <script>
      var message = "<?php echo $message; ?>";
      var popupMessage = document.getElementById("popupMessage");
      var popupOverlay = document.getElementById("popupOverlay");
      var closeButton = document.getElementById("closeButton");

      if (message) {
        popupOverlay.style.display = "block";
        popupMessage.style.display = "block";

        // Hide the popup automatically after 5 seconds
        setTimeout(function() {
          popupMessage.style.display = "none";
          popupOverlay.style.display = "none";
        }, 5000); // Adjust time as needed
      }

      // Close the popup when the close button is clicked
      closeButton.addEventListener("click", function() {
        popupMessage.style.display = "none";
        popupOverlay.style.display = "none";
      });
    </script>
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="text"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    autofocus
                    require
                  />
                </div>
                <button type="submit" class="btn btn-primary d-grid w-100">Send Reset Link</button>
              </form>
              <div class="text-center">
                <a href="login.php" class="d-flex align-items-center justify-content-center">
                  <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                  Back to login
                </a>
              </div>
            </div>
          </div>
          <!-- /Forgot Password -->
        </div>
      </div>
    </div>

    <!-- / Content -->

   

 <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="./vendor/libs/jquery/jquery.js"></script>
    <script src="./vendor/libs/popper/popper.js"></script>
    <script src="./vendor/js/bootstrap.js"></script>
    <script src="./vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="./vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="./vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="./jss/main.js"></script>

    <!-- Page JS -->
    <script src="./jss/dashboards-analytics.js"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="script.js"></script>
  </body>
</html>
