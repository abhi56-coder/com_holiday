<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

class HolidaypackagesHelper
{
    public static function addSubmenu($vName = 'packages')
    {
        HTMLHelper::_('sidebar.addEntry',
            Text::_('COM_HOLIDAYPACKAGES_SUBMENU_PACKAGES'),
            'index.php?option=com_holidaypackages&view=packages',
            $vName == 'packages'
        );

        HTMLHelper::_('sidebar.addEntry',
            Text::_('COM_HOLIDAYPACKAGES_SUBMENU_DETAILS'),
            'index.php?option=com_holidaypackages&view=details',
            $vName == 'details'
        );

        HTMLHelper::_('sidebar.addEntry',
            Text::_('COM_HOLIDAYPACKAGES_SUBMENU_DESTINATIONS'),
            'index.php?option=com_holidaypackages&view=destinations&category=',
            $vName == 'destinations'
        );
    }
}