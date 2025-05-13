<?php
/**
 * Savory Haven Restaurant - Admin Panel - Reservations Management Page
 * Displays and handles CRUD operations for the table_booking table.
 */

// Include the configuration file which handles DB connection and session start
// We assume admin.php has already checked authentication before including this file.

// $conn is available from config.php
// is_logged_in() is available from config.php
// get_current_user_role() is available from config.php
// check_permission() is available from config.php

// --- RESERVATION MANAGEMENT ACTIONS (Handle POST requests) ---
// Actions are handled here since this file is included within the authenticated section of admin.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

    // Only process if the action is one of the reservation actions
    if (in_array($action, ['confirm', 'cancel', 'delete']) && isset($_POST['reservation_id'])) {
        $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_SANITIZE_NUMBER_INT);

        if ($reservation_id !== false && $reservation_id !== null) {
            $allowed = false;
            $sql = "";
            $stmt = null;

            if ($action === 'confirm' || $action === 'cancel') {
                if (check_permission('staff')) { // Staff or higher can confirm/cancel
                    $new_status = ($action === 'confirm') ? 'confirmed' : 'cancelled';
                    $sql = "UPDATE table_booking SET status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("si", $new_status, $reservation_id);
                        $allowed = true;
                    }
                } else {
                    redirect_with_message('reservations', 'error', 'Insufficient permissions to ' . $action . ' reservation.');
                    exit;
                }
            } elseif ($action === 'delete') {
                if (check_permission('admin')) { 
                    $sql = "DELETE FROM table_booking WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("i", $reservation_id);
                        $allowed = true;
                    }
                } else {
                    redirect_with_message('reservations', 'error', 'Insufficient permissions to delete reservation.');
                    exit;
                }
            }

            if ($allowed && $stmt) {
                if ($stmt->execute()) {
                    redirect_with_message('reservations', 'success', 'Reservation ' . $action . 'ed successfully!');
                } else {
                    redirect_with_message('reservations', 'error', 'Error ' . $action . 'ing reservation: ' . $stmt->error);
                }
                $stmt->close();
            } elseif (!$allowed && !$stmt && !headers_sent()) {
                
            } elseif (!$stmt) {
                redirect_with_message('reservations', 'error', 'Error preparing statement for ' . $action . ' action.');
            }

        } else {
            redirect_with_message('reservations', 'error', 'Invalid reservation ID.');
        }
    }
 
}


$reservations = [];
// Assuming $conn is open and authenticated check has passed
if ($conn) {
    // Your existing query to fetch reservations
    $sql = "SELECT id, table_type, name, email, phone, date, people_count, table_location, table_preference, special_requests_text, time, status, created_at 
    FROM table_booking 
    WHERE is_archived = 0 
    ORDER BY created_at DESC";

    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }
        $result->free();
    } else {
        echo "<div class='alert alert-danger'>Error fetching reservations: " . $conn->error . "</div>";
    }
}


