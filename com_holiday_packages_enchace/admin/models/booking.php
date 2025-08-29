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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;

/**
 * Admin Booking Model for Holiday Packages Enhanced Component
 * 
 * Handles CRUD operations for individual bookings, payment processing,
 * booking confirmations, cancellations, and email notifications.
 */
class HolidayPackagesModelBooking extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     */
    public $typeAlias = 'com_holidaypackages.booking';

    /**
     * The prefix to use with controller messages
     *
     * @var    string
     */
    protected $text_prefix = 'COM_HOLIDAYPACKAGES_BOOKING';

    /**
     * Batch copy/move command. If set to false, the batch copy/move command is not supported
     *
     * @var    string
     */
    protected $batch_copymove = false;

    /**
     * Allowed batch commands
     *
     * @var    array
     */
    protected $batch_commands = [
        'status' => 'batchStatus'
    ];

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
        $data = $app->getUserState('com_holidaypackages.edit.booking.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_holidaypackages.booking', $data);

        return $data;
    }

    /**
     * Method to get a table object, load it if necessary
     *
     * @param   string  $type    The table name. Optional
     * @param   string  $prefix  The class prefix. Optional
     * @param   array   $config  Configuration array for model. Optional
     * @return  Table   A Table object
     */
    public function getTable($type = 'Booking', $prefix = 'HolidayPackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Method to auto-populate the model state
     *
     * Note: Calling getState in this method will result in recursion
     *
     * @param   Form   $form   The form to validate against
     * @param   array  $data   The data to validate
     * @param   string $group  The name of the field group to validate
     * @return  mixed  Array of filtered data if valid, false otherwise
     */
    public function validate($form, $data, $group = null)
    {
        // Validate booking dates
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = Factory::getDate($data['start_date']);
            $endDate = Factory::getDate($data['end_date']);
            
            if ($startDate >= $endDate) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_DATES'));
                return false;
            }
        }

        // Validate traveler count
        if (isset($data['adults']) && $data['adults'] < 1) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_ADULT_COUNT'));
            return false;
        }

        // Validate email format
        if (!empty($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_EMAIL'));
            return false;
        }

        return parent::validate($form, $data, $group);
    }

    /**
     * Method to save the form data
     *
     * @param   array  $data  The form data
     * @return  boolean  True on success
     */
    public function save($data)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $task = $input->getCmd('task');

        // Generate booking reference if new booking
        if (empty($data['id']) && empty($data['booking_reference'])) {
            $data['booking_reference'] = $this->generateBookingReference();
        }

        // Calculate total amount if not provided
        if (empty($data['total_amount']) && !empty($data['package_id'])) {
            $data['total_amount'] = $this->calculateBookingTotal($data);
        }

        // Set booking date if new
        if (empty($data['id'])) {
            $data['booking_date'] = Factory::getDate()->toSql();
            $data['status'] = $data['status'] ?? 'pending';
        }

        // Store previous status for notification logic
        $previousStatus = null;
        if (!empty($data['id'])) {
            $table = $this->getTable();
            $table->load($data['id']);
            $previousStatus = $table->status;
        }

        // Save the booking
        $result = parent::save($data);

        if ($result && !empty($data['id'])) {
            $bookingId = $data['id'];
            $currentStatus = $data['status'];

            // Handle status change notifications
            if ($previousStatus !== $currentStatus) {
                $this->handleStatusChangeNotification($bookingId, $previousStatus, $currentStatus);
            }

            // Handle payment processing for confirmed bookings
            if ($currentStatus === 'confirmed' && $previousStatus !== 'confirmed') {
                $this->processBookingConfirmation($bookingId);
            }

            // Handle cancellation logic
            if ($currentStatus === 'cancelled' && $previousStatus !== 'cancelled') {
                $this->processBookingCancellation($bookingId);
            }
        }

        return $result;
    }

    /**
     * Method to delete one or more records
     *
     * @param   array  $pks  An array of record primary keys
     * @return  boolean  True if successful, false if an error occurs
     */
    public function delete(&$pks)
    {
        $db = $this->getDatabase();
        $pks = (array) $pks;

        foreach ($pks as $pk) {
            // Check if booking can be deleted (only pending/cancelled bookings)
            $query = $db->getQuery(true)
                ->select($db->quoteName('status'))
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('id') . ' = :pk')
                ->bind(':pk', $pk, ParameterType::INTEGER);

            $status = $db->setQuery($query)->loadResult();

            if (!in_array($status, ['pending', 'cancelled'])) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CANNOT_DELETE_CONFIRMED_BOOKING'));
                return false;
            }

            // Delete related payment records
            $this->deleteBookingPayments($pk);

            // Delete related traveler records
            $this->deleteBookingTravelers($pk);
        }

        return parent::delete($pks);
    }

    /**
     * Generate a unique booking reference
     *
     * @return  string  The booking reference
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
     * Calculate booking total amount
     *
     * @param   array  $data  Booking data
     * @return  float  Total amount
     */
    protected function calculateBookingTotal(array $data): float
    {
        $db = $this->getDatabase();
        
        // Get package price
        $query = $db->getQuery(true)
            ->select($db->quoteName('price'))
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('id') . ' = :packageId')
            ->bind(':packageId', $data['package_id'], ParameterType::INTEGER);

        $packagePrice = (float) $db->setQuery($query)->loadResult();

        // Calculate base amount
        $adults = (int) ($data['adults'] ?? 1);
        $children = (int) ($data['children'] ?? 0);
        $infants = (int) ($data['infants'] ?? 0);

        $baseAmount = ($adults * $packagePrice) + 
                     ($children * $packagePrice * 0.75) + 
                     ($infants * $packagePrice * 0.1);

        // Apply extras
        $extrasAmount = 0;
        if (!empty($data['extras']) && is_array($data['extras'])) {
            foreach ($data['extras'] as $extra) {
                $extrasAmount += (float) ($extra['price'] ?? 0) * (int) ($extra['quantity'] ?? 1);
            }
        }

        // Calculate taxes (assuming 18% GST)
        $taxRate = 0.18;
        $taxAmount = ($baseAmount + $extrasAmount) * $taxRate;

        return $baseAmount + $extrasAmount + $taxAmount;
    }

    /**
     * Handle booking status change notification
     *
     * @param   int     $bookingId       Booking ID
     * @param   string  $previousStatus  Previous status
     * @param   string  $currentStatus   Current status
     * @return  void
     */
    protected function handleStatusChangeNotification(int $bookingId, ?string $previousStatus, string $currentStatus): void
    {
        try {
            // Get booking details
            $booking = $this->getBookingDetails($bookingId);
            
            if (!$booking) {
                return;
            }

            // Determine email template based on status
            $templates = [
                'confirmed' => 'booking_confirmed',
                'cancelled' => 'booking_cancelled',
                'completed' => 'booking_completed',
                'refunded' => 'booking_refunded'
            ];

            if (!isset($templates[$currentStatus])) {
                return;
            }

            // Send notification email
            $this->sendBookingNotificationEmail(
                $booking, 
                $templates[$currentStatus], 
                $currentStatus
            );

            // Log the notification
            Log::add(
                sprintf('Booking notification sent for booking %d - Status: %s', $bookingId, $currentStatus),
                Log::INFO,
                'com_holidaypackages'
            );

        } catch (Exception $e) {
            Log::add(
                sprintf('Failed to send booking notification for booking %d: %s', $bookingId, $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );
        }
    }

    /**
     * Process booking confirmation
     *
     * @param   int  $bookingId  Booking ID
     * @return  void
     */
    protected function processBookingConfirmation(int $bookingId): void
    {
        $db = $this->getDatabase();
        
        try {
            // Update package booking count
            $query = $db->getQuery(true)
                ->select($db->quoteName('package_id'))
                ->from($db->quoteName('#__hp_bookings'))
                ->where($db->quoteName('id') . ' = :bookingId')
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

            $packageId = $db->setQuery($query)->loadResult();

            if ($packageId) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__hp_packages'))
                    ->set($db->quoteName('bookings_count') . ' = ' . $db->quoteName('bookings_count') . ' + 1')
                    ->where($db->quoteName('id') . ' = :packageId')
                    ->bind(':packageId', $packageId, ParameterType::INTEGER);

                $db->setQuery($query)->execute();
            }

            // Trigger confirmation events
            PluginHelper::importPlugin('holidaypackages');
            Factory::getApplication()->triggerEvent('onHolidayPackagesBookingConfirmed', [$bookingId]);

        } catch (Exception $e) {
            Log::add(
                sprintf('Error processing booking confirmation for booking %d: %s', $bookingId, $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );
        }
    }

    /**
     * Process booking cancellation
     *
     * @param   int  $bookingId  Booking ID
     * @return  void
     */
    protected function processBookingCancellation(int $bookingId): void
    {
        $db = $this->getDatabase();
        
        try {
            // Update cancellation date
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_bookings'))
                ->set($db->quoteName('cancelled_at') . ' = NOW()')
                ->where($db->quoteName('id') . ' = :bookingId')
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            // Calculate refund amount based on cancellation policy
            $this->processBookingRefund($bookingId);

            // Trigger cancellation events
            PluginHelper::importPlugin('holidaypackages');
            Factory::getApplication()->triggerEvent('onHolidayPackagesBookingCancelled', [$bookingId]);

        } catch (Exception $e) {
            Log::add(
                sprintf('Error processing booking cancellation for booking %d: %s', $bookingId, $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );
        }
    }

    /**
     * Process booking refund
     *
     * @param   int  $bookingId  Booking ID
     * @return  void
     */
    protected function processBookingRefund(int $bookingId): void
    {
        $db = $this->getDatabase();
        
        try {
            // Get booking and package details
            $query = $db->getQuery(true)
                ->select([
                    'b.*',
                    'p.cancellation_policy'
                ])
                ->from($db->quoteName('#__hp_bookings', 'b'))
                ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
                ->where($db->quoteName('b.id') . ' = :bookingId')
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

            $booking = $db->setQuery($query)->loadObject();

            if (!$booking) {
                return;
            }

            // Calculate refund percentage based on policy and timing
            $refundPercentage = $this->calculateRefundPercentage(
                $booking->start_date,
                $booking->cancellation_policy
            );

            $refundAmount = $booking->total_amount * ($refundPercentage / 100);

            // Update booking with refund details
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_bookings'))
                ->set([
                    $db->quoteName('refund_amount') . ' = :refundAmount',
                    $db->quoteName('refund_percentage') . ' = :refundPercentage',
                    $db->quoteName('refund_processed_at') . ' = NOW()'
                ])
                ->where($db->quoteName('id') . ' = :bookingId')
                ->bind(':refundAmount', $refundAmount, ParameterType::FLOAT)
                ->bind(':refundPercentage', $refundPercentage, ParameterType::INTEGER)
                ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            // Process actual refund through payment gateway (would need implementation)
            // $this->processPaymentRefund($booking, $refundAmount);

        } catch (Exception $e) {
            Log::add(
                sprintf('Error processing refund for booking %d: %s', $bookingId, $e->getMessage()),
                Log::ERROR,
                'com_holidaypackages'
            );
        }
    }

    /**
     * Calculate refund percentage based on cancellation policy
     *
     * @param   string  $startDate  Package start date
     * @param   string  $policy     Cancellation policy JSON
     * @return  int     Refund percentage
     */
    protected function calculateRefundPercentage(string $startDate, ?string $policy): int
    {
        $now = Factory::getDate();
        $start = Factory::getDate($startDate);
        $daysUntilStart = $start->diff($now)->days;

        if ($daysUntilStart < 0) {
            return 0; // No refund for past bookings
        }

        // Default policy if none specified
        if (empty($policy)) {
            if ($daysUntilStart >= 30) return 90;
            if ($daysUntilStart >= 15) return 50;
            if ($daysUntilStart >= 7) return 25;
            return 0;
        }

        // Parse custom policy
        $policyData = json_decode($policy, true);
        if (!$policyData) {
            return 0;
        }

        foreach ($policyData as $rule) {
            if ($daysUntilStart >= $rule['days']) {
                return $rule['refund_percentage'];
            }
        }

        return 0;
    }

    /**
     * Get detailed booking information
     *
     * @param   int  $bookingId  Booking ID
     * @return  object|null  Booking details
     */
    protected function getBookingDetails(int $bookingId): ?object
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'p.title as package_title',
                'p.duration',
                'c.first_name',
                'c.last_name',
                'c.email',
                'c.phone'
            ])
            ->from($db->quoteName('#__hp_bookings', 'b'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON b.package_id = p.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON b.customer_id = c.id')
            ->where($db->quoteName('b.id') . ' = :bookingId')
            ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Send booking notification email
     *
     * @param   object  $booking   Booking details
     * @param   string  $template  Email template
     * @param   string  $status    Booking status
     * @return  void
     */
    protected function sendBookingNotificationEmail(object $booking, string $template, string $status): void
    {
        $app = Factory::getApplication();
        $mailer = $app->getMailer();

        // Prepare email data
        $recipient = $booking->email;
        $subject = Text::sprintf('COM_HOLIDAYPACKAGES_EMAIL_SUBJECT_' . strtoupper($status), $booking->booking_reference);
        
        // Load email template (would need template system implementation)
        $body = $this->loadEmailTemplate($template, $booking);

        // Send email
        $mailer->isHtml(true);
        $mailer->addRecipient($recipient);
        $mailer->setSubject($subject);
        $mailer->setBody($body);

        $sent = $mailer->Send();

        if (!$sent) {
            throw new Exception('Failed to send email notification');
        }
    }

    /**
     * Load email template with booking data
     *
     * @param   string  $template  Template name
     * @param   object  $booking   Booking data
     * @return  string  Rendered template
     */
    protected function loadEmailTemplate(string $template, object $booking): string
    {
        // Basic template - in production this would use a proper template engine
        $templates = [
            'booking_confirmed' => '
                <h2>Booking Confirmed - ' . $booking->booking_reference . '</h2>
                <p>Dear ' . $booking->first_name . ',</p>
                <p>Your booking for <strong>' . $booking->package_title . '</strong> has been confirmed.</p>
                <p><strong>Booking Details:</strong></p>
                <ul>
                    <li>Booking Reference: ' . $booking->booking_reference . '</li>
                    <li>Package: ' . $booking->package_title . '</li>
                    <li>Travel Date: ' . $booking->start_date . '</li>
                    <li>Travelers: ' . $booking->adults . ' Adults, ' . $booking->children . ' Children</li>
                    <li>Total Amount: ₹' . number_format($booking->total_amount, 2) . '</li>
                </ul>
                <p>We will contact you soon with further details.</p>
            ',
            'booking_cancelled' => '
                <h2>Booking Cancelled - ' . $booking->booking_reference . '</h2>
                <p>Dear ' . $booking->first_name . ',</p>
                <p>Your booking for <strong>' . $booking->package_title . '</strong> has been cancelled.</p>
                <p>Refund details will be processed as per our cancellation policy.</p>
            '
        ];

        return $templates[$template] ?? '';
    }

    /**
     * Delete booking payments
     *
     * @param   int  $bookingId  Booking ID
     * @return  void
     */
    protected function deleteBookingPayments(int $bookingId): void
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_payments'))
            ->where($db->quoteName('booking_id') . ' = :bookingId')
            ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
    }

    /**
     * Delete booking travelers
     *
     * @param   int  $bookingId  Booking ID
     * @return  void
     */
    protected function deleteBookingTravelers(int $bookingId): void
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_booking_travelers'))
            ->where($db->quoteName('booking_id') . ' = :bookingId')
            ->bind(':bookingId', $bookingId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
    }

    /**
     * Batch update booking status
     *
     * @param   string  $value  New status value
     * @param   array   $pks    Primary keys of records to update
     * @param   array   $contexts  Array of contexts
     * @return  boolean  True on success
     */
    public function batchStatus($value, $pks, $contexts)
    {
        if (empty($pks) || empty($value)) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'));
            return false;
        }

        $db = $this->getDatabase();

        foreach ($pks as $pk) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_bookings'))
                ->set($db->quoteName('status') . ' = :status')
                ->where($db->quoteName('id') . ' = :pk')
                ->bind(':status', $value)
                ->bind(':pk', $pk, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            // Handle status change notifications
            $this->handleStatusChangeNotification($pk, null, $value);
        }

        return true;
    }
}