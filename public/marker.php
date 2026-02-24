<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); echo "Not found"; exit; }

$st = db()->prepare("SELECT * FROM markers WHERE id=?");
$st->execute([$id]);
$marker = $st->fetch();
if (!$marker) { http_response_code(404); echo "Not found"; exit; }

$st2 = db()->prepare("SELECT path FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC");
$st2->execute([$id]);
$images = array_map(fn($r)=>$r['path'], $st2->fetchAll());

$heroVideo = trim((string)($marker['hero_video_url'] ?? ''));
$heroImg = $images[0] ?? '';
$sliderStyle = (string)($marker['slider_style'] ?? 'cards');
if (!in_array($sliderStyle, ['cards','strip'], true)) $sliderStyle = 'cards';

$type = strtolower((string)($marker['type'] ?? 'location'));

$rel = db()->prepare("SELECT id,title,short_text,type,lat,lng FROM markers WHERE id<>? AND type=? ORDER BY id DESC LIMIT 12");
$rel->execute([$id, $type]);
$related = $rel->fetchAll();

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($marker['title']) ?></title>

  <style>
    :root{
      --bg:#f2f4f7;
      --panel:#fff;
      --text:#0b1320;
      --muted:#5b6573;
      --line:#e6ebf0;
      --shadow:0 10px 28px rgba(16,24,40,.12);
      --shadow2:0 2px 10px rgba(16,24,40,.06);
      --b:#003580;
      --b2:#0057d9;
      --chip:#eef3ff;
      --r:18px;
      --max:1200px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--text)}
    a{color:inherit}

    /* ===== top menu ===== */
    .siteHeader{
      position:fixed;left:0;right:0;top:0;z-index:50;
      background:rgba(255,255,255,.86);
      backdrop-filter: blur(10px);
      border-bottom:1px solid rgba(230,235,240,.7);
    }
    .navInner{
      max-width:var(--max);
      margin:0 auto;
      height:64px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:0 16px;
      gap:12px;
    }
    .logo{
      font-weight:1000;
      color:var(--b);
      text-decoration:none;
      letter-spacing:.2px;
    }
    .navLinks{display:flex;gap:8px;align-items:center}
    .navLinks a{
      text-decoration:none;
      font-weight:900;
      font-size:13px;
      padding:10px 12px;
      border-radius:999px;
    }
    .navLinks a:hover{background:#f3f6fb}
    .navLinks a.active{background:var(--chip);color:var(--b)}
    .burger{
      display:none;
      border:1px solid var(--line);
      background:#fff;
      border-radius:12px;
      padding:8px 10px;
      font-weight:900;
      cursor:pointer;
    }
    .mobileMenu{
      display:none;
      padding:10px 16px 14px;
      border-top:1px solid var(--line);
      background:#fff;
    }
    .mobileMenu.open{display:grid;gap:8px}
    .mobileMenu a{padding:10px 12px;border-radius:12px;text-decoration:none;font-weight:900}
    .mobileMenu a:hover{background:#f3f6fb}

    /* ===== hero video ===== */
    .hero{
      position:relative;
      height: min(78vh, 720px);
      min-height:520px;
      overflow:hidden;
      background:#000;
    }
    .heroMedia{
      position:absolute;inset:0;
      width:100%;height:100%;
      object-fit:cover;
      filter: contrast(1.05) saturate(1.05);
    }
    .hero::after{
      content:"";
      position:absolute;inset:0;
      background:linear-gradient(180deg, rgba(0,0,0,.35) 0%, rgba(0,0,0,.20) 45%, rgba(0,0,0,.55) 100%);
    }
    .heroInner{
      position:relative;
      z-index:2;
      max-width:var(--max);
      margin:0 auto;
      padding: 110px 16px 32px;
      height:100%;
      display:flex;
      align-items:flex-end;
    }
    .heroCard{
      width:min(760px, 100%);
      background:rgba(255,255,255,.10);
      border:1px solid rgba(255,255,255,.22);
      border-radius:22px;
      padding:18px;
      backdrop-filter: blur(10px);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      color:#fff;
    }
    .typeChip{
      display:inline-flex;
      gap:8px;
      align-items:center;
      padding:8px 12px;
      border-radius:999px;
      background:rgba(255,255,255,.14);
      border:1px solid rgba(255,255,255,.22);
      font-weight:1000;
      font-size:12px;
      text-transform:capitalize;
    }
    h1{
      margin:12px 0 10px;
      font-size: clamp(26px, 4vw, 44px);
      line-height:1.05;
      letter-spacing:-.3px;
    }
    .heroSub{
      margin:0;
      opacity:.92;
      font-size:14px;
      line-height:1.45;
      max-width:60ch;
    }
    .heroBtns{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:14px;
    }
    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding:12px 14px;
      border-radius:14px;
      font-weight:1000;
      font-size:13px;
      text-decoration:none;
      border:1px solid rgba(255,255,255,.25);
    }
    .btnPrimary{background:#fff;color:var(--b);border-color:#fff}
    .btnGhost{background:rgba(255,255,255,.12);color:#fff}

    /* ===== content layout ===== */
    .page{
      max-width:var(--max);
      margin:0 auto;
      padding: 22px 16px 60px;
    }
    .sectionTitle{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:12px;
      margin:26px 0 12px;
    }
    .sectionTitle h2{margin:0;font-size:20px;letter-spacing:-.2px}
    .sectionTitle p{margin:0;color:var(--muted);font-size:13px}
    .contentBox{
      background:var(--panel);
      border:1px solid var(--line);
      border-radius:18px;
      box-shadow:var(--shadow2);
      padding:16px;
      line-height:1.7;
      color:#2a3442;
    }
    .contentBox h2,.contentBox h3{letter-spacing:-.2px}
    .contentBox a{color:var(--b2);font-weight:900;text-decoration:none}

    /* ===== slider base ===== */
    .slider{
      position:relative;
      background:var(--panel);
      border:1px solid var(--line);
      border-radius:18px;
      box-shadow:var(--shadow2);
      padding:12px;
      overflow:hidden;
    }
    .sliderTrack{
      display:flex;
      gap:12px;
      overflow:auto;
      scroll-snap-type:x mandatory;
      padding-bottom:6px;
    }
    .sliderTrack::-webkit-scrollbar{height:10px}
    .sliderTrack::-webkit-scrollbar-thumb{background:#dbe3ee;border-radius:999px}
    .slide{
      scroll-snap-align:start;
      flex:0 0 auto;
      border-radius:16px;
      border:1px solid rgba(0,0,0,.06);
      overflow:hidden;
      background:#fff;
      box-shadow:0 8px 24px rgba(16,24,40,.08);
    }
    .slideImg{width:100%;height:100%;object-fit:cover;display:block;background:#e9eef5}

    /* ===== slider style: cards ===== */
    .slider.cards .slide{width:320px}
    .slider.cards .slideImg{height:200px}
    .slideBody{padding:12px}
    .slideTitle{margin:0;font-weight:1000;color:var(--b2);font-size:14px}
    .slideText{margin:6px 0 0;color:var(--muted);font-size:12px;line-height:1.4}

    /* ===== slider style: strip ===== */
    .slider.strip{padding:0}
    .slider.strip .sliderTrack{gap:0;padding:0}
    .slider.strip .slide{
      width:min(72vw, 520px);
      border:none;
      border-radius:0;
      box-shadow:none;
    }
    .slider.strip .slideImg{height:320px}

    /* ===== related carousel (more-than-4 cards) ===== */
    .relWrap{
      position:relative;
      background:transparent;
    }
    .relTrack{
      display:flex;
      gap:12px;
      overflow:auto;
      scroll-snap-type:x mandatory;
      padding:2px 0 10px;
    }
    .relCard{
      flex:0 0 auto;
      width:280px;
      background:var(--panel);
      border:1px solid var(--line);
      border-radius:18px;
      box-shadow:var(--shadow2);
      overflow:hidden;
      scroll-snap-align:start;
      cursor:pointer;
      text-decoration:none;
    }
    .relThumb{height:160px;background:#e9eef5}
    .relBody{padding:12px}
    .relTitle{margin:0;font-weight:1000;color:var(--b2);font-size:14px}
    .relText{margin:6px 0 0;color:var(--muted);font-size:12px;line-height:1.4}
    .miniChip{
      display:inline-flex;
      padding:6px 10px;
      border-radius:999px;
      background:var(--chip);
      color:var(--b);
      font-size:12px;
      font-weight:1000;
      text-transform:capitalize;
      margin-top:10px;
    }

    /* ===== footer ===== */
    .footer{
      margin-top:28px;
      color:var(--muted);
      font-size:12px;
      text-align:center;
    }

    @media(max-width:900px){
      .navLinks{display:none}
      .burger{display:inline-flex}
      .heroInner{padding-top:94px}
      .hero{min-height:520px}
      .slider.cards .slide{width:min(84vw, 360px)}
      .relCard{width:min(78vw, 320px)}
    }
  </style>
  
  <style>
      /* ===== Hotel carousel card listing (Dorchester-like) ===== */
.carousel-card-listing{
  position: relative;
  overflow: hidden;
  border-radius: 18px;
  border: 1px solid #e6ebf0;
  background: #fff;
  box-shadow: 0 2px 10px rgba(16,24,40,.06);
}

.hotel-carousel-card-listing{
  padding: 12px;
}

.more-than-4-card .hotel-carousel-track{
  gap: 10px;
}

.hotel-carousel-header{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:12px;
  padding: 0 4px 10px 4px;
}

.hotel-carousel-title{
  margin:0;
  font-size:18px;
  font-weight:1100;
  letter-spacing:-.2px;
}

.hotel-carousel-sub{
  font-size:12px;
  color:#5b6573;
  margin-top:6px;
}

.hotel-carousel-actions{
  display:flex;
  gap:10px;
  align-items:center;
}

.hotel-carousel-btn{
  width:40px;height:40px;
  border-radius:999px;
  border:1px solid #e6ebf0;
  background:#fff;
  cursor:pointer;
  font-weight:1100;
  box-shadow: 0 2px 10px rgba(16,24,40,.06);
}
.hotel-carousel-btn:disabled{
  opacity:.45;
  cursor:not-allowed;
}

.hotel-carousel-viewport{
  overflow:auto;
  scroll-snap-type:x mandatory;
  -webkit-overflow-scrolling:touch;
  padding: 4px;
}
.hotel-carousel-viewport::-webkit-scrollbar{height:10px}
.hotel-carousel-viewport::-webkit-scrollbar-thumb{background:#d9e2ee;border-radius:999px}
.hotel-carousel-viewport::-webkit-scrollbar-track{background:transparent}

.hotel-carousel-track{
  display:flex;
  align-items:stretch;
  gap: 8px;
}

.hotel-carousel-card{
  flex: 0 0 260px;
  scroll-snap-align:start;
  border-radius:16px;
  overflow:hidden;
  border:1px solid #e6ebf0;
  background:#fff;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
.hotel-carousel-card:hover{
  transform: translateY(-2px);
  box-shadow: 0 16px 34px rgba(16,24,40,.10);
  border-color: rgba(0,87,217,.22);
}

.hotel-carousel-media{
  position:relative;
  width:100%;
  height:170px;
  background:#e9eef5;
}
.hotel-carousel-media img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}
.hotel-carousel-badge{
  position:absolute;
  left:10px;
  top:10px;
  background: rgba(255,255,255,.92);
  border:1px solid rgba(0,0,0,.06);
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:1100;
  text-transform:capitalize;
}

.hotel-carousel-body{
  padding:12px;
}
.hotel-carousel-name{
  margin:0;
  font-size:14px;
  font-weight:1100;
  color:#0057d9;
  line-height:1.25;
}
.hotel-carousel-desc{
  margin:8px 0 0;
  font-size:13px;
  line-height:1.35;
  color:#3b4554;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}

.hotel-carousel-dots{
  display:flex;
  gap:6px;
  justify-content:center;
  padding:10px 0 2px;
}
.hotel-carousel-dot{
  width:8px;height:8px;border-radius:999px;
  background:#d9e2ee;
}
.hotel-carousel-dot.active{
  width:18px;
  background: rgba(0,87,217,.75);
}

/* Mobile tweaks */
@media(max-width:980px){
  .hotel-carousel-card{flex-basis: 78vw;}
}
  </style>
</head>

<body>

<?php include __DIR__ . '/header.php'; ?>

<section class="hero">
  <?php if ($heroVideo): ?>
    <video class="heroMedia" autoplay muted loop playsinline <?= $heroImg ? 'poster="'.e($heroImg).'"' : '' ?>>
      <source src="<?= e($heroVideo) ?>" type="video/mp4">
    </video>
  <?php elseif ($heroImg): ?>
    <img class="heroMedia" src="<?= e($heroImg) ?>" alt="">
  <?php else: ?>
    <div class="heroMedia"></div>
  <?php endif; ?>
<section class="heroWhite" id="heroWhite">
  <video id="heroVideo" autoplay muted loop playsinline style="display:none"></video>
  <img id="heroImg" alt="" style="display:none; width:100%; height:100%; object-fit:cover;">
  <div class="heroOverlay"></div>
  <!-- your hero text content here -->
</section>
  <div class="heroInner">
    <div class="heroCard">
      <div class="typeChip">● <?= e($type) ?></div>
      <h1><?= e($marker['title']) ?></h1>
      <p class="heroSub"><?= e($marker['short_text'] ?? '') ?></p>

      <div class="heroBtns">
        <?php
          $dest = urlencode($marker['lat'].','.$marker['lng']);
        ?>
        <a class="btn btnPrimary" target="_blank"
           href="https://www.google.com/maps/dir/?api=1&destination=<?= $dest ?>&travelmode=driving">🚗 Directions</a>

        <a class="btn btnGhost" href="#gallery">🖼️ Gallery</a>
        <a class="btn btnGhost" href="#more">✨ More like this</a>
      </div>
    </div>
  </div>
</section>

<main class="page">

  <div class="sectionTitle">
    <h2>About this place</h2>
    <p>Custom content from admin page</p>
  </div>

  <div class="contentBox">
    <?php
      // You said you will add content inside.
      // IMPORTANT: This is raw HTML from DB.
      // Only you (admin) should be able to edit this.
      echo $marker['content_html'] ?: '<p>Add <b>content_html</b> for this marker in admin page.</p>';
    ?>
  </div>

  <div class="sectionTitle" id="gallery">
    <h2>Gallery</h2>
    <p>Slider style: <b><?= e($sliderStyle) ?></b> (change later dynamically)</p>
  </div>
<section class="carousel-card-listing more-than-4-card hotel-carousel-card-listing" id="photoCarousel">
  <div class="hotel-carousel-header">
    <div>
      <h2 class="hotel-carousel-title">Photos</h2>
      <div class="hotel-carousel-sub" id="photoCarouselSub">Loading…</div>
    </div>

    <div class="hotel-carousel-actions">
      <button class="hotel-carousel-btn" type="button" id="pcPrev">‹</button>
      <button class="hotel-carousel-btn" type="button" id="pcNext">›</button>
    </div>
  </div>

  <div class="hotel-carousel-viewport" id="pcViewport">
    <div class="hotel-carousel-track" id="pcTrack">
      <!-- JS will render slides -->
    </div>
  </div>

  <div class="hotel-carousel-dots" id="pcDots"></div>
</section>
  <section class="slider <?= e($sliderStyle) ?>" data-style="<?= e($sliderStyle) ?>">
    <div class="sliderTrack" id="galleryTrack">
      <?php if (!$images): ?>
        <div class="contentBox" style="width:100%">No images yet. Upload images from admin page.</div>
      <?php endif; ?>

      <?php foreach ($images as $img): ?>
        <div class="slide">
          <img class="slideImg" src="<?= e($img) ?>" alt="">
          <?php if ($sliderStyle === 'cards'): ?>
            <div class="slideBody">
              <p class="slideTitle"><?= e($marker['title']) ?></p>
              <p class="slideText"><?= e($marker['short_text'] ?? '') ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="sectionTitle" id="more">
    <h2>More <?= e($type) ?> nearby</h2>
    <p>Carousel card listing (more than 4 cards)</p>
  </div>

  <section class="relWrap">
    <div class="relTrack" id="relTrack">
      <?php if (!$related): ?>
        <div class="contentBox" style="width:100%">No related items yet. Add more markers of type “<?= e($type) ?>”.</div>
      <?php endif; ?>

      <?php foreach ($related as $r): ?>
        <?php
          $st3 = db()->prepare("SELECT path FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC LIMIT 1");
          $st3->execute([(int)$r['id']]);
          $thumb = ($st3->fetch()['path'] ?? '');
        ?>
        <a class="relCard" href="marker.php?id=<?= (int)$r['id'] ?>">
          <div class="relThumb">
            <?php if ($thumb): ?>
              <img class="slideImg" style="height:160px" src="<?= e($thumb) ?>" alt="">
            <?php endif; ?>
          </div>
          <div class="relBody">
            <p class="relTitle"><?= e($r['title']) ?></p>
            <p class="relText"><?= e($r['short_text'] ?? '') ?></p>
            <div class="miniChip"><?= e($r['type']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="footer">© <?= date('Y') ?> Khareef</div>

</main>

<script>
  // mobile menu
  document.getElementById('burger')?.addEventListener('click', () => {
    document.getElementById('mobileMenu')?.classList.toggle('open');
  });

  // OPTIONAL: later you can dynamically switch slider design on the fly:
  // Example: add ?style=strip to URL and it will change without changing images/titles.
  (function(){
    const url = new URL(window.location.href);
    const style = url.searchParams.get('style');
    if (!style) return;
    const slider = document.querySelector('.slider');
    if (!slider) return;
    slider.classList.remove('cards','strip');
    slider.classList.add(style);
  })();
  
</script>
<script>
  // ===== Build "Photos" carousel (custom first, then google) =====
function buildPhotosCarousel(photos, sourceLabel){
  const track = document.getElementById('pcTrack');
  const viewport = document.getElementById('pcViewport');
  const sub = document.getElementById('photoCarouselSub');
  const dots = document.getElementById('pcDots');
  const prev = document.getElementById('pcPrev');
  const next = document.getElementById('pcNext');

  track.innerHTML = '';
  dots.innerHTML = '';

  if(!photos || !photos.length){
    sub.textContent = "No photos available yet.";
    prev.disabled = true;
    next.disabled = true;
    return;
  }

  sub.textContent = `${photos.length} photos · ${sourceLabel}`;

  // Render cards
  photos.forEach((url, i)=>{
    const card = document.createElement('div');
    card.className = 'hotel-carousel-card';
    card.innerHTML = `
      <div class="hotel-carousel-media">
        <img src="${url}" alt="">
        <div class="hotel-carousel-badge">Photo ${i+1}</div>
      </div>
      <div class="hotel-carousel-body">
        <p class="hotel-carousel-name">${MARKER.title || ''}</p>
        <p class="hotel-carousel-desc">${MARKER.short_text || ''}</p>
      </div>
    `;
    // optional: click opens lightbox if you have it
    card.addEventListener('click', ()=> {
      if (typeof openLightbox === 'function') openLightbox(i);
    });
    track.appendChild(card);

    const dot = document.createElement('span');
    dot.className = 'hotel-carousel-dot' + (i===0 ? ' active' : '');
    dot.addEventListener('click', ()=> scrollToIndex(i));
    dots.appendChild(dot);
  });

  function scrollToIndex(i){
    const card = track.children[i];
    if(!card) return;
    card.scrollIntoView({behavior:'smooth', inline:'start', block:'nearest'});
  }

  function updateDots(){
    // determine which slide is most visible
    const cards = [...track.children];
    const vpRect = viewport.getBoundingClientRect();
    let bestI = 0;
    let bestScore = -Infinity;

    cards.forEach((c, i)=>{
      const r = c.getBoundingClientRect();
      const visible = Math.min(r.right, vpRect.right) - Math.max(r.left, vpRect.left);
      const score = visible; // bigger is better
      if(score > bestScore){
        bestScore = score;
        bestI = i;
      }
    });

    [...dots.children].forEach((d, i)=> d.classList.toggle('active', i === bestI));

    // enable/disable buttons
    prev.disabled = (bestI === 0);
    next.disabled = (bestI === cards.length - 1);
  }

  prev.addEventListener('click', ()=> {
    const active = [...dots.children].findIndex(d => d.classList.contains('active'));
    scrollToIndex(Math.max(0, active - 1));
  });

  next.addEventListener('click', ()=> {
    const active = [...dots.children].findIndex(d => d.classList.contains('active'));
    scrollToIndex(Math.min(track.children.length - 1, active + 1));
  });

  viewport.addEventListener('scroll', () => {
    window.requestAnimationFrame(updateDots);
  }, {passive:true});

  // initial state
  updateDots();
}

async function setHeroMedia(){
  const v = document.getElementById('heroVideo');
  const img = document.getElementById('heroImg');

  // 1) Decide hero image first: custom[0] else google[0]
  const { custom, google, all, usedGoogle } = await getAllPhotosForMarker();
  const heroPhoto = all[0] || '';

  // 2) If you have hero_video_url in DB, use it. Otherwise skip video.
  const videoUrl = (MARKER.hero_video_url || '').trim();

  function showImg(){
    v.style.display = 'none';
    if(heroPhoto){
      img.src = heroPhoto;
      img.style.display = 'block';
    } else {
      img.style.display = 'none';
    }
  }

  if(!videoUrl){
    showImg();
    return;
  }

  // 3) Try load video, if fails show image
  v.src = videoUrl;
  v.style.display = 'block';
  img.style.display = 'none';

  const fail = () => showImg();

  // if video cannot play -> fallback
  v.addEventListener('error', fail, {once:true});
  v.addEventListener('stalled', fail, {once:true});
  v.addEventListener('abort', fail, {once:true});

  // Some browsers block autoplay; if play() rejects -> fallback
  try {
    await v.play();
  } catch (e) {
    fail();
  }
}



async function setHeroMedia(){
  const v = document.getElementById('heroVideo');
  const img = document.getElementById('heroImg');

  // 1) Decide hero image first: custom[0] else google[0]
  const { custom, google, all, usedGoogle } = await getAllPhotosForMarker();
  const heroPhoto = all[0] || '';

  // 2) If you have hero_video_url in DB, use it. Otherwise skip video.
  const videoUrl = (MARKER.hero_video_url || '').trim();

  function showImg(){
    v.style.display = 'none';
    if(heroPhoto){
      img.src = heroPhoto;
      img.style.display = 'block';
    } else {
      img.style.display = 'none';
    }
  }

  if(!videoUrl){
    showImg();
    return;
  }

  // 3) Try load video, if fails show image
  v.src = videoUrl;
  v.style.display = 'block';
  img.style.display = 'none';

  const fail = () => showImg();

  // if video cannot play -> fallback
  v.addEventListener('error', fail, {once:true});
  v.addEventListener('stalled', fail, {once:true});
  v.addEventListener('abort', fail, {once:true});

  // Some browsers block autoplay; if play() rejects -> fallback
  try {
    await v.play();
  } catch (e) {
    fail();
  }
}
</script>
<script src="header.js"></script>
</body>
</html>
