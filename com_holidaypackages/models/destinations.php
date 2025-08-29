<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class HolidaypackagesModelDestinations extends ListModel {
    protected function getListQuery() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from($db->quoteName('n4gvg__holiday_destinations'))
              ->where('published = 1');
        return $query;
    }
}