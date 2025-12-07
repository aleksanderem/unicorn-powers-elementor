<?php
/**
 * Magic Card Elementor Widget
 *
 * Widżet pozwalający osadzić szablon Elementor z efektem Magic Card.
 * Zachowany dla wstecznej kompatybilności - preferowane jest użycie
 * kontrolek na kontenerach Elementora.
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! class_exists( 'Magic_Card_Elementor_Widget' ) ) :

class Magic_Card_Elementor_Widget extends Widget_Base {

    public function get_name() {
        return 'magic_card_elementor';
    }

    public function get_title() {
        return __( 'Magic Card (Template)', 'magic-card' );
    }

    public function get_icon() {
        return 'eicon-flipbox';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_keywords() {
        return array( 'magic', 'card', 'spotlight', 'gradient', 'hover', 'effect' );
    }

    public function get_style_depends() {
        return array( 'magic-card-style' );
    }

    public function get_script_depends() {
        return array( 'magic-card-script' );
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Content', 'magic-card' ),
            )
        );

        // Template selector
        $templates = get_posts( array(
            'post_type'      => 'elementor_library',
            'posts_per_page' => -1,
        ) );

        $options = array( '' => __( '— Select Template —', 'magic-card' ) );
        if ( $templates ) {
            foreach ( $templates as $template ) {
                $options[ $template->ID ] = $template->post_title;
            }
        }

        $this->add_control(
            'template_id',
            array(
                'label'   => __( 'Select Template', 'magic-card' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $options,
                'default' => '',
            )
        );

        $this->add_control(
            'extra_class',
            array(
                'label'       => __( 'Extra CSS Classes', 'magic-card' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'e.g. my-custom-class', 'magic-card' ),
            )
        );

        $this->end_controls_section();

        // Effect Settings Section
        $this->start_controls_section(
            'section_effect',
            array(
                'label' => __( 'Effect Settings', 'magic-card' ),
            )
        );

        $this->add_control(
            'gradient_color',
            array(
                'label'   => __( 'Gradient Color', 'magic-card' ),
                'type'    => Controls_Manager::COLOR,
                'default' => 'rgba(14, 165, 233, 0.25)',
                'selectors' => array(
                    '{{WRAPPER}} .magic-card' => '--magic-card-gradient-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'gradient_size',
            array(
                'label'      => __( 'Gradient Size', 'magic-card' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min'  => 100,
                        'max'  => 1000,
                        'step' => 10,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 400,
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .magic-card' => '--magic-card-gradient-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'enable_tilt',
            array(
                'label'        => __( 'Enable Tilt Effect', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );

        $this->add_control(
            'tilt_intensity',
            array(
                'label'   => __( 'Tilt Intensity', 'magic-card' ),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array(
                    'px' => array(
                        'min'  => 1,
                        'max'  => 20,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'size' => 10,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .magic-card' => '--magic-card-tilt-intensity: {{SIZE}};',
                ),
                'condition' => array(
                    'enable_tilt' => 'yes',
                ),
            )
        );

        $this->add_control(
            'enable_border_glow',
            array(
                'label'        => __( 'Border Glow on Hover', 'magic-card' ),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );

        $this->add_control(
            'border_glow_color',
            array(
                'label'   => __( 'Border Glow Color', 'magic-card' ),
                'type'    => Controls_Manager::COLOR,
                'default' => 'rgba(14, 165, 233, 0.5)',
                'selectors' => array(
                    '{{WRAPPER}} .magic-card' => '--magic-card-border-glow-color: {{VALUE}};',
                ),
                'condition' => array(
                    'enable_border_glow' => 'yes',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $template_id = isset( $settings['template_id'] ) ? intval( $settings['template_id'] ) : 0;
        $extra_class = ! empty( $settings['extra_class'] ) ? esc_attr( $settings['extra_class'] ) : '';

        $classes = 'magic-card';
        if ( $extra_class ) {
            $classes .= ' ' . $extra_class;
        }
        if ( 'yes' === $settings['enable_border_glow'] ) {
            $classes .= ' magic-card-border-glow';
        }

        $tilt_enabled = 'yes' === $settings['enable_tilt'] ? 'true' : 'false';

        echo '<div class="' . esc_attr( $classes ) . '"';
        echo ' data-magic-card="true"';
        echo ' data-magic-tilt="' . esc_attr( $tilt_enabled ) . '">';

        echo '<div class="magic-card-spotlight"></div>';
        echo '<div class="magic-card-inner">';

        if ( $template_id ) {
            echo do_shortcode( sprintf( '[elementor-template id="%d"]', $template_id ) );
        } else {
            echo '<p style="padding: 40px; text-align: center; color: #999;">';
            echo esc_html__( 'Select a template in widget settings.', 'magic-card' );
            echo '</p>';
        }

        echo '</div>'; // .magic-card-inner
        echo '</div>'; // .magic-card
    }
}

endif;
