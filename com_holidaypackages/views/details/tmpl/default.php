<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

// Load item data from the model's getItem() method
$item = $this->item ?? null;

$package = $item['package'] ?? null;
$itineraryDetails = $item['itineraryDetails'] ?? [];
$policies = $item['policies'] ?? [];
$counts = $item['counts'] ?? ['transfers' => 0, 'hotels' => 0, 'activities' => 0, 'meals' => 0];
$placeNightsString = $item['placeNightsString'] ?? '';
$totalDays = $item['totalDays'] ?? 0;
$totalNights = $item['totalNights'] ?? 0;

$pricePerPerson = $item['pricePerPerson'] ?? 0;
$originalPrice = $item['originalPrice'] ?? 0;
$discountPercentage = $item['discountPercentage'] ?? 0;

$summaryIconMap = [
    'Airport Transfers' => 'fas fa-plane',
    'Airport Pickup and Drop' => 'fas fa-shuttle-van',
    'Round Trip Flights' => 'fas fa-plane-departure',
    'Intercity Car Transfers' => 'fas fa-car',
    'Selected Meals' => 'fas fa-utensils',
    'resort' => 'fas fa-hotel',
    'Star Hotels' => 'fas fa-hotel',
    'Star Hotels and Houseboat' => 'fas fa-hotel',
    'Sightseeing' => 'fas fa-binoculars',
    'sightseeing' => 'fas fa-binoculars',
    'Activities' => 'fas fa-ticket-alt',
    'others' => 'fas fa-box'
];


HTMLHelper::stylesheet('com_holidaypackages/details.css', ['relative' => true, 'pathOnly' => false]);
?>

