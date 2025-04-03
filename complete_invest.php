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
$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '';
// Fetch user's details including balance
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
    $fullName = $userDetails['fullname'];
    $email = $userDetails['email'];
    $currentBalance = $userDetails['balance'];
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
    $packageAmount = (int)$_POST['investment_amount'];
    $deposit_method = $_POST['deposit_method'];

    // Process file upload for proof of payment
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === 0) {
        $uploadDir = "uploads/";
        $allowedExtensions = array("jpg", "jpeg", "png", "pdf");
        $filename = basename($_FILES['proof_of_payment']['name']);
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            $_SESSION['message'] = "Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.";
            $_SESSION['messageType'] = "error";
            header('Location: complete_invest.php');
            exit();
        }

        $fileTmpName = $_FILES['proof_of_payment']['tmp_name'];
        $newFileName = time() . "_" . $filename;
        $filePath = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmpName, $filePath)) {
            $_SESSION['message'] = "Error uploading proof of payment.";
            $_SESSION['messageType'] = "error";
            header('Location: invest.php');
            exit();
        }
        $proofOfPayment = $filePath;
    } else {
        $_SESSION['message'] = "File upload failed. Please try again.";
        $_SESSION['messageType'] = "error";
        header('Location: complete_invest.php');
        exit();
    }

    // Check if the user has already invested
    $investmentCheckSql = "SELECT * FROM investment WHERE user_id = ?";
    $investmentCheckStmt = $conn->prepare($investmentCheckSql);
    $investmentCheckStmt->bind_param("i", $userId);
    $investmentCheckStmt->execute();
    $investmentCheckResult = $investmentCheckStmt->get_result();

    if ($investmentCheckResult->num_rows > 0) {
        $_SESSION['message'] = "There is an ongoing investment.";
        $_SESSION['messageType'] = "error";
        $investmentCheckStmt->close();
        header('Location: complete_invest.php');
        exit();
    }
    $investmentCheckStmt->close();

    // Insert the investment into the `investment` table
    $status = "pending";
    $sql = "INSERT INTO investment (user_id, package, package_amount, created_at, deposit_method, proof_of_payment, status) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisss", $userId, $package, $packageAmount, $deposit_method, $proofOfPayment, $status);

    if ($stmt->execute()) {
        // Get the last inserted ID (investment ID)
        $investmentId = $stmt->insert_id;

        // Update the user's subscription date and balance
        $newBalance = $currentBalance + $packageAmount;
        $subscriptionDate = date('Y-m-d');
        $updateSql = "UPDATE users SET subscription_date = ?, balance = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sii", $subscriptionDate, $newBalance, $userId);

        if ($updateStmt->execute()) {
            // Insert into transactions table
            $transactionSql = "INSERT INTO transactions (user_id, insert_id, amount, status, date, transaction_type) 
                               VALUES (?, ?, ?, 'pending', CURDATE(), 'deposit')";
            $transactionStmt = $conn->prepare($transactionSql);
            $transactionStmt->bind_param("iii", $userId, $investmentId, $packageAmount);
            $transactionStmt->execute();
            $transactionStmt->close();

            // Check if user was referred and update referral package
            $referralSql = "SELECT referred_id FROM referrals_table WHERE referred_id = ?";
            $referralStmt = $conn->prepare($referralSql);
            $referralStmt->bind_param("i", $userId);
            $referralStmt->execute();
            $referralResult = $referralStmt->get_result();

            if ($referralResult->num_rows > 0) {
                $referral = $referralResult->fetch_assoc();
                $referredId = $referral['referred_id'];

                $updateReferralSql = "UPDATE referrals_table SET package = ?, package_amount = ? WHERE referred_id = ?";
                $updateReferralStmt = $conn->prepare($updateReferralSql);
                $updateReferralStmt->bind_param("sii", $package, $packageAmount, $referredId);
                $updateReferralStmt->execute();
                $updateReferralStmt->close();
            }
            $referralStmt->close();

            $_SESSION['message'] = "Investment successful!";
            $_SESSION['messageType'] = "success";
            $message = "You have successfully invested in the `$package` investment package. You can log in daily to check your earnings";
            $subject = "Investment Successful";

            // Send notification email
            sendEmail($fullName, $email, $message, $subject);
        } else {
            $_SESSION['message'] = "Investment successful, but error updating subscription date.";
            $_SESSION['messageType'] = "error";
        }
        $updateStmt->close();
    } else {
        $_SESSION['message'] = "Error during investment.";
        $_SESSION['messageType'] = "error";
    }
    $stmt->close();

    header('Location: complete_invest.php');
    exit();
}

