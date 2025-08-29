<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;

$app       = Factory::getApplication();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$user      = Factory::getUser();
$canEdit   = $user->authorise('core.edit', 'com_holidaypackages');

HTMLHelper::_('behavior.multiselect');
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=cities'); ?>" method="post" name="adminForm" id="adminForm">
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

            <!-- Search Tools -->
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info mt-3">
                    <span class="icon-info-circle" aria-hidden="true"></span>
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered" id="cityList">
                        <thead class="table-light">
                            <tr>
                            
                                <th class="text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
                                </th>

                                    <th class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>

                                <th>
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_CITY_NAME', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'c.published', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>

                                   <td class="text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                 
                                    <td>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=editcity.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->name); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->name); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'cities.', $canEdit); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <?php echo $this->pagination->getListFooter(); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
