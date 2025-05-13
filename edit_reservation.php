<?php
/**
 * Savory Haven Restaurant - Admin Panel - Edit Reservation Page
 * Displays a form to edit an existing reservation.
 * Accessible only by 'admin'.
 */

// Include the configuration file which handles DB connection, session start, and helpers
require_once 'config.php';

// --- Check if the user has 'admin' permission to access this page ---
if (!check_permission('admin')) {
    redirect_with_message('dashboard', 'error', 'You do not have permission to edit reservations.');
    exit;
}

// --- Get Reservation ID from URL ---
$reservation_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Validate Reservation ID
if ($reservation_id === false || $reservation_id === null || $reservation_id <= 0) {
    redirect_with_message('reservations', 'error', 'Invalid reservation ID specified.');
    exit;
}

// --- Fetch Reservation Data from Database ---
$reservation = null;
if ($conn) {
    $sql = "SELECT r.*, t.physical_table_id 
            FROM table_booking r 
            LEFT JOIN table_mapping t ON r.physical_table_id = t.physical_table_id 
            WHERE r.id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $reservation = $result->fetch_assoc();
        } else {
            redirect_with_message('reservations', 'error', 'Reservation not found.');
            exit;
        }
        $stmt->close();
    } else {
        error_log("Database error preparing statement: " . $conn->error);
        redirect_with_message('reservations', 'error', 'Error fetching reservation data.');
        exit;
    }
} else {
    error_log("Database connection not available in edit_reservation.php");
    redirect_with_message('reservations', 'error', 'Database connection error.');
    exit;
}

// --- Get Available Tables for Dropdown ---
$tables = [];
$tables_sql = "SELECT id, physical_table_id, capacity, location FROM table_mapping WHERE availability != 'unavailable' ORDER BY physical_table_id";
$tables_result = $conn->query($tables_sql);
if ($tables_result) {
    while ($row = $tables_result->fetch_assoc()) {
        $tables[] = $row;
    }
}

// --- Get Available Status Options ---
$available_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservation #<?php echo htmlspecialchars($reservation['id']); ?> - Savory Haven Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .edit-reservation-form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 30px;
            text-align: center;
        }
        .reservation-info {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 15px;
            margin-bottom: 20px;
        }
        .reservation-info h4 {
            color: #9c3d26;
            margin-bottom: 10px;
        }
        .reservation-info p {
            margin-bottom: 5px;
        }
        .reservation-info .badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        .reservation-number {
            background-color: #9c3d26;
            color: white;
            padding: 10px 20px;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .editing-indicator {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 0.5rem;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="edit-reservation-form-container">
        <div class="text-center">
            <div class="reservation-number">
                <i class="fas fa-calendar-check me-2"></i>
                Reservation #<?php echo htmlspecialchars($reservation['id']); ?>
            </div>
            <div class="editing-indicator">
                <i class="fas fa-edit me-2"></i>
                Currently Editing Reservation #<?php echo htmlspecialchars($reservation['id']); ?>
            </div>
        </div>

        <div class="reservation-info">
            <h4><i class="fas fa-info-circle me-2"></i>Reservation Information</h4>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($reservation['name']); ?></p>
            <p><strong>Email Address:</strong> <?php echo htmlspecialchars($reservation['email']); ?></p>
            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($reservation['phone']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['date']); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['time']); ?></p>
            <p><strong>Number of Guests:</strong> <?php echo htmlspecialchars($reservation['people_count']); ?> persons</p>
            <p><strong>Current Status:</strong> 
                <span class="badge bg-<?php 
                    echo $reservation['status'] === 'confirmed' ? 'success' : 
                        ($reservation['status'] === 'pending' ? 'warning' : 
                        ($reservation['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                ?>">
                    <?php echo htmlspecialchars(ucfirst($reservation['status'])); ?>
                </span>
            </p>
        </div>

        <?php
        // --- Display Feedback Message if set ---
        $feedback_message = '';
        $feedback_status = '';
        if (isset($_GET['status']) && isset($_GET['message'])) {
            $feedback_status = htmlspecialchars($_GET['status']);
            $feedback_message = htmlspecialchars($_GET['message']);
            echo '<script>
                window.addEventListener("load", function() {
                    const url = new URL(window.location.href);
                    if (url.searchParams.has("status") || url.searchParams.has("message")) {
                        url.searchParams.delete("status");
                        url.searchParams.delete("message");
                        if (window.history.replaceState) {
                            window.history.replaceState({}, document.title, url.toString());
                        }
                    }
                });
            </script>';
        }

        if (!empty($feedback_message)): ?>
            <div class="alert alert-<?php echo ($feedback_status === 'success' ? 'success' : 'danger'); ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo ($feedback_status === 'success' ? 'check-circle' : 'exclamation-triangle'); ?> me-2"></i>
                <?php echo $feedback_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="admin.php" method="post" id="editReservationForm">
            <input type="hidden" name="action" value="edit_reservation">
            <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
            <input type="hidden" name="redirect_to" value="reservations">

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($reservation['name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($reservation['email']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($reservation['phone']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($reservation['date']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="time" name="time" 
                               value="<?php echo htmlspecialchars($reservation['time']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="people_count" class="form-label">Number of Guests</label>
                        <input type="number" class="form-control" id="people_count" name="people_count" 
                               value="<?php echo htmlspecialchars($reservation['people_count']); ?>" required min="1">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="special_requests" class="form-label">Special Requests (Optional)</label>
                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?php echo htmlspecialchars($reservation['special_requests_text'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach ($available_statuses as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" 
                                <?php echo ($reservation['status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Changes
            </button>

            <a href="admin.php?page=reservations" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Cancel
            </a>
        </form>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editReservationForm');
            
            form.addEventListener('submit', function(event) {
                const peopleCount = document.getElementById('people_count').value;
                const selectedTable = document.getElementById('table_id');
                const tableCapacity = selectedTable.options[selectedTable.selectedIndex].text.match(/Capacity: (\d+)/)[1];
                
                if (peopleCount > tableCapacity) {
                    event.preventDefault();
                    alert('Party size cannot exceed table capacity!');
                }
            });
        });
    </script>
</body>
</html>