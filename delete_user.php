
<?php
ob_start();
require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    $stmt = $conn->prepare("DELETE FROM adminpanel_users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            
            header("Location: admin.php?page=users&status=deleted");
            exit;
        } else {
            
            echo "Error deleting user: " . $stmt->error;
        }
    } else {
        
        echo "Failed to prepare statement: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
