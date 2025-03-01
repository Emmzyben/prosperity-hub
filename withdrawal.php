<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php'; 
require 'send_email.php';
// Check if form is submitted
$userId = $_SESSION['userId'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
    $fullName = $userDetails['fullname'];
    $email = $userDetails['email'];
} else {
    echo "No user found.";
    exit(); 
}

$reason = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = "SELECT subscription_date, last_withdrawal_date, available_withdrawal FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Prepare date objects
$today = new DateTime();
$subscriptionDate = new DateTime($user['subscription_date']);
$lastWithdrawalDate = !empty($user['last_withdrawal_date']) ? new DateTime($user['last_withdrawal_date']) : null;
$availableWithdrawal = $user['available_withdrawal'];
// Define one week interval
$oneWeekInterval = new DateInterval('P1W');

// Determine eligibility for withdrawal
$canWithdraw = false;
$reason = "";

// First withdrawal logic
$firstWithdrawalDate = clone $subscriptionDate;
$firstWithdrawalDate->add($oneWeekInterval);

if (!$lastWithdrawalDate) {
    // First withdrawal
   if ($today >= $firstWithdrawalDate) {
        if ($today->diff($firstWithdrawalDate)->days > 7) {
            // User missed first week withdrawal; referral check applies
            $sql = "SELECT package FROM investment WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $userPackage = $user['package'];

            // Check referrals with the same package
            $referralSql = "
                SELECT COUNT(*) AS referral_count 
                FROM referrals_table 
                WHERE referral_id = ? AND package = ?
            ";
            $stmt = $conn->prepare($referralSql);
            $stmt->bind_param("is", $userId, $userPackage);
            $stmt->execute();
            $referralResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($referralResult['referral_count'] > 0) {
                $canWithdraw = true;
            } else {
                $reason = "You need to refer at least one person with the same package as you to make a withdrawal.";
            }
        } else {
            $canWithdraw = true; // Still within the first week
        }
    } else {
        $reason = "You can make your first withdrawal one week after investing.";
    }
} else {
    // Determine the next eligible withdrawal date
    $nextEligibleDate = clone $lastWithdrawalDate;
    $nextEligibleDate->add($oneWeekInterval);
    if ($today >= $nextEligibleDate) {
      if ($lastWithdrawalDate == $firstWithdrawalDate) {
          // Fetch the user's package
          $sql = "SELECT package FROM investment WHERE id = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $userId);
          $stmt->execute();
          $user = $stmt->get_result()->fetch_assoc();
          $stmt->close();
  
          $userPackage = $user['package'];
  
          // Check referrals with the same package
          $referralSql = "
              SELECT COUNT(*) AS referral_count 
              FROM referrals_table 
              WHERE referral_id = ? AND package = ?
          ";
          $stmt = $conn->prepare($referralSql);
          $stmt->bind_param("is", $userId, $userPackage);
          $stmt->execute();
          $referralResult = $stmt->get_result()->fetch_assoc();
          $stmt->close();
  
          if ($referralResult['referral_count'] > 0) {
              $canWithdraw = true;
          } else {
              $reason = "You need to refer at least one person with the same package as you to make a withdrawal.";
          }
      } else {
             // Fetch the user's package
          $sql = "SELECT package FROM investment WHERE id = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $userId);
          $stmt->execute();
          $user = $stmt->get_result()->fetch_assoc();
          $stmt->close();
  
          $userPackage = $user['package'];
  
          // Check referrals with the same package
          $referralSql = "
              SELECT COUNT(*) AS referral_count 
              FROM referrals_table 
              WHERE referral_id = ? AND package = ?
          ";
          $stmt = $conn->prepare($referralSql);
          $stmt->bind_param("is", $userId, $userPackage);
          $stmt->execute();
          $referralResult = $stmt->get_result()->fetch_assoc();
          $stmt->close();
  
          if ($referralResult['referral_count'] > 0) {
              $canWithdraw = true;
          } else {
              $reason = "You need to refer at least one person with the same package as you to make a withdrawal.";
          }
      }
  } else {
      $reason = "You need to wait at least one week since your last withdrawal.";
  }
  
}


  
    if ($canWithdraw) {
      // Sanitize form inputs 
      $accountName = filter_input(INPUT_POST, 'accountName', FILTER_SANITIZE_STRING);
      $bankName = filter_input(INPUT_POST, 'bankName', FILTER_SANITIZE_STRING);
      $accountNumber = filter_input(INPUT_POST, 'accountNumber', FILTER_SANITIZE_STRING);
      $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

      if ($accountName && $bankName && $accountNumber && $amount) {
          if ($amount > $availableWithdrawal) {
              $reason = "You cannot withdraw an amount greater than your available withdrawal of $availableWithdrawal.";
          } else {
              $sql = "INSERT INTO transactions (id, account_name, bank_name, account_number, amount, transactionType, status, date, time) 
                      VALUES (?, ?, ?, ?, ?, 'withdrawal', 'pending', CURDATE(), CURTIME())";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("issss", $userId, $accountName, $bankName, $accountNumber, $amount);

              if ($stmt->execute()) {
                  // Update last withdrawal date
                  $updateSql = "UPDATE users SET last_withdrawal_date = CURRENT_DATE WHERE id = ?";
                  $updateStmt = $conn->prepare($updateSql);
                  $updateStmt->bind_param("i", $userId);
                  $updateStmt->execute();
                  $updateStmt->close();

                  $reason = "Withdrawal request submitted successfully";
                  $message = "Your withdrawal request has been submitted successfully. You will receive your earning upon confirmation";
                  $subject ="Withdrawal request submitted successfully";
                  if (sendEmail($fullName,$email, $message, $subject)) {
                   // echo "Email sent successfully!";
               } else {
                   // echo "Failed to send email.";
               }
              } else {
                  $reason = "Error processing withdrawal. Please try again.";
              }
              $stmt->close();
          }
      } else {
          $reason = "Invalid account details. Please fill out all fields correctly.";
      }
  }
}

