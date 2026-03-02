(() => {
  const sliders = document.querySelectorAll('[data-ab-book-slider]');
  const body = document.body;

  const modal = document.createElement('div');
  modal.className = 'ab-lightbox';
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="ab-lightbox-backdrop" data-ab-lightbox-close></div>
    <div class="ab-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Image preview">
      <button type="button" class="ab-lightbox-close" aria-label="Close popup" data-ab-lightbox-close>&times;</button>
      <button type="button" class="ab-lightbox-nav ab-lightbox-nav-prev" aria-label="Previous image" data-ab-lightbox-prev>&larr;</button>
      <figure class="ab-lightbox-figure">
        <img class="ab-lightbox-image" alt="" data-ab-lightbox-image>
      </figure>
      <button type="button" class="ab-lightbox-nav ab-lightbox-nav-next" aria-label="Next image" data-ab-lightbox-next>&rarr;</button>
    </div>
  `;
  body.appendChild(modal);

  const modalImage = modal.querySelector('[data-ab-lightbox-image]');
  const modalPrev = modal.querySelector('[data-ab-lightbox-prev]');
  const modalNext = modal.querySelector('[data-ab-lightbox-next]');
  const modalCloseItems = Array.from(modal.querySelectorAll('[data-ab-lightbox-close]'));

  let activeSlider = null;
  let activeIndex = 0;
  let activeItems = [];

  const syncModalImage = () => {
    if (!modalImage || activeItems.length === 0) {
      return;
    }

    const item = activeItems[activeIndex];
    if (!item || !item.href) {
      return;
    }

    modalImage.setAttribute('src', item.href);
    modalImage.setAttribute('alt', item.alt || '');

    const hasMultiple = activeItems.length > 1;
    if (modalPrev) {
      modalPrev.hidden = !hasMultiple;
    }
    if (modalNext) {
      modalNext.hidden = !hasMultiple;
    }
  };

  const openModal = (slider, items, index) => {
    activeSlider = slider;
    activeItems = items;
    activeIndex = index;

    syncModalImage();

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    body.classList.add('ab-lightbox-open');
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    body.classList.remove('ab-lightbox-open');
    activeSlider = null;
    activeItems = [];
    activeIndex = 0;
  };

  const moveModal = (step) => {
    if (activeItems.length <= 1) {
      return;
    }
    activeIndex = (activeIndex + step + activeItems.length) % activeItems.length;
    syncModalImage();
    if (activeSlider && typeof activeSlider.setActive === 'function') {
      activeSlider.setActive(activeIndex);
    }
  };

  modalCloseItems.forEach((item) => {
    item.addEventListener('click', closeModal);
  });

  if (modalPrev) {
    modalPrev.addEventListener('click', () => moveModal(-1));
  }

  if (modalNext) {
    modalNext.addEventListener('click', () => moveModal(1));
  }

  document.addEventListener('keydown', (event) => {
    if (!modal.classList.contains('is-open')) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      closeModal();
    }

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      moveModal(-1);
    }

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      moveModal(1);
    }
  });

  sliders.forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll('[data-ab-book-slider-slide]'));
    const thumbs = Array.from(slider.querySelectorAll('[data-ab-book-slider-thumb]'));
    const dots = Array.from(slider.querySelectorAll('[data-ab-book-slider-dot]'));
    const prevButton = slider.querySelector('[data-ab-book-slider-prev]');
    const nextButton = slider.querySelector('[data-ab-book-slider-next]');
    const links = slides
      .map((slide) => {
        const anchor = slide.querySelector('a');
        const image = slide.querySelector('img');
        if (!anchor) {
          return null;
        }
        return {
          href: anchor.getAttribute('href') || '',
          alt: image ? image.getAttribute('alt') || '' : '',
        };
      })
      .filter((item) => item && item.href);

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

      if (modal.classList.contains('is-open') && activeSlider === slider) {
        activeIndex = normalized;
        syncModalImage();
      }
    };

    slider.setActive = setActive;

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

    slides.forEach((slide, index) => {
      const anchor = slide.querySelector('a');
      if (!anchor) {
        return;
      }

      anchor.addEventListener('click', (event) => {
        event.preventDefault();
        setActive(index);
        openModal(slider, links, index);
      });
    });

    slider.addEventListener('keydown', (event) => {
      if (modal.classList.contains('is-open')) {
        return;
      }

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
