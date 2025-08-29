<?php
/**
 * Holiday Packages Enhanced Component
 * 
 * @package     HolidayPackages
 * @subpackage  Site
 * @author      Your Name
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;

/**
 * Frontend Booking Model for Holiday Packages Enhanced Component
 * 
 * Handles customer booking process, availability checks, price calculations,
 * payment processing, and booking confirmations from the frontend.
 */
class HolidayPackagesModelBooking extends FormModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     */
    public $typeAlias = 'com_holidaypackages.booking';

    /**
     * The context for storing internal data
     *
     * @var    string
     */
    protected $context = 'com_holidaypackages.booking';

    /**
     * Method to get the record form
     *
     * @param   array    $data      Data for the form
     * @param   boolean  $loadData  True if the form is to load its own data, false otherwise
     * @return  Form|boolean  A Form object on success, false on failure
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form
        $form = $this->loadForm(
            'com_holidaypackages.booking',
            'booking',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form
     *
     * @return  mixed  The data for the form
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data
        $app = Factory::getApplication();
        $data = $app->getUserState($this->context . '.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Method to get a table object, load it if necessary
     *
     * @param   string  $type    The table name
     * @param   string  $prefix  The class prefix
     * @param   array   $config  Configuration array for model
     * @return  Table   A Table object
     */
    public function getTable($type = 'Booking', $prefix = 'HolidayPackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Check package availability for given dates and traveler count
     *
     * @param   int     $packageId    Package ID
     * @param   string  $startDate    Start date (Y-m-d format)
     * @param   string  $endDate      End date (Y-m-d format)  
     * @param   int     $travelers    Number of travelers
     * @return  array   Availability data
     */
    public function checkAvailability(int $packageId, string $startDate, string $endDate, int $travelers): array
    {
        $db = $this->getDatabase();
        
        try {
            // Get package details
            $query = $db->getQuery(true)
                ->select([
                    'p.*',
                    'COUNT(b.id) as current_bookings'
                ])
                ->from($db->quoteName('#__hp_packages', 'p'))
                ->leftJoin(
                    $db->quoteName('#__hp_bookings', 'b') . ' ON p.id = b.package_id 
                    AND b.status IN (' . $db->quote('confirmed') . ', ' . $db->quote('pending') . ')
                    AND ((b.start_date <= :endDate AND b.end_date >= :startDate))'
                )
                ->where($db->quoteName('p.id') . ' = :packageId')
                ->where($db->quoteName('p.published') . ' = 1')
                ->group($db->quoteName('p.id'))
                ->bind(':packageId', $packageId, ParameterType::INTEGER)
                ->bind(':startDate', $startDate)
                ->bind(':endDate', $endDate);

            $package = $db->setQuery($query)->loadObject();

            if (!$package) {
                return [
                    'available' => false,
                    'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_FOUND')
                ];
            }

            // Check if package is active during requested dates
            if (!empty($package->available_from) && $startDate < $package->available_from) {
                return [
                    'available' => false,
                    'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_NOT_AVAILABLE_FROM', $package->available_from)
                ];
            }

            if (!empty($package->available_to) && $startDate > $package->available_to) {
                return [
                    'available' => false,
                    'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_NOT_AVAILABLE_TO', $package->available_to)
                ];
            }

            // Check capacity
            $maxCapacity = $package->max_capacity ?: 999;
            $availableSpots = $maxCapacity - $package->current_bookings;

            if ($travelers > $availableSpots) {
                return [
                    'available' => false,
                    'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_INSUFFICIENT_CAPACITY', $availableSpots)
                ];
            }

            // Check minimum advance booking
            $minAdvanceDays = $package->min_advance_booking ?: 1;
            $today = Factory::getDate();
            $requestedDate = Factory::getDate($startDate);
            $daysDifference = $requestedDate->diff($today)->days;

            if ($daysDifference < $minAdvanceDays) {
                return [
                    'available' => false,
                    'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_MIN_ADVANCE_BOOKING', $minAdvanceDays)
                ];
            }

            return [
                'available' => true,
                'package' => $package,
                'available_spots' => $availableSpots,
                'message' => Text::_('COM_HOLIDAYPACKAGES_PACKAGE_AVAILABLE')
            ];

        } catch (Exception $e) {
            Log::add(
                sprintf('Error checking availability for package %d: %s', $packageId, $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );

            return [
                'available' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_AVAILABILITY_CHECK')
            ];
        }
    }

    /**
     * Calculate booking price with all components
     *
     * @param   array  $bookingData  Booking data
     * @return  array  Price breakdown
     */
    public function calculatePrice(array $bookingData): array
    {
        $db = $this->getDatabase();
        
        try {
            // Get package details
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__hp_packages'))
                ->where($db->quoteName('id') . ' = :packageId')
                ->bind(':packageId', $bookingData['package_id'], ParameterType::INTEGER);

            $package = $db->setQuery($query)->loadObject();

            if (!$package) {
                throw new Exception('Package not found');
            }

            // Base calculations
            $adults = (int) ($bookingData['adults'] ?? 1);
            $children = (int) ($bookingData['children'] ?? 0);
            $infants = (int) ($bookingData['infants'] ?? 0);

            $basePrice = (float) $package->price;
            
            // Calculate pricing based on age groups
            $adultPrice = $adults * $basePrice;
            $childPrice = $children * ($basePrice * 0.75); // 75% of adult price
            $infantPrice = $infants * ($basePrice * 0.1);  // 10% of adult price

            $subtotal = $adultPrice + $childPrice + $infantPrice;

            // Apply seasonal pricing adjustments
            $seasonalMultiplier = $this->getSeasonalPriceMultiplier(
                $bookingData['start_date'] ?? null,
                $package->seasonal_pricing ?? null
            );
            
            $subtotal *= $seasonalMultiplier;

            // Calculate extras
            $extrasTotal = 0;
            $extrasBreakdown = [];

            if (!empty($bookingData['extras']) && is_array($bookingData['extras'])) {
                foreach ($bookingData['extras'] as $extraId => $quantity) {
                    if ($quantity > 0) {
                        $extra = $this->getPackageExtra($package->id, $extraId);
                        if ($extra) {
                            $extraAmount = $extra->price * $quantity;
                            $extrasTotal += $extraAmount;
                            $extrasBreakdown[] = [
                                'id' => $extraId,
                                'name' => $extra->name,
                                'price' => $extra->price,
                                'quantity' => $quantity,
                                'total' => $extraAmount
                            ];
                        }
                    }
                }
            }

            // Apply discounts
            $discountAmount = 0;
            $discountDetails = [];

            // Early bird discount
            if (!empty($package->early_bird_discount) && !empty($bookingData['start_date'])) {
                $earlyBirdDiscount = $this->calculateEarlyBirdDiscount(
                    $bookingData['start_date'],
                    $package->early_bird_discount
                );
                
                if ($earlyBirdDiscount > 0) {
                    $discountAmount += $earlyBirdDiscount;
                    $discountDetails[] = [
                        'type' => 'early_bird',
                        'amount' => $earlyBirdDiscount,
                        'description' => 'Early Bird Discount'
                    ];
                }
            }

            // Group discount
            $totalTravelers = $adults + $children;
            if ($totalTravelers >= 10) {
                $groupDiscount = $subtotal * 0.1; // 10% group discount
                $discountAmount += $groupDiscount;
                $discountDetails[] = [
                    'type' => 'group',
                    'amount' => $groupDiscount,
                    'description' => 'Group Discount (10+ travelers)'
                ];
            }

            // Calculate taxes
            $taxableAmount = $subtotal + $extrasTotal - $discountAmount;
            $gstRate = 0.18; // 18% GST
            $gstAmount = $taxableAmount * $gstRate;

            // Service fee
            $serviceFee = $taxableAmount * 0.02; // 2% service fee

            // Calculate final total
            $finalTotal = $taxableAmount + $gstAmount + $serviceFee;

            return [
                'success' => true,
                'breakdown' => [
                    'base_price' => $basePrice,
                    'adults' => $adults,
                    'children' => $children,
                    'infants' => $infants,
                    'adult_price' => $adultPrice,
                    'child_price' => $childPrice,
                    'infant_price' => $infantPrice,
                    'subtotal' => $subtotal,
                    'seasonal_multiplier' => $seasonalMultiplier,
                    'extras_total' => $extrasTotal,
                    'extras_breakdown' => $extrasBreakdown,
                    'discount_amount' => $discountAmount,
                    'discount_details' => $discountDetails,
                    'taxable_amount' => $taxableAmount,
                    'gst_amount' => $gstAmount,
                    'service_fee' => $serviceFee,
                    'total' => $finalTotal
                ]
            ];

        } catch (Exception $e) {
            Log::add(
                sprintf('Error calculating price: %s', $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );

            return [
                'success' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_PRICE_CALCULATION')
            ];
        }
    }

    /**
     * Create a new booking
     *
     * @param   array  $data  Booking data
     * @return  array  Result data
     */
    public function createBooking(array $data): array
    {
        $db = $this->getDatabase();
        
        try {
            $db->transactionStart();

            // Validate booking data
            $validation = $this->validateBookingData($data);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }

            // Check availability one more time
            $availability = $this->checkAvailability(
                $data['package_id'],
                $data['start_date'],
                $data['end_date'],
                ($data['adults'] + $data['children'])
            );

            if (!$availability['available']) {
                throw new Exception($availability['message']);
            }

            // Calculate final price
            $priceData = $this->calculatePrice($data);
            if (!$priceData['success']) {
                throw new Exception($priceData['message']);
            }

            // Create or get customer
            $customerId = $this->createOrGetCustomer($data['customer']);

            // Generate booking reference
            $bookingReference = $this->generateBookingReference();

            // Create booking record
            $bookingId = $this->createBookingRecord([
                'package_id' => $data['package_id'],
                'customer_id' => $customerId,
                'booking_reference' => $bookingReference,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'adults' => $data['adults'],
                'children' => $data['children'],
                'infants' => $data['infants'],
                'total_amount' => $priceData['breakdown']['total'],
                'special_requests' => $data['special_requests'] ?? '',
                'status' => 'pending',
                'booking_date' => Factory::getDate()->toSql()
            ]);

            // Save travelers details
            if (!empty($data['travelers'])) {
                $this->saveTravelersDetails($bookingId, $data['travelers']);
            }

            // Save extras
            if (!empty($data['extras'])) {
                $this->saveBookingExtras($bookingId, $data['extras'], $priceData['breakdown']['extras_breakdown']);
            }

            $db->transactionCommit();

            // Trigger booking created event
            PluginHelper::importPlugin('holidaypackages');
            Factory::getApplication()->triggerEvent('onHolidayPackagesBookingCreated', [$bookingId]);

            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_reference' => $bookingReference,
                'total_amount' => $priceData['breakdown']['total'],
                'message' => Text::_('COM_HOLIDAYPACKAGES_BOOKING_CREATED_SUCCESS')
            ];

        } catch (Exception $e) {
            $db->transactionRollback();
            
            Log::add(
                sprintf('Error creating booking: %s', $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get booking by reference
     *
     * @param   string  $reference  Booking reference
     * @return  object|null  Booking data
     */
    public function getBookingByReference(string $reference): ?object
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'p.title as package_title',
                'p.duration',
                'p.destination',
                'p.images',
                'c.first_name',
                'c.last_name',
                'c.email',
                'c.phone'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id')
            ->where($db->quoteName('b.booking_reference') . ' = :reference')
            ->bind(':reference', $reference);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Get user's bookings
     *
     * @param   int  $userId  User ID
     * @return  array  Bookings list
     */
    public function getUserBookings(int $userId): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'p.title as package_title',
                'p.destination',
                'p.duration',
                'p.images'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id')
            ->where($db->quoteName('c.user_id') . ' = :userId')
            ->order($db->quoteName('b.booking_date') . ' DESC')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Validate booking data
     *
     * @param   array  $data  Booking data
     * @return  array  Validation result
     */
    protected function validateBookingData(array $data): array
    {
        // Required fields validation
        $required = ['package_id', 'start_date', 'end_date', 'adults', 'customer'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_REQUIRED_FIELD', $field)
                ];
            }
        }

        // Date validation
        $startDate = Factory::getDate($data['start_date']);
        $endDate = Factory::getDate($data['end_date']);
        $today = Factory::getDate();

        if ($startDate <= $today) {
            return [
                'valid' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_START_DATE_FUTURE')
            ];
        }

        if ($startDate >= $endDate) {
            return [
                'valid' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_DATE_RANGE')
            ];
        }

        // Traveler count validation
        if ($data['adults'] < 1) {
            return [
                'valid' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_MIN_ADULTS')
            ];
        }

        // Customer data validation
        $customer = $data['customer'];
        if (empty($customer['email']) || !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_EMAIL')
            ];
        }

        return ['valid' => true];
    }

    /**
     * Create or get existing customer
     *
     * @param   array  $customerData  Customer data
     * @return  int  Customer ID
     */
    protected function createOrGetCustomer(array $customerData): int
    {
        $db = $this->getDatabase();
        $user = Factory::getUser();

        // Check if customer exists by email
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('email') . ' = :email')
            ->bind(':email', $customerData['email']);

        $existingId = $db->setQuery($query)->loadResult();

        if ($existingId) {
            // Update existing customer
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_customers'))
                ->set([
                    $db->quoteName('first_name') . ' = :firstName',
                    $db->quoteName('last_name') . ' = :lastName',
                    $db->quoteName('phone') . ' = :phone',
                    $db->quoteName('address') . ' = :address',
                    $db->quoteName('city') . ' = :city',
                    $db->quoteName('state') . ' = :state',
                    $db->quoteName('country') . ' = :country',
                    $db->quoteName('pincode') . ' = :pincode',
                    $db->quoteName('modified') . ' = NOW()'
                ])
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':firstName', $customerData['first_name'])
                ->bind(':lastName', $customerData['last_name'])
                ->bind(':phone', $customerData['phone'] ?? '')
                ->bind(':address', $customerData['address'] ?? '')
                ->bind(':city', $customerData['city'] ?? '')
                ->bind(':state', $customerData['state'] ?? '')
                ->bind(':country', $customerData['country'] ?? '')
                ->bind(':pincode', $customerData['pincode'] ?? '')
                ->bind(':id', $existingId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();
            return $existingId;
        }

        // Create new customer
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__hp_customers'))
            ->columns([
                $db->quoteName('user_id'),
                $db->quoteName('first_name'),
                $db->quoteName('last_name'),
                $db->quoteName('email'),
                $db->quoteName('phone'),
                $db->quoteName('address'),
                $db->quoteName('city'),
                $db->quoteName('state'),
                $db->quoteName('country'),
                $db->quoteName('pincode'),
                $db->quoteName('created'),
                $db->quoteName('modified')
            ])
            ->values([
                ':userId',
                ':firstName',
                ':lastName',
                ':email',
                ':phone',
                ':address',
                ':city',
                ':state',
                ':country',
                ':pincode',
                'NOW()',
                'NOW()'
            ])
            ->bind(':userId', $user->id > 0 ? $user->id : null, ParameterType::INTEGER)
            ->bind(':firstName', $customerData['first_name'])
            ->bind(':lastName', $customerData['last_name'])
            ->bind(':email', $customerData['email'])
            ->bind(':phone', $customerData['phone'] ?? '')
            ->bind(':address', $customerData['address'] ?? '')
            ->bind(':city', $customerData['city'] ?? '')
            ->bind(':state', $customerData['state'] ?? '')
            ->bind(':country', $customerData['country'] ?? '')
            ->bind(':pincode', $customerData['pincode'] ?? '');

        $db->setQuery($query)->execute();
        return $db->insertid();
    }

    /**
     * Generate unique booking reference
     *
     * @return  string  Booking reference
     */
    protected function generateBookingReference(): string
    {
        $db = $this->getDatabase();
        
        do {
            $reference = 'HP' . strtoupper(substr(uniqid(), -8));
            
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('booking_reference') . ' = :ref')
                ->bind(':ref', $reference);

            $exists = $db->setQuery($query)->loadResult();
        } while ($exists > 0);

        return $reference;
    }

    /**
     * Create booking record
     *
     * @param   array  $data  Booking data
     * @return  int  Booking ID
     */
    protected function createBookingRecord(array $data): int
    {
        $table = $this->getTable('Booking');
        
        if (!$table->bind($data)) {
            throw new Exception($table->getError());
        }

        if (!$table->check()) {
            throw new Exception($table->getError());
        }

        if (!$table->store()) {
            throw new Exception($table->getError());
        }

        return $table->id;
    }

    /**
     * Save travelers details
     *
     * @param   int    $bookingId  Booking ID
     * @param   array  $travelers  Travelers data
     * @return  void
     */
    protected function saveTravelersDetails(int $bookingId, array $travelers): void
    {
        $db = $this->getDatabase();

        foreach ($travelers as $traveler) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__hp_booking_travelers'))
                ->columns([
                    $db->quoteName('booking_id'),
                    $db->quoteName('title'),
                    $db->quoteName('first_name'),
                    $db->quoteName('last_name'),
                    $db->quoteName('date_of_birth'),
                    $db->quoteName('gender'),
                    $db->quoteName('passport_number'),
                    $db->quoteName('passport_expiry'),
                    $db->quoteName('nationality'),
                    $db->quoteName('type')
                ])
                ->values([
                    ':bookingId',
                    ':title',
                    ':firstName',
                    ':lastName',
                    ':dateOfBirth',
                    ':gender',
                    ':passportNumber',
                    ':passportExpiry',
                    ':nationality',
                    ':type'
                ])
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER)
                ->bind(':title', $traveler['title'] ?? '')
                ->bind(':firstName', $traveler['first_name'])
                ->bind(':lastName', $traveler['last_name'])
                ->bind(':dateOfBirth', $traveler['date_of_birth'] ?? null)
                ->bind(':gender', $traveler['gender'] ?? '')
                ->bind(':passportNumber', $traveler['passport_number'] ?? '')
                ->bind(':passportExpiry', $traveler['passport_expiry'] ?? null)
                ->bind(':nationality', $traveler['nationality'] ?? '')
                ->bind(':type', $traveler['type'] ?? 'adult');

            $db->setQuery($query)->execute();
        }
    }

    /**
     * Save booking extras
     *
     * @param   int    $bookingId     Booking ID
     * @param   array  $extras        Selected extras
     * @param   array  $extrasData    Extras breakdown data
     * @return  void
     */
    protected function saveBookingExtras(int $bookingId, array $extras, array $extrasData): void
    {
        $db = $this->getDatabase();

        foreach ($extrasData as $extra) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__hp_booking_extras'))
                ->columns([
                    $db->quoteName('booking_id'),
                    $db->quoteName('extra_id'),
                    $db->quoteName('name'),
                    $db->quoteName('price'),
                    $db->quoteName('quantity'),
                    $db->quoteName('total_amount')
                ])
                ->values([
                    ':bookingId',
                    ':extraId',
                    ':name',
                    ':price',
                    ':quantity',
                    ':totalAmount'
                ])
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER)
                ->bind(':extraId', $extra['id'], ParameterType::INTEGER)
                ->bind(':name', $extra['name'])
                ->bind(':price', $extra['price'], ParameterType::FLOAT)
                ->bind(':quantity', $extra['quantity'], ParameterType::INTEGER)
                ->bind(':totalAmount', $extra['total'], ParameterType::FLOAT);

            $db->setQuery($query)->execute();
        }
    }

    /**
     * Get seasonal price multiplier
     *
     * @param   string  $date            Travel date
     * @param   string  $seasonalPricing Seasonal pricing JSON
     * @return  float   Price multiplier
     */
    protected function getSeasonalPriceMultiplier(?string $date, ?string $seasonalPricing): float
    {
        if (empty($date) || empty($seasonalPricing)) {
            return 1.0;
        }

        $pricing = json_decode($seasonalPricing, true);
        if (!$pricing) {
            return 1.0;
        }

        $travelDate = Factory::getDate($date);
        
        foreach ($pricing as $season) {
            $startDate = Factory::getDate($season['start_date']);
            $endDate = Factory::getDate($season['end_date']);
            
            if ($travelDate >= $startDate && $travelDate <= $endDate) {
                return (float) ($season['multiplier'] ?? 1.0);
            }
        }

        return 1.0;
    }

    /**
     * Calculate early bird discount
     *
     * @param   string  $startDate  Package start date
     * @param   string  $discount   Discount configuration JSON
     * @return  float   Discount amount
     */
    protected function calculateEarlyBirdDiscount(string $startDate, string $discount): float
    {
        $discountConfig = json_decode($discount, true);
        if (!$discountConfig) {
            return 0;
        }

        $now = Factory::getDate();
        $start = Factory::getDate($startDate);
        $daysUntilStart = $start->diff($now)->days;

        if ($daysUntilStart >= ($discountConfig['days_before'] ?? 30)) {
            return $discountConfig['amount'] ?? 0;
        }

        return 0;
    }

    /**
     * Get package extra details
     *
     * @param   int  $packageId  Package ID
     * @param   int  $extraId    Extra ID
     * @return  object|null  Extra details
     */
    protected function getPackageExtra(int $packageId, int $extraId): ?object
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__hp_package_extras'))
            ->where($db->quoteName('package_id') . ' = :packageId')
            ->where($db->quoteName('id') . ' = :extraId')
            ->bind(':packageId', $packageId, ParameterType::INTEGER)
            ->bind(':extraId', $extraId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject();
    }
}