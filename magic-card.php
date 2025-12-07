<?php
/**
 * Plugin Name: Unicorn Powers for Elementor
 * Plugin URI: https://github.com/aleksanderem/unicorn-powers-elementor
 * Description: Efekt "Magic Card" inspirowany MagicUI - spotlight gradient podążający za kursorem. Dostępny jako opcja w ustawieniach wizualnych kontenerów Elementora oraz jako shortcode [magic_card].
 * Version: 2.1.0
 * Author: MWT Solutions
 * Author URI: https://mwtsolutions.eu
 * License: GPL2+
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: unicorn-powers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MAGIC_CARD_VERSION', '2.1.0' );
define( 'MAGIC_CARD_URL', plugin_dir_url( __FILE__ ) );
define( 'MAGIC_CARD_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin Update Checker - automatyczne aktualizacje z GitHub
 */
if ( file_exists( MAGIC_CARD_PATH . 'vendor/plugin-update-checker.php' ) ) {
    require_once MAGIC_CARD_PATH . 'vendor/plugin-update-checker.php';

    $updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/aleksanderem/unicorn-powers-elementor',
        __FILE__,
        'unicorn-powers-elementor'
    );

    // Opcjonalnie: użyj releases zamiast branch
    $updateChecker->getVcsApi()->enableReleaseAssets();
}

/**
 * Register CSS i JS dla efektu Magic Card (early, before enqueue).
 */
function magic_card_register_assets() {
    wp_register_style(
        'magic-card-style',
        MAGIC_CARD_URL . 'magic-card.css',
        array(),
        MAGIC_CARD_VERSION
    );
    wp_register_script(
        'magic-card-script',
        MAGIC_CARD_URL . 'magic-card.js',
        array(),
        MAGIC_CARD_VERSION,
        true
    );
}
add_action( 'init', 'magic_card_register_assets' );

/**
 * Enqueue CSS i JS dla efektu Magic Card on frontend.
 */
function magic_card_enqueue_assets() {
    wp_enqueue_style( 'magic-card-style' );
    wp_enqueue_script( 'magic-card-script' );
}
add_action( 'wp_enqueue_scripts', 'magic_card_enqueue_assets' );

/**
 * Shortcode [magic_card] do otaczania dowolnej treści efektem karty.
 */
function magic_card_shortcode( $atts, $content = null ) {
    wp_enqueue_style( 'magic-card-style' );
    wp_enqueue_script( 'magic-card-script' );

    $atts = shortcode_atts( array(
        'class'          => '',
        'gradient_color' => 'rgba(14, 165, 233, 0.25)',
        'gradient_size'  => '400',
        'tilt'           => 'true',
    ), $atts, 'magic_card' );

    $content = do_shortcode( $content );

    $classes = 'magic-card';
    if ( ! empty( $atts['class'] ) ) {
        $classes .= ' ' . esc_attr( $atts['class'] );
    }

    $tilt_enabled = filter_var( $atts['tilt'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $classes ); ?>"
         data-magic-card="true"
         data-gradient-color="<?php echo esc_attr( $atts['gradient_color'] ); ?>"
         data-gradient-size="<?php echo esc_attr( $atts['gradient_size'] ); ?>"
         data-tilt="<?php echo esc_attr( $tilt_enabled ); ?>">
        <div class="magic-card-spotlight"></div>
        <div class="magic-card-inner">
            <?php echo $content; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'magic_card', 'magic_card_shortcode' );

/**
 * Inicjalizacja integracji z Elementorem
 */
function magic_card_init_elementor() {
    if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
        return;
    }

    // Magic Card for containers
    require_once MAGIC_CARD_PATH . 'includes/class-elementor-extension.php';
    new Magic_Card_Elementor_Extension();
}
add_action( 'elementor/init', 'magic_card_init_elementor' );

/**
 * Rejestracja widżetów Elementor
 */
function magic_card_register_elementor_widgets( $widgets_manager ) {
    if ( ! defined( 'ELEMENTOR_VERSION' ) || ! class_exists( '\\Elementor\\Widget_Base' ) ) {
        return;
    }

    // Legacy Magic Card widget
    require_once MAGIC_CARD_PATH . 'class-magic-card-elementor-widget.php';
    $widgets_manager->register( new \Magic_Card_Elementor_Widget() );

    // Magic Button widget
    require_once MAGIC_CARD_PATH . 'includes/class-magic-button-widget.php';
    $widgets_manager->register( new \Magic_Button_Widget() );
}
add_action( 'elementor/widgets/register', 'magic_card_register_elementor_widgets' );
