<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesTableCities extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__cities', 'id', $db);
    }

    public function check()
    {
        if (trim($this->name) == '') {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CITY_NAME_REQUIRED'));
            return false;
        }
        return true;
    }
}
