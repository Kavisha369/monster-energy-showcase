<?php
/**
 * Monster Energy — Premium Dynamic Showcase
 */
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Fetch Core products via relational helper
    $coreProducts = getProducts($pdo, ["`category` = 'core'"]);
    
    // Fetch Flavors via relational helper
    $rawFlavors = getProducts($pdo, ["`category` IN ('ultra', 'juice', 'java', 'rehab')"]);
    
    // Group flavors by category
    $flavors = [
        'ultra' => [],
        'juice' => [],
        'java' => [],
        'rehab' => []
    ];
    
    foreach ($rawFlavors as $f) {
        $flavors[$f['category']][] = $f;
    }
    
} catch (PDOException $e) {
    // Graceful fallback to avoid breaking layout
    $coreProducts = [];
    $flavors = ['ultra' => [], 'juice' => [], 'java' => [], 'rehab' => []];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monster Energy — Premium Showcase</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>

<canvas id="energyBackground" aria-hidden="true"></canvas>

<div class="claw-bg" aria-hidden="true"></div>

<div id="site-wrap">

  <!-- Ambient contrail -->
  <svg id="contrail" viewBox="0 0 100 2600" preserveAspectRatio="none" aria-hidden="true">
    <path id="contrailPath"
      d="M 88 0
         C 88 180, 16 250, 24 430
         C 32 610, 84 640, 81 820
         C 78 1000, 14 1040, 18 1230
         C 22 1420, 86 1460, 84 1660
         C 82 1860, 12 1900, 16 2100
         C 20 2280, 56 2320, 56 2600"
      fill="none"/>
  </svg>

  <!-- ── NAV ── -->
  <header class="nav" id="siteNav">
    <div class="nav__inner">
      <a class="nav__logo" href="#top" aria-label="Monster Energy Home">
        <span class="nav__logo-main"><span class="nav__logo-m">M</span>ONSTER</span>
        <span class="nav__logo-sub">ENERGY DRINKS</span>
      </a>
      <nav aria-label="Main Navigation">
        <ul class="nav__links">
          <li><a href="#showcase" aria-label="View Core Showcase Cans">Core Line</a></li>
          <li><a href="#ingredients" aria-label="View Active Formula Ingredients">Formula</a></li>
          <li><a href="#flavors" aria-label="View All Flavor Lines">All Flavors</a></li>
        </ul>
      </nav>
      <a class="btn btn-nav" href="#flavors" aria-label="Unleash the Beast - View All Flavors">Unleash the Beast</a>
    </div>
  </header>

  <!-- ── HERO ── -->
  <section class="hero" id="top">
    <div class="hero__bg-glow" aria-hidden="true"></div>

    <div class="hero__content">
      <span class="eyebrow">Premium Energy Drink</span>
      <h1 class="hero__headline">
        <span class="hero__line"><span class="hero__line-inner">UNLEASH</span></span>
        <span class="hero__line"><span class="hero__line-inner">THE</span></span>
        <span class="hero__line hero__line--green"><span class="hero__line-inner">BEAST.</span></span>
      </h1>
      <p class="hero__sub">
        160&nbsp;mg of caffeine. Taurine. B-Vitamins. Ginseng. The original
        energy blend that changed the game — engineered to give you the edge.
      </p>
      <div class="hero__stats">
        <div>
          <div class="hero__stat-num">160mg</div>
          <div class="hero__stat-label">Caffeine</div>
        </div>
        <div>
          <div class="hero__stat-num">16oz</div>
          <div class="hero__stat-label">Per Can</div>
        </div>
        <div>
          <div class="hero__stat-num">230</div>
          <div class="hero__stat-label">Calories</div>
        </div>
      </div>
      <div class="hero__actions">
        <a class="btn btn-solid" href="#flavors" aria-label="Shop All Flavors">Shop All Flavors</a>
        <a class="btn btn-ghost" href="#ingredients" aria-label="Learn What's Inside Monster Energy">What's Inside</a>
      </div>
    </div>

    <div class="hero__can-stage" id="heroCanStage" aria-hidden="true">
      <div class="hero__can-glow"></div>
      <img src="images/monster-original.png" class="can can--hero" id="heroCan" alt="Monster Energy Original Can" style="object-fit: contain;">
    </div>

    <div class="scroll-cue" aria-hidden="true">
      <span class="scroll-cue__line"></span>
      SCROLL
    </div>
  </section>

  <!-- ── SHOWCASE (Core 3) ── -->
  <section class="showcase" id="showcase">
    <span class="eyebrow eyebrow--center">The Original Line</span>
    <h2 class="section-title">The big bad buzz. Three ways.</h2>

    <div class="showcase__track" id="showcaseTrack">

      <?php if (empty($coreProducts)): ?>
        <div class="glass error-card" role="alert">
          <span class="error-card__icon" aria-hidden="true">⚠️</span>
          <h3>Beast Currently Sleeping</h3>
          <p>We are having trouble waking up our database. Please check your connection or try again later.</p>
        </div>
      <?php else: ?>
        <?php foreach ($coreProducts as $prod): ?>
          <?php $hasImage = !empty($prod['image_url']); ?>
          <article class="product-card<?php echo $hasImage ? ' loading' : ''; ?>" data-color="<?php echo htmlspecialchars($prod['accent_color']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
            <?php if ($hasImage): ?>
              <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" class="can" alt="<?php echo htmlspecialchars($prod['name']); ?>" style="object-fit: contain;" onload="this.closest('.product-card').classList.remove('loading')" onerror="this.closest('.product-card').classList.remove('loading')">
            <?php else: ?>
              <div class="can" style="--accent:<?php echo htmlspecialchars($prod['can_accent'] ?: $prod['accent_color']); ?>">
                <div class="can__body">
                  <div class="can__top"></div>
                  <div class="can__sheen"></div>
                  <div class="can__band" <?php if ($prod['can_band_bg']) echo 'style="background:'.htmlspecialchars($prod['can_band_bg']).'"'; ?>>
                    <div class="can__band-glow" <?php if ($prod['can_accent'] || $prod['accent_color']) echo 'style="--accent:'.htmlspecialchars($prod['can_accent'] ?: $prod['accent_color']).'; opacity:.25"'; ?>></div>
                    <div class="can__m" <?php if ($prod['can_m_color'] || $prod['can_m_shadow']) {
                        $styles = [];
                        if ($prod['can_m_color']) $styles[] = 'color:' . $prod['can_m_color'];
                        if ($prod['can_m_shadow']) $styles[] = 'text-shadow:0 0 12px ' . $prod['can_m_shadow'];
                        echo 'style="' . implode('; ', $styles) . '"';
                    } ?>>M</div>
                    <div class="can__label"><?php echo $prod['can_label'] ?: htmlspecialchars($prod['name']); ?></div>
                  </div>
                  <div class="can__bottom-rim"></div>
                </div>
              </div>
            <?php endif; ?>
            <div class="glass product-card__panel" data-tilt>
              <?php if ($prod['is_sugar_free']): ?>
                <div class="sugar-free-badge">Zero Sugar</div>
              <?php endif; ?>
              <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
              <div class="card-benefits">
                <?php if (!empty($prod['benefits'])): ?>
                  <?php foreach ($prod['benefits'] as $b): ?>
                    <span class="benefit-icon" data-benefit="<?php echo htmlspecialchars($b['tag']); ?>" aria-label="<?php echo htmlspecialchars($b['name']); ?>"><?php echo htmlspecialchars($b['icon']); ?>
                      <span class="spec-tooltip"><strong><?php echo htmlspecialchars($b['icon'] . ' ' . $b['name']); ?></strong><span><?php echo htmlspecialchars($b['description']); ?></span></span>
                    </span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <p class="product-card__tagline"><?php echo htmlspecialchars($prod['tagline']); ?></p>
              <div class="spec-gauges">
                <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['caffeine_level']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                  <svg viewBox="0 0 36 36">
                    <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  </svg>
                  <div class="radial-gauge__content">
                    <span class="radial-gauge__dt">Caffeine</span>
                    <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['caffeine_level']); ?>">0</span>%</span>
                  </div>
                </div>
                
                <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['recovery_factor']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                  <svg viewBox="0 0 36 36">
                    <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  </svg>
                  <div class="radial-gauge__content">
                    <span class="radial-gauge__dt">Recovery</span>
                    <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['recovery_factor']); ?>">0</span>%</span>
                  </div>
                </div>

                <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['tartness_level']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                  <svg viewBox="0 0 36 36">
                    <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  </svg>
                  <div class="radial-gauge__content">
                    <span class="radial-gauge__dt">Tartness</span>
                    <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['tartness_level']); ?>">0</span>%</span>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <span class="price" <?php if ($prod['accent_color']) echo 'style="color:'.htmlspecialchars($prod['accent_color']).'"'; ?>>$<?php echo htmlspecialchars($prod['price']); ?></span>
                <span class="volume"><?php echo htmlspecialchars($prod['size'] . ' · ' . $prod['volume']); ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </section>

  <!-- ── INGREDIENTS ── -->
  <section class="ingredients" id="ingredients">
    <span class="eyebrow eyebrow--center">The Formula</span>
    <h2 class="section-title">What makes Monster, Monster.</h2>

    <div class="ingredients__grid">
      <div class="glass ing-card">
        <span class="ing-card__icon" aria-hidden="true">⚡</span>
        <h3>Caffeine</h3>
        <p>160mg per 16oz can — twice the punch of most energy drinks. Sharpens focus in minutes.</p>
        <div class="bar"><div class="bar__fill" data-fill="88"></div></div>
        <span class="ing-card__val"><span class="counter" data-target="160">0</span>mg per 16oz</span>
        <div class="ing-card__tooltip" role="tooltip">
          <strong>⚡ ENERGY AMPLIFIER</strong>
          <span>High-octane CNS stimulation that breaks through fatigue barriers instantly.</span>
        </div>
      </div>
      <div class="glass ing-card">
        <span class="ing-card__icon" aria-hidden="true">◆</span>
        <h3>Taurine</h3>
        <p>Amino acid that supports endurance, cardiovascular function and peak metabolic output.</p>
        <div class="bar"><div class="bar__fill" data-fill="90"></div></div>
        <span class="ing-card__val"><span class="counter" data-target="2000">0</span>mg per can</span>
        <div class="ing-card__tooltip" role="tooltip">
          <strong>◆ CELLULAR HYDRATOR</strong>
          <span>Powerful amino acid that stabilizes membranes and spikes cellular endurance.</span>
        </div>
      </div>
      <div class="glass ing-card">
        <span class="ing-card__icon" aria-hidden="true">🌿</span>
        <h3>Panax Ginseng</h3>
        <p>Used for centuries for stamina and mental clarity — Monster's secret weapon for sustained output.</p>
        <div class="bar"><div class="bar__fill" data-fill="55"></div></div>
        <span class="ing-card__val">200mg per can</span>
        <div class="ing-card__tooltip" role="tooltip">
          <strong>🌿 COGNITIVE SHIELD</strong>
          <span>Precision adaptogen that enhances oxygen utilization and sharpens mental reflexes.</span>
        </div>
      </div>
      <div class="glass ing-card">
        <span class="ing-card__icon" aria-hidden="true">☀</span>
        <h3>B-Vitamins</h3>
        <p>B2, B3, B6 and B12 drive the energy metabolism that turns food into fuel that lasts.</p>
        <div class="bar"><div class="bar__fill" data-fill="70"></div></div>
        <span class="ing-card__val">B2 · B3 · B6 · B12</span>
        <div class="ing-card__tooltip" role="tooltip">
          <strong>☀ METABOLIC IGNITER</strong>
          <span>Coenzymes B2, B3, B6, & B12 that accelerate energy conversion and fuel output.</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ── FLAVORS (tabbed) ── -->
  <section class="flavors" id="flavors">
    <span class="eyebrow eyebrow--center">Flavor Lines</span>
    <h2 class="section-title">Same formula. Infinite territory.</h2>

    <div class="flavor-tabs" role="tablist" aria-label="Monster flavor lines tabs">
      <button class="flavor-tab active" data-group="ultra" role="tab" aria-selected="true" aria-controls="group-ultra" aria-label="View Monster Ultra Line">Monster Ultra</button>
      <button class="flavor-tab" data-group="juice" role="tab" aria-selected="false" aria-controls="group-juice" aria-label="View Juice Monster Line">Juice Monster</button>
      <button class="flavor-tab" data-group="java" role="tab" aria-selected="false" aria-controls="group-java" aria-label="View Java Monster Line">Java Monster</button>
      <button class="flavor-tab" data-group="rehab" role="tab" aria-selected="false" aria-controls="group-rehab" aria-label="View Rehab Monster Line">Rehab Monster</button>
    </div>

    <div class="benefit-filters" role="group" aria-label="Filter by product benefits">
      <span class="benefit-label">Benefit:</span>
      <button class="benefit-filter active" data-benefit="" aria-label="Show All Products">All</button>
      <button class="benefit-filter" data-benefit="zero-sugar" aria-label="Filter by Zero Sugar">Zero Sugar</button>
      <button class="benefit-filter" data-benefit="extreme-energy" aria-label="Filter by Extreme Energy">Extreme Energy</button>
      <button class="benefit-filter" data-benefit="recovery" aria-label="Filter by Recovery and Hydration">Recovery</button>
    </div>

    <div class="flavors__grid">

      <?php foreach (['ultra', 'juice', 'java', 'rehab'] as $catName): ?>
        <div class="flavor-group <?php echo $catName === 'ultra' ? 'active' : ''; ?>" id="group-<?php echo $catName; ?>" role="tabpanel" aria-label="<?php echo ucfirst($catName); ?> flavors">
          <?php if (empty($flavors[$catName])): ?>
            <div class="glass error-card" role="alert" style="grid-column: 1/-1;">
              <span class="error-card__icon" aria-hidden="true">⚠️</span>
              <h3>Beast Currently Sleeping</h3>
              <p>No flavors are currently available in this line. Check your database connection.</p>
            </div>
          <?php else: ?>
            <?php foreach ($flavors[$catName] as $prod): ?>
              <?php $hasImage = !empty($prod['image_url']); ?>
              <div class="glass ed-card<?php echo $hasImage ? ' loading' : ''; ?>" data-color="<?php echo htmlspecialchars($prod['accent_color']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>" data-tilt>
                <div class="ed-card__glow"></div>
                <?php if ($prod['is_sugar_free']): ?>
                  <div class="sugar-free-badge">Zero Sugar</div>
                <?php endif; ?>
                <?php if ($hasImage): ?>
                  <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" class="can can--small" alt="<?php echo htmlspecialchars($prod['name']); ?>" style="object-fit: contain;" onload="this.closest('.ed-card').classList.remove('loading')" onerror="this.closest('.ed-card').classList.remove('loading')">
                <?php else: ?>
                  <div class="can can--small" <?php if ($prod['can_accent']) echo 'style="--accent:'.htmlspecialchars($prod['can_accent']).'"'; ?>>
                    <div class="can__body">
                      <div class="can__top"></div><div class="can__sheen"></div>
                      <div class="can__band" <?php if ($prod['can_band_bg']) echo 'style="background:'.htmlspecialchars($prod['can_band_bg']).'"'; ?>>
                        <div class="can__m" <?php if ($prod['can_m_color'] || $prod['can_m_shadow']) {
                            $styles = [];
                            if ($prod['can_m_color']) $styles[] = 'color:' . $prod['can_m_color'];
                            if ($prod['can_m_shadow']) $styles[] = 'text-shadow:0 0 10px ' . $prod['can_m_shadow'];
                            echo 'style="' . implode('; ', $styles) . '"';
                        } ?>>M</div>
                        <div class="can__label" <?php if ($prod['can_label_color']) echo 'style="color:'.htmlspecialchars($prod['can_label_color']).'"'; ?>><?php echo $prod['can_label'] ?: htmlspecialchars($prod['name']); ?></div>
                      </div>
                      <div class="can__bottom-rim"></div>
                    </div>
                  </div>
                <?php endif; ?>
                <span class="line-tag"><?php echo htmlspecialchars($prod['line_tag']); ?></span>
                <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
                <div class="card-benefits">
                  <?php if (!empty($prod['benefits'])): ?>
                    <?php foreach ($prod['benefits'] as $b): ?>
                      <span class="benefit-icon" data-benefit="<?php echo htmlspecialchars($b['tag']); ?>" aria-label="<?php echo htmlspecialchars($b['name']); ?>"><?php echo htmlspecialchars($b['icon']); ?>
                        <span class="spec-tooltip"><strong><?php echo htmlspecialchars($b['icon'] . ' ' . $b['name']); ?></strong><span><?php echo htmlspecialchars($b['description']); ?></span></span>
                      </span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <p><?php echo htmlspecialchars($prod['tagline']); ?></p>
                <div class="spec-gauges">
                  <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['caffeine_level']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                    <svg viewBox="0 0 36 36">
                      <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                      <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="radial-gauge__content">
                      <span class="radial-gauge__dt">Caffeine</span>
                      <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['caffeine_level']); ?>">0</span>%</span>
                    </div>
                  </div>
                  
                  <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['recovery_factor']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                    <svg viewBox="0 0 36 36">
                      <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                      <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="radial-gauge__content">
                      <span class="radial-gauge__dt">Recovery</span>
                      <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['recovery_factor']); ?>">0</span>%</span>
                    </div>
                  </div>

                  <div class="radial-gauge" data-value="<?php echo htmlspecialchars($prod['tartness_level']); ?>" style="--accent:<?php echo htmlspecialchars($prod['accent_color']); ?>">
                    <svg viewBox="0 0 36 36">
                      <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                      <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="radial-gauge__content">
                      <span class="radial-gauge__dt">Tartness</span>
                      <span class="radial-gauge__dd"><span class="counter" data-target="<?php echo htmlspecialchars($prod['tartness_level']); ?>">0</span>%</span>
                    </div>
                  </div>
                </div>
                <span class="price" <?php if ($prod['accent_color'] && $prod['accent_color'] !== '#F5F5F5') echo 'style="color:'.htmlspecialchars($prod['accent_color']).'"'; ?>>$<?php echo htmlspecialchars($prod['price']); ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </div><!-- /.flavors__grid -->
  </section>

  <!-- ── NEWSLETTER / FOOTER ── -->
  <div class="cta-wrap">
    <div class="glass newsletter">
      <h2>Unleash the beast in your inbox.</h2>
      <p>New flavors, exclusive drops, and Monster events — first access, every time.</p>
      <form class="nl-form" onsubmit="handleNL(event)" aria-label="Newsletter Subscription Form">
        <input type="email" placeholder="you@example.com" required aria-label="Enter email address to subscribe">
        <button type="submit" class="btn btn-solid" aria-label="Subscribe to newsletter">Notify Me</button>
      </form>
      <div class="nl-message" id="nlMessage" aria-live="polite"></div>
    </div>
  </div>

  <footer>
    <span>&copy; 2026 — Concept showcase. Not an official Monster Energy Company property.</span>
    <a href="#top">↑ Back to top</a>
  </footer>

</div><!-- /#site-wrap -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="animations.js"></script>
<script>
/* ── CSRF Token ── */
window.csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;

/* ── Newsletter ── */
function handleNL(e) {
  e.preventDefault();
  var btn   = e.target.querySelector('button');
  var input = e.target.querySelector('input');
  var msgEl = document.getElementById('nlMessage');
  var originalText = btn.textContent;
  
  btn.textContent = "Submitting...";
  btn.disabled = true;
  input.disabled = true;
  msgEl.className = "nl-message";
  msgEl.textContent = "";
  
  fetch('subscribe.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ 
      email: input.value,
      csrf_token: window.csrfToken
    })
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    btn.textContent = originalText;
    btn.disabled = false;
    input.disabled = false;
    if (data.success) {
      msgEl.textContent = data.message;
      msgEl.className = "nl-message success";
      input.value = "";
    } else {
      msgEl.textContent = data.message;
      msgEl.className = "nl-message error";
    }
  })
  .catch(function(err) {
    btn.textContent = originalText;
    btn.disabled = false;
    input.disabled = false;
    msgEl.textContent = "Network Error! Please try again.";
    msgEl.className = "nl-message error";
  });
}
</script>
</body>
</html>
