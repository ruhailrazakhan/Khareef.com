<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); echo "Not found"; exit; }

$st = db()->prepare("SELECT * FROM markers WHERE id=?");
$st->execute([$id]);
$marker = $st->fetch();
if (!$marker) { http_response_code(404); echo "Not found"; exit; }

$st2 = db()->prepare("SELECT path FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC");
$st2->execute([$id]);
$images = array_values(array_filter(array_map(fn($r)=>$r['path'], $st2->fetchAll()))); // custom uploads

$heroVideo = trim((string)($marker['hero_video_url'] ?? ''));
$heroImg   = $images[0] ?? ''; // will be replaced by JS fallback if empty or video fails

$sliderStyle = (string)($marker['slider_style'] ?? 'cards');
if (!in_array($sliderStyle, ['cards','strip'], true)) $sliderStyle = 'cards';

$type = strtolower((string)($marker['type'] ?? 'location'));

$placeId = trim((string)($marker['place_id'] ?? ''));
$useGooglePhotos = (int)($marker['use_google_photos'] ?? 0) === 1;
$googleThumbIndex = (int)($marker['google_thumb_index'] ?? 0);

$rel = db()->prepare("SELECT id,title,short_text,type,lat,lng,place_id,use_google_photos,google_thumb_index
                      FROM markers
                      WHERE id<>? AND type=?
                      ORDER BY id DESC
                      LIMIT 12");
$rel->execute([$id, $type]);
$related = $rel->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($marker['title'] ?? 'Marker') ?></title>

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
    .logo{font-weight:1000;color:var(--b);text-decoration:none;letter-spacing:.2px}
    .navLinks{display:flex;gap:8px;align-items:center}
    .navLinks a{text-decoration:none;font-weight:900;font-size:13px;padding:10px 12px;border-radius:999px}
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

    /* ===== hero ===== */
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
      display:none;
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
    .heroBtns{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:8px;
      padding:12px 14px;border-radius:14px;font-weight:1000;font-size:13px;
      text-decoration:none;border:1px solid rgba(255,255,255,.25);
    }
    .btnPrimary{background:#fff;color:var(--b);border-color:#fff}
    .btnGhost{background:rgba(255,255,255,.12);color:#fff}

    /* ===== content layout ===== */
    .page{max-width:var(--max);margin:0 auto;padding: 22px 16px 60px}
    .sectionTitle{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:26px 0 12px}
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
    .contentBox a{color:var(--b2);font-weight:900;text-decoration:none}


    .relThumb{height:160px;background:#e9eef5}
    .relBody{padding:12px}
    .relTitle{margin:0;font-weight:1000;color:var(--b2);font-size:14px}
    .relText{margin:6px 0 0;color:var(--muted);font-size:12px;line-height:1.4}
    .miniChip{
      display:inline-flex;padding:6px 10px;border-radius:999px;background:var(--chip);
      color:var(--b);font-size:12px;font-weight:1000;text-transform:capitalize;margin-top:10px;
    }

    .footer{margin-top:28px;color:var(--muted);font-size:12px;text-align:center}

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
  
//================================
   //Luxury Gallery (Dorchester style)
   //Applies to #gallerySlider only
//================================== 

#gallerySlider{
  border-radius: 22px;
  border: 1px solid rgba(230,235,240,.9);
  background: rgba(255,255,255,.92);
  box-shadow: 0 22px 60px rgba(16,24,40,.10);
  overflow: hidden;
  padding: 14px;
}

/* Header spacing (your section title already exists) */
#gallerySlider + .muted,
#gallerySlider .muted{
  color: #6b7482;
}

/* Track: smooth luxury scrolling */
#galleryTrack{
  display:flex;
  gap: 14px;
  overflow:auto;
  padding: 4px 4px 12px;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scroll-behavior:smooth;
}
#galleryTrack::-webkit-scrollbar{height:10px}
#galleryTrack::-webkit-scrollbar-track{background:transparent}
#galleryTrack::-webkit-scrollbar-thumb{
  background:#d9e2ee;
  border-radius:999px;
}

/* Slide base */
#galleryTrack .slide{
  position:relative;
  scroll-snap-align:start;
  border-radius: 18px;
  overflow:hidden;
  border: 1px solid rgba(0,0,0,.06);
  background:#fff;
  box-shadow: 0 10px 26px rgba(16,24,40,.10);
  transform: translateZ(0);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  cursor: pointer;
}
#galleryTrack .slide:hover{
  transform: translateY(-3px);
  box-shadow: 0 26px 70px rgba(16,24,40,.16);
  border-color: rgba(0,87,217,.22);
}

