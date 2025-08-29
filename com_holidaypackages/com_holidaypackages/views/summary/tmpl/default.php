<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.modal'); // Load Bootstrap modal functionality

// Fallback values if $this->state is null
$listOrder = $this->state ? $this->state->get('list.ordering') : 'id';
$listDirn = $this->state ? $this->state->get('list.direction') : 'ASC';
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=summary'); ?>" method="post" name="adminForm" id="adminForm">
    <!-- <?php if (!empty($this->sidebar)) : ?>
        <div id="j-sidebar-container" class="span2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif; ?> -->

    <div class="clearfix"></div>
    <table class="table table-striped" id="summaryList">
        <thead>
            <tr>
                <th width="1%" class="nowrap center hidden-phone">
                    <?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
                </th>
                <th width="1%" class="nowrap center">
                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                </th>
                <th class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_HOLIDAYPACKAGES_FIELD_DESTINATION_LABEL', 'title', $listDirn, $listOrder); ?>
                </th>
                <th class="nowrap center">
                    <?php echo Text::_('COM_HOLIDAYPACKAGES_SUMMARY_NUMBER_OF_PACKAGES'); ?>
                </th>
                <th class="nowrap">
                    <?php echo Text::_('COM_HOLIDAYPACKAGES_PACKAGES_AND_DETAILS'); ?>
                </th>
                <th width="10%" class="nowrap center">
                    <?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($this->destinations) && is_array($this->destinations)) : ?>
                <?php foreach ($this->destinations as $i => $destination) : ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="center hidden-phone">
                            <?php echo (int) $destination->id; ?>
                        </td>
                        <td class="center">
                            <?php echo HTMLHelper::_('grid.id', $i, $destination->id); ?>
                        </td>
                        <td>
                            <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=destination.edit&id=' . (int) $destination->id); ?>">
                                <?php echo htmlspecialchars($destination->title, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td class="center">
                            <?php echo count($destination->packages); ?>
                        </td>
                        <td>
                            <?php if (!empty($destination->packages)) : ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($destination->packages as $j => $package) : ?>
                                        <li style="border-bottom: 1px solid #ddd; padding: 5px 0;">
                                            <a href="#packageModal-<?php echo $destination->id . '-' . $j; ?>" data-bs-toggle="modal" data-bs-target="#packageModal-<?php echo $destination->id . '-' . $j; ?>">
                                                <?php echo htmlspecialchars($package->title, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </li>

                                        <!-- Modal for Package Details -->
                                        <div class="modal fade" id="packageModal-<?php echo $destination->id . '-' . $j; ?>" tabindex="-1" role="dialog" aria-labelledby="packageModalLabel-<?php echo $destination->id . '-' . $j; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="packageModalLabel-<?php echo $destination->id . '-' . $j; ?>">
                                                            <?php echo htmlspecialchars($package->title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo Text::_('COM_HOLIDAYPACKAGES_DETAILS'); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if (!empty($package->details)) : ?>
                                                            <div class="package-detail">
                                                                <strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_ITINERARY_LABEL'); ?>:</strong>
                                                                <p><?php echo nl2br(htmlspecialchars($package->details->itinerary, ENT_QUOTES, 'UTF-8')); ?></p>
                                                            </div>
                                                            <div class="package-detail">
                                                                <strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_POLICIES_LABEL'); ?>:</strong>
                                                                <p><?php echo nl2br(htmlspecialchars($package->details->policies, ENT_QUOTES, 'UTF-8')); ?></p>
                                                            </div>
                                                            <div class="package-detail">
                                                                <strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_SUMMARY_LABEL'); ?>:</strong>
                                                                <p><?php echo nl2br(htmlspecialchars($package->details->summary, ENT_QUOTES, 'UTF-8')); ?></p>
                                                            </div>
                                                        <?php else : ?>
                                                            <p><?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'); ?>
                            <?php endif; ?>
                        </td>
                        <td class="center">
                            <?php echo HTMLHelper::_('jgrid.published', $destination->published, $i, 'summary.', true); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6"><?php echo Text::_('JERROR_NO_ITEMS_FOUND'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <?php echo isset($this->pagination) && $this->pagination instanceof \Joomla\CMS\Pagination\Pagination ? $this->pagination->getListFooter() : ''; ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

<style>
.package-detail {
    margin: 10px 0;
}
.package-detail strong {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}
.package-detail p {
    margin: 0;
    padding: 5px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.list-unstyled li {
    margin: 5px 0;
}
</style>