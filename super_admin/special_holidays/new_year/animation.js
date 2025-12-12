document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.createElement('div');
    overlay.className = 'newyear-overlay';
    document.body.appendChild(overlay);

    function createFirework() {
        if (document.hidden) return;

        const fw = document.createElement('div');
        fw.className = 'firework';

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const color = `hsl(${Math.random() * 360}, 100%, 50%)`;

        fw.style.left = x + 'vw';
        fw.style.top = y + 'vh';
        fw.style.backgroundColor = color;
        fw.style.boxShadow = `0 0 10px ${color}, 0 0 20px ${color}`;

        overlay.appendChild(fw);

        setTimeout(() => fw.remove(), 1000);
    }

    setInterval(createFirework, 800);
});