$conn->close();
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

    <title>Dashboard - Cashstack</title>

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
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

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
            
            <li class="menu-item active">
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
              <a href="login.php" class="menu-link">
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
                    <div class="avatar avatar-online">
                      <img src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/download.jpeg'; ?>"alt class="w-px-30 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                      
                        <div class="d-flex">
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
              
              <div style="margin-bottom: 20px;" id="diva">
                <div class="card">
                  <div class="card-body" id="diva">
                    <div class="card-title d-flex align-items-start justify-content-between">
                      <div class="avatar flex-shrink-0">
                        <img src="./assets/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
                      
                      </div>
                    
                    </div>
                    <span>Available Withdrawal balance</span>
                    <h3 class="card-title text-nowrap mb-1">₦<span class="counter" data-target="<?php echo htmlspecialchars($userDetails['available_withdrawal']); ?>">0</span></h3>
                </div>
                </div>
              </div>
              <div style="margin-bottom: 20px;" id="diva">
                <div class="card">
                  <div class="card-body" id="diva">
                  
                    <p  style="color:green"><?php echo htmlspecialchars($reason); ?></p>
                </div>
                </div>
              </div>
              <div class="row">
              
                <div class="container-xxl flex-grow-1 container-p-y" id="diva">
                 
                    <div class="row">
                     
                      <!-- Basic with Icons -->
                      <div class="col-xxl" id="diva">
                        <div class="card mb-4">
                          <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Enter account details to withdraw</h5>
                          </div>
                          <div class="card-body">
                          <form id="accountLookupForm" method="post" action="">
                          <div class="row mb-3">
    <label class="col-sm-2 col-form-label" for="bank">Bank Name</label>
    <div class="col-sm-10">
        <div class="input-group input-group-merge">
            <span id="basic-icon-default-company2" class="input-group-text">
                <i class="bx bx-buildings"></i>
            </span>
            <!-- Dropdown for bank selection -->
            <select name="bankName" id="bank" class="form-select" onchange="setBankCode(this)">
                <option value="">Loading banks...</option>
            </select>
            <!-- Hidden input to hold the bank name -->
            <input type="hidden" id="hiddenBankName" name="bankName" />
        </div>
    </div>
