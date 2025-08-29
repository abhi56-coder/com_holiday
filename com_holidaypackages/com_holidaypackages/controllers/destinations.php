<?php

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

require_once JPATH_COMPONENT . '/controller.php';


class HolidaypackagesControllerDestinations extends HolidaypackagesController
{

    public function getModel($name = 'Destinations', $prefix = 'HolidaypackagesModel', $config = [])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish()
    {
        $category = $this->getCategoryFromRequest();
        $this->changeStatus(1, 'Destinations Published', $category);
    }

    public function unpublish()
    {
        $category = $this->getCategoryFromRequest();
        $this->changeStatus(0, 'Destinations Unpublished', $category);
    }

    public function archive()
    {
        $category = $this->getCategoryFromRequest();
        $this->changeStatus(2, 'Destinations Archived', $category);
    }

    public function trash()
    {
        $category = $this->getCategoryFromRequest();
        $this->changeStatus(-2, 'Destinations Trashed', $category);
    }

   
    protected function changeStatus($value, $message, $category = '')
    {
        Session::checkToken() or jexit('Invalid Token');

        $input = Factory::getApplication()->input;
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);

        $categoryParam = $category ? '&category=' . urlencode($category) : '';

        if (count($cid)) {
            $model = $this->getModel('Destinations');
            $result = false;

            switch ($value) {
                case 1: $result = $model->publish($cid); break;
                case 0: $result = $model->unpublish($cid); break;
                case 2: $result = $model->archive($cid); break;
                case -2: $result = $model->trash($cid); break;
            }

            if ($result) {
                $this->setMessage($message);
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        } else {
            $this->setMessage(\Joomla\CMS\Language\Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=destinations' . $categoryParam, false));
    }


    public function add()
{
    $input = Factory::getApplication()->input;
    $category = $input->getString('category', '');

    $categoryParam = $category !== '' ? '&category=' . urlencode($category) : '';
    $this->setRedirect('index.php?option=com_holidaypackages&view=destination&layout=edit' . $categoryParam);
}
public function edit($key = null, $urlVar = null)
{
    $input = Factory::getApplication()->input;
    $id = $input->getInt('cid', [])[0] ?? 0;
    $category = $input->getString('category', '');
    $categoryParam = $category ? '&category=' . urlencode($category) : '';

    $this->setRedirect('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . $id . $categoryParam);
    return true;
}


    public function delete()
    {
        $this->checkToken();

        $input = Factory::getApplication()->input;
        $cid = $input->get('cid', [], 'array');
        ArrayHelper::toInteger($cid);

        $category = $this->getCategoryFromRequest();
        $categoryParam = $category ? '&category=' . urlencode($category) : '';

        if (count($cid)) {
            $model = $this->getModel();
            if ($model->delete($cid)) {
                $this->setMessage(\Joomla\CMS\Language\Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_DELETED', count($cid)));
            } else {
                $this->setMessage($model->getError(), 'error');
            }
        } else {
            $this->setMessage(\Joomla\CMS\Language\Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=destinations' . $categoryParam, false));
    }

  
    private function getCategoryFromRequest()
    {
        $input = Factory::getApplication()->input;
        $category = $input->getString('category', '');

        $task = $input->getString('task', '');
        if (strpos($task, '&category=') !== false) {
            $taskParts = explode('&category=', $task);
            $category = !empty($taskParts[1]) ? urldecode($taskParts[1]) : $category;
        }

        return $category;
    }
}
