<?php
/**
 * Holiday Packages Enhanced Component
 * 
 * @package     HolidayPackages
 * @subpackage  Administrator
 * @author      Your Name
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;

/**
 * Booking Table Class for Holiday Packages Enhanced Component
 * 
 * Handles booking data operations including validation, storage,
 * and relationship management with packages, customers, and payments.
 */
class HolidayPackagesTableBooking extends Table implements VersionableTableInterface, TaggableTableInterface
{
    use TaggableTableTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     */
    protected $_supportNullValue = false;

    /**
     * The type alias for content versioning
     *
     * @var    string
     */
    public $typeAlias = 'com_holidaypackages.booking';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_holidaypackages.booking';
        
        parent::__construct('#__hp_bookings', 'id', $db);

        // Set the created and modified dates
        $this->setColumnAlias('created', 'booking_date');
        $this->setColumnAlias('modified', 'modified');
    }

    /**
     * Method to bind an associative array or object to the Table instance.
     *
     * @param   array|object  $src     An associative array or object to bind to the Table instance
     * @param   array|string  $ignore  An optional array or space separated list of properties to ignore while binding
     * @return  boolean  True on success
     */
    public function bind($src, $ignore = [])
    {
        // Convert array to object if needed
        if (is_array($src)) {
            $src = (object) $src;
        }

        // Handle JSON fields
        if (isset($src->extras) && (is_array($src->extras) || is_object($src->extras))) {
            $src->extras = json_encode($src->extras);
        }

        if (isset($src->special_requests) && is_array($src->special_requests)) {
            $src->special_requests = implode("\n", $src->special_requests);
        }

        // Set booking date if not provided
        if (empty($src->booking_date)) {
            $src->booking_date = Factory::getDate()->toSql();
        }

        // Set modified date
        if (empty($src->id)) {
            $src->modified = null;
        } else {
            $src->modified = Factory::getDate()->toSql();
        }

        // Generate booking reference if not provided
        if (empty($src->booking_reference) && empty($src->id)) {
            $src->booking_reference = $this->generateBookingReference();
        }

        return parent::bind($src, $ignore);
    }

    /**
     * Method to perform sanity checks on the Table instance properties
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database
     */
    public function check()
    {
        try {
            parent::check();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Check required fields
        if (empty($this->package_id)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_PACKAGE_REQUIRED'));
            return false;
        }

        if (empty($this->customer_id)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_CUSTOMER_REQUIRED'));
            return false;
        }

        if (empty($this->start_date)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_START_DATE_REQUIRED'));
            return false;
        }

        if (empty($this->end_date)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_END_DATE_REQUIRED'));
            return false;
        }

        // Validate dates
        $startDate = Factory::getDate($this->start_date);
        $endDate = Factory::getDate($this->end_date);
        $today = Factory::getDate();

        if ($startDate >= $endDate) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_INVALID_DATES'));
            return false;
        }

        // Don't allow past bookings for new records
        if (empty($this->id) && $startDate < $today) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_PAST_DATE'));
            return false;
        }

        // Validate traveler counts
        if ($this->adults < 1) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_MIN_ADULTS'));
            return false;
        }

        if ($this->children < 0) {
            $this->children = 0;
        }

        if ($this->infants < 0) {
            $this->infants = 0;
        }

        // Validate total amount
        if ($this->total_amount <= 0) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_INVALID_AMOUNT'));
            return false;
        }

        // Validate status
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'refunded'];
        if (!in_array($this->status, $validStatuses)) {
            $this->status = 'pending';
        }

        // Check booking reference uniqueness
        if (!$this->checkBookingReferenceUnique()) {
            return false;
        }

        // Validate package availability
        if (!$this->validatePackageAvailability()) {
            return false;
        }

        return true;
    }

    /**
     * Method to store a row in the database
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null
     * @return  boolean  True on success
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate();
        $user = Factory::getUser();

        // Set creation date if new record
        if (empty($this->id)) {
            if (empty($this->booking_date)) {
                $this->booking_date = $date->toSql();
            }
        } else {
            // Always update modified date for existing records
            $this->modified = $date->toSql();
        }

        // Store the booking
        $result = parent::store($updateNulls);

        if ($result) {
            // Handle post-save operations
            $this->handlePostSaveOperations();
        }

        return $result;
    }

    /**
     * Method to delete a row from the database table
     *
     * @param   mixed  $pk  An optional primary key value to delete
     * @return  boolean  True on success
     */
    public function delete($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        // Load the row if not already loaded
        if (!$this->load($pk)) {
            return false;
        }

        // Only allow deletion of pending or cancelled bookings
        if (!in_array($this->status, ['pending', 'cancelled'])) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_CANNOT_DELETE_CONFIRMED'));
            return false;
        }

        // Delete related records
        $this->deleteRelatedRecords($pk);

        return parent::delete($pk);
    }

    /**
     * Generate unique booking reference
     *
     * @return  string  Unique booking reference
     */
    protected function generateBookingReference(): string
    {
        $db = $this->_db;
        
        do {
            $reference = 'HP' . strtoupper(substr(uniqid(), -8));
            
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('booking_reference') . ' = ' . $db->quote($reference));

            $exists = $db->setQuery($query)->loadResult();
        } while ($exists > 0);

        return $reference;
    }

    /**
     * Check if booking reference is unique
     *
     * @return  boolean  True if unique
     */
    protected function checkBookingReferenceUnique(): bool
    {
        if (empty($this->booking_reference)) {
            return true;
        }

        $db = $this->_db;
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('booking_reference') . ' = ' . $db->quote($this->booking_reference));

        if (!empty($this->id)) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $count = $db->setQuery($query)->loadResult();

        if ($count > 0) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_BOOKING_REFERENCE_EXISTS'));
            return false;
        }

        return true;
    }

    /**
     * Validate package availability for booking dates
     *
     * @return  boolean  True if available
     */
    protected function validatePackageAvailability(): bool
    {
        $db = $this->_db;

        // Get package details
        $query = $db->getQuery(true)
            ->select([
                'published',
                'max_capacity',
                'available_from',
                'available_to',
                'min_advance_booking'
            ])
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('id') . ' = ' . (int) $this->package_id);

        $package = $db->setQuery($query)->loadObject();

        if (!$package) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_FOUND'));
            return false;
        }

        // Check if package is published
        if (!$package->published) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_PUBLISHED'));
            return false;
        }

        // Check availability dates
        if (!empty($package->available_from) && $this->start_date < $package->available_from) {
            $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_AVAILABLE_FROM', $package->available_from));
            return false;
        }

        if (!empty($package->available_to) && $this->start_date > $package->available_to) {
            $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_AVAILABLE_TO', $package->available_to));
            return false;
        }

        // Check minimum advance booking
        if (!empty($package->min_advance_booking)) {
            $today = Factory::getDate();
            $startDate = Factory::getDate($this->start_date);
            $daysDifference = $startDate->diff($today)->days;

            if ($daysDifference < $package->min_advance_booking) {
                $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_MIN_ADVANCE_BOOKING', $package->min_advance_booking));
                return false;
            }
        }

        // Check capacity (if specified)
        if (!empty($package->max_capacity)) {
            $totalTravelers = $this->adults + $this->children;
            
            // Get existing bookings for same dates
            $query = $db->getQuery(true)
                ->select('SUM(adults + children) as total_travelers')
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('package_id') . ' = ' . (int) $this->package_id)
                ->where($db->quoteName('status') . ' IN (' . $db->quote('confirmed') . ', ' . $db->quote('pending') . ')')
                ->where('((start_date <= ' . $db->quote($this->end_date) . ' AND end_date >= ' . $db->quote($this->start_date) . '))');

            if (!empty($this->id)) {
                $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
            }

            $existingTravelers = (int) $db->setQuery($query)->loadResult();
            
            if (($existingTravelers + $totalTravelers) > $package->max_capacity) {
                $available = $package->max_capacity - $existingTravelers;
                $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_INSUFFICIENT_CAPACITY', $available));
                return false;
            }
        }

        return true;
    }

    /**
     * Handle post-save operations
     *
     * @return  void
     */
    protected function handlePostSaveOperations(): void
    {
        // Update package booking count
        $this->updatePackageBookingCount();
        
        // Handle tags if this implements TaggableTableInterface
        // This would be implemented if using Joomla's tagging system
    }

    /**
     * Update package booking count
     *
     * @return  void
     */
    protected function updatePackageBookingCount(): void
    {
        $db = $this->_db;
        
        // Count confirmed bookings for this package
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('package_id') . ' = ' . (int) $this->package_id)
            ->where($db->quoteName('status') . ' IN (' . $db->quote('confirmed') . ', ' . $db->quote('completed') . ')');

        $bookingCount = (int) $db->setQuery($query)->loadResult();

        // Update package
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__hp_packages'))
            ->set($db->quoteName('bookings_count') . ' = ' . $bookingCount)
            ->where($db->quoteName('id') . ' = ' . (int) $this->package_id);

        $db->setQuery($query)->execute();
    }

    /**
     * Delete related records when booking is deleted
     *
     * @param   mixed  $pk  Primary key
     * @return  void
     */
    protected function deleteRelatedRecords($pk): void
    {
        $db = $this->_db;

        // Delete booking travelers
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_booking_travelers'))
            ->where($db->quoteName('booking_id') . ' = ' . (int) $pk);
        $db->setQuery($query)->execute();

        // Delete booking extras
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_booking_extras'))
            ->where($db->quoteName('booking_id') . ' = ' . (int) $pk);
        $db->setQuery($query)->execute();

        // Delete payment records (only pending payments)
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('booking_id') . ' = ' . (int) $pk)
            ->where($db->quoteName('status') . ' = ' . $db->quote('pending'));
        $db->setQuery($query)->execute();
    }

    /**
     * Get the type alias for content versioning
     *
     * @return  string  The alias as described above
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }

    /**
     * Method to get the booking details with related data
     *
     * @param   mixed  $pk  Primary key
     * @return  object|null  Booking details or null
     */
    public function getBookingDetails($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return null;
        }

        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'p.title as package_title',
                'p.destination',
                'p.duration',
                'p.images as package_images',
                'CONCAT(c.first_name, " ", c.last_name) as customer_name',
                'c.email as customer_email',
                'c.phone as customer_phone'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id')
            ->where($db->quoteName('b.id') . ' = ' . (int) $pk);

        return $db->setQuery($query)->loadObject();
    }
}