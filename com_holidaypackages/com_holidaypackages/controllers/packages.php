<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

class HolidaypackagesControllerPackages extends FormController
{
   
    protected $view_list = 'packages';

   
    protected $view_item = 'package';

    
    public function getModel($name = 'Package', $prefix = 'HolidaypackagesModel', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    
    public function display($cachable = false, $urlparams = [])
    {
        $input         = Factory::getApplication()->input;
        $destinationId = $input->getInt('destination_id', 0);

        // Push destination_id into the list model state
        $listModel = $this->getModel('Packages', 'HolidaypackagesModel', ['ignore_request' => true]);
        $listModel->setState('filter.destination_id', $destinationId);

        return parent::display($cachable, $urlparams);
    }



    public function publish()
    {
        $this->changeStatus(1, Text::_('COM_HOLIDAYPACKAGES_PACKAGES_PUBLISHED'));
    }

    public function unpublish()
    {
        $this->changeStatus(0, Text::_('COM_HOLIDAYPACKAGES_PACKAGES_UNPUBLISHED'));
    }

    public function archive()
    {
        $this->changeStatus(2, Text::_('COM_HOLIDAYPACKAGES_PACKAGES_ARCHIVED'));
    }

    public function trash()
    {
        $this->changeStatus(-2, Text::_('COM_HOLIDAYPACKAGES_PACKAGES_TRASHED'));
    }

    protected function changeStatus($value, $message)
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app          = Factory::getApplication();
        $input        = $app->input;
        $cid          = (array) $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);
        $destinationId = $input->getInt('destination_id', 0);
        $destParam     = $destinationId ? '&destination_id=' . (int) $destinationId : '';

        if (empty($cid)) {
            $this->setMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=' . $this->view_list . $destParam, false));
            return false;
        }

        $listModel = $this->getModel('Packages', 'HolidaypackagesModel', ['ignore_request' => true]);
        $result    = $listModel->changeStatus($cid, $value);

        if ($result) {
            $this->setMessage($message);
        } else {
            $this->setMessage($listModel->getError(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=' . $this->view_list . $destParam, false));
        return $result;
    }



    public function add($key = null, $urlVar = null)
    {
        $app           = Factory::getApplication();
        $destinationId = $app->input->getInt('destination_id', 0);

        $app->setUserState('com_holidaypackages.edit.package.id', 0);
        $app->setUserState('com_holidaypackages.edit.package.data', ['destination_id' => $destinationId]);

        $this->setRedirect(
            Route::_('index.php?option=com_holidaypackages&view=' . $this->view_item . '&layout=edit&destination_id=' . (int) $destinationId, false)
        );

        return true;
    }

    public function edit($key = null, $urlVar = null)
    {
        $app           = Factory::getApplication();
        $cid           = (array) $this->input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);
        $id            = !empty($cid) ? (int) $cid[0] : 0;
        $destinationId = $app->input->getInt('destination_id', 0);

        if (!$id) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=' . $this->view_list . '&destination_id=' . (int) $destinationId, false));
            return false;
        }

        $this->input->set('id', $id);
        $this->holdEditId('com_holidaypackages.edit.package', $id);
        $app->setUserState('com_holidaypackages.edit.package.id', $id);

        $this->setRedirect(
            Route::_('index.php?option=com_holidaypackages&view=' . $this->view_item . '&layout=edit&id=' . $id . '&destination_id=' . (int) $destinationId, false)
        );

        return true;
    }

    public function delete($key = null)
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app           = Factory::getApplication();
        $cid           = (array) $this->input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);
        $destinationId = $this->input->getInt('destination_id', 0);
        $destParam     = $destinationId ? '&destination_id=' . (int) $destinationId : '';

        if (empty($cid)) {
            $this->setMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=' . $this->view_list . $destParam, false));
            return false;
        }

        $listModel = $this->getModel('Packages', 'HolidaypackagesModel', ['ignore_request' => true]);

        if ($listModel->delete($cid)) {
            $this->setMessage(Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_DELETED', count($cid)));
        } else {
            $this->setMessage($listModel->getError(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=' . $this->view_list . $destParam, false));
        return true;
    }
}
