document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.createElement('div');
    overlay.className = 'christmas-overlay';
    document.body.appendChild(overlay);

    function createSnowflake() {
        if (document.hidden) return; // Don't animate in background

        const snowflake = document.createElement('div');
        snowflake.classList.add('snowflake');
        snowflake.innerHTML = '❄';

        // Random properties
        const startLeft = Math.random() * 100;
        const duration = Math.random() * 5 + 5; // 5-10s
        const size = Math.random() * 10 + 15; // 15-25px

        snowflake.style.left = startLeft + 'vw';
        snowflake.style.animationDuration = duration + 's';
        snowflake.style.fontSize = size + 'px';

        overlay.appendChild(snowflake);

        // Remove after animation
        setTimeout(() => {
            snowflake.remove();
        }, duration * 1000);
    }

    // lower creation rate
    setInterval(createSnowflake, 300);
});