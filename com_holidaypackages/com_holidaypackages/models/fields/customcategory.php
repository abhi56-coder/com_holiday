<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

class JFormFieldCustomcategory extends ListField
{
    protected $type = 'customcategory';

    protected function getOptions()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('DISTINCT category AS value, category AS text')
            ->from($db->quoteName('n4gvg__holidaypackages_dashboard'))
            ->where($db->quoteName('category') . ' != ' . $db->quote(''))
            ->order('category ASC');

        $db->setQuery($query);
        $options = $db->loadObjectList();

        // Merge with default options
        $defaultOptions = parent::getOptions();
        // array_unshift($defaultOptions, HTMLHelper::_('select.option', '', Text::_('COM_HOLIDAYPACKAGES_SELECT_CATEGORY')));

        return array_merge($defaultOptions, $options);
    }
}