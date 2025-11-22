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
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejseapp - Mine Rejser</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Mine rejser</h1>
            <a href="add.php" class="btn btn-primary">Tilføj ny rejse</a>
        </header>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="message message-success">
                <p>Rejse slettet succesfuldt!</p>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
            <div class="message message-success">
                <p>Rejse tilføjet succesfuldt!</p>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="message message-success">
                <p>Rejse opdateret succesfuldt!</p>
            </div>
        <?php endif; ?>

        <?php if (empty($travels)): ?>
            <div class="message">
                <p>Ingen rejser registreret endnu. <a href="add.php">Tilføj din første rejsedestination!</a></p>
            </div>
        <?php else: ?>
            <table class="travels-table">
                <thead>
                    <tr>
                        <th class="sortable">
                            <a href="?sort=city&order=<?php echo getSortOrder('city', $sortBy, $sortOrder); ?>" class="sort-link">
                                By
                                <?php if ($sortBy === 'city'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="sortable">
                            <a href="?sort=country&order=<?php echo getSortOrder('country', $sortBy, $sortOrder); ?>" class="sort-link">
                                Land
                                <?php if ($sortBy === 'country'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="sortable">
                            <a href="?sort=year&order=<?php echo getSortOrder('year', $sortBy, $sortOrder); ?>" class="sort-link">
                                År
                                <?php if ($sortBy === 'year'): ?>
                                    <span class="sort-icon"><?php echo $sortOrder === 'asc' ? '↑' : '↓'; ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Beskrivelse</th>
                        <th>Handlinger</th>
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
                                <a href="edit.php?id=<?php echo (int)$travel['id']; ?>" class="btn btn-small btn-edit" title="Rediger">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <a href="delete.php?id=<?php echo (int)$travel['id']; ?>" class="btn btn-small btn-delete" title="Slet">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

