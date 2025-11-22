<?php
require_once 'config.php';

// Get sorting parameter
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'year';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Validate sort parameters
$allowedSorts = ['year', 'country', 'city'];
$allowedOrders = ['asc', 'desc'];

if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'year';
}
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'desc';
}

// Helper function to get sort order for a column
function getSortOrder($column, $currentSort, $currentOrder) {
    if ($currentSort === $column) {
        // If already sorting by this column, toggle order
        return $currentOrder === 'asc' ? 'desc' : 'asc';
    } else {
        // Default to asc for new columns, except year defaults to desc
        return $column === 'year' ? 'desc' : 'asc';
    }
}

// Build SQL query
$sql = "SELECT id, city, country, year, description, created_at 
        FROM travels 
        ORDER BY " . $sortBy . " " . strtoupper($sortOrder);

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query($sql);
    $travels = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching travels: " . $e->getMessage());
    $travels = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel App - My Travels</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Travels</h1>
            <a href="add.php" class="btn btn-primary">Add New Travel</a>
        </header>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="message message-success">
                <p>Travel deleted successfully!</p>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
            <div class="message message-success">
                <p>Travel added successfully!</p>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="message message-success">
                <p>Travel updated successfully!</p>
            </div>
        <?php endif; ?>

        <?php if (empty($travels)): ?>
            <div class="message">
                <p>No travels recorded yet. <a href="add.php">Add your first travel destination!</a></p>
            </div>
        <?php else: ?>
            <table class="travels-table">
                <thead>
                    <tr>
                        <th class="sortable">
                            <a href="?sort=city&order=<?php echo getSortOrder('city', $sortBy, $sortOrder); ?>" class="sort-link">
                                City
                                <?php if ($sortBy === 'city'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="sortable">
                            <a href="?sort=country&order=<?php echo getSortOrder('country', $sortBy, $sortOrder); ?>" class="sort-link">
                                Country
                                <?php if ($sortBy === 'country'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="sortable">
                            <a href="?sort=year&order=<?php echo getSortOrder('year', $sortBy, $sortOrder); ?>" class="sort-link">
                                Year
                                <?php if ($sortBy === 'year'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($travels as $travel): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($travel['city'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($travel['country'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($travel['year'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($travel['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo (int)$travel['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                <a href="delete.php?id=<?php echo (int)$travel['id']; ?>" class="btn btn-small btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

