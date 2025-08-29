<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class HolidaypackagesModelHolidaypackages extends ListModel {
    public function getListQuery() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from($db->quoteName('#__holidaypackages'))
              ->where('published = 1');
        return $query;
    }
}
?>