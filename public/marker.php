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
<script src="header.js"></script>
</body>
</html>