/* Image: premium grading */
#galleryTrack .slideImg{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
  background:#e9eef5;
  filter: contrast(1.04) saturate(1.02);
}

/* Cards mode */
/*
#gallerySlider.cards #galleryTrack .slide{
  width: 360px;
}
#gallerySlider.cards #galleryTrack .slideImg{
  height: 230px;
}*/

/* Card body */
#gallerySlider.cards .slideBody{
  padding: 12px 14px 14px;
}
#gallerySlider.cards .slideTitle{
  margin:0;
  font-weight: 1100;
  font-size: 14px;
  line-height: 1.25;
  color: #0b1320;
  letter-spacing: -.1px;
}
#gallerySlider.cards .slideText{
  margin: 7px 0 0;
  font-size: 12px;
  line-height: 1.35;
  color: #5b6573;
}

/* Strip mode (big photo) */
#gallerySlider.strip{
  padding:0;
  border-radius: 22px;
}
#gallerySlider.strip #galleryTrack{
  gap:0;
  padding:0;
}
#gallerySlider.strip #galleryTrack .slide{
  width: min(82vw, 740px);
  border: none;
  border-radius: 0;
  box-shadow: none;
}
#gallerySlider.strip #galleryTrack .slideImg{
  height: min(58vh, 520px);
}

/* Subtle “fade edges” like luxury sites */
#gallerySlider::before,
#gallerySlider::after{
  content:"";
  position:absolute;
  top:0;
  bottom:0;
  width:42px;
  pointer-events:none;
  z-index:2;
}
#gallerySlider{ position:relative; }
#gallerySlider::before{
  left:0;
  background: linear-gradient(90deg, rgba(255,255,255,.98), rgba(255,255,255,0));
}
#gallerySlider::after{
  right:0;
  background: linear-gradient(270deg, rgba(255,255,255,.98), rgba(255,255,255,0));
}

/* Optional badge for “Google” or “Custom” (if you add data-source attr later) */
#galleryTrack .slide[data-source="google"]::after,
#galleryTrack .slide[data-source="custom"]::after{
  position:absolute;
  top:12px; left:12px;
  padding:7px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:1100;
  border:1px solid rgba(0,0,0,.06);
  background: rgba(255,255,255,.92);
}
#galleryTrack .slide[data-source="google"]::after{ content:"Google"; color:#003580; }
#galleryTrack .slide[data-source="custom"]::after{ content:"Custom"; color:#0057d9; }

/* Mobile: big, swipe-friendly */
@media(max-width:900px){
  #gallerySlider{ padding: 12px; border-radius: 20px; }
  #galleryTrack{ gap: 12px; }
  #gallerySlider.cards #galleryTrack .slide{ width: min(86vw, 420px); }
  #gallerySlider.cards #galleryTrack .slideImg{ height: 240px; }
  #gallerySlider.strip #galleryTrack .slide{ width: 100vw; }
  #gallerySlider.strip #galleryTrack .slideImg{ height: 54vh; }
}

</style>
</head>

<body>

<?php include __DIR__ . '/header.php'; ?>

