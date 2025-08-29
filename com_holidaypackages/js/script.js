document.addEventListener('DOMContentLoaded', function() {
    const carouselItems = document.getElementById('carouselItems');
    const leftArrow = document.getElementById('leftArrow');
    const rightArrow = document.getElementById('rightArrow');

    // Check if there are items to scroll
    if (carouselItems.children.length > 0) {
        const itemWidth = carouselItems.children[0].offsetWidth + 15; // Width of item + gap

        leftArrow.addEventListener('click', function() {
            carouselItems.scrollLeft -= itemWidth * 3; // Scroll left by 3 items
        });

        rightArrow.addEventListener('click', function() {
            carouselItems.scrollLeft += itemWidth * 3; // Scroll right by 3 items
        });

        // Show/hide arrows based on scroll position
        carouselItems.addEventListener('scroll', function() {
            leftArrow.style.display = carouselItems.scrollLeft > 0 ? 'block' : 'none';
            rightArrow.style.display = carouselItems.scrollLeft < (carouselItems.scrollWidth - carouselItems.clientWidth) ? 'block' : 'none';
        });

        // Initial arrow visibility
        leftArrow.style.display = 'none';
        rightArrow.style.display = carouselItems.scrollWidth > carouselItems.clientWidth ? 'block' : 'none';
    } else {
        // Hide arrows if no items
        leftArrow.style.display = 'none';
        rightArrow.style.display = 'none';
    }
});
