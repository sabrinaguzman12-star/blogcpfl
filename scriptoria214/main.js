
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  if (toggle && menu) toggle.addEventListener('click', () => menu.classList.toggle('open'));
  const filterInput = document.querySelector('[data-article-filter]');
  if (filterInput) {
    filterInput.addEventListener('input', function () {
      const value = this.value.toLowerCase().trim();
      document.querySelectorAll('[data-filter-item]').forEach((item) => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(value) ? '' : 'none';
      });
    });
  }
});
