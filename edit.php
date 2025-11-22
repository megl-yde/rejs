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
    'description' => '',
    'latitude' => '',
    'longitude' => ''
];

if (!$notFound) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT id, city, country, year, description, latitude, longitude FROM travels WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $travelId]);
        $travel = $stmt->fetch();
        
        if ($travel) {
            $formData = [
                'city' => $travel['city'],
                'country' => $travel['country'],
                'year' => $travel['year'],
                'description' => $travel['description'],
                'latitude' => $travel['latitude'] ?? '',
                'longitude' => $travel['longitude'] ?? ''
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
    $formData['latitude'] = trim($_POST['latitude'] ?? '');
    $formData['longitude'] = trim($_POST['longitude'] ?? '');

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

    // Validate coordinates if provided
    $latitude = null;
    $longitude = null;
    if (!empty($formData['latitude'])) {
        $latitude = filter_var($formData['latitude'], FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $latitude < -90 || $latitude > 90) {
            $errors[] = 'Latitude skal være et tal mellem -90 og 90.';
        }
    }
    if (!empty($formData['longitude'])) {
        $longitude = filter_var($formData['longitude'], FILTER_VALIDATE_FLOAT);
        if ($longitude === false || $longitude < -180 || $longitude > 180) {
            $errors[] = 'Longitude skal være et tal mellem -180 og 180.';
        }
    }
    // Both or neither must be set
    if ((!empty($formData['latitude']) && empty($formData['longitude'])) || 
        (empty($formData['latitude']) && !empty($formData['longitude']))) {
        $errors[] = 'Både latitude og longitude skal angives, eller begge skal være tomme.';
    }

    // If no errors, update database
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $sql = "UPDATE travels 
                    SET city = :city, country = :country, year = :year, description = :description, 
                        latitude = :latitude, longitude = :longitude 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $travelId,
                ':city' => $formData['city'],
                ':country' => $formData['country'],
                ':year' => (int)$formData['year'],
                ':description' => $formData['description'],
                ':latitude' => $latitude,
                ':longitude' => $longitude
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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

                <div class="form-group">
                    <label>Koordinater</label>
                    <div class="coordinate-actions">
                        <button type="button" id="geocode-btn" class="btn btn-secondary">Find koordinater automatisk</button>
                        <button type="button" id="clear-coords-btn" class="btn btn-secondary">Ryd koordinater</button>
                    </div>
                    <div class="coordinate-group">
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="number" 
                                   id="latitude" 
                                   name="latitude" 
                                   value="<?php echo htmlspecialchars($formData['latitude'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   step="any"
                                   min="-90"
                                   max="90"
                                   placeholder="f.eks. 55.6761">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="number" 
                                   id="longitude" 
                                   name="longitude" 
                                   value="<?php echo htmlspecialchars($formData['longitude'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   step="any"
                                   min="-180"
                                   max="180"
                                   placeholder="f.eks. 12.5683">
                        </div>
                    </div>
                    <div id="map-preview-container">
                        <div id="map-preview"></div>
                    </div>
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

    <script>
        let previewMap;
        let previewMarker;

        // Initialize map preview
        function initMapPreview() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lon = parseFloat(document.getElementById('longitude').value);
            const container = document.getElementById('map-preview-container');
            const mapElement = document.getElementById('map-preview');

            // Remove existing map if it exists
            if (previewMap) {
                previewMap.remove();
                previewMap = null;
                previewMarker = null;
            }

            if (!isNaN(lat) && !isNaN(lon) && lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180) {
                // Show container first
                container.style.display = 'block';
                
                // Use setTimeout to ensure container is visible and has correct size
                function createMap() {
                    // Double-check that container is visible
                    if (container.offsetWidth === 0 || container.offsetHeight === 0) {
                        // If not visible, try again after a short delay
                        setTimeout(createMap, 100);
                        return;
                    }

                    // Create map
                    previewMap = L.map('map-preview', {
                        zoomControl: true
                    }).setView([lat, lon], 13);
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(previewMap);

                    // Add marker
                    previewMarker = L.marker([lat, lon]).addTo(previewMap);
                    
                    // Force map to recalculate size after a short delay
                    setTimeout(function() {
                        if (previewMap) {
                            previewMap.invalidateSize();
                        }
                    }, 100);
                }
                
                setTimeout(createMap, 50);
            } else {
                container.style.display = 'none';
            }
        }

        // Geocode button
        document.getElementById('geocode-btn').addEventListener('click', function() {
            const city = document.getElementById('city').value;
            const country = document.getElementById('country').value;

            if (!city || !country) {
                alert('Indtast venligst både by og land først.');
                return;
            }

            this.disabled = true;
            this.textContent = 'Søger...';

            const params = new URLSearchParams({
                city: city,
                country: country
            });

            fetch('api/geocode.php?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Geocoding failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data && data.lat && data.lon) {
                    document.getElementById('latitude').value = parseFloat(data.lat).toFixed(8);
                    document.getElementById('longitude').value = parseFloat(data.lon).toFixed(8);
                    initMapPreview();
                } else {
                    alert('Kunne ikke finde koordinater for ' + city + ', ' + country);
                }
            })
            .catch(error => {
                console.error('Geocoding error:', error);
                alert('Fejl ved opslag af koordinater: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Find koordinater automatisk';
            });
        });

        // Clear coordinates button
        document.getElementById('clear-coords-btn').addEventListener('click', function() {
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            initMapPreview();
        });

        // Update map when coordinates change
        document.getElementById('latitude').addEventListener('input', initMapPreview);
        document.getElementById('longitude').addEventListener('input', initMapPreview);

        // Initialize map on page load if coordinates exist
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit longer to ensure all styles are applied
            setTimeout(function() {
                initMapPreview();
            }, 100);
        });

        // Handle window resize to update map size
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (previewMap) {
                    previewMap.invalidateSize();
                }
            }, 250);
        });

        // Use IntersectionObserver to detect when container becomes visible
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && previewMap) {
                        // Container is now visible, invalidate size
                        setTimeout(function() {
                            if (previewMap) {
                                previewMap.invalidateSize();
                            }
                        }, 100);
                    }
                });
            }, {
                threshold: 0.1
            });

            // Observe the container when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('map-preview-container');
                if (container) {
                    observer.observe(container);
                }
            });
        }
    </script>
</body>
</html>

