<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTableDetail extends Table {
    public function __construct(&$db) {
        parent::__construct('n4gvg__holiday_details', 'id', $db);
    }

    public function check() {
        if (trim($this->itinerary) == '') {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITINERARY_REQUIRED'));
            return false;
        }
        return parent::check();
    }

    public function publish($pks = null, $state = 1, $userId = 0) {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_details'))
            ->set($db->quoteName('published') . ' = ' . (int) $state)
            ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', (array) $pks)) . ')');
        $db->setQuery($query);
        return $db->execute();
    }

    public function toggle($id, $value) {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_details'))
            ->set($db->quoteName('published') . ' = ' . (int) $value)
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        return $db->execute();
    }
}