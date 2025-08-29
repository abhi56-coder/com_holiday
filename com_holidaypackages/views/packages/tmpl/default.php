<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('bootstrap.loadCss');
HTMLHelper::_('jquery.framework');
$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_holidaypackages/css/packages.css');

$app = Factory::getApplication();
$input = $app->input;

$destinationId = $input->getInt('id', 0);
if (!$destinationId) {
    $destinationId = $input->getInt('destination_id', 0);
    if (!$destinationId && isset($_GET['id'])) {
        $destinationId = (int)$_GET['id']; 
    }
}
echo '<script>console.log("Initial destinationId from PHP:", ' . json_encode($destinationId) . ');</script>'; // Debug log
$startingFrom = $input->getString('starting_from', '');
$startDate = $input->getString('start_date', '');
$rooms = $input->getInt('rooms', 1);
$adults = $input->getInt('adults', 1);
$children = $input->getInt('children', 0);

$db = Factory::getDbo();

try {
    $model = $this->getModel();
    $durationRange = $model->getDurationRangeByDestination($destinationId);
    $minDuration = $durationRange['min'] ?? 1;
    $maxDuration = $durationRange['max'] ?? 30;
    echo '<script>window.packageSettings = window.packageSettings || {}; window.packageSettings.minDuration = ' . (int)$minDuration . '; window.packageSettings.maxDuration = ' . (int)$maxDuration . ';</script>';

    $query = $db->getQuery(true)
        ->select($db->quoteName('name'))
        ->from($db->quoteName('n4gvg__cities'));  
    $db->setQuery($query);
    $famousCities = $db->loadColumn();

    $query = $db->getQuery(true)
        ->select('DISTINCT duration')
        ->from($db->quoteName('n4gvg__holiday_packages'))
        ->where($db->quoteName('destination_id') . ' = ' . (int)$destinationId)
        ->order('duration ASC');
    $db->setQuery($query);
    $durations = $db->loadColumn();

    $query = $db->getQuery(true)
        ->select('MIN(price_per_person) AS min_price, MAX(price_per_person) AS max_price')
        ->from($db->quoteName('n4gvg__holiday_packages'))
        ->where($db->quoteName('destination_id') . ' = ' . (int)$destinationId);
    $db->setQuery($query);
    $priceRange = $db->loadRow();
    $minPrice = ($priceRange[0] !== null) ? (int)$priceRange[0] : 0;
    $maxPrice = ($priceRange[1] !== null) ? (int)$priceRange[1] : 250000;

    $durationRanges = [];
    $uniqueDurations = array_unique(array_map('intval', $durations));
    sort($uniqueDurations);

    if (!empty($uniqueDurations)) {
        foreach ($uniqueDurations as $duration) {
            $durationRanges[$duration] = [
                'label' => $duration . ($duration > 1 ? ' days' : ' day'),
                'count' => 0
            ];
        }
    } else {
        $durationRanges['1-15'] = ['label' => '1-15 days', 'count' => 0];
    }

    $selectedDestinationTitle = 'Select Destination';
    $destinations = isset($this->destinations) && (is_array($this->destinations) || is_object($this->destinations)) ? $this->destinations : [];
    foreach ($destinations as $dest) {
        if ($dest->id == $destinationId) {
            $selectedDestinationTitle = $dest->title . ' (' . (int)$dest->package_count . ')';
            break;
        }
    }

    $filterOptions = $this->filterOptions ?? (object)[
        'durations' => [],
        'flights' => [],
        'hotelCategories' => [],
        'cities' => [],
        'packageTypes' => []
    ];
    $packageCounts = $this->packageCounts ?? (object)['all_packages' => 0];

    if (!empty($filterOptions->durations)) {
        foreach ($durationRanges as $duration => &$data) {
            if (isset($filterOptions->durations[$duration])) {
                $data['count'] = (int)$filterOptions->durations[$duration];
            }
        }
    }

    $currentFilters = [
        'duration_range' => $input->getString('duration_range', ''),
        'flights' => $input->getString('flights', ''),
        'hotel_category' => $input->get('hotel_category', [], 'array'),
        'cities' => $input->get('cities', [], 'array'),
        'package_type' => $input->get('package_type', [], 'array'),
        'special_package' => $input->get('special_package', [], 'array'),
        'sort' => $input->getString('sort', 'popular'),
        'tab_filter' => $input->getString('tab_filter', 'all'),
        'min_price' => $input->getInt('min_price', $minPrice),
        'max_price' => $input->getInt('max_price', $maxPrice)
    ];

    $selectedFiltersPresent = false;
    foreach ($currentFilters as $key => $value) {
        if ($key !== 'sort' && $key !== 'tab_filter' && $key !== 'min_price' && $key !== 'max_price' && (!empty($value) || (is_array($value) && !empty($value)))) {
            $selectedFiltersPresent = true;
            break;
        }
    }
    if ($currentFilters['min_price'] > $minPrice || $currentFilters['max_price'] < $maxPrice) {
        $selectedFiltersPresent = true;
    }

    $maxDurationAvailable = !empty($uniqueDurations) ? max($uniqueDurations) : $maxDuration;
    $currentDurationValue = !empty($currentFilters['duration_range']) ? (int)explode('-', $currentFilters['duration_range'])[1] : $maxDuration;

    $query = $db->getQuery(true)
        ->select('DISTINCT special_package')
        ->from($db->quoteName('n4gvg__holiday_packages'))
        ->where('published = 1')
        ->where($db->quoteName('destination_id') . ' = ' . (int)$destinationId)
        ->where($db->quoteName('special_package') . ' IS NOT NULL AND ' . $db->quoteName('special_package') . ' != ""')
        ->order('special_package ASC');
    $db->setQuery($query);
    $specialPackagesRaw = $db->loadColumn();

    $specialPackages = [];
    foreach ($specialPackagesRaw as $jsonString) {
        $decoded = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                if (isset($value['special_package']['detail'])) {
                    $specialPackages[] = $value['special_package']['detail'];
                }
            }
        }
    }
    $specialPackages = array_unique($specialPackages);

    $packageND = [];
    $query = $db->getQuery(true)
        ->select('package_id, MAX(day_number) as max_days')
        ->from($db->quoteName('n4gvg__holiday_itineraries'))
        ->where($db->quoteName('package_id') . ' IN (SELECT id FROM ' . $db->quoteName('n4gvg__holiday_packages') . ' WHERE destination_id = ' . (int)$destinationId . ')')
        ->group('package_id');
    $db->setQuery($query);
    $itineraryDays = $db->loadAssocList();
    foreach ($itineraryDays as $row) {
        $maxDays = (int)$row['max_days'];
        $nights = $maxDays;
        $days = $maxDays + 1;
        $packageND[$row['package_id']] = [$nights, $days];
    }
} catch (Exception $e) {
    $app->enqueueMessage('Database error: ' . $e->getMessage(), 'error');
}


