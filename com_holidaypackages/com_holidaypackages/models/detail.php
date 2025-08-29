<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;

class HolidaypackagesModelDetail extends AdminModel {
    public function getTable($type = 'Detail', $prefix = 'HolidaypackagesTable', $config = array()) {
        return \JTable::getInstance($type, $prefix, $config);
    }

   public function getForm($data = array(), $loadData = true)
{
    $form = $this->loadForm('com_holidaypackages.detail', 'detail', array('control' => 'jform', 'load_data' => $loadData));

    if (empty($form)) {
        return false;
    }

    
    $input = \JFactory::getApplication()->input;
    $id = $input->getInt('id');

    if ($id) {
 
        $form->setFieldAttribute(
            'package_id',
            'query',
            'SELECT id, title FROM n4gvg__holiday_packages WHERE published = 1'
        );
    } else {
      
        $form->setFieldAttribute(
            'package_id',
            'query',
            'SELECT id, title FROM n4gvg__holiday_packages WHERE published = 1 AND id NOT IN (SELECT package_id FROM n4gvg__holiday_details)'
        );
    }

    return $form;
}


    protected function loadFormData() {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.detail.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    public function save($data) {
        $table = $this->getTable();
        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }
        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }
        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }
        return $table->id;
    }
}