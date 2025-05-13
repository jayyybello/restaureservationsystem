<?php
/**
 * Savory Haven Restaurant - Admin Panel - Edit Table Page
 * Displays a form to edit an existing table in the table_mapping table.
 * Accessible only by 'admin'.
 */

// Include the configuration file which handles DB connection, session start, and helpers
require_once 'config.php';

// --- Check if the user has 'admin' permission to access this page ---
if (!check_permission('admin')) {
    redirect_with_message('dashboard', 'error', 'You do not have permission to edit tables.');
    exit;
}

// --- Get Table ID from URL ---
$table_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Validate Table ID
if ($table_id === false || $table_id === null || $table_id <= 0) {
    redirect_with_message('tables', 'error', 'Invalid table ID specified.');
    exit;
}

// --- Fetch Table Data from Database ---
$table = null;
if ($conn) {
    $sql = "SELECT id, physical_table_id, capacity, location, availability FROM table_mapping WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $table = $result->fetch_assoc();
        } else {
            redirect_with_message('tables', 'error', 'Table not found.');
            exit;
        }
        $stmt->close();
    } else {
        error_log("Database error preparing statement: " . $conn->error);
        redirect_with_message('tables', 'error', 'Error fetching table data.');
        exit;
    }
} else {
    error_log("Database connection not available in edit_table.php");
    redirect_with_message('tables', 'error', 'Database connection error.');
    exit;
}

// --- Get Available Status Options ---
$available_statuses = ['available', 'taken', 'unavailable'];

// Get table details for display
$table_details = null;
if ($conn && $table) {
    $sql = "SELECT * FROM table_mapping WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $table_details = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Table #<?php echo htmlspecialchars($table['physical_table_id']); ?> - Savory Haven Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .edit-table-form-container {
            max-width: 600px;
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
        .table-info {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 15px;
            margin-bottom: 20px;
        }
        .table-info h4 {
            color: #9c3d26;
            margin-bottom: 10px;
        }
        .table-info p {
            margin-bottom: 5px;
        }
        .table-info .badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        .table-number {
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
    <div class="edit-table-form-container">
        <div class="text-center">
            <div class="table-number">
                <i class="fas fa-chair me-2"></i>
                Table #<?php echo htmlspecialchars($table['physical_table_id']); ?>
            </div>
            <div class="editing-indicator">
                <i class="fas fa-edit me-2"></i>
                Currently Editing Table #<?php echo htmlspecialchars($table['physical_table_id']); ?>
            </div>
        </div>

        <?php if ($table_details): ?>
        <div class="table-info">
            <h4><i class="fas fa-info-circle me-2"></i>Table Information</h4>
            <p><strong>Table Number:</strong> #<?php echo htmlspecialchars($table_details['physical_table_id']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($table_details['location']); ?></p>
            <p><strong>Capacity:</strong> <?php echo htmlspecialchars($table_details['capacity']); ?> persons</p>
            <p><strong>Current Status:</strong> 
                <span class="badge bg-<?php 
                    echo $table_details['availability'] === 'available' ? 'success' : 
                        ($table_details['availability'] === 'taken' ? 'warning' : 'danger'); 
                ?>">
                    <?php echo htmlspecialchars(ucfirst($table_details['availability'])); ?>
                </span>
            </p>
        </div>
        <?php endif; ?>

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

        <form action="admin.php" method="post" id="editTableForm">
            <input type="hidden" name="action" value="edit_table">
            <input type="hidden" name="table_id" value="<?php echo htmlspecialchars($table['id']); ?>">
            <input type="hidden" name="redirect_to" value="tables">

            <div class="mb-3">
                <label for="physical_table_id" class="form-label">Physical Table ID:</label>
                <input type="number" class="form-control" id="physical_table_id" name="physical_table_id" 
                       value="<?php echo htmlspecialchars($table['physical_table_id']); ?>" required min="1">
                <div class="form-text">The physical identifier of the table in the restaurant.</div>
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Capacity:</label>
                <input type="number" class="form-control" id="capacity" name="capacity" 
                       value="<?php echo htmlspecialchars($table['capacity']); ?>" required min="1">
                <div class="form-text">Maximum number of guests this table can accommodate.</div>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Location:</label>
                <input type="text" class="form-control" id="location" name="location" 
                       value="<?php echo htmlspecialchars($table['location']); ?>" required>
                <div class="form-text">Table location (e.g., Window, Patio, Bar).</div>
            </div>

            <div class="mb-3">
                <label for="availability" class="form-label">Availability Status:</label>
                <select class="form-select" id="availability" name="availability" required>
                    <?php foreach ($available_statuses as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" 
                                <?php echo ($table['availability'] === $status) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Current status of the table.</div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Changes for Table #<?php echo htmlspecialchars($table['physical_table_id']); ?>
            </button>

            <a href="admin.php?page=tables" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Cancel
            </a>
        </form>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editTableForm');
            
            form.addEventListener('submit', function(event) {
                const capacity = document.getElementById('capacity').value;
                const physicalId = document.getElementById('physical_table_id').value;
                
                if (capacity < 1) {
                    event.preventDefault();
                    alert('Capacity must be at least 1!');
                }
                
                if (physicalId < 1) {
                    event.preventDefault();
                    alert('Physical Table ID must be at least 1!');
                }
            });
        });
    </script>
</body>
</html>