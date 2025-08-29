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
 * Admin Payments List Model for Holiday Packages Enhanced Component
 * 
 * Manages payment transactions listing, filtering, and financial reporting
 * for the admin backend.
 */
class HolidayPackagesModelPayments extends ListModel
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
                'id', 'p.id',
                'booking_reference', 'b.booking_reference',
                'customer_name',
                'amount', 'p.amount',
                'gateway', 'p.gateway',
                'status', 'p.status',
                'transaction_id', 'p.transaction_id',
                'payment_date', 'p.payment_date',
                'created', 'p.created',
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
    protected function populateState($ordering = 'p.id', $direction = 'desc')
    {
        // Load the filter search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Load filter by status
        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
        $this->setState('filter.status', $status);

        // Load filter by gateway
        $gateway = $this->getUserStateFromRequest($this->context . '.filter.gateway', 'filter_gateway', '', 'string');
        $this->setState('filter.gateway', $gateway);

        // Load filter by date range
        $dateFrom = $this->getUserStateFromRequest($this->context . '.filter.date_from', 'filter_date_from', '', 'string');
        $this->setState('filter.date_from', $dateFrom);

        $dateTo = $this->getUserStateFromRequest($this->context . '.filter.date_to', 'filter_date_to', '', 'string');
        $this->setState('filter.date_to', $dateTo);

        // Load filter by amount range
        $amountFrom = $this->getUserStateFromRequest($this->context . '.filter.amount_from', 'filter_amount_from', '', 'float');
        $this->setState('filter.amount_from', $amountFrom);

        $amountTo = $this->getUserStateFromRequest($this->context . '.filter.amount_to', 'filter_amount_to', '', 'float');
        $this->setState('filter.amount_to', $amountTo);

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
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.gateway');
        $id .= ':' . $this->getState('filter.date_from');
        $id .= ':' . $this->getState('filter.date_to');
        $id .= ':' . $this->getState('filter.amount_from');
        $id .= ':' . $this->getState('filter.amount_to');

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

        // Select payment fields with related booking and customer data
        $query->select([
            'p.*',
            'b.booking_reference',
            'b.package_id',
            'pkg.title as package_title',
            'CONCAT(c.first_name, " ", c.last_name) as customer_name',
            'c.email as customer_email',
            'c.phone as customer_phone'
        ]);

        $query->from($db->quoteName('#__hp_payments', 'p'));
        
        // Join with bookings
        $query->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON p.booking_id = b.id');
        
        // Join with customers
        $query->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id');
        
        // Join with packages
        $query->leftJoin($db->quoteName('#__hp_packages', 'pkg') . ' ON b.package_id = pkg.id');

        // Filter by search term
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('p.id') . ' = ' . (int) substr($search, 3));
            } elseif (stripos($search, 'txn:') === 0) {
                $transactionId = $db->quote('%' . $db->escape(substr($search, 4), true) . '%');
                $query->where($db->quoteName('p.transaction_id') . ' LIKE ' . $transactionId);
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '(' . $db->quoteName('b.booking_reference') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('p.transaction_id') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.first_name') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.last_name') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.email') . ' LIKE ' . $search .
                    ' OR CONCAT(' . $db->quoteName('c.first_name') . ', " ", ' . $db->quoteName('c.last_name') . ') LIKE ' . $search . ')'
                );
            }
        }

        // Filter by status
        $status = $this->getState('filter.status');
        if (!empty($status)) {
            $query->where($db->quoteName('p.status') . ' = :status')
                  ->bind(':status', $status);
        }

        // Filter by gateway
        $gateway = $this->getState('filter.gateway');
        if (!empty($gateway)) {
            $query->where($db->quoteName('p.gateway') . ' = :gateway')
                  ->bind(':gateway', $gateway);
        }

        // Filter by date range
        $dateFrom = $this->getState('filter.date_from');
        if (!empty($dateFrom)) {
            $query->where($db->quoteName('p.payment_date') . ' >= :dateFrom')
                  ->bind(':dateFrom', $dateFrom . ' 00:00:00');
        }

        $dateTo = $this->getState('filter.date_to');
        if (!empty($dateTo)) {
            $query->where($db->quoteName('p.payment_date') . ' <= :dateTo')
                  ->bind(':dateTo', $dateTo . ' 23:59:59');
        }

        // Filter by amount range
        $amountFrom = $this->getState('filter.amount_from');
        if (!empty($amountFrom)) {
            $query->where($db->quoteName('p.amount') . ' >= :amountFrom')
                  ->bind(':amountFrom', $amountFrom, ParameterType::FLOAT);
        }

        $amountTo = $this->getState('filter.amount_to');
        if (!empty($amountTo)) {
            $query->where($db->quoteName('p.amount') . ' <= :amountTo')
                  ->bind(':amountTo', $amountTo, ParameterType::FLOAT);
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'p.id');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Get payment statistics for dashboard
     *
     * @return  object  Statistics data
     */
    public function getPaymentStats(): object
    {
        $db = $this->getDatabase();

        // Total payments and revenue
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as total_payments',
                'SUM(amount) as total_revenue',
                'AVG(amount) as average_payment'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('completed'));
        $totals = $db->setQuery($query)->loadObject();

        // Successful payments this month
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as count',
                'SUM(amount) as revenue'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('completed'))
            ->where($db->quoteName('payment_date') . ' >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)');
        $thisMonth = $db->setQuery($query)->loadObject();

        // Failed payments count
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('failed'));
        $failedPayments = $db->setQuery($query)->loadResult();

        // Pending payments
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as count',
                'SUM(amount) as amount'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('pending'));
        $pending = $db->setQuery($query)->loadObject();

        // Payment gateway distribution
        $query = $db->getQuery(true)
            ->select([
                'gateway',
                'COUNT(*) as payment_count',
                'SUM(amount) as total_amount'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('completed'))
            ->group('gateway')
            ->order('total_amount DESC');
        $gatewayStats = $db->setQuery($query)->loadObjectList();

        // Monthly revenue trend (last 12 months)
        $query = $db->getQuery(true)
            ->select([
                'DATE_FORMAT(payment_date, "%Y-%m") as month',
                'SUM(amount) as revenue',
                'COUNT(*) as transactions'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('completed'))
            ->where($db->quoteName('payment_date') . ' >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)')
            ->group('DATE_FORMAT(payment_date, "%Y-%m")')
            ->order('month ASC');
        $monthlyRevenue = $db->setQuery($query)->loadObjectList();

        // Recent failed payments for analysis
        $query = $db->getQuery(true)
            ->select([
                'p.*',
                'b.booking_reference',
                'CONCAT(c.first_name, " ", c.last_name) as customer_name'
            ])
            ->from($db->quoteName('#__hp_payments', 'p'))
            ->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON p.booking_id = b.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id')
            ->where($db->quoteName('p.status') . ' = ' . $db->quote('failed'))
            ->order($db->quoteName('p.created') . ' DESC')
            ->setLimit(10);
        $recentFailures = $db->setQuery($query)->loadObjectList();

        // Calculate success rate
        $totalAttempts = ((int) $totals->total_payments) + ((int) $failedPayments);
        $successRate = $totalAttempts > 0 ? round(((int) $totals->total_payments / $totalAttempts) * 100, 2) : 0;

        return (object) [
            'total_payments' => (int) $totals->total_payments,
            'total_revenue' => (float) $totals->total_revenue,
            'average_payment' => (float) $totals->average_payment,
            'payments_this_month' => (int) $thisMonth->count,
            'revenue_this_month' => (float) $thisMonth->revenue,
            'failed_payments' => (int) $failedPayments,
            'pending_payments_count' => (int) $pending->count,
            'pending_payments_amount' => (float) $pending->amount,
            'success_rate' => $successRate,
            'gateway_stats' => $gatewayStats,
            'monthly_revenue' => $monthlyRevenue,
            'recent_failures' => $recentFailures
        ];
    }

    /**
     * Get payment gateways for filter dropdown
     *
     * @return  array  List of gateways
     */
    public function getGateways(): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'DISTINCT ' . $db->quoteName('gateway'),
                'COUNT(*) as payment_count'
            ])
            ->from($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('gateway') . ' IS NOT NULL')
            ->where($db->quoteName('gateway') . ' != ' . $db->quote(''))
            ->group($db->quoteName('gateway'))
            ->order($db->quoteName('gateway') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Generate financial report for given period
     *
     * @param   string  $period  Report period (daily, weekly, monthly, yearly)
     * @param   string  $from    Start date
     * @param   string  $to      End date
     * @return  array   Report data
     */
    public function generateFinancialReport(string $period = 'monthly', ?string $from = null, ?string $to = null): array
    {
        $db = $this->getDatabase();

        // Set default date range if not provided
        if (empty($from)) {
            $from = date('Y-m-01', strtotime('-11 months')); // Last 12 months
        }
        if (empty($to)) {
            $to = date('Y-m-d');
        }

        // Determine date format based on period
        $dateFormats = [
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            'yearly' => '%Y'
        ];

        $dateFormat = $dateFormats[$period] ?? $dateFormats['monthly'];

        try {
            // Main revenue query
            $query = $db->getQuery(true)
                ->select([
                    'DATE_FORMAT(payment_date, "' . $dateFormat . '") as period',
                    'COUNT(*) as transaction_count',
                    'SUM(amount) as gross_revenue',
                    'SUM(CASE WHEN gateway = "paypal" THEN amount * 0.029 ELSE amount * 0.025 END) as gateway_fees',
                    'SUM(amount) - SUM(CASE WHEN gateway = "paypal" THEN amount * 0.029 ELSE amount * 0.025 END) as net_revenue',
                    'AVG(amount) as average_transaction'
                ])
                ->from($db->quoteName('#__hp_payments'))
                ->where($db->quoteName('status') . ' = ' . $db->quote('completed'))
                ->where($db->quoteName('payment_date') . ' >= :from')
                ->where($db->quoteName('payment_date') . ' <= :to')
                ->group('DATE_FORMAT(payment_date, "' . $dateFormat . '")')
                ->order('period ASC')
                ->bind(':from', $from)
                ->bind(':to', $to);

            $revenueData = $db->setQuery($query)->loadObjectList();

            // Gateway breakdown
            $query = $db->getQuery(true)
                ->select([
                    'DATE_FORMAT(payment_date, "' . $dateFormat . '") as period',
                    'gateway',
                    'COUNT(*) as count',
                    'SUM(amount) as revenue'
                ])
                ->from($db->quoteName('#__hp_payments'))
                ->where($db->quoteName('status') . ' = ' . $db->quote('completed'))
                ->where($db->quoteName('payment_date') . ' >= :from')
                ->where($db->quoteName('payment_date') . ' <= :to')
                ->group(['DATE_FORMAT(payment_date, "' . $dateFormat . '")', 'gateway'])
                ->order(['period ASC', 'gateway ASC'])
                ->bind(':from', $from)
                ->bind(':to', $to);

            $gatewayBreakdown = $db->setQuery($query)->loadObjectList();

            // Refunds data
            $query = $db->getQuery(true)
                ->select([
                    'DATE_FORMAT(payment_date, "' . $dateFormat . '") as period',
                    'COUNT(*) as refund_count',
                    'SUM(amount) as refund_amount'
                ])
                ->from($db->quoteName('#__hp_payments'))
                ->where($db->quoteName('status') . ' = ' . $db->quote('refunded'))
                ->where($db->quoteName('payment_date') . ' >= :from')
                ->where($db->quoteName('payment_date') . ' <= :to')
                ->group('DATE_FORMAT(payment_date, "' . $dateFormat . '")')
                ->order('period ASC')
                ->bind(':from', $from)
                ->bind(':to', $to);

            $refundsData = $db->setQuery($query)->loadObjectList();

            // Failed payments analysis
            $query = $db->getQuery(true)
                ->select([
                    'DATE_FORMAT(created, "' . $dateFormat . '") as period',
                    'COUNT(*) as failed_count',
                    'gateway',
                    'failure_reason'
                ])
                ->from($db->quoteName('#__hp_payments'))
                ->where($db->quoteName('status') . ' = ' . $db->quote('failed'))
                ->where($db->quoteName('created') . ' >= :from')
                ->where($db->quoteName('created') . ' <= :to')
                ->group(['DATE_FORMAT(created, "' . $dateFormat . '")', 'gateway', 'failure_reason'])
                ->order('period ASC')
                ->bind(':from', $from)
                ->bind(':to', $to);

            $failuresData = $db->setQuery($query)->loadObjectList();

            // Calculate totals
            $totalRevenue = array_sum(array_column($revenueData, 'gross_revenue'));
            $totalTransactions = array_sum(array_column($revenueData, 'transaction_count'));
            $totalFees = array_sum(array_column($revenueData, 'gateway_fees'));
            $totalRefunds = array_sum(array_column($refundsData, 'refund_amount'));
            $totalFailed = array_sum(array_column($failuresData, 'failed_count'));

            return [
                'success' => true,
                'period' => $period,
                'date_range' => ['from' => $from, 'to' => $to],
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'total_fees' => $totalFees,
                    'net_revenue' => $totalRevenue - $totalFees,
                    'total_refunds' => $totalRefunds,
                    'total_failed' => $totalFailed,
                    'average_transaction' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
                    'success_rate' => ($totalTransactions + $totalFailed) > 0 ? 
                        round(($totalTransactions / ($totalTransactions + $totalFailed)) * 100, 2) : 0
                ],
                'revenue_data' => $revenueData,
                'gateway_breakdown' => $gatewayBreakdown,
                'refunds_data' => $refundsData,
                'failures_data' => $failuresData
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_REPORT_GENERATION', $e->getMessage())
            ];
        }
    }

    /**
     * Process refund for a payment
     *
     * @param   int     $paymentId     Payment ID
     * @param   float   $refundAmount  Refund amount
     * @param   string  $reason        Refund reason
     * @return  array   Result data
     */
    public function processRefund(int $paymentId, float $refundAmount, string $reason = ''): array
    {
        $db = $this->getDatabase();

        try {
            $db->transactionStart();

            // Get payment details
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__hp_payments'))
                ->where($db->quoteName('id') . ' = :paymentId')
                ->bind(':paymentId', $paymentId, ParameterType::INTEGER);

            $payment = $db->setQuery($query)->loadObject();

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($payment->status !== 'completed') {
                throw new Exception('Can only refund completed payments');
            }

            if ($refundAmount > $payment->amount) {
                throw new Exception('Refund amount cannot exceed payment amount');
            }

            // Update payment status
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_payments'))
                ->set([
                    $db->quoteName('status') . ' = ' . $db->quote('refunded'),
                    $db->quoteName('refund_amount') . ' = :refundAmount',
                    $db->quoteName('refund_reason') . ' = :reason',
                    $db->quoteName('refunded_at') . ' = NOW()'
                ])
                ->where($db->quoteName('id') . ' = :paymentId')
                ->bind(':refundAmount', $refundAmount, ParameterType::FLOAT)
                ->bind(':reason', $reason)
                ->bind(':paymentId', $paymentId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            // Here you would integrate with actual payment gateway refund API
            // For now, we'll just log the refund request

            $db->transactionCommit();

            return [
                'success' => true,
                'message' => Text::_('COM_HOLIDAYPACKAGES_REFUND_PROCESSED'),
                'refund_amount' => $refundAmount
            ];

        } catch (Exception $e) {
            $db->transactionRollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}