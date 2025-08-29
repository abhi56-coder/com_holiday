<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelPackages extends ListModel
{
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->ensureTableColumns();
    }

    protected function ensureTableColumns()
    {
        $db = $this->getDbo();
        $table = 'n4gvg__holiday_packages';

        $columnsToAdd = [
            ['name' => 'duration', 'type' => 'INT DEFAULT 0', 'after' => 'published'],
            ['name' => 'flight_included', 'type' => 'TINYINT(1) DEFAULT 0', 'after' => 'duration'],
            ['name' => 'price', 'type' => 'DECIMAL(10,2) DEFAULT 0.00', 'after' => 'flight_included'],
            ['name' => 'original_price', 'type' => 'DECIMAL(10,2) DEFAULT 0.00', 'after' => 'price'],
            ['name' => 'hotel_category', 'type' => 'VARCHAR(50) DEFAULT NULL', 'after' => 'original_price'],
            ['name' => 'package_type', 'type' => 'VARCHAR(50) DEFAULT NULL', 'after' => 'hotel_category'],
            ['name' => 'cities_covered', 'type' => 'VARCHAR(255) DEFAULT NULL', 'after' => 'package_type'],
            ['name' => 'hotel_name', 'type' => 'VARCHAR(100) DEFAULT NULL', 'after' => 'cities_covered'],
            ['name' => 'activities_count', 'type' => 'INT DEFAULT 0', 'after' => 'hotel_name'],
        ];

        $existingColumns = [];
        $query = "SHOW COLUMNS FROM " . $db->quoteName($table);
        $db->setQuery($query);
        $columns = $db->loadObjectList();
        foreach ($columns as $column) {
            $existingColumns[] = $column->Field;
        }

        $lastExistingColumn = 'published';
        foreach ($columnsToAdd as $column) {
            if (!in_array($column['name'], $existingColumns)) {
                $query = "ALTER TABLE " . $db->quoteName($table) . " ADD COLUMN " . $db->quoteName($column['name']) . " " . $column['type'] . " AFTER " . $db->quoteName($lastExistingColumn);
                try {
                    $db->setQuery($query);
                    $db->execute();
                } catch (Exception $e) {
                    Factory::getApplication()->enqueueMessage('Failed to add column ' . $column['name'] . ': ' . $e->getMessage(), 'warning');
                    continue;
                }
            }
            $lastExistingColumn = $column['name'];
        }
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $app = Factory::getApplication();
        $input = $app->input;

        $subQuery = $db->getQuery(true)
            ->select('GROUP_CONCAT(DISTINCT 
                COALESCE(
                    REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(all_sections, "$.*.all_sections.section_type")), \'"\', \'\'), \'[\', \'\'),
                    \'\'
                )
            )')
            ->from($db->quoteName('n4gvg__holiday_itineraries', 'i'))
            ->where($db->quoteName('i.package_id') . ' = p.id')
            ->where($db->quoteName('i.all_sections') . ' IS NOT NULL')
            ->where($db->quoteName('i.status') . ' = 1');

        $query->select([
                'p.*', 
                'd.title as destination_title', 
                '(' . $subQuery . ') as section_types',
                'p.duration', 
                'p.price', 
                'p.original_price', 
                'p.hotel_category',
                'p.flight_included', 
                'p.package_type', 
                'p.cities_covered', 
                'p.hotel_name', 
                'p.activities_count',
                'p.special_package',
                'p.image'
            ])
            ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
            ->join('LEFT', $db->quoteName('n4gvg__holiday_destinations', 'd') . ' ON p.destination_id = d.id')
            ->where('p.published = 1');

        // Filter by destination ID if specified
        $urlId = $input->getInt('id', 0);
        $startingFrom = $input->getString('starting_from', '');
        if ($urlId) {
            $query->where('p.destination_id = ' . (int)$urlId);
        }
        if (!empty($startingFrom)) {
            $city = trim($startingFrom);
            $query->where('(' . 
                $db->quoteName('p.departure_city') . ' = ' . $db->quote($city) . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%, ' . $city . '%') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%' . $city . ', %') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%, ' . $city . ', %') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote($city . ',%') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%,' . $city . '%') . ')'
            );
        }

        // Apply additional filters and sorting
        $this->applyFilters($query, $input, $db);
        $this->applySorting($query, $input, $db);

        return $query;
    }

    public function getFilteredPackages($departureCity = '', $destinationId = 0)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('p.*')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1');

        if (!empty($departureCity)) {
            $city = trim($departureCity);
            $query->where('(' . 
                $db->quoteName('p.departure_city') . ' = ' . $db->quote($city) . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%, ' . $city . '%') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%' . $city . ', %') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%, ' . $city . ', %') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote($city . ',%') . ' OR ' .
                $db->quoteName('p.departure_city') . ' LIKE ' . $db->quote('%,' . $city . '%') . ')'
            );
        }
        
        if ($destinationId) {
            $query->where($db->quoteName('p.destination_id') . ' = ' . (int)$destinationId);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getAllDepartureCities()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        
        $query->select('DISTINCT departure_city')
              ->from($db->quoteName('n4gvg__holiday_packages'))
              ->where('published = 1');
        
        $db->setQuery($query);
        $results = $db->loadColumn();
        
        $cities = [];
        foreach ($results as $cityList) {
            $individualCities = array_map('trim', explode(',', $cityList));
            foreach ($individualCities as $city) {
                $cleanCity = trim($city);
                if (!empty($cleanCity) && !in_array($cleanCity, $cities)) {
                    $cities[] = $cleanCity;
                }
            }
        }
        
        sort($cities);
        return $cities;
    }

    
    public function updateItineraryDates($packages, $selectedDate)
    {
        $db = $this->getDbo();

        $baseDate = date_create_from_format('Y-m-d', date('Y-m-d', strtotime(str_replace('-', '/', $selectedDate))));

        foreach ($packages as $package) {
            $query = $db->getQuery(true)
                ->select(['id', 'day_number'])
                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                ->where($db->quoteName('package_id') . ' = ' . (int)$package->id)
                ->order('day_number ASC');

            $db->setQuery($query);
            $itineraries = $db->loadObjectList();

            foreach ($itineraries as $itinerary) {
                $updateDate = clone $baseDate;
                $interval = new DateInterval('P' . max(0, (int)$itinerary->day_number - 1) . 'D');
                $updateDate->add($interval);
                $formattedDate = $updateDate->format('Y-m-d');

                $updateQuery = $db->getQuery(true)
                    ->update($db->quoteName('n4gvg__holiday_itineraries'))
                    ->set($db->quoteName('date') . ' = ' . $db->quote($formattedDate))
                    ->where($db->quoteName('id') . ' = ' . (int)$itinerary->id);

                try {
                    $db->setQuery($updateQuery);
                    $db->execute();
                } catch (Exception $e) {
                    Factory::getApplication()->enqueueMessage('Error updating date for ID ' . $itinerary->id . ': ' . $e->getMessage(), 'error');
                }
            }
        }
    }

    protected function applyFilters($query, $input, $db) {
        $durationRange = $input->getString('duration_range', '');
        if (!empty($durationRange)) {
            list($minDuration, $maxDuration) = explode('-', $durationRange);
            
            $subQuery = $db->getQuery(true)
                ->select('DISTINCT package_id')
                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                ->group('package_id')
                ->having('MAX(day_number) BETWEEN ' . (int)$minDuration . ' AND ' . (int)$maxDuration);
                
            $query->where('p.id IN (' . (string)$subQuery . ')');
        }

        $flights = $input->getString('flights', '');
        if ($flights === 'with') {
            $query->where('p.flight_included = 1');
        } elseif ($flights === 'without') {
            $query->where('p.flight_included = 0');
        }

        $minPrice = $input->getInt('min_price', 0);
        $maxPrice = $input->getInt('max_price', 0);
        if ($minPrice > 0 || $maxPrice > 0) {
            if ($minPrice > 0) {
                $query->where('p.price_per_person >= ' . (int)$minPrice);
            }
            if ($maxPrice > 0) {
                $query->where('p.price_per_person <= ' . (int)$maxPrice);
            }
        }

        $hotelCategories = $input->get('hotel_category', [], 'array');
        if (!empty($hotelCategories)) {
            $hotelCategoryConditions = [];
            foreach ($hotelCategories as $category) {
                if ($category === '<3*') {
                    $hotelCategoryConditions[] = $db->quoteName('p.hotel_category') . ' < ' . $db->quote('3*');
                } else {
                    $hotelCategoryConditions[] = $db->quoteName('p.hotel_category') . ' = ' . $db->quote($category);
                }
            }
            $query->where('(' . implode(' OR ', $hotelCategoryConditions) . ')');
        }

        $cities = $input->get('cities', [], 'array');
        if (!empty($cities)) {
            $cityConditions = [];
            $subQuery = $db->getQuery(true)
                ->select('DISTINCT package_id')
                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                ->where('place_name IN (' . implode(',', array_map([$db, 'quote'], $cities)) . ')');
            $query->where('p.id IN (' . $subQuery . ')');
        }

        $packageTypes = $input->get('package_type', [], 'array');
        if (!empty($packageTypes)) {
            $packageTypeConditions = [];
            foreach ($packageTypes as $type) {
                $packageTypeConditions[] = $db->quoteName('p.package_type') . ' = ' . $db->quote($type);
            }
            $query->where('(' . implode(' OR ', $packageTypeConditions) . ')');
        }

        $specialPackages = $input->get('special_package', [], 'array');
        if (!empty($specialPackages)) {
            $specialPackageConditions = [];
            foreach ($specialPackages as $type) {
                $specialPackageConditions[] = $db->quoteName('p.special_package') . ' LIKE ' . $db->quote('%' . $db->escape($type) . '%');
            }
            $query->where('(' . implode(' OR ', $specialPackageConditions) . ')');
        }
    }

    protected function applySorting($query, $input, $db) {
        $sort = $input->getString('sort', 'popular');

        switch ($sort) {
            case 'price_low_high':
                $query->order('p.price_per_person ASC');
                break;

            case 'price_high_low':
                $query->order('p.price_per_person DESC');
                break;

            case 'duration_low_high':
            case 'duration_high_low':
                // Join the itineraries table
                $query->select('MAX(i.day_number) AS max_days')
                      ->join('LEFT', $db->quoteName('n4gvg__holiday_itineraries', 'i') . ' ON i.package_id = p.id AND i.status = 1')
                      ->group('p.id');

                // Order by max_days
                $orderDir = $sort === 'duration_low_high' ? 'ASC' : 'DESC';
                $query->order('max_days ' . $orderDir);
                break;

            case 'popular':
            default:
                $query->order('p.title ASC');
                break;
        }
    }

    public function getDestinationsWithCounts() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('d.id, d.title, COUNT(p.id) as package_count')
              ->from($db->quoteName('n4gvg__holiday_destinations', 'd'))
              ->join('LEFT', $db->quoteName('n4gvg__holiday_packages', 'p') . ' ON p.destination_id = d.id AND p.published = 1')
              ->where('d.published = 1')
              ->group('d.id, d.title')
              ->order('d.title ASC');

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getFilterOptions($destinationId = 0) {
        $db = $this->getDbo();
        $app = Factory::getApplication();
        $input = $app->input;

        $urlId = $input->getInt('id', 0);
        if ($urlId) {
            $destinationId = $urlId;
        } else {
            $paramDestinationId = $input->getInt('destination_id', 0);
            if ($paramDestinationId) {
                $destinationId = $paramDestinationId;
            }
        }

        $options = new stdClass();

        $query = $db->getQuery(true);
        $query->select('p.id, p.duration, p.flight_included, p.price_per_person, p.hotel_category, p.package_type, p.special_package')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1');

        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }

        $db->setQuery($query);
        $packages = $db->loadObjectList();

        $options->durations = [];
        $options->flights = ['with' => 0, 'without' => 0];
        $options->hotelCategories = [];
        $options->cities = [];
        $options->packageTypes = [];
        $options->specialPackages = [];

        $allPackageIds = [];
        foreach ($packages as $package) {
            $allPackageIds[] = (int) $package->id;

            if (!in_array($package->duration, $options->durations)) {
                $options->durations[] = $package->duration;
            }

            if ($package->flight_included == 1) {
                $options->flights['with']++;
            } else {
                $options->flights['without']++;
            }

            if (!empty($package->hotel_category)) {
                if (!isset($options->hotelCategories[$package->hotel_category])) {
                    $options->hotelCategories[$package->hotel_category] = 0;
                }
                $options->hotelCategories[$package->hotel_category]++;
            }

            if (!empty($package->package_type)) {
                if (!isset($options->packageTypes[$package->package_type])) {
                    $options->packageTypes[$package->package_type] = 0;
                }
                $options->packageTypes[$package->package_type]++;
            }

            if (!empty($package->special_package)) {
                if (!isset($options->specialPackages[$package->special_package])) {
                    $options->specialPackages[$package->special_package] = 0;
                }
                $options->specialPackages[$package->special_package]++;
            }
        }
        sort($options->durations);
        ksort($options->hotelCategories);

        if (!empty($allPackageIds)) {
            $query->clear()
                  ->select('DISTINCT i.place_name')
                  ->from($db->quoteName('n4gvg__holiday_itineraries', 'i'))
                  ->where($db->quoteName('i.package_id') . ' IN (' . implode(',', $allPackageIds) . ')');

            $db->setQuery($query);
            $citiesRaw = $db->loadColumn();
            $uniqueCities = [];
            foreach($citiesRaw as $cityList) {
                $individualCities = array_map('trim', explode(',', $cityList));
                foreach($individualCities as $city) {
                    if (!empty($city) && !in_array($city, $uniqueCities)) {
                        $uniqueCities[] = $city;
                    }
                }
            }
            sort($uniqueCities);
            $options->cities = $uniqueCities;
        }

        return $options;
    }

    public function getDurationRangeByDestination($destinationId)
    {
        $db = $this->getDbo();
        
        // First get all package IDs for this destination
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('n4gvg__holiday_packages'))
            ->where($db->quoteName('destination_id') . ' = ' . (int)$destinationId)
            ->where($db->quoteName('published') . ' = 1');
        
        $db->setQuery($query);
        $packageIds = $db->loadColumn();
        
        if (empty($packageIds)) {
            return ['min' => 1, 'max' => 10]; 
        }
        
        // Now get min and max day_number for these packages
        $query = $db->getQuery(true)
            ->select('MIN(day_number) AS min_duration, MAX(day_number) AS max_duration')
            ->from($db->quoteName('n4gvg__holiday_itineraries'))
            ->where($db->quoteName('package_id') . ' IN (' . implode(',', $packageIds) . ')')
            ->where($db->quoteName('status') . ' = 1');
        
        $db->setQuery($query);
        $result = $db->loadObject();
        
        if ($result) {
            $minDuration = (int)$result->min_duration;
            $maxDuration = (int)$result->max_duration;
            
            // Ensure we have reasonable values
            $minDuration = $minDuration > 0 ? $minDuration : 1;
            $maxDuration = $maxDuration > $minDuration ? $maxDuration : ($minDuration + 1);
            
            return [
                'min' => $minDuration,
                'max' => $maxDuration
            ];
        }
        
        return ['min' => 1, 'max' => 10]; 
    }

    public function getPackageCountsForTabs($destinationId = 0) {
        $db = $this->getDbo();
        $app = Factory::getApplication();
        $input = $app->input;

        $urlId = $input->getInt('id', 0);
        if ($urlId) {
            $destinationId = $urlId;
        } else {
            $paramDestinationId = $input->getInt('destination_id', 0);
            if ($paramDestinationId) {
                $destinationId = $paramDestinationId;
            }
        }

        $counts = new stdClass();

        $query = $db->getQuery(true);
        $query->select('COUNT(p.id)')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1');
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }
        $db->setQuery($query);
        $counts->all_packages = $db->loadResult();

        $query->clear()
              ->select('COUNT(p.id)')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1')
              ->where($db->quoteName('p.package_type') . ' = ' . $db->quote('Group Tour'));
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }
        $db->setQuery($query);
        $counts->group_tours = $db->loadResult();

        $query->clear()
              ->select('COUNT(p.id)')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1')
              ->where($db->quoteName('p.special_package') . ' = ' . $db->quote('Book @ ₹1.4'));
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }
        $db->setQuery($query);
        $counts->book_at_1_4 = $db->loadResult();

        $query->clear()
              ->select('COUNT(p.id)')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1')
              ->where($db->quoteName('p.special_package') . ' = ' . $db->quote('Newly Launched'));
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }
        $db->setQuery($query);
        $counts->newly_launched = $db->loadResult();

        $query->clear()
              ->select('COUNT(p.id)')
              ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
              ->where('p.published = 1')
              ->where($db->quoteName('p.special_package') . ' = ' . $db->quote('Luxury'));
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }
        $db->setQuery($query);
        $counts->luxury = $db->loadResult();

        return $counts;
    }

    public function processSectionTypes($sectionTypesJson, $packageData = null) {
        // Ensure $sectionTypesJson is a string
        if (is_array($sectionTypesJson)) {
            $sectionTypesJson = implode(',', $sectionTypesJson);
        } elseif (empty($sectionTypesJson) || !is_string($sectionTypesJson)) {
            return [];
        }

        $types = array_map('trim', explode(',', $sectionTypesJson));
        $types = array_filter($types);
        if (empty($types)) return [];

        $activitiesCount = 0;
        $otherFeatures = [];

        foreach ($types as $type) {
            $cleanType = trim($type, "[] \t\n\r\0\x0B");
            
            if (strcasecmp($cleanType, 'Activities') === 0) {
                $activitiesCount++;
            } elseif (!in_array($cleanType, $otherFeatures)) {
                // Handle Star Hotels with rating
                if (strcasecmp($cleanType, 'Star Hotels') === 0 && 
                    !empty($packageData->hotel_category)) {
                    $stars = str_replace('*', ' star', $packageData->hotel_category);
                    $otherFeatures[] = $stars . ' Hotels';
                } else {
                    $otherFeatures[] = $cleanType;
                }
            }
        }

        $result = [];
        if ($activitiesCount > 0) {
            $result[] = $activitiesCount > 1 ? "Activities ($activitiesCount)" : "Activities";
        }
        
        return array_merge($result, array_slice($otherFeatures, 0, 5));
    }

    public function getSectionTypesAndActivitiesCount($destinationId = 0) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('i.all_sections')
              ->from($db->quoteName('n4gvg__holiday_itineraries', 'i'))
              ->join('INNER', $db->quoteName('n4gvg__holiday_packages', 'p') . ' ON i.package_id = p.id')
              ->where('p.published = 1');

        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }

        $db->setQuery($query);
        $results = $db->loadColumn();

        $sectionCounts = [];

        foreach ($results as $jsonData) {
            if (empty($jsonData)) continue;

            $decoded = json_decode($jsonData, true);

            if (!is_array($decoded)) continue;

            foreach ($decoded as $entry) {
                if (isset($entry['all_sections']['section_type'])) {
                    $type = trim($entry['all_sections']['section_type']);
                    if ($type !== '') {
                        if (!isset($sectionCounts[$type])) {
                            $sectionCounts[$type] = 0;
                        }
                        $sectionCounts[$type]++;
                    }
                }
            }
        }

        return $sectionCounts;
    }

    public function getActivitiesCountForPackage($packageId) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        
        $query->select('COUNT(*)')
              ->from($db->quoteName('n4gvg__holiday_itineraries'))
              ->where($db->quoteName('package_id') . ' = ' . (int)$packageId)
              ->where($db->quoteName('all_sections') . ' LIKE ' . $db->quote('%Activities%'))
              ->where($db->quoteName('status') . ' = 1');
        
        $db->setQuery($query);
        return $db->loadResult();
    }


function getDestinationData($id)
{
    $imagePath = '';
    $destinationTitle = '';

    if ($id > 0) {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('image'), $db->quoteName('title')])
            ->from($db->quoteName('n4gvg__holiday_destinations')) 
            ->where($db->quoteName('id') . ' = ' . (int) $id);

        $db->setQuery($query);
        $result = $db->loadAssoc();

        if ($result) {
            $imagePath = $result['image'] ?? '';
            $destinationTitle = $result['title'] ?? '';
        }
    }

    return [
        'imageUrl' => $imagePath ? Uri::root() . ltrim($imagePath, '/') : '',
        'title' => $destinationTitle
    ];
}

}