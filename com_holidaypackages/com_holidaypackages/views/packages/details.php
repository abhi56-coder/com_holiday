<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

// Fetch the package ID from the request
$packageId = $this->input->getInt('package_id', 0);
if (!$packageId) {
    echo Text::_('JERROR_NO_PACKAGE_ID');
    return;
}

// Fetch package details from the database
$db = Factory::getDbo();
$query = $db->getQuery(true)
    ->select($db->quoteName(array('p.id', 'p.title', 'p.image', 'p.duration', 'p.price', 'd.title AS destination_title')))
    ->from($db->quoteName('n4gvg__holiday_packages', 'p'))
    ->join('LEFT', $db->quoteName('n4gvg__holiday_destinations', 'd') . ' ON ' . $db->quoteName('p.destination_id') . ' = ' . $db->quoteName('d.id'))
    ->where($db->quoteName('p.id') . ' = ' . (int) $packageId);
$db->setQuery($query);
$package = $db->loadObject();

if (!$package) {
    echo Text::_('JERROR_PACKAGE_NOT_FOUND');
    return;
}

// Fetch package details (itinerary, policies, summary)
$query = $db->getQuery(true)
    ->select($db->quoteName(array('id', 'package_id', 'itinerary', 'policies', 'summary')))
    ->from($db->quoteName('n4gvg__holiday_details'))
    ->where($db->quoteName('package_id') . ' = ' . (int) $packageId)
    ->where($db->quoteName('published') . ' = 1');
$db->setQuery($query);
$details = $db->loadObject();
?>

<div class="package-details">
    <h3><?php echo htmlspecialchars($package->title, ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_DESTINATION_LABEL'); ?>:</strong> <?php echo htmlspecialchars($package->destination_title, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_DURATION_LABEL'); ?>:</strong> <?php echo htmlspecialchars($package->duration, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_PRICE_LABEL'); ?>:</strong> <?php echo htmlspecialchars($package->price, ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (!empty($package->image)) : ?>
        <div class="package-image">
            <strong><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_IMAGE_LABEL'); ?>:</strong><br>
            <img src="<?php echo Uri::root() . htmlspecialchars($package->image, ENT_QUOTES, 'UTF-8'); ?>" 
                 alt="<?php echo htmlspecialchars($package->title, ENT_QUOTES, 'UTF-8'); ?>" 
                 style="max-width: 200px; max-height: 200px; margin-top: 10px;" />
        </div>
    <?php endif; ?>

    <table class="table table-bordered">
        <tbody>
            <tr>
                <th><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_ITINERARY_LABEL'); ?></th>
                <td>
                    <?php if ($details && !empty($details->itinerary)) : ?>
                        <div class="large-text"><?php echo nl2br(htmlspecialchars($details->itinerary, ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php else : ?>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_POLICIES_LABEL'); ?></th>
                <td>
                    <?php if ($details && !empty($details->policies)) : ?>
                        <div class="large-text"><?php echo nl2br(htmlspecialchars($details->policies, ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php else : ?>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_SUMMARY_LABEL'); ?></th>
                <td>
                    <?php if ($details && !empty($details->summary)) : ?>
                        <div class="large-text"><?php echo nl2br(htmlspecialchars($details->summary, ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php else : ?>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'); ?>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<style>
.package-details h3 {
    margin-bottom: 15px;
}
.package-details p {
    margin: 5px 0;
}
.package-image {
    margin: 10px 0;
}
.large-text {
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.table th {
    width: 20%;
    vertical-align: top;
}
</style>