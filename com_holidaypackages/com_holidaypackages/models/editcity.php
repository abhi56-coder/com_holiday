<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelEditcity extends AdminModel
{
    protected $text_prefix = 'COM_HOLIDAYPACKAGES';

    public function getTable($type = 'Cities', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function getItem($pk = null)
    {
        $input = Factory::getApplication()->input;
        $pk = $pk ?: $input->getInt('id', 0);
        $item = parent::getItem($pk);

        if ($item === false) {
            $item = $this->getTable();
            $item->id = 0;
            $item->name = '';
            $item->published = 1;
        }

        return $item;
    }

    public function save($data)
    {
        return parent::save($data);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.editcity', 'editcity', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'));
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.editcity.data', []);
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }
}