$app = Factory::getApplication();
$input = $app->input;
$id = (int) $input->getInt('id');

$imagePath = '';
$destinationTitle = '';
if ($id > 0) {
    $db = Factory::getDbo();

    $query = $db->getQuery(true)
        ->select([$db->quoteName('image'), $db->quoteName('title')])
        ->from($db->quoteName('n4gvg__holiday_destinations'))
        ->where($db->quoteName('id') . ' = ' . $db->quote($id));

    $db->setQuery($query);
    $result = $db->loadAssoc(); 

    if ($result) {
        $imagePath = $result['image'] ?? '';
        $destinationTitle = $result['title'] ?? '';
    }
}
$fullImageUrl = $imagePath ? Uri::root() . ltrim($imagePath, '/') : '';

?>


    <div class="search-section" id="search-section">
        <div class="form-group">
            <button class="custom-button" id="starting-from"><?php echo htmlspecialchars($startingFrom ?: 'Select City', ENT_QUOTES, 'UTF-8'); ?></button>
            <div class="dropdown" id="starting-from-dropdown">
                <div class="option" data-city="">Select City</div>
                <?php foreach ($famousCities as $city): ?>
                    <div class="option" data-city="<?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <button class="custom-button" id="destination" data-id="<?php echo (int)$destinationId; ?>">
                <?php echo htmlspecialchars($selectedDestinationTitle, ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <div class="dropdown" id="destination-dropdown">
                <div class="option" data-id="">Select Destination</div>
                <?php if (!empty($destinations)): ?>
                    <?php foreach ($destinations as $dest): ?>
                        <div class="option" data-id="<?php echo (int)$dest->id; ?>">
                            <?php echo htmlspecialchars($dest->title, ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$dest->package_count; ?>)
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <button class="custom-button" id="start-date"><?php echo !empty($startDate) ? htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') : 'Select Date'; ?></button>
            <input class="clender" type="hidden" id="start-date-value" value="<?php echo htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
     <div class="form-group">
    <button class="custom-button" id="rooms-guests">
        <i class="fas fa-bed"></i> <?php echo (int)$rooms; ?> Room<?php echo $rooms > 1 ? 's' : ''; ?>
        <i class="fas fa-user"></i> <?php echo (int)$adults; ?> Guest<?php echo $adults > 1 ? 's' : ''; ?>
        <?php if ($children > 0): ?>
            <i class="fas fa-child"></i> <?php echo (int)$children; ?> Child<?php echo $children > 1 ? 'ren' : ''; ?>
        <?php endif; ?>
    </button>
    <div class="dropdown rooms-guests-dropdown" id="rooms-guests-dropdown">
        <div class="guest-limit-notice">
            <i class="fas fa-info-circle"></i> Maximum 4 guests allowed per room
        </div>
        
        <div id="room-sections-container">
            <div class="room-section" data-room-id="1">
                <div class="room-header">
                    <h4><i class="fas fa-bed"></i> ROOM 1</h4>
                    <button class="remove-room-btn" onclick="removeRoom(1)" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="guest-types">
                    <div class="guest-type">
                        <label><i class="fas fa-user"></i> Adults <br>(12+ yrs)</label>
                        <div class="counter">
                            <button type="button" onclick="updateCounter(this, 'adults', -1, 1)" <?php echo $adults <= 1 ? 'disabled' : ''; ?>>
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="count-display">1</span>
                            <button type="button" onclick="updateCounter(this, 'adults', 1, 1)" <?php echo $adults >= 4 ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="guest-type">
                        <label><i class="fas fa-child"></i> Child <br>(0-11 yrs)</label>
                        <div class="counter">
                            <button type="button" onclick="updateCounter(this, 'children', -1, 1)" <?php echo $children <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="count-display">0</span>
                            <button type="button" onclick="updateCounter(this, 'children', 1, 1)" <?php echo ($adults + $children) >= 4 ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="child-age-section" style="display: none;">
                    <div class="age-header">
                        <label>Child Ages</label>
                        <span class="help-text">Age on last travel day</span>
                    </div>
                    <div class="age-selectors-container">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="room-actions">
            <button type="button" class="add-room-btn" onclick="addRoom()">
                <i class="fas fa-plus"></i> ADD ANOTHER ROOM
            </button>
            <button type="button" class="apply-btn" onclick="applyRoomsGuests()">
                <i class="fas fa-check"></i> APPLY
            </button>
        </div>
    </div>
</div> 
        <button type="button" class="btn" id="search-btn">Search</button>
        <button type="button" class="btn explore-btn" id="explore-btn">CLEAR</button>
    </div>
<div class="hero-image-container">
    <img src="<?php echo htmlspecialchars($fullImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
         alt="Destination Image"
         onerror="this.onerror=null;this.src='http://localhost/tourism_joomla/images/carousel-2.jpg';"
         class="hero-image">

    <div class="hero-image-title">
        <?php echo !empty($destinationTitle) ? htmlspecialchars($destinationTitle, ENT_QUOTES, 'UTF-8') : 'Popular Packages'; ?>
    </div>
</div>

<div class="package-wrapper">
    <!-- Selected Filters Display -->
    <div class="selected-filters-wrapper" id="selected-filters-display" style="<?php echo $selectedFiltersPresent ? 'display: flex;' : 'display: none;'; ?>">
        <?php
        $actualSelectedFiltersCount = 0;
        if (!empty($currentFilters['duration_range'])) {
            echo '<div class="selected-filter-tag">Duration: ' . htmlspecialchars($currentFilters['duration_range'] . 'N', ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="duration_range" data-value="' . htmlspecialchars($currentFilters['duration_range'], ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        if (!empty($currentFilters['flights'])) {
            echo '<div class="selected-filter-tag">Flights: ' . htmlspecialchars(ucfirst($currentFilters['flights']), ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="flights" data-value="' . htmlspecialchars($currentFilters['flights'], ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        if ($currentFilters['min_price'] > $minPrice || $currentFilters['max_price'] < $maxPrice) {
            echo '<div class="selected-filter-tag">Budget: ₹' . number_format($currentFilters['min_price']) . ' - ₹' . number_format($currentFilters['max_price']) . '<button type="button" class="clear-tag" data-filter="budget" data-value="' . $currentFilters['min_price'] . '-' . $currentFilters['max_price'] . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        foreach ($currentFilters['hotel_category'] as $category) {
            echo '<div class="selected-filter-tag">Hotel: ' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="hotel_category" data-value="' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        foreach ($currentFilters['cities'] as $city) {
            echo '<div class="selected-filter-tag">City: ' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="cities" data-value="' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        foreach ($currentFilters['package_type'] as $type) {
            echo '<div class="selected-filter-tag">Type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="package_type" data-value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        foreach ($currentFilters['special_package'] as $sp_type) {
            echo '<div class="selected-filter-tag">Special: ' . htmlspecialchars($sp_type, ENT_QUOTES, 'UTF-8') . '<button type="button" class="clear-tag" data-filter="special_package" data-value="' . htmlspecialchars($sp_type, ENT_QUOTES, 'UTF-8') . '">×</button></div>';
            $actualSelectedFiltersCount++;
        }
        if ($actualSelectedFiltersCount > 0):
        ?>
            <span class="clear-all-filters" id="clear-all-filters">Clear All</span>
        <?php endif; ?>
    </div>

    <div class="clearfix">
        <!-- Filter Section -->
        
            <h5>FILTERS</h5>
<div class="filter-section">
            <!-- Duration Filter -->
            <div class="filter-card">
                <div class="form-group">
                    <label for="duration-range-slider">Duration (in Nights)</label>
                    <div class="range-wrapper">
                        <input type="range" id="duration-range-slider" 
                               min="<?php echo (int)$minDuration; ?>" 
                               max="<?php echo (int)$maxDuration; ?>" 
                               value="<?php echo $currentDurationValue > $maxDuration ? (int)$maxDuration : (int)$currentDurationValue; ?>">
                        <span class="range-display" id="duration-range-display">
                            1-<?php echo $currentDurationValue > $maxDuration ? (int)$maxDuration : (int)$currentDurationValue; ?>N
                        </span>
                    </div>
                </div>
            </div>

            <!-- Flights Filter -->
            <div class="filter-card">
                <div class="form-group">
                    <label>Flights</label>
                    <div class="checkbox-group">
                        <div class="checkbox">
                            <input type="checkbox" id="with-flight" name="flights" value="with" <?php echo ($currentFilters['flights'] === 'with') ? 'checked' : ''; ?>>
                            <label for="with-flight">With Flight (<?php echo (int)($filterOptions->flights['with'] ?? 0); ?>)</label>
                        </div>
                        <div class="checkbox">
                            <input type="checkbox" id="without-flight" name="flights" value="without" <?php echo ($currentFilters['flights'] === 'without') ? 'checked' : ''; ?>>
                            <label for="without-flight">Without Flight (<?php echo (int)($filterOptions->flights['without'] ?? 0); ?>)</label>
                        </div>
                    </div>
                </div>
            </div>

<div class="filter-card">
    <div class="form-group budget-filter">
        <label>Budget (per person)</label>
        <div class="range-wrapper dual-range">
            <div class="slider-track"></div>
            <input type="range" id="min-price-slider" name="min_price" min="<?php echo (int)$minPrice; ?>" max="<?php echo (int)$maxPrice; ?>" value="<?php echo (int)$currentFilters['min_price']; ?>">
            <input type="range" id="max-price-slider" name="max_price" min="<?php echo (int)$minPrice; ?>" max="<?php echo (int)$maxPrice; ?>" value="<?php echo (int)$currentFilters['max_price']; ?>">
        </div>
        <div class="range-values">
            <span id="min-price-display">₹<?php echo number_format($currentFilters['min_price']); ?></span>
            <span>-</span>
            <span id="max-price-display">₹<?php echo number_format($currentFilters['max_price']); ?></span>
        </div>
    </div>
</div>
            <!-- Hotel Category Filter -->
            <div class="filter-card">
                <div class="form-group">
                    <label>Hotel Category</label>
                    <div class="checkbox-group">
                        <?php
                        $hotelCategories = $filterOptions->hotelCategories ?? [];
                        if (!empty($hotelCategories)):
                            $orderedCategories = ['3*', '4*', '5*'];
                            foreach ($orderedCategories as $cat):
                                $count = isset($hotelCategories[$cat]) ? (int)$hotelCategories[$cat] : 0;
                                $id_friendly_cat = str_replace(['<', '*'], ['less-', 'star'], $cat);
                        ?>
                        <div class="checkbox">
                            <input type="checkbox" id="hotel-<?php echo htmlspecialchars($id_friendly_cat, ENT_QUOTES, 'UTF-8'); ?>" 
                                   name="hotel_category[]" 
                                   value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                                   <?php echo in_array($cat, $currentFilters['hotel_category']) ? 'checked' : ''; ?>
                                   data-filter="hotel_category">
                            <label for="hotel-<?php echo htmlspecialchars($id_friendly_cat, ENT_QUOTES, 'UTF-8'); ?>" style="color: green;">
                                <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$count; ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hotel categories available for this destination.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cities Filter -->
            <div class="filter-card">
                <div class="form-group">
                    <label>Cities</label>
                    <div class="checkbox-group cities">
                        <?php if (!empty($filterOptions->cities)): ?>
                            <?php foreach ($filterOptions->cities as $city): ?>
                                <div class="checkbox">
                                    <input type="checkbox" id="city-<?php echo htmlspecialchars(str_replace(' ', '-', $city), ENT_QUOTES, 'UTF-8'); ?>" 
                                           name="cities[]" 
                                           value="<?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>" 
                                           <?php echo in_array($city, $currentFilters['cities']) ? 'checked' : ''; ?>>
                                    <label for="city-<?php echo htmlspecialchars(str_replace(' ', '-', $city), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No cities available for this destination.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Package Type Filter -->
            <div class="filter-card">
                <div class="form-group">
                    <label>Package Type</label>
                    <div class="checkbox-group">
                        <?php if (!empty($filterOptions->packageTypes)): ?>
                            <?php foreach ($filterOptions->packageTypes as $type => $count): ?>
                                <div class="checkbox">
                                    <input type="checkbox" id="package-type-<?php echo htmlspecialchars(str_replace(' ', '-', $type), ENT_QUOTES, 'UTF-8'); ?>" 
                                           name="package_type[]" 
                                           value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" 
                                           <?php echo in_array($type, $currentFilters['package_type']) ? 'checked' : ''; ?>>
                                    <label for="package-type-<?php echo htmlspecialchars(str_replace(' ', '-', $type), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$count; ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No package types available for this destination.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Content -->
        <div class="package-content-wrapper">
            <!-- Tabs Section -->
            <div class="tabs-section" id="tabs-section">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentFilters['tab_filter'] === 'all') ? 'active' : ''; ?>" 
                           href="#" data-tab-filter="all">
                            All Packages <span class="badge"><?php echo (int)($packageCounts->all_packages ?? 0); ?></span>
                        </a>
                    </li>
                    <?php
                    $shownPackages = [];
                    foreach ($specialPackages as $package) {
                        if (!in_array($package, $shownPackages)) {
                            $shownPackages[] = $package;
                            $tabFilter = strtolower(str_replace(' ', '_', $package));
                            $query = $db->getQuery(true)
                                ->select('COUNT(*)')
                                ->from($db->quoteName('n4gvg__holiday_packages'))
                                ->where($db->quoteName('special_package') . ' LIKE ' . $db->quote('%"detail":"' . $db->escape($package) . '"%'))
                                ->where($db->quoteName('destination_id') . ' = ' . (int)$destinationId)
                                ->where($db->quoteName('published') . ' = 1');
                            $db->setQuery($query);
                            $count = $db->loadResult();
                            if ($count > 0) {
                    ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo ($currentFilters['tab_filter'] === $tabFilter) ? 'active' : ''; ?>"
                                       href="#" data-tab-filter="<?php echo htmlspecialchars($tabFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($package, ENT_QUOTES, 'UTF-8'); ?> <span class="badge"><?php echo (int)$count; ?></span>
                                    </a>
                                </li>
                    <?php
                            }
                        }
                    }
                    ?>
                </ul>
            </div>

            <!-- Sort By -->
            <div class="sort-by-container">
                <label for="sort-by-select">Sorted By:</label>
                <select id="sort-by-select">
                    <option value="popular" <?php echo ($currentFilters['sort'] === 'popular') ? 'selected' : ''; ?>>Popular</option>
                    <option value="price_low_high" <?php echo ($currentFilters['sort'] === 'price_low_high') ? 'selected' : ''; ?>>Price - Low to High</option>
                    <option value="price_high_low" <?php echo ($currentFilters['sort'] === 'price_high_low') ? 'selected' : ''; ?>>Price - High to Low</option>
                    <option value="duration_low_high" <?php echo ($currentFilters['sort'] === 'duration_low_high') ? 'selected' : ''; ?>>Duration - Low to High</option>
                    <option value="duration_high_low" <?php echo ($currentFilters['sort'] === 'duration_high_low') ? 'selected' : ''; ?>>Duration - High to Low</option>
                </select>
            </div>

            <!-- Package Cards -->
            <?php if (empty($this->items)): ?>
                <div class="no-packages-message">
                    Packages not available for the selected criteria. Please try adjusting your filters or selecting another destination.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($this->items as $item): ?>
                        <?php
                        // Process features using the model
                        $features = $model->processSectionTypes($item->section_types ?? '', $item);
                        $features = array_values(array_unique(array_filter(array_map('trim', $features))));

                        // Extract special package details for this item
                        $itemSpecialPackages = [];
                        $decoded = json_decode($item->special_package ?? '', true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            foreach ($decoded as $key => $value) {
                                if (isset($value['special_package']['detail'])) {
                                    $itemSpecialPackages[] = $value['special_package']['detail'];
                                }
                            }
                        }

                        // Check if this item matches the selected tab filter
                        $showItem = ($currentFilters['tab_filter'] === 'all');
                        if (!$showItem && !empty($currentFilters['tab_filter'])) {
                            $tabFilterName = str_replace('_', ' ', $currentFilters['tab_filter']);
                            $showItem = in_array($tabFilterName, $itemSpecialPackages);
                        }

                        if ($showItem) {
                            $query = $db->getQuery(true)
                                ->select($db->quoteName(['place_name']))
                                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                                ->where($db->quoteName('package_id') . ' = ' . (int)$item->id)
                                ->order('day_number ASC');
                            $db->setQuery($query);
                            $itineraries = $db->loadColumn();
                            $placeNights = array_count_values($itineraries);
                            $placeNightsString = '';
                            foreach ($placeNights as $place => $nights) {
                                if ($placeNightsString) $placeNightsString .= ' • ';
                                $placeNightsString .= $nights . 'N ' . htmlspecialchars($place, ENT_QUOTES, 'UTF-8');
                            }
                            $days = isset($packageND[$item->id]) ? $packageND[$item->id][1] : (int)$item->duration;
                            $nights = isset($packageND[$item->id]) ? $packageND[$item->id][0] : ($days > 0 ? $days - 1 : 0);
                        ?>
                            <div class="col-md-6 col-sm-12">
                                <div class="package-card">
                                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=details&id=' . (int)$item->id); ?>">
                                        <img src="<?php echo Uri::root() . htmlspecialchars($item->image ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                             onerror="this.onerror=null;this.src='https://placehold.co/400x220/E0E0E0/333333?text=No+Image';"
                                             alt="<?php echo htmlspecialchars($item->title ?? 'Package Image', ENT_QUOTES, 'UTF-8'); ?>">
                                    </a>
                                   
                                    <div class="package-content">
                                        <div class="header-content">
                                            <div>
                                                <h4><?php echo htmlspecialchars($item->title ?? 'Package Title', ENT_QUOTES, 'UTF-8'); ?></h4>
                                                <div class="destination-breakdown">
                                                    <?php echo htmlspecialchars($placeNightsString ?: 'No itinerary available', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </div>
                                            <div class="duration-tag"><?php echo (int)$nights; ?>N/<?php echo (int)$days; ?>D</div>
                                        </div>
                                        <ul class="package-features">
                                            <?php if (!empty($features)): ?>
                                                <?php foreach ($features as $feature): ?>
                                                    <li><?php echo htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li>No package details found</li>
                                            <?php endif; ?>
                                        </ul>
                                        <?php
                                        $query = $db->getQuery(true)
                                            ->select($db->quoteName('all_sections'))
                                            ->from($db->quoteName('n4gvg__holiday_itineraries'))
                                            ->where($db->quoteName('package_id') . ' = ' . (int)$item->id)
                                            ->where($db->quoteName('all_sections') . ' IS NOT NULL')
                                            ->where($db->quoteName('all_sections') . ' != ""');
                                        $db->setQuery($query);
                                        $allSectionsRows = $db->loadColumn();
                                        $activityHeadings = [];
                                        foreach ($allSectionsRows as $json) {
                                            $decoded = json_decode($json, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                uksort($decoded, 'strnatcmp');
                                                foreach ($decoded as $sectionBlock) {
                                                    $section = $sectionBlock['all_sections'] ?? null;
                                                    if (is_array($section) && isset($section['section_type']) && $section['section_type'] === 'Activities') {
                                                        if (!empty($section['heading'])) {
                                                            $activityHeadings[] = $section['heading'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $activityHeadings = array_slice(array_unique($activityHeadings), 0, 4);
                                        if (!empty($activityHeadings)): ?>
                                            <div class="package-activities">
                                                <ul class="activity-list">
                                                    <?php foreach ($activityHeadings as $activity): ?>
                                                        <li>
                                                            <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=details&id=' . (int)$item->id); ?>" 
                                                               class="activity-link">
                                                                <?php echo htmlspecialchars($activity, ENT_QUOTES, 'UTF-8'); ?>
                                                                <svg class="activity-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <div class="package-price-info">
                                            <span class="discount-text">This price is lower than the average price in month</span>
                                            <div class="price-container">
                                                <?php if (!empty($item->price_per_person)): ?>
                                                    <span class="actual-price">₹<?php echo number_format((float)$item->price_per_person, 0, '.', ','); ?></span>
                                                    <span class="per-person">/Person</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item->price_per_person)): ?>
                                                <span class="total-price-text">Total Price ₹<?php echo number_format((float)$item->price_per_person * 2, 0, '.', ','); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="popup-overlay" id="customize-popup" style="display: none;">
        <div class="popup-content" id="form-popup">
<button class="close-button" onclick="document.getElementById('customize-popup').style.display='none'">
    <i class="fas fa-times"></i>
</button>     
   <div class="popup-header">
    <div class="header-icon">
        <i class="fas fa-envelope-open-text fa-2x"></i> <!-- Added fa-2x for larger size -->
    </div>
    <div class="header-text">
        <h3>Get a quote</h3>
        <p>Please share your details below and our holiday expert will get in touch with you.</p>
    </div>
</div>
            {convertforms 2}
        </div>
    </div>
    <!-- Customize Button -->
   <button type="button" class="customize-btn" id="customize-btn">
            <span class="icon"><i class="fas fa-plane"></i></span> Customise my trip
        </button>
</div>

<script>
    window.packageSettings = {
        initialDestinationId: <?php echo (int)$destinationId; ?>,
        baseUrl: '<?php echo Route::_('index.php?option=com_holidaypackages&view=packages'); ?>',
        maxPrice: <?php echo (int)$maxPrice; ?>,
        minPrice: <?php echo (int)$minPrice; ?>,
        minDuration: <?php echo (int)$minDuration; ?>,
        maxDuration: <?php echo (int)$maxDuration; ?>,
        initialParams: {
            starting_from: '<?php echo addslashes($startingFrom); ?>',
            id: '<?php echo (int)$destinationId; ?>',
            start_date: '<?php echo addslashes($startDate); ?>',
            rooms: '<?php echo (int)$rooms; ?>',
            adults: '<?php echo (int)$adults; ?>',
            children: '<?php echo (int)$children; ?>',
            duration_range: '<?php echo addslashes($currentFilters['duration_range']); ?>',
            flights: '<?php echo addslashes($currentFilters['flights']); ?>',
            min_price: <?php echo (int)$currentFilters['min_price']; ?>,
            max_price: <?php echo (int)$currentFilters['max_price']; ?>,
            sort: '<?php echo addslashes($currentFilters['sort']); ?>',
            tab_filter: '<?php echo addslashes($currentFilters['tab_filter']); ?>'
        }
    };
    document.addEventListener('DOMContentLoaded', function() {
        const customizeButton = document.getElementById('customize-btn');
        const popupOverlay = document.getElementById('customize-popup');
        const formPopup = document.getElementById('form-popup');

        customizeButton.addEventListener('click', function() {
            popupOverlay.style.display = 'block';
        });

        popupOverlay.addEventListener('click', function(e) {
            if (e.target === popupOverlay) {
                popupOverlay.style.display = 'none';
                formPopup.reset();
            }
        });

        document.getElementById('customize-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('<?php echo Route::_('index.php?option=com_holidaypackages&task=saveCustomRequest'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Request submitted successfully!');
                    popupOverlay.style.display = 'none';
                    this.reset();
                } else {
                    alert('Error submitting request.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
</script>
<script src="<?php echo Uri::root(); ?>media/com_holidaypackages/js/packages.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>