$conn->close();

// Retrieve any message for display
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>Invest now</title>

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
    <?php if ($messageType === 'error') : ?>
      // Redirect to fund.php after 2 seconds for balance errors
      setTimeout(function() {
        window.location.href = 'invest.php';
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
            
             
            <li class="menu-item">
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
          <div class="content-wrapper"  id="diva" >
            <!-- Content -->

<div class="container mt-5"style="margin-bottom:20px">
    <div class="card shadow-lg p-4">
        <h3 class="text-center mb-4">Complete Your Investment</h3>
        <form action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" id="package" name="package">
    
    <!-- Investment Amount -->
    <div class="mb-3">
        <label for="investment_amount" class="form-label">Investment Amount:</label>
        <input type="text" class="form-control" id="investment_amount" name="investment_amount" value="<?php echo $amount; ?>" readonly>
    </div>

    <!-- Deposit Method -->
    <div class="mb-3">
        <label for="deposit_method" class="form-label">Deposit Method</label>
        <select class="form-select" id="deposit_method" name="deposit_method" onchange="updateWalletAddress()">
            <option value="Ethereum">Ethereum</option>
            <option value="Bitcoin">Bitcoin</option>
            <option value="USDT">USDT (TRC20)</option>
            <option value="BNB">BNB</option>
            <option value="Dogecoin">DogeCoin</option>
        </select>
    </div>

    <!-- Wallet Address Display -->
    <div class="mb-3">
        <label class="form-label">Wallet Address</label>
        <div class="input-group">
            <input type="text" class="form-control" id="wallet_address" readonly>
            <button type="button" class="btn btn-primary" onclick="copyWallet()">Copy Wallet</button>
        </div>
    </div>

    <!-- Warning Message -->
    <div class="alert alert-warning mt-3" role="alert">
        <b>WARNING:</b> Send the exact deposit amount into the wallet address above.
    </div>

    <!-- Proof of Payment -->
    <div class="mb-3">
        <label for="proof_of_payment" class="form-label">Proof of Payment</label>
        <input type="file" class="form-control" id="proof_of_payment" name="proof_of_payment" required>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary w-100">Submit Deposit</button>
</form>

<!-- JavaScript -->
<script>
    // Wallet Addresses
    var walletAddresses = {
        "Ethereum": "piivgcgdxghgvhgchh",
        "Bitcoin": "jhjkhjkhhghghgj",
        "USDT": "johouhouhugiftyy",
        "BNB": "ggyufydrittot",
        "Dogecoin": "hiughiygyityt"
    };

    // Update Wallet Address Based on Selection
    function updateWalletAddress() {
        var method = document.getElementById("deposit_method").value;
        document.getElementById("wallet_address").value = walletAddresses[method];
    }

    // Copy Wallet Address
    function copyWallet() {
        var walletInput = document.getElementById("wallet_address").value;
        navigator.clipboard.writeText(walletInput).then(() => {
            alert("Wallet Address Copied: " + walletInput);
        }).catch(err => {
            console.error("Error copying: ", err);
        });
    }

    // Assign Package Based on Investment Amount
    function assignPackage() {
        var investAmount = parseInt(document.getElementById("investment_amount").value);
        var packageInput = document.getElementById("package");

        if (investAmount === 100) {
            packageInput.value = 'Basic';
        } else if (investAmount === 5000) {
            packageInput.value = 'Intermediate';
        } else if (investAmount === 15000) {
            packageInput.value = 'Professional';
        } else if (investAmount === 60000) {
            packageInput.value = 'Expert';
        } else {
            packageInput.value = ''; // No package assigned
        }
    }

    // Initialize Defaults on Page Load
    window.onload = function () {
        updateWalletAddress();
        assignPackage();
    };
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


  </div>






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
