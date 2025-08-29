<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTableDestination extends Table {
    public function __construct($db) {
        parent::__construct('n4gvg__holiday_destinations', 'id', $db);
    }

    public function check() {
        if (trim($this->title) == '') {
            $this->setError('Title is required.');
            return false;
        }
        return parent::check();
    }
}