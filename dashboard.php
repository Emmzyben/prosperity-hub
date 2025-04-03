<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['userId']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php'; 

$userId = $_SESSION['userId'];

// Fetch user details
function getUserDetails($conn, $userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$userDetails = getUserDetails($conn, $userId);
if (!$userDetails) {
    $_SESSION['message'] = "No user found.";
    $_SESSION['messageType'] = "error";
}

// Fetch transactions
function getUserTransactions($conn, $userId) {
    $sql = "SELECT transaction_type, amount, status, date FROM transactions WHERE user_id = ? ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$transactions = getUserTransactions($conn, $userId);

// Fetch approved investments
function getUserInvestments($conn, $userId) {
    $sql = "SELECT * FROM investment WHERE user_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$investment = getUserInvestments($conn, $userId);

// Fetch pending investments
function getUserPendingInvestments($conn, $userId) {
    $sql = "SELECT * FROM investment WHERE user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$pendingInvestment = getUserPendingInvestments($conn, $userId);

// Fetch pending withdrawals
function getPendingWithdrawals($conn, $userId) {
    $sql = "SELECT * FROM withdrawal WHERE user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$withdrawal = getPendingWithdrawals($conn, $userId);

// Fetch referral details
$sql = "SELECT * FROM referrals_table WHERE referral_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();
$referralDetails = [];
if ($result->num_rows > 0) {
    $referralDetails = $result->fetch_all(MYSQLI_ASSOC);
}


// Get the number of package referrals
$sql = "SELECT package_amount FROM referrals_table WHERE referral_id = ? AND package != '' AND bonus_awarded = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$referrals = $result->fetch_all(MYSQLI_ASSOC);

// Calculate total referral bonus
$totalReferralBonus = 0;
$packageReferralCount = count($referrals); // Count the number of valid referrals

foreach ($referrals as $referral) {
    $package_amount = $referral['package_amount'];
    $totalReferralBonus += 0.10 * $package_amount; // 10% of each package amount
}

// Update user's referral bonus and balance only if there are valid referrals
if ($packageReferralCount > 0) {
  $updateSql = "UPDATE users 
                SET referalBonus = referalBonus + ?, 
                    balance = balance + ?, 
                    referral_count = referral_count + ? 
                WHERE id = ?";
  $stmt = $conn->prepare($updateSql);
  $stmt->bind_param("ddii", $totalReferralBonus, $totalReferralBonus, $packageReferralCount, $userId);
  $stmt->execute();
}


// Mark the referrals as awarded to prevent duplicate updates
$markBonusSql = "UPDATE referrals_table SET bonus_awarded = 1 WHERE referral_id = ? AND package != '' AND bonus_awarded = 0";
$markBonusStmt = $conn->prepare($markBonusSql);
$markBonusStmt->bind_param("i", $userId);
$markBonusStmt->execute();
$markBonusStmt->close();

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

    <title>Dashboard - Prosperity hub global incorporated</title>

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
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

 <script src="./jss/config.js"></script>
    <link rel="stylesheet" href="loader.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="darkmode.css" />
    <style>
      #balance{
  display: flex;flex-direction: row;
}
#balance>div{
  background-color:#0ace86;color:#fff;padding:10px;border-radius:10px;margin-right:20px
}
    </style>
   <style>
 
    @media only screen and (max-width: 900px) {

  #dp{
    display: none;
  }
}
</style>
  </head>

  <body>
 


</div>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

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

            <li class="menu-item active">
              <a href="dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>

        <li class="menu-item">
              <a href="invest.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Invest Now</div>
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
        <div class="layout-page" id="diva">
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
                      <img src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/placeholder.png'; ?>"alt class="w-px-40 h-auto rounded-circle" width="40px"/>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li> 
                      <a class="dropdown-item" href="#">
                      
                        <div class="d-flex"  id="diva">
                          
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
          <div class="content-wrapper" >
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y"  id="diva">
              <div class="row">
                 <div class="col-lg-8 mb-4 order-0">
                  <div class="card"  id="diva">
                    <div style="display:flex;flex-direction: row;justify-content: space-between;padding-bottom: 20px;padding-right: 10px;">
                      <div>
                      <?php if (isset($userDetails)): ?>
                        <div class="card-body">
                          <h5 class="card-title text-primary">Welcome <?php echo htmlspecialchars($userDetails['fullname']); ?> ! 🎉</h5>
                          <!-- <p class="mb-4">
                            You have earned <span class="fw-bold">1.49%</span> more profit today. 
                          </p> -->
                          <div id="balance">
                             <div>
                              <div>
                                 <img
                                src="./assets/icons/unicons/wallet-info.png"
                                alt="Credit Card"
                                class="rounded"
                                width="40px"
                              />
                              </div>
                             <div>
                             <?php if (!empty($investment)): ?>
                              <?php foreach ($investment as $inv):  ?>
                             <h6 class="card-title text-nowrap mb-1" style="color:#fff">Account Balance: $<span><?php echo htmlspecialchars($userDetails['balance']); ?></span></h6>
                             <?php endforeach; ?>
<?php else: ?>
    <p >Account Balance: $0</p>
<?php endif; ?>
                             </div>
                             </div>
                             <div>
                              <div>
                                 <img
                                src="./assets/icons/unicons/wallet-info.png"
                                alt="Credit Card"
                                class="rounded"
                                width="40px"
                              />
                              </div>
                             <div>
                             <h6 class="card-title text-nowrap mb-1" style="color:#fff">Profit: $<span><?php echo htmlspecialchars($userDetails['profit']); ?></span></h6>
    
                             </div>
                             </div>
                          </div>
                        </div>
                        
                      </div>
                      <div id="dp">
                        <div class="card-body pb-0 px-0 px-md-4"  id="diva">
                          <img
                        src="<?php echo !empty($userDetails['profile_picture']) ? htmlspecialchars($userDetails['profile_picture']) : 'assets/placeholder.png'; ?>"

                           id='picture'
                            alt="View Badge User"
                            style="border-radius: 10px;"  id="diva"
                          />
                        </div>
                      </div>
                   
        <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4 col-md-4 order-1"  id="diva">
                  <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-4" >
                      <div class="card" style="height:200px">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                              <img
                                src="./assets/icons/unicons/chart-success.png"
                                alt="chart success"
                                class="rounded"
                              />
                            </div>
                         
                          </div>
                          <?php if (!empty($investment)): ?>
    <span class="fw-semibold d-block mb-1">Current Plan</span>
    <?php foreach ($investment as $inv):  ?>
        <p class="card-title mb-2"><?php echo htmlspecialchars($inv['package']); ?></p>
        <small class="text-success fw-semibold">
            $<span><?php echo htmlspecialchars($inv['package_amount']); ?></span>
        </small>
    <?php endforeach; ?>
<?php else: ?>
    <p >No current investment plan available.</p>
<?php endif; ?>
 </div>
                      </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-4" id="diva" >
                      <div class="card" style="height:200px">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                              <img
                                src="./assets/icons/unicons/cc-primary.png"
                                alt="Credit Card"
                                class="rounded"
                              />
                            </div>
                          
                          </div>
                          <span>Referal Bonus</span>
                          <h3 class="card-title text-nowrap mb-1">$<span ><?php echo htmlspecialchars($userDetails['referalBonus']); ?></span></h3>
                        </div>
                      </div>
                    </div>
                    <!-- lets see -->
                  
                  </div>
                </div>
        


              </div>
            
              <div>

              </div>
                    

              <div class="row d-flex flex-wrap justify-content-center">
  <!-- First Box -->
  <div class="col-lg-6 col-md-4 col-6 mb-4">
    <div class="card" style="height: 200px">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <img src="./assets/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
          </div>
        </div>
        <span>Pending Withdraw</span>
        <?php if (!empty($withdrawal)): ?>
          <?php foreach ($withdrawal as $inv):  ?>
        <h3 class="card-title text-nowrap mb-1">
          $<span ><?php echo htmlspecialchars($inv['amount']); ?></span>
        </h3>
        <?php endforeach; ?>
<?php else: ?>
  <h3 class="card-title text-nowrap mb-1">
          $<span >0</span>
        </h3>
<?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Second Box -->
  <div class="col-lg-6 col-md-4 col-6 mb-4">
    <div class="card" style="height: 200px">
      <div class="card-body">
        <div class="card-title d-flex align-items-start justify-content-between">
          <div class="avatar flex-shrink-0">
            <img src="./assets/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
          </div>
        </div>
        <span>Pending Invest</span>
        <?php if (!empty($pendingInvestment)): ?>
    <?php foreach ($pendingInvestment as $inv2): ?>
      <h3 class="card-title text-nowrap mb-1">$<span><?php echo htmlspecialchars($inv2['package_amount']); ?></span></h3>
    <?php endforeach; ?>
    <?php else: ?>
      <h3 class="card-title text-nowrap mb-1">$ 0</h3>
<?php endif; ?>
      </div>
    </div>
  </div>

 
</div>





              <div style="margin-bottom: 20px;"  id="diva">
                <div class="card">
                  <div class="card-body">
                  <div class="card-title">
    <p>Referral Link</p>
    <form action="">
        <!-- Display referral code in a read-only input field -->
        <input type="text" id="referralCode" value="<?php echo htmlspecialchars($userDetails['referralLink']); ?>" style="width: 100%; border-radius: 5px; border: 1px solid rgb(197, 192, 192); padding: 7px;" readonly>
        
        <!-- Copy Button -->
         <div style="margin:10px">
             <button type="button" onclick="copyReferralCode()" class="btn btn-dark">Copy</button>
        
        <!-- Share Button -->
        <button type="button" onclick="shareReferralCode()" class="btn btn-primary">Share</button>
         </div>
     
    </form>
</div>

<!-- JavaScript for Copy and Share functionality -->
<script>
    // Function to copy the referral code to clipboard
    function copyReferralCode() {
        var referralInput = document.getElementById("referralCode");

        // Check if Clipboard API is available
        if (navigator.clipboard) {
            navigator.clipboard.writeText(referralInput.value).then(function() {
                alert("Referral code copied: " + referralInput.value);
            }).catch(function(err) {
                alert("Failed to copy: " + err);
            });
        } else {
            // Fallback for browsers that don't support Clipboard API
            referralInput.select();
            referralInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand("copy");
            alert("Referral code copied: " + referralInput.value);
        }
    }

    // Function to share the referral code using the Web Share API
    function shareReferralCode() {
        var referralCode = document.getElementById("referralCode").value;
        var shareText = "Join using my referral code: " + referralCode;

        if (navigator.share) {
            navigator.share({
                title: "Referral Code",
                text: shareText,
                url: window.location.href // Share the current page URL
            })
            .then(() => console.log("Successful share"))
            .catch((error) => console.log("Error sharing", error));
        } else {
            // Fallback to copying if sharing is not supported
            copyReferralCode();
            alert("Sharing not supported on this browser. The referral code has been copied.");
        }
    }
</script>
              </div>
            </div>

<br>

            <!-- / Content -->
   <div class="row">
              
              <div class="card" id="diva">
    <h5 class="card-header">Referrals</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Investment Plan</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <?php if (!empty($referralDetails)): ?>
                    <?php foreach ($referralDetails as $referral): ?>
                        <tr>
                            <td><i class="fab fa-user fa-lg text-info me-3"></i> <strong><?php echo htmlspecialchars($referral['referred_fullname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($referral['referred_email']); ?></td>
                            <td><span class="badge bg-label-primary me-1"><?php echo htmlspecialchars($referral['package']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No referrals found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<br>
<br>
<div class="container mt-4">
    <h5 class="text-left mb-4">Transaction History</h5>

    <?php if (!empty($transactions)) : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Transaction Type</th>
                        <th>Amount ($)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction) : ?>
                        <tr>
                            <td><?php echo ucfirst(htmlspecialchars($transaction['transaction_type'])); ?></td>
                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                            <td>
                                <?php
                                if ($transaction['status'] == 'pending') {
                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                } elseif ($transaction['status'] == 'success') {
                                    echo '<span class="badge bg-success">Success</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Failed</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date("d M Y h:i A", strtotime($transaction['date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="alert alert-info text-center" role="alert">
            No transactions found.
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JavaScript (Optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


            </div>
            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme"  id="diva">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0"  id="diva">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  , Prosperity hub global incorporated
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
