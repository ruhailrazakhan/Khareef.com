(function(){
  const header = document.getElementById('lpHeader');
  const burger = document.getElementById('lpBurger');
  const mobile = document.getElementById('lpMobile');

  // solid header on scroll (nice on hero video pages)
  function onScroll(){
    const solid = window.scrollY > 20;
    header?.classList.toggle('isSolid', solid);
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();

  // mobile toggle
  burger?.addEventListener('click', () => {
    const isOpen = !mobile.hasAttribute('hidden');
    burger.setAttribute('aria-expanded', String(!isOpen));
    if (isOpen) mobile.setAttribute('hidden', '');
    else mobile.removeAttribute('hidden');
  });

  // desktop dropdown logic
  document.querySelectorAll('.lpLink[data-menu]').forEach(btn => {
    const key = btn.getAttribute('data-menu');
    const item = btn.closest('.lpItem');

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const isOpen = item.classList.contains('open');

      // close others
      document.querySelectorAll('.lpItem.open').forEach(x => {
        x.classList.remove('open');
        const b = x.querySelector('.lpLink[data-menu]');
        if (b) b.setAttribute('aria-expanded','false');
      });

      item.classList.toggle('open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  // click outside closes dropdowns
  document.addEventListener('click', (e) => {
    const inside = e.target.closest('.lpItem');
    if (inside) return;
    document.querySelectorAll('.lpItem.open').forEach(x => {
      x.classList.remove('open');
      const b = x.querySelector('.lpLink[data-menu]');
      if (b) b.setAttribute('aria-expanded','false');
    });
  });
})();