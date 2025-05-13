<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "rrs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Kunin lahat ng archived reservations
$sql = "SELECT * FROM table_booking WHERE is_archived = 1 ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #6c757d;
            color: white;
            font-size: 1.2rem;
            font-weight: 500;
            border-radius: 8px 8px 0 0;
        }
        .card-body {
            background-color: white;
            border-radius: 0 0 8px 8px;
        }
        .card-footer {
            background-color: transparent;
            border-top: none;
        }
        .alert {
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .btn-restore {
            width: 100%;
            border-radius: 50px;
        }
        .btn-back {
            width: 100%;
            border-radius: 50px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center">Archived Information 🗃️</h2>

    <?php if (isset($_GET['restored']) && $_GET['restored'] === 'success'): ?>
        <div class="alert alert-success mb-4">
            Reservation successfully restored!
        </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <?php echo htmlspecialchars($row['name']); ?> - <?php echo htmlspecialchars($row['date']); ?> at <?php echo htmlspecialchars($row['time']); ?>
                </div>
                <div class="card-body">
                    <p><strong>Guests:</strong> <?php echo $row['people_count']; ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['phone']); ?></p>
                </div>
                <div class="card-footer">
                    <form method="POST" action="restore.php">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="restore_user" class="btn btn-success btn-restore">
                            Restore Reservation
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info mb-4">
            No archived Information found.
        </div>
    <?php endif; ?>

    <a href="admin.php" class="btn btn-secondary btn-back mt-4">Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
