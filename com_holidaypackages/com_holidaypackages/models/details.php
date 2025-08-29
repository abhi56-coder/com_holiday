<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class HolidaypackagesModelDetails extends ListModel {
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array('id', 'package_id', 'itinerary', 'policies', 'summary', 'published');
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'id', $direction = 'ASC') {
        $app = Factory::getApplication();

        // Load the filter state
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        // Load pagination state
        $limit = $this->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit', 20), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $this->getUserStateFromRequest($this->context . '.list.start', 'limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // List state information
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('d.id', 'd.package_id', 'd.itinerary', 'd.policies', 'd.summary', 'd.published')))
              ->select($db->quoteName('p.title', 'package_title'))
              ->from($db->quoteName('n4gvg__holiday_details', 'd'))
              ->join('LEFT', $db->quoteName('n4gvg__holiday_packages', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('d.package_id'));

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where($db->quoteName('d.itinerary') . ' LIKE ' . $search);
        }

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('d.published') . ' = ' . (int) $published);
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();
        return is_array($items) ? $items : array();
    }

    public function getPagination() {
        $pagination = parent::getPagination();
        if ($pagination === null) {
            $total = $this->getTotal();
            $limit = $this->getState('list.limit', 20);
            $limitstart = $this->getState('list.start', 0);
            $pagination = new \Joomla\CMS\Pagination\Pagination($total, $limitstart, $limit);
        }
        return $pagination;
    }
}