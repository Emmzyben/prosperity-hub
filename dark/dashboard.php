<?php
session_start();

// Ensure userId is set; redirect if missing
if (!isset($_SESSION['userId']) || !isset($_SESSION['username'])) {
    header('Location: ..index.php');
    exit();
}

include '../database/dbconfig.php'; 

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
$showKYCModal = empty($userDetails['idType']) || empty($userDetails['idNumber']);

if (!$userDetails) {
    $_SESSION['message'] = "No user found.";
    $_SESSION['messageType'] = "error";
}

// Fetch transactions
function getUserTransactions($conn, $userId) {
    $sql = "SELECT * FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$transactions = getUserTransactions($conn, $userId);

// Fetch investments
function getUserInvestments($conn, $userId) {
    $sql = "SELECT * FROM investment WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId); 
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$investment = getUserInvestments($conn, $userId);

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

 <script src="./jss/config.js"></script>
    <link rel="stylesheet" href="loader.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="darkmode.css" />
    <style>
      #balance{
  display: flex;flex-direction: row;
}

#balance>div{
  background-color:#696cff;color:#fff;padding:10px;border-radius:10px;margin-right:20px
}
    </style>
   <style>
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 100000; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0, 0, 0); /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        padding-top: 60px; /* Location of the box */
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
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

    /* Disable pointer events on the body when the modal is shown */
    body.modal-open {
        pointer-events: none; /* Prevent interaction with the page */
    }
</style>
  </head>

  <body>
  <div class="loader">
    <div class="cube">
        <div class="face front"></div>
        <div class="face back"></div>
        <div class="face left"></div>
        <div class="face right"></div>
        <div class="face top"></div>
        <div class="face bottom"></div>
    </div>
</div>

<!-- KYC Modal -->
<div id="kycModal" class="modal" style="display: none;">
    <div class="modal-content">
        <!--   -->
        <h2>Complete Your KYC</h2>
        <p>Please complete your KYC (Know Your Customer) requirements to continue using the platform.</p>
        <a href="pages-account-settings-account.php" class="btn btn-primary">Go to KYC</a>
    </div>
</div>

<script>
    window.addEventListener("load", function() {
        const loader = document.querySelector('.loader');
        loader.style.display = 'none'; 

        const showKYCModal = <?= json_encode(isset($showKYCModal) && $showKYCModal); ?>;
        if (showKYCModal) {
            document.getElementById('kycModal').style.display = 'block';
            document.body.classList.add('modal-open'); 
        }
    });3

    function closeModal() {
        document.getElementById('kycModal').style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    document.querySelector('.close').onclick = closeModal;

    window.onclick = function(event) {
        var modal = document.getElementById('kycModal');
        if (event.target !== modal && !modal.contains(event.target)) {
            return; 
        }
    }
</script>

</div>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

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
              <span class="app-brand-text demo menu-text fw-bolder ms-2"  id="diva">CashStack</span>
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
                      <img  src="../<?php echo htmlspecialchars($userDetails['profile_picture']); ?>" alt class="w-px-40 h-auto rounded-circle" width="40px"/>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                      
                        <div class="d-flex"  id="diva">
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
                      <a class="dropdown-item" href="../dashboard.php">
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
                          <p class="mb-4">
                            You have earned <span class="fw-bold">1.49%</span> more profit today. 
                          </p>
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
                             <h6 class="card-title text-nowrap mb-1" style="color:#fff">Fund Balance: ₦<span class="counter" data-target="<?php echo htmlspecialchars($userDetails['balance']); ?>"><?php echo htmlspecialchars($userDetails['balance']); ?></span></h6>
    
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
                             <h6 class="card-title text-nowrap mb-1" style="color:#fff">Profit: ₦<span class="counter" data-target="<?php echo htmlspecialchars($userDetails['profit']); ?>"><?php echo htmlspecialchars($userDetails['profit']); ?></span></h6>
    
                             </div>
                             </div>
                          </div>
                        </div>
                        
                      </div>
                      <div>
                        <div class="card-body pb-0 px-0 px-md-4"  id="diva">
                          <img
                         src="../<?php echo htmlspecialchars($userDetails['profile_picture']); ?>" 

                            id="picture"
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
            ₦<span class="counter" data-target="<?php echo htmlspecialchars($inv['package_amount']); ?>">0</span>
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
                          <span>Available Withdrawal</span>
                          <h3 class="card-title text-nowrap mb-1">₦<span class="counter" data-target="<?php echo htmlspecialchars($userDetails['available_withdrawal']); ?>"><?php echo htmlspecialchars($userDetails['available_withdrawal']); ?></span></h3>
                        </div>
                      </div>
                    </div>
                    <!-- lets see -->
                  
                  </div>
                </div>
        


              </div>
             
              <div class="row">
              
            <!-- Transactions -->
            <div id="diva">
  <div class="card h-100">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title m-0 me-2">Transactions</h5>
    </div>
    <div class="card-body">
      <ul class="p-0 m-0">
        <?php if (!empty($transactions)): ?>
          <?php foreach ($transactions as $transaction): ?>
            <li class="d-flex mb-4 pb-1">
              <div class="avatar flex-shrink-0 me-3">
                <?php if ($transaction['transactionType'] == 'Withdrawal'): ?>
                  <img src="./assets/icons/unicons/cc-success.png" alt="User" class="rounded" />
                <?php else: ?>
                  <img src="./assets/icons/unicons/cc-warning.png" alt="User" class="rounded" />
                <?php endif; ?>
              </div>
              <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                <div class="me-2">
                  <p class="mb-0"><?= htmlspecialchars($transaction['transactionType'], ENT_QUOTES); ?></p>
                  <?php
// Assuming $transaction['time'] and $transaction['date'] are in correct formats
$time = DateTime::createFromFormat('H:i:s', $transaction['time'])->format('h:i A'); // 12-hour format with AM/PM
$date = DateTime::createFromFormat('Y-m-d', $transaction['date'])->format('d/m/Y'); // dd/mm/yyyy format
?>
<small class="text-muted d-block mb-1">
    <?= htmlspecialchars($time, ENT_QUOTES); ?> . <?= htmlspecialchars($date, ENT_QUOTES); ?>
</small>

                  <small class="text-muted d-block mb-1">
                    Status: <?= htmlspecialchars($transaction['status'], ENT_QUOTES); ?>
                  </small>
                  <small class="text-muted d-block mb-1">
                    Transaction ID: <?= htmlspecialchars($transaction['transactionId'], ENT_QUOTES); ?>
                  </small>
                </div>
                <div class="user-progress d-flex align-items-center gap-1">
                  <h6 class="mb-0">
                    <?php if ($transaction['transactionType'] == 'Withdrawal'): ?>
                      -<?= htmlspecialchars($transaction['amount'], ENT_QUOTES); ?>
                    <?php else: ?>
                      +<?= htmlspecialchars($transaction['amount'], ENT_QUOTES); ?>
                    <?php endif; ?>
                  </h6>
                  <span class="text-muted">NGN</span>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="d-flex mb-4 pb-1">
            <div class="w-100 text-center">
              <p>No transactions available.</p>
            </div>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<!--/ Transactions -->

          
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
