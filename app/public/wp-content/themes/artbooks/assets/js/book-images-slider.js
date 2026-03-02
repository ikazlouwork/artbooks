(() => {
  const sliders = document.querySelectorAll('[data-ab-book-slider]');

  sliders.forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll('[data-ab-book-slider-slide]'));
    if (slides.length <= 1) {
      return;
    }

    const thumbs = Array.from(slider.querySelectorAll('[data-ab-book-slider-thumb]'));
    const dots = Array.from(slider.querySelectorAll('[data-ab-book-slider-dot]'));
    const prevButton = slider.querySelector('[data-ab-book-slider-prev]');
    const nextButton = slider.querySelector('[data-ab-book-slider-next]');

    let currentIndex = 0;

    const setActive = (nextIndex) => {
      const normalized = (nextIndex + slides.length) % slides.length;
      currentIndex = normalized;

      slides.forEach((slide, index) => {
        slide.classList.toggle('is-active', index === normalized);
      });

      thumbs.forEach((thumb, index) => {
        const isActive = index === normalized;
        thumb.classList.toggle('is-active', isActive);
        thumb.setAttribute('aria-current', isActive ? 'true' : 'false');
      });

      dots.forEach((dot, index) => {
        dot.classList.toggle('is-active', index === normalized);
      });
    };

    if (prevButton) {
      prevButton.addEventListener('click', () => {
        setActive(currentIndex - 1);
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', () => {
        setActive(currentIndex + 1);
      });
    }

    thumbs.forEach((thumb, index) => {
      thumb.addEventListener('click', () => {
        setActive(index);
      });
    });

    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        setActive(index);
      });
    });

    slider.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        setActive(currentIndex - 1);
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault();
        setActive(currentIndex + 1);
      }
    });

    setActive(0);
  });
})();
