<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTablePolicies extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__holiday_policies', 'id', $db);
    }

    public function check()
    {
        // Add validation rules here if needed
        if (empty($this->title)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_TITLE_REQUIRED'));
            return false;
        }
        return parent::check();
    }
}