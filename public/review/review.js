document.addEventListener('DOMContentLoaded', function () {
    const track = document.querySelector('.review-track-wrapper');
    const prevBtn = document.querySelector('.review-prev');
    const nextBtn = document.querySelector('.review-next');

    if (!track) return;

    // Scroll amount calculation (one card width + gap)
    const getScrollAmount = () => {
        const card = document.querySelector('.review-card');
        if (card) {
            // card width + gap (approx 1.5rem = 24px)
            return card.offsetWidth + 24;
        }
        return 300;
    };

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            track.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
        });
    }

    // Optional: Hide/Show arrows based on scroll position
    const updateArrows = () => {
        if (!prevBtn || !nextBtn) return;

        if (track.scrollLeft <= 10) {
            prevBtn.style.opacity = '0.5';
            prevBtn.style.pointerEvents = 'none';
        } else {
            prevBtn.style.opacity = '1';
            prevBtn.style.pointerEvents = 'auto';
        }

        if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
            nextBtn.style.opacity = '0.5';
            nextBtn.style.pointerEvents = 'none';
        } else {
            nextBtn.style.opacity = '1';
            nextBtn.style.pointerEvents = 'auto';
        }
    };

    track.addEventListener('scroll', updateArrows);
    updateArrows(); // Initial check

    // Auto-scroll loop (pauses on hover)
    let autoScroll;
    const startAutoScroll = () => {
        autoScroll = setInterval(() => {
            if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
                track.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
            }
        }, 5000);
    };

    const stopAutoScroll = () => clearInterval(autoScroll);

    startAutoScroll();
    track.addEventListener('mouseenter', stopAutoScroll);
    track.addEventListener('mouseleave', startAutoScroll);
});
