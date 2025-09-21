// Optional: Random floating effect (extra fun)
document.querySelectorAll('.decor').forEach(el => {
  el.style.transition = "transform 2s ease-in-out";
  setInterval(() => {
    const x = Math.random() * 20 - 10; // random left/right
    const y = Math.random() * 20 - 10; // random up/down
    el.style.transform = `translate(${x}px, ${y}px)`;
  }, 3000);
});
