<?php


include 'db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

   
    $sql = "UPDATE table_booking SET is_archived = 1 WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        
        header("Location:/restaurantReservationSystem/archived_reservations.php?archived=success");
        exit();
    } else {
        echo "Error archiving record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
