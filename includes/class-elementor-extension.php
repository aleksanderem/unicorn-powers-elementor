<?php
/**
 * Magic Card - Elementor Extension
 *
 * Dodaje kontrolki Magic Card do kontenerów i widżetów Elementora.
 * Wzorowane na uicore-animate - efekt dostępny w ustawieniach wizualnych.
 */

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;
use Elementor\Group_Control_Background;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Magic_Card_Elementor_Extension {

    public function __construct() {
        // Dodaj sekcję kontrolek do kontenerów
        add_action( 'elementor/element/container/section_effects/after_section_end', array( $this, 'add_magic_card_section' ), 10, 2 );

        // Dodaj kontrolki do sekcji (dla starszych szablonów)
        add_action( 'elementor/element/section/section_effects/after_section_end', array( $this, 'add_magic_card_section' ), 10, 2 );

        // Renderuj atrybuty przed elementem
        add_action( 'elementor/frontend/container/before_render', array( $this, 'before_render' ) );
        add_action( 'elementor/frontend/section/before_render', array( $this, 'before_render' ) );

        // Enqueue scripts gdy efekt jest aktywny
        add_action( 'elementor/frontend/container/before_render', array( $this, 'maybe_enqueue_scripts' ) );
        add_action( 'elementor/frontend/section/before_render', array( $this, 'maybe_enqueue_scripts' ) );

        // Enqueue w edytorze Elementora (preview iframe)
        add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
        add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_editor_scripts' ) );

        // Enqueue also in editor panel for live preview
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );

        // Add inline script for live preview in editor
        add_action( 'elementor/preview/enqueue_scripts', array( $this, 'add_editor_inline_script' ), 100 );

        // Add editor panel script for repeater live preview
        add_action( 'elementor/editor/footer', array( $this, 'add_editor_panel_script' ) );
    }

    /**
     * Add inline script to handle live preview updates in editor
     */
    public function add_editor_inline_script() {
        $script = "
        (function() {
            // Initialize when DOM is ready
            function initMagicCardEditor() {
                if (typeof window.magicCardInit === 'function') {
                    window.magicCardInit();
                }
            }

            // Run on load
            if (document.readyState === 'complete') {
                initMagicCardEditor();
            } else {
                window.addEventListener('load', initMagicCardEditor);
            }

            // Elementor frontend hooks (in preview iframe)
            if (typeof elementorFrontend !== 'undefined') {
                elementorFrontend.hooks.addAction('frontend/element_ready/container.default', function(\$element) {
                    setTimeout(initMagicCardEditor, 100);
                });
            }

            // jQuery ready as fallback
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function() {
                    setTimeout(initMagicCardEditor, 500);
                });

                // Listen for Elementor frontend init
                jQuery(window).on('elementor/frontend/init', function() {
                    setTimeout(initMagicCardEditor, 300);

                    // Hook into element ready
                    if (typeof elementorFrontend !== 'undefined') {
                        elementorFrontend.hooks.addAction('frontend/element_ready/global', function(\$element) {
                            setTimeout(initMagicCardEditor, 100);
                        });
                    }
                });
            }

            // Periodic check for editor changes (fallback for when other methods don't work)
            var lastStyleState = '';
            setInterval(function() {
                var elements = document.querySelectorAll('.magic-card-enabled-yes, .has-magic-card, .magic-card-parent');
                var currentState = '';
                elements.forEach(function(el) {
                    currentState += el.getAttribute('style') || '';
                    currentState += el.getAttribute('data-magic-card-mode') || '';
                    currentState += el.className || '';
                });
                if (currentState !== lastStyleState) {
                    lastStyleState = currentState;
                    if (typeof window.magicCardInit === 'function') {
                        window.magicCardInit();
                    }
                }
            }, 500);
        })();
        ";

        wp_add_inline_script( 'magic-card-script', $script );
    }

    /**
     * Add editor panel script to handle repeater changes for live preview
     */
    public function add_editor_panel_script() {
        ?>
        <style>
            /* Toggle button styles */
            #magic-card-outline-toggle {
                position: fixed;
                bottom: 20px;
                left: 20px;
                z-index: 99999;
                background: #5a922c;
                color: #fff;
                border: none;
                padding: 8px 14px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                transition: background 0.2s;
            }
            #magic-card-outline-toggle:hover {
                background: #4a7a24;
            }
            #magic-card-outline-toggle.active {
                background: #d35400;
            }
            #magic-card-outline-toggle.active:hover {
                background: #b34700;
            }
        </style>
        <script>
        (function waitForElementor() {
            if (typeof elementor === 'undefined') {
                setTimeout(waitForElementor, 100);
                return;
            }

            // Add toggle button for hiding container outlines
            function addOutlineToggle() {
                if (document.getElementById('magic-card-outline-toggle')) return;

                var btn = document.createElement('button');
                btn.id = 'magic-card-outline-toggle';
                btn.textContent = 'Hide Outlines';
                btn.title = 'Toggle container outlines visibility (Magic Card)';

                // Check saved state
                var hidden = localStorage.getItem('magic-card-hide-outlines') === 'true';
                if (hidden) {
                    btn.classList.add('active');
                    btn.textContent = 'Show Outlines';
                    updatePreviewFrame(true);
                }

                btn.addEventListener('click', function() {
                    var isHidden = btn.classList.toggle('active');
                    btn.textContent = isHidden ? 'Show Outlines' : 'Hide Outlines';
                    localStorage.setItem('magic-card-hide-outlines', isHidden);
                    updatePreviewFrame(isHidden);
                });

                document.body.appendChild(btn);
            }

            function updatePreviewFrame(hide) {
                var iframe = document.getElementById('elementor-preview-iframe');
                if (iframe && iframe.contentDocument && iframe.contentDocument.body) {
                    if (hide) {
                        iframe.contentDocument.body.classList.add('magic-card-hide-outlines');
                    } else {
                        iframe.contentDocument.body.classList.remove('magic-card-hide-outlines');
                    }
                }
            }

            // Add toggle button when editor is ready
            setTimeout(addOutlineToggle, 1000);

            // Re-apply state when preview reloads
            elementor.on('preview:loaded', function() {
                var hidden = localStorage.getItem('magic-card-hide-outlines') === 'true';
                if (hidden) {
                    setTimeout(function() { updatePreviewFrame(true); }, 500);
                }
            });

            // Build conic gradient from colors array
            function buildMultiGradient(colors) {
                if (!colors || colors.length < 2) {
                    return 'conic-gradient(from 0deg, rgba(14, 165, 233, 1) 0deg, rgba(168, 85, 247, 1) 180deg, rgba(14, 165, 233, 1) 360deg)';
                }
                var stops = [];
                var count = colors.length;
                for (var i = 0; i < count; i++) {
                    var angle = Math.round((i / count) * 360);
                    stops.push(colors[i] + ' ' + angle + 'deg');
                }
                stops.push(colors[0] + ' 360deg');
                return 'conic-gradient(from 0deg, ' + stops.join(', ') + ')';
            }

            // Build beam gradient
            function buildBeamGradient(color1, color2, beamWidth, fadeFront, fadeBack) {
                beamWidth = beamWidth || 45;
                fadeFront = fadeFront || 15;
                fadeBack = fadeBack || 15;
                color1 = color1 || 'rgba(14, 165, 233, 1)';
                color2 = color2 || 'rgba(168, 85, 247, 1)';

                return 'conic-gradient(from 0deg, ' +
                    'transparent 0deg, ' +
                    'transparent ' + fadeBack + 'deg, ' +
                    color1 + ' ' + (fadeBack + Math.round(beamWidth * 0.3)) + 'deg, ' +
                    color2 + ' ' + (fadeBack + Math.round(beamWidth * 0.7)) + 'deg, ' +
                    color1 + ' ' + (fadeBack + beamWidth) + 'deg, ' +
                    'transparent ' + (fadeBack + beamWidth + fadeFront) + 'deg)';
            }

            // Build conic glow gradient (Aceternity-style)
            function buildConicGlowGradient(colors) {
                if (!colors || colors.length < 2) {
                    return 'radial-gradient(circle, #dd7bbb 10%, #dd7bbb00 20%), radial-gradient(circle at 40% 40%, #d79f1e 5%, #d79f1e00 15%), radial-gradient(circle at 60% 60%, #5a922c 10%, #5a922c00 20%), radial-gradient(circle at 40% 60%, #4c7894 10%, #4c789400 20%), repeating-conic-gradient(from 0deg at 50% 50%, #dd7bbb 0%, #d79f1e 5%, #5a922c 10%, #4c7894 15%, #dd7bbb 20%)';
                }

                var gradientParts = [];
                var numColors = colors.length;
                var positions = [
                    ['50%', '50%'],
                    ['40%', '40%'],
                    ['60%', '60%'],
                    ['40%', '60%'],
                    ['60%', '40%']
                ];

                // Add radial gradients for each color
                for (var i = 0; i < Math.min(numColors, 5); i++) {
                    var color = colors[i];
                    var pos = positions[i];
                    var fadeSize = (i === 0) ? ['10%', '20%'] : ['5%', '15%'];
                    var transparent = color + '00';
                    gradientParts.push('radial-gradient(circle at ' + pos[0] + ' ' + pos[1] + ', ' + color + ' ' + fadeSize[0] + ', ' + transparent + ' ' + fadeSize[1] + ')');
                }

                // Add repeating conic gradient
                var conicStops = [];
                var repeatTimes = 5;
                var percentPerColor = 100 / repeatTimes / numColors;
                var currentPercent = 0;

                for (var j = 0; j <= numColors; j++) {
                    var c = colors[j % numColors];
                    conicStops.push(c + ' ' + currentPercent + '%');
                    currentPercent += percentPerColor;
                }

                gradientParts.push('repeating-conic-gradient(from 0deg at 50% 50%, ' + conicStops.join(', ') + ')');

                return gradientParts.join(', ');
            }

            // Update gradient in preview iframe
            function updatePreviewGradient(elementId, gradient, varName) {
                var previewFrame = document.querySelector('#elementor-preview-iframe');
                if (!previewFrame || !previewFrame.contentDocument) return;

                var previewDoc = previewFrame.contentDocument;
                var element = previewDoc.querySelector('[data-id="' + elementId + '"]');
                if (!element) return;

                // Set CSS variable on element
                element.style.setProperty(varName, gradient);

                // Set on all child containers for inheritance
                var children = element.querySelectorAll('[data-element_type="container"]');
                children.forEach(function(child) {
                    child.style.setProperty(varName, gradient);
                });

                // Also set directly on blobs for immediate effect
                var blobs = element.querySelectorAll('.magic-holder .blob');
                blobs.forEach(function(blob) {
                    blob.style.setProperty('background', gradient, 'important');
                });
            }

            // Get colors from repeater model
            function getColorsFromRepeater(repeaterValue) {
                var colors = [];
                if (repeaterValue && repeaterValue.models) {
                    repeaterValue.models.forEach(function(model) {
                        var color = model.get('color');
                        if (color) colors.push(color);
                    });
                } else if (Array.isArray(repeaterValue)) {
                    repeaterValue.forEach(function(item) {
                        if (item.color) colors.push(item.color);
                    });
                }
                return colors;
            }

            // Handle settings change for current element
            function handleSettingsChange(model) {
                if (!model) return;

                var settings = model.get('settings');
                if (!settings) return;

                var magicEnabled = settings.get('magic_card_enable');
                if (magicEnabled !== 'yes') return;

                var effectMode = settings.get('magic_card_effect_mode') || 'spotlight';
                var elementId = model.get('id');

                if (!effectMode || effectMode === 'spotlight') {
                    // Spotlight mode - update blob color directly
                    var bgType = settings.get('magic_card_glow_background') || 'classic';
                    var color1 = settings.get('magic_card_glow_color') || 'rgba(14, 165, 233, 0.9)';
                    var background;

                    if (bgType === 'gradient') {
                        var color2 = settings.get('magic_card_glow_color_b') || 'rgba(168, 85, 247, 1)';
                        var angleObj = settings.get('magic_card_glow_gradient_angle');
                        var angle = angleObj && angleObj.size !== undefined ? angleObj.size : 180;
                        var loc1Obj = settings.get('magic_card_glow_color_stop');
                        var loc1 = loc1Obj && loc1Obj.size !== undefined ? loc1Obj.size : 0;
                        var loc2Obj = settings.get('magic_card_glow_color_b_stop');
                        var loc2 = loc2Obj && loc2Obj.size !== undefined ? loc2Obj.size : 100;
                        var gradientType = settings.get('magic_card_glow_gradient_type') || 'linear';

                        if (gradientType === 'radial') {
                            var position = settings.get('magic_card_glow_gradient_position') || 'center center';
                            background = 'radial-gradient(at ' + position + ', ' + color1 + ' ' + loc1 + '%, ' + color2 + ' ' + loc2 + '%)';
                        } else {
                            background = 'linear-gradient(' + angle + 'deg, ' + color1 + ' ' + loc1 + '%, ' + color2 + ' ' + loc2 + '%)';
                        }
                    } else {
                        background = color1;
                    }

                    // Update blob directly
                    updateSpotlightBlob(elementId, background);

                    // Handle always-on spotlight mode
                    var alwaysOn = settings.get('magic_card_spotlight_always_on') === 'yes';
                    var posXObj = settings.get('magic_card_spotlight_pos_x');
                    var posYObj = settings.get('magic_card_spotlight_pos_y');
                    var posX = posXObj && posXObj.size !== undefined ? posXObj.size : 50;
                    var posY = posYObj && posYObj.size !== undefined ? posYObj.size : 50;

                    updateSpotlightAlwaysOn(elementId, alwaysOn, posX, posY);
                } else if (effectMode === 'animated_border') {
                    var gradientColors = settings.get('magic_card_gradient_colors');
                    var colors = getColorsFromRepeater(gradientColors);
                    if (colors.length >= 2) {
                        var gradient = buildMultiGradient(colors);
                        updatePreviewGradient(elementId, gradient, '--magic-card-multi-gradient');
                    }
                } else if (effectMode === 'beam') {
                    var color1 = settings.get('magic_card_color1') || 'rgba(14, 165, 233, 1)';
                    var color2 = settings.get('magic_card_color2') || 'rgba(168, 85, 247, 1)';
                    var beamWidth = settings.get('magic_card_beam_width');
                    var fadeFront = settings.get('magic_card_beam_fade_front');
                    var fadeBack = settings.get('magic_card_beam_fade_back');

                    beamWidth = beamWidth ? beamWidth.size : 45;
                    fadeFront = fadeFront ? fadeFront.size : 15;
                    fadeBack = fadeBack ? fadeBack.size : 15;

                    var gradient = buildBeamGradient(color1, color2, beamWidth, fadeFront, fadeBack);
                    updatePreviewGradient(elementId, gradient, '--magic-card-beam-gradient');
                } else if (effectMode === 'conic_glow') {
                    var conicColors = settings.get('magic_card_conic_colors');
                    var colors = getColorsFromRepeater(conicColors);
                    if (colors.length >= 2) {
                        var gradient = buildConicGlowGradient(colors);
                        updatePreviewGradient(elementId, gradient, '--magic-card-conic-gradient');
                    }
                }
            }

            // Update spotlight blob background directly
            function updateSpotlightBlob(elementId, background) {
                var previewFrame = document.querySelector('#elementor-preview-iframe');
                if (!previewFrame || !previewFrame.contentDocument) return;

                var previewDoc = previewFrame.contentDocument;
                var element = previewDoc.querySelector('[data-id=\"' + elementId + '\"]');
                if (!element) return;

                // Find blob on this element or its children
                var blobs = element.querySelectorAll('.magic-holder .blob');
                blobs.forEach(function(blob) {
                    blob.style.setProperty('background', background, 'important');
                });
            }

            // Update spotlight always-on mode in editor preview
            function updateSpotlightAlwaysOn(elementId, alwaysOn, posX, posY) {
                var previewFrame = document.querySelector('#elementor-preview-iframe');
                if (!previewFrame || !previewFrame.contentDocument) return;

                var previewDoc = previewFrame.contentDocument;
                var element = previewDoc.querySelector('[data-id=\"' + elementId + '\"]');
                if (!element) return;

                // Set data attribute for always-on
                if (alwaysOn) {
                    element.setAttribute('data-magic-spotlight-always-on', 'true');
                } else {
                    element.removeAttribute('data-magic-spotlight-always-on');
                }

                // Set CSS variables for position
                element.style.setProperty('--magic-card-spotlight-x', posX + '%');
                element.style.setProperty('--magic-card-spotlight-y', posY + '%');

                // Find holders (on element or children)
                var holders = element.querySelectorAll('.magic-holder');
                holders.forEach(function(holder) {
                    var blob = holder.querySelector('.blob');
                    if (!blob) return;

                    var parentEl = holder.parentElement;
                    var rect = parentEl.getBoundingClientRect();
                    var blobSizeStr = getComputedStyle(parentEl).getPropertyValue('--magic-card-blob-size');
                    var blobSize = parseInt(blobSizeStr) || 250;

                    if (alwaysOn) {
                        holder.classList.add('spotlight-always-on');

                        // Calculate pixel position from percentage
                        var pixelX = (rect.width * posX / 100) - (blobSize / 2);
                        var pixelY = (rect.height * posY / 100) - (blobSize / 2);

                        blob.style.transform = 'translate(' + pixelX + 'px, ' + pixelY + 'px)';
                        blob.style.opacity = '1';
                        blob._defaultPosition = { x: pixelX, y: pixelY };
                    } else {
                        holder.classList.remove('spotlight-always-on');
                        blob.style.opacity = '0';
                    }
                });
            }

            // Listen for any editor changes
            elementor.channels.editor.on('change', function(controlView) {
                if (!controlView || !controlView.container) return;
                handleSettingsChange(controlView.container.model);
            });

            // Also hook into element settings model changes
            elementor.hooks.addAction('panel/open_editor/container', function(panel, model) {
                if (!model || !model.get('settings')) return;

                var settings = model.get('settings');

                // Listen for changes on this specific model
                settings.on('change', function() {
                    setTimeout(function() {
                        handleSettingsChange(model);
                    }, 100);
                });
            });

            // Initialize blobs with gradients from Elementor model
            function initBlobGradients() {
                var previewFrame = document.querySelector('#elementor-preview-iframe');
                if (!previewFrame || !previewFrame.contentDocument) return;
                var doc = previewFrame.contentDocument;

                doc.querySelectorAll('.magic-holder').forEach(function(holder) {
                    var blob = holder.querySelector('.blob');
                    if (!blob) return;

                    var cardElement = holder.closest('[data-id]');
                    if (!cardElement) return;

                    // Find settings - either on this element or on parent with magic-card-enabled-yes
                    var settings = null;
                    var effectMode = null;

                    // First try: element itself
                    var elementId = cardElement.getAttribute('data-id');
                    var container = elementor.getContainer(elementId);
                    if (container && container.model) {
                        var s = container.model.get('settings');
                        if (s && s.get('magic_card_enable') === 'yes') {
                            settings = s;
                            effectMode = s.get('magic_card_effect_mode');
                        }
                    }

                    // Second try: find parent with magic-card-parent or magic-card-enabled-yes class
                    if (!settings) {
                        var parentEl = cardElement.closest('.magic-card-parent, .magic-card-enabled-yes');
                        if (parentEl) {
                            var parentId = parentEl.getAttribute('data-id');
                            var parentContainer = elementor.getContainer(parentId);
                            if (parentContainer && parentContainer.model) {
                                var ps = parentContainer.model.get('settings');
                                if (ps && ps.get('magic_card_enable') === 'yes') {
                                    settings = ps;
                                    effectMode = ps.get('magic_card_effect_mode');
                                }
                            }
                        }
                    }

                    if (!settings) return;

                    // Handle spotlight mode (default) - uses glow color settings
                    if (!effectMode || effectMode === 'spotlight') {
                        var bgType = settings.get('magic_card_glow_background') || 'classic';
                        var color1 = settings.get('magic_card_glow_color') || 'rgba(14, 165, 233, 0.9)';

                        if (bgType === 'gradient') {
                            var color2 = settings.get('magic_card_glow_color_b') || 'rgba(168, 85, 247, 1)';
                            var angleObj = settings.get('magic_card_glow_gradient_angle');
                            var angle = angleObj && angleObj.size !== undefined ? angleObj.size : 180;
                            var loc1Obj = settings.get('magic_card_glow_color_stop');
                            var loc1 = loc1Obj && loc1Obj.size !== undefined ? loc1Obj.size : 0;
                            var loc2Obj = settings.get('magic_card_glow_color_b_stop');
                            var loc2 = loc2Obj && loc2Obj.size !== undefined ? loc2Obj.size : 100;
                            var gradientType = settings.get('magic_card_glow_gradient_type') || 'linear';

                            var gradient;
                            if (gradientType === 'radial') {
                                var position = settings.get('magic_card_glow_gradient_position') || 'center center';
                                gradient = 'radial-gradient(at ' + position + ', ' + color1 + ' ' + loc1 + '%, ' + color2 + ' ' + loc2 + '%)';
                            } else {
                                gradient = 'linear-gradient(' + angle + 'deg, ' + color1 + ' ' + loc1 + '%, ' + color2 + ' ' + loc2 + '%)';
                            }
                            blob.style.setProperty('background', gradient, 'important');
                        } else {
                            // Classic - single color
                            blob.style.setProperty('background', color1, 'important');
                        }
                    } else if (effectMode === 'animated_border') {
                        var gradientColors = settings.get('magic_card_gradient_colors');
                        var colors = getColorsFromRepeater(gradientColors);
                        if (colors.length >= 2) {
                            var gradient = buildMultiGradient(colors);
                            blob.style.setProperty('background', gradient, 'important');
                        }
                    } else if (effectMode === 'beam') {
                        var color1 = settings.get('magic_card_color1') || 'rgba(14, 165, 233, 1)';
                        var color2 = settings.get('magic_card_color2') || 'rgba(168, 85, 247, 1)';
                        var beamWidth = settings.get('magic_card_beam_width');
                        var fadeFront = settings.get('magic_card_beam_fade_front');
                        var fadeBack = settings.get('magic_card_beam_fade_back');
                        beamWidth = beamWidth ? beamWidth.size : 45;
                        fadeFront = fadeFront ? fadeFront.size : 15;
                        fadeBack = fadeBack ? fadeBack.size : 15;
                        var gradient = buildBeamGradient(color1, color2, beamWidth, fadeFront, fadeBack);
                        blob.style.setProperty('background', gradient, 'important');
                    }
                });
            }

            // Find containers at specified nesting level (mirrors JS function)
            function findContainersAtLevel(parent, level, doc) {
                var inner = parent.querySelector(':scope > .e-con-inner');
                var searchIn = inner || parent;

                var currentLevel = Array.from(searchIn.querySelectorAll(':scope > [data-element_type="container"]'));

                if (level === 1) {
                    return currentLevel;
                }

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

            // Add or update editor-only styles for target highlighting
            function updateEditorHighlightStyles(doc) {
                var styleId = 'magic-card-editor-highlight';
                var existingStyle = doc.getElementById(styleId);
                if (!existingStyle) {
                    existingStyle = doc.createElement('style');
                    existingStyle.id = styleId;
                    doc.head.appendChild(existingStyle);
                }
                existingStyle.textContent = `
                    .magic-card-target-highlight {
                        position: relative;
                    }
                    .magic-card-target-highlight::before {
                        content: '';
                        position: absolute;
                        inset: 0;
                        background: repeating-linear-gradient(
                            -45deg,
                            rgba(93, 186, 216, 0.08),
                            rgba(93, 186, 216, 0.08) 4px,
                            transparent 4px,
                            transparent 8px
                        );
                        border: 2px dashed rgba(93, 186, 216, 0.5);
                        border-radius: inherit;
                        pointer-events: none;
                        z-index: 9999;
                    }
                    .magic-card-target-highlight::after {
                        content: 'Magic Target';
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        background: rgba(93, 186, 216, 0.9);
                        color: #000;
                        font-size: 9px;
                        font-weight: 600;
                        padding: 2px 6px;
                        border-radius: 3px;
                        pointer-events: none;
                        z-index: 10000;
                    }
                `;
            }

            // Sync data-magic-card-mode attribute from Elementor settings to DOM
            // (PHP before_render doesn't run in live preview, so we need to do this in JS)
            function syncMagicCardModeAttributes() {
                var previewFrame = document.querySelector('#elementor-preview-iframe');
                if (!previewFrame || !previewFrame.contentDocument) return;
                var doc = previewFrame.contentDocument;

                // Ensure highlight styles exist
                updateEditorHighlightStyles(doc);

                // Clear all previous highlights
                doc.querySelectorAll('.magic-card-target-highlight').forEach(function(el) {
                    el.classList.remove('magic-card-target-highlight');
                });

                doc.querySelectorAll('.magic-card-enabled-yes').forEach(function(el) {
                    var elementId = el.getAttribute('data-id');
                    if (!elementId) return;

                    var container = elementor.getContainer(elementId);
                    if (!container || !container.model) return;

                    var settings = container.model.get('settings');
                    if (!settings || settings.get('magic_card_enable') !== 'yes') return;

                    var applyMode = settings.get('magic_card_apply_mode') || 'self';
                    var targetLevel = parseInt(settings.get('magic_card_target_level')) || 1;
                    var glowBehavior = settings.get('magic_card_glow_behavior') || 'separated';
                    var excludeClass = settings.get('magic_card_exclude_class') || '';
                    var showTargetHelper = settings.get('magic_card_show_target_helper') === 'yes';

                    // Check if any relevant setting changed
                    var currentMode = el.getAttribute('data-magic-card-mode');
                    var currentLevel = el.getAttribute('data-magic-target-level');
                    var currentBehavior = el.getAttribute('data-magic-glow-behavior');

                    var needsReinit = currentMode !== applyMode ||
                                      currentLevel !== String(targetLevel) ||
                                      currentBehavior !== glowBehavior;

                    // Always update attributes
                    el.setAttribute('data-magic-card-mode', applyMode);
                    el.setAttribute('data-magic-target-level', targetLevel);
                    el.setAttribute('data-magic-glow-behavior', glowBehavior);

                    if (excludeClass) {
                        el.setAttribute('data-magic-card-exclude', excludeClass.trim());
                    } else {
                        el.removeAttribute('data-magic-card-exclude');
                    }

                    // Toggle classes based on mode
                    if (applyMode === 'children') {
                        el.classList.add('magic-card-parent');
                        el.classList.remove('has-magic-card');

                        // Highlight target containers (if helper enabled)
                        if (showTargetHelper) {
                            var targetContainers = findContainersAtLevel(el, targetLevel, doc);
                            targetContainers.forEach(function(target) {
                                if (!excludeClass || !target.classList.contains(excludeClass.trim())) {
                                    target.classList.add('magic-card-target-highlight');
                                }
                            });
                        }
                    } else {
                        el.classList.add('has-magic-card');
                        el.classList.remove('magic-card-parent');
                        // Highlight self (if helper enabled)
                        if (showTargetHelper) {
                            el.classList.add('magic-card-target-highlight');
                        }
                    }

                    if (needsReinit) {
                        // Remove existing magic-holder to force reinit
                        var existingHolder = el.querySelector(':scope > .magic-holder');
                        var existingBlur = el.querySelector(':scope > .magic-inner-blur');
                        if (existingHolder) existingHolder.remove();
                        if (existingBlur) existingBlur.remove();

                        // Remove from all nested containers (any level)
                        el.querySelectorAll('[data-element_type="container"]').forEach(function(child) {
                            var childHolder = child.querySelector(':scope > .magic-holder');
                            var childBlur = child.querySelector(':scope > .magic-inner-blur');
                            if (childHolder) childHolder.remove();
                            if (childBlur) childBlur.remove();
                        });

                        // Re-init magic cards in preview iframe
                        if (previewFrame.contentWindow && previewFrame.contentWindow.magicCardInit) {
                            previewFrame.contentWindow.magicCardInit();
                        }
                    }
                });
            }

            // Run periodically - simpler and more reliable
            setInterval(initBlobGradients, 1000);
            setInterval(syncMagicCardModeAttributes, 300);

            // Periodic check as fallback (for repeater items and active panel)
            var lastState = '';
            setInterval(function() {
                try {
                    var activeContainer = elementor.getPanelView().getCurrentPageView();
                    if (!activeContainer || !activeContainer.model) return;

                    var settings = activeContainer.model.get('settings');
                    if (!settings) return;

                    var effectMode = settings.get('magic_card_effect_mode');
                    if (effectMode !== 'animated_border' && effectMode !== 'beam') return;

                    var currentState = '';
                    if (effectMode === 'animated_border') {
                        var colors = getColorsFromRepeater(settings.get('magic_card_gradient_colors'));
                        currentState = colors.join(',');
                    } else {
                        currentState = [
                            settings.get('magic_card_color1'),
                            settings.get('magic_card_color2'),
                            JSON.stringify(settings.get('magic_card_beam_width')),
                            JSON.stringify(settings.get('magic_card_beam_fade_front')),
                            JSON.stringify(settings.get('magic_card_beam_fade_back'))
                        ].join(',');
                    }

                    if (currentState !== lastState) {
                        lastState = currentState;
                        handleSettingsChange(activeContainer.model);
                    }
                } catch(e) {}
            }, 500);

            console.log('Magic Card editor live preview initialized');
        })();
        </script>
        <?php
    }

    /**
     * Dodaj sekcję kontrolek Magic Card
     */
    public function add_magic_card_section( Controls_Stack $element, $section_id ) {
        $element->start_controls_section(
            'magic_card_section',
            array(
                'label' => '<span title="Powered by the Unicorns" style="font-size:10px;font-weight:500;background:#5dbad8;color:black;padding:2px 5px;border-radius:3px;margin-right:4px;">Magic</span> Magic Card Effect ✨',
                'tab'   => Controls_Manager::TAB_ADVANCED,
            )
        );

        // Główny przełącznik włączający efekt
        $element->add_control(
            'magic_card_enable',
            array(
                'label'        => __( 'Enable Magic Card', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( 'Spotlight gradient effect following mouse cursor.', 'magic-card' ),
                'frontend_available' => true,
                'render_type'  => 'template',
                'prefix_class' => 'magic-card-enabled-',
            )
        );

        // Tryb efektu (Spotlight, Animated Border, Beam, Conic Glow)
        $element->add_control(
            'magic_card_effect_mode',
            array(
                'label'   => __( 'Effect Mode', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'spotlight',
                'options' => array(
                    'spotlight'       => __( 'Spotlight (follows cursor)', 'magic-card' ),
                    'conic_glow'      => __( 'Conic Glow (masked rotating gradient)', 'magic-card' ),
                    'animated_border' => __( 'Animated Border (rotating gradient)', 'magic-card' ),
                    'beam'            => __( 'Beam (rotating light beam)', 'magic-card' ),
                ),
                'frontend_available' => true,
                'render_type'  => 'template',
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-effect-mode: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Spotlight Always On - glow visible always, movement only on hover
        $element->add_control(
            'magic_card_spotlight_always_on',
            array(
                'label'        => __( 'Always Visible', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( 'Keep spotlight glow visible at all times. Movement still follows cursor on hover.', 'magic-card' ),
                'frontend_available' => true,
                'render_type'  => 'ui',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'spotlight',
                ),
            )
        );

        // Spotlight default position X (percentage)
        $element->add_control(
            'magic_card_spotlight_pos_x',
            array(
                'label'      => __( 'Default Position X', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( '%' ),
                'range'      => array(
                    '%' => array(
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => '%',
                    'size' => 50,
                ),
                'description' => __( 'Horizontal position of spotlight when idle (0% = left, 100% = right).', 'magic-card' ),
                'frontend_available' => true,
                'render_type' => 'ui',
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-spotlight-x: {{SIZE}}%;',
                ),
                'condition' => array(
                    'magic_card_enable'              => 'yes',
                    'magic_card_effect_mode'         => 'spotlight',
                    'magic_card_spotlight_always_on' => 'yes',
                ),
            )
        );

        // Spotlight default position Y (percentage)
        $element->add_control(
            'magic_card_spotlight_pos_y',
            array(
                'label'      => __( 'Default Position Y', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( '%' ),
                'range'      => array(
                    '%' => array(
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => '%',
                    'size' => 50,
                ),
                'description' => __( 'Vertical position of spotlight when idle (0% = top, 100% = bottom).', 'magic-card' ),
                'frontend_available' => true,
                'render_type' => 'ui',
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-spotlight-y: {{SIZE}}%;',
                ),
                'condition' => array(
                    'magic_card_enable'              => 'yes',
                    'magic_card_effect_mode'         => 'spotlight',
                    'magic_card_spotlight_always_on' => 'yes',
                ),
            )
        );

        // Tryb aplikowania efektu
        $element->add_control(
            'magic_card_apply_mode',
            array(
                'label'   => __( 'Apply To', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'self',
                'options' => array(
                    'self'     => __( 'This Container Only', 'magic-card' ),
                    'children' => __( 'All Child Containers', 'magic-card' ),
                ),
                'description'  => __( 'Apply effect to this container or all nested containers inside.', 'magic-card' ),
                'frontend_available' => true,
                'render_type'  => 'template',
                'condition' => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Glow behavior mode (for children mode with spotlight or conic_glow)
        $element->add_control(
            'magic_card_glow_behavior',
            array(
                'label'   => __( 'Glow Behavior', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'separated',
                'options' => array(
                    'separated' => __( 'Separated (each card independent)', 'magic-card' ),
                    'connected' => __( 'Connected (all cards react to cursor)', 'magic-card' ),
                ),
                'description'  => __( 'Separated: glow only on hovered card. Connected: all cards glow based on cursor proximity.', 'magic-card' ),
                'frontend_available' => true,
                'render_type'  => 'template',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_apply_mode'  => 'children',
                    'magic_card_effect_mode' => array( 'spotlight', 'conic_glow' ),
                ),
            )
        );

        // Glow radius for connected mode
        $element->add_control(
            'magic_card_glow_radius',
            array(
                'label'      => __( 'Glow Reach', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 100,
                        'max'  => 800,
                        'step' => 50,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 400,
                ),
                'description' => __( 'How far the cursor influence extends (in pixels).', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-glow-radius: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable'        => 'yes',
                    'magic_card_apply_mode'    => 'children',
                    'magic_card_effect_mode'   => array( 'spotlight', 'conic_glow' ),
                    'magic_card_glow_behavior' => 'connected',
                ),
            )
        );

        // Target nesting level for children mode
        $element->add_control(
            'magic_card_target_level',
            array(
                'label'   => __( 'Target Level', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '1',
                'options' => array(
                    '1' => __( '1st level (direct children)', 'magic-card' ),
                    '2' => __( '2nd level (grandchildren)', 'magic-card' ),
                    '3' => __( '3rd level', 'magic-card' ),
                ),
                'description'  => __( 'Which nesting level of containers to apply the effect to. Use 2nd level when your direct children are row wrappers.', 'magic-card' ),
                'frontend_available' => true,
                'render_type'  => 'template',
                'condition' => array(
                    'magic_card_enable'     => 'yes',
                    'magic_card_apply_mode' => 'children',
                ),
            )
        );

        // Show target helper in editor
        $element->add_control(
            'magic_card_show_target_helper',
            array(
                'label'        => __( 'Show Target Helper', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'magic-card' ),
                'label_off'    => __( 'Hide', 'magic-card' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Show striped overlay on containers that will receive the effect (editor only).', 'magic-card' ),
                'frontend_available' => true,
                'condition' => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Klasa wykluczająca
        $element->add_control(
            'magic_card_exclude_class',
            array(
                'label'       => __( 'Exclude Class', 'magic-card' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => 'no-magic-card',
                'description' => __( 'Containers with this CSS class will be excluded from the effect.', 'magic-card' ),
                'frontend_available' => true,
                'condition' => array(
                    'magic_card_enable'     => 'yes',
                    'magic_card_apply_mode' => 'children',
                ),
            )
        );

        // Glow Background - Group_Control dla gradientu, selector na blob
        $element->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'magic_card_glow',
                'label'    => __( 'Glow Color', 'magic-card' ),
                'types'    => array( 'classic', 'gradient' ),
                'exclude'  => array( 'image' ),
                'selector' => '{{WRAPPER}} .magic-holder .blob',
                'fields_options' => array(
                    'background' => array(
                        'default' => 'classic',
                        'label'   => __( 'Glow Type', 'magic-card' ),
                    ),
                    'color' => array(
                        'default' => 'rgba(14, 165, 233, 0.9)',
                    ),
                ),
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'spotlight',
                ),
            )
        );

        // Wielkość blob
        $element->add_control(
            'magic_card_gradient_size',
            array(
                'label'      => __( 'Glow Size', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 100,
                        'max'  => 600,
                        'step' => 10,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 250,
                ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-blob-size: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Blur amount
        $element->add_control(
            'magic_card_blur',
            array(
                'label'      => __( 'Blur Amount', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 5,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 40,
                ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-blur: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // === Conic Glow Settings ===
        $element->add_control(
            'magic_card_conic_heading',
            array(
                'label'     => __( 'Conic Glow Settings', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Variant (color scheme)
        $element->add_control(
            'magic_card_conic_variant',
            array(
                'label'   => __( 'Color Variant', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => array(
                    'default' => __( 'Aceternity (pink/yellow/green/blue)', 'magic-card' ),
                    'white'   => __( 'Black & White', 'magic-card' ),
                    'custom'  => __( 'Custom colors', 'magic-card' ),
                ),
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Glow Colors (repeater) - only for custom variant
        $element->add_control(
            'magic_card_conic_colors',
            array(
                'label'       => __( 'Gradient Colors', 'magic-card' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => array(
                    array(
                        'name'    => 'color',
                        'label'   => __( 'Color', 'magic-card' ),
                        'type'    => Controls_Manager::COLOR,
                        'default' => '#dd7bbb',
                    ),
                ),
                'default'     => array(
                    array( 'color' => '#dd7bbb' ),
                    array( 'color' => '#d79f1e' ),
                    array( 'color' => '#5a922c' ),
                    array( 'color' => '#4c7894' ),
                ),
                'title_field' => '<span style="background:{{{ color }}};width:20px;height:20px;display:inline-block;border-radius:3px;margin-right:8px;vertical-align:middle;border:1px solid rgba(0,0,0,0.2);"></span> {{{ color }}}',
                'condition'   => array(
                    'magic_card_enable'        => 'yes',
                    'magic_card_effect_mode'   => 'conic_glow',
                    'magic_card_conic_variant' => 'custom',
                ),
            )
        );

        // Conic Border Width
        $element->add_control(
            'magic_card_conic_border_width',
            array(
                'label'      => __( 'Border Width', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 10,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 2,
                ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-border-width: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Spread (spotlight width in degrees)
        $element->add_control(
            'magic_card_conic_spread',
            array(
                'label'      => __( 'Spotlight Spread', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 5,
                        'max'  => 180,
                        'step' => 5,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 60,
                ),
                'description' => __( 'Angular spread of the glowing effect in degrees.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-spread: {{SIZE}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Blur
        $element->add_control(
            'magic_card_conic_blur',
            array(
                'label'      => __( 'Glow Blur', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 40,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 4,
                ),
                'description' => __( 'Blur applied to the glowing effect.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-blur: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Inactive Zone
        $element->add_control(
            'magic_card_conic_inactive_zone',
            array(
                'label'      => __( 'Inactive Zone', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 1,
                        'step' => 0.05,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 0.6,
                ),
                'description' => __( 'Radius multiplier (0-1) for center zone where effect is disabled.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-inactive-zone: {{SIZE}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Proximity
        $element->add_control(
            'magic_card_conic_proximity',
            array(
                'label'      => __( 'Proximity', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 200,
                        'step' => 10,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 64,
                ),
                'description' => __( 'Distance beyond element bounds where effect remains active.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-proximity: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Movement Duration
        $element->add_control(
            'magic_card_conic_movement_duration',
            array(
                'label'      => __( 'Movement Duration', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 5,
                        'step' => 0.1,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 0.3,
                ),
                'description' => __( 'Duration of the glow movement animation in seconds.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-conic-duration: {{SIZE}}s;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Always Visible (glow option)
        $element->add_control(
            'magic_card_conic_always_visible',
            array(
                'label'        => __( 'Always Visible', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( 'Force effect to be visible regardless of hover state.', 'magic-card' ),
                'condition'    => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Glow Persists (stays when mouse leaves proximity)
        $element->add_control(
            'magic_card_conic_glow_persists',
            array(
                'label'        => __( 'Glow Persists', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( 'Glow stays visible when mouse leaves proximity (does not fade out).', 'magic-card' ),
                'condition'    => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Conic Idle Border Opacity (subtle border visible on all cards)
        $element->add_control(
            'magic_card_conic_idle_opacity',
            array(
                'label'       => __( 'Idle Border Opacity', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'description' => __( 'Subtle border visible on all cards even when not hovered.', 'magic-card' ),
                'range'       => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 0.5,
                        'step' => 0.01,
                    ),
                ),
                'default'     => array(
                    'size' => 0.15,
                    'unit' => 'px',
                ),
                'selectors'   => array(
                    '{{WRAPPER}}' => '--magic-card-conic-idle-opacity: {{SIZE}};',
                ),
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // === Inner Glow Settings (for Conic Glow mode) ===
        $element->add_control(
            'magic_card_inner_glow_heading',
            array(
                'label'     => __( 'Inner Glow', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Inner Glow Size
        $element->add_control(
            'magic_card_inner_glow_size',
            array(
                'label'       => __( 'Inner Glow Size', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'description' => __( 'Size of the radial glow that follows cursor inside the card.', 'magic-card' ),
                'range'       => array(
                    'px' => array(
                        'min'  => 50,
                        'max'  => 400,
                        'step' => 10,
                    ),
                ),
                'default'     => array(
                    'size' => 150,
                    'unit' => 'px',
                ),
                'selectors'   => array(
                    '{{WRAPPER}}' => '--magic-card-inner-glow-size: {{SIZE}}{{UNIT}};',
                ),
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Inner Glow Colors Repeater
        $inner_glow_repeater = new Repeater();

        $inner_glow_repeater->add_control(
            'color',
            array(
                'label'   => __( 'Color', 'magic-card' ),
                'type'    => Controls_Manager::COLOR,
                'default' => 'rgba(255, 255, 255, 0.3)',
            )
        );

        $element->add_control(
            'magic_card_inner_glow_colors',
            array(
                'label'       => __( 'Inner Glow Colors', 'magic-card' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $inner_glow_repeater->get_controls(),
                'default'     => array(
                    array( 'color' => 'rgba(14, 165, 233, 0.3)' ),
                    array( 'color' => 'rgba(168, 85, 247, 0.3)' ),
                    array( 'color' => 'rgba(236, 72, 153, 0.3)' ),
                ),
                'title_field' => '<span style="background: {{{ color }}}; width: 20px; height: 20px; display: inline-block; border-radius: 3px; margin-right: 5px; vertical-align: middle;"></span> {{{ color }}}',
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Inner Glow Animation Speed
        $element->add_control(
            'magic_card_inner_glow_speed',
            array(
                'label'       => __( 'Color Animation Speed', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'description' => __( 'Duration of one full color cycle in seconds.', 'magic-card' ),
                'range'       => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 20,
                        'step' => 0.5,
                    ),
                ),
                'default'     => array(
                    'size' => 5,
                    'unit' => 'px',
                ),
                'selectors'   => array(
                    '{{WRAPPER}}' => '--magic-card-inner-glow-speed: {{SIZE}}s;',
                ),
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // Inner Glow Blur
        $element->add_control(
            'magic_card_inner_glow_blur',
            array(
                'label'       => __( 'Inner Glow Blur', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'description' => __( 'Blur amount for softer glow effect.', 'magic-card' ),
                'range'       => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ),
                ),
                'default'     => array(
                    'size' => 20,
                    'unit' => 'px',
                ),
                'selectors'   => array(
                    '{{WRAPPER}}' => '--magic-card-inner-glow-blur: {{SIZE}}{{UNIT}};',
                ),
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'conic_glow',
                ),
            )
        );

        // === Animation Settings (for Animated Border and Beam modes) ===
        $element->add_control(
            'magic_card_animation_heading',
            array(
                'label'     => __( 'Animation Settings', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Color 1 for beam mode (simple 2-color)
        $element->add_control(
            'magic_card_color1',
            array(
                'label'     => __( 'Gradient Color 1', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(14, 165, 233, 1)',
                'selectors' => array(
                    '{{WRAPPER}}' => '--magic-card-color1: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'beam',
                ),
            )
        );

        // Color 2 for beam mode (simple 2-color)
        $element->add_control(
            'magic_card_color2',
            array(
                'label'     => __( 'Gradient Color 2', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(168, 85, 247, 1)',
                'selectors' => array(
                    '{{WRAPPER}}' => '--magic-card-color2: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'beam',
                ),
            )
        );

        // Multi-color repeater for animated_border mode
        $repeater = new Repeater();

        $repeater->add_control(
            'color',
            array(
                'label'   => __( 'Color', 'magic-card' ),
                'type'    => Controls_Manager::COLOR,
                'default' => 'rgba(14, 165, 233, 1)',
            )
        );

        $element->add_control(
            'magic_card_gradient_colors',
            array(
                'label'       => __( 'Gradient Colors', 'magic-card' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array( 'color' => 'rgba(14, 165, 233, 1)' ),
                    array( 'color' => 'rgba(168, 85, 247, 1)' ),
                ),
                'title_field' => '<span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:{{{ color }}};vertical-align:middle;margin-right:8px;"></span> {{{ color }}}',
                'prevent_empty' => false,
                'frontend_available' => true,
                'render_type' => 'template',
                'condition'   => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'animated_border',
                ),
            )
        );

        // Animation direction
        $element->add_control(
            'magic_card_animation_direction',
            array(
                'label'   => __( 'Animation Direction', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => array(
                    'normal'  => __( 'Clockwise', 'magic-card' ),
                    'reverse' => __( 'Counter-clockwise', 'magic-card' ),
                ),
                'selectors' => array(
                    '{{WRAPPER}}' => '--magic-card-animation-direction: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Animation speed
        $element->add_control(
            'magic_card_animation_speed',
            array(
                'label'      => __( 'Animation Speed', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 10,
                        'step' => 0.5,
                    ),
                ),
                'default'    => array(
                    'size' => 4,
                ),
                'description' => __( 'Duration of one full rotation in seconds.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-animation-speed: {{SIZE}}s;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Beam width (only for beam mode)
        $element->add_control(
            'magic_card_beam_width',
            array(
                'label'      => __( 'Beam Width', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 5,
                        'max'  => 90,
                        'step' => 5,
                    ),
                ),
                'default'    => array(
                    'size' => 45,
                ),
                'description' => __( 'Width of the visible beam in degrees.', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-beam-width: {{SIZE}}deg;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'beam',
                ),
            )
        );

        // Beam front fade (leading edge blur)
        $element->add_control(
            'magic_card_beam_fade_front',
            array(
                'label'      => __( 'Front Fade (Leading Edge)', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 30,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'size' => 15,
                ),
                'description' => __( 'Blur at the leading edge of the beam (0 = sharp).', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-beam-fade-front: {{SIZE}}deg;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'beam',
                ),
            )
        );

        // Beam back fade (trailing edge blur)
        $element->add_control(
            'magic_card_beam_fade_back',
            array(
                'label'      => __( 'Back Fade (Trailing Edge)', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 30,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'size' => 15,
                ),
                'description' => __( 'Blur at the trailing edge of the beam (0 = sharp).', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-beam-fade-back: {{SIZE}}deg;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => 'beam',
                ),
            )
        );

        // Border width (glow border thickness)
        $element->add_control(
            'magic_card_border_width',
            array(
                'label'      => __( 'Border Width', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 10,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 2,
                ),
                'separator'  => 'before',
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-border-width: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Border radius
        $element->add_control(
            'magic_card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 12,
                ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-border-radius: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Inner background color
        $element->add_control(
            'magic_card_inner_bg',
            array(
                'label'     => __( 'Inner Background', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.85)',
                'selectors' => array(
                    '{{WRAPPER}}' => '--magic-card-inner-bg: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Inner background hover color
        $element->add_control(
            'magic_card_inner_bg_hover',
            array(
                'label'     => __( 'Inner Background (Hover)', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.75)',
                'selectors' => array(
                    '{{WRAPPER}}' => '--magic-card-inner-bg-hover: {{VALUE}};',
                ),
                'condition' => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // === Animation Delay Settings (for Animated Border and Beam modes) ===
        $element->add_control(
            'magic_card_delay_heading',
            array(
                'label'     => __( 'Animation Delay', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Enable viewport trigger
        $element->add_control(
            'magic_card_viewport_trigger',
            array(
                'label'        => __( 'Start on Viewport Entry', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( 'Animation starts when element enters viewport.', 'magic-card' ),
                'condition'    => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Initial delay (for single element or first child)
        $element->add_control(
            'magic_card_start_delay',
            array(
                'label'      => __( 'Start Delay', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 2000,
                        'step' => 50,
                    ),
                ),
                'default'    => array(
                    'size' => 0,
                ),
                'description' => __( 'Delay before animation starts (in milliseconds).', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-start-delay: {{SIZE}}ms;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                ),
            )
        );

        // Cascade delay (for children mode - delay between each child)
        $element->add_control(
            'magic_card_cascade_delay',
            array(
                'label'      => __( 'Cascade Delay (Children)', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 0,
                        'max'  => 1000,
                        'step' => 50,
                    ),
                ),
                'default'    => array(
                    'size' => 150,
                ),
                'description' => __( 'Delay between each child element animation (in milliseconds).', 'magic-card' ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-cascade-delay: {{SIZE}}ms;',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_effect_mode' => array( 'animated_border', 'beam' ),
                    'magic_card_apply_mode'  => 'children',
                ),
            )
        );

        // Efekt Tilt (3D rotation)
        $element->add_control(
            'magic_card_tilt_enable',
            array(
                'label'        => __( 'Enable Tilt Effect', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __( '3D rotation effect on hover.', 'magic-card' ),
                'separator'    => 'before',
                'condition'    => array(
                    'magic_card_enable' => 'yes',
                ),
            )
        );

        // Intensywność tilt
        $element->add_control(
            'magic_card_tilt_intensity',
            array(
                'label'      => __( 'Tilt Intensity', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 20,
                        'step' => 1,
                    ),
                ),
                'default'    => array(
                    'size' => 10,
                ),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--magic-card-tilt-intensity: {{SIZE}};',
                ),
                'condition'  => array(
                    'magic_card_enable'      => 'yes',
                    'magic_card_tilt_enable' => 'yes',
                ),
            )
        );

        $element->end_controls_section();
    }

    /**
     * Resolve global color to actual color value
     */
    private function resolve_color( $color, $settings = array(), $key = '' ) {
        // Check if the color value itself is a global reference
        $global_ref = null;

        // First check __globals__ array
        if ( ! empty( $key ) && isset( $settings['__globals__'][ $key ] ) && ! empty( $settings['__globals__'][ $key ] ) ) {
            $global_ref = $settings['__globals__'][ $key ];
        }
        // Then check if the color value itself is a global reference (handle both escaped and unescaped)
        elseif ( ! empty( $color ) && ( strpos( $color, 'globals/colors' ) !== false || strpos( $color, 'globals\\/colors' ) !== false ) ) {
            $global_ref = str_replace( '\\/', '/', $color ); // Unescape slashes
        }

        if ( $global_ref && strpos( $global_ref, 'globals/colors' ) !== false ) {
            // Get Elementor kit settings
            $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
            if ( $kit ) {
                $kit_settings = $kit->get_settings();
                // Extract color ID from reference
                preg_match( '/id=([a-zA-Z0-9_]+)/', $global_ref, $matches );
                if ( ! empty( $matches[1] ) ) {
                    $color_id = $matches[1];
                    // Search in system colors
                    if ( isset( $kit_settings['system_colors'] ) && is_array( $kit_settings['system_colors'] ) ) {
                        foreach ( $kit_settings['system_colors'] as $sys_color ) {
                            if ( isset( $sys_color['_id'] ) && $sys_color['_id'] === $color_id && ! empty( $sys_color['color'] ) ) {
                                return $sys_color['color'];
                            }
                        }
                    }
                    // Search in custom colors
                    if ( isset( $kit_settings['custom_colors'] ) && is_array( $kit_settings['custom_colors'] ) ) {
                        foreach ( $kit_settings['custom_colors'] as $custom_color ) {
                            if ( isset( $custom_color['_id'] ) && $custom_color['_id'] === $color_id && ! empty( $custom_color['color'] ) ) {
                                return $custom_color['color'];
                            }
                        }
                    }
                }
            }
        }
        // Return original color if not global or not found
        return $color;
    }

    /**
     * Dodaj atrybuty data-* i inline style przed renderowaniem
     */
    public function before_render( $element ) {
        $settings = $element->get_settings_for_display();

        if ( 'yes' !== $settings['magic_card_enable'] ) {
            return;
        }

        $apply_mode = isset( $settings['magic_card_apply_mode'] ) ? $settings['magic_card_apply_mode'] : 'self';

        if ( 'children' === $apply_mode ) {
            // Tryb children - parent jest tylko wrapperem z ustawieniami
            $element->add_render_attribute( '_wrapper', 'class', 'magic-card-parent' );
            $element->add_render_attribute( '_wrapper', 'data-magic-card-mode', 'children' );

            // Target nesting level
            $target_level = isset( $settings['magic_card_target_level'] ) ? $settings['magic_card_target_level'] : '1';
            $element->add_render_attribute( '_wrapper', 'data-magic-target-level', esc_attr( $target_level ) );

            // Glow behavior for spotlight mode
            $glow_behavior = isset( $settings['magic_card_glow_behavior'] ) ? $settings['magic_card_glow_behavior'] : 'separated';
            $element->add_render_attribute( '_wrapper', 'data-magic-glow-behavior', esc_attr( $glow_behavior ) );

            // Dodaj exclude class jeśli ustawiona
            $exclude_class = isset( $settings['magic_card_exclude_class'] ) ? trim( $settings['magic_card_exclude_class'] ) : '';
            if ( ! empty( $exclude_class ) ) {
                $element->add_render_attribute( '_wrapper', 'data-magic-card-exclude', esc_attr( $exclude_class ) );
            }
        } else {
            // Tryb self - efekt na tym kontenerze
            $element->add_render_attribute( '_wrapper', 'class', 'has-magic-card' );
            $element->add_render_attribute( '_wrapper', 'data-magic-card', 'true' );
        }

        // Effect mode (spotlight, animated_border, beam)
        $effect_mode = isset( $settings['magic_card_effect_mode'] ) ? $settings['magic_card_effect_mode'] : 'spotlight';
        $element->add_render_attribute( '_wrapper', 'data-magic-effect-mode', esc_attr( $effect_mode ) );

        // Spotlight always-on mode
        $spotlight_always_on = 'spotlight' === $effect_mode && 'yes' === ( $settings['magic_card_spotlight_always_on'] ?? '' );
        if ( $spotlight_always_on ) {
            $element->add_render_attribute( '_wrapper', 'data-magic-spotlight-always-on', 'true' );
        }

        // Zbierz wszystkie CSS variables jako inline style
        $css_vars = array();

        // Effect mode as CSS variable
        $css_vars[] = '--magic-card-effect-mode: ' . esc_attr( $effect_mode );

        // Kolor blob z Group_Control_Background
        $bg_type = isset( $settings['magic_card_glow_background'] ) ? $settings['magic_card_glow_background'] : 'classic';

        // Resolve colors (handles global colors)
        $raw_color1 = ! empty( $settings['magic_card_glow_color'] ) ? $settings['magic_card_glow_color'] : '';
        $raw_color2 = ! empty( $settings['magic_card_glow_color_b'] ) ? $settings['magic_card_glow_color_b'] : '';

        $color1 = $this->resolve_color( $raw_color1, $settings, 'magic_card_glow_color' );
        $color2 = $this->resolve_color( $raw_color2, $settings, 'magic_card_glow_color_b' );

        // Apply defaults if still empty
        $color1 = ! empty( $color1 ) ? $color1 : 'rgba(14, 165, 233, 1)';
        $color2 = ! empty( $color2 ) ? $color2 : 'rgba(168, 85, 247, 1)';

        if ( 'gradient' === $bg_type ) {
            $angle         = isset( $settings['magic_card_glow_gradient_angle']['size'] ) ? $settings['magic_card_glow_gradient_angle']['size'] : 180;
            $location      = isset( $settings['magic_card_glow_color_stop']['size'] ) ? $settings['magic_card_glow_color_stop']['size'] : 0;
            $location_b    = isset( $settings['magic_card_glow_color_b_stop']['size'] ) ? $settings['magic_card_glow_color_b_stop']['size'] : 100;
            $gradient_type = isset( $settings['magic_card_glow_gradient_type'] ) ? $settings['magic_card_glow_gradient_type'] : 'linear';

            if ( 'radial' === $gradient_type ) {
                $position   = isset( $settings['magic_card_glow_gradient_position'] ) ? $settings['magic_card_glow_gradient_position'] : 'center center';
                $css_vars[] = '--magic-card-blob-color: radial-gradient(at ' . esc_attr( $position ) . ', ' . esc_attr( $color1 ) . ' ' . esc_attr( $location ) . '%, ' . esc_attr( $color2 ) . ' ' . esc_attr( $location_b ) . '%)';
            } else {
                $css_vars[] = '--magic-card-blob-color: linear-gradient(' . esc_attr( $angle ) . 'deg, ' . esc_attr( $color1 ) . ' ' . esc_attr( $location ) . '%, ' . esc_attr( $color2 ) . ' ' . esc_attr( $location_b ) . '%)';
            }
        } else {
            $blob_color = ! empty( $color1 ) ? $color1 : 'rgba(14, 165, 233, 0.9)';
            $css_vars[] = '--magic-card-blob-color: ' . esc_attr( $blob_color );
        }

        // Blob size
        if ( ! empty( $settings['magic_card_gradient_size']['size'] ) ) {
            $css_vars[] = '--magic-card-blob-size: ' . esc_attr( $settings['magic_card_gradient_size']['size'] ) . esc_attr( $settings['magic_card_gradient_size']['unit'] ?? 'px' );
        }

        // Blur
        if ( ! empty( $settings['magic_card_blur']['size'] ) ) {
            $css_vars[] = '--magic-card-blur: ' . esc_attr( $settings['magic_card_blur']['size'] ) . esc_attr( $settings['magic_card_blur']['unit'] ?? 'px' );
        }

        // Border width
        if ( ! empty( $settings['magic_card_border_width']['size'] ) ) {
            $css_vars[] = '--magic-card-border-width: ' . esc_attr( $settings['magic_card_border_width']['size'] ) . esc_attr( $settings['magic_card_border_width']['unit'] ?? 'px' );
        }

        // Border radius
        if ( ! empty( $settings['magic_card_border_radius']['size'] ) ) {
            $css_vars[] = '--magic-card-border-radius: ' . esc_attr( $settings['magic_card_border_radius']['size'] ) . esc_attr( $settings['magic_card_border_radius']['unit'] ?? 'px' );
        }

        // Inner background
        if ( ! empty( $settings['magic_card_inner_bg'] ) ) {
            $css_vars[] = '--magic-card-inner-bg: ' . esc_attr( $settings['magic_card_inner_bg'] );
        }

        // Inner background hover
        if ( ! empty( $settings['magic_card_inner_bg_hover'] ) ) {
            $css_vars[] = '--magic-card-inner-bg-hover: ' . esc_attr( $settings['magic_card_inner_bg_hover'] );
        }

        // Tilt effect
        if ( 'yes' === $settings['magic_card_tilt_enable'] ) {
            $element->add_render_attribute( '_wrapper', 'data-magic-tilt', 'true' );
            if ( ! empty( $settings['magic_card_tilt_intensity']['size'] ) ) {
                $css_vars[] = '--magic-card-tilt-intensity: ' . esc_attr( $settings['magic_card_tilt_intensity']['size'] );
            }
        }

        // Animation settings (for animated_border, beam, and conic_glow modes)
        if ( in_array( $effect_mode, array( 'animated_border', 'beam', 'conic_glow' ), true ) ) {
            // Animation speed
            $animation_speed = isset( $settings['magic_card_animation_speed']['size'] ) ? $settings['magic_card_animation_speed']['size'] : 4;
            $css_vars[]      = '--magic-card-animation-speed: ' . esc_attr( $animation_speed ) . 's';

            // Animation direction (now stored directly as normal/reverse)
            $animation_direction = isset( $settings['magic_card_animation_direction'] ) ? $settings['magic_card_animation_direction'] : 'normal';
            $css_vars[]          = '--magic-card-animation-direction: ' . esc_attr( $animation_direction );

            // Start delay
            $start_delay = isset( $settings['magic_card_start_delay']['size'] ) ? $settings['magic_card_start_delay']['size'] : 0;
            $css_vars[]  = '--magic-card-start-delay: ' . esc_attr( $start_delay ) . 'ms';

            // Cascade delay (children mode)
            if ( 'children' === $apply_mode ) {
                $cascade_delay = isset( $settings['magic_card_cascade_delay']['size'] ) ? $settings['magic_card_cascade_delay']['size'] : 150;
                $css_vars[]    = '--magic-card-cascade-delay: ' . esc_attr( $cascade_delay ) . 'ms';
            }

            // Viewport trigger
            if ( 'yes' === ( $settings['magic_card_viewport_trigger'] ?? '' ) ) {
                $element->add_render_attribute( '_wrapper', 'data-magic-viewport-trigger', 'true' );
            }

            // Beam-specific settings
            if ( 'beam' === $effect_mode ) {
                $beam_width = isset( $settings['magic_card_beam_width']['size'] ) ? $settings['magic_card_beam_width']['size'] : 45;
                $fade_front = isset( $settings['magic_card_beam_fade_front']['size'] ) ? $settings['magic_card_beam_fade_front']['size'] : 15;
                $fade_back  = isset( $settings['magic_card_beam_fade_back']['size'] ) ? $settings['magic_card_beam_fade_back']['size'] : 15;
                $color1     = ! empty( $settings['magic_card_color1'] ) ? $settings['magic_card_color1'] : 'rgba(14, 165, 233, 1)';
                $color2     = ! empty( $settings['magic_card_color2'] ) ? $settings['magic_card_color2'] : 'rgba(168, 85, 247, 1)';

                // Build beam gradient inline
                $beam_gradient = sprintf(
                    'conic-gradient(from 0deg, transparent 0deg, transparent %ddeg, %s %ddeg, %s %ddeg, %s %ddeg, transparent %ddeg)',
                    $fade_back,
                    esc_attr( $color1 ),
                    $fade_back + round( $beam_width * 0.3 ),
                    esc_attr( $color2 ),
                    $fade_back + round( $beam_width * 0.7 ),
                    esc_attr( $color1 ),
                    $fade_back + $beam_width,
                    $fade_back + $beam_width + $fade_front
                );
                $css_vars[] = '--magic-card-beam-gradient: ' . $beam_gradient;
            }

            // Animated border multi-color gradient
            if ( 'animated_border' === $effect_mode ) {
                $gradient_colors = isset( $settings['magic_card_gradient_colors'] ) ? $settings['magic_card_gradient_colors'] : array();

                if ( ! empty( $gradient_colors ) && is_array( $gradient_colors ) ) {
                    // Build conic gradient from repeater colors
                    $colors = array();
                    foreach ( $gradient_colors as $item ) {
                        if ( ! empty( $item['color'] ) ) {
                            $colors[] = esc_attr( $item['color'] );
                        }
                    }

                    if ( count( $colors ) >= 2 ) {
                        // Calculate even spacing
                        $color_count = count( $colors );
                        $stops       = array();
                        for ( $i = 0; $i < $color_count; $i++ ) {
                            $angle   = ( $i / $color_count ) * 360;
                            $stops[] = $colors[ $i ] . ' ' . round( $angle ) . 'deg';
                        }
                        // Add first color at end for seamless loop
                        $stops[] = $colors[0] . ' 360deg';

                        $gradient   = 'conic-gradient(from 0deg, ' . implode( ', ', $stops ) . ')';
                        $css_vars[] = '--magic-card-multi-gradient: ' . $gradient;
                    }
                } else {
                    // Fallback to default 2-color
                    $css_vars[] = '--magic-card-multi-gradient: conic-gradient(from 0deg, rgba(14, 165, 233, 1) 0deg, rgba(168, 85, 247, 1) 180deg, rgba(14, 165, 233, 1) 360deg)';
                }
            }

            // Conic Glow gradient (Aceternity-style)
            if ( 'conic_glow' === $effect_mode ) {
                $conic_variant = isset( $settings['magic_card_conic_variant'] ) ? $settings['magic_card_conic_variant'] : 'default';

                // Build gradient based on variant
                if ( 'white' === $conic_variant ) {
                    // Black & White variant
                    $css_vars[] = '--magic-card-conic-gradient: conic-gradient(from 0deg, #000 0deg, #fff 90deg, #000 180deg, #fff 270deg, #000 360deg)';
                } elseif ( 'custom' === $conic_variant ) {
                    // Custom colors from repeater
                    $conic_colors = isset( $settings['magic_card_conic_colors'] ) ? $settings['magic_card_conic_colors'] : array();

                    if ( ! empty( $conic_colors ) && is_array( $conic_colors ) ) {
                        $colors = array();
                        foreach ( $conic_colors as $item ) {
                            if ( ! empty( $item['color'] ) ) {
                                $colors[] = esc_attr( $item['color'] );
                            }
                        }

                        if ( count( $colors ) >= 2 ) {
                            $num_colors = count( $colors );
                            $stops      = array();
                            for ( $i = 0; $i < $num_colors; $i++ ) {
                                $angle   = round( ( $i / $num_colors ) * 360 );
                                $stops[] = $colors[ $i ] . ' ' . $angle . 'deg';
                            }
                            $stops[]    = $colors[0] . ' 360deg';
                            $css_vars[] = '--magic-card-conic-gradient: conic-gradient(from 0deg, ' . implode( ', ', $stops ) . ')';
                        } else {
                            // Fallback if not enough colors
                            $css_vars[] = '--magic-card-conic-gradient: conic-gradient(from 0deg, #dd7bbb 0deg, #d79f1e 90deg, #5a922c 180deg, #4c7894 270deg, #dd7bbb 360deg)';
                        }
                    } else {
                        $css_vars[] = '--magic-card-conic-gradient: conic-gradient(from 0deg, #dd7bbb 0deg, #d79f1e 90deg, #5a922c 180deg, #4c7894 270deg, #dd7bbb 360deg)';
                    }
                } else {
                    // Default multi-color gradient (exact Aceternity gradient)
                    $css_vars[] = '--magic-card-conic-gradient: radial-gradient(circle, #dd7bbb 10%, #dd7bbb00 20%), radial-gradient(circle at 40% 40%, #d79f1e 5%, #d79f1e00 15%), radial-gradient(circle at 60% 60%, #5a922c 10%, #5a922c00 20%), radial-gradient(circle at 40% 60%, #4c7894 10%, #4c789400 20%), repeating-conic-gradient(from 236.84deg at 50% 50%, #dd7bbb 0%, #d79f1e 5%, #5a922c 10%, #4c7894 15%, #dd7bbb 20%)';
                }

                // Always visible attribute
                if ( 'yes' === ( $settings['magic_card_conic_always_visible'] ?? '' ) ) {
                    $element->add_render_attribute( '_wrapper', 'data-magic-conic-always-visible', 'true' );
                }

                // Glow persists attribute (stays when mouse leaves)
                if ( 'yes' === ( $settings['magic_card_conic_glow_persists'] ?? '' ) ) {
                    $element->add_render_attribute( '_wrapper', 'data-magic-conic-glow-persists', 'true' );
                }

                // Inner Glow color animation - pass colors as data attribute for JS
                $inner_glow_colors = isset( $settings['magic_card_inner_glow_colors'] ) ? $settings['magic_card_inner_glow_colors'] : array();

                if ( ! empty( $inner_glow_colors ) && is_array( $inner_glow_colors ) ) {
                    $colors = array();
                    foreach ( $inner_glow_colors as $item ) {
                        if ( ! empty( $item['color'] ) ) {
                            $colors[] = $item['color'];
                        }
                    }

                    if ( count( $colors ) >= 1 ) {
                        // Pass colors as JSON data attribute for JS animation
                        $element->add_render_attribute( '_wrapper', 'data-magic-inner-glow-colors', wp_json_encode( $colors ) );
                    }
                }
            }
        }

        // Dodaj inline style z CSS variables
        if ( ! empty( $css_vars ) ) {
            $element->add_render_attribute( '_wrapper', 'style', implode( '; ', $css_vars ) );
        }
    }

    /**
     * Enqueue scripts gdy efekt jest aktywny
     */
    public function maybe_enqueue_scripts( $element ) {
        $settings = $element->get_settings_for_display();

        if ( 'yes' === $settings['magic_card_enable'] ) {
            wp_enqueue_style( 'magic-card-style' );
            wp_enqueue_script( 'magic-card-script' );

            // Dynamiczny CSS dla exclude class
            $exclude_class = isset( $settings['magic_card_exclude_class'] ) ? trim( $settings['magic_card_exclude_class'] ) : '';
            if ( ! empty( $exclude_class ) ) {
                $safe_class = esc_attr( $exclude_class );
                $inline_css = ".{$safe_class}:has(.magic-holder) > .magic-holder,
.{$safe_class}:has(.magic-inner-blur) > .magic-inner-blur,
.{$safe_class} > .magic-holder,
.{$safe_class} > .magic-inner-blur {
    display: none !important;
}";
                wp_add_inline_style( 'magic-card-style', $inline_css );
            }

        }
    }

    /**
     * Enqueue scripts w edytorze Elementora
     */
    public function enqueue_editor_scripts() {
        wp_enqueue_style( 'magic-card-style' );
        wp_enqueue_script( 'magic-card-script' );

        // Add editor styles to hide container outlines when editing magic cards
        wp_add_inline_style( 'magic-card-style', '
            /* Hide grid outlines and element overlays in editor when toggle is on */
            body.magic-card-hide-outlines .e-grid-outline,
            body.magic-card-hide-outlines .elementor-element > .elementor-element-overlay {
                display: none !important;
            }
        ' );
    }
}
