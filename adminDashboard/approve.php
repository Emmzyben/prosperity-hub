<?php
function handleTransaction($transactionId, $actionType)
{
    include './database/dbconfig.php';

    if ($actionType === 'approve') {
                // Proceed with the transaction
                $transferStatus = "approved";
                $transactionStatus ="success";
                $sql = "UPDATE investment SET status = ? WHERE investmentId = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $transferStatus, $transactionId);
                $stmt->execute();
                // Update transaction status
                $sql = "UPDATE transactions SET status = ? WHERE insert_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $transactionStatus, $transactionId);
                $stmt->execute();

               

                echo "<script>alert('Transaction approved!');</script>";
            
    
    } elseif ($actionType === 'decline') {
        // Decline transaction
        $sql = "UPDATE transactions SET status = 'pending' WHERE insert_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();

        echo "<script>alert('Transaction declined successfully.');</script>";
    } else {
        echo "<script>alert('Invalid action type.');</script>";
    }

    // Close database connection
    $conn->close();
}
?>
