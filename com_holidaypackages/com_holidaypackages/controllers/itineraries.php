<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

require_once JPATH_COMPONENT . '/controller.php';

class HolidaypackagesControllerItineraries extends HolidaypackagesController
{
    public function getModel($name = 'Itineraries', $prefix = 'HolidaypackagesModel', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function add()
    {
        $app = Factory::getApplication();
        $packageId = $app->input->getInt('package_id', 0);
        $destinationId = $app->input->getInt('destination_id', 0);

        if ($packageId == 0) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . $destinationId, false));
            return;
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
    }

    public function edit($key = null, $urlVar = null)
    {
        $app = Factory::getApplication();
        $cid = $app->input->get('cid', [], 'array');
        $id = (int) (count($cid) ? $cid[0] : $app->input->getInt('id', 0));
        $packageId = $app->input->getInt('package_id', 0);
        $destinationId = $app->input->getInt('destination_id', 0);

        if ($packageId == 0) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . $destinationId, false));
            return;
        }

        if ($id) {
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . $id . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        } else {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_ITEM_SELECTED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }
    }

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $packageId = $input->getInt('package_id', 0);
        $destinationId = $input->getInt('destination_id', 0);

        if ($packageId == 0) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . $destinationId, false));
            return;
        }

        if ($model->save($data)) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ITINERARY_SAVED'));
        } else {
            $this->setMessage($model->getError(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
    }

    public function publish()
    {
        $this->changeStatus(1, 'COM_HOLIDAYPACKAGES_ITINERARIES_PUBLISHED');
    }

    public function unpublish()
    {
        $this->changeStatus(0, 'COM_HOLIDAYPACKAGES_ITINERARIES_UNPUBLISHED');
    }

    public function archive()
    {
        $this->changeStatus(2, 'COM_HOLIDAYPACKAGES_ITINERARIES_ARCHIVED');
    }

    public function trash()
    {
        $this->changeStatus(-2, 'COM_HOLIDAYPACKAGES_ITINERARIES_TRASHED');
    }

    public function delete()
    {
        Session::checkToken() or jexit('Invalid Token');

        $input = Factory::getApplication()->getInput();
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);
        $packageId = $input->getInt('package_id', 0);
        $destinationId = $input->getInt('destination_id', 0);

        if ($packageId == 0) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . $destinationId, false));
            return;
        }

        if (count($cid)) {
            $model = $this->getModel('Itineraries');
            if ($model->delete($cid)) {
                $this->setMessage(Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_DELETED', count($cid)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        } else {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_SELECTED'), 'error');
        }

        $baseUrl = 'index.php?option=com_holidaypackages&view=itineraries';
        $baseUrl .= '&package_id=' . $packageId . '&destination_id=' . $destinationId;
        $this->setRedirect(Route::_($baseUrl, false));
    }

    public function cancel($key = null)
    {
        $app = Factory::getApplication();
        $packageId = $app->input->getInt('package_id', 0);
        $destinationId = $app->input->getInt('destination_id', 0);
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
    }

    protected function changeStatus($value, $message)
    {
        Session::checkToken() or jexit('Invalid Token');

        $input = Factory::getApplication()->getInput();
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);
        $packageId = $input->getInt('package_id', 0);
        $destinationId = $input->getInt('destination_id', 0);

        if ($packageId == 0) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . $destinationId, false));
            return;
        }

        if (count($cid)) {
            $model = $this->getModel('Itineraries');
            $result = false;

            switch ($value) {
                case 1:
                    $result = $model->publish($cid);
                    break;
                case 0:
                    $result = $model->unpublish($cid);
                    break;
                case 2:
                    $result = $model->archive($cid);
                    break;
                case -2:
                    $result = $model->trash($cid);
                    break;
            }

            if ($result) {
                $this->setMessage(Text::_($message));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        } else {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_SELECTED'), 'error');
        }

        $baseUrl = 'index.php?option=com_holidaypackages&view=itineraries';
        $baseUrl .= '&package_id=' . $packageId . '&destination_id=' . $destinationId;
        $this->setRedirect(Route::_($baseUrl, false));
    }
}