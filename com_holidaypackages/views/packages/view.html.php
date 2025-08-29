<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;

class HolidaypackagesViewPackages extends HtmlView
{
    protected $items;
    protected $destinations;
    protected $filterOptions;
    protected $packageCounts;
    protected $sectionDetails;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $task = $input->getCmd('task', '');
        $format = $input->getCmd('format', 'html');

        if ($task === 'filterPackages' && $format === 'raw') {
            $this->items = $this->get('Items');
            $this->destinations = $this->getModel()->getDestinationsWithCounts();
            $this->filterOptions = $this->getModel()->getFilterOptions($input->getInt('destination_id', 0));
            $this->packageCounts = $this->getModel()->getPackageCountsForTabs($input->getInt('destination_id', 0));
            $this->sectionDetails = $this->getModel()->getSectionTypesAndActivitiesCount($input->getInt('destination_id', 0));
            parent::display($tpl);
            return;
        }

        $destinationId = $input->getInt('id', 0);

        $model = $this->getModel();

        $this->items = $this->get('Items');

        if (is_array($this->items) || is_object($this->items)) {
            foreach ($this->items as &$item) {
                $item->section_types = $model->processSectionTypes($item->section_types ?? '');
            }
        }

        $this->destinations = $model->getDestinationsWithCounts();
        $this->filterOptions = $model->getFilterOptions($destinationId);
        $this->packageCounts = $model->getPackageCountsForTabs($destinationId);
        $this->sectionDetails = $model->getSectionTypesAndActivitiesCount($destinationId);

        $errors = $this->get('Errors');
        if (count($errors)) {
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            return false;
        }

        parent::display($tpl);
    }
}