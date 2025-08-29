<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class HolidaypackagesModelSummary extends ListModel {
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array('id', 'title', 'published');
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

        $query->select($db->quoteName(array('id', 'title', 'image', 'price', 'published')))
              ->from($db->quoteName('n4gvg__holiday_destinations'));

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where($db->quoteName('title') . ' LIKE ' . $search);
        }

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('published') . ' = ' . (int) $published);
        }

        // Add ordering
        $orderCol = $this->state->get('list.ordering', 'id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getDestinations() {
        $destinations = parent::getItems();
        if (!is_array($destinations)) {
            return array();
        }

        $db = $this->getDbo();
        foreach ($destinations as $destination) {
            // Fetch packages for this destination
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id', 'title', 'destination_id', 'duration', 'price', 'published')))
                  ->from($db->quoteName('n4gvg__holiday_packages'))
                  ->where('destination_id = ' . (int)$destination->id)
                  ->where('published = 1');
            $db->setQuery($query);
            $destination->packages = $db->loadObjectList();

            // Fetch details for each package
            foreach ($destination->packages as $package) {
                $query = $db->getQuery(true);
                $query->select($db->quoteName(array('id', 'package_id', 'itinerary', 'policies', 'summary', 'published')))
                      ->from($db->quoteName('n4gvg__holiday_details'))
                      ->where('package_id = ' . (int)$package->id)
                      ->where('published = 1');
                $db->setQuery($query);
                $package->details = $db->loadObject();
            }
        }

        return $destinations;
    }

    public function getItems() {
        return $this->getDestinations();
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

    public function publish($pks, $value = 1) {
        $table = \JTable::getInstance('Destination', 'HolidaypackagesTable');
        return $table->publish($pks, $value);
    }

    public function delete($pks) {
        $table = \JTable::getInstance('Destination', 'HolidaypackagesTable');
        foreach ($pks as $pk) {
            if (!$table->delete($pk)) {
                $this->setError($table->getError());
                return false;
            }
        }
        return true;
    }
}