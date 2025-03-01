<?php
session_start();
if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
} else {
    header('Location: ..index.php');
    exit(); 
}

include '../database/dbconfig.php'; 

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
    include '../database/dbconfig.php'; 

    // Capture the existing values
    $existingFullname = $userDetails['fullname'];
    $existingUsername = $userDetails['username'];
    $existingPhoneNumber = $userDetails['phoneNumber'];
    $existingAddress = $userDetails['address'];
    $existingState = $userDetails['state'];
    $existingidNumber = $userDetails['idNumber'];
    $existingidType = $userDetails['idType'];

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
    if (!empty($_POST['state']) && $_POST['state'] !== $existingState) {
        $updateFields[] = "state = ?";
        $params[] = $_POST['state'];
        $paramTypes .= "s";
    }
    if (!empty($_POST['idNumber']) && $_POST['idNumber'] !== $existingidNumber) {
        $updateFields[] = "idNumber = ?";
        $params[] = $_POST['idNumber'];
        $paramTypes .= "s";
    }
    if (!empty($_POST['idType']) && $_POST['idType'] !== $existingidType) {
      $updateFields[] = "idType = ?";
      $params[] = $_POST['idType'];
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
    <link rel="stylesheet" href="darkmode.css" />
    <link rel="stylesheet" href="./vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="./vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="./jss/config.js"></script>
    <link rel="stylesheet" href="style.css" />
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
          <div class="app-brand demo">
            <a href="index.php" class="app-brand-link">
              <span class="app-brand-logo demo">
                <svg
                  width="25"
                  viewBox="0 0 25 42"
                  version="1.1"
                  xmlns="http://www.w3.org/2000/svg"
                  xmlns:xlink="http://www.w3.org/1999/xlink"
                >
                  <defs>
                    <path
                      d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                      id="path-1"
                    ></path>
                    <path
                      d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                      id="path-3"
                    ></path>
                    <path
                      d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                      id="path-4"
                    ></path>
                    <path
                      d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                      id="path-5"
                    ></path>
                  </defs>
                  <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                      <g id="Icon" transform="translate(27.000000, 15.000000)">
                        <g id="Mask" transform="translate(0.000000, 8.000000)">
                          <mask id="mask-2" fill="white">
                            <use xlink:href="#path-1"></use>
                          </mask>
                          <use fill="#696cff" xlink:href="#path-1"></use>
                          <g id="Path-3" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-3"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                          </g>
                          <g id="Path-4" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-4"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                          </g>
                        </g>
                        <g
                          id="Triangle"
                          transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) "
                        >
                          <use fill="#696cff" xlink:href="#path-5"></use>
                          <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                        </g>
                      </g>
                    </g>
                  </g>
                </svg>
              </span>
              <span class="app-brand-text demo menu-text fw-bolder ms-2" id="diva">CashStack</span>
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
            <li class="menu-item">
              <a href="referal.php" class="menu-link">
                  <i class="bx bx-user me-2"></i>
                <div data-i18n="Layouts">Referal</div>
              </a>

            </li>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Pages</span>
            </li>
            
            <li class="menu-item">
              <a href="wallet.php" class="menu-link">
                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                <div data-i18n="Authentications">Wallet</div>
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
                <li class="nav-item navbar-dropdown dropdown-user dropdown" id="diva">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online" id="diva">
                      <img  src="../<?php echo htmlspecialchars($userDetails['profile_picture']); ?>" alt class="w-px-30 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                      
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img  src="../<?php echo htmlspecialchars($userDetails['profile_picture']); ?>" alt class="w-px-40 h-auto rounded-circle" />
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
                      <a class="dropdown-item" href="../pages-account-settings-account.php">
                        <span class="d-flex align-items-center align-middle">
                          <i class="flex-shrink-0 bx bx-layout me-2"></i>
                          <span class="flex-grow-1 align-middle">Light Mode</span>
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
  src="../<?php echo htmlspecialchars($userDetails['profile_picture'] ?? '', ENT_QUOTES); ?>"
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
                            <label for="firstName" class="form-label">Username</label>
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
                          <div class="mb-3 col-md-6">
    <label for="state" class="form-label">State</label>
    <select class="form-control" id="state" name="state">
        <option value="">Select State</option>
        <option value="Abia" <?= isset($userDetails['state']) && $userDetails['state'] === 'Abia' ? 'selected' : '' ?>>Abia</option>
        <option value="Adamawa" <?= isset($userDetails['state']) && $userDetails['state'] === 'Adamawa' ? 'selected' : '' ?>>Adamawa</option>
        <option value="Akwa Ibom" <?= isset($userDetails['state']) && $userDetails['state'] === 'Akwa Ibom' ? 'selected' : '' ?>>Akwa Ibom</option>
        <option value="Anambra" <?= isset($userDetails['state']) && $userDetails['state'] === 'Anambra' ? 'selected' : '' ?>>Anambra</option>
        <option value="Bauchi" <?= isset($userDetails['state']) && $userDetails['state'] === 'Bauchi' ? 'selected' : '' ?>>Bauchi</option>
        <option value="Bayelsa" <?= isset($userDetails['state']) && $userDetails['state'] === 'Bayelsa' ? 'selected' : '' ?>>Bayelsa</option>
        <option value="Benue" <?= isset($userDetails['state']) && $userDetails['state'] === 'Benue' ? 'selected' : '' ?>>Benue</option>
        <option value="Borno" <?= isset($userDetails['state']) && $userDetails['state'] === 'Borno' ? 'selected' : '' ?>>Borno</option>
        <option value="Cross River" <?= isset($userDetails['state']) && $userDetails['state'] === 'Cross River' ? 'selected' : '' ?>>Cross River</option>
        <option value="Delta" <?= isset($userDetails['state']) && $userDetails['state'] === 'Delta' ? 'selected' : '' ?>>Delta</option>
        <option value="Ebonyi" <?= isset($userDetails['state']) && $userDetails['state'] === 'Ebonyi' ? 'selected' : '' ?>>Ebonyi</option>
        <option value="Edo" <?= isset($userDetails['state']) && $userDetails['state'] === 'Edo' ? 'selected' : '' ?>>Edo</option>
        <option value="Ekiti" <?= isset($userDetails['state']) && $userDetails['state'] === 'Ekiti' ? 'selected' : '' ?>>Ekiti</option>
        <option value="Enugu" <?= isset($userDetails['state']) && $userDetails['state'] === 'Enugu' ? 'selected' : '' ?>>Enugu</option>
        <option value="Gombe" <?= isset($userDetails['state']) && $userDetails['state'] === 'Gombe' ? 'selected' : '' ?>>Gombe</option>
        <option value="Imo" <?= isset($userDetails['state']) && $userDetails['state'] === 'Imo' ? 'selected' : '' ?>>Imo</option>
        <option value="Jigawa" <?= isset($userDetails['state']) && $userDetails['state'] === 'Jigawa' ? 'selected' : '' ?>>Jigawa</option>
        <option value="Kaduna" <?= isset($userDetails['state']) && $userDetails['state'] === 'Kaduna' ? 'selected' : '' ?>>Kaduna</option>
        <option value="Kano" <?= isset($userDetails['state']) && $userDetails['state'] === 'Kano' ? 'selected' : '' ?>>Kano</option>
        <option value="Kogi" <?= isset($userDetails['state']) && $userDetails['state'] === 'Kogi' ? 'selected' : '' ?>>Kogi</option>
        <option value="Kwara" <?= isset($userDetails['state']) && $userDetails['state'] === 'Kwara' ? 'selected' : '' ?>>Kwara</option>
        <option value="Lagos" <?= isset($userDetails['state']) && $userDetails['state'] === 'Lagos' ? 'selected' : '' ?>>Lagos</option>
        <option value="Nasarawa" <?= isset($userDetails['state']) && $userDetails['state'] === 'Nasarawa' ? 'selected' : '' ?>>Nasarawa</option>
        <option value="Niger" <?= isset($userDetails['state']) && $userDetails['state'] === 'Niger' ? 'selected' : '' ?>>Niger</option>
        <option value="Ogun" <?= isset($userDetails['state']) && $userDetails['state'] === 'Ogun' ? 'selected' : '' ?>>Ogun</option>
        <option value="Ondo" <?= isset($userDetails['state']) && $userDetails['state'] === 'Ondo' ? 'selected' : '' ?>>Ondo</option>
        <option value="Osun" <?= isset($userDetails['state']) && $userDetails['state'] === 'Osun' ? 'selected' : '' ?>>Osun</option>
        <option value="Oyo" <?= isset($userDetails['state']) && $userDetails['state'] === 'Oyo' ? 'selected' : '' ?>>Oyo</option>
        <option value="Plateau" <?= isset($userDetails['state']) && $userDetails['state'] === 'Plateau' ? 'selected' : '' ?>>Plateau</option>
        <option value="Rivers" <?= isset($userDetails['state']) && $userDetails['state'] === 'Rivers' ? 'selected' : '' ?>>Rivers</option>
        <option value="Sokoto" <?= isset($userDetails['state']) && $userDetails['state'] === 'Sokoto' ? 'selected' : '' ?>>Sokoto</option>
        <option value="Taraba" <?= isset($userDetails['state']) && $userDetails['state'] === 'Taraba' ? 'selected' : '' ?>>Taraba</option>
        <option value="Yobe" <?= isset($userDetails['state']) && $userDetails['state'] === 'Yobe' ? 'selected' : '' ?>>Yobe</option>
        <option value="Zamfara" <?= isset($userDetails['state']) && $userDetails['state'] === 'Zamfara' ? 'selected' : '' ?>>Zamfara</option>
    </select>
</div>

<div class="mb-3 col-md-6">
    <label for="idType" class="form-label">Select ID type</label>
    <?php if (!empty($userDetails['idType'])): ?>
        <p class="form-control-plaintext">ID already set</p>
    <?php else: ?>
    <select class="form-control" id="idType" name="idType">
      <option value="NIN">NIN</option>
      <option value="Driving License">Driving License</option>
    </select>
    <?php endif; ?>
</div>
<div class="mb-3 col-md-6">
    <?php if (!empty($userDetails['idNumber'])): ?>
        <p class="form-control-plaintext"></p>
    <?php else: ?>
      <label for="bvn" class="form-label">ID number</label>
        <input 
            class="form-control" 
            type="text" 
            id="idNumber" 
            name="idNumber" 
            placeholder="Enter ID number" 
        />
    <?php endif; ?>
</div>


                        </div>
                        <div class="mt-2">
                          <button type="submit" class="btn btn-primary me-2">Save changes</button>
                          <button type="reset" class="btn btn-outline-secondary">Cancel</button>
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
