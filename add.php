<?php
require_once 'config.php';

$errors = [];
$formData = [
    'city' => '',
    'country' => '',
    'year' => '',
    'description' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $sql = "INSERT INTO travels (city, country, year, description) 
                    VALUES (:city, :country, :year, :description)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':city' => $formData['city'],
                ':country' => $formData['country'],
                ':year' => (int)$formData['year'],
                ':description' => $formData['description']
            ]);
            
            // Redirect to index after successful submission
            header('Location: index.php?added=1');
            exit;
        } catch (PDOException $e) {
            error_log("Error inserting travel: " . $e->getMessage());
            $errors[] = 'An error occurred while saving your travel. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel App - Add Travel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Add New Travel</h1>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="message message-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="add.php" class="travel-form">
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
                <button type="submit" class="btn btn-primary">Add Travel</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

