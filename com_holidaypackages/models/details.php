<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text; // Make sure to include Text for JError messages

class HolidaypackagesModelDetails extends ItemModel
{
    /**
     * Processes the JSON from the 'all_sections' field into a single ordered list.
     *
     * @param string $json The JSON string from the database.
     * @return array A flat, ordered list of section items.
     */
    private function processSectionsIntoFlatList($json)
    {
        $sections = [];
        if (empty($json)) {
            return $sections;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return $sections;
        }

        // Sort by key (e.g., "all_sections0", "all_sections1") to guarantee order
        uksort($data, 'strnatcmp');

        foreach ($data as $parentKey => $value) {
            if (strpos($parentKey, 'all_sections') === 0 && isset($value['all_sections'])) {
                $section = $value['all_sections'];

                // Process multi_images to flatten it into a simple array of image paths
                if (isset($section['multi_images']) && is_array($section['multi_images'])) {
                    $images = [];
                    foreach ($section['multi_images'] as $imageKey => $imageValue) {
                        if (isset($imageValue['multi_images']['image'])) {
                            $images[] = $imageValue['multi_images']['image'];
                        }
                    }
                    $section['multi_images'] = $images; // Replace the complex structure with a simple array
                } else {
                    $section['multi_images'] = []; // Ensure it's always an array, even if empty
                }

                $sections[] = $section;
            }
        }
        return $sections;
    }

    public function getItem($pk = null)
    {
        $db = $this->getDbo();
        $app = Factory::getApplication();
        $packageId = $pk ?: $app->input->getInt('id');

        if (!$packageId) {
            return null;
        }

        // Fetch package info
        $packageQuery = $db->getQuery(true)
            ->select(['id', 'title', 'image', 'price_per_person'])
            ->from($db->quoteName('n4gvg__holiday_packages'))
            ->where('id = ' . (int)$packageId);
        $db->setQuery($packageQuery);
        $package = $db->loadObject();

        if (!$package) {
            return null;
        }

        // Initialize price variables directly from package object
        $pricePerPerson = (float) ($package->price_per_person ?? 0);
        $originalPrice = 0;
        $discountPercentage = 0;

        // Calculate original price if a discount is applied (e.g., 6% as a dummy value)
        if ($pricePerPerson > 0) {
            $dummyDiscountRate = 0.06; // 6%
            $originalPrice = $pricePerPerson / (1 - $dummyDiscountRate);
            $discountPercentage = $dummyDiscountRate * 100;
        }

        // Fetch itinerary details
        $itineraryQuery = $db->getQuery(true)
            ->select(['day_number', 'date', 'place_name', 'all_sections'])
            ->from($db->quoteName('n4gvg__holiday_itineraries'))
            ->where('package_id = ' . (int)$packageId)
            ->where($db->quoteName('status') . ' = 1')
            ->order('day_number ASC');
        $db->setQuery($itineraryQuery);
        $itineraryDetails = $db->loadObjectList();

        foreach ($itineraryDetails as $i => $day) {
            $day->all_day_sections = $this->processSectionsIntoFlatList($day->all_sections ?? '');
            $day->transfer_sections = [];
            $day->sightseeing_sections = [];
            $day->resort_sections = [];
            $day->activity_sections = [];
            $day->meal_sections = [];

            $sectionTypeMap = [
                'Airport Transfers' => 'transfer',
                'Airport Pickup and Drop' => 'transfer',
                'Round Trip Flights' => 'transfer',
                'Intercity Car Transfers' => 'transfer',
                'Selected Meals' => 'meal',
                'resort' => 'hotel',
                'Star Hotels' => 'hotel',
                'Star Hotels and Houseboat' => 'hotel',
                'Sightseeing' => 'activity',
                'Activities' => 'activity',
                'others' => 'other'
            ];

            foreach ($day->all_day_sections as $section) {
                $rawType = $section['section_type'] ?? 'unknown';
                $normalizedType = $sectionTypeMap[$rawType] ?? 'unknown';

                // Ensure 'multi_images' is handled as an array in the PHP model before passing to view
                if (!isset($section['multi_images']) || !is_array($section['multi_images'])) {
                    $section['multi_images'] = [];
                }

                if ($normalizedType === 'transfer') {
                    $day->transfer_sections[] = ['transfer' => $section];
                } elseif ($normalizedType === 'hotel') {
                    $day->resort_sections[] = ['resort' => $section];
                } elseif ($normalizedType === 'activity') {
                    if (in_array($rawType, ['Sightseeing', 'sightseeing'])) {
                        $day->sightseeing_sections[] = ['sightseeing' => $section];
                    } else {
                        $day->activity_sections[] = ['activity' => $section];
                    }
                } elseif ($normalizedType === 'meal') {
                    $day->meal_sections[] = ['meal' => $section];
                }
            }
            $itineraryDetails[$i] = $day;
        }
// In your model (HolidaypackagesModelDetails.php), update the policies query part:
// Fetch policies
$policiesQuery = $db->getQuery(true)
    ->select($db->quoteName(['title', 'description']))
    ->from($db->quoteName('n4gvg__holiday_policies'))
    ->where($db->quoteName('package_id') . ' = ' . (int)$packageId)
    ->order($db->quoteName('id') . ' ASC');
$db->setQuery($policiesQuery);

try {
    $policies = $db->loadObjectList();
    
    // Debugging: Check what's actually being fetched
    if (JDEBUG) {
        JLog::add('Policies data: ' . print_r($policies, true), JLog::DEBUG, 'com_holidaypackages');
    }
} catch (Exception $e) {
    $policies = [];
    JFactory::getApplication()->enqueueMessage('Error loading policies: ' . $e->getMessage(), 'error');
}

        // Counts and place nights
        $counts = ['transfers' => 0, 'hotels' => 0, 'activities' => 0, 'meals' => 0];
        $placeNights = [];

        foreach ($itineraryDetails as $day) {
            $counts['transfers'] += count($day->transfer_sections);
            $counts['hotels'] += count($day->resort_sections);
            $counts['activities'] += count($day->activity_sections);
            $counts['meals'] += count($day->meal_sections);

            if (!empty($day->place_name)) {
                if (!isset($placeNights[$day->place_name])) {
                    $placeNights[$day->place_name] = 0;
                }
                $placeNights[$day->place_name]++;
            }
        }

        $placeNightsString = '';
        foreach ($placeNights as $place => $nights) {
            $placeNightsString .= ($placeNightsString ? ' - ' : '') . $nights . 'N ' . $place;
        }

        // Calculate total days and nights from itinerary details
        $totalDays = count($itineraryDetails);
        $totalNights = array_sum($placeNights);

        return [
            'package' => $package,
            'itineraryDetails' => $itineraryDetails,
            'policies' => $policies,
            'counts' => $counts,
            'placeNightsString' => $placeNightsString,
            'totalDays' => $totalDays,
            'totalNights' => $totalNights,
            'pricePerPerson' => $pricePerPerson,
            'originalPrice' => $originalPrice,
            'discountPercentage' => $discountPercentage
        ];
    }
}