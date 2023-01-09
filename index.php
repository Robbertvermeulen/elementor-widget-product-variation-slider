<?php
/**
 * Plugin Name: Elementor product variation price slider
 * Description: A Elementor widget for displaying a slider for selecting variations of a Woocommerce product.
 * Version: 1.0
 * Author: EPIC WP
 * Author URI: https://www.epic-wp.com
 * License: GPL2
 */

/**
 * Enqueue scripts and styles.
 */
function pvsw_enqueue_scripts() {
    wp_enqueue_style('elementor-product-variation-slider-widget', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('elementor-product-variation-slider-widget', plugin_dir_url(__FILE__) . 'assets/js/main.js', ['wp-element'], '1.0', true);

    $currency = get_woocommerce_currency();

    wp_localize_script( 'elementor-product-variation-slider-widget', 'pvsw', array(
        'currency' => $currency,
        'ajaxUrl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'pvsw_enqueue_scripts');

/**
 * Add ajax action for adding to cart.
 */
function pvsw_handle_ajax_add_to_cart() {

    if (empty($_POST['product_id'])) 
        wp_die();
    
    // Add product to cart
    $cart_item_key = WC()->cart->add_to_cart($_POST['product_id'], 1);
    $checkout_url = wc_get_checkout_url();

    wp_send_json([
        'success' => true,
        'redirect_url' => $checkout_url
    ]);
}
add_action('wp_ajax_pvsw_add_to_cart', 'pvsw_handle_ajax_add_to_cart');
add_action('wp_ajax_nopriv_pvsw_add_to_cart', 'pvsw_handle_ajax_add_to_cart');

/**
 * Register new Elementor widget.
 */
function pvsw_register_new_widgets($widgets_manager) {

    if (!class_exists('Elementor\Widget_Base')) {
        return;
    }

    class WooCommerce_Product_Variation_Price_Slider_Elementor_Widget extends Elementor\Widget_Base {
        
        public function get_name() {
            return 'woocommerce-product-price-slider';
        }

        public function get_title() {
            return __( 'Product variation slider' );
        }

        public function get_categories() {
            return ['woocommerce' ];
        }

        public function get_keywords() {
            return ['woocommerce', 'product', 'price', 'slider'];
        }

        public function get_script_depends() {
            return ['jquery', 'elementor-product-variation-slider-widget'];
        }

        public function get_style_depends() {}

        protected function register_controls() {

            $products = wc_get_products(['limit' => -1]);
            $product_options = [];
            if (!empty($products)) {
                foreach ($products as $product) {
                    $product_id = $product->get_id();
                    $product_options[$product_id] = $product->get_title();
                }
            }

            $this->start_controls_section(
                'section_content',
                [
                    'label' => __( 'Content' ),
                ]
            );

            $this->add_control(
                'variation_slider_product',
                [
                    'label' => __( 'Product' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'placeholder' => __( 'Product' ),
                    'options' => $product_options
                ]
            );

            $this->add_control(
                'variation_slider_value_suffix',
                [
                    'label' => __('Value suffix'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'placeholder' => __('Value suffix')
                ]
            );

            $this->add_control(
                'variation_slider_cart_button_text',
                [
                    'label' => __('Cart button text'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'placeholder' => __('Value suffix'),
                    'default' => __('Add to cart')
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {

            $javascript_variations = [];
            $range_min = 0;
            $range_max = 0;

            $settings = $this->get_settings_for_display();
            $product_id = $settings['variation_slider_product'];
            $product = wc_get_product($product_id);

            if ($product && is_a($product, 'WC_Product_Variable')) {
                $variations = $product->get_available_variations('objects');
                if (!empty($variations)) {
                    foreach ($variations as $variation) {
                        $attribute_value = $variation->get_attribute('employees');
                        $javascript_variations[] = [
                            'attribute_value' => $attribute_value,
                            'price' => $variation->get_price(),
                            'id' => $variation->get_id()
                        ];
                    }
                }
            }   
            ?>

            <?php if (!empty($javascript_variations)) { ?>
                <script>
                    window.productVariationSlider = {
                        variations: JSON.parse('<?php echo json_encode($javascript_variations); ?>'),
                        valueSuffix: '<?php echo $settings['variation_slider_value_suffix']; ?>',
                        cartButtonText: '<?php echo $settings['variation_slider_cart_button_text']; ?>',
                    };
                </script>
            <?php } ?>

            <div id="product_variation_slider"></div>

            <?php
        }

        protected function content_template() {}
    }
    $widgets_manager->register(new \WooCommerce_Product_Variation_Price_Slider_Elementor_Widget());
}
add_action( 'elementor/widgets/register', 'pvsw_register_new_widgets' );

