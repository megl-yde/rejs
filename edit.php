<?php
require_once 'config.php';

$errors = [];
$notFound = false;
$travelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($travelId <= 0) {
    $notFound = true;
    $errors[] = 'Ugyldigt rejse-ID.';
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
            $errors[] = 'Rejse ikke fundet.';
        }
    } catch (PDOException $e) {
        error_log("Error loading travel: " . $e->getMessage());
        $errors[] = 'Der opstod en fejl ved indlæsning af rejsen.';
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
            $errors[] = 'Der opstod en fejl ved opdatering af rejsen. Prøv venligst igen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejseapp - Rediger rejse</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Rediger rejse</h1>
            <a href="index.php" class="btn btn-secondary">Tilbage til Liste</a>
        </header>

        <?php if ($notFound): ?>
            <div class="message message-error">
                <p>Rejse ikke fundet. <a href="index.php">Tilbage til rejseliste</a></p>
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
                    <button type="submit" class="btn btn-primary">Opdater Rejse</button>
                    <a href="delete.php?id=<?php echo $travelId; ?>" class="btn btn-delete" title="Slet">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </a>
                    <a href="index.php" class="btn btn-secondary">Annuller</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

