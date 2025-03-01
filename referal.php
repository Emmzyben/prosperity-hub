<?php
session_start();

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

include './database/dbconfig.php';

$userId = $_SESSION['userId'];

// Fetch user details from the users table
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userDetails = $result->fetch_assoc();
} else {
    exit("No user found.");
}

// Fetch referral details from the referrals_table using referral_id
$sql = "SELECT * FROM referrals_table WHERE referral_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

$referralDetails = [];
if ($result->num_rows > 0) {
    $referralDetails = $result->fetch_all(MYSQLI_ASSOC);
}

// Count package referrals that haven't awarded bonuses
$sql = "SELECT COUNT(*) as package_referrals FROM referrals_table WHERE referral_id = ? AND package != '' AND bonus_awarded = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($packageReferralCount);
$stmt->fetch();
$stmt->close();

// Calculate the referral bonus as 500 for each referral with a package
$referralBonus = $packageReferralCount * 500;

// Update only the referral bonus and referral count
$updateSql = "UPDATE users SET referalBonus = referalBonus + ?, referral_count = referral_count + ? WHERE id = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("iii", $referralBonus, $packageReferralCount, $userId);
if ($stmt->execute()) {
    // Successfully updated referral bonus and count
} else {
    exit("Error updating user records: " . $stmt->error);
}

// Mark bonuses as awarded to prevent multiple increments
$markBonusSql = "UPDATE referrals_table SET bonus_awarded = 1 WHERE referral_id = ? AND package != '' AND bonus_awarded = 0";
$markBonusStmt = $conn->prepare($markBonusSql);
$markBonusStmt->bind_param("i", $userId);
$markBonusStmt->execute();
$markBonusStmt->close();

// Count the number of referrals for the user (this seems redundant, you might want to omit this if not needed)
$sql = "SELECT COUNT(*) as referral_count FROM referrals_table WHERE referral_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($referralCount);
$stmt->fetch();
$stmt->close();

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

    <title>Referral - Cashstack</title>

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
 

      <script src="./jss/config.js"></script>
    <link rel="stylesheet" href="loader.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="darkmode.css" />
  </head>

  <body>
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
            <li class="menu-item active">
              <a href="referal.php" class="menu-link">
                  <i class="bx bx-user me-2"></i>
                <div data-i18n="Layouts">referral</div>
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
              <div class="row">
                <div class="col-lg-8 mb-4 order-0">
                    <div class="card"  id="diva">
                      <div class="d-flex align-items-end row">
                        <div class="col-sm-7">
                        <?php if (isset($userDetails)): ?>
                          <div class="card-body"  id="diva">
                            <h5 class="card-title text-primary">Welcome <?php echo htmlspecialchars($userDetails['fullname']); ?> ! 🎉</h5>
                            <p class="mb-4">
                              Keep referring to earn more
                            </p>
  
                          </div>
                          <?php endif; ?>
                        </div>
                        
                      </div>
                    </div>
                  </div>
                <div class="col-lg-4 col-md-4 order-1"  id="diva">
                  <div class="row">
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                      <div class="card">
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
                          <span class="fw-semibold d-block mb-1">Total Referrals</span>
             
                          <h3 class="card-title text-nowrap mb-1"><?php echo $referralCount; ?></h3>
                        </div>
                      </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                      <div class="card">
                        <div class="card-body">
                          <div class="card-title d-flex align-items-start justify-content-between">
                            <div class="avatar flex-shrink-0">
                              <img
                                src="./assets/icons/unicons/wallet-info.png"
                                alt="Credit Card"
                                class="rounded"
                              />
                            </div>
                          
                          </div>
                          <span>Referral Bonus</span>
                          <h3 class="card-title text-nowrap mb-1">₦<span class="counter" data-target="<?php echo htmlspecialchars($userDetails['referalBonus']); ?>">0</span></h3>
                      </div>
                      </div>
                    </div>
                    <!-- lets see -->
                  
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
        <input type="text" id="referralCode" value="<?php echo htmlspecialchars($userDetails['referralLink']); ?>" style="width: 200px; border-radius: 5px; border: 1px solid rgb(197, 192, 192); padding: 7px;" readonly>
        
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
              </div>
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
