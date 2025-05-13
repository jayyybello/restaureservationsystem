<?php

require_once 'config.php';
require_once 'admin.php';

if (!check_permission('super_admin')) {
    redirect_with_message('dashboard', 'error', 'You do not have permission to access user management.');
    exit;
}

$users = [];


$reconnect_needed = false;

if (!isset($conn) || !is_object($conn)) {
    $reconnect_needed = true;
} elseif (get_class($conn) !== 'mysqli') {
    $reconnect_needed = true;
} else {
    try {
        if (!$conn->ping()) {
            $reconnect_needed = true;
        }
    } catch (Throwable $e) {
        $reconnect_needed = true;
    }
}

if ($reconnect_needed) {
    error_log("Reconnecting to database...");
    $conn = new mysqli("localhost", "root", "", "rrs");

    if ($conn->connect_error) {
        die("Reconnect failed: " . $conn->connect_error);
    }
}


$sql = "SELECT id, username, role, full_name, email, created_at, updated_at FROM adminPanel_users ORDER BY id ASC";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    $result->free();
} else {
    echo "<div class='alert alert-danger'>Query Error: " . $conn->error . "</div>";
}
?>

<div class="row justify-content-between align-items-center mb-4">
    <div class="col-md-6">
        <h1 class="admin-title">Users Management</h1>
        <p class="text-muted">Manage admin panel user accounts and roles</p>
    </div>


    <div class="d-flex justify-content-end gap-2 mb-4"> 
    
    <form method="GET" action="add_user.php" class="flex-grow-1">
        <button type="submit" class="btn btn-primary btn-lg w-100" style="height: 55px;">
            <i class="fas fa-user-plus me-2"></i> Add New User
        </button>
    </form>

   
    <form method="POST" action="archive_user.php" style="width: 220px;">
        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
        <button type="submit" name="archive_user" class="btn btn-danger btn-lg w-100" style="height: 55px;">
            🗃️ Archive
        </button>
    </form>
</div>



<div class="card mb-4">
    <div class="card-header admin-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i> Existing Users
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No users found in the database.
            </div>
        <?php else: ?>
             <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                             <th>Full Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                             <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                 <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                 <td><?php echo htmlspecialchars($user['updated_at']); ?></td>
                                <td>
                                    <?php // Link to Edit User page (will need edit_user.php) ?>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit User">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    <?php  ?>
                                    <?php // Only show delete if NOT the current logged in user and has super admin permission ?>
                                    <?php if ($user['role'] !== 'Super_admin'): ?>
                                    <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                   <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-danger">
                                   <i class="fas fa-trash" style="color: red;"></i> Delete
                                        </button>
                                        </form>
                                    <?php endif; ?> 
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
             </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Note: This file is included within admin.php, so no full HTML structure needed.
// JavaScript for delete confirmation should be in the main admin.php script block.
?>