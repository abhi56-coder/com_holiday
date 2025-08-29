<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTablePackages extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__holiday_packages', 'id', $db);
    }

    public function check()
    {
        if (trim($this->title) == '') {
            $this->setError(JText::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_TITLE'));
            return false;
        }
        return true;
    }
}