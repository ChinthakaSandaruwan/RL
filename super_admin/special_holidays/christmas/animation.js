document.addEventListener('DOMContentLoaded', () => {
    // Check if overlay already exists to prevent duplicates
    if (document.querySelector('.christmas-overlay')) return;

    const overlay = document.createElement('div');
    overlay.className = 'christmas-overlay';
    document.body.appendChild(overlay);

    let mouseX = 0;
    let targetWind = 0;
    let currentWind = 0;
    const windowWidth = window.innerWidth;

    // Track mouse movement
    document.addEventListener('mousemove', (e) => {
        // Calculate normalized position (-1 to 1)
        mouseX = (e.clientX / windowWidth) * 2 - 1;
        // Map to wind offset range (e.g., -200px to 200px)
        targetWind = mouseX * 300;
    });

    // Smooth wind transition
    function updateWind() {
        // Linear interpolation (lerp) for smoothness
        currentWind += (targetWind - currentWind) * 0.05;
        overlay.style.setProperty('--mouse-wind', `${currentWind}px`);
        requestAnimationFrame(updateWind);
    }
    updateWind();

    function createSnowflake() {
        if (document.hidden) return;

        const snowflake = document.createElement('div');
        snowflake.classList.add('snowflake');

        // Random properties for variety
        const size = Math.random() * 6 + 4; // 4px to 10px
        const startLeft = Math.random() * 100; // 0% to 100% (vw)
        const duration = Math.random() * 10 + 5; // 5s to 15s
        const opacity = Math.random() * 0.5 + 0.3; // 0.3 to 0.8
        const drift = (Math.random() - 0.5) * 100; // -50px to 50px natural drift

        // Apply styles
        snowflake.style.width = `${size}px`;
        snowflake.style.height = `${size}px`;
        snowflake.style.left = `${startLeft}vw`;
        snowflake.style.animationDuration = `${duration}s`;
        snowflake.style.setProperty('--drift', `${drift}px`);
        snowflake.style.setProperty('--opacity', opacity);

        overlay.appendChild(snowflake);

        // Remove after animation completes
        setTimeout(() => {
            snowflake.remove();
        }, duration * 1000);
    }

    // Dynamic spawn rate (more fast, less slow)
    // Initial burst
    for (let i = 0; i < 30; i++) {
        setTimeout(createSnowflake, Math.random() * 3000);
    }

    setInterval(createSnowflake, 150); // Create a snowflake every 150ms
});