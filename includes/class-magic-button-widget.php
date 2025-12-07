<?php
/**
 * Magic Button Widget for Elementor
 *
 * Custom button widget with animated gradient effects.
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Magic_Button_Widget extends Widget_Base {

    /**
     * Gradient presets
     */
    private $gradient_presets = array(
        'rainbow'    => array(
            'label'  => 'Rainbow',
            'colors' => array( '#94a3b8', '#9c3084', '#6548bb', '#f472b6', '#60bc4e', '#c27dff' ),
        ),
        'ocean'      => array(
            'label'  => 'Ocean',
            'colors' => array( '#0ea5e9', '#06b6d4', '#8b5cf6', '#3b82f6', '#22d3ee' ),
        ),
        'sunset'     => array(
            'label'  => 'Sunset',
            'colors' => array( '#f97316', '#ec4899', '#8b5cf6', '#f59e0b', '#ef4444' ),
        ),
        'nature'     => array(
            'label'  => 'Nature',
            'colors' => array( '#22c55e', '#14b8a6', '#84cc16', '#10b981', '#06b6d4' ),
        ),
        'monochrome' => array(
            'label'  => 'Monochrome',
            'colors' => array( '#94a3b8', '#64748b', '#cbd5e1', '#475569', '#e2e8f0' ),
        ),
        'custom'     => array(
            'label'  => 'Custom',
            'colors' => array(),
        ),
    );

    public function get_name() {
        return 'magic_button';
    }

    public function get_title() {
        return __( 'Magic Button', 'magic-card' );
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_keywords() {
        return array( 'button', 'magic', 'gradient', 'animated', 'glow' );
    }

    public function get_style_depends() {
        return array( 'magic-card-style' );
    }

    public function get_script_depends() {
        return array( 'magic-card-script' );
    }

    protected function register_controls() {
        // ===== CONTENT TAB =====
        $this->start_controls_section(
            'section_button',
            array(
                'label' => __( 'Button', 'magic-card' ),
            )
        );

        $this->add_control(
            'text',
            array(
                'label'       => __( 'Text', 'magic-card' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Click me', 'magic-card' ),
                'placeholder' => __( 'Click me', 'magic-card' ),
                'dynamic'     => array( 'active' => true ),
            )
        );

        $this->add_control(
            'link',
            array(
                'label'       => __( 'Link', 'magic-card' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://your-link.com', 'magic-card' ),
                'default'     => array( 'url' => '#' ),
                'dynamic'     => array( 'active' => true ),
            )
        );

        $this->add_responsive_control(
            'align',
            array(
                'label'        => __( 'Alignment', 'magic-card' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => array(
                    'left'   => array(
                        'title' => __( 'Left', 'magic-card' ),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __( 'Center', 'magic-card' ),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right'  => array(
                        'title' => __( 'Right', 'magic-card' ),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default'      => 'center',
                'prefix_class' => 'elementor%s-align-',
            )
        );

        $this->add_control(
            'selected_icon',
            array(
                'label'            => __( 'Icon', 'magic-card' ),
                'type'             => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'skin'             => 'inline',
                'label_block'      => false,
            )
        );

        $this->add_control(
            'icon_position',
            array(
                'label'     => __( 'Icon Position', 'magic-card' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'left',
                'options'   => array(
                    'left'  => __( 'Before', 'magic-card' ),
                    'right' => __( 'After', 'magic-card' ),
                ),
                'condition' => array(
                    'selected_icon[value]!' => '',
                ),
            )
        );

        $this->add_control(
            'icon_spacing',
            array(
                'label'     => __( 'Icon Spacing', 'magic-card' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => array(
                    'px' => array( 'min' => 0, 'max' => 50 ),
                ),
                'default'   => array( 'size' => 8 ),
                'selectors' => array(
                    '{{WRAPPER}} .magic-btn-icon-left'  => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .magic-btn-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'selected_icon[value]!' => '',
                ),
            )
        );

        $this->end_controls_section();

        // ===== MAGIC EFFECT SECTION =====
        $this->start_controls_section(
            'section_magic_effect',
            array(
                'label' => __( 'Magic Effect', 'magic-card' ),
            )
        );

        $this->add_control(
            'effect_mode',
            array(
                'label'   => __( 'Effect Mode', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'full',
                'options' => array(
                    'text_only'   => __( 'Animated Text Only', 'magic-card' ),
                    'border_only' => __( 'Animated Border Only', 'magic-card' ),
                    'full'        => __( 'Full Animated', 'magic-card' ),
                ),
            )
        );

        // Gradient preset
        $preset_options = array();
        foreach ( $this->gradient_presets as $key => $preset ) {
            $preset_options[ $key ] = $preset['label'];
        }

        $this->add_control(
            'color_preset',
            array(
                'label'   => __( 'Color Preset', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'rainbow',
                'options' => $preset_options,
            )
        );

        // Custom colors
        for ( $i = 1; $i <= 6; $i++ ) {
            $default_colors = array(
                1 => '#94a3b8',
                2 => '#60a5fa',
                3 => '#a78bfa',
                4 => '#f472b6',
                5 => '#22c55e',
                6 => '#f59e0b',
            );

            $this->add_control(
                'custom_color_' . $i,
                array(
                    'label'     => sprintf( __( 'Color %d', 'magic-card' ), $i ),
                    'type'      => Controls_Manager::COLOR,
                    'default'   => $default_colors[ $i ],
                    'condition' => array( 'color_preset' => 'custom' ),
                )
            );
        }

        $this->add_control(
            'heading_animation',
            array(
                'label'     => __( 'Animation', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'animation_speed',
            array(
                'label'       => __( 'Animation Speed (s)', 'magic-card' ),
                'description' => __( 'Synchronized speed for border, text, and glow animations', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'range'       => array( 'px' => array( 'min' => 1, 'max' => 15, 'step' => 0.5 ) ),
                'default'     => array( 'size' => 7 ),
            )
        );

        $this->add_control(
            'heading_border',
            array(
                'label'     => __( 'Border', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array( 'effect_mode' => array( 'border_only', 'full' ) ),
            )
        );

        $this->add_control(
            'border_width',
            array(
                'label'     => __( 'Border Width', 'magic-card' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => array( 'px' => array( 'min' => 1, 'max' => 5 ) ),
                'default'   => array( 'size' => 2 ),
                'condition' => array( 'effect_mode' => array( 'border_only', 'full' ) ),
            )
        );

        $this->add_control(
            'heading_glow',
            array(
                'label'     => __( 'Glow', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_hidden',
            array(
                'label'       => __( 'Hide Glow by Default', 'magic-card' ),
                'description' => __( 'Glow will only appear on hover with smooth animation', 'magic-card' ),
                'type'        => Controls_Manager::SWITCHER,
                'label_on'    => __( 'Yes', 'magic-card' ),
                'label_off'   => __( 'No', 'magic-card' ),
                'default'     => '',
                'condition'   => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_direction',
            array(
                'label'       => __( 'Glow Direction', 'magic-card' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'outer',
                'options'     => array(
                    'outer'       => __( 'Outer (default)', 'magic-card' ),
                    'inner'       => __( 'Inner', 'magic-card' ),
                    'inner_outer' => __( 'Inner → Outer on Hover', 'magic-card' ),
                    'outer_inner' => __( 'Outer → Inner on Hover', 'magic-card' ),
                ),
                'condition'   => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_blur',
            array(
                'label'     => __( 'Glow Blur', 'magic-card' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => array( 'px' => array( 'min' => 5, 'max' => 30 ) ),
                'default'   => array( 'size' => 10 ),
                'condition' => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_opacity',
            array(
                'label'       => __( 'Glow Opacity', 'magic-card' ),
                'description' => __( 'Opacity when glow is visible (default state or hover)', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'range'       => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
                'default'     => array( 'size' => 0.54 ),
                'condition'   => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_opacity_hover',
            array(
                'label'     => __( 'Glow Opacity on Hover', 'magic-card' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
                'default'   => array( 'size' => 0.8 ),
                'condition' => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'glow_inset_hover',
            array(
                'label'       => __( 'Glow Size on Hover', 'magic-card' ),
                'description' => __( 'How far the glow extends beyond button (px)', 'magic-card' ),
                'type'        => Controls_Manager::SLIDER,
                'range'       => array( 'px' => array( 'min' => 5, 'max' => 40 ) ),
                'default'     => array( 'size' => 15 ),
                'condition'   => array( 'effect_mode' => 'full' ),
            )
        );

        $this->add_control(
            'hover_scale',
            array(
                'label'   => __( 'Hover Scale', 'magic-card' ),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array( 'px' => array( 'min' => 1, 'max' => 1.2, 'step' => 0.01 ) ),
                'default' => array( 'size' => 1.03 ),
            )
        );

        $this->end_controls_section();

        // ===== ADVANCED GRADIENTS SECTION =====
        $this->start_controls_section(
            'section_advanced_gradients',
            array(
                'label' => __( 'Advanced Gradients', 'magic-card' ),
            )
        );

        $this->add_control(
            'advanced_gradients_info',
            array(
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => __( 'Override auto-generated gradients with custom CSS values. Leave empty to use colors from preset/custom colors above.', 'magic-card' ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            )
        );

        $this->add_control(
            'custom_border_gradient',
            array(
                'label'       => __( 'Border Gradient', 'magic-card' ),
                'description' => __( 'e.g. conic-gradient(from 0deg, #f97316, #ec4899, #ef4444, #f97316)', 'magic-card' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 2,
                'placeholder' => 'conic-gradient(from var(--magic-btn-border-angle), ...)',
                'condition'   => array( 'effect_mode' => array( 'border_only', 'full' ) ),
            )
        );

        $this->add_control(
            'custom_text_gradient',
            array(
                'label'       => __( 'Text Gradient', 'magic-card' ),
                'description' => __( 'e.g. linear-gradient(90deg, #ea580c, #fdba74, #fed7aa, #f9a8d4, #db2777, #ea580c)', 'magic-card' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 2,
                'placeholder' => 'linear-gradient(90deg, ...)',
                'condition'   => array( 'effect_mode' => array( 'text_only', 'full' ) ),
            )
        );

        $this->add_control(
            'custom_glow_gradient',
            array(
                'label'       => __( 'Glow Gradient', 'magic-card' ),
                'description' => __( 'e.g. linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.5), rgba(236, 72, 153, 0.5), transparent)', 'magic-card' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 2,
                'placeholder' => 'linear-gradient(90deg, transparent, ...)',
                'condition'   => array( 'effect_mode' => 'full' ),
            )
        );

        $this->end_controls_section();

        // ===== STYLE TAB - Button =====
        $this->start_controls_section(
            'section_style_button',
            array(
                'label' => __( 'Button', 'magic-card' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'typography',
                'selector' => '{{WRAPPER}} .magic-btn-text',
            )
        );

        $this->add_control(
            'background_color',
            array(
                'label'   => __( 'Background Color', 'magic-card' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#1a1a2e',
            )
        );

        $this->add_control(
            'background_opacity',
            array(
                'label'   => __( 'Background Opacity', 'magic-card' ),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
                'default' => array( 'size' => 0.5 ),
            )
        );

        $this->add_control(
            'heading_backdrop',
            array(
                'label'     => __( 'Backdrop Filter', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'backdrop_blur',
            array(
                'label'   => __( 'Blur', 'magic-card' ),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
                'default' => array( 'size' => 10 ),
            )
        );

        $this->add_control(
            'backdrop_brightness',
            array(
                'label'   => __( 'Brightness', 'magic-card' ),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array( 'px' => array( 'min' => 0, 'max' => 2, 'step' => 0.05 ) ),
                'default' => array( 'size' => 0.65 ),
            )
        );

        $this->add_control(
            'heading_text_shadow',
            array(
                'label'     => __( 'Text Shadow', 'magic-card' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array( 'effect_mode' => array( 'text_only', 'full' ) ),
            )
        );

        $this->add_control(
            'shadow_color',
            array(
                'label'     => __( 'Shadow Color', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.35)',
                'condition' => array( 'effect_mode' => array( 'text_only', 'full' ) ),
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label'     => __( 'Text Color', 'magic-card' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .magic-btn-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .magic-btn-icon' => 'color: {{VALUE}};',
                ),
                'condition' => array( 'effect_mode' => 'border_only' ),
            )
        );

        $this->add_responsive_control(
            'padding',
            array(
                'label'      => __( 'Padding', 'magic-card' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'default'    => array(
                    'top'    => 15,
                    'right'  => 30,
                    'bottom' => 15,
                    'left'   => 30,
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .magic-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'border_radius',
            array(
                'label'      => __( 'Border Radius', 'magic-card' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'default'    => array(
                    'top'    => 999,
                    'right'  => 999,
                    'bottom' => 999,
                    'left'   => 999,
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .magic-btn-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .magic-btn'         => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Build gradient colors array
     */
    private function get_gradient_colors( $settings ) {
        $preset = $settings['color_preset'] ?? 'rainbow';

        if ( 'custom' === $preset ) {
            $colors = array();
            for ( $i = 1; $i <= 6; $i++ ) {
                $color = $settings[ 'custom_color_' . $i ] ?? '';
                if ( ! empty( $color ) ) {
                    $colors[] = $color;
                }
            }
            return count( $colors ) >= 2 ? $colors : $this->gradient_presets['rainbow']['colors'];
        }

        return $this->gradient_presets[ $preset ]['colors'] ?? $this->gradient_presets['rainbow']['colors'];
    }

    /**
     * Build conic gradient for border
     */
    private function build_conic_gradient( $colors ) {
        $count = count( $colors );
        $stops = array();

        for ( $i = 0; $i < $count; $i++ ) {
            $angle   = round( ( $i / $count ) * 360 );
            $stops[] = esc_attr( $colors[ $i ] ) . ' ' . $angle . 'deg';
        }
        $stops[] = esc_attr( $colors[0] ) . ' 360deg';

        return 'conic-gradient(from var(--magic-btn-border-angle), ' . implode( ', ', $stops ) . ')';
    }

    /**
     * Build linear gradient for text
     */
    private function build_linear_gradient( $colors ) {
        $gradient_colors = implode( ', ', array_map( 'esc_attr', $colors ) );
        $gradient_colors .= ', ' . esc_attr( $colors[0] );
        return 'linear-gradient(90deg, ' . $gradient_colors . ')';
    }

    /**
     * Build glow gradient
     */
    private function build_glow_gradient( $colors ) {
        $stops = array( 'transparent 0%' );
        $count = count( $colors );

        for ( $i = 0; $i < $count; $i++ ) {
            $percent = 10 + ( $i / ( $count - 1 ) ) * 75;
            $color   = $this->color_with_opacity( $colors[ $i ], 0.5 );
            $stops[] = $color . ' ' . round( $percent ) . '%';
        }

        $stops[] = 'transparent 100%';
        return 'linear-gradient(90deg, ' . implode( ', ', $stops ) . ')';
    }

    /**
     * Add opacity to color
     */
    private function color_with_opacity( $color, $opacity ) {
        if ( strpos( $color, '#' ) === 0 ) {
            $hex = ltrim( $color, '#' );
            if ( strlen( $hex ) === 3 ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
            return "rgba({$r}, {$g}, {$b}, {$opacity})";
        }
        return $color;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $mode   = $settings['effect_mode'] ?? 'full';
        $colors = $this->get_gradient_colors( $settings );

        // Build background color with opacity
        $bg_color   = $settings['background_color'] ?? '#1a1a2e';
        $bg_opacity = $settings['background_opacity']['size'] ?? 0.5;
        $bg_rgba    = $this->color_with_opacity( $bg_color, $bg_opacity );

        // Glow direction and inset values
        $glow_direction  = $settings['glow_direction'] ?? 'outer';
        $glow_inset_size = $settings['glow_inset_hover']['size'] ?? 15;

        // Calculate inset values based on direction
        $outer_inset = '-' . $glow_inset_size . 'px -' . round( $glow_inset_size * 1.3 ) . 'px';
        $inner_inset = $glow_inset_size . 'px ' . round( $glow_inset_size * 1.3 ) . 'px';

        switch ( $glow_direction ) {
            case 'inner':
                $glow_inset        = $inner_inset;
                $glow_inset_hidden = $inner_inset;
                $glow_inset_hover  = $inner_inset;
                break;
            case 'inner_outer':
                $glow_inset        = $inner_inset;
                $glow_inset_hidden = $inner_inset;
                $glow_inset_hover  = $outer_inset;
                break;
            case 'outer_inner':
                $glow_inset        = $outer_inset;
                $glow_inset_hidden = $outer_inset;
                $glow_inset_hover  = $inner_inset;
                break;
            default: // outer
                $glow_inset        = $outer_inset;
                $glow_inset_hidden = '50% 50%';
                $glow_inset_hover  = $outer_inset;
                break;
        }

        // Build CSS variables - use custom gradients if provided, otherwise auto-generate
        $border_gradient = ! empty( $settings['custom_border_gradient'] )
            ? $settings['custom_border_gradient']
            : $this->build_conic_gradient( $colors );

        $text_gradient = ! empty( $settings['custom_text_gradient'] )
            ? $settings['custom_text_gradient']
            : $this->build_linear_gradient( $colors );

        $glow_gradient = ! empty( $settings['custom_glow_gradient'] )
            ? $settings['custom_glow_gradient']
            : $this->build_glow_gradient( $colors );

        $css_vars = array(
            '--magic-btn-border-gradient'    => $border_gradient,
            '--magic-btn-text-gradient'      => $text_gradient,
            '--magic-btn-glow-gradient'      => $glow_gradient,
            '--magic-btn-speed'              => ( $settings['animation_speed']['size'] ?? 7 ) . 's',
            '--magic-btn-border-width'       => ( $settings['border_width']['size'] ?? 2 ) . 'px',
            '--magic-btn-glow-blur'          => ( $settings['glow_blur']['size'] ?? 10 ) . 'px',
            '--magic-btn-glow-opacity'       => $settings['glow_opacity']['size'] ?? 0.54,
            '--magic-btn-glow-opacity-hover' => $settings['glow_opacity_hover']['size'] ?? 0.8,
            '--magic-btn-glow-inset'         => $glow_inset,
            '--magic-btn-glow-inset-hidden'  => $glow_inset_hidden,
            '--magic-btn-glow-inset-hover'   => $glow_inset_hover,
            '--magic-btn-hover-scale'        => $settings['hover_scale']['size'] ?? 1.03,
            '--magic-btn-bg'                 => $bg_rgba,
            '--magic-btn-backdrop-blur'      => ( $settings['backdrop_blur']['size'] ?? 10 ) . 'px',
            '--magic-btn-backdrop-brightness'=> $settings['backdrop_brightness']['size'] ?? 0.65,
            '--magic-btn-shadow-color'       => $settings['shadow_color'] ?? 'rgba(0, 0, 0, 0.35)',
        );

        // Check if glow should be hidden by default (or if using transition directions)
        $glow_hidden = ! empty( $settings['glow_hidden'] ) && 'yes' === $settings['glow_hidden'];
        // Also enable hidden mode for direction transitions
        if ( in_array( $glow_direction, array( 'inner_outer', 'outer_inner' ), true ) ) {
            $glow_hidden = true;
        }

        $style_parts = array();
        foreach ( $css_vars as $prop => $val ) {
            $style_parts[] = $prop . ': ' . $val;
        }
        $style = implode( '; ', $style_parts );

        // Link attributes
        $this->add_render_attribute( 'button', 'class', 'magic-btn' );

        if ( ! empty( $settings['link']['url'] ) ) {
            $this->add_link_attributes( 'button', $settings['link'] );
        }

        // Icon
        $icon_html = '';
        if ( ! empty( $settings['selected_icon']['value'] ) ) {
            $icon_position = $settings['icon_position'] ?? 'left';
            ob_start();
            Icons_Manager::render_icon( $settings['selected_icon'], array( 'aria-hidden' => 'true', 'class' => 'magic-btn-icon magic-btn-icon-' . $icon_position ) );
            $icon_html = ob_get_clean();
        }

        ?>
        <div class="magic-btn-wrapper" data-magic-button-mode="<?php echo esc_attr( $mode ); ?>"<?php echo $glow_hidden ? ' data-magic-glow-hidden' : ''; ?> style="<?php echo esc_attr( $style ); ?>">
            <?php if ( 'full' === $mode ) : ?>
                <div class="magic-btn-glow"></div>
            <?php endif; ?>
            <a <?php $this->print_render_attribute_string( 'button' ); ?>>
                <span class="magic-btn-content">
                    <?php if ( $icon_html && 'left' === ( $settings['icon_position'] ?? 'left' ) ) : ?>
                        <?php echo $icon_html; ?>
                    <?php endif; ?>
                    <span class="magic-btn-text"><?php echo esc_html( $settings['text'] ); ?></span>
                    <?php if ( $icon_html && 'right' === ( $settings['icon_position'] ?? 'left' ) ) : ?>
                        <?php echo $icon_html; ?>
                    <?php endif; ?>
                </span>
            </a>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var iconHTML = '';
        if ( settings.selected_icon && settings.selected_icon.value ) {
            iconHTML = elementor.helpers.renderIcon( view, settings.selected_icon, { 'aria-hidden': 'true', 'class': 'magic-btn-icon magic-btn-icon-' + settings.icon_position }, 'i', 'object' );
        }

        var colors = [];
        var presets = {
            'rainbow': ['#94a3b8', '#9c3084', '#6548bb', '#f472b6', '#60bc4e', '#c27dff'],
            'ocean': ['#0ea5e9', '#06b6d4', '#8b5cf6', '#3b82f6', '#22d3ee'],
            'sunset': ['#f97316', '#ec4899', '#8b5cf6', '#f59e0b', '#ef4444'],
            'nature': ['#22c55e', '#14b8a6', '#84cc16', '#10b981', '#06b6d4'],
            'monochrome': ['#94a3b8', '#64748b', '#cbd5e1', '#475569', '#e2e8f0']
        };

        if ( settings.color_preset === 'custom' ) {
            for ( var i = 1; i <= 6; i++ ) {
                var color = settings['custom_color_' + i];
                if ( color ) colors.push( color );
            }
            if ( colors.length < 2 ) colors = presets.rainbow;
        } else {
            colors = presets[settings.color_preset] || presets.rainbow;
        }

        function buildConicGradient( colors ) {
            var stops = [];
            for ( var i = 0; i < colors.length; i++ ) {
                var angle = Math.round( ( i / colors.length ) * 360 );
                stops.push( colors[i] + ' ' + angle + 'deg' );
            }
            stops.push( colors[0] + ' 360deg' );
            return 'conic-gradient(from var(--magic-btn-border-angle), ' + stops.join(', ') + ')';
        }

        function buildLinearGradient( colors ) {
            return 'linear-gradient(90deg, ' + colors.join(', ') + ', ' + colors[0] + ')';
        }

        function hexToRgba( hex, opacity ) {
            hex = hex.replace('#', '');
            if ( hex.length === 3 ) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            var r = parseInt( hex.substring(0, 2), 16 );
            var g = parseInt( hex.substring(2, 4), 16 );
            var b = parseInt( hex.substring(4, 6), 16 );
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + opacity + ')';
        }

        function buildGlowGradient( colors ) {
            var stops = ['transparent 0%'];
            for ( var i = 0; i < colors.length; i++ ) {
                var percent = 10 + ( i / ( colors.length - 1 ) ) * 75;
                stops.push( hexToRgba( colors[i], 0.5 ) + ' ' + Math.round( percent ) + '%' );
            }
            stops.push( 'transparent 100%' );
            return 'linear-gradient(90deg, ' + stops.join(', ') + ')';
        }

        var bgColor = settings.background_color || '#1a1a2e';
        var bgOpacity = settings.background_opacity ? settings.background_opacity.size : 0.5;
        var bgRgba = hexToRgba( bgColor, bgOpacity );

        var glowInsetSize = settings.glow_inset_hover ? settings.glow_inset_hover.size : 15;
        var glowDirection = settings.glow_direction || 'outer';

        // Calculate inset values based on direction
        var outerInset = '-' + glowInsetSize + 'px -' + Math.round( glowInsetSize * 1.3 ) + 'px';
        var innerInset = glowInsetSize + 'px ' + Math.round( glowInsetSize * 1.3 ) + 'px';

        var glowInset, glowInsetHidden, glowInsetHover;

        switch ( glowDirection ) {
            case 'inner':
                glowInset = innerInset;
                glowInsetHidden = innerInset;
                glowInsetHover = innerInset;
                break;
            case 'inner_outer':
                glowInset = innerInset;
                glowInsetHidden = innerInset;
                glowInsetHover = outerInset;
                break;
            case 'outer_inner':
                glowInset = outerInset;
                glowInsetHidden = outerInset;
                glowInsetHover = innerInset;
                break;
            default: // outer
                glowInset = outerInset;
                glowInsetHidden = '50% 50%';
                glowInsetHover = outerInset;
                break;
        }

        // Use custom gradients if provided, otherwise auto-generate
        var borderGradient = settings.custom_border_gradient || buildConicGradient( colors );
        var textGradient = settings.custom_text_gradient || buildLinearGradient( colors );
        var glowGradient = settings.custom_glow_gradient || buildGlowGradient( colors );

        var style = '--magic-btn-border-gradient: ' + borderGradient + '; ';
        style += '--magic-btn-text-gradient: ' + textGradient + '; ';
        style += '--magic-btn-glow-gradient: ' + glowGradient + '; ';
        style += '--magic-btn-speed: ' + ( settings.animation_speed ? settings.animation_speed.size : 7 ) + 's; ';
        style += '--magic-btn-border-width: ' + ( settings.border_width.size || 2 ) + 'px; ';
        style += '--magic-btn-glow-blur: ' + ( settings.glow_blur.size || 10 ) + 'px; ';
        style += '--magic-btn-glow-opacity: ' + ( settings.glow_opacity.size || 0.54 ) + '; ';
        style += '--magic-btn-glow-opacity-hover: ' + ( settings.glow_opacity_hover ? settings.glow_opacity_hover.size : 0.8 ) + '; ';
        style += '--magic-btn-glow-inset: ' + glowInset + '; ';
        style += '--magic-btn-glow-inset-hidden: ' + glowInsetHidden + '; ';
        style += '--magic-btn-glow-inset-hover: ' + glowInsetHover + '; ';
        style += '--magic-btn-hover-scale: ' + ( settings.hover_scale.size || 1.03 ) + '; ';
        style += '--magic-btn-bg: ' + bgRgba + '; ';
        style += '--magic-btn-backdrop-blur: ' + ( settings.backdrop_blur ? settings.backdrop_blur.size : 10 ) + 'px; ';
        style += '--magic-btn-backdrop-brightness: ' + ( settings.backdrop_brightness ? settings.backdrop_brightness.size : 0.65 ) + '; ';
        style += '--magic-btn-shadow-color: ' + ( settings.shadow_color || 'rgba(0, 0, 0, 0.35)' );

        var linkUrl = settings.link.url || '#';
        var glowHidden = settings.glow_hidden === 'yes' || glowDirection === 'inner_outer' || glowDirection === 'outer_inner';
        #>
        <div class="magic-btn-wrapper" data-magic-button-mode="{{ settings.effect_mode }}"<# if ( glowHidden ) { #> data-magic-glow-hidden<# } #> style="{{ style }}">
            <# if ( settings.effect_mode === 'full' ) { #>
                <div class="magic-btn-glow"></div>
            <# } #>
            <a href="{{ linkUrl }}" class="magic-btn">
                <span class="magic-btn-content">
                    <# if ( iconHTML && iconHTML.rendered && settings.icon_position === 'left' ) { #>
                        {{{ iconHTML.value }}}
                    <# } #>
                    <span class="magic-btn-text">{{{ settings.text }}}</span>
                    <# if ( iconHTML && iconHTML.rendered && settings.icon_position === 'right' ) { #>
                        {{{ iconHTML.value }}}
                    <# } #>
                </span>
            </a>
        </div>
        <?php
    }
}
