/**
 * Magic Card Effect - Lytbox Style
 *
 * Dla POJEDYNCZEGO: .has-magic-card - dodaje strukturę do tego elementu
 * Dla CHILDREN: .magic-card-enabled-yes - znajduje wszystkie [data-element_type="container"] wewnątrz i do każdego dodaje strukturę
 */

(function() {
    'use strict';

    // Track observed elements for viewport detection
    var viewportObserver = null;
    var observedElements = new Set();

    /**
     * Dodaj strukturę magic card do elementu (to co działało dla pojedynczego)
     */
    function setupMagicCard(element, childIndex) {
        // Pomiń jeśli już ma strukturę
        if (element.querySelector(':scope > .magic-holder')) {
            return;
        }

        // Dodaj isolation i position
        element.style.position = 'relative';
        element.style.isolation = 'isolate';

        // Layer 1: magic-holder z blob (z-index: -2)
        var holder = document.createElement('div');
        holder.className = 'magic-holder';

        // Store child index for cascade delay calculation
        if (typeof childIndex === 'number') {
            holder.setAttribute('data-child-index', childIndex);
        }

        var blob = document.createElement('div');
        blob.className = 'blob';

        var fakeblob = document.createElement('div');
        fakeblob.className = 'fakeblob';

        holder.appendChild(blob);
        holder.appendChild(fakeblob);

        // Layer 2: inner-blur (z-index: -1)
        var blur = document.createElement('div');
        blur.className = 'magic-inner-blur';

        // Layer 3: inner-glow wrapper (z-index: 0) - clips the glow to card bounds
        var innerGlow = document.createElement('div');
        innerGlow.className = 'magic-inner-glow';

        // Two layers for crossfade animation
        var layer1 = document.createElement('div');
        layer1.className = 'magic-inner-glow-layer active';
        var layer2 = document.createElement('div');
        layer2.className = 'magic-inner-glow-layer';
        innerGlow.appendChild(layer1);
        innerGlow.appendChild(layer2);

        // Wstaw na początek (order matters for stacking)
        element.insertBefore(innerGlow, element.firstChild);
        element.insertBefore(blur, element.firstChild);
        element.insertBefore(holder, element.firstChild);

        // Store references on holder for easy access
        holder._innerGlow = innerGlow;
        holder._innerGlowLayers = [layer1, layer2];
        holder._activeLayerIndex = 0;

        // Apply effect mode for CSS targeting (needed for editor live preview)
        applyEffectModeToHolder(element);

        // Setup spotlight always-on mode
        setupSpotlightAlwaysOn(element, holder, blob);

        // Setup conic glow element if in conic_glow mode
        setupConicGlow(element, holder);
    }

    /**
     * Setup conic glow element for conic_glow mode
     * Creates real div elements instead of pseudo-elements for better control
     * Stores references on holder for fast access (avoids querySelector in animation loop)
     */
    function setupConicGlow(element, holder) {
        var effectMode = getEffectMode(element);
        if (effectMode !== 'conic_glow') return;

        // Create .magic-conic-glow container if not exists
        var glowEl = holder.querySelector('.magic-conic-glow');
        if (!glowEl) {
            glowEl = document.createElement('div');
            glowEl.className = 'magic-conic-glow';
            holder.appendChild(glowEl);
        }

        // Create idle border element (replaces ::before)
        var idleEl = glowEl.querySelector('.magic-conic-idle');
        if (!idleEl) {
            idleEl = document.createElement('div');
            idleEl.className = 'magic-conic-idle';
            glowEl.appendChild(idleEl);
        }

        // Create spotlight element (replaces ::after)
        var spotlightEl = glowEl.querySelector('.magic-conic-spotlight');
        if (!spotlightEl) {
            spotlightEl = document.createElement('div');
            spotlightEl.className = 'magic-conic-spotlight';
            glowEl.appendChild(spotlightEl);
        }

        // Store references on holder for fast access in animation loop
        holder._conicSpotlight = spotlightEl;
        holder._conicIdle = idleEl;
        // Note: inner glow (_innerGlow) is created in setupMagicCard as sibling of holder

        // Apply conic styles directly to elements (reads from computed style for inheritance)
        applyConicStyles(element, holder, idleEl, spotlightEl);

        // If always visible, show glow immediately at default position (top)
        if (isConicAlwaysVisible(element)) {
            setConicStartAngle(holder, 0);
            setConicActive(holder, 1);
        }

        // Setup inner glow color animation
        setupInnerGlowColorAnimation(element, holder);
    }

    /**
     * Ensure inner glow wrapper and layers exist
     * Creates them if missing (for editor compatibility)
     */
    function ensureInnerGlowLayers(element, holder) {
        // Check if already set up
        if (holder._innerGlowLayers && holder._innerGlowLayers.length === 2) {
            return;
        }

        // Find or create inner glow wrapper
        var innerGlow = holder._innerGlow || element.querySelector(':scope > .magic-inner-glow');
        if (!innerGlow) {
            innerGlow = document.createElement('div');
            innerGlow.className = 'magic-inner-glow';
            // Insert after holder (before content)
            var blur = element.querySelector(':scope > .magic-inner-blur');
            if (blur && blur.nextSibling) {
                element.insertBefore(innerGlow, blur.nextSibling);
            } else {
                element.insertBefore(innerGlow, element.firstChild);
            }
        }
        holder._innerGlow = innerGlow;

        // Check for existing layers
        var existingLayers = innerGlow.querySelectorAll('.magic-inner-glow-layer');
        if (existingLayers.length === 2) {
            holder._innerGlowLayers = [existingLayers[0], existingLayers[1]];
            holder._activeLayerIndex = existingLayers[0].classList.contains('active') ? 0 : 1;
            return;
        }

        // Remove old structure if exists (e.g., old .magic-inner-glow-effect)
        var oldEffect = innerGlow.querySelector('.magic-inner-glow-effect');
        if (oldEffect) {
            oldEffect.remove();
        }

        // Create two layers for crossfade
        var layer1 = document.createElement('div');
        layer1.className = 'magic-inner-glow-layer active';
        var layer2 = document.createElement('div');
        layer2.className = 'magic-inner-glow-layer';
        innerGlow.appendChild(layer1);
        innerGlow.appendChild(layer2);

        holder._innerGlowLayers = [layer1, layer2];
        holder._activeLayerIndex = 0;
    }

    /**
     * Setup inner glow color cycling animation
     * Uses two layers with crossfade for smooth transitions
     */
    function setupInnerGlowColorAnimation(element, holder) {
        var layers = holder._innerGlowLayers;
        if (!layers || layers.length < 2) return;

        // Get glow size from CSS variable
        var computedStyle = getComputedStyle(element);
        var glowSize = computedStyle.getPropertyValue('--magic-card-inner-glow-size').trim() || '150px';

        // Helper to build gradient background
        function buildGradient(color) {
            return 'radial-gradient(circle ' + glowSize + ' at var(--magic-conic-edge-x, 50%) var(--magic-conic-edge-y, 50%), ' + color + ' 0%, transparent 100%)';
        }

        // Get colors from data attribute (on element or parent)
        var colorsJson = element.getAttribute('data-magic-inner-glow-colors') ||
                         (element.closest('[data-magic-inner-glow-colors]') ?
                          element.closest('[data-magic-inner-glow-colors]').getAttribute('data-magic-inner-glow-colors') : null);

        var colors = null;
        if (colorsJson) {
            try {
                colors = JSON.parse(colorsJson);
            } catch (e) {
                console.warn('Magic Card: Failed to parse inner glow colors', e);
            }
        }

        // Fallback to CSS variable color or default
        if (!colors || colors.length < 1) {
            var fallbackColor = computedStyle.getPropertyValue('--magic-card-inner-glow-color').trim() || 'rgba(14, 165, 233, 0.3)';
            colors = [fallbackColor];
        }

        // Set initial color on both layers
        layers[0].style.background = buildGradient(colors[0]);
        layers[1].style.background = buildGradient(colors[0]);

        // If only one color, done - no animation needed
        if (colors.length === 1) return;

        // Get animation speed from CSS variable (default 5s for full cycle)
        var speed = parseFloat(computedStyle.getPropertyValue('--magic-card-inner-glow-speed')) || 5;
        var intervalMs = (speed * 1000) / colors.length;

        // Store animation state on holder
        holder._colorIndex = 0;
        holder._glowColors = colors;
        holder._buildGradient = buildGradient;

        // Start color cycling with crossfade
        holder._colorInterval = setInterval(function() {
            // Move to next color
            holder._colorIndex = (holder._colorIndex + 1) % holder._glowColors.length;
            var nextColor = holder._glowColors[holder._colorIndex];

            // Get current and next layer
            var currentLayerIdx = holder._activeLayerIndex;
            var nextLayerIdx = 1 - currentLayerIdx;

            // Set next color on inactive layer
            layers[nextLayerIdx].style.background = holder._buildGradient(nextColor);

            // Crossfade: activate next, deactivate current
            layers[nextLayerIdx].classList.add('active');
            layers[currentLayerIdx].classList.remove('active');

            // Update active layer index
            holder._activeLayerIndex = nextLayerIdx;
        }, intervalMs);
    }

    /**
     * Apply conic glow styles to holder element
     * CSS variables are set on holder and inherited by children via CSS
     */
    function applyConicStyles(element, holder, idleEl, spotlightEl) {
        var computedStyle = getComputedStyle(element);

        // Get values from computed style (inherits from parent in children mode)
        var gradient = computedStyle.getPropertyValue('--magic-card-conic-gradient').trim();
        var idleOpacity = computedStyle.getPropertyValue('--magic-card-conic-idle-opacity').trim();
        var borderWidth = computedStyle.getPropertyValue('--magic-card-conic-border-width').trim();
        var blur = computedStyle.getPropertyValue('--magic-card-conic-blur').trim();
        var spread = computedStyle.getPropertyValue('--magic-card-conic-spread').trim();

        // Set CSS variables on holder - children inherit via CSS
        if (gradient) {
            holder.style.setProperty('--magic-card-conic-gradient', gradient);
        }
        if (idleOpacity) {
            holder.style.setProperty('--magic-card-conic-idle-opacity', idleOpacity);
        }
        if (borderWidth) {
            holder.style.setProperty('--magic-card-conic-border-width', borderWidth);
        }
        if (blur) {
            holder.style.setProperty('--magic-card-conic-blur', blur);
        }
        if (spread) {
            holder.style.setProperty('--magic-card-conic-spread', spread);
        }
    }

    /**
     * Setup spotlight always-on mode - show blob immediately in default position
     */
    function setupSpotlightAlwaysOn(element, holder, blob) {
        // Check if always-on mode is enabled (on element or parent)
        var alwaysOn = getAlwaysOnSetting(element);
        if (!alwaysOn) return;

        // Mark holder as always-on for CSS styling
        holder.classList.add('spotlight-always-on');

        // Calculate and apply default position
        updateSpotlightDefaultPosition(element, blob);
    }

    /**
     * Calculate and update spotlight default position based on CSS variables
     */
    function updateSpotlightDefaultPosition(element, blob) {
        var rect = element.getBoundingClientRect();
        var style = getComputedStyle(element);
        var blobSize = parseInt(style.getPropertyValue('--magic-card-blob-size')) || 250;

        // Get X/Y percentages from CSS variables (default 50% = center)
        var xPercent = parseFloat(style.getPropertyValue('--magic-card-spotlight-x')) || 50;
        var yPercent = parseFloat(style.getPropertyValue('--magic-card-spotlight-y')) || 50;

        // Convert percentage to pixel position (center blob on that point)
        var posX = (rect.width * xPercent / 100) - (blobSize / 2);
        var posY = (rect.height * yPercent / 100) - (blobSize / 2);

        // Set initial position and make visible
        blob.style.transform = 'translate(' + posX + 'px, ' + posY + 'px)';
        blob.style.opacity = '1';

        // Store default position for returning after hover
        blob._defaultPosition = { x: posX, y: posY };
        blob._parentElement = element;
    }

    /**
     * Recalculate default position (called when CSS variables change)
     */
    function recalculateSpotlightPositions() {
        document.querySelectorAll('.magic-holder.spotlight-always-on').forEach(function(holder) {
            var blob = holder.querySelector('.blob');
            var element = holder.parentElement;
            if (blob && element && !blob._isHovering) {
                updateSpotlightDefaultPosition(element, blob);
            }
        });
    }

    /**
     * Get always-on setting from element or parent
     */
    function getAlwaysOnSetting(element) {
        // Check element itself
        if (element.getAttribute('data-magic-spotlight-always-on') === 'true') {
            return true;
        }
        // Check parent (for children mode)
        var parent = element.closest('[data-magic-spotlight-always-on="true"]');
        return !!parent;
    }

    /**
     * Update effect modes on all existing magic cards (for editor changes)
     */
    function updateAllEffectModes() {
        document.querySelectorAll('.has-magic-card, .magic-card-parent [data-element_type="container"], .magic-card-enabled-yes [data-element_type="container"]').forEach(function(el) {
            var holder = el.querySelector(':scope > .magic-holder');
            if (holder) {
                applyEffectModeToHolder(el);

                // Update conic glow styles if in conic_glow mode
                if (holder.classList.contains('mode-conic_glow')) {
                    // Use cached references or fallback to querySelector
                    var idleEl = holder._conicIdle || holder.querySelector('.magic-conic-idle');
                    var spotlightEl = holder._conicSpotlight || holder.querySelector('.magic-conic-spotlight');
                    if (idleEl && spotlightEl) {
                        applyConicStyles(el, holder, idleEl, spotlightEl);
                    }

                    // Setup/update inner glow color animation
                    // Ensure layers exist (may need to create them if element was created before this update)
                    ensureInnerGlowLayers(el, holder);
                    if (!holder._colorInterval && holder._innerGlowLayers) {
                        setupInnerGlowColorAnimation(el, holder);
                    }
                }
            }
        });
    }

    /**
     * Przetwórz wszystkie magic cards
     */
    function processAllCards() {
        // 1. POJEDYNCZY TRYB: .has-magic-card
        document.querySelectorAll('.has-magic-card').forEach(function(el) {
            setupMagicCard(el);
            setupViewportTrigger(el);
        });

        // 2. CHILDREN lub SELF TRYB: .magic-card-parent, .magic-card-enabled-yes
        // Sprawdź data-magic-card-mode aby określić tryb
        document.querySelectorAll('.magic-card-parent, .magic-card-enabled-yes').forEach(function(parent) {
            // Pomiń jeśli już przetworzony jako .has-magic-card
            if (parent.classList.contains('has-magic-card')) {
                return;
            }

            var applyMode = parent.getAttribute('data-magic-card-mode');
            var excludeClass = parent.getAttribute('data-magic-card-exclude');
            var targetLevel = parseInt(parent.getAttribute('data-magic-target-level')) || 1;

            // SELF mode: efekt na tym elemencie (nie na dzieciach)
            if (applyMode !== 'children') {
                setupMagicCard(parent);
                setupViewportTrigger(parent);
                return;
            }

            // CHILDREN mode: znajdź kontenery na odpowiednim poziomie zagnieżdżenia
            var children = findContainersAtLevel(parent, targetLevel);

            // Jeśli nie ma dzieci na tym poziomie, fallback do self
            if (children.length === 0) {
                setupMagicCard(parent);
                setupViewportTrigger(parent);
                return;
            }

            // CHILDREN mode: efekt na dzieciach kontenerach
            var childIndex = 0;
            children.forEach(function(child) {
                // Pomiń wykluczone - sprawdź czy ma tę klasę
                if (excludeClass && excludeClass.trim() !== '' && child.classList.contains(excludeClass.trim())) {
                    return;
                }
                setupMagicCard(child, childIndex);
                childIndex++;
            });

            // Setup viewport trigger for parent (will cascade to children)
            setupViewportTriggerForChildren(parent, children, excludeClass);
        });
    }

    /**
     * Find containers at specified nesting level
     * Level 1 = direct children, Level 2 = grandchildren, etc.
     */
    function findContainersAtLevel(parent, level) {
        var inner = parent.querySelector(':scope > .e-con-inner');
        var searchIn = inner || parent;

        // Get direct children first
        var currentLevel = Array.from(searchIn.querySelectorAll(':scope > [data-element_type="container"]'));

        if (level === 1) {
            return currentLevel;
        }

        // For level 2+, we need to go deeper
        var currentDepth = 1;

        while (currentDepth < level && currentLevel.length > 0) {
            var nextLevel = [];
            currentLevel.forEach(function(container) {
                var containerInner = container.querySelector(':scope > .e-con-inner');
                var containerSearchIn = containerInner || container;
                var nestedContainers = containerSearchIn.querySelectorAll(':scope > [data-element_type="container"]');
                nextLevel = nextLevel.concat(Array.from(nestedContainers));
            });
            currentLevel = nextLevel;
            currentDepth++;
        }

        return currentLevel;
    }

    /**
     * Setup viewport trigger for single element
     */
    function setupViewportTrigger(element) {
        var hasViewportTrigger = element.getAttribute('data-magic-viewport-trigger') === 'true';
        var holder = element.querySelector(':scope > .magic-holder');

        if (!holder) return;

        // Mark if viewport enabled for CSS fallback detection
        if (hasViewportTrigger) {
            holder.classList.add('magic-viewport-enabled');
        }

        // If no viewport trigger, activate immediately
        if (!hasViewportTrigger) {
            activateHolder(holder, 0);
            return;
        }

        // Setup IntersectionObserver
        observeElement(element, function() {
            var startDelay = getComputedStyleValue(element, '--magic-card-start-delay', 0);
            activateHolder(holder, startDelay);
        });
    }

    /**
     * Setup viewport trigger for children mode with cascade delay
     */
    function setupViewportTriggerForChildren(parent, children, excludeClass) {
        var hasViewportTrigger = parent.getAttribute('data-magic-viewport-trigger') === 'true';

        // Get all valid children (non-excluded)
        var validChildren = [];
        children.forEach(function(child) {
            if (excludeClass && excludeClass.trim() !== '' && child.classList.contains(excludeClass.trim())) {
                return;
            }
            var holder = child.querySelector(':scope > .magic-holder');
            if (holder) {
                if (hasViewportTrigger) {
                    holder.classList.add('magic-viewport-enabled');
                }
                validChildren.push({ element: child, holder: holder });
            }
        });

        // If no viewport trigger, activate all immediately
        if (!hasViewportTrigger) {
            validChildren.forEach(function(item) {
                activateHolder(item.holder, 0);
            });
            return;
        }

        // Setup IntersectionObserver for parent
        observeElement(parent, function() {
            var startDelay = getComputedStyleValue(parent, '--magic-card-start-delay', 0);
            var cascadeDelay = getComputedStyleValue(parent, '--magic-card-cascade-delay', 150);

            // Activate children with cascade delay
            validChildren.forEach(function(item, index) {
                var totalDelay = startDelay + (index * cascadeDelay);
                activateHolder(item.holder, totalDelay);
            });
        });
    }

    /**
     * Activate a holder (start animation)
     */
    function activateHolder(holder, delay) {
        if (holder.classList.contains('magic-active')) return;

        if (delay > 0) {
            setTimeout(function() {
                holder.classList.add('magic-active');
            }, delay);
        } else {
            holder.classList.add('magic-active');
        }
    }

    /**
     * Get computed CSS variable value as number
     */
    function getComputedStyleValue(element, property, defaultValue) {
        var style = getComputedStyle(element);
        var value = style.getPropertyValue(property);
        if (value) {
            // Parse number from value (remove 'ms', 'px', etc.)
            var num = parseFloat(value);
            if (!isNaN(num)) {
                return num;
            }
        }
        return defaultValue;
    }

    /**
     * Setup IntersectionObserver and observe element
     */
    function observeElement(element, callback) {
        // Avoid duplicate observation
        if (observedElements.has(element)) return;
        observedElements.add(element);

        // Create observer if not exists
        if (!viewportObserver) {
            viewportObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        // Get stored callback
                        var cb = entry.target._magicCardCallback;
                        if (cb) {
                            cb();
                            // Unobserve after trigger (one-time)
                            viewportObserver.unobserve(entry.target);
                            observedElements.delete(entry.target);
                            delete entry.target._magicCardCallback;
                        }
                    }
                });
            }, {
                threshold: 0.1, // Trigger when 10% visible
                rootMargin: '0px 0px -50px 0px' // Slight offset from bottom
            });
        }

        // Store callback on element
        element._magicCardCallback = callback;
        viewportObserver.observe(element);
    }

    /**
     * Get effect mode for an element (check parent or self)
     */
    function getEffectMode(element) {
        // Check if element itself has the mode
        var mode = element.getAttribute('data-magic-effect-mode');
        if (mode) return mode;

        // Check parent (for children mode)
        var parent = element.closest('[data-magic-effect-mode]');
        if (parent) {
            return parent.getAttribute('data-magic-effect-mode');
        }

        // Fallback: check CSS variable (useful in Elementor editor)
        var style = getComputedStyle(element);
        var cssMode = style.getPropertyValue('--magic-card-effect-mode');
        if (cssMode) {
            cssMode = cssMode.trim();
            if (cssMode === 'animated_border' || cssMode === 'beam' || cssMode === 'conic_glow') {
                return cssMode;
            }
        }

        // Check parent for CSS variable
        var parentWithClass = element.closest('.magic-card-parent, .magic-card-enabled-yes, .has-magic-card');
        if (parentWithClass) {
            var parentStyle = getComputedStyle(parentWithClass);
            var parentCssMode = parentStyle.getPropertyValue('--magic-card-effect-mode');
            if (parentCssMode) {
                parentCssMode = parentCssMode.trim();
                if (parentCssMode === 'animated_border' || parentCssMode === 'beam' || parentCssMode === 'conic_glow') {
                    return parentCssMode;
                }
            }
        }

        return 'spotlight'; // default
    }

    /**
     * Apply effect mode class to magic-holder for CSS targeting
     */
    function applyEffectModeToHolder(element) {
        var holder = element.querySelector(':scope > .magic-holder');
        if (!holder) return;

        var mode = getEffectMode(element);

        // Remove old mode classes
        holder.classList.remove('mode-spotlight', 'mode-animated_border', 'mode-beam', 'mode-conic_glow');

        // Add current mode class
        holder.classList.add('mode-' + mode);

        // For animated/beam/conic modes, also set data attribute on parent for CSS
        if (mode === 'animated_border' || mode === 'beam' || mode === 'conic_glow') {
            element.setAttribute('data-magic-effect-mode', mode);
        }
    }

    /**
     * Check if element is in connected glow mode
     */
    function isConnectedGlowMode(element) {
        var parent = element.closest('[data-magic-glow-behavior="connected"]');
        return !!parent;
    }

    /**
     * Calculate angle from element center to cursor position (in degrees)
     */
    function calculateAngleFromCenter(x, y, element) {
        var rect = element.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;

        // Calculate angle in radians, then convert to degrees
        var radians = Math.atan2(y - centerY, x - centerX);
        var degrees = radians * (180 / Math.PI);

        // Convert to 0-360 range (atan2 returns -180 to 180)
        return (degrees + 360) % 360;
    }

    /**
     * Ensure conic glow elements exist in holder (container + idle + spotlight)
     * Also caches references on holder for fast access
     * Note: inner glow is created separately in setupMagicCard as sibling of holder
     */
    function ensureConicGlowElement(holder) {
        // Check if already set up via cached reference
        if (holder._conicSpotlight && holder._conicIdle) {
            return holder._conicSpotlight.parentElement;
        }

        var glowEl = holder.querySelector('.magic-conic-glow');
        if (!glowEl) {
            glowEl = document.createElement('div');
            glowEl.className = 'magic-conic-glow';
            holder.appendChild(glowEl);
        }

        // Ensure idle element exists
        var idleEl = glowEl.querySelector('.magic-conic-idle');
        if (!idleEl) {
            idleEl = document.createElement('div');
            idleEl.className = 'magic-conic-idle';
            glowEl.appendChild(idleEl);
        }

        // Ensure spotlight element exists
        var spotlightEl = glowEl.querySelector('.magic-conic-spotlight');
        if (!spotlightEl) {
            spotlightEl = document.createElement('div');
            spotlightEl.className = 'magic-conic-spotlight';
            glowEl.appendChild(spotlightEl);
        }

        // Cache references
        holder._conicSpotlight = spotlightEl;
        holder._conicIdle = idleEl;

        return glowEl;
    }

    /**
     * Check if cursor is in inactive zone (center of element)
     */
    function isInInactiveZone(mouseX, mouseY, element) {
        var rect = element.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;
        var maxRadius = Math.min(rect.width, rect.height) / 2;

        // Get inactive zone multiplier from CSS variable
        var inactiveZone = getComputedStyleValue(element, '--magic-card-conic-inactive-zone', 0.7);
        var inactiveRadius = maxRadius * inactiveZone;

        var distance = Math.sqrt(Math.pow(mouseX - centerX, 2) + Math.pow(mouseY - centerY, 2));
        return distance < inactiveRadius;
    }

    /**
     * Check if cursor is within proximity of element
     */
    function isWithinProximity(mouseX, mouseY, element) {
        var rect = element.getBoundingClientRect();
        var proximity = getComputedStyleValue(element, '--magic-card-conic-proximity', 0);

        return mouseX >= rect.left - proximity &&
               mouseX <= rect.right + proximity &&
               mouseY >= rect.top - proximity &&
               mouseY <= rect.bottom + proximity;
    }

    /**
     * Normalize angle difference to handle wrap-around (e.g., 350° to 10°)
     * Returns value between -180 and 180
     */
    function angleDiff(current, target) {
        var diff = ((target - current + 180) % 360) - 180;
        return diff;
    }

    /**
     * Handle conic glow effect for a single element
     * Uses continuous animation loop that smoothly follows target
     * Optimized: only updates DOM when values actually change
     */
    function handleConicGlow(ev, element, holder, isConnected, opacity) {
        // Ensure glow element exists
        ensureConicGlowElement(holder);

        // Get element bounds
        var rect = element.getBoundingClientRect();
        var centerX = rect.left + rect.width * 0.5;
        var centerY = rect.top + rect.height * 0.5;

        // Calculate edge position for inner glow (glow sticks to nearest edge)
        var innerGlowLayers = holder._innerGlowLayers;
        if (innerGlowLayers && innerGlowLayers.length > 0) {
            // Calculate cursor position relative to element (0-1 range)
            var relX = (ev.clientX - rect.left) / rect.width;
            var relY = (ev.clientY - rect.top) / rect.height;

            // Find nearest edge and project cursor position onto it
            var distToLeft = relX;
            var distToRight = 1 - relX;
            var distToTop = relY;
            var distToBottom = 1 - relY;

            var minDist = Math.min(distToLeft, distToRight, distToTop, distToBottom);

            var edgeX, edgeY;

            if (minDist === distToLeft) {
                // Nearest to left edge
                edgeX = 0;
                edgeY = relY * 100;
            } else if (minDist === distToRight) {
                // Nearest to right edge
                edgeX = 100;
                edgeY = relY * 100;
            } else if (minDist === distToTop) {
                // Nearest to top edge
                edgeX = relX * 100;
                edgeY = 0;
            } else {
                // Nearest to bottom edge
                edgeX = relX * 100;
                edgeY = 100;
            }

            // Set position on both layers
            var edgeXStr = edgeX.toFixed(1) + '%';
            var edgeYStr = edgeY.toFixed(1) + '%';
            innerGlowLayers.forEach(function(layer) {
                layer.style.setProperty('--magic-conic-edge-x', edgeXStr);
                layer.style.setProperty('--magic-conic-edge-y', edgeYStr);
            });
        }

        // Calculate target angle (same formula as Aceternity)
        var targetAngle = (180 * Math.atan2(ev.clientY - centerY, ev.clientX - centerX)) / Math.PI + 90;

        // Initialize current angle if not set
        if (typeof holder._currentAngle === 'undefined') {
            holder._currentAngle = targetAngle;
            setConicStartAngle(holder, targetAngle);
        }

        // Store target angle for animation loop
        holder._targetAngle = targetAngle;

        // Check inactive zone
        var inInactiveZone = isInInactiveZone(ev.clientX, ev.clientY, element);

        // Handle opacity - only update DOM if changed
        var finalOpacity;
        if (isConnected) {
            finalOpacity = inInactiveZone ? opacity * 0.3 : opacity;
        } else {
            finalOpacity = inInactiveZone ? 0.3 : 1;
        }

        // Only update CSS if opacity actually changed (avoid constant DOM writes)
        var newOpacity = finalOpacity.toFixed(2);
        if (holder._lastOpacity !== newOpacity) {
            holder._lastOpacity = newOpacity;
            setConicActive(holder, finalOpacity);
        }

        // Start animation loop only if not running AND target is different from current
        if (!holder._animating) {
            var diff = Math.abs(angleDiff(holder._currentAngle, holder._targetAngle));
            if (diff > 0.5) {
                holder._animating = true;
                animateConicGlowLoop(holder);
            }
        }
    }

    /**
     * Set conic start angle on holder
     * CSS variables inherit to children, no need to set on each element
     */
    function setConicStartAngle(holder, angle) {
        var angleStr = typeof angle === 'number' ? angle.toFixed(1) : angle;
        holder.style.setProperty('--magic-conic-start', angleStr);
    }

    /**
     * Set conic active state (opacity) on holder and inner glow
     * CSS variables inherit to children for conic elements
     * Inner glow is separate element, needs direct opacity update
     */
    function setConicActive(holder, value) {
        var valueStr = typeof value === 'number' ? value.toFixed(2) : value;
        holder.style.setProperty('--magic-conic-active', valueStr);

        // Update inner glow opacity directly (it's a sibling, not child of holder)
        var innerGlow = holder._innerGlow;
        if (innerGlow) {
            innerGlow.style.opacity = valueStr;
        }
    }

    /**
     * Continuous animation loop for smooth conic glow
     * Uses dynamic smoothing - fast when far, slow when close (easeOut feel)
     * IMPORTANT: Stops automatically when target is reached to save CPU
     */
    function animateConicGlowLoop(holder) {
        if (!holder._animating) return;

        // Calculate angle difference
        var diff = angleDiff(holder._currentAngle, holder._targetAngle);
        var absDiff = Math.abs(diff);

        // Stop animation if close enough to target (< 0.5 degree)
        // Animation will restart when mouse moves again
        if (absDiff < 0.5) {
            holder._currentAngle = holder._targetAngle;
            setConicStartAngle(holder, holder._currentAngle);
            holder._animating = false;
            return;
        }

        // Dynamic smoothing based on distance:
        // Lower values = slower, more visible easing
        // At 60fps, smoothing of 0.1 means ~90% of distance covered in ~40 frames (~0.6s)
        var smoothing;
        if (absDiff > 90) {
            // Very far - fast catch up
            smoothing = 0.15;
        } else if (absDiff > 45) {
            // Far - moderate speed
            smoothing = 0.08 + (absDiff - 45) / 45 * 0.07;
        } else if (absDiff > 15) {
            // Medium - slowing down
            smoothing = 0.03 + (absDiff - 15) / 30 * 0.05;
        } else if (absDiff > 3) {
            // Close - slow approach
            smoothing = 0.015 + (absDiff - 3) / 12 * 0.015;
        } else {
            // Very close - super smooth landing
            smoothing = 0.008 + absDiff / 3 * 0.007;
        }

        // Apply smoothing
        holder._currentAngle += diff * smoothing;

        // Update CSS on holder and spotlight
        setConicStartAngle(holder, holder._currentAngle);

        // Continue loop
        requestAnimationFrame(function() {
            animateConicGlowLoop(holder);
        });
    }

    /**
     * Hide conic glow effect
     */
    function hideConicGlow(holder, keepVisible) {
        holder._animating = false;
        if (!keepVisible) {
            setConicActive(holder, 0);
            // Reset lastOpacity so next hover triggers update
            holder._lastOpacity = '0.00';
        }
        // If keepVisible is true, animation stops but glow stays visible
    }

    /**
     * Check if conic glow should be always visible
     */
    function isConicAlwaysVisible(element) {
        return element.hasAttribute('data-magic-conic-always-visible') ||
               element.closest('[data-magic-conic-always-visible]') !== null;
    }

    /**
     * Check if conic glow should persist (stay visible when mouse leaves)
     */
    function isConicGlowPersists(element) {
        return element.hasAttribute('data-magic-conic-glow-persists') ||
               element.closest('[data-magic-conic-glow-persists]') !== null;
    }

    /**
     * Handle conic glow in connected mode for a group
     */
    function handleConnectedConicGlow(ev, group) {
        var glowRadius = getComputedStyleValue(group.parent, '--magic-card-glow-radius', 400);
        var glowPersists = isConicGlowPersists(group.parent);

        group.children.forEach(function(child) {
            var holder = child.querySelector(':scope > .magic-holder');
            if (!holder || !holder.classList.contains('mode-conic_glow')) return;

            var distance = distanceToElementCenter(ev.clientX, ev.clientY, child);
            var opacity = Math.max(0, 1 - (distance / glowRadius));

            if (opacity > 0) {
                handleConicGlow(ev, child, holder, true, opacity);
            } else {
                // Out of range - hide or persist
                hideConicGlow(holder, glowPersists);
            }
        });
    }

    /**
     * Get connected glow parent and its children at the correct nesting level
     */
    function getConnectedGlowGroup(element) {
        var parent = element.closest('[data-magic-glow-behavior="connected"]');
        if (!parent) return null;

        var targetLevel = parseInt(parent.getAttribute('data-magic-target-level')) || 1;
        var children = findContainersAtLevel(parent, targetLevel);

        return {
            parent: parent,
            children: children.filter(function(child) {
                return child.querySelector(':scope > .magic-holder');
            })
        };
    }

    /**
     * Calculate distance from point to element center
     */
    function distanceToElementCenter(x, y, element) {
        var rect = element.getBoundingClientRect();
        var centerX = rect.left + rect.width / 2;
        var centerY = rect.top + rect.height / 2;
        return Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    }

    /**
     * Clamp blob position within element bounds
     */
    function clampBlobPosition(x, y, element, blobSize) {
        var rect = element.getBoundingClientRect();
        var halfBlob = blobSize / 2;

        // Calculate position relative to element
        var relX = x - rect.left - halfBlob;
        var relY = y - rect.top - halfBlob;

        // Clamp to element bounds (allow blob to be partially outside)
        var maxX = rect.width - halfBlob;
        var maxY = rect.height - halfBlob;
        var minX = -halfBlob;
        var minY = -halfBlob;

        return {
            x: Math.max(minX, Math.min(maxX, relX)),
            y: Math.max(minY, Math.min(maxY, relY))
        };
    }

    /**
     * Handle connected glow mode for a group of cards
     */
    function handleConnectedGlow(ev, group) {
        var glowRadius = getComputedStyleValue(group.parent, '--magic-card-glow-radius', 400);

        group.children.forEach(function(child) {
            var holder = child.querySelector(':scope > .magic-holder');
            var blob = holder ? holder.querySelector('.blob') : null;
            if (!blob) return;

            var effectMode = getEffectMode(child);
            if (effectMode !== 'spotlight') return;

            var distance = distanceToElementCenter(ev.clientX, ev.clientY, child);
            var rect = child.getBoundingClientRect();
            var blobSize = parseInt(getComputedStyle(child).getPropertyValue('--magic-card-blob-size')) || 250;

            // Calculate opacity based on distance (1 at center, 0 at radius edge)
            var opacity = Math.max(0, 1 - (distance / glowRadius));

            if (opacity > 0) {
                // Calculate blob position - follow cursor but clamp to card bounds
                var pos = clampBlobPosition(ev.clientX, ev.clientY, child, blobSize);

                blob.animate([{
                    transform: 'translate(' + pos.x + 'px, ' + pos.y + 'px)'
                }], { duration: 300, fill: 'forwards' });

                blob.style.opacity = opacity.toFixed(2);
                blob._isConnectedActive = true;
            } else if (blob._isConnectedActive) {
                // Out of range - fade out
                blob.style.opacity = '0';
                blob._isConnectedActive = false;
            }
        });
    }

    /**
     * Mouse move - animacja blob + tilt effect
     */
    function onMouseMove(ev) {
        // Track which connected groups we've handled
        var handledConnectedGroups = new Set();

        // Process all magic holders
        document.querySelectorAll('.magic-holder').forEach(function(holder) {
            var parentElement = holder.parentElement;
            var effectMode = getEffectMode(parentElement);

            // Skip mouse tracking for animated_border and beam modes
            if (effectMode === 'animated_border' || effectMode === 'beam') {
                return;
            }

            // Handle CONIC GLOW mode
            if (effectMode === 'conic_glow') {
                // Check if always visible
                var alwaysVisible = isConicAlwaysVisible(parentElement);
                // Check if glow should persist when mouse leaves
                var glowPersists = isConicGlowPersists(parentElement);

                // Check if connected mode
                if (isConnectedGlowMode(parentElement)) {
                    var group = getConnectedGlowGroup(parentElement);
                    if (group && !handledConnectedGroups.has(group.parent)) {
                        handledConnectedGroups.add(group.parent);
                        handleConnectedConicGlow(ev, group);
                    }
                    return;
                }

                // Separated mode - check if mouse is over element (with proximity)
                var isMouseOver = isWithinProximity(ev.clientX, ev.clientY, parentElement);

                if (isMouseOver || alwaysVisible) {
                    handleConicGlow(ev, parentElement, holder, false, 1);
                    holder._isConicHovering = true;
                } else if (holder._isConicHovering && !alwaysVisible) {
                    // Mouse left - hide or persist
                    holder._isConicHovering = false;
                    hideConicGlow(holder, glowPersists);
                }
                return;
            }

            // Handle SPOTLIGHT mode
            var blob = holder.querySelector('.blob');
            var fakeblob = holder.querySelector('.fakeblob');
            if (!blob || !fakeblob) return;

            // Check if this is connected glow mode
            if (isConnectedGlowMode(parentElement)) {
                var group = getConnectedGlowGroup(parentElement);
                if (group && !handledConnectedGroups.has(group.parent)) {
                    handledConnectedGroups.add(group.parent);
                    handleConnectedGlow(ev, group);
                }
                return; // Skip normal processing for connected mode
            }

            // Check if this is always-on mode
            var isAlwaysOn = holder.classList.contains('spotlight-always-on');

            // Check if mouse is over this element
            var parentRect = parentElement.getBoundingClientRect();
            var isMouseOver = ev.clientX >= parentRect.left && ev.clientX <= parentRect.right &&
                              ev.clientY >= parentRect.top && ev.clientY <= parentRect.bottom;

            if (isMouseOver) {
                // Mouse is over - follow cursor
                var rec = fakeblob.getBoundingClientRect();
                blob.animate([{
                    transform: 'translate(' +
                        (ev.clientX - rec.left - rec.width/2) + 'px,' +
                        (ev.clientY - rec.top - rec.height/2) + 'px)'
                }], { duration: 300, fill: 'forwards' });

                blob.style.opacity = '1';
                blob._isHovering = true;
            } else if (!isMouseOver && blob._isHovering) {
                // Mouse left the element
                blob._isHovering = false;

                if (isAlwaysOn && blob._defaultPosition) {
                    // Always-on mode: return to default position
                    blob.animate([{
                        transform: 'translate(' + blob._defaultPosition.x + 'px, ' + blob._defaultPosition.y + 'px)'
                    }], { duration: 300, fill: 'forwards' });
                    // Keep blob visible
                } else if (!isAlwaysOn) {
                    // Normal mode: hide blob
                    blob.style.opacity = '0';
                }
            }
        });

        // Tilt effect
        document.querySelectorAll('[data-magic-tilt="true"]').forEach(function(card) {
            var rect = card.getBoundingClientRect();
            var x = ev.clientX - rect.left;
            var y = ev.clientY - rect.top;

            // Check if mouse is over this card
            if (x < 0 || x > rect.width || y < 0 || y > rect.height) {
                return;
            }

            var centerX = rect.width / 2;
            var centerY = rect.height / 2;
            var intensity = parseFloat(getComputedStyle(card).getPropertyValue('--magic-card-tilt-intensity')) || 10;

            var rotateX = ((y - centerY) / centerY) * -intensity;
            var rotateY = ((x - centerX) / centerX) * intensity;

            card.style.transform = 'perspective(1000px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
        });
    }

    /**
     * Mouse leave - reset tilt
     */
    function setupTiltReset() {
        document.querySelectorAll('[data-magic-tilt="true"]').forEach(function(card) {
            if (card.dataset.tiltSetup) return;
            card.dataset.tiltSetup = 'true';

            card.style.transition = 'transform 0.3s ease-out';

            card.addEventListener('mouseleave', function() {
                card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
            });
        });
    }

    // Init
    function init() {
        processAllCards();
        setupTiltReset();
        updateAllEffectModes();
    }

    // Export for Elementor editor
    window.magicCardInit = init;
    window.magicCardUpdateModes = updateAllEffectModes;
    window.magicCardUpdateSpotlightPositions = recalculateSpotlightPositions;

    // Mouse tracking
    window.addEventListener('mousemove', onMouseMove);

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // MutationObserver dla Elementor editor
    var observer = new MutationObserver(function(mutations) {
        var shouldProcess = false;
        var shouldUpdateModes = false;

        mutations.forEach(function(m) {
            // Check for added nodes (new elements)
            if (m.addedNodes && m.addedNodes.length > 0) {
                m.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (
                            node.classList.contains('magic-card-parent') ||
                            node.classList.contains('magic-card-enabled-yes') ||
                            node.classList.contains('has-magic-card') ||
                            node.querySelector('.magic-card-parent, .magic-card-enabled-yes, .has-magic-card')
                        )) {
                            shouldProcess = true;
                        }
                    }
                });
            }

            if (!m.target || !m.target.classList) return;

            // Check for class changes
            if (m.target.classList.contains('magic-card-parent') ||
                m.target.classList.contains('magic-card-enabled-yes') ||
                m.target.classList.contains('has-magic-card')) {
                shouldProcess = true;
            }

            // Check for style changes (Elementor sets CSS variables via style attribute or dynamic CSS)
            if (m.attributeName === 'style' &&
                (m.target.classList.contains('magic-card-parent') ||
                 m.target.classList.contains('magic-card-enabled-yes') ||
                 m.target.classList.contains('has-magic-card'))) {
                shouldUpdateModes = true;
            }
        });

        if (shouldProcess) {
            init();
        } else if (shouldUpdateModes) {
            updateAllEffectModes();
        }
    });

    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
    }

    // Additional observer for Elementor dynamic style tags
    var styleObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.target.tagName === 'STYLE' && m.target.textContent.includes('--magic-card')) {
                updateAllEffectModes();
            }
        });
    });

    // Observe head for style changes
    if (document.head) {
        styleObserver.observe(document.head, { childList: true, subtree: true, characterData: true });
    }

    // Elementor frontend
    if (typeof jQuery !== 'undefined') {
        jQuery(window).on('elementor/frontend/init', function() {
            setTimeout(init, 500);
        });
    }

})();
