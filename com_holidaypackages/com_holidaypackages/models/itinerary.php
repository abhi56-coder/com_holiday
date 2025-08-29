<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelItinerary extends AdminModel
{
    public function getItem($pk = null)
    {
        $input = Factory::getApplication()->input;
        $pk = $pk ?: $input->getInt('id', 0);
        $packageId = $input->getInt('package_id', 0);
        $db = $this->getDbo();

        if ($pk > 0) {
            $item = parent::getItem($pk);
            if (!$item) {
                return false;
            }
        } else {
            $item = $this->getTable();
            $item->package_id = $packageId;

            $query = $db->getQuery(true)
                ->select('MAX(day_number)')
                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                ->where($db->quoteName('package_id') . ' = ' . (int) $packageId);
            $db->setQuery($query);
            $item->day_number = $db->loadResult() !== null ? $db->loadResult() + 1 : 1;

            $query->clear()
                ->select('MAX(date)')
                ->from($db->quoteName('n4gvg__holiday_itineraries'))
                ->where($db->quoteName('package_id') . ' = ' . (int) $packageId);
            $db->setQuery($query);
            $lastDate = $db->loadResult();
            $item->date = $lastDate ? date('Y-m-d', strtotime($lastDate . ' +1 day')) : date('Y-m-d');
        }

        if ($item->package_id) {
            $query = $db->getQuery(true)
                ->select('title')
                ->from($db->quoteName('n4gvg__holiday_packages'))
                ->where('id = ' . (int) $item->package_id);
            $db->setQuery($query);
            $item->package_title = $db->loadResult();
        }

        if (!empty($item->all_sections) && is_string($item->all_sections)) {
            $decoded = json_decode($item->all_sections, true);
            $item->all_sections = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        } else {
            $item->all_sections = [];
        }

        return $item;
    }

    public function save($data)
    {
        if (isset($data['all_sections']) && is_array($data['all_sections'])) {
            $data['all_sections'] = json_encode($data['all_sections'], JSON_UNESCAPED_UNICODE);
        } else {
            $data['all_sections'] = json_encode([]);
        }

        return parent::save($data);
    }

    public function getTable($name = 'Itinerary', $prefix = 'HolidaypackagesTable', $options = [])
    {
        return Table::getInstance($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.itinerary', 'itinerary', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'));
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.itinerary.data', []);
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }
}