// Database connection
$conn = new mysqli("localhost", "root", "", "rrs"); // adjust kung iba ang db config mo

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check kung na-submit ang archive button
if (isset($_POST['archive_user'])) {
    $user_id = intval($_POST['user_id']);

    // Update query para gawing archived
    $sql = "UPDATE table_booking SET is_archived = 1 WHERE id = $user_id";

    if ($conn->query($sql) === TRUE) {
        // Optional: success message or redirect
        header("Location: bookings.php?archived=success");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
if (isset($_GET['archived']) && $_GET['archived'] == 'success') {
    echo "<div class='alert alert-success'>Booking archived successfully!</div>";
    $sql = "SELECT * FROM table_booking WHERE is_archived = 0 ORDER BY date DESC";

}


$sql = "SELECT * FROM table_booking WHERE is_archived = 0 ORDER BY date DESC";



// --- HTML for Reservations Management Page ---
// This HTML will be included directly into the main-content div of admin.php
?>

<div class="row justify-content-between align-items-center mb-4">
    <div class="col-md-6">
        <h1 class="admin-title">Reservations</h1>
        <p class="text-muted">Manage upcoming and past reservations</p>
    </div>
    <div class="col-md-6 text-md-end">
        </div>
</div>

<div class="card mb-4">
    <div class="card-header admin-header">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt me-2"></i> Reservation List
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search reservations...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select id="filterSelect" class="form-select">
                    <option value="all">All reservations</option>
                    <option value="today">Today</option>
                    <option value="week">This week</option>
                    <option value="month">This month</option>
                     <option value="pending">Pending</option>
                     <option value="confirmed">Confirmed</option>
                     <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="d-flex justify-content-end mt-3 gap-2">
    <form method="POST" style="width: 100%;">
        <button type="submit" class="btn btn-primary btn-lg w-100" style="height: 55px;">
            <i class="fas fa-plus-circle me-1"></i> Add Table ito muna 
        </button>
    </form>
    

    <form action="/restaurantReservationSystem/archive.php" method="POST" style="width: 220px;">
    <input type="hidden" name="user_id" value="<?php echo $reservation['id']; ?>">
    <button type="submit" name="archive_user" class="btn btn-danger btn-lg w-100" style="height: 55px;">
        🗃️ Archive
    </button>
</form>
</div>


        <?php if (empty($reservations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No reservations found in the database.
            </div>
        <?php else: ?>
            <div class="reservation-list">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="card reservation-card" data-reservation-id="<?php echo $reservation['id']; ?>">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars($reservation['name']); ?></strong> -
                                        <?php echo htmlspecialchars($reservation['date']); ?> <?php echo htmlspecialchars($reservation['time']); ?>
                                    </p>
                                    <p class="mb-1 text-muted"><small>
                                         <?php echo htmlspecialchars($reservation['people_count']); ?> guests
                                         | <?php echo htmlspecialchars($reservation['email']); ?>
                                         | <?php echo htmlspecialchars($reservation['phone']); ?>
                                    </small></p>
                                     <?php if (!empty($reservation['table_type'])): ?>
                                         <p class="mb-1"><small>Table Type: <?php echo htmlspecialchars($reservation['table_type']); ?></small></p>
                                     <?php endif; ?>
                                     <?php if (!empty($reservation['table_location'])): ?>
                                         <p class="mb-1"><small>Location: <?php echo htmlspecialchars($reservation['table_location']); ?></small></p>
                                     <?php endif; ?>
                                     <?php if (!empty($reservation['table_preference'])): ?>
                                         <p class="mb-1"><small>Preference: <?php echo htmlspecialchars($reservation['table_preference']); ?></small></p>
                                     <?php endif; ?>
                                     <?php if (!empty($reservation['special_requests_text'])): ?>
                                         <p class="mb-1"><small>Special Requests: <?php echo htmlspecialchars($reservation['special_requests_text']); ?></small></p>
                                     <?php endif; ?>
                                     <p class="mb-0"><small>
                                         Status: <span class="status-<?php echo strtolower($reservation['status']); ?>"><?php echo htmlspecialchars(ucfirst($reservation['status'])); ?></span>
                                         | Created At: <?php echo htmlspecialchars($reservation['created_at']); ?>
                                         </small></p>
                                </div>
                                <div class="col-md-4 text-end">
                                     <?php if ($reservation['status'] === 'pending'): ?>
                                         <?php if (check_permission('staff')): ?>
                                             <form action="" method="post" class="d-inline">
                                                 <input type="hidden" name="action" value="confirm">
                                                 <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                  <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Confirm Reservation">
                                                      <i class="fas fa-check"></i> Confirm
                                                  </button>
                                             </form>
                                         <?php endif; ?>
                                         <?php if (check_permission('staff')): ?>
                                             <form action="" method="post" class="d-inline">
                                                 <input type="hidden" name="action" value="cancel">
                                                 <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Reservation">
                                                      <i class="fas fa-times"></i> Cancel
                                                  </button>
                                             </form>
                                         <?php endif; ?>
                                     <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                         <span class="badge bg-success text-white me-1">Confirmed</span>
                                         <?php if (check_permission('staff')): ?>
                                             <form action="" method="post" class="d-inline">
                                                 <input type="hidden" name="action" value="cancel">
                                                 <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Reservation">
                                                      <i class="fas fa-times"></i> Cancel
                                                  </button>
                                             </form>
                                         <?php endif; ?>
                                     <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                         <span class="badge bg-danger text-white">Cancelled</span>
                                     <?php endif; ?>

                                     <a href="edit_reservation.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Reservation">
                                         <i class="fas fa-edit"></i> Edit
                                     </a>

                                     <?php if (check_permission('admin')): ?>



                                        
                                        <form action="archive.php" method="post" class="d-inline">
    <input type="hidden" name="user_id" value="<?php echo $reservation['id']; ?>">
    <button type="submit" class="btn btn-sm btn-danger" title="Archive Reservation" onclick="return confirm('Are you sure you want to archive this reservation?');">
        <i class="fas fa-trash-alt"></i> Delete
    </button>
</form>

<?php if (isset($_GET['archived']) && $_GET['archived'] === 'success'): ?>
    <div class="alert alert-warning">Reservation archived successfully!</div>
<?php endif; ?>


                                     <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php // No closing </body> or </html> tag here, as this file is included in admin.php ?>
<?php // JavaScript for search/filter and delete confirmation are in the main admin.php script block ?>