<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelDashboard extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'd.id',
                'title', 'd.title',
                'category', 'd.category',
                'published', 'd.published',
            ];
        }

        parent::__construct($config);
        $this->context = 'com_holidaypackages.dashboard';
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

        // Category Filter
        $category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '', 'string');
        $this->setState('filter.category', $category);

        // Set the ordering and direction
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);

        // Set pagination
        $this->setState('list.limit', $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint'));
        $this->setState('list.start', $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint'));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'd.*'))
              ->from($db->quoteName('n4gvg__holidaypackages_dashboard', 'd'));

        // Search filter
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('d.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(d.title LIKE ' . $search . ' OR d.category LIKE ' . $search . ')');
            }
        }

        // Published filter
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where('d.published = ' . (int) $published);
        }

        // Category filter
        $category = $this->getState('filter.category');
        if (!empty($category)) {
            $query->where('d.category = ' . $db->quote($category));
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

    public function getTable($type = 'Dashboard', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

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
        $table = $this->getTable('Dashboard', 'HolidaypackagesTable');
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