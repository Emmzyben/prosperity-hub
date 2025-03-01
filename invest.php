<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php';
require 'send_email.php';
$userId = $_SESSION['userId'];

// Fetch user's details including balance
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
    $userBalance = $userDetails['balance']; // User's balance
    $fullName = $userDetails['fullname'];
    $email = $userDetails['email'];
} else {
    $_SESSION['message'] = "No user found.";
    $_SESSION['messageType'] = "error";
    header('Location: invest.php');
    exit();
}

$stmt->close();

// Check if the investment form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package = $_POST['package'];
    $packageAmount = $_POST['amount'];

    // Check if user has enough balance to invest
    if ($userBalance >= $packageAmount) {
        // Check if the user has already invested
        $investmentCheckSql = "SELECT * FROM investment WHERE id = ?";
        $investmentCheckStmt = $conn->prepare($investmentCheckSql);
        $investmentCheckStmt->bind_param("i", $userId);
        $investmentCheckStmt->execute();
        $investmentCheckResult = $investmentCheckStmt->get_result();

        if ($investmentCheckResult->num_rows > 0) {
            $_SESSION['message'] = "There is an ongoing investment.";
            $_SESSION['messageType'] = "error";
        } else {
            // Insert the investment into the investments table
            $sql = "INSERT INTO investment (id, package, package_amount) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $userId, $package, $packageAmount);

            if ($stmt->execute()) {
                // Deduct the package amount from the user's balance
                $newBalance = $userBalance - $packageAmount;
                $updateBalanceSql = "UPDATE users SET balance = ? WHERE id = ?";
                $updateBalanceStmt = $conn->prepare($updateBalanceSql);
                $updateBalanceStmt->bind_param("di", $newBalance, $userId);

                if ($updateBalanceStmt->execute()) {
                    // Update the user's subscription date
                    $subscriptionDate = date('Y-m-d'); // Current date
                    $updateSql = "UPDATE users SET subscription_date = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $subscriptionDate, $userId);

                    if ($updateStmt->execute()) {
                        // Update the referral package if applicable
                        $referralSql = "SELECT referred_id FROM referrals_table WHERE referred_id = ?";
                        $referralStmt = $conn->prepare($referralSql);
                        $referralStmt->bind_param("i", $userId);
                        $referralStmt->execute();
                        $referralResult = $referralStmt->get_result();

                        if ($referralResult->num_rows > 0) {
                            // Update the package for the referred user
                            $referral = $referralResult->fetch_assoc();
                            $referredId = $referral['referred_id'];

                            $updateReferralSql = "UPDATE referrals_table SET package = ? WHERE referred_id = ?";
                            $updateReferralStmt = $conn->prepare($updateReferralSql);
                            $updateReferralStmt->bind_param("si", $package, $referredId);

                            if (!$updateReferralStmt->execute()) {
                                $_SESSION['message'] = "Investment successful, but error updating referral package.";
                                $_SESSION['messageType'] = "error";
                            }
                            $updateReferralStmt->close();
                        }

                        $_SESSION['message'] = "Investment successful!";
                        $_SESSION['messageType'] = "success";
                        $message = "You have successfully invested in the `$package` investment package. You can log in daily to check your earnings";
                        $subject = "Investment Successful";

                        if (sendEmail($fullName, $email, $message, $subject)) {
                            // Email sent successfully
                        }
                    } else {
                        $_SESSION['message'] = "Investment successful, but error updating subscription date.";
                        $_SESSION['messageType'] = "error";
                    }

                    $updateStmt->close();
                } else {
                    $_SESSION['message'] = "Investment successful, but error deducting balance.";
                    $_SESSION['messageType'] = "error";
                }

                $updateBalanceStmt->close();
            } else {
                $_SESSION['message'] = "Error during investment.";
                $_SESSION['messageType'] = "error";
            }

            $stmt->close();
        }

        // Close the investment check statement
        $investmentCheckStmt->close();
    } else {
        $_SESSION['message'] = "Insufficient balance to invest in this package.";
        $_SESSION['messageType'] = "balanceError";
    }

    header('Location: invest.php');
    exit();
}

