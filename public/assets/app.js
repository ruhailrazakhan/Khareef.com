(async function(){
  const listEl = document.getElementById('list');
  const filtersEl = document.getElementById('filters');
  const searchEl = document.getElementById('search');

  const res = await fetch('api.php', {cache:'no-store'});
  const {settings, types, locations} = await res.json();

  if (!settings.google_api_key) {
    listEl.innerHTML = '<div style="color:#b00020;padding:10px">Admin must set Google Maps API key.</div>';
    return;
  }

  await new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(settings.google_api_key)}`;
    s.onload = resolve;
    s.onerror = reject;
    document.head.appendChild(s);
  });

  const ALWAYS = settings.always_on_type || 'Location';
  const enabled = new Set([ALWAYS]);

  // Filters
  filtersEl.innerHTML = '';
  types.filter(t => Number(t.is_active)===1).forEach(t => {
    const label = document.createElement('label');
    label.className = 'filter';

    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = (t.name === ALWAYS) ? true : false;
    if (t.name === ALWAYS) cb.disabled = true;

    cb.addEventListener('change', () => {
      if (cb.checked) enabled.add(t.name);
      else enabled.delete(t.name);
      render();
      syncMarkers();
    });

    const span = document.createElement('span');
    span.textContent = t.name;

    label.appendChild(cb);
    label.appendChild(span);
    filtersEl.appendChild(label);
  });

  const map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: Number(settings.default_lat), lng: Number(settings.default_lng)},
    zoom: Number(settings.default_zoom || 11),
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
  });

  const info = new google.maps.InfoWindow();
  const markers = new Map();

  function escapeHtml(str){
    return String(str||'').replace(/[&<>"']/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[s]));
  }

  // markers
  locations.forEach(loc => {
    const m = new google.maps.Marker({
      position: {lat: Number(loc.lat), lng: Number(loc.lng)},
      map,
      title: loc.title,
      icon: loc.type_icon || undefined,
    });

    m.addListener('click', () => {
      setActive(loc.id);

      const dest = encodeURIComponent(`${loc.lat},${loc.lng}`);

      info.setContent(`
        <div style="max-width:320px;font-size:14px;line-height:1.3">
          <div style="font-weight:700;margin-bottom:4px;">${escapeHtml(loc.title)}</div>
          <div style="color:#555;margin-bottom:10px;">${escapeHtml(loc.address || '')}</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
            <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=driving"
              style="text-decoration:none;padding:8px 10px;border-radius:12px;background:#111;color:#fff;font-size:12px;">🚗 Driving</a>
            <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=walking"
              style="text-decoration:none;padding:8px 10px;border-radius:12px;background:#111;color:#fff;font-size:12px;">🚶 Walking</a>
            <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=transit"
              style="text-decoration:none;padding:8px 10px;border-radius:12px;background:#111;color:#fff;font-size:12px;">🚌 Transit</a>
          </div>
          ${loc.description ? `<div style="color:#333;margin-top:6px">${escapeHtml(loc.description).slice(0,220)}${loc.description.length>220?'…':''}</div>` : ''}
        </div>
      `);
      info.open({anchor:m, map});
      scrollToCard(loc.id);
    });

    markers.set(loc.id, {marker:m, data:loc});
  });

  function setActive(id){
    document.querySelectorAll('.card').forEach(c => c.classList.toggle('active', Number(c.dataset.id) === Number(id)));
  }

  function scrollToCard(id){
    const el = document.querySelector(`.card[data-id="${id}"]`);
    if (el) el.scrollIntoView({behavior:'smooth', block:'nearest'});
  }

  function passesFilters(loc){
    const q = (searchEl.value||'').trim().toLowerCase();
    const matchText =
      (loc.title||'').toLowerCase().includes(q) ||
      (loc.address||'').toLowerCase().includes(q) ||
      (loc.short_text||'').toLowerCase().includes(q);

    const matchType = (loc.type_name === ALWAYS) || enabled.has(loc.type_name);
    return matchText && matchType;
  }

  function render(){
    const filtered = locations.filter(passesFilters);
    listEl.innerHTML = '';
    if (!filtered.length) {
      listEl.innerHTML = '<div style="color:#666;padding:10px">No results</div>';
      return;
    }
    filtered.forEach(loc => {
      const div = document.createElement('div');
      div.className = 'card';
      div.dataset.id = loc.id;

      const img = loc.image_path ? `<img class="thumb" src="${escapeHtml(loc.image_path)}" alt="">` : `<div class="thumb"></div>`;

      div.innerHTML = `
        ${img}
        <div style="min-width:0">
          <h3>${escapeHtml(loc.title)}</h3>
          <div class="meta">${escapeHtml(loc.type_name)}${loc.address ? ' • ' + escapeHtml(loc.address) : ''}</div>
          <p class="desc">${escapeHtml(loc.short_text || '')}</p>
        </div>
      `;

      div.addEventListener('click', () => {
        const item = markers.get(loc.id);
        if (!item) return;
        setActive(loc.id);
        map.panTo(item.marker.getPosition());
        map.setZoom(Math.max(map.getZoom(), 13));
        google.maps.event.trigger(item.marker, 'click');
      });

      listEl.appendChild(div);
    });
  }

  function syncMarkers(){
    markers.forEach(({marker, data}) => {
      marker.setVisible(passesFilters(data));
    });
  }

  searchEl.addEventListener('input', () => { render(); syncMarkers(); });

  render();
  syncMarkers();
})();
