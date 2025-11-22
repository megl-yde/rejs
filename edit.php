<?php
require_once 'config.php';

$errors = [];
$notFound = false;
$travelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($travelId <= 0) {
    $notFound = true;
    $errors[] = 'Invalid travel ID.';
}

// Load existing travel data
$formData = [
    'city' => '',
    'country' => '',
    'year' => '',
    'description' => ''
];

if (!$notFound) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT id, city, country, year, description FROM travels WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $travelId]);
        $travel = $stmt->fetch();
        
        if ($travel) {
            $formData = [
                'city' => $travel['city'],
                'country' => $travel['country'],
                'year' => $travel['year'],
                'description' => $travel['description']
            ];
        } else {
            $notFound = true;
            $errors[] = 'Travel not found.';
        }
    } catch (PDOException $e) {
        error_log("Error loading travel: " . $e->getMessage());
        $errors[] = 'An error occurred while loading the travel.';
        $notFound = true;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notFound) {
    // Get and sanitize input
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['country'] = trim($_POST['country'] ?? '');
    $formData['year'] = trim($_POST['year'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');

    // Validation
    if (empty($formData['city'])) {
        $errors[] = 'City is required.';
    } elseif (strlen($formData['city']) > 255) {
        $errors[] = 'City name is too long (max 255 characters).';
    }

    if (empty($formData['country'])) {
        $errors[] = 'Country is required.';
    } elseif (strlen($formData['country']) > 255) {
        $errors[] = 'Country name is too long (max 255 characters).';
    }

    if (empty($formData['year'])) {
        $errors[] = 'Year is required.';
    } elseif (!is_numeric($formData['year'])) {
        $errors[] = 'Year must be a number.';
    } else {
        $year = (int)$formData['year'];
        if ($year < 1000 || $year > 9999) {
            $errors[] = 'Year must be a valid 4-digit year.';
        }
    }

    // If no errors, update database
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $sql = "UPDATE travels 
                    SET city = :city, country = :country, year = :year, description = :description 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $travelId,
                ':city' => $formData['city'],
                ':country' => $formData['country'],
                ':year' => (int)$formData['year'],
                ':description' => $formData['description']
            ]);
            
            // Redirect to index after successful update
            header('Location: index.php?updated=1');
            exit;
        } catch (PDOException $e) {
            error_log("Error updating travel: " . $e->getMessage());
            $errors[] = 'An error occurred while updating the travel. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel App - Edit Travel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Travel</h1>
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
        <?php endif; ?>

        <?php if (!$notFound): ?>
            <form method="POST" action="edit.php?id=<?php echo $travelId; ?>" class="travel-form">
                <div class="form-group">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" 
                           id="city" 
                           name="city" 
                           value="<?php echo htmlspecialchars($formData['city'], ENT_QUOTES, 'UTF-8'); ?>" 
                           required 
                           maxlength="255"
                           placeholder="Enter city name">
                </div>

                <div class="form-group">
                    <label for="country">Country <span class="required">*</span></label>
                    <input type="text" 
                           id="country" 
                           name="country" 
                           value="<?php echo htmlspecialchars($formData['country'], ENT_QUOTES, 'UTF-8'); ?>" 
                           required 
                           maxlength="255"
                           placeholder="Enter country name">
                </div>

                <div class="form-group">
                    <label for="year">Year <span class="required">*</span></label>
                    <input type="number" 
                           id="year" 
                           name="year" 
                           value="<?php echo htmlspecialchars($formData['year'], ENT_QUOTES, 'UTF-8'); ?>" 
                           required 
                           min="1000" 
                           max="9999"
                           placeholder="e.g., 2023">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" 
                              name="description" 
                              rows="5" 
                              placeholder="Enter a description of your travel experience..."><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Travel</button>
                    <a href="delete.php?id=<?php echo $travelId; ?>" class="btn btn-delete">Delete</a>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

