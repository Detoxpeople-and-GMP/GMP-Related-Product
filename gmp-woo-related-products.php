<?php
/*
Plugin Name: GMP Related Product
Description: Shows a related product grid where the shortcode is added.
Version: 1.0.0
Author: GMP(Shalomt)
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register shortcode
add_shortcode('gmp_woo_related_products', 'gmp_woo_related_products_shortcode');

// Enqueue styles
function gmp_woorp_enqueue_styles() {
    wp_enqueue_style('gmp-woorp-styles', plugins_url('/css/style.css', __FILE__));
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'gmp_woorp_enqueue_styles');

// Enqueue admin styles and scripts
function gmp_woorp_enqueue_admin_styles() {
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('gmp-woorp-admin-js', plugins_url('/js/admin.js', __FILE__), array('jquery', 'select2-js'), null, true);
    wp_localize_script('gmp-woorp-admin-js', 'gmp_woorp_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'gmp_woorp_search_nonce' => wp_create_nonce('gmp_woorp_search_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'gmp_woorp_enqueue_admin_styles');


// Add admin menu
function gmp_woorp_add_admin_menu() {
    add_menu_page(
        'GMP WOO Related Products',
        'WOO Related Products',
        'manage_options',
        'gmp-woo-related-products',
        'gmp_woorp_admin_page',
        'dashicons-admin-plugins',
        20
    );
}
add_action('admin_menu', 'gmp_woorp_add_admin_menu');

// Admin page content
function gmp_woorp_admin_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmp_woorp_save_settings'])) {
        update_option('gmp_woorp_exclude_out_of_stock', isset($_POST['gmp_woorp_exclude_out_of_stock']) ? '1' : '0');
        update_option('gmp_woorp_exclude_backorder', isset($_POST['gmp_woorp_exclude_backorder']) ? '1' : '0');
        update_option('gmp_woorp_selected_brands', isset($_POST['gmp_woorp_selected_brands']) ? $_POST['gmp_woorp_selected_brands'] : []);
    }

    $exclude_out_of_stock = get_option('gmp_woorp_exclude_out_of_stock', '0');
    $exclude_backorder = get_option('gmp_woorp_exclude_backorder', '0');
    $selected_brands = get_option('gmp_woorp_selected_brands', []);

    ?>
    <div class="wrap">
        <h1>GMP WOO Related Products</h1>
        <p>This plugin displays a related product grid based on WOO logic where the shortcode is added.</p>
        <h2>Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Exclude Out of Stock Products</th>
                    <td>
                        <input type="checkbox" name="gmp_woorp_exclude_out_of_stock" value="1" <?php checked($exclude_out_of_stock, '1'); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Exclude Backorder Products</th>
                    <td>
                        <input type="checkbox" name="gmp_woorp_exclude_backorder" value="1" <?php checked($exclude_backorder, '1'); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Select Brands</th>
                    <td>
                        <select name="gmp_woorp_selected_brands[]" multiple="multiple" class="gmp-woorp-select2" style="width: 100%;">
                            <?php
                            $brands = get_terms('pa_brand', array('hide_empty' => false));
                            foreach ($brands as $brand) {
                                $selected = in_array($brand->term_id, $selected_brands) ? 'selected' : '';
                                echo '<option value="' . esc_attr($brand->term_id) . '" ' . $selected . '>' . esc_html($brand->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="gmp_woorp_save_settings" class="button button-primary" value="Save Changes" />
            </p>
        </form>
        <h2>Shortcode</h2>
        <p>Use the following shortcode to display the WOO related products:</p>
        <pre>[gmp_woo_related_products]</pre>
    </div>
    <?php
}


function gmp_woo_related_products_shortcode($atts) {
    if (is_cart()) {
        // Fetch products from selected brands
        $related_products = gmp_woorp_get_related_products_from_cart();
    } elseif (is_product()) {
        // Fetch related products based on current product
        global $product;
        $related_products = gmp_woorp_get_related_products($product->get_id());
    } else {
        // Fetch random products from selected brands for any other page
        $related_products = gmp_woorp_get_related_products_from_cart();
    }

    ob_start();
    ?>
    <div class="gmp-woo-related-products-grid">
        <?php foreach ($related_products as $related_product) : ?>
            <div class="gmp-product-item">
                <a href="<?php echo get_permalink($related_product->get_id()); ?>">
                    <?php echo $related_product->get_image(); ?>
                    <h3><?php echo $related_product->get_name(); ?></h3>
                </a>
                <div class="gmp-product-actions">
                    <p class="price"><?php echo $related_product->get_price_html(); ?></p>
                    <?php
                echo sprintf(
                    '<a href="%s" class="button add_to_cart_button ajax_add_to_cart" data-product_id="%s" data-product_sku="%s" aria-label="%s" rel="nofollow">%s</a>',
                    esc_url($related_product->add_to_cart_url()),
                    esc_attr($related_product->get_id()),
                    esc_attr($related_product->get_sku()),
                    esc_html__('Add to cart', 'woocommerce'),
                    esc_html__('Add to cart', 'woocommerce')
                );
                ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}





function gmp_woorp_get_related_products_from_cart() {
    $related_products = [];

    // Fetch selected brands from options
    $selected_brands = get_option('gmp_woorp_selected_brands', []);
    
    // Exclude out of stock and backorder products options
    $exclude_out_of_stock = get_option('gmp_woorp_exclude_out_of_stock', '0') === '1';
    $exclude_backorder = get_option('gmp_woorp_exclude_backorder', '0') === '1';

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 10,
        'orderby' => 'rand',
        'tax_query' => array(),
        'meta_query' => array() // Initialize the meta_query array
    );

    // Exclude out of stock products
    if ($exclude_out_of_stock) {
        $args['meta_query'][] = array(
            'key' => '_stock_status',
            'value' => 'instock',
            'compare' => 'IN'
        );
    }

    // Exclude backorder products
    if ($exclude_backorder) {
        $args['meta_query'][] = array(
            'key' => '_backorders',
            'value' => 'no',
            'compare' => 'IN'
        );
    }

    // Filter by selected brands (product attribute)
    if (!empty($selected_brands)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'pa_brand',
            'field' => 'term_id',
            'terms' => $selected_brands,
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $related_products[] = wc_get_product(get_the_ID());
        }
    }
    wp_reset_postdata();

    shuffle($related_products);

    // Ensure unique related products and limit the number
    $related_products = array_unique($related_products, SORT_REGULAR);
    $related_products = array_slice($related_products, 0, 4);

    return $related_products;
}




function gmp_woorp_get_related_products($product_id) {
    $product = wc_get_product($product_id);
    $related_products = [];

    if (!$product) {
        return $related_products;
    }

    // Fetch products by category and tags
    $category_ids = $product->get_category_ids();
    $tag_ids = $product->get_tag_ids();

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 10,
        'post__not_in' => array($product_id),
        'tax_query' => array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            ),
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $tag_ids,
            ),
        ),
        'meta_query' => array() // Initialize the meta_query array
    );

    // Exclude out of stock products
    if (get_option('gmp_woorp_exclude_out_of_stock', '0') === '1') {
        $args['meta_query'][] = array(
            'key' => '_stock_status',
            'value' => 'instock',
            'compare' => 'IN'
        );
    }

    // Exclude backorder products
    if (get_option('gmp_woorp_exclude_backorder', '0') === '1') {
        $args['meta_query'][] = array(
            'key' => '_backorders',
            'value' => 'no',
            'compare' => 'IN'
        );
    }

    // Filter by selected brands (product attribute)
    $selected_brands = get_option('gmp_woorp_selected_brands', []);
    if (!empty($selected_brands)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'pa_brand',
            'field' => 'term_id',
            'terms' => $selected_brands,
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $related_products[] = wc_get_product(get_the_ID());
        }
    }
    wp_reset_postdata();

    // Remove duplicates and limit the number of related products
    $related_products = array_unique($related_products, SORT_REGULAR);

    return $related_products;
}

function gmp_woorp_get_random_related_products() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'orderby' => 'rand',
        'tax_query' => array(
            'relation' => 'AND', // Change to AND since we need to match all criteria
        ),
        'meta_query' => array() // Initialize the meta_query array
    );

    // Exclude out of stock products
    if (get_option('gmp_woorp_exclude_out_of_stock', '0') === '1') {
        $args['meta_query'][] = array(
            'key' => '_stock_status',
            'value' => 'instock',
            'compare' => 'IN'
        );
    }

    // Exclude backorder products
    if (get_option('gmp_woorp_exclude_backorder', '0') === '1') {
        $args['meta_query'][] = array(
            'key' => '_backorders',
            'value' => 'no',
            'compare' => 'IN'
        );
    }

    // Filter by selected brands (product attribute)
    $selected_brands = get_option('gmp_woorp_selected_brands', []);
    if (!empty($selected_brands)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'pa_brand',
            'field' => 'term_id',
            'terms' => $selected_brands,
        );
    }

    $query = new WP_Query($args);
    $related_products = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $related_products[] = wc_get_product(get_the_ID());
        }
    }
    wp_reset_postdata();

    return $related_products;
}


// AJAX handler for searching products
function gmp_woorp_search_products() {
    check_ajax_referer('gmp_woorp_search_nonce', 'security');

    $search_term = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 20,
        's' => $search_term,
    );

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $results[] = array(
                'id' => $product->get_id(),
                'text' => $product->get_name(),
            );
        }
    }
    wp_reset_postdata();

    wp_send_json($results);
}
add_action('wp_ajax_gmp_woorp_search_products', 'gmp_woorp_search_products');
