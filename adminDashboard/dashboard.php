<?php
session_start();

// Ensure userId is set; redirect if missing
if (!isset($_SESSION['userId']) || !isset($_SESSION['username'])) {
    header('Location: ../admin.php');
    exit();
}

include './database/dbconfig.php';

$userId = $_SESSION['userId'];

$query = "SELECT investment.*, users.fullname
          FROM investment
          INNER JOIN users ON investment.user_id = users.id
          WHERE investment.status = 'pending'
          ORDER BY investment.created_at DESC";

$result = $conn->query($query);

$query = "SELECT withdrawal.*, users.fullname
          FROM withdrawal
          INNER JOIN users ON withdrawal.user_id = users.id
          WHERE withdrawal.status = 'pending'
          ORDER BY withdrawal.date DESC";

$withdrawalResult = $conn->query($query);



// Query to get all users
$queryUsers = "SELECT id FROM users";
$resultUsers = $conn->query($queryUsers);

if ($resultUsers->num_rows > 0) {
    // Array to store package subscription counts
    $packageCounts = [];

    while ($user = $resultUsers->fetch_assoc()) {
        $userId = $user['id'];

        // Query to get the package for the user from the investment table
        $queryPackage = "SELECT package FROM investment WHERE user_id = ?";
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
          <a href="" class="app-brand-link">
            <span class="app-brand-logo demo">
                   <img src="../assets/logo.png" alt="" style="width:80px">
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
                    <th>Basic (#100)</th>
                    <th>Intermidiate (#5000)</th>
                    <th>Professional Plan (#15000)</th>
                    <th>Expert plan(#60000)</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                <tr>
                    <?php
                    // Initialize package counts
                    $packageNames = ['Basic', 'Intermidiate', 'Professional', 'Expert'];
                    $packageCounts = array_fill_keys($packageNames, 0);

                    // Fetch all users and their packages
                    $queryUsers = "SELECT id FROM users";
                    $resultUsers = $conn->query($queryUsers);

                    if ($resultUsers->num_rows > 0) {
                        while ($user = $resultUsers->fetch_assoc()) {
                            $userId = $user['id'];
                            $queryPackage = "SELECT package FROM investment WHERE user_id = ?";
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
    <h5 class="card-header">Pending Investments</h5>
    <div class="table-responsive text-nowrap">
    <table class="table table-hover">
    <thead>
        <tr>
            <th>Investor Name</th>
            <th>Package</th>
            <th>Package Amount</th>
            <th>Deposit Method</th>
            <th>Proof of Payment</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                    <td><strong><?php echo htmlspecialchars($row['package']); ?></strong></td>
                    <td>₦<?php echo number_format($row['package_amount'], 2); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['deposit_method']); ?></strong></td>
                    <td>
                        <?php if (!empty($row['proof_of_payment'])): ?>
                            <a href="../<?php echo htmlspecialchars($row['proof_of_payment']); ?>" 
                               download 
                               class="btn btn-sm btn-primary">
                                Download Proof
                            </a>
                        <?php else: ?>
                            <span class="text-muted">No proof uploaded</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></strong></td>
                    <td><span class="badge bg-label-warning me-1"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);" 
                                   onclick="confirmAction('approve', '<?php echo $row['investmentId']; ?>')">
                                    <i class="bx bx-edit-alt me-1"></i> Approve
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" 
                                   onclick="confirmAction('decline', '<?php echo $row['investmentId']; ?>')">
                                    <i class="bx bx-trash me-1"></i> Decline
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No pending investment transactions found.</td>
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



          <div class="container mt-5">
    <div class="card">
    <h5 class="card-header">Pending Withdrawals</h5>
    <div class="table-responsive text-nowrap">
    <table class="table table-hover">
    <thead>
        <tr>
            <th>Investor Name</th>
            <th>Withdrawal Method</th>
            <th>Amount</th>
            <th>Wallet Address</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
    <?php if ($withdrawalResult && $withdrawalResult->num_rows > 0): ?>
        <?php while ($row = $withdrawalResult->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                <td><strong><?php echo htmlspecialchars($row['withdrawal_method']); ?></strong></td>
                <td>₦<?php echo number_format($row['amount'], 2); ?></td>
                <td><strong><?php echo htmlspecialchars($row['wallet_address']); ?></strong></td>
                <td><strong><?php echo date('d M Y, h:i A', strtotime($row['date'])); ?></strong></td>
                <td><span class="badge bg-label-warning me-1"><?php echo ucfirst($row['status']); ?></span></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="javascript:void(0);" 
                               onclick="confirmAction2('approve', '<?php echo $row['id']; ?>')">
                                <i class="bx bx-edit-alt me-1"></i> Approve
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" 
                               onclick="confirmAction2('decline', '<?php echo $row['id']; ?>')">
                                <i class="bx bx-trash me-1"></i> Decline
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No pending withdrawal transactions found.</td>
        </tr>
    <?php endif; ?>
</tbody>

</table>

    </div>
</div>

<!-- Modal -->
<!-- Withdrawal Modal -->
<div class="modal fade" id="verificationModalWithdrawal" tabindex="-1" aria-labelledby="verificationModalWithdrawalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalWithdrawalLabel">Confirm Withdrawal Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to <span id="withdrawalActionType"></span> this withdrawal?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="withdrawalTransactionForm" method="POST" action="">
                    <input type="hidden" name="transactionId" id="withdrawalTransactionId">
                    <input type="hidden" name="actionType" id="withdrawalActionTypeInput">
                    <button type="submit" class="btn btn-primary">Proceed</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmAction2(actionType, transactionId) {
        document.getElementById('withdrawalActionType').textContent = actionType;
        document.getElementById('withdrawalTransactionId').value = transactionId;
        document.getElementById('withdrawalActionTypeInput').value = actionType;

        new bootstrap.Modal(document.getElementById('verificationModalWithdrawal')).show();
    }
</script>


<?php
// Include your PHP function here
include 'approveWithdrawal.php';  

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = isset($_POST['transactionId']) ? $_POST['transactionId'] : null;
    $actionType = isset($_POST['actionType']) ? $_POST['actionType'] : null;

    if ($transactionId && $actionType) {
        handleWithdrawal($transactionId, $actionType);
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