<section class="hero" id="hero">
  <!-- video + image exist always; JS decides what to show -->
  <video class="heroMedia" id="heroVideo" autoplay muted loop playsinline></video>
  <img class="heroMedia" id="heroImage" alt="">

  <div class="heroInner">
    <div class="heroCard">
      <div class="typeChip">● <?= e($type) ?></div>
      <h1><?= e($marker['title'] ?? '') ?></h1>
      <p class="heroSub"><?= e($marker['short_text'] ?? '') ?></p>

      <div class="heroBtns">
        <?php $dest = urlencode(($marker['lat'] ?? '').','.($marker['lng'] ?? '')); ?>
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
      // raw HTML from DB (admin-only edit)
      echo $marker['content_html'] ?: '<p>Add <b>content_html</b> for this marker in admin page.</p>';
    ?>
  </div>

  <div class="sectionTitle" id="gallery">
    <h2>Gallery</h2>
    <p>
      Slider style: <b><?= e($sliderStyle) ?></b> |
      Order: <b>Custom → Google</b>
    </p>
  </div>

  <section class="slider <?= e($sliderStyle) ?>" id="gallerySlider">
    <div class="sliderTrack" id="galleryTrack">
      <!-- We render custom slides immediately, then append Google slides in JS -->
      <?php foreach ($images as $img): ?>
        <div class="slide">
          <img class="slideImg" src="<?= e($img) ?>" alt="">
          <?php if ($sliderStyle === 'cards'): ?>
            <div class="slideBody">
              <p class="slideTitle"><?= e($marker['title'] ?? '') ?></p>
              <p class="slideText"><?= e($marker['short_text'] ?? '') ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if (!$images): ?>
        <div class="contentBox" style="width:100%" id="noCustomNotice">
          No uploaded images. If Google photos enabled, they will load here automatically.
        </div>
      <?php endif; ?>
    </div>
  </section>

  <div class="sectionTitle" id="more">
    <h2>More <?= e($type) ?> nearby</h2>
    <p>Carousel card listing (more than 4 cards)</p>
  </div>

  <section>
    <div class="relTrack" id="relTrack">
      <?php if (!$related): ?>
        <div class="contentBox" style="width:100%">No related items yet. Add more markers of type “<?= e($type) ?>”.</div>
      <?php endif; ?>

      <?php foreach ($related as $r): ?>
        <?php
          $st3 = db()->prepare("SELECT path FROM marker_images WHERE marker_id=? ORDER BY sort_order ASC, id ASC LIMIT 1");
          $st3->execute([(int)$r['id']]);
          $thumb = (string)($st3->fetch()['path'] ?? '');
          $rPlace = trim((string)($r['place_id'] ?? ''));
          $rUseGoogle = (int)($r['use_google_photos'] ?? 0);
          $rThumbIdx = (int)($r['google_thumb_index'] ?? 0);
        ?>
        <a class="relCard" href="marker.php?id=<?= (int)$r['id'] ?>">
          <div class="relThumb">
            <?php if ($thumb): ?>
              <img class="slideImg" style="height:160px" src="<?= e($thumb) ?>" alt="">
            <?php else: ?>
              <!-- If no custom thumb, JS can fill Google thumb live -->
              <img class="slideImg" style="height:160px"
                data-rel-thumb="1"
                data-place-id="<?= e($rPlace) ?>"
                data-use-google="<?= (int)$rUseGoogle ?>"
                data-thumb-index="<?= (int)$rThumbIdx ?>"
                src="data:image/svg+xml;charset=UTF-8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450"><rect width="100%" height="100%" fill="#e9eef5"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#7a8596" font-family="Arial" font-size="26">Loading…</text></svg>') ?>"
                alt="">
            <?php endif; ?>
          </div>
          <div class="relBody">
            <p class="relTitle"><?= e($r['title'] ?? '') ?></p>
            <p class="relText"><?= e($r['short_text'] ?? '') ?></p>
            <div class="miniChip"><?= e($r['type'] ?? '') ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="footer">© <?= date('Y') ?> Khareef</div>

</main>

