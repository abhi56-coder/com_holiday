<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

// Load Bootstrap JavaScript and CSS for the carousel
HTMLHelper::_('bootstrap.framework');
?>

<style>
    .carousel-item img {
        max-height: 500px;
        object-fit: cover;
        width: 100%;
    }
    .carousel-caption {
        background: rgba(0, 0, 0, 0.5);
        padding: 15px;
        border-radius: 5px;
    }
</style>

<div id="holidaypackagesCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php if (!empty($this->items)) : ?>
            <?php foreach ($this->items as $i => $item) : ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <?php
                    $imagePath = !empty($item->image) ? htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8') : 'media/com_holidaypackages/images/placeholder.jpg';
                    ?>
                    <img src="<?php echo $imagePath; ?>" class="d-block" alt="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="carousel-caption d-none d-md-block">
                        <h5><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h5>
                        <p>Price: ₹<?php echo number_format($item->price, 2); ?> | Duration: <?php echo htmlspecialchars($item->duration, ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=package&id=' . (int) $item->id); ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="carousel-item active">
                <div class="d-block w-100 text-center p-5">
                    <p><?php echo Text::_('COM_HOLIDAYPACKAGES_NO_PACKAGES'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#holidaypackagesCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#holidaypackagesCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>