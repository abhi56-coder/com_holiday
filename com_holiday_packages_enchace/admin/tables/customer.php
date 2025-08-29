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
use Joomla\CMS\Filter\OutputFilter;

/**
 * Customer Table Class for Holiday Packages Enhanced Component
 * 
 * Handles customer data operations including validation, storage,
 * and profile management.
 */
class HolidayPackagesTableCustomer extends Table
{
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     */
    protected $_supportNullValue = false;

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__hp_customers', 'id', $db);

        // Set the created and modified dates
        $this->setColumnAlias('created_time', 'created');
        $this->setColumnAlias('modified_time', 'modified');
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
        if (isset($src->preferences) && (is_array($src->preferences) || is_object($src->preferences))) {
            $src->preferences = json_encode($src->preferences);
        }

        // Clean and format data
        if (!empty($src->email)) {
            $src->email = strtolower(trim($src->email));
        }

        if (!empty($src->phone)) {
            $src->phone = preg_replace('/[^0-9+\-\s\(\)]/', '', $src->phone);
        }

        if (!empty($src->first_name)) {
            $src->first_name = ucfirst(trim($src->first_name));
        }

        if (!empty($src->last_name)) {
            $src->last_name = ucfirst(trim($src->last_name));
        }

        // Handle newsletter subscription
        if (!isset($src->newsletter_subscribed)) {
            $src->newsletter_subscribed = 0;
        }

        // Set creation date if new record
        if (empty($src->created) && empty($src->id)) {
            $src->created = Factory::getDate()->toSql();
        }

        // Always set modified date
        $src->modified = Factory::getDate()->toSql();

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
        if (empty($this->first_name)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_FIRST_NAME_REQUIRED'));
            return false;
        }

        if (empty($this->last_name)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_LAST_NAME_REQUIRED'));
            return false;
        }

