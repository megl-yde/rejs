<?php
require_once 'config.php';

$errors = [];
$success = false;
$notFound = false;
$travelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$travelData = null;

if ($travelId <= 0) {
    $notFound = true;
    $errors[] = 'Invalid travel ID.';
}

// Load travel data for confirmation display
if (!$notFound) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT id, city, country, year FROM travels WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $travelId]);
        $travelData = $stmt->fetch();
        
        if (!$travelData) {
            $notFound = true;
            $errors[] = 'Travel not found.';
        }
    } catch (PDOException $e) {
        error_log("Error loading travel: " . $e->getMessage());
        $errors[] = 'An error occurred while loading the travel.';
        $notFound = true;
    }
}

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notFound && $travelData) {
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    
    if ($confirm === 'yes') {
        try {
            $pdo = getDBConnection();
            $sql = "DELETE FROM travels WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $travelId]);
            
            // Redirect to index after successful deletion
            header('Location: index.php?deleted=1');
            exit;
        } catch (PDOException $e) {
            error_log("Error deleting travel: " . $e->getMessage());
            $errors[] = 'An error occurred while deleting the travel. Please try again.';
        }
    } else {
        // User cancelled - redirect to index
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel App - Delete Travel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Delete Travel</h1>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </header>

        <?php if ($notFound): ?>
            <div class="message message-error">
                <p>Travel not found. <a href="index.php">Return to travel list</a></p>
            </div>
        <?php elseif (!empty($errors)): ?>
            <div class="message message-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($travelData): ?>
            <div class="message message-error">
                <p><strong>Are you sure you want to delete this travel?</strong></p>
                <p>This action cannot be undone.</p>
            </div>

            <div class="delete-confirmation">
                <div class="travel-info">
                    <p><strong>City:</strong> <?php echo htmlspecialchars($travelData['city'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Country:</strong> <?php echo htmlspecialchars($travelData['country'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($travelData['year'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <form method="POST" action="delete.php?id=<?php echo $travelId; ?>" class="delete-form">
                    <input type="hidden" name="confirm" value="yes">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-delete">Yes, Delete</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

