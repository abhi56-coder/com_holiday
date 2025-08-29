<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

require_once JPATH_COMPONENT . '/controller.php';

class HolidaypackagesControllerPolicies extends HolidaypackagesController
{
    public function getModel($name = 'Policies', $prefix = 'HolidaypackagesModel', $config = [])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish()
    {
        $this->changeStatus(1, 'Policies Published');
    }

    public function unpublish()
    {
        $this->changeStatus(0, 'Policies Unpublished');
    }

    public function archive()
    {
        $this->changeStatus(2, 'Policies Archived');
    }

    public function trash()
    {
        $this->changeStatus(-2, 'Policies Trashed');
    }

    public function delete()
    {
        Session::checkToken() or jexit('Invalid Token');

        $input = Factory::getApplication()->getInput();
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);

        if (count($cid)) {
            $model = $this->getModel('Policies');
            if ($model->delete($cid)) {
                $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_POLICIES_DELETED'));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        } else {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_SELECTED'), 'error');
        }

        $input = Factory::getApplication()->getInput();
        $baseUrl = 'index.php?option=com_holidaypackages&view=policies';
        $baseUrl .= '&package_id=' . $input->getInt('package_id', 0);
        $baseUrl .= '&destination_id=' . $input->getInt('destination_id', 0);
        $this->setRedirect(Route::_($baseUrl, false));
    }

    protected function changeStatus($value, $message)
    {
        Session::checkToken() or jexit('Invalid Token');

        $input = Factory::getApplication()->getInput();
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);

        if (count($cid)) {
            $model = $this->getModel('Policies');
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
                $this->setMessage($message);
            }
        }

        $input = Factory::getApplication()->getInput();
        $baseUrl = 'index.php?option=com_holidaypackages&view=policies';
        $baseUrl .= '&package_id=' . $input->getInt('package_id', 0);
        $baseUrl .= '&destination_id=' . $input->getInt('destination_id', 0);
        $this->setRedirect(Route::_($baseUrl, false));
    }
}