$conn->close();

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

    <title>Wallet - Cashstack</title>

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
          .invest{
    display: flex;flex-direction: row;justify-content: center;align-items: center;flex-wrap:wrap
  }
  .invest>div{
    padding: 10px;margin: 20px;box-shadow: 2px 2px 10px #696cff;
    border-radius:10px;text-align:center;width: 25%;
  }
  #ps{
    margin:25px
  }
  #ps>p{
    border-bottom:1px solid #d5d5f538
  }
  .invest>div:hover{
    box-shadow: 5px 5px 10px #696cff;
    transition:4s;
  }
  @media only screen and (max-width: 900px) {
     .invest>div{
        width: 100%;font-size:13px
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

<script>
  // Fetch the message from PHP
  var message = "<?php echo $message; ?>";
  var popupMessage = document.getElementById("popupMessage");
  var popupOverlay = document.getElementById("popupOverlay");
  var closeButton = document.getElementById("closeButton");

  // Check if there is a message to display
  if (message) {
    // Show the overlay and message
    popupOverlay.style.display = "block";
    popupMessage.style.display = "block";

    // Determine action based on message type
    <?php if ($messageType === 'balanceError') : ?>
      // Redirect to fund.php after 2 seconds for balance errors
      setTimeout(function() {
        window.location.href = 'fund.php';
      }, 2000); 
    <?php elseif ($messageType === 'success') : ?>
      // Redirect to dashboard.php after 2 seconds for success messages
      setTimeout(function() {
        window.location.href = 'dashboard.php';
      }, 2000); 
    <?php else : ?>
      // Hide the message after 5 seconds for other messages
      setTimeout(function() {
        popupMessage.style.display = "none";
        popupOverlay.style.display = "none";
      }, 5000); 

      // Close the popup when the close button is clicked
      closeButton.addEventListener("click", function() {
        popupMessage.style.display = "none";
        popupOverlay.style.display = "none";
      });
    <?php endif; ?>
  }
</script>


        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo" style="margin-left: -20px">
          <a href="" class="app-brand-link">
            <span class="app-brand-logo demo">
                   <img src="picture/section.png" alt="" style="width:100px">
                  </span>
                  <span class="app-brand-text demo text-body fw-bolder" style="margin-left: -28px;color:black">ashStack</span>
             
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
            <li class="menu-item ">
                <a href="referal.php" class="menu-link">
                    <i class="bx bx-user me-2"></i>
                  <div data-i18n="Layouts">Referal</div>
                </a>
  
              </li>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Pages</span>
            </li>
            
            <li class="menu-item ">
              <a href="wallet.php" class="menu-link">
                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                <div data-i18n="Authentications">Wallet</div>
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
                <h5 class="card-title">Investment <span style="color:#696cff">Plans</span></h5>
                <p class="mb-4">To make a solid investment, you have to know where you are investing, find a plan which is best for you</p>
            </div>
          
            <div class="invest" id="diva">
    <!-- Basic Package -->
    <div id="basic">
        <h4 class="card-title text-primary">Basic</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦3000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Basic">
            <input type="hidden" name="package" value="Basic">
            <input type="hidden" name="amount" value="3000">
            <button type="button" class="btn btn-primary" onclick="showModal('Basic', 3000)">Invest Now</button>
        </form>
    </div>

    <!-- Bronze Package -->
    <div id="bronze">
        <h4 class="card-title text-primary">Bronze</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦6000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Bronze">
            <input type="hidden" name="package" value="Bronze">
            <input type="hidden" name="amount" value="6000">
            <button type="button" class="btn btn-primary" onclick="showModal('Bronze', 6000)">Invest Now</button>
        </form>
    </div>

    <!-- Additional Packages (Silver, Gold, Diamond, Platinum) -->
    <div id="silver">
        <h4 class="card-title text-primary">Silver</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦9000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Silver">
            <input type="hidden" name="package" value="Silver">
            <input type="hidden" name="amount" value="9000">
            <button type="button" class="btn btn-primary" onclick="showModal('Silver', 9000)">Invest Now</button>
        </form>
    </div>

      <div id="gold">
        <h4 class="card-title text-primary">Gold</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦12000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Gold">
            <input type="hidden" name="package" value="Gold">
            <input type="hidden" name="amount" value="12000">
            <button type="button" class="btn btn-primary" onclick="showModal('Gold', 12000)">Invest Now</button>
        </form>
    </div>
    
     <div id="diamond">
        <h4 class="card-title text-primary">Diamond</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦15000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Diamond">
            <input type="hidden" name="package" value="Diamond">
            <input type="hidden" name="amount" value="15000">
            <button type="button" class="btn btn-primary" onclick="showModal('Diamond', 15000)">Invest Now</button>
        </form>
    </div>
    <div id="platinum">
        <h4 class="card-title text-primary">Platinum</h4>
        <div id="ps">
            <p>Return 1.49%</p>
            <p>Every Day</p>
            <p>A Month</p>
            <p>Total 30%</p>
        </div>
        <p class="card-title text-primary"><b>₦18000</b></p>

        <form action="" method="POST" onsubmit="showLoader()" data-package="Platinum">
            <input type="hidden" name="package" value="Platinum">
            <input type="hidden" name="amount" value="18000">
            <button type="button" class="btn btn-primary" onclick="showModal('Platinum', 18000)">Invest Now</button>
        </form>
    </div>
</div>


                 </div>


            
         <div id="confirmationModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h4 id="modalTitle"></h4>
        <p id="modalBody"></p>
        <button class="btn btn-primary" onclick="submitForm()">Yes, Proceed</button>
        <br>
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
    </div>
</div>

<script>
    let selectedPackage = '';
    let selectedAmount = '';

    function showModal(packageName, amount) {
        selectedPackage = packageName;
        selectedAmount = amount;

        // Update modal content
        document.getElementById('modalTitle').innerText = `Confirm Investment in ${packageName}`;
        document.getElementById('modalBody').innerText = `Are you sure you want to invest ₦${amount} in the ${packageName} package?`;

        // Show modal
        document.getElementById('confirmationModal').style.display = 'block';
    }

    function closeModal() {
        // Hide modal
        document.getElementById('confirmationModal').style.display = 'none';
    }

    function submitForm() {
        // Find the form corresponding to the selected package and submit it
        const form = document.querySelector(`form[data-package="${selectedPackage}"]`);
        if (form) {
            form.submit();
        } else {
            console.error('Form not found for package:', selectedPackage);
        }
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('confirmationModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
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
