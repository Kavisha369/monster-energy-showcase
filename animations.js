/**
 * Monster Energy — Premium Cinematic Animations
 * Built with GSAP + ScrollTrigger
 */
(function () {
  'use strict';

  // Prevent errors if GSAP is not loaded
  if (typeof gsap === 'undefined') return;

  // Register ScrollTrigger plugin
  gsap.registerPlugin(ScrollTrigger);

  // Check user motion preferences
  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Retrieve transition settings from CSS custom properties
  var rootStyle = getComputedStyle(document.documentElement);
  var transitionSpeed = parseFloat(rootStyle.getPropertyValue('--transition-speed')) || 0.4;
  var maxTilt = parseFloat(rootStyle.getPropertyValue('--tilt-angle')) || 13;

  /* ============================================================
     1. NAV ANIMATIONS (SCROLL-AWARE HIDE/SHRINK & ACTIVE LINKS WITH SLIDING ACCENT)
     ============================================================ */
  var nav = document.getElementById('siteNav');
  if (nav) {
    var lastScrollY = window.scrollY;
    var links = document.querySelectorAll('.nav__links a');
    var track = document.querySelector('.nav__links');

    function moveIndicator(link) {
      if (!link || !track) return;
      var trackRect = track.getBoundingClientRect();
      var linkRect = link.getBoundingClientRect();
      var leftOffset = linkRect.left - trackRect.left;
      var width = linkRect.width;

      gsap.to(track, {
        '--indicator-left': leftOffset + 'px',
        '--indicator-width': width + 'px',
        duration: 0.38,
        ease: 'power2.out'
      });
    }

    // Scroll trigger for condensed state and hide-on-scroll down
    ScrollTrigger.create({
      start: 'top -60',
      onUpdate: function(s) {
        var currentScrollY = s.scroll();
        
        // Prevent scroll bounce trigger at top boundary
        if (currentScrollY < 0) return;
        
        var isScrollingDown = currentScrollY > lastScrollY;
        
        // Condensed sticky styling
        nav.classList.toggle('condensed', currentScrollY > 60);
        
        // Hide-on-scroll down, show-on-scroll up
        if (currentScrollY > 150) {
          if (isScrollingDown) {
            // Hide navigation smoothly
            gsap.to(nav, { yPercent: -200, duration: 0.35, ease: 'power2.out' });
          } else {
            // Show navigation smoothly
            gsap.to(nav, { yPercent: 0, duration: 0.35, ease: 'power2.out' });
          }
        } else {
          gsap.to(nav, { yPercent: 0, duration: 0.3, ease: 'power2.out' });
        }
        
        lastScrollY = currentScrollY;
      }
    });

    // Handle manual clicks
    links.forEach(function(link) {
      link.addEventListener('click', function() {
        links.forEach(function(a) { a.classList.remove('active'); });
        link.classList.add('active');
        moveIndicator(link);
      });
    });

    // Scroll-Trigger Active States for Section Navigation Links
    var sections = ['showcase', 'ingredients', 'flavors'];
    sections.forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) return;
      ScrollTrigger.create({
        trigger: el,
        start: 'top 40%',
        end: 'bottom 40%',
        onToggle: function(self) {
          if (self.isActive) {
            var targetLink = document.querySelector('.nav__links a[href="#' + id + '"]');
            if (targetLink) {
              links.forEach(function(a) { a.classList.remove('active'); });
              targetLink.classList.add('active');
              moveIndicator(targetLink);
            }
          }
        }
      });
    });

    // Initialize indicator on load / window resize
    function initNavIndicator() {
      var activeLink = document.querySelector('.nav__links a.active') || links[0];
      if (activeLink) moveIndicator(activeLink);
    }

    // Delay slightly to let viewport/fonts resolve sizes
    window.addEventListener('load', function() {
      setTimeout(initNavIndicator, 150);
    });
    window.addEventListener('resize', initNavIndicator);
  }

  /* ============================================================
     2. HERO ENTRANCE & FLOATING CAN
     ============================================================ */
  if (!prefersReduced) {
    var lines   = document.querySelectorAll('.hero__line-inner');
    var sub     = document.querySelector('.hero__sub');
    var stats   = document.querySelector('.hero__stats');
    var actions = document.querySelector('.hero__actions');
    var heroCan = document.getElementById('heroCan');

    // Initialize staging styles
    gsap.set(lines, { yPercent: 115, opacity: 0 });
    gsap.set([sub, stats, actions], { opacity: 0, y: 22 });
    if (heroCan) {
      gsap.set(heroCan, { opacity: 0, scale: 0.82, y: 40, rotation: -8 });
    }

    // Snappy entrance timeline using back.out(1.7) where visual impact is highest
    var tl = gsap.timeline({ delay: 0.1 });
    tl.to(lines, { yPercent: 0, opacity: 1, duration: 0.85, ease: 'power3.out', stagger: 0.11 })
      .to(heroCan, { opacity: 1, scale: 1, y: 0, rotation: 0, duration: 1.1, ease: 'back.out(1.7)' }, 0.15)
      .to(sub, { opacity: 1, y: 0, duration: 0.7, ease: 'back.out(1.7)' }, '-=0.55')
      .to(stats, { opacity: 1, y: 0, duration: 0.6, ease: 'back.out(1.7)' }, '-=0.45')
      .to(actions, { opacity: 1, y: 0, duration: 0.6, ease: 'back.out(1.7)' }, '-=0.45');

    // Immersive floating micro-animation for hero can
    if (heroCan) {
      gsap.to(heroCan, {
        y: -18,
        rotation: 3,
        duration: 3.8,
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
        delay: 1.5
      });
    }
  }

  /* ============================================================
     3. AMBIENT CONTRAIL SCROLL ANIMATION
     ============================================================ */
  var cPath = document.getElementById('contrailPath');
  if (cPath) {
    var cLen = cPath.getTotalLength();
    cPath.style.strokeDasharray = cLen;
    cPath.style.strokeDashoffset = prefersReduced ? 0 : cLen;

    if (!prefersReduced) {
      ScrollTrigger.create({
        trigger: '#site-wrap',
        start: 'top top',
        end: 'bottom bottom',
        scrub: true,
        onUpdate: function(s) {
          cPath.style.strokeDashoffset = cLen * (1 - s.progress);
        }
      });
    }
  }

  /* ============================================================
     4. SHOWCASE CORES (HORIZONTAL SCROLL & FLOAT ENTRANCE)
     ============================================================ */
  var showcaseSec = document.querySelector('.showcase');
  var track = document.getElementById('showcaseTrack');
  
  if (showcaseSec && track) {
    var cards = track.querySelectorAll('.product-card');

    // Cinematic float-in with staggered alternates rotation
    if (!prefersReduced && cards.length) {
      gsap.from(cards, {
        opacity: 0,
        y: 120,
        scale: 0.88,
        rotation: function(i) {
          return i % 2 === 0 ? -6 : 6;
        },
        transformOrigin: "bottom center",
        duration: 1.25,
        stagger: 0.1,
        ease: 'back.out(1.7)',
        scrollTrigger: {
          trigger: showcaseSec,
          start: 'top 85%'
        }
      });
    }
  }

  /* ============================================================
     5. COUNTERS & PROGRESS BARS
     ============================================================ */
  document.querySelectorAll('.counter').forEach(function (el) {
    var targetVal = parseFloat(el.dataset.target) || 0;
    if (prefersReduced) {
      el.textContent = targetVal;
      return;
    }
    ScrollTrigger.create({
      trigger: el,
      start: 'top 92%',
      once: true,
      onEnter: function () {
        gsap.to(el, {
          textContent: targetVal,
          duration: 1.2,
          ease: 'power1.out',
          snap: { textContent: 1 }
        });
      }
    });
  });

  document.querySelectorAll('.bar__fill').forEach(function (bar) {
    var fillVal = bar.dataset.fill || 0;
    if (prefersReduced) {
      bar.style.width = fillVal + '%';
      return;
    }
    gsap.to(bar, {
      width: fillVal + '%',
      duration: 1.4,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: bar,
        start: 'top 93%',
        once: true
      }
    });
  });

  /* ============================================================
     6. INGREDIENTS STAGGERED POP-UP
     ============================================================ */
  if (!prefersReduced) {
    var ingCards = document.querySelectorAll('.ing-card');
    if (ingCards.length) {
      gsap.from(ingCards, {
        opacity: 0,
        y: 45,
        scale: 0.94,
        duration: 0.7,
        stagger: 0.1,
        ease: 'back.out(1.7)',
        scrollTrigger: {
          trigger: '.ingredients__grid',
          start: 'top 85%'
        }
      });
    }
  }

  /* ============================================================
     7. TABS EDITIONS / FLAVORS DYNAMIC ENTRANCE
     ============================================================ */
  // Expose function globally so click handlers in index.php can trigger it
  window.animateEdCards = function () {
    if (prefersReduced) return;
    var edCards = document.querySelectorAll('.flavor-group.active .ed-card');
    if (!edCards.length) return;

    // Reset styles and play entry animation
    gsap.killTweensOf(edCards);
    gsap.fromTo(edCards,
      {
        opacity: 0,
        y: 40,
        scale: 0.92,
        rotation: function(i) {
          return i % 2 === 0 ? -4 : 4;
        }
      },
      {
        opacity: 1,
        y: 0,
        scale: 1,
        rotation: 0,
        duration: 0.75,
        stagger: 0.1,
        ease: 'back.out(1.7)',
        clearProps: "transform,opacity"
      }
    );
  };

  // Initial trigger on scroll
  if (!prefersReduced) {
    ScrollTrigger.create({
      trigger: '.flavors__grid',
      start: 'top 84%',
      once: true,
      onEnter: function() {
        window.animateEdCards();
      }
    });
  }

  /* ============================================================
     8. DYNAMIC 3D TILT HOVER EFFECT
     ============================================================ */
  if (!prefersReduced && !window.matchMedia('(pointer: coarse)').matches) {
    document.addEventListener('mouseover', function(e) {
      var card = e.target.closest('[data-tilt]');
      if (!card) return;

      var bounds;

      card._mmove = function(ev) {
        if (!bounds) bounds = card.getBoundingClientRect();
        var px = (ev.clientX - bounds.left) / bounds.width - 0.5;
        var py = (ev.clientY - bounds.top) / bounds.height - 0.5;
        
        gsap.to(card, {
          rotateY: px * maxTilt,
          rotateX: py * -maxTilt,
          duration: 0.35,
          ease: 'power2.out'
        });
      };

      card._mleave = function() {
        gsap.to(card, {
          rotateY: 0,
          rotateX: 0,
          duration: 0.5,
          ease: 'power3.out'
        });
        card.removeEventListener('mousemove', card._mmove);
        card.removeEventListener('mouseleave', card._mleave);
      };

      bounds = card.getBoundingClientRect();
      card.style.transformPerspective = '700px';
      card.addEventListener('mousemove', card._mmove);
      card.addEventListener('mouseleave', card._mleave);
    });
  }

  /* ============================================================
     9. MAGNETIC NAV LINKS HOVER EFFECT
     ============================================================ */
  if (!prefersReduced && !window.matchMedia('(pointer: coarse)').matches) {
    document.querySelectorAll('.nav__links a, .btn-nav').forEach(function(link) {
      link.addEventListener('mousemove', function(e) {
        var bounds = link.getBoundingClientRect();
        var x = e.clientX - bounds.left - bounds.width / 2;
        var y = e.clientY - bounds.top - bounds.height / 2;
        
        gsap.to(link, {
          x: x * 0.35,
          y: y * 0.35,
          duration: 0.3,
          ease: 'power2.out'
        });
      });
      
      link.addEventListener('mouseleave', function() {
        gsap.to(link, {
          x: 0,
          y: 0,
          duration: 0.4,
          ease: 'back.out(2)'
        });
      });
    });
  }

  /* ============================================================
     10. INTERACTIVE PARTICLE "ENERGY" BACKGROUND
     ============================================================ */
  var canvas = document.getElementById('energyBackground');
  if (canvas) {
    var ctx = canvas.getContext('2d');
    var particles = [];
    var maxParticles = 65;
    var mouse = { x: 0, y: 0, sx: 0, sy: 0 };
    var hasMoved = false;

    window.addEventListener('mousemove', function (e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      if (!hasMoved) {
        mouse.sx = mouse.x;
        mouse.sy = mouse.y;
        hasMoved = true;
      }
    });

    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    function Particle() {
      this.reset(true);
    }

    Particle.prototype.reset = function (init) {
      this.x = Math.random() * canvas.width;
      this.y = init ? Math.random() * canvas.height : canvas.height + 20;
      this.size = Math.random() * 3.5 + 1.5;
      this.speedY = -(Math.random() * 0.8 + 0.4);
      this.speedX = (Math.random() - 0.5) * 0.4;
      this.opacity = Math.random() * 0.5 + 0.2;
      this.fadeSpeed = Math.random() * 0.005 + 0.002;
      this.springFactor = Math.random() * 0.02 + 0.005;
    };

    Particle.prototype.update = function () {
      this.y += this.speedY;
      this.x += this.speedX;

      if (hasMoved) {
        var dx = mouse.sx - this.x;
        var dy = mouse.sy - this.y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        
        if (dist < 350) {
          this.x += dx * this.springFactor;
          this.y += dy * this.springFactor;
        }
      }

      if (this.y < -20 || this.x < -20 || this.x > canvas.width + 20) {
        this.reset(false);
      }
    };

    Particle.prototype.draw = function () {
      var radGrad = ctx.createRadialGradient(
        this.x, this.y, 0,
        this.x, this.y, this.size * 4
      );
      radGrad.addColorStop(0, 'rgba(61, 255, 84, ' + this.opacity + ')');
      radGrad.addColorStop(0.3, 'rgba(61, 255, 84, ' + (this.opacity * 0.4) + ')');
      radGrad.addColorStop(1, 'rgba(61, 255, 84, 0)');

      ctx.fillStyle = radGrad;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size * 4, 0, Math.PI * 2);
      ctx.fill();
    };

    for (var i = 0; i < maxParticles; i++) {
      particles.push(new Particle());
    }

    function animateParticles() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      if (hasMoved) {
        mouse.sx += (mouse.x - mouse.sx) * 0.08;
        mouse.sy += (mouse.y - mouse.sy) * 0.08;
      }

      for (var i = 0; i < particles.length; i++) {
        var p = particles[i];
        p.update();
        p.draw();
      }

      requestAnimationFrame(animateParticles);
    }
    animateParticles();
  }

  /* ============================================================
     11. 360-DEGREE INTERACTIVE PRODUCT ROTATOR
     ============================================================ */
  var heroCanStage = document.getElementById('heroCanStage');
  var heroCan = document.getElementById('heroCan');
  if (heroCanStage && heroCan) {
    var isDragging = false;
    var startX = 0;
    var currentRotationY = 0;
    var targetRotationY = 0;
    var velocity = 0;
    var friction = 0.95;
    var lastX = 0;
    
    // Preload image frames if frame array exists
    var preloadedFrames = [];
    if (window.productFrames && window.productFrames.length > 0) {
      window.productFrames.forEach(function(src) {
        var img = new Image();
        img.src = src;
        preloadedFrames.push(img);
      });
    }

    // Preload back view image
    var backImg = new Image();
    backImg.src = 'images/monster-original-back.png';

    gsap.ticker.add(function () {
      if (!isDragging) {
        targetRotationY += velocity;
        velocity *= friction;
        if (Math.abs(velocity) < 0.01) velocity = 0;
      }
      
      currentRotationY += (targetRotationY - currentRotationY) * 0.1;
      
      var normalizedRot = ((currentRotationY % 360) + 360) % 360;
      var showBack = (normalizedRot > 90 && normalizedRot < 270);
      
      // Calculate display Y rotation to prevent horizontal mirroring on the back view
      var displayRotY = showBack ? (currentRotationY - 180) : currentRotationY;
      
      gsap.set(heroCan, {
        rotationY: displayRotY,
        transformPerspective: 1000
      });

      if (window.productFrames && window.productFrames.length > 0) {
        var frameCount = window.productFrames.length;
        var frameIndex = Math.floor((normalizedRot / 360) * frameCount);
        if (heroCan.src !== window.productFrames[frameIndex]) {
          heroCan.src = window.productFrames[frameIndex];
        }
      } else {
        // Automatically swap between front and back views at threshold angles
        var targetSrc = showBack ? 'images/monster-original-back.png' : 'images/monster-original.png';
        if (heroCan.getAttribute('src') !== targetSrc) {
          heroCan.setAttribute('src', targetSrc);
        }
      }
    });

    heroCanStage.addEventListener('mousedown', function (e) {
      isDragging = true;
      startX = e.clientX;
      lastX = e.clientX;
      velocity = 0;
      heroCan.style.transition = 'none';
    });

    window.addEventListener('mousemove', function (e) {
      if (!isDragging) return;
      var dx = e.clientX - lastX;
      lastX = e.clientX;
      targetRotationY += dx * 0.5;
      velocity = dx * 0.5;
    });

    window.addEventListener('mouseup', function () {
      isDragging = false;
    });

    heroCanStage.addEventListener('touchstart', function (e) {
      if (e.touches.length === 0) return;
      isDragging = true;
      startX = e.touches[0].clientX;
      lastX = e.touches[0].clientX;
      velocity = 0;
      heroCan.style.transition = 'none';
    });

    window.addEventListener('touchmove', function (e) {
      if (!isDragging || e.touches.length === 0) return;
      var dx = e.touches[0].clientX - lastX;
      lastX = e.touches[0].clientX;
      targetRotationY += dx * 0.6;
      velocity = dx * 0.6;
    });

    window.addEventListener('touchend', function () {
      isDragging = false;
    });
  }

  /* ============================================================
     12. DATA-DRIVEN PRODUCTS CACHE
     ============================================================ */
  var productsCache = {};

  /* ============================================================
     13. DYNAMIC COLOR THEMING (CSS VARIABLE MORPHING)
     ============================================================ */
  function hexToRgba(hex, alpha) {
    hex = hex.replace('#', '');
    if (hex.length === 3) {
      hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    var r = parseInt(hex.substring(0, 2), 16);
    var g = parseInt(hex.substring(2, 4), 16);
    var b = parseInt(hex.substring(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function initDynamicTheming() {
    document.addEventListener('mouseover', function(e) {
      var card = e.target.closest('.product-card, .ed-card');
      if (!card) return;
      
      var accentColor = card.dataset.color || card.style.getPropertyValue('--accent') || '#3DFF54';
      var dimColor = hexToRgba(accentColor, 0.18);
      var glowColor = hexToRgba(accentColor, 0.35);
      var bodyColor = hexToRgba(accentColor, 0.035);
      
      gsap.killTweensOf(document.documentElement);
      gsap.to(document.documentElement, {
        '--green': accentColor,
        '--accent-color': accentColor,
        '--green-dim': dimColor,
        '--green-glow': glowColor,
        '--body-glow': bodyColor,
        duration: 0.5,
        ease: 'power2.out'
      });
    });
    
    document.addEventListener('mouseout', function(e) {
      var card = e.target.closest('.product-card, .ed-card');
      if (!card) return;
      
      var related = e.relatedTarget;
      if (related && related.closest && related.closest('.product-card, .ed-card') === card) {
        return;
      }
      
      gsap.killTweensOf(document.documentElement);
      gsap.to(document.documentElement, {
        '--green': '#3DFF54',
        '--accent-color': '#3DFF54',
        '--green-dim': 'rgba(61,255,84,.18)',
        '--green-glow': 'rgba(61,255,84,.35)',
        '--body-glow': 'rgba(61,255,84,.035)',
        duration: 0.6,
        ease: 'power2.out'
      });
    });
  }

  /* ============================================================
     14. SVG SPEC RADIAL GAUGES ANIMATION
     ============================================================ */
  function animateGauges(container) {
    var parent = container || document;
    var gauges = parent.querySelectorAll('.radial-gauge');
    gauges.forEach(function(gauge) {
      var fillPath = gauge.querySelector('.radial-gauge__fill');
      if (!fillPath) return;
      
      var targetPercentage = parseFloat(gauge.dataset.value || gauge.dataset.percentage) || 0;
      var counter = gauge.querySelector('.counter');
      var targetVal = counter ? parseFloat(counter.dataset.target) || 0 : 0;
      
      // Reset fill and counter
      gsap.killTweensOf(fillPath);
      fillPath.setAttribute('stroke-dasharray', '0, 100');
      if (counter) counter.textContent = '0';
      
      if (prefersReduced) {
        fillPath.setAttribute('stroke-dasharray', targetPercentage + ', 100');
        if (counter) counter.textContent = targetVal;
        return;
      }
      
      var animData = { val: 0, count: 0 };
      
      if (container) {
        // Immediate spring effect
        gsap.to(animData, {
          val: targetPercentage,
          count: targetVal,
          duration: 1.5,
          ease: 'back.out(1.7)',
          onUpdate: function() {
            fillPath.setAttribute('stroke-dasharray', animData.val.toFixed(1) + ', 100');
            if (counter) counter.textContent = Math.round(animData.count);
          }
        });
      } else {
        // ScrollTrigger activation
        ScrollTrigger.create({
          trigger: gauge,
          start: 'top 95%',
          once: true,
          onEnter: function() {
            gsap.to(animData, {
              val: targetPercentage,
              count: targetVal,
              duration: 1.5,
              ease: 'back.out(1.7)',
              onUpdate: function() {
                fillPath.setAttribute('stroke-dasharray', animData.val.toFixed(1) + ', 100');
                if (counter) counter.textContent = Math.round(animData.count);
              }
            });
          }
        });
      }
    });
  }

  /* ============================================================
     15. INGREDIENT STORYTELLING TOOLTIP HOVERS
     ============================================================ */
  function initIngredientTooltips() {
    var cards = document.querySelectorAll('.ing-card');
    cards.forEach(function(card) {
      var tooltip = card.querySelector('.ing-card__tooltip');
      if (!tooltip) return;
      
      card.addEventListener('mouseenter', function() {
        gsap.killTweensOf(tooltip);
        gsap.fromTo(tooltip, 
          { opacity: 0, y: -15, visibility: 'visible' },
          { opacity: 1, y: 0, duration: 0.45, ease: 'back.out(1.8)' }
        );
      });
      
      card.addEventListener('mouseleave', function() {
        gsap.killTweensOf(tooltip);
        gsap.to(tooltip, {
          opacity: 0,
          y: -10,
          duration: 0.3,
          ease: 'power2.in',
          onComplete: function() {
            tooltip.style.visibility = 'hidden';
          }
        });
      });
    });
  }

  /* ============================================================
     16. PARALLAX PRODUCT FLOATING & DEPTH OF FIELD
     ============================================================ */
  function initParallaxHero() {
    if (prefersReduced || !heroCan) return;
    
    // Parallax can float offset
    gsap.to(heroCan, {
      y: 70,
      scale: 1.04,
      scrollTrigger: {
        trigger: '#top',
        start: 'top top',
        end: 'bottom top',
        scrub: true
      }
    });
    
    // Blur ambient visual elements on scroll for depth-of-field simulation
    var clawBg = document.querySelector('.claw-bg');
    if (clawBg) {
      gsap.to(clawBg, {
        filter: 'blur(8px)',
        scrollTrigger: {
          trigger: '#top',
          start: 'top top',
          end: 'bottom top',
          scrub: true
        }
      });
    }
  }

  /* ============================================================
     17. SMART BENEFIT FILTERING & AJAX REDRAW
     ============================================================ */
  var currentCategory = 'ultra';
  var currentBenefit = '';

  function handleFilterChange() {
    var activeGroup = document.querySelector('.flavor-group.active');
    var oldCards = activeGroup ? activeGroup.querySelectorAll('.ed-card') : [];
    
    var exitTimeline = gsap.timeline();
    
    if (oldCards.length > 0 && !prefersReduced) {
      exitTimeline.to(oldCards, {
        opacity: 0,
        y: 40,
        scale: 0.92,
        rotation: function(i) {
          return i % 2 === 0 ? 4 : -4;
        },
        duration: 0.45,
        stagger: 0.08,
        ease: 'back.out(1.7)',
        onComplete: runUpdate
      });
    } else {
      runUpdate();
    }
    
    function runUpdate() {
      var cacheKey = currentCategory + '_' + currentBenefit;
      
      // Update active category group DOM classes
      document.querySelectorAll('.flavor-group').forEach(function(g) {
        g.classList.remove('active');
      });
      var targetGroup = document.getElementById('group-' + currentCategory);
      if (targetGroup) {
        targetGroup.classList.add('active');
      }
      
      if (productsCache[cacheKey]) {
        updateGridContent(targetGroup, productsCache[cacheKey]);
      } else {
        var url = 'products-api.php?category=' + currentCategory;
        if (currentBenefit) {
          url += '&benefit=' + currentBenefit;
        }
        
        targetGroup.innerHTML = `
          <div class="glass ed-card loading" style="grid-column: 1/-1; height: 350px;"></div>
        `;
        
        fetch(url)
          .then(function(res) { return res.json(); })
          .then(function(json) {
            if (json.success && json.data) {
              productsCache[cacheKey] = json.data;
              updateGridContent(targetGroup, json.data);
            }
          })
          .catch(function(err) {
            console.error('Error filtering products:', err);
          });
      }
    }
  }

  function updateGridContent(container, products) {
    if (products.length === 0) {
      container.innerHTML = `
        <div class="glass error-card" role="alert" style="grid-column: 1/-1;">
          <span class="error-card__icon" aria-hidden="true">⚠️</span>
          <h3>No Flavors Found</h3>
          <p>No products in this line match the selected benefit filter.</p>
        </div>
      `;
      return;
    }

    var html = '';
    products.forEach(function(prod) {
      var hasImage = prod.image_url && prod.image_url.trim() !== '';
      var priceStyle = (prod.accent_color && prod.accent_color !== '#F5F5F5') 
        ? `style="color:${prod.accent_color}"` 
        : '';
        
      var isSugarFree = (parseInt(prod.is_sugar_free) === 1);
        
      html += `
        <div class="glass ed-card ${hasImage ? 'loading' : ''}" data-color="${prod.accent_color}" style="--accent:${prod.accent_color}" data-tilt>
          <div class="ed-card__glow"></div>
      `;
      
      if (isSugarFree) {
        html += `
          <div class="sugar-free-badge">Zero Sugar</div>
        `;
      }
      
      if (hasImage) {
        html += `
          <img src="${prod.image_url}" class="can can--small" alt="${prod.name}" style="object-fit: contain;" onload="this.closest('.ed-card').classList.remove('loading')" onerror="this.closest('.ed-card').classList.remove('loading')">
        `;
      } else {
        var canAccent = prod.can_accent || prod.accent_color;
        var bandStyle = prod.can_band_bg ? `style="background:${prod.can_band_bg}"` : '';
        var mStyle = '';
        if (prod.can_m_color || prod.can_m_shadow) {
          var styles = [];
          if (prod.can_m_color) styles.push('color:' + prod.can_m_color);
          if (prod.can_m_shadow) styles.push('text-shadow:0 0 10px ' + prod.can_m_shadow);
          mStyle = `style="${styles.join('; ')}"`;
        }
        var labelColor = prod.can_label_color ? `style="color:${prod.can_label_color}"` : '';
        var label = prod.can_label || prod.name;
        
        html += `
          <div class="can can--small" style="--accent:${canAccent}">
            <div class="can__body">
              <div class="can__top"></div><div class="can__sheen"></div>
              <div class="can__band" ${bandStyle}>
                <div class="can__m" ${mStyle}>M</div>
                <div class="can__label" ${labelColor}>${label}</div>
              </div>
              <div class="can__bottom-rim"></div>
            </div>
          </div>
        `;
      }
      
      // Dynamic benefits renderer with HTML escaping helper
      function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
      }
      
      var benefitsHtml = '<div class="card-benefits">';
      if (prod.benefits && prod.benefits.length > 0) {
        prod.benefits.forEach(function(b) {
          benefitsHtml += `
            <span class="benefit-icon" data-benefit="${escapeHtml(b.tag)}" aria-label="${escapeHtml(b.name)}">${escapeHtml(b.icon)}
              <span class="spec-tooltip"><strong>${escapeHtml(b.icon)} ${escapeHtml(b.name)}</strong><span>${escapeHtml(b.description)}</span></span>
            </span>
          `;
        });
      }
      benefitsHtml += '</div>';
      
      html += `
          <span class="line-tag">${prod.line_tag}</span>
          <h3>${prod.name}</h3>
          ${benefitsHtml}
          <p>${prod.tagline}</p>
          <div class="spec-gauges">
            <div class="radial-gauge" data-value="${prod.caffeine_level}" style="--accent:${prod.accent_color}">
              <svg viewBox="0 0 36 36">
                <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <div class="radial-gauge__content">
                <span class="radial-gauge__dt">Caffeine</span>
                <span class="radial-gauge__dd"><span class="counter" data-target="${prod.caffeine_level}">0</span>%</span>
              </div>
            </div>
            
            <div class="radial-gauge" data-value="${prod.recovery_factor}" style="--accent:${prod.accent_color}">
              <svg viewBox="0 0 36 36">
                <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <div class="radial-gauge__content">
                <span class="radial-gauge__dt">Recovery</span>
                <span class="radial-gauge__dd"><span class="counter" data-target="${prod.recovery_factor}">0</span>%</span>
              </div>
            </div>

            <div class="radial-gauge" data-value="${prod.tartness_level}" style="--accent:${prod.accent_color}">
              <svg viewBox="0 0 36 36">
                <path class="radial-gauge__bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="radial-gauge__fill" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <div class="radial-gauge__content">
                <span class="radial-gauge__dt">Tartness</span>
                <span class="radial-gauge__dd"><span class="counter" data-target="${prod.tartness_level}">0</span>%</span>
              </div>
            </div>
          </div>
          <span class="price" ${priceStyle}>$${prod.price}</span>
        </div>
      `;
    });
    
    container.innerHTML = html;
    
    // Animate radial gauges inside newly created cards
    animateGauges(container);

    // Stagger entry animation
    var edCards = container.querySelectorAll('.ed-card');
    if (edCards.length > 0 && !prefersReduced) {
      gsap.fromTo(edCards,
        {
          opacity: 0,
          y: 40,
          scale: 0.92,
          rotation: function(i) {
            return i % 2 === 0 ? -4 : 4;
          }
        },
        {
          opacity: 1,
          y: 0,
          scale: 1,
          rotation: 0,
          duration: 0.75,
          stagger: 0.08,
          ease: 'back.out(1.7)',
          clearProps: "transform,opacity"
        }
      );
    }
  }

  function initBenefitTooltips() {
    document.addEventListener('mouseenter', function(e) {
      var icon = e.target.closest('.benefit-icon');
      if (!icon) return;
      var tooltip = icon.querySelector('.spec-tooltip');
      if (!tooltip) return;
      
      gsap.killTweensOf(tooltip);
      gsap.fromTo(tooltip, 
        { opacity: 0, scale: 0.85, y: 10, visibility: 'visible' },
        { opacity: 1, scale: 1, y: 0, duration: 0.4, ease: 'back.out(1.8)' }
      );
    }, true);
    
    document.addEventListener('mouseleave', function(e) {
      var icon = e.target.closest('.benefit-icon');
      if (!icon) return;
      var tooltip = icon.querySelector('.spec-tooltip');
      if (!tooltip) return;
      
      gsap.killTweensOf(tooltip);
      gsap.to(tooltip, {
        opacity: 0,
        scale: 0.85,
        y: 8,
        duration: 0.25,
        ease: 'power2.in',
        onComplete: function() {
          tooltip.style.visibility = 'hidden';
        }
      });
    }, true);
  }

  function initFilters() {
    var tabs = document.querySelectorAll('.flavor-tab');
    var benefits = document.querySelectorAll('.benefit-filter');
    
    tabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        tabs.forEach(function(t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        
        currentCategory = tab.dataset.group;
        handleFilterChange();
      });
    });

    benefits.forEach(function(btn) {
      btn.addEventListener('click', function() {
        benefits.forEach(function(b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        currentBenefit = btn.dataset.benefit;
        handleFilterChange();
      });
    });
  }

  // Initialize and attach behaviors on page load
  window.addEventListener('load', function() {
    setTimeout(function() {
      var activeGroup = document.getElementById('group-' + currentCategory);
      animateGauges(activeGroup);
      animateGauges();
      initDynamicTheming();
      initIngredientTooltips();
      initBenefitTooltips();
      initParallaxHero();
      initFilters();
    }, 200);
  });

})();
