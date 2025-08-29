<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

class JFormFieldPackage_id extends FormField
{
    protected $type = 'list';

    protected function getOptions()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $options = [];

        // Get current editing item ID
        $currentDetailId = (int) $input->getInt('id');

        // Get the current package_id of the editing item
        $currentPackageId = 0;
        if ($currentDetailId > 0) {
            $query->clear()
                ->select($db->quoteName('package_id'))
                ->from($db->quoteName('n4gvg__holiday_details'))
                ->where($db->quoteName('id') . ' = ' . $db->quote($currentDetailId));
            $db->setQuery($query);
            $currentPackageId = (int) $db->loadResult();
        }

        // Build main query: fetch published packages
        $query->clear()
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__holiday_packages'))
            ->where($db->quoteName('published') . ' = 1');

        // Exclude packages already used, except current one
        if ($currentPackageId > 0) {
            $query->where($db->quoteName('id') . ' NOT IN (
                SELECT package_id FROM ' . $db->quoteName('#__holiday_details') . '
                WHERE id != ' . (int) $currentDetailId . '
            )');
        } else {
            $query->where($db->quoteName('id') . ' NOT IN (
                SELECT package_id FROM ' . $db->quoteName('#__holiday_details') . '
            )');
        }

        $query->order('title ASC');
        $db->setQuery($query);

        try {
            $rows = $db->loadObjectList();

            foreach ($rows as $row) {
                $options[] = JHtml::_('select.option', $row->id, $row->title);
            }

            // If editing and current package is missing from list, add it manually
            if ($currentPackageId > 0 && !in_array($currentPackageId, array_column($options, 'value'))) {
                $query->clear()
                    ->select($db->quoteName(['id', 'title']))
                    ->from($db->quoteName('#__holiday_packages'))
                    ->where($db->quoteName('id') . ' = ' . (int) $currentPackageId);
                $db->setQuery($query);
                $currentPackage = $db->loadObject();
                if ($currentPackage) {
                    $options[] = JHtml::_('select.option', $currentPackage->id, $currentPackage->title);
                }
            }

        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading packages: ' . $e->getMessage(), 'error');
        }

        return array_merge(parent::getOptions(), $options);
    }
}
