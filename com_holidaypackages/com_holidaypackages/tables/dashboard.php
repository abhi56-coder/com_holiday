<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTableDashboard extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__holidaypackages_dashboard', 'id', $db);
    }

    public function check()
    {
        if (trim($this->title) == '') {
            $this->setError(JText::_('COM_HOLIDAYPACKAGES_ERROR_TITLE_REQUIRED'));
            return false;
        }
        return true;
    }
}