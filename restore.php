<?php
$conn = new mysqli("localhost", "root", "", "rrs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['restore_user']) && isset($_POST['user_id'])) {
    $booking_id = intval($_POST['user_id']);
    $sql = "UPDATE table_booking SET is_archived = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            header("Location: archived_reservations.php?restored=success");
            exit();
        } else {
            echo "Error restoring record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();
?>
