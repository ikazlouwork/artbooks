(() => {
  const dataNode = document.getElementById('ab-home-hero-data');

  if (!dataNode) {
    return;
  }

  let books;

  try {
    books = JSON.parse(dataNode.textContent || '[]');
  } catch (_error) {
    return;
  }

  if (!Array.isArray(books) || books.length < 2) {
    return;
  }

  const titleEl = document.querySelector('[data-hero-title]');
  const descriptionEl = document.querySelector('[data-hero-description]');
  const authorEl = document.querySelector('[data-hero-author]');
  const coverLinkEl = document.querySelector('[data-hero-cover]');
  const buyGridEl = document.querySelector('[data-hero-buy-grid]');

  if (!titleEl || !descriptionEl || !coverLinkEl) {
    return;
  }

  const buildCover = (book) => {
    coverLinkEl.replaceChildren();

    if (typeof book.cover_url === 'string' && book.cover_url !== '') {
      const img = document.createElement('img');
      img.src = book.cover_url;
      img.alt = typeof book.title === 'string' ? book.title : '';
      img.loading = 'eager';
      img.decoding = 'async';
      coverLinkEl.appendChild(img);
      return;
    }

    const placeholder = document.createElement('span');
    placeholder.className = 'ab-book-cover-placeholder';
    coverLinkEl.appendChild(placeholder);
  };

  const decodeText = (value) => {
    if (typeof value !== 'string') {
      return '';
    }

    const textarea = document.createElement('textarea');
    textarea.innerHTML = value;
    return textarea.value;
  };

  const buildBuyLinks = (book) => {
    if (!buyGridEl) {
      return;
    }

    buyGridEl.replaceChildren();

    if (!Array.isArray(book.buy_links) || book.buy_links.length === 0) {
      buyGridEl.hidden = true;
      return;
    }

    book.buy_links.slice(0, 6).forEach((item) => {
      if (!item || typeof item.url !== 'string' || typeof item.label !== 'string') {
        return;
      }

      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = item.url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer nofollow';
      a.textContent = item.label;
      li.appendChild(a);
      buyGridEl.appendChild(li);
    });

    buyGridEl.hidden = buyGridEl.children.length === 0;
  };

  const renderBook = (book) => {
    const title = decodeText(book.title);
    const description = decodeText(book.description);
    const author = decodeText(book.author);
    const permalink = typeof book.permalink === 'string' ? book.permalink : '';

    titleEl.textContent = title;
    descriptionEl.textContent = description;

    if (authorEl) {
      authorEl.textContent = author;
      authorEl.hidden = author === '';
    }

    if (permalink !== '') {
      coverLinkEl.href = permalink;
      coverLinkEl.setAttribute('aria-label', `Open ${title}`);
    } else {
      coverLinkEl.removeAttribute('href');
      coverLinkEl.setAttribute('aria-label', title);
    }

    buildCover(book);
    buildBuyLinks(book);
  };

  let currentIndex = 0;

  window.setInterval(() => {
    currentIndex = (currentIndex + 1) % books.length;
    renderBook(books[currentIndex]);
  }, 10000);
})();
