<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTableItineraries extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__holiday_itineraries', 'id', $db);
    }

    public function check()
    {
        if (trim($this->day_number) == '') {
            $this->setError(JText::_('COM_HOLIDAYPACKAGES_DAY_NUMBER_REQUIRED'));
            return false;
        }
        return true;
    }
}