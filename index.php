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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-button active" data-tab="map">Kort</button>
                <button class="tab-button" data-tab="list">Liste</button>
            </div>

            <!-- Map View -->
            <div id="map-view" class="tab-content active">
                <div id="travel-map"></div>
                <p class="map-info">Kortet viser alle rejser med koordinater. Rejser uden koordinater vises ikke på kortet.</p>
            </div>

            <!-- List View -->
            <div id="list-view" class="tab-content">
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
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab + '-view').classList.add('active');

                    // Initialize map if switching to map tab and map not initialized
                    if (targetTab === 'map' && !window.mapInitialized) {
                        initializeMap();
                    }
                });
            });

            // Initialize map if map tab is active by default
            const activeTab = document.querySelector('.tab-button.active');
            if (activeTab && activeTab.getAttribute('data-tab') === 'map') {
                initializeMap();
            }
        });

        // Map initialization
        let map;
        let mapInitialized = false;

        function initializeMap() {
            if (mapInitialized) return;
            
            // Initialize map centered on Europe
            map = L.map('travel-map').setView([54.5, 10.0], 3);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Load and display travels
            fetch('api/travels.php')
                .then(response => response.json())
                .then(travels => {
                    if (travels.length === 0) {
                        document.querySelector('.map-info').textContent = 'Ingen rejser med koordinater fundet. Tilføj koordinater til dine rejser for at se dem på kortet.';
                        return;
                    }

                    // Group travels by coordinates
                    const locationGroups = new Map();
                    
                    travels.forEach(travel => {
                        const lat = parseFloat(travel.latitude);
                        const lon = parseFloat(travel.longitude);
                        
                        if (!isNaN(lat) && !isNaN(lon)) {
                            // Create a key from coordinates (rounded to avoid floating point issues)
                            const coordKey = `${lat.toFixed(8)},${lon.toFixed(8)}`;
                            
                            if (!locationGroups.has(coordKey)) {
                                locationGroups.set(coordKey, {
                                    lat: lat,
                                    lon: lon,
                                    travels: []
                                });
                            }
                            
                            locationGroups.get(coordKey).travels.push(travel);
                        }
                    });

                    // Sort travels within each group by year (descending)
                    locationGroups.forEach(group => {
                        group.travels.sort((a, b) => parseInt(b.year) - parseInt(a.year));
                    });

                    // Create markers for each unique location
                    const bounds = [];
                    locationGroups.forEach(group => {
                        // Create popup content showing all travels to this location
                        const firstTravel = group.travels[0];
                        let popupContent = `
                            <div class="map-popup">
                                <h3>${escapeHtml(firstTravel.city)}, ${escapeHtml(firstTravel.country)}</h3>
                        `;
                        
                        if (group.travels.length > 1) {
                            popupContent += `<p class="travel-count"><strong>${group.travels.length} rejser</strong></p>`;
                        }
                        
                        popupContent += '<div class="travel-list">';
                        group.travels.forEach(travel => {
                            popupContent += `
                                <div class="travel-item">
                                    <p><strong>År:</strong> ${escapeHtml(travel.year)}</p>
                                    ${travel.description ? `<p>${escapeHtml(travel.description)}</p>` : ''}
                                    <a href="edit.php?id=${travel.id}" class="btn btn-small btn-edit">Rediger</a>
                                </div>
                            `;
                            if (travel !== group.travels[group.travels.length - 1]) {
                                popupContent += '<hr class="travel-separator">';
                            }
                        });
                        popupContent += '</div></div>';
                        
                        const marker = L.marker([group.lat, group.lon]).addTo(map);
                        marker.bindPopup(popupContent);
                        bounds.push([group.lat, group.lon]);
                    });

                    // Fit map to show all markers
                    if (bounds.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                })
                .catch(error => {
                    console.error('Error loading travels:', error);
                    document.querySelector('.map-info').textContent = 'Fejl ved indlæsning af rejser.';
                });

            mapInitialized = true;
            window.mapInitialized = true;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

