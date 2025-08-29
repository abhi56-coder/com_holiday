<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelCities extends ListModel
{
    protected $context = 'com_holidaypackages.cities';

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'c.id',
                'name', 'c.name',
                'published', 'c.published'
            );
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'c.id', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        // Search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Published
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', ($published === '' || $published === '*') ? null : (int) $published);

        // Ordering
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);

        // Pagination
        $limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint');
        $this->setState('list.limit', $limit);

        $start = $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint');
        $this->setState('list.start', $start);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('c.*')
              ->from($db->quoteName('n4gvg__cities', 'c'));

        // Search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('c.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('c.name LIKE ' . $search);
            }
        }

        // Published
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where('c.published = ' . (int) $published);
        }

        // Ordering
        $orderCol  = $this->state->get('list.ordering', 'c.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    public function getTable($type = 'Cities', $prefix = 'HolidaypackagesTable', $config = array())
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
        $table = $this->getTable('Cities', 'HolidaypackagesTable');
        $pks   = (array) $pks;

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
        $pks   = (array) $pks;

        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if ($table->published == $status) continue;

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
