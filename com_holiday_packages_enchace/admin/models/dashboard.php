<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages.admin
 * @version     2.0.0
 * @author      Holiday Packages Team
 * @copyright   Copyright (C) 2024 Holiday Packages. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Dashboard model.
 *
 * @since  2.0.0
 */
class HolidaypackagesModelDashboard extends BaseDatabaseModel
{
    /**
     * Get dashboard statistics
     *
     * @return  array  Statistics data
     *
     * @since   2.0.0
     */
    public function getStats()
    {
        $db = $this->getDbo();
        $stats = array();

        // Get date ranges
        $now = Factory::getDate();
        $thisMonthStart = Factory::getDate($now->format('Y-m-01'));
        $lastMonthStart = Factory::getDate($now->format('Y-m-01'))->sub(new DateInterval('P1M'));
        $lastMonthEnd = Factory::getDate($thisMonthStart)->sub(new DateInterval('P1D'));

        // Total packages
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $stats['total_packages'] = (int) $db->loadResult();

        // Packages created this month vs last month
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('created') . ' >= :thisMonthStart')
            ->bind(':thisMonthStart', $thisMonthStart->toSql());
        $db->setQuery($query);
        $thisMonthPackages = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('created') . ' >= :lastMonthStart')
            ->where($db->quoteName('created') . ' <= :lastMonthEnd')
            ->bind(':lastMonthStart', $lastMonthStart->toSql())
            ->bind(':lastMonthEnd', $lastMonthEnd->toSql());
        $db->setQuery($query);
        $lastMonthPackages = (int) $db->loadResult();

        $stats['packages_change'] = $this->calculatePercentageChange($thisMonthPackages, $lastMonthPackages);

        // Total bookings
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'));
        $db->setQuery($query);
        $stats['total_bookings'] = (int) $db->loadResult();

        // Bookings this month vs last month
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('created') . ' >= :thisMonthStart')
            ->bind(':thisMonthStart', $thisMonthStart->toSql());
        $db->setQuery($query);
        $thisMonthBookings = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('created') . ' >= :lastMonthStart')
            ->where($db->quoteName('created') . ' <= :lastMonthEnd')
            ->bind(':lastMonthStart', $lastMonthStart->toSql())
            ->bind(':lastMonthEnd', $lastMonthEnd->toSql());
        $db->setQuery($query);
        $lastMonthBookings = (int) $db->loadResult();

        $stats['bookings_change'] = $this->calculatePercentageChange($thisMonthBookings, $lastMonthBookings);

        // Total revenue
        $query = $db->getQuery(true)
            ->select('SUM(' . $db->quoteName('total_amount') . ')')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('payment_status') . ' = ' . $db->quote('Completed'));
        $db->setQuery($query);
        $stats['total_revenue'] = (float) $db->loadResult() ?: 0;

        // Revenue this month vs last month
        $query = $db->getQuery(true)
            ->select('SUM(' . $db->quoteName('total_amount') . ')')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('payment_status') . ' = ' . $db->quote('Completed'))
            ->where($db->quoteName('created') . ' >= :thisMonthStart')
            ->bind(':thisMonthStart', $thisMonthStart->toSql());
        $db->setQuery($query);
        $thisMonthRevenue = (float) $db->loadResult() ?: 0;

        $query = $db->getQuery(true)
            ->select('SUM(' . $db->quoteName('total_amount') . ')')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('payment_status') . ' = ' . $db->quote('Completed'))
            ->where($db->quoteName('created') . ' >= :lastMonthStart')
            ->where($db->quoteName('created') . ' <= :lastMonthEnd')
            ->bind(':lastMonthStart', $lastMonthStart->toSql())
            ->bind(':lastMonthEnd', $lastMonthEnd->toSql());
        $db->setQuery($query);
        $lastMonthRevenue = (float) $db->loadResult() ?: 0;

        $stats['revenue_change'] = $this->calculatePercentageChange($thisMonthRevenue, $lastMonthRevenue);

        // Total customers
        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('customer_id') . ')')
            ->from($db->quoteName('#__hp_bookings'));
        $db->setQuery($query);
        $stats['total_customers'] = (int) $db->loadResult();

        // New customers this month vs last month
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('created') . ' >= :thisMonthStart')
            ->bind(':thisMonthStart', $thisMonthStart->toSql());
        $db->setQuery($query);
        $thisMonthCustomers = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_customers'))
            ->where($db->quoteName('created') . ' >= :lastMonthStart')
            ->where($db->quoteName('created') . ' <= :lastMonthEnd')
            ->bind(':lastMonthStart', $lastMonthStart->toSql())
            ->bind(':lastMonthEnd', $lastMonthEnd->toSql());
        $db->setQuery($query);
        $lastMonthCustomers = (int) $db->loadResult();

        $stats['customers_change'] = $this->calculatePercentageChange($thisMonthCustomers, $lastMonthCustomers);

        // Pending bookings
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('booking_status') . ' = ' . $db->quote('Pending'));
        $db->setQuery($query);
        $stats['pending_bookings'] = (int) $db->loadResult();

        return $stats;
    }

    /**
     * Get recent bookings
     *
     * @param   int  $limit  Number of bookings to return
     *
     * @return  array  Recent bookings data
     *
     * @since   2.0.0
     */
    public function getRecentBookings($limit = 10)
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select([
                'b.id',
                'b.booking_reference',
                'b.total_amount',
                'b.currency',
                'b.booking_status',
                'b.payment_status',
                'b.created'
            ])
            ->select([
                'p.title AS package_title',
                'p.image AS package_image'
            ])
            ->select([
                'c.first_name',
                'c.last_name',
                'CONCAT(c.first_name, " ", c.last_name) AS customer_name'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->join('LEFT', $db->quoteName('#__hp_packages', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('b.package_id'))
            ->join('LEFT', $db->quoteName('#__hp_customers', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('b.customer_id'))
            ->order($db->quoteName('b.created') . ' DESC');

        $db->setQuery($query, 0, $limit);

        try {
            return $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }
    }

    /**
     * Get popular packages
     *
     * @param   int  $limit  Number of packages to return
     *
     * @return  array  Popular packages data
     *
     * @since   2.0.0
     */
    public function getPopularPackages($limit = 5)
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select([
                'p.id',
                'p.title',
                'p.image',
                'p.price_adult',
                'p.currency',
                'p.rating',
                'p.review_count',
                'p.featured'
            ])
            ->select([
                'd.title AS destination_title'
            ])
            ->select([
                'COUNT(b.id) AS booking_count',
                'SUM(b.total_amount) AS total_revenue'
            ])
            ->from($db->quoteName('#__hp_packages', 'p'))
            ->join('LEFT', $db->quoteName('#__hp_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('p.destination_id'))
            ->join('LEFT', $db->quoteName('#__hp_bookings', 'b') . ' ON ' . $db->quoteName('b.package_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('p.published') . ' = 1')
            ->group([
                'p.id',
                'p.title',
                'p.image',
                'p.price_adult',
                'p.currency',
                'p.rating',
                'p.review_count',
                'p.featured',
                'd.title'
            ])
            ->order('booking_count DESC, p.rating DESC');

        $db->setQuery($query, 0, $limit);

        try {
            return $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }
    }

    /**
     * Get revenue chart data for the last 12 months
     *
     * @return  array  Chart data
     *
     * @since   2.0.0
     */
    public function getRevenueChartData()
    {
        $db = $this->getDbo();
        $data = array(
            'labels' => array(),
            'values' => array()
        );

        // Get data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Factory::getDate()->sub(new DateInterval("P{$i}M"));
            $monthStart = $date->format('Y-m-01');
            $monthEnd = $date->format('Y-m-t');
            
            $data['labels'][] = $date->format('M Y');

            $query = $db->getQuery(true)
                ->select('SUM(' . $db->quoteName('total_amount') . ')')
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('payment_status') . ' = ' . $db->quote('Completed'))
                ->where($db->quoteName('created') . ' >= ' . $db->quote($monthStart . ' 00:00:00'))
                ->where($db->quoteName('created') . ' <= ' . $db->quote($monthEnd . ' 23:59:59'));

            $db->setQuery($query);
            $revenue = (float) $db->loadResult() ?: 0;
            $data['values'][] = $revenue;
        }

        return $data;
    }

    /**
     * Get bookings status chart data
     *
     * @return  array  Chart data
     *
     * @since   2.0.0
     */
    public function getBookingsChartData()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('booking_status'),
                'COUNT(*) AS count'
            ])
            ->from($db->quoteName('#__hp_bookings'))
            ->group($db->quoteName('booking_status'));

        $db->setQuery($query);
        $results = $db->loadObjectList();

        $data = array(
            'labels' => array(),
            'values' => array()
        );

        foreach ($results as $result) {
            $data['labels'][] = $result->booking_status;
            $data['values'][] = (int) $result->count;
        }

        return $data;
    }

    /**
     * Get packages performance chart data
     *
     * @return  array  Chart data
     *
     * @since   2.0.0
     */
    public function getPackagesChartData()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select([
                'p.title',
                'COUNT(b.id) AS booking_count'
            ])
            ->from($db->quoteName('#__hp_packages', 'p'))
            ->join('LEFT', $db->quoteName('#__hp_bookings', 'b') . ' ON ' . $db->quoteName('b.package_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('p.published') . ' = 1')
            ->group(['p.id', 'p.title'])
            ->order('booking_count DESC');

        $db->setQuery($query, 0, 10);
        $results = $db->loadObjectList();

        $data = array(
            'labels' => array(),
            'values' => array()
        );

        foreach ($results as $result) {
            $data['labels'][] = strlen($result->title) > 20 ? substr($result->title, 0, 20) . '...' : $result->title;
            $data['values'][] = (int) $result->booking_count;
        }

        return $data;
    }

    /**
     * Get component parameters
     *
     * @return  Registry  Component parameters
     *
     * @since   2.0.0
     */
    public function getParams()
    {
        return ComponentHelper::getParams('com_holidaypackages');
    }

    /**
     * Calculate percentage change between two values
     *
     * @param   float  $current   Current value
     * @param   float  $previous  Previous value
     *
     * @return  float  Percentage change
     *
     * @since   2.0.0
     */
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get system health indicators
     *
     * @return  array  Health indicators
     *
     * @since   2.0.0
     */
    public function getSystemHealth()
    {
        $db = $this->getDbo();
        $health = array();

        // Database connectivity
        try {
            $db->getVersion();
            $health['database'] = 'good';
        } catch (Exception $e) {
            $health['database'] = 'error';
        }

        // Check for failed payments in the last 24 hours
        $yesterday = Factory::getDate()->sub(new DateInterval('P1D'))->toSql();
        
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('Failed'))
            ->where($db->quoteName('created') . ' >= :yesterday')
            ->bind(':yesterday', $yesterday);

        $db->setQuery($query);
        $failedPayments = (int) $db->loadResult();

        $health['payments'] = $failedPayments > 10 ? 'warning' : 'good';

        // Check for old pending bookings
        $weekAgo = Factory::getDate()->sub(new DateInterval('P7D'))->toSql();
        
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('booking_status') . ' = ' . $db->quote('Pending'))
            ->where($db->quoteName('created') . ' <= :weekAgo')
            ->bind(':weekAgo', $weekAgo);

        $db->setQuery($query);
        $oldPendingBookings = (int) $db->loadResult();

        $health['bookings'] = $oldPendingBookings > 5 ? 'warning' : 'good';

        return $health;
    }
}