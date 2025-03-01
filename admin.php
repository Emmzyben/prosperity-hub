<?php
session_start();
require './database/dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch and sanitize form data
    $emailOrUsername = htmlspecialchars(trim($_POST['email-username']));
    $password = trim($_POST['password']);

    // Prepare query with placeholders
    $query = "SELECT id, password, username FROM admin WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Directly compare plain text password
        if ($password === $user['password']) {
            // Set session variables on successful login
            $_SESSION['userId'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to the dashboard
            header('Location: ./adminDashboard/dashboard.php');
            exit();
        } else {
            // Set error message for invalid password
            $message = "Invalid password. Please try again.";
            $messageType = "error";
        }
    } else {
        // Set error message if no matching user is found
        $message = "No user found with that username.";
        $messageType = "error";
    }

    // Set session messages
    $_SESSION['message'] = $message;
    $_SESSION['messageType'] = $messageType;

    // Close connections
    $stmt->close();
    $conn->close();
}

// Display any session message on the HTML page
if (isset($_SESSION['message'])) {
    $message = htmlspecialchars($_SESSION['message']); // HTML escape to prevent XSS
    $messageType = $_SESSION['messageType'];

    // Unset session variables after displaying the message
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

    <title>Login</title>

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
    <link rel="stylesheet" href="loader.css" />
    <link rel="stylesheet" href="style.css" />
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
          <!-- Register -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="#" class="app-brand-link gap-2">
                 
                  <span class="app-brand-text demo text-body fw-bolder">CashStack Admin Login</span>
                </a>
              </div>
              <!-- /Logo -->
              <div id="popupOverlay" class="popup-overlay"></div>
    <div id="popupMessage" class="popup-message <?php echo $messageType == 'success' ? 'popup-success' : 'popup-error'; ?>">
      <?php echo $message; ?><br>
      <button id="closeButton" class="close-btn">Close</button>
    </div>
    <script>
      // Check if there's a message to display
      var message = "<?php echo $message; ?>";
      var popupMessage = document.getElementById("popupMessage");
      var popupOverlay = document.getElementById("popupOverlay");
      var closeButton = document.getElementById("closeButton");

      if (message) {
        // Show the popup and overlay
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
              <form id="formAuthentication" class="mb-3" action="" method="POST" onsubmit="showLoader()">
                <div class="mb-3">
                  <label for="email" class="form-label">Username</label>
                  <input
                    type="text"
                    class="form-control"
                    id="email"
                    name="email-username"
                    placeholder="Enter your email or username"
                    autofocus
                  />
                </div>
                <div class="mb-3 form-password-toggle">
                  <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">Password</label>
                   
                  </div>
                  <div class="input-group input-group-merge">
                    <input
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password"
                    />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>
                
                <div class="mb-3">
                  <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                </div>
              </form>

             
            </div>
          </div>
          <!-- /Register -->
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
