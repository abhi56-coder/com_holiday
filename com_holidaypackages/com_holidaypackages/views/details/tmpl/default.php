<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=details'); ?>" method="post" name="adminForm" id="adminForm">
    <?php if (!empty($this->sidebar)) : ?>
        <div id="j-sidebar-container" class="span2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif; ?>

    <div class="clearfix"></div>
    <table class="table table-striped" id="detailList">
        <thead>
            <tr>
                <th width="1%" class="nowrap center hidden-phone">
                    <?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
                </th>
                <th width="1%" class="nowrap center">
                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                </th>
                <th class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_HOLIDAYPACKAGES_FIELD_PACKAGE_LABEL', 'package_id', $listDirn, $listOrder); ?>
                </th>
                <th class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_HOLIDAYPACKAGES_FIELD_ITINERARY_LABEL', 'itinerary', $listDirn, $listOrder); ?>
                </th>
                <th class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_HOLIDAYPACKAGES_FIELD_POLICIES_LABEL', 'policies', $listDirn, $listOrder); ?>
                </th>
                <th class="nowrap">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_HOLIDAYPACKAGES_FIELD_SUMMARY_LABEL', 'summary', $listDirn, $listOrder); ?>
                </th>
                <th width="10%" class="nowrap center">
                    <?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'published', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($this->items)) : ?>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="center hidden-phone">
                            <?php echo (int) $item->id; ?>
                        </td>
                        <td class="center">
                            <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                        </td>
                      <td>
    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=detail.edit&id=' . (int) $item->id); ?>">
        <?php echo htmlspecialchars($item->package_title ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </a>
</td>

                        <td>
                            <span class="hasTooltip" title="<?php echo htmlspecialchars($item->itinerary, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo substr(htmlspecialchars($item->itinerary, ENT_QUOTES, 'UTF-8'), 0, 50) . (strlen($item->itinerary) > 50 ? '...' : ''); ?>
                            </span>
                        </td>
                        <td>
                            <span class="hasTooltip" title="<?php echo htmlspecialchars($item->policies, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo substr(htmlspecialchars($item->policies, ENT_QUOTES, 'UTF-8'), 0, 50) . (strlen($item->policies) > 50 ? '...' : ''); ?>
                            </span>
                        </td>
                        <td>
                            <span class="hasTooltip" title="<?php echo htmlspecialchars($item->summary, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo substr(htmlspecialchars($item->summary, ENT_QUOTES, 'UTF-8'), 0, 50) . (strlen($item->summary) > 50 ? '...' : ''); ?>
                            </span>
                        </td>
                        <td class="center">
                            <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'details.', true); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php echo Text::_('JERROR_NO_ITEMS_FOUND'); ?></td>
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

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>