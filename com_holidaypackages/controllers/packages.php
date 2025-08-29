<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

class HolidaypackagesControllerPackages extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->getView('packages', 'html');
        $model = $this->getModel('Packages');
        $view->setModel($model, true);
        $view->destinations = $model->getDestinationsWithCounts();
        $view->filterOptions = $model->getFilterOptions();
        $view->packageCounts = $model->getPackageCountsForTabs();
        $view->durations = $model->getDurations($this->input->getInt('id', 0) ?: $this->input->getInt('destination_id', 0));
        $view->cities = $model->getCities();
        $view->specialPackages = $model->getSpecialPackages($this->input->getInt('id', 0) ?: $this->input->getInt('destination_id', 0));
        $priceRange = $model->getPriceRange($this->input->getInt('id', 0) ?: $this->input->getInt('destination_id', 0));
        $view->maxPrice = $priceRange[1] ?: 0;
        $view->minPrice = $priceRange[0] ?: 0;
        $view->items = $model->getItems();
        $view->display();
    }

    public function filterPackages()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $duration = $input->getInt('duration', 0);
        $flights = $input->getString('flights', '');
        $budget = $input->getString('budget', '');
        $hotelCategory = $input->getString('hotelCategory', '');
        $destinationId = $input->getInt('destinationId', 0);

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('p.id, p.title, p.image, p.destination_id, d.title AS destination_title')
              ->from('n4gvg__holiday_packages AS p')
              ->leftJoin('n4gvg__holiday_destinations AS d ON d.id = p.destination_id');

        if ($destinationId > 0) {
            $query->where('p.destination_id = ' . (int)$destinationId);
        }

        if ($duration > 0) {
            $query->where('(SELECT COUNT(*) FROM n4gvg__holiday_itineraries i WHERE i.package_id = p.id) = ' . (int)$duration);
        }

        $columns = $db->getTableColumns('n4gvg__holiday_packages');
        if (isset($columns['flight_included'])) {
            if ($flights === 'with') {
                $query->where('p.flight_included = 1');
            } elseif ($flights === 'without') {
                $query->where('p.flight_included = 0');
            }
        }

        if (isset($columns['price']) && $budget) {
            switch ($budget) {
                case '<35000':
                    $query->where('p.price < 35000');
                    break;
                case '35000-45000':
                    $query->where('p.price BETWEEN 35000 AND 45000');
                    break;
                case '45000-50000':
                    $query->where('p.price BETWEEN 45000 AND 50000');
                    break;
                case '>50000':
                    $query->where('p.price > 50000');
                    break;
            }
        }

        if (isset($columns['hotel_category']) && $hotelCategory) {
            switch ($hotelCategory) {
                case '<3*':
                    $query->where('p.hotel_category < 3');
                    break;
                case '3*':
                    $query->where('p.hotel_category = 3');
                    break;
                case '4*':
                    $query->where('p.hotel_category = 4');
                    break;
                case '5*':
                    $query->where('p.hotel_category = 5');
                    break;
            }
        }

        $db->setQuery($query);
        try {
            $packages = $db->loadObjectList() ?: [];
        } catch (Exception $e) {
            $app->enqueueMessage('Error fetching packages: ' . $e->getMessage(), 'error');
            echo new JsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
            $app->close();
        }

        echo new JsonResponse($packages);
        $app->close();
    }

    public function saveGuestDetails()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $rooms = $input->getInt('rooms', 1);
        $adults = $input->getInt('adults', 1);
        $children = $input->getInt('children', 0);

        $response = [
            'success' => true,
            'message' => 'Guest details saved successfully',
            'data' => [
                'rooms' => $rooms,
                'adults' => $adults,
                'children' => $children
            ]
        ];

        echo new JsonResponse($response);
        $app->close();
    }
}