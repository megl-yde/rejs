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
        $errors[] = 'By er påkrævet.';
    } elseif (strlen($formData['city']) > 255) {
        $errors[] = 'Bynavn er for langt (maks 255 tegn).';
    }

    if (empty($formData['country'])) {
        $errors[] = 'Land er påkrævet.';
    } elseif (strlen($formData['country']) > 255) {
        $errors[] = 'Landnavn er for langt (maks 255 tegn).';
    }

    if (empty($formData['year'])) {
        $errors[] = 'År er påkrævet.';
    } elseif (!is_numeric($formData['year'])) {
        $errors[] = 'År skal være et tal.';
    } else {
        $year = (int)$formData['year'];
        if ($year < 1000 || $year > 9999) {
            $errors[] = 'År skal være et gyldigt 4-cifret årstal.';
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
            $errors[] = 'Der opstod en fejl ved lagring af din rejse. Prøv venligst igen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejseapp - Tilføj Rejse</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Tilføj Ny Rejse</h1>
            <a href="index.php" class="btn btn-secondary">Tilbage til Liste</a>
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
                <label for="city">By <span class="required">*</span></label>
                <input type="text" 
                       id="city" 
                       name="city" 
                       value="<?php echo htmlspecialchars($formData['city'], ENT_QUOTES, 'UTF-8'); ?>" 
                       required 
                       maxlength="255"
                       placeholder="Indtast bynavn">
            </div>

            <div class="form-group">
                <label for="country">Land <span class="required">*</span></label>
                <input type="text" 
                       id="country" 
                       name="country" 
                       value="<?php echo htmlspecialchars($formData['country'], ENT_QUOTES, 'UTF-8'); ?>" 
                       required 
                       maxlength="255"
                       placeholder="Indtast landnavn">
            </div>

            <div class="form-group">
                <label for="year">År <span class="required">*</span></label>
                <input type="number" 
                       id="year" 
                       name="year" 
                       value="<?php echo htmlspecialchars($formData['year'], ENT_QUOTES, 'UTF-8'); ?>" 
                       required 
                       min="1000" 
                       max="9999"
                       placeholder="f.eks. 2023">
            </div>

            <div class="form-group">
                <label for="description">Beskrivelse</label>
                <textarea id="description" 
                          name="description" 
                          rows="5" 
                          placeholder="Indtast en beskrivelse af din rejseoplevelse..."><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Tilføj Rejse</button>
                <a href="index.php" class="btn btn-secondary">Annuller</a>
            </div>
        </form>
    </div>
</body>
</html>