</div>


    <div class="row mb-3">
        <label class="col-sm-2 col-form-label" for="accountNumber">Account Number</label>
        <div class="col-sm-10">
            <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="bx bx-buildings"></i></span>
                <input type="text" name="accountNumber" id="accountNumber" class="form-control" required>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <label class="col-sm-2 col-form-label" for="accountName">Account Name</label>
        <div class="col-sm-10">
            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text">
                    <i class="bx bx-user"></i>
                </span>
                <input type="text" name="accountName" id="accountName" class="form-control" required readonly>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label" for="Amount">Amount</label>
        <div class="col-sm-10">
            <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="bx bx-buildings"></i></span>
                <input type="text" name="amount" id="amount" class="form-control" required>
            </div>
        </div>
    </div>
    <div class="row justify-content-end">
        <div class="col-sm-10">
            <button type="submit" class="btn btn-primary">Withdraw</button>
        </div>
    </div>
</form>

<script>
    let bankCode = ''; // To store the selected bank code
    let bankName = ''; // To store the selected bank name

    // Function to handle setting the bank code and bank name when a bank is selected
    function setBankCode(select) {
        bankCode = select.value; // Store the bank code for account lookup
        bankName = select.options[select.selectedIndex].text; // Store the bank name for submission

        // Set the hidden input value for form submission
        document.getElementById('hiddenBankName').value = bankName;
    }


    // Function to fetch and populate the bank dropdown
    async function fetchBanks() {
        const bankSelect = document.getElementById('bank');
        bankSelect.innerHTML = '<option value="">Loading banks...</option>'; // Show loading message

        try {
            const response = await fetch('fetchBanks.php'); // Call PHP script to fetch bank list
            const data = await response.json();

            if (data.error) {
                // Display error if present
                bankSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
            } else if (data.data && Array.isArray(data.data)) {
                // Populate dropdown with bank options
                bankSelect.innerHTML = '<option value="">Select a Bank</option>';
                data.data.forEach(bank => {
                    const option = document.createElement('option');
                    option.value = bank.code; // Bank code used for account lookup
                    option.textContent = bank.name; // Bank name displayed to the user
                    bankSelect.appendChild(option);
                });
            } else {
                // Handle unexpected data structure
                bankSelect.innerHTML = '<option value="">Error: No banks found</option>';
            }
        } catch (error) {
            // Handle network or other errors
            bankSelect.innerHTML = `<option value="">Error: ${error.message}</option>`;
            console.error('Fetch error:', error);
        }
    }

    // Function to handle the account lookup when account number loses focus
    const handleAccountLookup = async () => {
        const accountNumber = document.getElementById('accountNumber').value;

        if (!accountNumber || !bankCode) {
            alert('Please select a bank and enter an account number.');
            return;
        }

        try {
            const response = await fetch('resolveAccount.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    account_number: accountNumber,
                    bank_code: bankCode, // Use the bank code for account lookup
                }),
            });

            const data = await response.json();

            if (data.error) {
                alert(data.error);
                document.getElementById('accountName').value = ''; // Clear on error
            } else if (data.data && data.data.account_name) {
                document.getElementById('accountName').value = data.data.account_name; // Display account name
            } else {
                alert('Failed to resolve account name. Please check your details.');
                document.getElementById('accountName').value = '';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An unexpected error occurred during account lookup.');
            document.getElementById('accountName').value = '';
        }
    };

    // Attach an event listener to trigger lookup when the account number input loses focus
    document.getElementById('accountNumber').addEventListener('blur', handleAccountLookup);

    // Fetch and populate the bank list when the page loads
    document.addEventListener('DOMContentLoaded', fetchBanks);

    // Function to handle form submission
    const handleSubmit = () => {
        const submissionData = {
            accountNumber: document.getElementById('accountNumber').value,
            bankName: bankName, // Use the bank name for form submission
        };

        console.log('Submitting form with data:', submissionData);
        // Perform your submission logic here, e.g., send data to the server
    };

    // Attach an event listener to handle form submission
    document.getElementById('submitButton').addEventListener('click', handleSubmit);
</script>






                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
              </div>
            </div>
            <!-- / Content -->
       
        <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme" id="diva">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column" id="diva">
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
<!--End of Tawk.to Script-->
  </body>
</html>
