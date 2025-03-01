document.addEventListener('DOMContentLoaded', function () {
  const counters = document.querySelectorAll('.counter');

  counters.forEach(counter => {
    const target = +counter.getAttribute('data-target'); // Target number
    const startValue = 0; // Starting point for the counter
    const duration = 2000; // Duration of the animation in milliseconds
    const step = target / (duration / 16); // Calculate the increment based on 16ms per frame (60fps)

    const updateCounter = () => {
      const currentValue = +counter.innerText;

      if (currentValue < target) {
        const nextValue = Math.min(currentValue + step, target);
        counter.innerText = Math.floor(nextValue);

        requestAnimationFrame(updateCounter);
      } else {
        counter.innerText = target; 
      }
    };

    // Initialize counter
    counter.innerText = startValue;
    updateCounter();
  });
});



document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('#diva'); // Selecting by ID
  
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        } else {
          entry.target.classList.remove('visible');
        }
      });
    }, {
      threshold: 0.1
    });
  
    sections.forEach(section => {
      observer.observe(section);
    });
  });
  
