<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class HolidaypackagesController extends BaseController {
    public function saveGuestDetails() {
        $app = Factory::getApplication();
        $model = $this->getModel('Packages', 'HolidaypackagesModel');

        // Validate CSRF token
        if (!JSession::checkToken()) {
            $response = [
                'success' => false,
                'message' => 'Invalid CSRF token'
            ];
            echo json_encode($response);
            $app->close();
            return;
        }

        $rooms = $app->input->getInt('rooms', 1);
        $adults = $app->input->getInt('adults', 1);
        $children = $app->input->getInt('children', 0);
        $selectedDate = $app->input->getString('selected_date', '');

        $response = ['success' => false, 'message' => ''];

        try {
            // Convert the date to a format suitable for MySQL (yyyy-mm-dd)
            $selectedDate = $selectedDate ? date('Y-m-d', strtotime($selectedDate)) : null;

            if ($model->saveGuestDetails($rooms, $adults, $children, $selectedDate)) {
                $response['success'] = true;
                $response['message'] = 'Guest details saved successfully';
            } else {
                $response['message'] = 'Failed to save guest details: Unknown error';
            }
        } catch (Exception $e) {
            $response['message'] = 'Exception: ' . $e->getMessage();
            // Log the error for debugging
            JLog::add('Error in saveGuestDetails: ' . $e->getMessage(), JLog::ERROR, 'com_holidaypackages');
        }

        echo json_encode($response);
        $app->close();
    }
}