<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$app        = Factory::getApplication();
$input      = $app->input;
$packageId  = $input->getInt('package_id', 0);
$destinationId = $input->getInt('destination_id', 0);

if ($packageId == 0) {
    $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
    $app->redirect(Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . (int)$destinationId, false));
    return;
}



$listOrder  = $this->state->get('list.ordering', 'day_number');
$listDirn   = $this->state->get('list.direction', 'asc');
$user       = Factory::getUser();
$canEdit    = $user->authorise('core.edit', 'com_holidaypackages');
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId); ?>" method="post" name="adminForm" id="adminForm">
    <div class="container-fluid">
        <div class="row">
            <?php if (!empty($this->sidebar)) : ?>
                <div id="j-sidebar-container" class="col-md-2 col-12 mb-3">
                    <?php echo $this->sidebar; ?>
                </div>
                <div id="j-main-container" class="col-md-10 col-12">
            <?php else : ?>
                <div id="j-main-container" class="col-12">
            <?php endif; ?>

  

            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="itineraryList">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
                            </th>
                            <th class="text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th class="text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_DAY_NUMBER', 'day_number', $listDirn, $listOrder); ?>
                            </th>
                            <th class="text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_DATE', 'date', $listDirn, $listOrder); ?>
                            </th>
                            <th>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_PLACE_NAME', 'place_name', $listDirn, $listOrder); ?>
                            </th>
                            <th class="text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_STATUS', 'status', $listDirn, $listOrder); ?>
                            </th>
                            <th class="text-center">
                                <?php echo Text::_('JACTION_EDIT'); ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($this->items)) : ?>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>
                                    <td class="text-center"><?php echo (int)$item->id; ?></td>
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                    <td class="text-center"><?php echo $item->day_number; ?></td>
                                    <td class="text-center"><?php echo $item->date; ?></td>
                                    <td><?php echo htmlspecialchars($item->place_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->status, $i, 'itineraries.', $canEdit); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($canEdit) : ?>
                                            <a class="btn btn-sm btn-primary" href="<?php echo Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . (int)$item->id . '&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId); ?>">
                                                <span class="icon-edit"></span> <?php echo Text::_('JACTION_EDIT'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center"><?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="7">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <input type="hidden" name="package_id" value="<?php echo (int)$packageId; ?>" />
            <input type="hidden" name="destination_id" value="<?php echo (int)$destinationId; ?>" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
