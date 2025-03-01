<?php
session_start();

// Ensure userId is set; redirect if missing
if (!isset($_SESSION['userId']) || !isset($_SESSION['username'])) {
    header('Location: ../admin.php');
    exit();
}

include './database/dbconfig.php';

$userId = $_SESSION['userId'];

// Modify the query to order by combined date and time in descending order
$query = "SELECT * FROM transactions 
          WHERE transactionType = 'withdrawal' 
          AND (status = 'pending' OR status = 'declined' OR status = 'NEW' OR status = 'FAILED') 
          ORDER BY CONCAT(date, ' ', time) DESC";  // Combine date and time for sorting
$result = $conn->query($query);

// Query to get all users
$queryUsers = "SELECT id FROM users";
$resultUsers = $conn->query($queryUsers);

if ($resultUsers->num_rows > 0) {
    // Array to store package subscription counts
    $packageCounts = [];

    while ($user = $resultUsers->fetch_assoc()) {
        $userId = $user['id'];

        // Query to get the package for the user from the investment table
        $queryPackage = "SELECT package FROM investment WHERE id = ?";
        $stmt = $conn->prepare($queryPackage);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $resultPackage = $stmt->get_result();

        if ($resultPackage->num_rows > 0) {
            while ($row = $resultPackage->fetch_assoc()) {
                $package = $row['package'];

                // Increment package count
                if (!isset($packageCounts[$package])) {
                    $packageCounts[$package] = 0;
                }
                $packageCounts[$package]++;
            }
        }
        $stmt->close();
    }
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
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Admin - Cashstack</title>

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
   
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="index.html" class="app-brand-link">
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
              <span class="app-brand-text demo menu-text fw-bolder ms-2">CashStack</span>
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
              <a href="fundUser.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Fund User</div>
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

            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
      
           
              <div class="row">
                <!-- Bootstrap Dark Table -->
                <div class="card">
    <h5 class="card-header">User Details</h5>
    <p>Total number of users for each package</p>
    <div class="table-responsive text-nowrap">
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Basic (#3000)</th>
                    <th>Bronze (#6000)</th>
                    <th>Silver (#9000)</th>
                    <th>Gold (#12000)</th>
                    <th>Diamond (#15000)</th>
                    <th>Platinum (#18000)</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <tr>
                    <?php
                    // Initialize package counts
                    $packageNames = ['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'];
                    $packageCounts = array_fill_keys($packageNames, 0);

                    // Fetch all users and their packages
                    $queryUsers = "SELECT id FROM users";
                    $resultUsers = $conn->query($queryUsers);

                    if ($resultUsers->num_rows > 0) {
                        while ($user = $resultUsers->fetch_assoc()) {
                            $userId = $user['id'];
                            $queryPackage = "SELECT package FROM investment WHERE id = ?";
                            $stmt = $conn->prepare($queryPackage);
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $resultPackage = $stmt->get_result();

                            if ($resultPackage->num_rows > 0) {
                                while ($row = $resultPackage->fetch_assoc()) {
                                    $package = $row['package'];
                                    if (isset($packageCounts[$package])) {
                                        $packageCounts[$package]++;
                                    }
                                }
                            }
                            $stmt->close();
                        }
                    }

                    // Display package counts
                    foreach ($packageNames as $package) {
                        $count = $packageCounts[$package] ?? 0;
                        echo "<td><strong>$count</strong></td>";
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

              <!--/ Bootstrap Dark Table -->

           
            </div>
          
            
            <br>
            <div class="row">

    <!-- Hoverable Table rows -->
    <div class="container mt-5">
    <div class="card">
    <h5 class="card-header">Pending Withdrawals</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Account Number</th>
                    <th>Bank</th>
                    <th>Amount</th>
                    <th>Transaction Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $row['account_name']; ?></strong></td>
                            <td><strong><?php echo $row['account_number']; ?></strong></td>
                            <td><strong><?php echo $row['bank_name']; ?></strong></td>
                            <td>₦<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo ucfirst($row['transactionType']); ?></td>
                            <td><span class="badge bg-label-warning me-1"><?php echo ucfirst($row['status']); ?></span></td>
                            <td>
                                <div class="dropdown">
                                    <button
                                        type="button"
                                        class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"
                                    >
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a
                                            class="dropdown-item"
                                            href="javascript:void(0);"
                                            onclick="confirmAction('approve', '<?php echo $row['transactionId']; ?>')"
                                        >
                                            <i class="bx bx-edit-alt me-1"></i> Approve
                                        </a>
                                        <a
                                            class="dropdown-item"
                                            href="javascript:void(0);"
                                            onclick="confirmAction('decline', '<?php echo $row['transactionId']; ?>')"
                                        >
                                            <i class="bx bx-trash me-1"></i> Decline
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No pending withdrawal transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to <span id="actionType"></span> this transaction?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="transactionForm" method="POST" action="">
                    <input type="hidden" name="transactionId" id="transactionId">
                    <input type="hidden" name="actionType" id="actionTypeInput">
                    <button type="submit" class="btn btn-primary">Proceed</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Store transaction details to be passed to handleTransaction function
    function confirmAction(actionType, transactionId) {
        // Update modal with action type and transactionId
        document.getElementById('actionType').textContent = actionType;
        document.getElementById('transactionId').value = transactionId;
        document.getElementById('actionTypeInput').value = actionType;

        // Show the modal
        new bootstrap.Modal(document.getElementById('verificationModal')).show();
    }
</script>

<?php
// Include your PHP function here
include 'approve.php';  

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = isset($_POST['transactionId']) ? $_POST['transactionId'] : null;
    $actionType = isset($_POST['actionType']) ? $_POST['actionType'] : null;

    if ($transactionId && $actionType) {
        // Call the handleTransaction function with the parameters
        handleTransaction($transactionId, $actionType);
    }
}

?>




</div>



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
  </body>
</html>
