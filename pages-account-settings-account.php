<?php
session_start();
if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
} else {
    header('Location: login.php');
    exit(); 
}

include './database/dbconfig.php'; 

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
} else {
    $userDetails = null; 
}

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include './database/dbconfig.php'; 

    // Capture the existing values
    $existingFullname = $userDetails['fullname'];
    $existingUsername = $userDetails['username'];
    $existingPhoneNumber = $userDetails['phoneNumber'];
    $existingAddress = $userDetails['address'];

    // Prepare an array to hold the updated fields
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    // Check and prepare fields to update
    if (!empty($_POST['username']) && $_POST['username'] !== $existingUsername) {
      $updateFields[] = "username = ?";
      $params[] = $_POST['username'];
      $paramTypes .= "s";
  }
    if (!empty($_POST['fullname']) && $_POST['fullname'] !== $existingFullname) {
        $updateFields[] = "fullname = ?";
        $params[] = $_POST['fullname'];
        $paramTypes .= "s";
    }
    if (!empty($_POST['phoneNumber']) && $_POST['phoneNumber'] !== $existingPhoneNumber) {
        $updateFields[] = "phoneNumber = ?";
        $params[] = $_POST['phoneNumber'];
        $paramTypes .= "s";
    }
    if (!empty($_POST['address']) && $_POST['address'] !== $existingAddress) {
        $updateFields[] = "address = ?";
        $params[] = $_POST['address'];
        $paramTypes .= "s";
    }
  

    // Only update if there are fields to update
    if (!empty($updateFields)) {
        // Join the fields for the query
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $userId;  // Add the userId to the parameters
        $paramTypes .= "i";    // userId is an integer

        // Prepare the update statement
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($paramTypes, ...$params);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully.";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $stmt->error;
            $_SESSION['messageType'] = "danger";
        }

        $stmt->close();
    } else {
        $_SESSION['message'] = "No changes made to the profile.";
        $_SESSION['messageType'] = "info";
    }

    $conn->close();
    header('Location: pages-account-settings-account.php');
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
  class="light-style layout-menu-fixed"
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

    <title>Account settings </title>

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

    <link rel="stylesheet" href="./vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->

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
    <!-- Layout wrapper -->
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
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->
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
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo" style="margin-left: -20px">
            <a href="" class="app-brand-link">
            <span class="app-brand-logo demo">
                   <img src="./assets/logo.png" alt="" style="width:80px">
                  </span>
      
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboard -->
            <li class="menu-item ">
              <a href="dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>

        <li class="menu-item">
              <a href="invest.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Invest</div>
              </a>
            </li>
       
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Pages</span>
            </li>
            
             
            <li class="menu-item">
              <a href="withdrawal.php" class="menu-link">
                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                <div data-i18n="Authentications">Withdrawal</div>
              </a>
            </li>
            <li class="menu-item active">
              <a href="pages-account-settings-account.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div data-i18n="Account Settings">Account Settings</div>
              </a>    
            </li>
            <li class="menu-item">
              <a href="logout.php" class="menu-link">
              <i class="bx bx-power-off me-2"></i>
                <div data-i18n="logout.php">Log Out</div>
              </a>    
            </li>
         
          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                
                </div>
              </div>
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Place this tag where you want the button to render. -->
              

                <!-- User -->
                 <?php if (isset($userDetails)): ?>
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online"  id="diva">
                      <img src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/download.jpeg'; ?>"alt class="w-px-30 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                      
                        <div class="d-flex"  id="diva">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/download.jpeg'; ?>"alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block"><?php echo htmlspecialchars($userDetails['fullname']); ?></span>
                            
                          </div>
                        </div>
                        <?php endif; ?>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="pages-account-settings-account.php">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">Account</span>
                      </a>
                    </li>
                
                    <li>
                      <a class="dropdown-item" href="wallet.php">
                        <span class="d-flex align-items-center align-middle">
                          <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                          <span class="flex-grow-1 align-middle">Wallet</span>
                       </span>
                      </a>
                    </li>
                  
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="logout.php">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper" id="diva">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y" id="diva">
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Account Settings</span></h4>

              <div class="row">
                <div class="col-md-12">
                 
                  <div class="card mb-4" id="diva">
                    <h5 class="card-header">Profile Details</h5>
                    <!-- Account -->
                    <div class="card-body">
                      <div class="d-flex align-items-start align-items-sm-center gap-4">
                        <img
                           src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/download.jpeg'; ?>"
