<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelTravelinquiry extends AdminModel
{
    protected $text_prefix = 'COM_HOLIDAYPACKAGES';

    public function getTable($type = 'Travelinquiries', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.travelinquiry', 'travelinquiry', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            $this->setError(Text::_('JERROR_NO_FORM_FOUND'));
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.travelinquiry.data', []);
        return empty($data) ? $this->getItem() : $data;
    }

    public function getItem($pk = null)
    {
        $pk = $pk ?: (int) $this->getState($this->getName() . '.id');
        if ($pk > 0) {
            $table = $this->getTable('Travelinquiries', 'HolidaypackagesTable');
            if ($table->load($pk)) {
                $item = new \stdClass();
                $properties = $table->getProperties(1);
                foreach ($properties as $key => $value) {
                    $item->$key = $value;
                }
                return $item;
            } else {
                $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND', $pk));
                return false;
            }
        }
        $item = new \stdClass();
        $item->id = 0;
        $item->first_name = '';
        $item->last_name = '';
        $item->phone = '';
        $item->email = '';
        $item->destination = '';
        $item->start_date = '';
        $item->end_date = '';
        $item->insurance = 0;
        $item->published = 1;
        return $item;
    }

    public function save($data)
    {
        $table = $this->getTable();
        $pk = (int) ($data['id'] ?? 0);

        if ($pk > 0 && !$table->load($pk)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND'));
            return false;
        }

        if (!$table->bind($data) || !$table->check() || !$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        $this->setState($this->getName() . '.id', $table->id);
        return true;
    }

    protected function populateState()
    {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState($this->getName() . '.id', $id);

        parent::populateState('id', 'asc');
    }
}