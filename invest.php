
<?php
session_start();
include './database/dbconfig.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    $_SESSION['message'] = "You must log in first!";
    $_SESSION['messageType'] = "error";
    header('Location: login.php');
    exit();
}

// Get user details
$userId = $_SESSION['userId'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
    $userBalance = $userDetails['balance'];
    $fullName = $userDetails['fullname'];
} else {
    $_SESSION['message'] = "User not found.";
    $_SESSION['messageType'] = "error";
    header('Location: invest.php');
    exit();
}

// Close statement
$stmt->close();


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

    <title>Invest Now</title>

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
    <link rel="stylesheet" href="loader.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="darkmode.css" />
    <style>
  #how2{
    margin: 30px;display: flex;flex-direction: row;justify-content:center;align-items: center;flex-wrap: wrap;
  padding: 30px;border-bottom: 1px solid #192838;
  }
  #how2>div{
      background: linear-gradient(180deg, #192838 0%, #12182B 100%);
      border: 0.5px solid;
      border: 1px solid #34ffb536;padding: 10px;flex-direction: column;margin: 10px;
      color: #F4F4F4;border-radius: 10px;width: 20%;height: auto;display: flex;align-items: center;justify-content: center;
      }
      #how2 h3{
      font-size: 16px;
      font-weight: 500;
      line-height: 19.36px;
      text-align: center;color: #34FFB4;
      text-underline-position: from-font;
      text-decoration-skip-ink: none;
      
      }
      #how2 p{
      font-size: 12px;
      font-weight: 400;
      line-height: 14.52px;
      text-align: center;
      text-underline-position: from-font;
      text-decoration-skip-ink: none;
      
      }
  @media only screen and (max-width: 900px) {
    #how2{
    margin: 20px;display: flex;flex-direction: row;justify-content:center;align-items: center;
  padding: 20px;
  }
  #how2>div{
   
      border: 1px solid #34ffb536;padding: 20px;flex-direction: column;margin: 10px;
      color: #F4F4F4;border-radius: 10px;width: 100%;height: auto;display: flex;align-items: center;justify-content: center;
      }
  }
    </style>
    <style>
        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
            text-align: center;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
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
  <?php if ($messageType !== 'balanceError') : ?>
    <button id="closeButton" class="close-btn">Close</button>
  <?php endif; ?>
</div>



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

            <li class="menu-item ">
              <a href="dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>

            <li class="menu-item active">
              <a href="invest.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Invest</div>
              </a>

            </li>
          
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Pages</span>
            </li>
            
             
            <li class="menu-item ">
              <a href="withdrawal.php" class="menu-link">
                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                <div data-i18n="Authentications">Withdrawal</div>
              </a>
            </li>
            <li class="menu-item">
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
          <div class="content-wrapper"  id="diva">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y"  id="diva">
             
            <div style="text-align:center"  id="diva">
                <h5 class="card-title">Investment <span style="color:#34FFB4">Plans</span></h5>
                <p class="mb-4">To make a solid investment, you have to know where you are investing, find a plan which is best for you</p>
            </div>
          
         
            <div id="how2">
    <div>
        <h3>Basic Plan</h3>
        <p>Investment</p>
        <h3>$100</h3>
        <p>-</p>
        <h3>$4999</h3>

        <p>Profit</p>
        <h3>2% Daily</h3>

        <p>Duration</p>
        <h3>6 Days</h3>

        <p>Referral Bonus</p>
        <h3>10%</h3>

        <button class="btn btn-primary" onclick="redirectToInvestment(100)">Invest Now</button>
    </div>

    <div>
        <h3>Intermediate Plan</h3>
        <p>Investment</p>
        <h3>$5000</h3>
        <p>-</p>
        <h3>$14999</h3>

        <p>Profit</p>
        <h3>2.5% Daily</h3>

        <p>Duration</p>
        <h3>6 Days</h3>

        <p>Referral Bonus</p>
        <h3>10%</h3>

        <button class="btn btn-primary" onclick="redirectToInvestment(5000)">Invest Now</button>
    </div>

    <div>
        <h3>Professional Plan</h3>
        <p>Investment</p>
        <h3>$15000</h3>
        <p>-</p>
        <h3>$59999</h3>

        <p>Profit</p>
        <h3>3% Daily</h3>

        <p>Duration</p>
        <h3>10 Days</h3>

        <p>Referral Bonus</p>
        <h3>10%</h3>

        <button class="btn btn-primary" onclick="redirectToInvestment(15000)">Invest Now</button>
    </div>

    <div>
        <h3>Expert Plan</h3>
        <p>Investment</p>
        <h3>$60000</h3>
        <p>-</p>
        <h3>$Unlimited</h3>

        <p>Profit</p>
        <h3>4% Daily</h3>

        <p>Duration</p>
        <h3>78 Days</h3>

        <p>Referral Bonus</p>
        <h3>10%</h3>

        <button class="btn btn-primary" onclick="redirectToInvestment(60000)">Invest Now</button>
    </div>
</div>

<script>
    function redirectToInvestment(amount) {
        window.location.href = "complete_invest.php?amount=" + amount;
    }
</script>


  






            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme"  id="diva">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0"  id="diva">
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