"
                          alt="user-avatar"
                          class="d-block rounded"
                          height="100"
                          width="100"
                          id="uploadedAvatar"
                        />
                        <form action="upload_photo.php" method="POST" enctype="multipart/form-data" onsubmit="showLoader()" id="diva">
                        <div class="button-wrapper">
                          <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                            <span class="d-none d-sm-block">Upload new photo</span>
                            <i class="bx bx-upload d-block d-sm-none"></i>
                            <input
        type="file"
        id="upload"
        class="account-file-input"
        name="photo"
        hidden
        accept="image/png, image/jpeg"
        onchange="this.form.submit()" 
      />
                          </label>
                          <button type="button" class="btn btn-outline-secondary account-image-reset mb-4" onclick="resetUpload()">
      <i class="bx bx-reset d-block d-sm-none"></i>
      <span class="d-none d-sm-block">Reset</span>
    </button>

                          <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p>
                        </div>
                      </div>
                    </div>
                    </form>
                    <script>
  function resetUpload() {
    document.getElementById('upload').value = '';
  }
</script>


                    <hr class="my-0" />
                    <div class="card-body" id="diva">
                      <form id="formAccountSettings" method="POST" action="" onsubmit="showLoader()">
                      <div class="row">
                          <div class="mb-3 col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input
                              class="form-control"
                              type="text"
                              id="username"
                              name="username"
                              value="<?= htmlspecialchars($userDetails['username'] ?? '', ENT_QUOTES); ?>"
                              autofocus
                            />
                          </div>
                        
                          <div class="mb-3 col-md-6">
                            <label for="firstName" class="form-label">Full Name</label>
                            <input
                              class="form-control"
                              type="text"
                              id="fullname"
                              name="fullname"
                              value="<?= htmlspecialchars($userDetails['fullname'] ?? '', ENT_QUOTES); ?>"
                              autofocus
                            />
                          </div>
</div>
                          <div class="row">
                          <div class="mb-3 col-md-6">
                            <label class="form-label" for="phoneNumber">Phone Number</label>
                            <div class="input-group input-group-merge">
                              <span class="input-group-text">NGN (+234)</span>
                              <input
                                type="text"
                                id="phoneNumber"
                                name="phoneNumber"
                                class="form-control"
                              value="<?= htmlspecialchars($userDetails['phoneNumber'] ?? '', ENT_QUOTES); ?>"

                                placeholder=""
                              />
                            </div>
                          </div>
                          <div class="mb-3 col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="" 
                            value="<?= htmlspecialchars($userDetails['address'] ?? '', ENT_QUOTES); ?>"
                            
                            />
                          </div>
</div>

 <div class="mt-2">
                          <button type="submit" class="btn btn-primary me-2">Save changes</button>
                          <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                        </div>
                        </div>
                       
                      </form>
                    </div>
                    <!-- /Account -->
                  </div>
            


                  <div class="row" id="diva">
                
                  <form action="password_update.php" method="post">
    <div >
        <div class="card mb-4">
            <h5 class="card-header">Change Password</h5>
            <div class="card-body demo-vertical-spacing demo-only-element">
                <div class="form-password-toggle">
                    <label class="form-label" for="new-password">New Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="new-password"
                            name="password"
                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                            aria-describedby="password-icon"
                        />
                        <span id="password-icon" class="input-group-text cursor-pointer">
                            <i class="bx bx-hide"></i>
                        </span>
                    </div>
                </div>

                <div class="form-password-toggle">
                    <label class="form-label" for="confirm-password">Confirm Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                            aria-describedby="confirm-password-icon"
                        />
                        <span id="confirm-password-icon" class="input-group-text cursor-pointer">
                            <i class="bx bx-hide"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Update Password</button>
            </div>
        </div>
    </div>
</form>

</div>





<div class="card" id="diva">
    <h5 class="card-header">Delete Account</h5>
    <div class="card-body">
        <div class="mb-3 col-12 mb-0">
            <div class="alert alert-warning">
                <h6 class="alert-heading fw-bold mb-1">Are you sure you want to delete your account?</h6>
                <p class="mb-0">Once you delete your account, there is no going back. Please be certain.</p>
            </div>
        </div>
        <form id="formAccountDeactivation" action="delete.php" method="POST">
            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="accountActivation"
                    id="accountActivation"
                    required
                />
                <label class="form-check-label" for="accountActivation">
                    I confirm my account deactivation
                </label>
            </div>
            <input type="hidden" name="userId" value="<?= htmlspecialchars($userDetails['id'] ?? '', ENT_QUOTES); ?>" />
            <button type="submit" class="btn btn-danger deactivate-account">Deactivate Account</button>
        </form>
    </div>
</div>

                </div>
              </div>
            </div>
            <!-- / Content -->

            <!-- Footer -->
                       <footer class="content-footer footer bg-footer-theme" id="diva">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0" id="diva">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  , Cashstack
               </div>
                
                 
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

   

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
