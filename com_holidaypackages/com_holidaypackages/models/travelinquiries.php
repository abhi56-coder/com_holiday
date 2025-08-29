<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelTravelinquiries extends ListModel
{
    public function __construct($config = [])
    {
   if (empty($config['filter_fields'])) {
    $config['filter_fields'] = [
        'id', 'd.id',
        'first_name', 'd.first_name',
        'last_name', 'd.last_name',
        'phone', 'd.phone',
        'email', 'd.email',
        'destination', 'd.destination',
        'start_date', 'd.start_date',
        'end_date', 'd.end_date',
        'insurance', 'd.insurance',
        'search', 'filter_search',
        'published', 'd.published',
    ];
}

        parent::__construct($config);
        $this->context = 'com_holidaypackages.travelinquiries';
    }

   protected function populateState($ordering = 'd.id', $direction = 'ASC')
{
    $app = Factory::getApplication();

    // Search Filter
    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
    $this->setState('filter.search', $search);

    // Published Status Filter
    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
    $this->setState('filter.published', $published === '*' || $published === '' ? null : (int)$published);

    // ✅ Corrected ordering and direction (do not override user-selected sort)
    $listOrdering = $this->getUserStateFromRequest($this->context . '.list.ordering', 'filter_order', $ordering, 'cmd');
    $listDirection = $this->getUserStateFromRequest($this->context . '.list.direction', 'filter_order_Dir', $direction, 'cmd');

    $this->setState('list.ordering', $listOrdering);
    $this->setState('list.direction', $listDirection);

    // Pagination
    $this->setState('list.limit', $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint'));
    $this->setState('list.start', $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint'));

    parent::populateState($ordering, $direction);
}


    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'd.*'))
              ->from($db->quoteName('n4gvg__travel_inquiries', 'd'));

        // Search filter
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('d.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(d.first_name LIKE ' . $search . ' OR d.last_name LIKE ' . $search . ' OR d.city LIKE ' . $search . ' OR d.state LIKE ' . $search . ' OR d.destination LIKE ' . $search . ' OR d.budget LIKE ' . $search . ' OR d.travelers LIKE ' . $search . ' OR d.departure_city LIKE ' . $search . ' OR d.insurance LIKE ' . $search . ')');
            }
        }

        // Published filter
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where('d.published = ' . (int) $published);
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'd.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    public function getItems()
    {
        return parent::getItems();
    }

    public function getTable($type = 'Travelinquiries', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }


    // protected function loadFormData()
    // {
    //     $data = Factory::getApplication()->getUserState($this->context . '.filter', []);
    //     return $data;
    // }

    public function publish($pks, $value = 1)
    {
        return $this->changeStatus($pks, $value);
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

    public function delete($pks)
    {
        $table = $this->getTable('Travelinquiries', 'HolidaypackagesTable');
        $pks = (array) $pks;

        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if (!$table->delete($pk)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND'));
                return false;
            }
        }

        return true;
    }

    private function changeStatus($pks, $status)
    {
        $table = $this->getTable();
        $pks = (array) $pks;
        
        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if ($table->published === $status) {
                    // Skip if already in the desired state
                    continue;
                }
                
                $table->published = $status;
                
                if (!$table->store()) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND'));
                return false;
            }
        }
        
        return true;
    }
}