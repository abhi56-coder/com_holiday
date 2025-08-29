<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class HolidaypackagesControllerSummary extends AdminController {
    public function getModel($name = 'Summary', $prefix = 'HolidaypackagesModel', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish() {
        $this->checkToken();
        $model = $this->getModel();
        if (!$model) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_MODEL_NOT_FOUND'), 'error');
            return false;
        }
        $cid = Factory::getApplication()->input->get('cid', array(), 'array');
        if (empty($cid)) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        } else {
            try {
                $model->publish($cid, 1);
                $this->setMessage(Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_PUBLISHED', count($cid)));
            } catch (Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }
        $this->setRedirect('index.php?option=com_holidaypackages&view=summary');
    }

    public function unpublish() {
        $this->checkToken();
        $model = $this->getModel();
        if (!$model) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_MODEL_NOT_FOUND'), 'error');
            return false;
        }
        $cid = Factory::getApplication()->input->get('cid', array(), 'array');
        if (empty($cid)) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        } else {
            try {
                $model->publish($cid, 0);
                $this->setMessage(Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_UNPUBLISHED', count($cid)));
            } catch (Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }
        $this->setRedirect('index.php?option=com_holidaypackages&view=summary');
    }

    public function delete() {
        $this->checkToken();
        $model = $this->getModel();
        if (!$model) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_MODEL_NOT_FOUND'), 'error');
            return false;
        }
        $cid = Factory::getApplication()->input->get('cid', array(), 'array');
        if (empty($cid)) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        } else {
            try {
                $model->delete($cid);
                $this->setMessage(Text::plural('COM_HOLIDAYPACKAGES_N_ITEMS_DELETED', count($cid)));
            } catch (Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }
        $this->setRedirect('index.php?option=com_holidaypackages&view=summary');
    }
}