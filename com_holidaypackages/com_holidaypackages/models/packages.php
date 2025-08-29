<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelPackages extends ListModel
{
    protected $context = 'com_holidaypackages.packages';

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'p.id',
                'p.title',
                'destination_title',
                'p.duration',
                'p.price',
                'p.departure_city',
                'p.package_details',
                'p.special_package',
                'p.price_per_person',
                'p.price_per_couple',
                'p.emi_option',
                'p.label',
                'p.hotel_category',
                'p.inclusions',
                'p.published',
                'p.destination_id',
                'p.image',
                'p.member',              // Added new field
                'p.package_type',        // Added new field
                'p.flight_included'      // Added new field
            );
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'p.id', $direction = 'ASC')
    {
        $app = Factory::getApplication();

        // Search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Published
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'cmd');
        $this->setState('filter.published', ($published === '' || $published === '*') ? null : (int) $published);

        // destination_id (URL has priority)
        $destinationId = $app->input->getInt('destination_id', 0);
        $filterDestinationId = $this->getUserStateFromRequest($this->context . '.filter.destination_id', 'filter_destination_id', 0, 'int');

        if ($destinationId) {
            $this->setState('filter.destination_id', $destinationId);
            $app->setUserState($this->context . '.filter.destination_id', $destinationId);
        } elseif ($filterDestinationId) {
            $this->setState('filter.destination_id', $filterDestinationId);
        }

        // Pagination
        $limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint');
        $this->setState('list.limit', $limit);
        $start = $app->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint');
        $this->setState('list.start', $start);

        // Sorting via fullordering (e.g. "p.title ASC")
        $fullordering = $this->getUserStateFromRequest($this->context . '.list.fullordering', 'fullordering', '', 'cmd');
        if ($fullordering) {
            $parts = explode(' ', trim($fullordering), 2);
            $orderCol = $parts[0] ?? $ordering;
            $orderDir = strtoupper($parts[1] ?? $direction);

            $allowed = array('ASC', 'DESC');
            if (!in_array($orderDir, $allowed, true)) {
                $orderDir = 'ASC';
            }

            $this->setState('list.ordering', $orderCol);
            $this->setState('list.direction', $orderDir);
        } else {
            // Default: ID ASC
            $this->setState('list.ordering', $ordering);
            $this->setState('list.direction', strtoupper($direction));
        }

        // Params
        $params = ComponentHelper::getParams('com_holidaypackages');
        $this->setState('params', $params);

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.destination_id');
        $id .= ':' . $this->getState('list.ordering');
        $id .= ':' . $this->getState('list.direction');
        $id .= ':' . $this->getState('list.limit');
        $id .= ':' . $this->getState('list.start');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array(
            'p.id', 'p.title', 'p.destination_id', 'p.image', 'p.duration', 'p.price', 'p.departure_city',
            'p.package_details', 'p.special_package', 'p.price_per_person', 'p.price_per_couple',
            'p.emi_option', 'p.label', 'p.hotel_category', 'p.inclusions', 'p.description', 'p.published',
            'p.member',              
            'p.package_type',        
            'p.flight_included'      
        )))
        ->select($db->quoteName('d.title', 'destination_title'))
        ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
        ->join('LEFT', $db->quoteName('n4gvg__holiday_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('p.destination_id'));

        // Search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('p.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(p.title LIKE ' . $search . ' OR p.description LIKE ' . $search . ' OR p.member LIKE ' . $search . ' OR p.package_type LIKE ' . $search . ')');
            }
        }

        // Published
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where('p.published = ' . (int) $published);
        } else {
            $query->where('p.published IN (0,1,2,-2)');
        }

        // Destination
        $destinationId = (int) $this->getState('filter.destination_id');
        if ($destinationId) {
            $query->where('p.destination_id = ' . (int) $destinationId);
        }

        // Ordering
        $orderCol = $this->state->get('list.ordering', 'p.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $textColumns = [
            'p.title',
            'destination_title',
            'p.label',
            'p.hotel_category',
            'p.inclusions',
            'p.description',
            'p.member',              // Added new field
            'p.package_type'         // Added new field
        ];

        if (!empty($orderCol)) {
            if (in_array($orderCol, $textColumns, true)) {
                $query->order('LOWER(CONVERT(' . $orderCol . ' USING utf8mb4)) ' . $orderDirn);
            } else {
                $query->order($orderCol . ' ' . $orderDirn);
            }
        }

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        return is_array($items) ? $items : array();
    }

    public function getTable($type = 'Package', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function changeStatus($pks, $value)
    {
        $table = $this->getTable();
        $pks = (array) $pks;

        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if ((int) $table->published === (int) $value) {
                    continue;
                }
                $table->published = (int) $value;

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

    public function publish($pks) { return $this->changeStatus($pks, 1); }
    public function unpublish($pks) { return $this->changeStatus($pks, 0); }
    public function archive($pks) { return $this->changeStatus($pks, 2); }
    public function trash($pks) { return $this->changeStatus($pks, -2); }

    public function delete($pks)
    {
        $table = $this->getTable();
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

    public function getFilterForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.filter_packages', 'filter_packages', array('control' => '', 'load_data' => $loadData));
        return $form ?: null;
    }
}