<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class HolidaypackagesController extends BaseController {
    public function display($cachable = false, $urlparams = array()) {
        $view = $this->input->get('view', 'destinations');
        $this->input->set('view', $view);
        parent::display($cachable, $urlparams);
        return $this;
    }

    public function search()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Packages');

        $departureCity = $input->getString('starting_from', '');
$departureCity = $input->getString('starting_from', '');
$destinationId = $input->getInt('destination_id', 0);
if ($destinationId === 0) {
    $destinationId = $input->getInt('id', 0); 
}
$selectedDate = $input->getString('start_date', '');        $selectedDate = $input->getString('start_date', '');
        $rooms = $input->getInt('rooms', 1);
        $adults = $input->getInt('adults', 1);
        $children = $input->getInt('children', 0);

        // Debug: Log input parameters
        // $app->enqueueMessage('Searching with: departureCity=' . $departureCity . ', destinationId=' . $destinationId . ', date=' . $selectedDate, 'notice');

        // Fetch filtered packages
        $packages = $model->getFilteredPackages($departureCity, $destinationId);

        // Update itineraries with selected date
        if (!empty($selectedDate) && !empty($packages)) {
            $model->updateItineraryDates($packages, $selectedDate);
            // $app->enqueueMessage('Itinerary dates updated for ' . count($packages) . ' packages.', 'notice');
        } else {
            $app->enqueueMessage('No packages or date to update.', 'notice');
        }

        // Redirect with filtered data using id
        $redirectUrl = 'index.php?option=com_holidaypackages&view=packages&id=' . $destinationId .
            '&starting_from=' . urlencode($departureCity) .
            '&start_date=' . urlencode($selectedDate) .
            '&rooms=' . $rooms .
            '&adults=' . $adults .
            '&children=' . $children;
        
        $this->setRedirect($redirectUrl);
    }
}