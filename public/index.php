<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php'; // must define GOOGLE_API_KEY

$IS_ADMIN = is_admin();
$current = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Travel Guide</title>

  <style>
    :root{
      --bg:#f2f4f7;
      --panel:#fff;
      --text:#0b1320;
      --muted:#5b6573;
      --line:#e6ebf0;
      --shadow:0 6px 22px rgba(16,24,40,.08);
      --shadow2:0 2px 10px rgba(16,24,40,.06);
      --r:16px;

      --b:#003580;
      --b2:#0057d9;
      --chip:#eef3ff;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
      background:var(--bg);
      color:var(--text);
    }

    /* ====== Mobile toggle bar (Booking-like) ====== */
    .mobileBar{
      display:none;
      position:sticky;
      top:0;
      z-index:100;
      background:#fff;
      border-bottom:1px solid var(--line);
      padding:10px;
      gap:10px;
    }
    .mbBtn{
      flex:1;
      border:1px solid var(--line);
      background:#fff;
      border-radius:999px;
      padding:10px 12px;
      font-weight:900;
      cursor:pointer;
    }
    .mbBtn.active{
      border-color:rgba(0,87,217,.55);
      box-shadow:0 0 0 4px rgba(0,87,217,.12);
    }

    /* ====== Layout ====== */
    .ui{
      display:grid;
      grid-template-columns: 280px 440px 1fr; /* FIXED */
      height:100vh;
      min-height:0;
    }

    /* ====== Filters ====== */
    .filters{
      padding:14px;
      border-right:1px solid var(--line);
      overflow:auto;
      min-height:0;
    }
    .filtersBox{
      background:var(--panel);
      border:1px solid var(--line);
      border-radius:16px;
      box-shadow:var(--shadow2);
      padding:14px;
    }
    .filtersBox h3{margin:0 0 12px;font-size:16px}
    .filterGroup{padding:12px 0;border-top:1px solid var(--line)}
    .filterGroup:first-of-type{border-top:none;padding-top:0}
    .filterTitle{font-weight:900;font-size:13px;margin-bottom:10px;color:#2a3442}
    .check{display:flex;align-items:center;gap:10px;margin:10px 0;font-size:13px;color:#2a3442}
    .check input{transform:scale(1.05)}

    /* ====== List column ====== */
    .side{
      background:var(--bg);
      border-right:1px solid var(--line);
      overflow:auto;
      min-height:0;
    }
    .top{
      position:sticky;top:0;z-index:5;
      background:#fff;
      border-bottom:1px solid var(--line);
      padding:12px;
    }
    .topRow{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
    .sortPill{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 12px;border:1px solid var(--line);
      border-radius:999px;background:#fff;font-size:13px;box-shadow:var(--shadow2);
    }

    .search{
      width:100%;
      padding:12px 14px 12px 42px;
      border:1px solid var(--line);
      border-radius:14px;
      background:#fff;
      outline:none;
      font-size:14px;
      box-shadow:var(--shadow2);
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' stroke='%23626d7a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='9' cy='9' r='7'/%3E%3Cpath d='M16 16l-3.5-3.5'/%3E%3C/svg%3E");
      background-repeat:no-repeat;
      background-position:14px 50%;
    }
    .search:focus{border-color:rgba(0,87,217,.55);box-shadow:0 0 0 4px rgba(0,87,217,.12), var(--shadow2)}

    .list{padding:12px}

    .card{
      background:var(--panel);
      border:1px solid var(--line);
      border-radius:16px;
      box-shadow:var(--shadow2);
      margin-bottom:12px;
      padding:10px;
      display:grid;
      grid-template-columns:112px 1fr;
      gap:12px;
      cursor:pointer;
      transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    .card:hover{transform:translateY(-1px);box-shadow:var(--shadow);border-color:rgba(0,87,217,.22)}
    .card.active{border-color:rgba(0,87,217,.55);box-shadow:0 0 0 4px rgba(0,87,217,.12), var(--shadow)}

    .thumb{
      width:112px;height:112px;border-radius:14px;object-fit:cover;background:#e9eef5;
      border:1px solid rgba(0,0,0,.05)
    }
    .cardMain{min-width:0}
    .titleRow{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .card h3{margin:0;font-size:15px;line-height:1.25;color:var(--b2);font-weight:900}
    .meta{color:var(--muted);font-size:12px;margin:6px 0 8px}
    .tag{
      display:inline-flex;align-items:center;gap:6px;
      padding:6px 10px;border-radius:999px;
      background:var(--chip);color:var(--b);font-size:12px;font-weight:900;
      text-transform:capitalize;
    }
    .desc{
      color:#3b4554;font-size:13px;line-height:1.35;margin:8px 0 0;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
    }
    .muted{color:var(--muted)}
    .empty{padding:12px;color:var(--muted)}

    /* ====== Map ====== */
    .map{position:relative;background:#fff;min-height:0}
    #map{width:100%;height:100%}
    .mapTopSearch{
      position:absolute;left:12px;top:12px;z-index:10;
      width:min(520px, calc(100% - 24px));
      background:#fff;border:1px solid var(--line);
      border-radius:14px;box-shadow:var(--shadow);
      padding:10px 12px;
      display:flex;align-items:center;gap:10px;
    }
    .mapTopSearch input{border:none;outline:none;width:100%;font-size:14px}
    .mapTopSearch span{opacity:.7}

    /* ====== Mobile ====== */
    @media(max-width:980px){
      .mobileBar{display:flex}

      .ui{
        grid-template-columns:1fr;
        height:calc(100vh - 52px); /* minus mobile bar */
      }

      .filters{display:none}

      /* default mode: list */
      body.mobile-list .map{display:none}
      body.mobile-list .side{display:block}

      /* map mode */
      body.mobile-map .map{display:block;height:100%}
      body.mobile-map .side{display:none}

      #map{height:100%}
      .side{border-right:none}
    }
  </style>

  <!--- Menu CSS ----->
  <style>
    .siteHeader{
      position:sticky;
      top:0;
      z-index:999;
      background:#fff;
      border-bottom:1px solid #e6ebf0;
    }

    .navContainer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:0 18px;
      height:60px;
    }

    .logo{
      font-weight:1000;
      font-size:18px;
      color:#003580;
      text-decoration:none;
    }

    .navLinks{
      display:flex;
      gap:10px;
    }

    .navLinks a{
      text-decoration:none;
      color:#1b2b3d;
      font-weight:800;
      font-size:14px;
      padding:10px 14px;
      border-radius:999px;
      transition:all .2s ease;
    }

    .navLinks a:hover{ background:#f3f6fb; }

    .navLinks a.active{
      background:#eef3ff;
      color:#003580;
    }

    .menuToggle{
      display:none;
      border:1px solid #e6ebf0;
      background:#fff;
      border-radius:10px;
      padding:6px 10px;
      font-size:18px;
      cursor:pointer;
    }

    .mobileMenu{
      display:none;
      flex-direction:column;
      border-top:1px solid #e6ebf0;
      background:#fff;
    }

    .mobileMenu a{
      padding:14px 18px;
      text-decoration:none;
      font-weight:800;
      color:#1b2b3d;
      border-bottom:1px solid #f0f0f0;
    }

    .mobileMenu a:hover{ background:#f3f6fb; }

    @media(max-width:900px){
      .navLinks{display:none}
      .menuToggle{display:block}
    }
  </style>
</head>

<body class="mobile-list">

<header class="siteHeader">
  <div class="navContainer">

    <a href="index.php" class="logo">
      <img src="https://pakcomcenter.com/khareef/wp-content/themes/Mina2025/assets/images/logo.png" style="width:48px" alt="Logo"/>
    </a>

    <nav class="navLinks">
      <a href="index.php" class="<?= $current==='index.php'?'active':'' ?>">Home</a>
      <?php if ($IS_ADMIN): ?>
        <a href="admin_add_marker.php" class="<?= $current==='admin_add_marker.php'?'active':'' ?>">Add Marker</a>
      <?php endif; ?>
    </nav>

    <button class="menuToggle" id="menuToggle" type="button">☰</button>

  </div>

  <div class="mobileMenu" id="mobileMenu">
    <a href="index.php">Home</a>
    <?php if ($IS_ADMIN): ?>
      <a href="admin_add_marker.php">Add Marker</a>
    <?php endif; ?>
  </div>
</header>

<div class="mobileBar">
  <button class="mbBtn active" id="btnList" type="button">List</button>
  <button class="mbBtn" id="btnMap" type="button">Map</button>
</div>

<div class="ui">
  <aside class="filters">
    <div class="filtersBox">
      <h3>Filter</h3>

      <div class="filterGroup">
        <div class="filterTitle">Type</div>

        <label class="check"><input type="checkbox" value="location" class="typeFilter" checked> Location</label>
        <label class="check"><input type="checkbox" value="hotel" class="typeFilter" checked> Hotel</label>
        <label class="check"><input type="checkbox" value="petrol" class="typeFilter" checked> Petrol</label>
        <label class="check"><input type="checkbox" value="restaurant" class="typeFilter" checked> Restaurant</label>
        <label class="check"><input type="checkbox" value="cafe" class="typeFilter" checked> Cafe</label>
      </div>
    </div>
  </aside>

  <aside class="side">
    <div class="top">
      <div class="topRow">
        <div class="sortPill">Sort by: <strong>Newest</strong> ▾</div>
        <div class="muted" style="font-size:12px" id="countText"></div>
      </div>
      <input id="search" class="search" placeholder="Search markers…" />
    </div>

    <div id="list" class="list">Loading…</div>
  </aside>

  <main class="map">
    <div class="mapTopSearch">
      <span>🔍</span>
      <input id="mapSearch" placeholder="Search on map" />
    </div>
    <div id="map"></div>
  </main>
</div>

<script>
  // PHP -> JS flag (fixes your broken template condition)
  const IS_ADMIN = <?= $IS_ADMIN ? 'true' : 'false' ?>;

  function esc(str){
    return String(str||'').replace(/[&<>"']/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[s]));
  }

  function typeLabel(t){
    return (t || 'location').toLowerCase();
  }

  function getPinIcon(type){
    const colors = {
      location: "#0A4AA6",
      hotel: "#0071c2",
      petrol: "#d32f2f",
      restaurant: "#2e7d32",
      cafe: "#6a1b9a"
    };
    const color = colors[(type||'').toLowerCase()] || colors.location;

    const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44">
      <path d="M22 2C14 2 7.5 8.5 7.5 16.5c0 10.5 14.5 25.5 14.5 25.5S36.5 27 36.5 16.5C36.5 8.5 30 2 22 2z" fill="${color}"/>
      <circle cx="22" cy="16.5" r="7.5" fill="#ffffff"/>
    </svg>`;

    return {
      url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg.trim()),
      scaledSize: new google.maps.Size(36,36),
      anchor: new google.maps.Point(18,34)
    };
  }

  async function loadMarkers(){
    const res = await fetch('get_markers.php', {cache:'no-store'});
    const text = await res.text();
    try {
      const json = JSON.parse(text);
      if (!json.ok) throw new Error(json.error || 'API error');
      return json.data || [];
    } catch (e) {
      console.log("API response:", text);
      document.getElementById('list').innerHTML =
        '<div class="empty">API error. Open console to see response.</div>';
      return [];
    }
  }

  let MAP, INFO;
  const MARKERS = new Map();
  let LOCS = [];

  function setMobileMode(mode){
    document.body.classList.toggle('mobile-list', mode === 'list');
    document.body.classList.toggle('mobile-map',  mode === 'map');
    document.getElementById('btnList')?.classList.toggle('active', mode === 'list');
    document.getElementById('btnMap')?.classList.toggle('active',  mode === 'map');

    if (mode === 'map' && MAP) {
      setTimeout(() => google.maps.event.trigger(MAP, 'resize'), 200);
    }
  }

  document.getElementById('btnList')?.addEventListener('click', ()=>setMobileMode('list'));
  document.getElementById('btnMap')?.addEventListener('click', ()=>setMobileMode('map'));

  function setActiveCard(id){
    document.querySelectorAll('.card').forEach(c =>
      c.classList.toggle('active', Number(c.dataset.id) === Number(id))
    );
  }

  function scrollToCard(id){
    const el = document.querySelector(`.card[data-id="${id}"]`);
    if (el) el.scrollIntoView({behavior:'smooth', block:'nearest'});
  }

  function popupHtml(loc){
    const dest = encodeURIComponent(`${loc.lat},${loc.lng}`);
    const img = loc.images && loc.images.length ? `
      <img src="${esc(loc.images[0])}" style="width:100%;height:150px;object-fit:cover;border-radius:12px;margin-bottom:10px">
    ` : '';
    const t = typeLabel(loc.type);

    return `
      <div style="width:320px;font-family:ui-sans-serif,system-ui;color:#0b1320">
        <div style="background:#fff;border:1px solid #e6ebf0;border-radius:16px;box-shadow:0 10px 26px rgba(16,24,40,.12);overflow:hidden">
          <div style="padding:12px">
            ${img}
            <div style="font-weight:900;font-size:15px;line-height:1.2;margin-bottom:6px;">${esc(loc.title)}</div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
              <span style="padding:6px 10px;border-radius:999px;background:#eef3ff;color:#003580;font-size:12px;font-weight:900;text-transform:capitalize">${esc(t)}</span>
            </div>
            <div style="color:#3b4554;font-size:13px;line-height:1.35">${esc(loc.short_text || '')}</div>
          </div>

          <div style="padding:12px;border-top:1px solid #e6ebf0;background:#fbfcfe">
            <div style="font-size:12px;color:#5b6573;margin-bottom:8px;font-weight:700">Get directions</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=driving"
                style="text-decoration:none;padding:10px 12px;border-radius:12px;background:#003580;color:#fff;font-size:12px;font-weight:900;display:inline-flex;gap:8px;align-items:center">
                🚗 Driving
              </a>
              <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=walking"
                style="text-decoration:none;padding:10px 12px;border-radius:12px;background:#fff;color:#0b1320;border:1px solid #e6ebf0;font-size:12px;font-weight:900;display:inline-flex;gap:8px;align-items:center">
                🚶 Walking
              </a>
              <a target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${dest}&travelmode=transit"
                style="text-decoration:none;padding:10px 12px;border-radius:12px;background:#fff;color:#0b1320;border:1px solid #e6ebf0;font-size:12px;font-weight:900;display:inline-flex;gap:8px;align-items:center">
                🚌 Transit
              </a>
            </div>

            <div style="margin-top:10px">
              <a target="_blank" href="https://www.google.com/maps/search/?api=1&query=${dest}"
                style="color:#0057d9;text-decoration:none;font-size:12px;font-weight:800">
                Open in Google Maps →
              </a>
              <a href="marker.php?id=${loc.id}" style="display:inline-block;margin-top:10px;font-weight:900;color:#0057d9;text-decoration:none">
                View details →
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function renderList(){
    const listEl = document.getElementById('list');
    const q = (document.getElementById('search').value || '').trim().toLowerCase();
    const checkedTypes = [...document.querySelectorAll('.typeFilter:checked')].map(cb => cb.value);

    const filtered = LOCS.filter(l => {
      const matchesSearch =
        (l.title||'').toLowerCase().includes(q) ||
        (l.short_text||'').toLowerCase().includes(q);

      const t = (l.type || 'location').toLowerCase();
      const matchesType = checkedTypes.includes(t);

      return matchesSearch && matchesType;
    });

    document.getElementById('countText').textContent = filtered.length ? `${filtered.length} results` : '';

    listEl.innerHTML = '';
    if (!filtered.length){
      listEl.innerHTML = `<div class="empty">No results</div>`;
    }

    MARKERS.forEach((m, id) => {
      const isVisible = filtered.some(x => Number(x.id) === Number(id));
      m.setVisible(isVisible);
    });

    filtered.forEach(l => {
      const div = document.createElement('div');
      div.className = 'card';
      div.dataset.id = l.id;

      const thumb = (l.images && l.images.length) ? l.images[0] : '';

      div.innerHTML = `
        <div style="position:relative">
          <img class="thumb" src="${esc(thumb)}" onerror="this.style.display='none'">

          ${IS_ADMIN ? `
            <a onclick="event.stopPropagation()" href="admin_edit_marker.php?id=${l.id}"
               style="position:absolute;top:8px;left:8px;z-index:5;
                      background:#fff;border:1px solid #e6ebf0;border-radius:12px;
                      padding:8px 10px;font-weight:900;font-size:12px;
                      text-decoration:none;color:#0057d9;box-shadow:0 2px 10px rgba(16,24,40,.08)">
              Edit
            </a>
          ` : ``}
        </div>

        <div class="cardMain">
          <div class="titleRow">
            <h3>${esc(l.title)}</h3>
            <div style="opacity:.4">♡</div>
          </div>
          <div class="meta">
            <span class="tag">${esc((l.type||'location'))}</span>
          </div>
          <p class="desc">${esc(l.short_text || '')}</p>
        </div>
      `;

      div.addEventListener('click', () => {
        const m = MARKERS.get(Number(l.id));
        if (!m) return;
        setActiveCard(l.id);

        if (window.matchMedia('(max-width: 980px)').matches) setMobileMode('map');

        MAP.panTo(m.getPosition());
        MAP.setZoom(Math.max(MAP.getZoom(), 13));
        google.maps.event.trigger(m, 'click');
      });

      listEl.appendChild(div);
    });
  }

  window.initMap = async function(){
    LOCS = await loadMarkers();

    const center = LOCS.length
      ? {lat: Number(LOCS[0].lat), lng: Number(LOCS[0].lng)}
      : {lat: 23.5880, lng: 58.3829};

    MAP = new google.maps.Map(document.getElementById('map'), {
      center,
      zoom: LOCS.length ? 12 : 6,
      mapTypeControl:false,
      streetViewControl:false,
      fullscreenControl:true
    });

    INFO = new google.maps.InfoWindow();

    LOCS.forEach(loc => {
      const m = new google.maps.Marker({
        position: {lat: Number(loc.lat), lng: Number(loc.lng)},
        map: MAP,
        title: loc.title,
        icon: getPinIcon(loc.type)
      });

      m.addListener('click', () => {
        setActiveCard(loc.id);
        scrollToCard(loc.id);
        INFO.setContent(popupHtml(loc));
        INFO.open({anchor:m, map: MAP});
      });

      MARKERS.set(Number(loc.id), m);
    });

    document.querySelectorAll('.typeFilter').forEach(cb => cb.addEventListener('change', renderList));
    document.getElementById('search').addEventListener('input', renderList);
    document.getElementById('mapSearch').addEventListener('input', (e) => {
      document.getElementById('search').value = e.target.value;
      renderList();
    });

    renderList();
  };

  document.getElementById('menuToggle')?.addEventListener('click', function(){
    const menu = document.getElementById('mobileMenu');
    menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
  });
</script>

<script async
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars(GOOGLE_API_KEY, ENT_QUOTES, 'UTF-8') ?>&callback=initMap"></script>

</body>
</html>
