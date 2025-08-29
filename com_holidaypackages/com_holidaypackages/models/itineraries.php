<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelItineraries extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'package_id', 'day_number', 'date', 'place_name', 'status', 'search'
            ];
        }
        parent::__construct($config);
        $this->context = 'com_holidaypackages.itineraries';
    }

    protected function populateState($ordering = 'day_number', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);

        $app = Factory::getApplication('administrator');

        // Search Filter
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Published Status Filter
        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published === '*' || $published === '' ? null : (int)$published);

        // Package ID Filter
        $packageId = $app->input->getInt('package_id', 0);
        $this->setState('filter.package_id', $packageId);

        $this->setState('list.limit', $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint'));
        $this->setState('list.start', $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint'));
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('n4gvg__holiday_itineraries'));

        // Filter: Package ID
        $packageId = $this->getState('filter.package_id');
        if ($packageId) {
            $query->where($db->quoteName('package_id') . ' = ' . (int) $packageId);
        }

        // Filter: Published
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where($db->quoteName('status') . ' = ' . (int) $published);
        }

        // Filter: Search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = '%' . $db->escape($search, true) . '%';
            $query->where('(' . $db->quoteName('place_name') . ' LIKE ' . $db->quote($search) . ')');
        }

        // Ordering
        $orderCol = $this->state->get('list.ordering', 'day_number');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        return parent::getItems();
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.itinerary', 'itinerary', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_holidaypackages.edit.itinerary.data', []);

        if (empty($data)) {
            $id = $app->input->getInt('id', 0);
            if ($id) {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('n4gvg__holiday_itineraries'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);
                $db->setQuery($query);
                $data = $db->loadObject();
            } else {
                $data = new \stdClass();
                $data->package_id = $app->input->getInt('package_id', 0);
            }
        }

        return $data;
    }

    public function save($data)
    {
        $table = $this->getTable();
        $jsonFields = ['images', 'structured_details', 'transfer_sections', 'sightseeing_sections', 'resort_sections', 'activity_sections', 'meal_sections'];

        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            } elseif (!isset($data[$field])) {
                $data[$field] = '{}';
            }
        }

        if (!$table->bind($data) || !$table->check() || !$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    public function getItem($pk = null)
    {
        $pk = $pk ?: (int) $this->getState('itinerary.id');
        $table = $this->getTable();
        if ($pk > 0 && !$table->load($pk)) {
            throw new Exception(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND'), 404);
        }
        return $table;
    }

    public function getTable($type = 'Itineraries', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function publish($pks)
    {
        return $this->changeStatus($pks, 1);
    }

    public function unpublish($pks)
    {
        return $this->changeStatus($pks, 0);
    }

    public function archive($pks)
    {
        return $this->changeStatus($pks, 2);
    }

    public function trash($pks)
    {
        return $this->changeStatus($pks, -2);
    }

    private function changeStatus($ids, $status)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_itineraries'))
            ->set($db->quoteName('status') . ' = ' . (int) $status)
            ->where('id IN (' . implode(',', array_map('intval', $ids)) . ')');
        $db->setQuery($query);
        return $db->execute();
    }

    public function delete($pks)
    {
        $table = $this->getTable('Itineraries', 'HolidaypackagesTable');

        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if (!$table->delete($pk)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }
}