        if (empty($this->email)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_EMAIL_REQUIRED'));
            return false;
        }

        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_INVALID_EMAIL'));
            return false;
        }

        // Check email uniqueness
        if (!$this->checkEmailUnique()) {
            return false;
        }

        // Validate date of birth if provided
        if (!empty($this->date_of_birth)) {
            $birthDate = Factory::getDate($this->date_of_birth);
            $today = Factory::getDate();
            
            if ($birthDate > $today) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_INVALID_BIRTH_DATE'));
                return false;
            }

            // Check minimum age (assume 18 for bookings)
            $age = $today->diff($birthDate)->y;
            if ($age < 18) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_MINIMUM_AGE'));
                return false;
            }
        }

        // Validate phone number format (basic)
        if (!empty($this->phone) && !preg_match('/^[\+]?[0-9\-\s\(\)]{6,20}$/', $this->phone)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_INVALID_PHONE'));
            return false;
        }

        // Clean and validate preferences JSON
        if (!empty($this->preferences)) {
            if (is_string($this->preferences)) {
                $decoded = json_decode($this->preferences, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->preferences = '{}';
                }
            }
        } else {
            $this->preferences = '{}';
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

        // Set creation date if new record
        if (empty($this->id)) {
            if (empty($this->created)) {
                $this->created = $date->toSql();
            }
        }

        // Always update modified date
        $this->modified = $date->toSql();

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

        // Check if customer has any confirmed bookings
        if ($this->hasConfirmedBookings($pk)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_HAS_BOOKINGS'));
            return false;
        }

        // Delete related records
        $this->deleteRelatedRecords($pk);

        return parent::delete($pk);
    }

    /**
     * Check if email is unique
     *
     * @return  boolean  True if unique
     */
    protected function checkEmailUnique(): bool
    {
        $db = $this->_db;
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($this->email));

        if (!empty($this->id)) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $count = $db->setQuery($query)->loadResult();

        if ($count > 0) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_EMAIL_EXISTS'));
            return false;
        }

        return true;
    }

    /**
     * Check if customer has confirmed bookings
     *
     * @param   int  $customerId  Customer ID
     * @return  boolean  True if has confirmed bookings
     */
    protected function hasConfirmedBookings($customerId): bool
    {
        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('customer_id') . ' = ' . (int) $customerId)
            ->where($db->quoteName('status') . ' IN (' . $db->quote('confirmed') . ', ' . $db->quote('completed') . ')');

        $count = $db->setQuery($query)->loadResult();
        
        return $count > 0;
    }

    /**
     * Delete related records when customer is deleted
     *
     * @param   mixed  $pk  Primary key
     * @return  void
     */
    protected function deleteRelatedRecords($pk): void
    {
        $db = $this->_db;

        try {
            // Delete reviews
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__hp_reviews'))
                ->where($db->quoteName('customer_id') . ' = ' . (int) $pk);
            $db->setQuery($query)->execute();

            // Delete cancelled/pending bookings only
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('customer_id') . ' = ' . (int) $pk)
                ->where($db->quoteName('status') . ' IN (' . $db->quote('pending') . ', ' . $db->quote('cancelled') . ')');
            $db->setQuery($query)->execute();

        } catch (Exception $e) {
            // Log error but don't fail the deletion
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_HOLIDAYPACKAGES_WARNING_CLEANUP_FAILED', $e->getMessage()),
                'warning'
            );
        }
    }

    /**
     * Handle post-save operations
     *
     * @return  void
     */
    protected function handlePostSaveOperations(): void
    {
        // Update Joomla user profile if linked
        if (!empty($this->user_id)) {
            $this->syncJoomlaUserProfile();
        }
    }

    /**
     * Sync customer data with Joomla user profile
     *
     * @return  void
     */
    protected function syncJoomlaUserProfile(): void
    {
        try {
            $user = Factory::getUser($this->user_id);
            
            if ($user->id) {
                // Update basic user fields if they match
                $needsUpdate = false;
                
                if ($user->email !== $this->email) {
                    $user->email = $this->email;
                    $needsUpdate = true;
                }
                
                $fullName = trim($this->first_name . ' ' . $this->last_name);
                if ($user->name !== $fullName) {
                    $user->name = $fullName;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    $user->save();
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the save operation
            Factory::getLog()->add(
                sprintf('Failed to sync customer %d with Joomla user %d: %s', $this->id, $this->user_id, $e->getMessage()),
                Factory::getLog()::ERROR,
                'com_holidaypackages'
            );
        }
    }

    /**
     * Get customer statistics
     *
     * @param   mixed  $pk  Primary key
     * @return  object|null  Customer statistics
     */
    public function getCustomerStats($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return null;
        }

        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select([
                'COUNT(DISTINCT b.id) as total_bookings',
                'COUNT(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN 1 END) as completed_bookings',
                'COUNT(CASE WHEN b.status = ' . $db->quote('pending') . ' THEN 1 END) as pending_bookings',
                'COUNT(CASE WHEN b.status = ' . $db->quote('cancelled') . ' THEN 1 END) as cancelled_bookings',
                'SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) as total_spent',
                'AVG(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount END) as average_booking_value',
                'MAX(b.booking_date) as last_booking_date',
                'MIN(b.booking_date) as first_booking_date',
                'COUNT(DISTINCT r.id) as review_count',
                'AVG(r.rating) as average_rating_given'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_reviews', 'r') . ' ON b.id = r.booking_id')
            ->where($db->quoteName('b.customer_id') . ' = ' . (int) $pk);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Get customer booking history
     *
     * @param   mixed  $pk     Primary key
     * @param   int    $limit  Number of records to return
     * @return  array  Booking history
     */
    public function getBookingHistory($pk = null, $limit = 10)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return [];
        }

        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'p.title as package_title',
                'p.destination',
                'p.duration'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
            ->where($db->quoteName('b.customer_id') . ' = ' . (int) $pk)
            ->order($db->quoteName('b.booking_date') . ' DESC');

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Update customer preferences
     *
     * @param   mixed  $pk           Primary key
     * @param   array  $preferences  Preferences array
     * @return  boolean  True on success
     */
    public function updatePreferences($pk = null, array $preferences = [])
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return false;
        }

        $db = $this->_db;
        
        // Get current preferences
        $query = $db->getQuery(true)
            ->select($db->quoteName('preferences'))
            ->from($db->quoteName($this->_tbl))
            ->where($db->quoteName($this->_tbl_key) . ' = ' . (int) $pk);

        $currentPrefs = $db->setQuery($query)->loadResult();
        $currentPrefs = $currentPrefs ? json_decode($currentPrefs, true) : [];

        // Merge with new preferences
        $newPrefs = array_merge($currentPrefs, $preferences);

        // Update database
        $query = $db->getQuery(true)
            ->update($db->quoteName($this->_tbl))
            ->set($db->quoteName('preferences') . ' = ' . $db->quote(json_encode($newPrefs)))
            ->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
            ->where($db->quoteName($this->_tbl_key) . ' = ' . (int) $pk);

        return $db->setQuery($query)->execute();
    }

    /**
     * Subscribe/unsubscribe from newsletter
     *
     * @param   mixed    $pk          Primary key
     * @param   boolean  $subscribe   True to subscribe, false to unsubscribe
     * @return  boolean  True on success
     */
    public function updateNewsletterSubscription($pk = null, bool $subscribe = true)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return false;
        }

        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->update($db->quoteName($this->_tbl))
            ->set($db->quoteName('newsletter_subscribed') . ' = ' . ($subscribe ? 1 : 0))
            ->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
            ->where($db->quoteName($this->_tbl_key) . ' = ' . (int) $pk);

        return $db->setQuery($query)->execute();
    }
}