<div class="holiday-details-wrapper">
    <?php if (!$package) : ?>
        <div class="alert alert-error text-center">
            <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_PACKAGE_FOUND'); ?>
        </div>
    <?php else : ?>
        <div class="header-banner">
            <img src="<?php echo Uri::root() . htmlspecialchars($package->image ?? 'images/blog-1.jpg', ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo htmlspecialchars($package->title ?? 'Package', ENT_QUOTES, 'UTF-8'); ?>"
                 onerror="this.onerror=null; this.src='<?php echo Uri::root(); ?>images/fallback.jpg';">
            <div class="header-content">
                <h1><?php echo htmlspecialchars($package->title ?? 'Package Title', ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="duration"><?php echo (int)$totalDays; ?> Days <?php echo (int)$totalNights; ?> Nights</div>
                <div class="nights-breakdown"><?php echo htmlspecialchars($placeNightsString ?: 'No itinerary available', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="nav-buttons">
            <button class="nav-btn active" onclick="showSection('itinerary')"><?php echo Text::_('COM_HOLIDAYPACKAGES_ITINERARY'); ?></button>
            <button class="nav-btn" onclick="showSection('summary')"><?php echo Text::_('COM_HOLIDAYPACKAGES_SUMMARY'); ?></button>
            <button class="nav-btn" onclick="showSection('policies')"><?php echo Text::_('COM_HOLIDAYPACKAGES_POLICIES'); ?></button>
        </div>

        <div class="content-and-sidebar-wrapper">
            <div class="main-content-area">
                <div id="itinerary-section" class="content-section active">
                    <?php if (!empty($itineraryDetails)) : ?>
                        <div class="day-plan">
                            <h4><?php echo Text::_('COM_HOLIDAYPACKAGES_DAY_PLAN'); ?></h4>
                            <ul>
                                <?php
                                $sortedItinerary = $itineraryDetails;
                                usort($sortedItinerary, function($a, $b) {
                                    $dayA = isset($a->day_number) ? (int)$a->day_number : 0;
                                    $dayB = isset($b->day_number) ? (int)$b->day_number : 0;
                                    return $dayA <=> $dayB;
                                });
                                ?>
                                <?php foreach ($sortedItinerary as $index => $day) : ?>
                                    <?php
                                    try {
                                        $date = new DateTime($day->date ?? 'now');
                                        $formattedDate = $date->format('d M');
                                        $dayName = $date->format('l');
                                    } catch (Exception $e) {
                                        $formattedDate = 'N/A';
                                        $dayName = 'N/A';
                                    }
                                    $activeClass = $index === 0 ? 'active' : '';
                                    ?>
                                    <li class="<?php echo $activeClass; ?>" onclick="showItinerary(<?php echo ($index + 1); ?>)">
                                        <div class="date-day">
                                            <span class="date"><?php echo htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="day"><?php echo htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <span class="place"><?php echo htmlspecialchars($day->place_name ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="detail-card">
                            <table class="summary-table">
                                <tr>
                                    <th class="filter active" data-filter="all" onclick="filterContent('all')"><?php echo (int)$totalDays; ?> <?php echo Text::_('COM_HOLIDAYPACKAGES_DAY_PLAN'); ?></th>
                                    <th class="filter" data-filter="transfers" onclick="filterContent('transfers')"><?php echo (int)$counts['transfers']; ?> <?php echo Text::_('COM_HOLIDAYPACKAGES_TRANSFERS'); ?></th>
                                    <th class="filter" data-filter="hotels" onclick="filterContent('hotels')"><?php echo (int)$counts['hotels']; ?> <?php echo Text::_('COM_HOLIDAYPACKAGES_HOTELS'); ?></th>
                                    <th class="filter" data-filter="activities" onclick="filterContent('activities')"><?php echo (int)$counts['activities']; ?> <?php echo Text::_('COM_HOLIDAYPACKAGES_ACTIVITIES'); ?></th>
                                </tr>
                            </table>
                            <div class="itinerary-content" id="itinerary-content-display"></div>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITINERARY_DETAILS_FOUND'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="summary-section" class="content-section">
                    <?php if (!empty($itineraryDetails)) : ?>
                        <div class="summary-section">
                            <?php
                            $groupedByPlace = [];
                            foreach ($itineraryDetails as $day) {
                                $placeName = $day->place_name ?? 'Unknown';
                                $groupedByPlace[$placeName][] = $day;
                            }

                            foreach ($groupedByPlace as $place => $days) :
                                $nightCount = count($days);
                                ?>
                                <div class="summary-place-card">
                                    <h3><?php echo htmlspecialchars($place, ENT_QUOTES, 'UTF-8'); ?> - <?php echo (int)$nightCount; ?> <?php echo Text::_('COM_HOLIDAYPACKAGES_NIGHT' . ($nightCount > 1 ? 'S' : '')); ?></h3>
                                    <?php foreach ($days as $day) : ?>
                                        <?php
                                        try {
                                            $date = new DateTime($day->date ?? 'now');
                                            $formattedDate = $date->format('M d, D');
                                        } catch (Exception $e) {
                                            $formattedDate = 'N/A';
                                        }
                                        ?>
                                       <div class="summary-day-card">
                                        <div class="summary-day-header">
                                            <h4><?php echo Text::sprintf('COM_HOLIDAYPACKAGES_DAY_HEADER', (int)$day->day_number, $formattedDate); ?></h4>
                                        </div>
                                        <?php
                                        $decodeSection = function($section) {
                                            return is_array($section) ? $section : (json_decode($section, true) ?: []);
                                        };

                                        $resortData = $decodeSection($day->resort_sections ?? []);
                                        foreach ($resortData as $entry) {
                                            $item = $entry['resort'] ?? null;
                                            if ($item && !empty($item['heading'])) {
                                                $iconClass = $summaryIconMap[$item['section_type'] ?? 'resort'] ?? 'fas fa-hotel';
                                                echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . Text::sprintf('COM_HOLIDAYPACKAGES_CHECKIN', htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8')) . '</div>';
                                            }
                                        }

                                        $transferData = $decodeSection($day->transfer_sections ?? []);
                                        foreach ($transferData as $entry) {
                                            $item = $entry['transfer'] ?? null;
                                            if ($item && !empty($item['heading'])) {
                                                $iconClass = $summaryIconMap[$item['section_type'] ?? 'Intercity Car Transfers'] ?? 'fas fa-car';
                                                echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8') . '</div>';
                                            }
                                        }

                                        $sightseeingData = $decodeSection($day->sightseeing_sections ?? []);
                                        foreach ($sightseeingData as $entry) {
                                            $item = $entry['sightseeing'] ?? null;
                                            if ($item && !empty($item['heading'])) {
                                                $iconClass = $summaryIconMap[$item['section_type'] ?? 'Sightseeing'] ?? 'fas fa-binoculars';
                                                echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8') . '</div>';
                                            }
                                        }

                                        $activityData = $decodeSection($day->activity_sections ?? []);
                                        foreach ($activityData as $entry) {
                                            $item = $entry['activity'] ?? null;
                                            if ($item && !empty($item['heading'])) {
                                                $iconClass = $summaryIconMap[$item['section_type'] ?? 'Activities'] ?? 'fas fa-ticket-alt';
                                                echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8') . '</div>';
                                            }
                                        }

                                        // Render meals
                                        $mealData = $decodeSection($day->meal_sections ?? []);
                                        foreach ($mealData as $entry) {
                                            $item = $entry['meal'] ?? null;
                                            if ($item && !empty($item['heading'])) {
                                                $iconClass = $summaryIconMap[$item['section_type'] ?? 'Selected Meals'] ?? 'fas fa-utensils';
                                                echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8') . '</div>';
                                            }
                                        }

                                        
                                        $maxDay = max(array_column($itineraryDetails, 'day_number') ?: [0]);
                                        if ($day->day_number == $maxDay) {
                                            foreach ($resortData as $entry) {
                                                $item = $entry['resort'] ?? null;
                                                if ($item && !empty($item['heading'])) {
                                                    $iconClass = $summaryIconMap[$item['section_type'] ?? 'resort'] ?? 'fas fa-hotel';
                                                    echo '<div class="activity-item"><span class="activity-icon"><i class="' . $iconClass . '"></i></span> ' . Text::sprintf('COM_HOLIDAYPACKAGES_CHECKOUT', htmlspecialchars($item['heading'], ENT_QUOTES, 'UTF-8')) . '</div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_SUMMARY_DETAILS_FOUND'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="policies-section" class="content-section">
                    <?php if (!empty($policies)) : ?>
                        <div class="policies-section">
                            <h4><?php echo Text::_('COM_HOLIDAYPACKAGES_PACKAGE_POLICIES'); ?></h4>
                            <?php foreach ($policies as $index => $policy) : ?>
                                <div class="policy-item">
                                    <div class="policy-header" data-index="<?php echo (int)$index; ?>">
                                        <h5><?php echo htmlspecialchars($policy->title ?? 'Policy', ENT_QUOTES, 'UTF-8'); ?></h5>
                                        <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                                    </div>
                                    <div class="policy-content">
                                        <?php if (!empty($policy->description)) : ?>
                                            <div class="policy-text">
                                                <?php echo nl2br(htmlspecialchars($policy->description, ENT_QUOTES, 'UTF-8')); ?>
                                            </div>
                                        <?php else : ?>
                                            <p class="no-description"><?php echo Text::_('COM_HOLIDAYPACKAGES_NO_DESCRIPTION_AVAILABLE'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_POLICIES_FOUND'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="payment-sidebar">
                <div class="price-details">
                    <?php if ($originalPrice > 0 && $originalPrice > $pricePerPerson) : ?>
                        <div class="old-price">₹<?php echo number_format($originalPrice, 0, '.', ','); ?></div>
                        <div class="discount"><?php echo number_format($discountPercentage, 0); ?>% OFF</div>
                    <?php endif; ?>
                    <div class="current-price">₹<?php echo number_format($pricePerPerson, 0, '.', ','); ?> <small>/Adult</small></div>
                    <div class="price-note">Excluding applicable taxes</div>
                </div>
                <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=payment'); ?>" class="payment-btn">
                    <?php echo Text::_('COM_HOLIDAYPACKAGES_PROCEED_TO_PAYMENT'); ?>
                </a>
            </div>
        </div>
        <div class="popup-overlay" id="customize-popup" style="display: none;">
            <div class="popup-content" id="form-popup">
                <button class="close-button" onclick="document.getElementById('customize-popup').style.display='none'"><i class="fas fa-times"></i></button>

          <div class="popup-header">
    <div class="header-icon">
        <i class="fas fa-envelope-open-text"></i> 
    </div>
    <div class="header-text">
        <h3>Get a quote</h3>
        <p>Please share your details below and our holiday expert will get in touch with you.</p>
    </div>
</div>

                {convertforms 2}
            </div>
        </div>

        <button type="button" class="customize-btn" id="customize-btn">
            <span class="icon"><i class="fas fa-plane"></i></span> Customise my trip
        </button>
    <?php endif; ?>

    <div id="js-data-container"
         data-itinerary='<?php echo json_encode($itineraryDetails, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>'
         data-root-url='<?php echo Uri::root(); ?>'
         data-translations='<?php echo json_encode([
             'PLACE' => Text::_('COM_HOLIDAYPACKAGES_PLACE'),
             'DURATION' => Text::_('COM_HOLIDAYPACKAGES_DURATION'),
             'PLACE_COVERED' => Text::_('COM_HOLIDAYPACKAGES_PLACE_COVERED'),
             'DAY_PLAN' => Text::_('COM_HOLIDAYPACKAGES_DAY_PLAN'),
             'TRANSFERS' => Text::_('COM_HOLIDAYPACKAGES_TRANSFERS'),
             'HOTELS' => Text::_('COM_HOLIDAYPACKAGES_HOTELS'),
             'ACTIVITIES' => Text::_('COM_HOLIDAYPACKAGES_ACTIVITIES'),
             'MEALS' => Text::_('COM_HOLIDAYPACKAGES_MEALS'),
             'ACTIVITY' => Text::_('COM_HOLIDAYPACKAGES_ACTIVITY'),
             'CHECKIN' => Text::_('COM_HOLIDAYPACKAGES_CHECKIN'),
             'CHECKOUT' => Text::_('COM_HOLIDAYPACKAGES_CHECKOUT')
         ]); ?>'>
    </div>
</div>
<script src="<?php echo Uri::root(); ?>media/com_holidaypackages/js/holiday-details.js"></script>
