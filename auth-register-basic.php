<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require './database/dbconfig.php';
require 'send_email.php';

function generateReferralCode($username) {
    $cleanedUsername = strtoupper(preg_replace("/[^A-Z]/i", '', $username));
    $shuffled = str_shuffle($cleanedUsername);
    $prefix = substr($shuffled, 0, 3);
    $randomNumber = rand(100, 999);
    return $prefix . $randomNumber;
}

define('BASE_URL', 'http://localhost/cashstack/auth-register-basic.php'); 

// Check if a refcode exists in the URL
if (isset($_GET['ref']) && !empty(trim($_GET['ref']))) {
    $refcode = trim($_GET['ref']);
    $referrerQuery = "SELECT id FROM users WHERE refCode = ?";
    $stmtReferrer = $conn->prepare($referrerQuery);
    $stmtReferrer->bind_param("s", $refcode);
    $stmtReferrer->execute();
    $referrerResult = $stmtReferrer->get_result();

    if ($referrerResult->num_rows > 0) {
        $referrer = $referrerResult->fetch_assoc();
        $_SESSION['referrer_id'] = $referrer['id']; // Store referrer ID in session
        $_SESSION['refcode'] = $refcode; // Optionally store the refcode
    } else {
        $_SESSION['referrer_id'] = null; // No valid referrer found
    }

    $stmtReferrer->close();
}

// POST handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = '';
    $messageType = '';

    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : '';
    $uploadedRefcode = $_SESSION['refcode'] ?? null;

    if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        $destPath = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $uploadDir = 'profileImages/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destPath = $uploadDir . $fileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $message = "Failed to upload profile picture.";
                $messageType = "error";
            }
        }

        $checkUserQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($checkUserQuery);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or Email already exists. Please choose another.";
            $messageType = "error";
        } else {
            $refcode = generateReferralCode($username);
            $referralLink = BASE_URL . "?ref=" . $refcode;

            $insertQuery = "INSERT INTO users (fullname, username, email, password, profile_picture, refCode, referralLink) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sssssss", $fullname, $username, $email, $password, $destPath, $refcode, $referralLink);

            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                $_SESSION['userId'] = $newUserId;
                $_SESSION['username'] = $username;
                $message = "Registration successful! Your referral link: " . $referralLink;
                $messageType = "success";

                if (!empty($uploadedRefcode)) {
                    $referrerId = $_SESSION['referrer_id'] ?? null;

                    if ($referrerId) {
                        $insertReferralQuery = "INSERT INTO referrals_table (referral_id, referred_fullname, referred_email, referred_id) VALUES (?, ?, ?, ?)";
                        $stmtReferral = $conn->prepare($insertReferralQuery);
                        $stmtReferral->bind_param("isss", $referrerId, $fullname, $email, $newUserId);

                        if ($stmtReferral->execute()) {
                            $emailMessage = "Congrats! Your account has been created successfully with username: `$username` and password: `$password`. Please keep this information safe. To start investing, log into your dashboard and navigate to the investment page to choose a package. For any complaints, our customer support is always available 24/7.";
                            $subject = "Registration Successful";

                            sendEmail($fullname, $email, $emailMessage, $subject);
                        } else {
                            $message = "Failed to record the referral information.";
                            $messageType = "error";
                        }

                        $stmtReferral->close();
                    }
                }

                header('Location: dashboard.php');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }
        }

        $stmt->close();
        $conn->close();
    }

    $_SESSION['message'] = $message;
    $_SESSION['messageType'] = $messageType;
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

    <title>Register now</title>

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
    <div class="loader" style="display: none;">
    <div class="cube">
        <div class="face front"></div>
        <div class="face back"></div>
        <div class="face left"></div>
        <div class="face right"></div>
        <div class="face top"></div>
        <div class="face bottom"></div>
    </div>
    <script>
    function showLoader() {
        const loader = document.querySelector('.loader');
        loader.style.display = 'flex';
    }

    window.addEventListener("load", function() {
        const loader = document.querySelector('.loader');
        loader.style.display = 'none'; 
    });
</script>
</div>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <!-- Register Card -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center" style="margin-left: -35px;">
                <a href="#" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                   <img src="./assets/logo.png" alt="" style="width:150px">
                  </span>
              </a>
              </div>
              <!-- /Logo -->
              <h4 class="mb-2">Adventure starts here 🚀</h4>
              <p class="mb-4">Lets make investing easy and fun!</p>
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
              <form id="formAuthentication" class="mb-3" action="" method="POST" enctype="multipart/form-data" onsubmit="showLoader()">
    <div class="mb-3">
        <label for="fullname" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Enter your Fullname" required />
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required />
    </div>
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required />
    </div>
    <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">Password</label>
        <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control" name="password" placeholder="••••••••" required />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
    </div>
 

    <button class="btn btn-primary d-grid w-100" type="submit">Sign up</button>
</form>


              
              

              <p class="text-center">
                <span>Already have an account?</span>
                <a href="login.php">
                  <span>Sign in instead</span>
                </a>
              </p>
              <p class="text-center">
                <span>Or</span>
                <a href="index.html">
                  <span>Go Home</span>
                </a>
              </p>
            </div>
          </div>
          <!-- Register Card -->
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
    <!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/673b39204304e3196ae478c6/1icvlea1t';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
  </body>
</html>
