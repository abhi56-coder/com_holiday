<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelEditdashboard extends AdminModel
{
    public function getTable($type = 'Dashboard', $prefix = 'HolidaypackagesTable', $config = array())
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.editdashboard', 'dashboard', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        return $this->getItem();
    }

    public function getItem($pk = null)
    {
        $pk = $pk ?? $this->getState('editdashboard.id');
        $table = $this->getTable();

        if ($pk && $table->load($pk)) {
            return $table;
        }

        return $table;
    }

    public function save($data)
    {
        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $table = $this->getTable();

        if ($id && !$table->load($id)) {
            $this->setError('Item not found');
            return false;
        }

        if (!$table->bind($data) || !$table->check() || !$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        $this->setState('editdashboard.id', $table->id);
        return true;
    }
}