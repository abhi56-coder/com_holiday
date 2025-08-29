<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;

class HolidaypackagesModelDestination extends AdminModel {
    public function getTable($type = 'Destination', $prefix = 'HolidaypackagesTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm('com_holidaypackages.destination', 'destination', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData() {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.destination.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }
}