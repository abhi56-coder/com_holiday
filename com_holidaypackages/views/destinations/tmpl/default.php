<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.framework');
?>

<!-- Link to the external CSS -->
<?php
HTMLHelper::_('stylesheet', 'com_holidaypackages/css/style.css', array('version' => 'auto', 'relative' => true));
?>

<style>
    .carousel-wrapper {
        position: relative;
        max-width: 100%;
        overflow: hidden;
        padding: 30px 40px;
        background: #fff;
    }

    .carousel-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: left;
    }

    .carousel-track-container {
        overflow-x: auto;
        position: relative;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    .carousel-track-container::-webkit-scrollbar {
        display: none; /* Chrome, Safari, and Opera */
    }

    .carousel-track {
        display: flex;
        gap: 15px;
        transition: transform 0.5s ease;
        will-change: transform;
    }

    .destination-card {
        min-width: 200px;
        flex: 0 0 auto;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        color: #fff; /* Ensure parent text color is white */
    }

    .destination-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        border-radius: 12px;
    }

    .destination-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 12px;
        font-size: 14px;
        text-align: left;
        color: #fff; /* Explicitly set text color to white */
    }

    .destination-info strong {
        font-size: 15px;
        display: block;
        margin-bottom: 5px;
        color: #fff; /* Ensure title is white */
    }

    .destination-info .duration {
        font-size: 12px;
        display: block;
        margin-bottom: 5px;
        color: #fff; /* Ensure duration is white */
    }

    .destination-info span {
        color: #fff; /* Ensure price is white */
    }

    .carousel-control-prev,
    .carousel-control-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        z-index: 2;
    }

    .carousel-control-prev {
        left: 10px;
    }

    .carousel-control-next {
        right: 10px;
    }
</style>

<!-- <div class="carousel-wrapper">
    <div class="carousel-title">Best Selling Destinations</div>

    <button class="carousel-control-prev" onclick="scrollCarousel(-1)">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" onclick="scrollCarousel(1)">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>

    <div class="carousel-track-container">
        <div class="carousel-track" id="carouselTrack">
            <?php if (!empty($this->items)) : ?>
                <?php foreach ($this->items as $item) : ?>
                    <?php
                    $detailsUrl = Route::_('index.php?option=com_holidaypackages&view=packages&id=' . (int) $item->id);
                    $imagePath = !empty($item->image) ? JUri::root() . htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8') : JUri::root() . 'media/com_holidaypackages/images/placeholder.jpg';
                    $duration = !empty($item->duration) ? htmlspecialchars($item->duration, ENT_QUOTES, 'UTF-8') : '5 Days/4 Nights'; // Fallback duration
                    ?>
                    <div class="destination-card">
                        <a href="<?php echo $detailsUrl; ?>" class="destination-box">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="destination-info">
                                <strong><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="duration"><?php echo $duration; ?></span>
                                <span>Starting at ₹<?php echo number_format($item->price, 0); ?> per person</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No destinations found.</p>
            <?php endif; ?>
        </div>
    </div>
</div> -->

<!-- <script>
    const track = document.getElementById('carouselTrack');
    let scrollPosition = 0;
    const scrollAmount = 230;

    function scrollCarousel(direction) {
        const containerWidth = track.offsetWidth;
        const scrollLimit = track.scrollWidth - containerWidth;

        scrollPosition += direction * scrollAmount;

        // Loop back to start if reaching the end
        if (scrollPosition >= scrollLimit) {
            scrollPosition = 0;
        } else if (scrollPosition < 0) {
            scrollPosition = scrollLimit;
        }

        track.style.transform = `translateX(-${scrollPosition}px)`;
    }

    // Auto-slide every 3 seconds
    setInterval(() => {
        scrollCarousel(1);
    }, 1000);
</script> -->

<?php
// Include additional JS if needed
// HTMLHelper::_('script', 'com_holidaypackages/js/script.js', array('version' => 'auto', 'relative' => true));
?>