<script>
  // ===== Marker config from PHP =====
  const MARKER = {
    id: <?= (int)$id ?>,
    title: <?= json_encode((string)($marker['title'] ?? ''), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    short_text: <?= json_encode((string)($marker['short_text'] ?? ''), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    lat: <?= json_encode((float)($marker['lat'] ?? 0), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    lng: <?= json_encode((float)($marker['lng'] ?? 0), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    place_id: <?= json_encode($placeId, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    use_google_photos: <?= $useGooglePhotos ? '1' : '0' ?>,
    google_thumb_index: <?= (int)$googleThumbIndex ?>,
    hero_video_url: <?= json_encode($heroVideo, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    slider_style: <?= json_encode($sliderStyle, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
    custom_images_count: <?= (int)count($images) ?>
  };

  // ===== mobile menu (if your header.php uses these IDs) =====
  document.getElementById('burger')?.addEventListener('click', () => {
    document.getElementById('mobileMenu')?.classList.toggle('open');
  });

  // ===== Google Places Photos (LEGAL: load live, do NOT store) =====
  let __placesSvc = null;
  const __photoCache = new Map();

  function fetchPlacePhotoUrls(placeId, max=18){
    if(!placeId || !__placesSvc) return Promise.resolve([]);
    if(__photoCache.has(placeId)) return Promise.resolve(__photoCache.get(placeId));

    return new Promise((resolve)=>{
      __placesSvc.getDetails({ placeId, fields:["photos"] }, (place, status)=>{
        if(status !== google.maps.places.PlacesServiceStatus.OK || !place || !place.photos){
          __photoCache.set(placeId, []);
          return resolve([]);
        }
        const urls = place.photos.slice(0, max).map(p => p.getUrl({ maxWidth: 1600, maxHeight: 1000 }));
        __photoCache.set(placeId, urls);
        resolve(urls);
      });
    });
  }

  // ===== HERO: try video, fallback to first available photo =====
  async function setHeroMedia(){
    const v = document.getElementById('heroVideo');
    const img = document.getElementById('heroImage');

    // Start with best photo choice:
    // If you have uploaded images in HTML already -> use first <img> in gallery
    let fallbackPhoto = '';
    const firstGalleryImg = document.querySelector('#galleryTrack img.slideImg');
    if(firstGalleryImg && firstGalleryImg.src) fallbackPhoto = firstGalleryImg.src;

    // If no custom images, try google photo(0)
    if(!fallbackPhoto && MARKER.use_google_photos === 1 && MARKER.place_id){
      const g = await fetchPlacePhotoUrls(MARKER.place_id, 10);
      fallbackPhoto = g[0] || '';
    }

    const showImg = () => {
      v.style.display = 'none';
      if(fallbackPhoto){
        img.src = fallbackPhoto;
        img.style.display = 'block';
      } else {
        // nothing available
        img.style.display = 'none';
      }
    };

    const videoUrl = (MARKER.hero_video_url || '').trim();
    if(!videoUrl){
      showImg();
      return;
    }

    // Try to play video
    v.innerHTML = '';
    const source = document.createElement('source');
    source.src = videoUrl;
    source.type = 'video/mp4';
    v.appendChild(source);

    v.style.display = 'block';
    img.style.display = 'none';

    const fail = () => showImg();
    v.addEventListener('error', fail, {once:true});
    v.addEventListener('stalled', fail, {once:true});
    v.addEventListener('abort', fail, {once:true});

    try {
      await v.play(); // autoplay can fail on mobile
    } catch(e){
      fail();
    }
  }

  // ===== Gallery: append Google photos AFTER custom uploads =====
  function appendGoogleSlides(urls){
    const track = document.getElementById('galleryTrack');
    const noCustom = document.getElementById('noCustomNotice');
    if(noCustom) noCustom.remove();

    const style = (MARKER.slider_style || 'cards');

    urls.forEach((u)=>{
      const slide = document.createElement('div');
      slide.className = 'slide';
      slide.innerHTML = `
        <img class="slideImg" src="${u}" alt="">
        ${style === 'cards' ? `
          <div class="slideBody">
            <p class="slideTitle">${MARKER.title || ''}</p>
            <p class="slideText">${MARKER.short_text || ''}</p>
          </div>
        ` : ``}
      `;
      track.appendChild(slide);
    });
  }

  async function loadGoogleGalleryIfNeeded(){
    if(!(MARKER.use_google_photos === 1 && MARKER.place_id)) return;
    const urls = await fetchPlacePhotoUrls(MARKER.place_id, 18);
    if(urls && urls.length) appendGoogleSlides(urls);
  }

  // ===== Related cards: if no custom thumb, try Google thumb =====
  async function hydrateRelatedThumbs(){
    const relImgs = document.querySelectorAll('img[data-rel-thumb="1"]');
    for(const img of relImgs){
      const useGoogle = Number(img.getAttribute('data-use-google') || '0') === 1;
      const pid = (img.getAttribute('data-place-id') || '').trim();
      const idx = Number(img.getAttribute('data-thumb-index') || '0');

      if(!useGoogle || !pid) {
        img.src = "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(
          `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450">
            <rect width="100%" height="100%" fill="#e9eef5"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
              fill="#7a8596" font-family="Arial" font-size="26">No thumbnail</text>
          </svg>`
        );
        continue;
      }

      const urls = await fetchPlacePhotoUrls(pid, 8);
      const u = urls[idx] || urls[0] || '';
      if(u) img.src = u;
      else img.src = "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(
        `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450">
          <rect width="100%" height="100%" fill="#e9eef5"/>
          <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
            fill="#7a8596" font-family="Arial" font-size="26">No thumbnail</text>
        </svg>`
      );
    }
  }

  // ===== init Google map + places service =====
  window.initMarker = async function(){
    // Create a tiny hidden map (required for PlacesService in JS)
    const div = document.createElement('div');
    div.style.width = '1px';
    div.style.height = '1px';
    div.style.position = 'absolute';
    div.style.left = '-9999px';
    document.body.appendChild(div);

    const map = new google.maps.Map(div, {center:{lat:Number(MARKER.lat),lng:Number(MARKER.lng)}, zoom: 12});
    __placesSvc = new google.maps.places.PlacesService(map);

    // Load Google gallery after custom
    await loadGoogleGalleryIfNeeded();

    // After we load google, we can safely apply hero fallback (so it can use google photo if no custom)
    await setHeroMedia();

    // Fill related thumbs from google if needed
    await hydrateRelatedThumbs();
  };
</script>

<script async
  src="https://maps.googleapis.com/maps/api/js?key=<?= e(GOOGLE_API_KEY) ?>&libraries=places&callback=initMarker"></script>

</body>
</html>
