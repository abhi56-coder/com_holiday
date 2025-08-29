<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;

// Load Joomla core JavaScript and Bootstrap
HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.framework');

$listOrder     = $this->state->get('list.ordering');
$listDirn      = $this->state->get('list.direction');
$input         = Factory::getApplication()->getInput();
$packageId     = $input->getInt('package_id', $this->state->get('filter.package_id', 0));
$destinationId = $input->getInt('destination_id', $this->state->get('filter.destination_id', 0));
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=policies&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row">
        <?php if (!empty($this->sidebar)) : ?>
            <div id="j-sidebar-container" class="col-md-2 col-12">
                <?php echo $this->sidebar; ?>
            </div>
            <div id="j-main-container" class="col-md-10 col-12">
        <?php else : ?>
            <div id="j-main-container" class="col-12">
        <?php endif; ?>

        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="policyList">
                <thead>
                    <tr>
                        <th class="text-center" width="5%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center" width="1%">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </th>
                        <th>
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_TITLE', 'title', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_DESCRIPTION', 'description', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center" width="10%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->items)) : ?>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center"><?php echo (int) $item->id; ?></td>
                                <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                <td>
                                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=policy&layout=edit&id=' . (int) $item->id . '&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId); ?>">
                                        <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $description = htmlspecialchars($item->description, ENT_QUOTES, 'UTF-8');
                                    $maxLength = 50;
                                    if (strlen($description) > $maxLength) {
                                        $shortDescription = substr($description, 0, $maxLength) . '...';
                                        echo $shortDescription;
                                        echo ' <a href="' . Route::_('index.php?option=com_holidaypackages&view=policy&layout=edit&id=' . (int) $item->id . '&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId) . '" class="btn btn-link btn-sm">' . Text::_('COM_HOLIDAYPACKAGES_SEE_MORE') . '</a>';
                                    } else {
                                        echo $description;
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'policies.', true); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="alert alert-info">
                                    <span class="icon-info-circle" aria-hidden="true"></span>
                                    <?php echo Text::_('JERROR_NO_ITEMS_FOUND'); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><?php echo $this->pagination->getListFooter(); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter[package_id]" value="<?php echo (int)$packageId; ?>" />
        <input type="hidden" name="filter[destination_id]" value="<?php echo (int)$destinationId; ?>" />
        <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>