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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

/**
 * Admin Customers List Model for Holiday Packages Enhanced Component
 * 
 * Manages customer data listing, searching, filtering, and statistics
 * for the admin backend.
 */
class HolidayPackagesModelCustomers extends ListModel
{
    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'c.id',
                'first_name', 'c.first_name',
                'last_name', 'c.last_name',
                'email', 'c.email',
                'phone', 'c.phone',
                'city', 'c.city',
                'country', 'c.country',
                'total_bookings',
                'total_spent',
                'last_booking_date',
                'created', 'c.created',
                'search'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state
     *
     * @param   string  $ordering   Column to order by
     * @param   string  $direction  Direction to order by
     * @return  void
     */
    protected function populateState($ordering = 'c.id', $direction = 'desc')
    {
        // Load the filter search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Load filter by country
        $country = $this->getUserStateFromRequest($this->context . '.filter.country', 'filter_country', '', 'string');
        $this->setState('filter.country', $country);

        // Load filter by registration date range
        $dateFrom = $this->getUserStateFromRequest($this->context . '.filter.date_from', 'filter_date_from', '', 'string');
        $this->setState('filter.date_from', $dateFrom);

        $dateTo = $this->getUserStateFromRequest($this->context . '.filter.date_to', 'filter_date_to', '', 'string');
        $this->setState('filter.date_to', $dateTo);

        // Load filter by booking count
        $minBookings = $this->getUserStateFromRequest($this->context . '.filter.min_bookings', 'filter_min_bookings', '', 'int');
        $this->setState('filter.min_bookings', $minBookings);

        // Load filter by total spent range
        $minSpent = $this->getUserStateFromRequest($this->context . '.filter.min_spent', 'filter_min_spent', '', 'float');
        $this->setState('filter.min_spent', $minSpent);

        $maxSpent = $this->getUserStateFromRequest($this->context . '.filter.max_spent', 'filter_max_spent', '', 'float');
        $this->setState('filter.max_spent', $maxSpent);

        // Load the default list behavior
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state
     *
     * @param   string  $id  A prefix for the store id
     * @return  string  A store id
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.country');
        $id .= ':' . $this->getState('filter.date_from');
        $id .= ':' . $this->getState('filter.date_to');
        $id .= ':' . $this->getState('filter.min_bookings');
        $id .= ':' . $this->getState('filter.min_spent');
        $id .= ':' . $this->getState('filter.max_spent');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data
     *
     * @return  DatabaseQuery
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select customer fields and aggregated booking data
        $query->select([
            'c.*',
            'u.username',
            'COUNT(DISTINCT b.id) as total_bookings',
            'SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) as total_spent',
            'MAX(b.booking_date) as last_booking_date',
            'AVG(r.rating) as average_rating',
            'COUNT(DISTINCT r.id) as review_count'
        ]);

        $query->from($db->quoteName('#__hp_customers', 'c'));
        
        // Join with users table for registered customers
        $query->leftJoin($db->quoteName('#__users', 'u') . ' ON c.user_id = u.id');
        
        // Join with bookings for statistics
        $query->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON c.id = b.customer_id');
        
        // Join with reviews for rating information
        $query->leftJoin($db->quoteName('#__hp_reviews', 'r') . ' ON c.id = r.customer_id');

        // Filter by search term
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('c.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '(' . $db->quoteName('c.first_name') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.last_name') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.email') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.phone') . ' LIKE ' . $search .
                    ' OR CONCAT(' . $db->quoteName('c.first_name') . ', " ", ' . $db->quoteName('c.last_name') . ') LIKE ' . $search . ')'
                );
            }
        }

        // Filter by country
        $country = $this->getState('filter.country');
        if (!empty($country)) {
            $query->where($db->quoteName('c.country') . ' = :country')
                  ->bind(':country', $country);
        }

        // Filter by registration date range
        $dateFrom = $this->getState('filter.date_from');
        if (!empty($dateFrom)) {
            $query->where($db->quoteName('c.created') . ' >= :dateFrom')
                  ->bind(':dateFrom', $dateFrom . ' 00:00:00');
        }

        $dateTo = $this->getState('filter.date_to');
        if (!empty($dateTo)) {
            $query->where($db->quoteName('c.created') . ' <= :dateTo')
                  ->bind(':dateTo', $dateTo . ' 23:59:59');
        }

        // Group by customer to get aggregated data
        $query->group([
            'c.id',
            'c.user_id',
            'c.first_name',
            'c.last_name',
            'c.email',
            'c.phone',
            'c.address',
            'c.city',
            'c.state',
            'c.country',
            'c.pincode',
            'c.date_of_birth',
            'c.preferences',
            'c.newsletter_subscribed',
            'c.created',
            'c.modified',
            'u.username'
        ]);

        // Filter by minimum bookings (HAVING clause since it's aggregated)
        $minBookings = $this->getState('filter.min_bookings');
        if (!empty($minBookings)) {
            $query->having('COUNT(DISTINCT b.id) >= :minBookings')
                  ->bind(':minBookings', $minBookings, ParameterType::INTEGER);
        }

        // Filter by total spent range (HAVING clause since it's aggregated)
        $minSpent = $this->getState('filter.min_spent');
        if (!empty($minSpent)) {
            $query->having('SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) >= :minSpent')
                  ->bind(':minSpent', $minSpent, ParameterType::FLOAT);
        }

        $maxSpent = $this->getState('filter.max_spent');
        if (!empty($maxSpent)) {
            $query->having('SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) <= :maxSpent')
                  ->bind(':maxSpent', $maxSpent, ParameterType::FLOAT);
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'c.id');
        $orderDirn = $this->state->get('list.direction', 'desc');

        // Handle special ordering cases for aggregated fields
        if ($orderCol === 'total_bookings') {
            $query->order('COUNT(DISTINCT b.id) ' . $db->escape($orderDirn));
        } elseif ($orderCol === 'total_spent') {
            $query->order('SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) ' . $db->escape($orderDirn));
        } elseif ($orderCol === 'last_booking_date') {
            $query->order('MAX(b.booking_date) ' . $db->escape($orderDirn));
        } elseif ($orderCol === 'average_rating') {
            $query->order('AVG(r.rating) ' . $db->escape($orderDirn));
        } else {
            $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        }

        return $query;
    }

    /**
     * Get customer statistics
     *
     * @return  object  Statistics data
     */
    public function getCustomerStats(): object
    {
        $db = $this->getDatabase();

        // Total customers
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_customers'));
        $totalCustomers = $db->setQuery($query)->loadResult();

        // New customers this month
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('created') . ' >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)');
        $newCustomersThisMonth = $db->setQuery($query)->loadResult();

        // Customers with bookings
        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT customer_id)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('status') . ' IN (' . $db->quote('confirmed') . ', ' . $db->quote('completed') . ')');
        $activeCustomers = $db->setQuery($query)->loadResult();

        // Top spending customers
        $query = $db->getQuery(true)
            ->select([
                'c.first_name',
                'c.last_name',
                'c.email',
                'SUM(b.total_amount) as total_spent',
                'COUNT(b.id) as booking_count'
            ])
            ->from($db->quoteName('#__hp_customers', 'c'))
            ->innerJoin($db->quoteName('#__hp_bookings', 'b') . ' ON c.id = b.customer_id')
            ->where($db->quoteName('b.status') . ' = ' . $db->quote('completed'))
            ->group('c.id')
            ->order('total_spent DESC')
            ->setLimit(5);
        $topCustomers = $db->setQuery($query)->loadObjectList();

        // Customer acquisition by month (last 12 months)
        $query = $db->getQuery(true)
            ->select([
                'DATE_FORMAT(created, "%Y-%m") as month',
                'COUNT(*) as count'
            ])
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('created') . ' >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)')
            ->group('DATE_FORMAT(created, "%Y-%m")')
            ->order('month ASC');
        $acquisitionByMonth = $db->setQuery($query)->loadObjectList();

        // Countries with most customers
        $query = $db->getQuery(true)
            ->select([
                'country',
                'COUNT(*) as customer_count'
            ])
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('country') . ' IS NOT NULL')
            ->where($db->quoteName('country') . ' != ' . $db->quote(''))
            ->group('country')
            ->order('customer_count DESC')
            ->setLimit(10);
        $topCountries = $db->setQuery($query)->loadObjectList();

        return (object) [
            'total_customers' => (int) $totalCustomers,
            'new_customers_this_month' => (int) $newCustomersThisMonth,
            'active_customers' => (int) $activeCustomers,
            'conversion_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0,
            'top_customers' => $topCustomers,
            'acquisition_by_month' => $acquisitionByMonth,
            'top_countries' => $topCountries
        ];
    }

    /**
     * Get available countries for filter dropdown
     *
     * @return  array  List of countries
     */
    public function getCountries(): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'DISTINCT ' . $db->quoteName('country'),
                'COUNT(*) as customer_count'
            ])
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('country') . ' IS NOT NULL')
            ->where($db->quoteName('country') . ' != ' . $db->quote(''))
            ->group($db->quoteName('country'))
            ->order($db->quoteName('country') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Export customer data to CSV
     *
     * @param   array  $customerIds  Optional array of specific customer IDs to export
     * @return  array  Export result
     */
    public function exportToCSV($customerIds = null): array
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            // Build export query
            $query->select([
                'c.id',
                'c.first_name',
                'c.last_name',
                'c.email',
                'c.phone',
                'c.address',
                'c.city',
                'c.state',
                'c.country',
                'c.pincode',
                'c.date_of_birth',
                'c.newsletter_subscribed',
                'c.created',
                'COUNT(DISTINCT b.id) as total_bookings',
                'SUM(CASE WHEN b.status = ' . $db->quote('completed') . ' THEN b.total_amount ELSE 0 END) as total_spent',
                'MAX(b.booking_date) as last_booking_date'
            ])
            ->from($db->quoteName('#__hp_customers', 'c'))
            ->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON c.id = b.customer_id');

            if ($customerIds && is_array($customerIds)) {
                $customerIds = array_map('intval', $customerIds);
                $query->where($db->quoteName('c.id') . ' IN (' . implode(',', $customerIds) . ')');
            }

            $query->group('c.id')
                  ->order('c.id ASC');

            $customers = $db->setQuery($query)->loadObjectList();

            // Generate CSV content
            $csvData = [];
            $csvData[] = [
                'ID',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Address',
                'City',
                'State',
                'Country',
                'Pincode',
                'Date of Birth',
                'Newsletter Subscribed',
                'Registration Date',
                'Total Bookings',
                'Total Spent',
                'Last Booking Date'
            ];

            foreach ($customers as $customer) {
                $csvData[] = [
                    $customer->id,
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email,
                    $customer->phone,
                    $customer->address,
                    $customer->city,
                    $customer->state,
                    $customer->country,
                    $customer->pincode,
                    $customer->date_of_birth,
                    $customer->newsletter_subscribed ? 'Yes' : 'No',
                    $customer->created,
                    $customer->total_bookings,
                    '₹' . number_format((float) $customer->total_spent, 2),
                    $customer->last_booking_date ?: 'Never'
                ];
            }

            // Convert to CSV string
            $output = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return [
                'success' => true,
                'data' => $csv,
                'filename' => 'customers_export_' . date('Y-m-d_H-i-s') . '.csv',
                'count' => count($customers)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_EXPORT_FAILED', $e->getMessage())
            ];
        }
    }

    /**
     * Delete customers and their associated data
     *
     * @param   array  $pks  Primary keys of customers to delete
     * @return  boolean  True on success
     */
    public function delete($pks)
    {
        $db = $this->getDatabase();
        $pks = (array) $pks;

        try {
            $db->transactionStart();

            foreach ($pks as $pk) {
                // Check if customer has any confirmed/completed bookings
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__hp_bookings'))
                    ->where($db->quoteName('customer_id') . ' = :customerId')
                    ->where($db->quoteName('status') . ' IN (' . $db->quote('confirmed') . ', ' . $db->quote('completed') . ')')
                    ->bind(':customerId', $pk, ParameterType::INTEGER);

                $hasActiveBookings = $db->setQuery($query)->loadResult();

                if ($hasActiveBookings > 0) {
                    throw new Exception(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_CUSTOMER_HAS_BOOKINGS', $pk));
                }

                // Delete customer reviews
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_reviews'))
                    ->where($db->quoteName('customer_id') . ' = :customerId')
                    ->bind(':customerId', $pk, ParameterType::INTEGER);
                $db->setQuery($query)->execute();

                // Delete customer bookings (only pending/cancelled)
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_bookings'))
                    ->where($db->quoteName('customer_id') . ' = :customerId')
                    ->where($db->quoteName('status') . ' IN (' . $db->quote('pending') . ', ' . $db->quote('cancelled') . ')')
                    ->bind(':customerId', $pk, ParameterType::INTEGER);
                $db->setQuery($query)->execute();

                // Delete customer record
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_customers'))
                    ->where($db->quoteName('id') . ' = :customerId')
                    ->bind(':customerId', $pk, ParameterType::INTEGER);
                $db->setQuery($query)->execute();
            }

            $db->transactionCommit();
            return true;

        } catch (Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
}