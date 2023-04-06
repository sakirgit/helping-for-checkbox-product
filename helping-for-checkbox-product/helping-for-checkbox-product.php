<?php 
/**
 * Plugin Name: Helping for Checkbox product
 * Plugin URI: https://developer-s.com/
 * Description: Interact with custom product API
 * Version: 1.0
 * Author: Sakir Ahamed
 * Author URI: https://developer-s.com/
 **/

// Hook into WooCommerce

add_filter( 'woocommerce_api_check_https', '__return_false' );
function chrome_extension_cors_rest_send_cors_headers() {
  function is_chrome_extension() {
    $chrome = "chrome-extension://";
    $origin = get_http_origin();
    return substr($origin, 0, strlen($chrome)) === $chrome;
  }

  function allow_access( $filter ) {
    header( 'Access-Control-Allow-Origin: *');
        header( 'Access-Control-Allow-Methods: OPTIONS, GET' );
    return $filter;
  }

  if (is_chrome_extension()) {
    add_filter( 'rest_pre_serve_request', 'allow_access' );
  }
}
add_action( 'rest_api_init', 'chrome_extension_cors_rest_send_cors_headers');
















add_action('woocommerce_init', 'my_custom_product_api_init');

function my_custom_product_api_init() {
  // Register a custom endpoint for the API
  add_rewrite_endpoint('api-check-product-by-sku', EP_ALL);

  // Listen for requests to the custom endpoint
  add_action('parse_request', 'my_custom_product_api_request');

  // Add the API response to the product page
  add_action('woocommerce_single_product_summary', 'my_custom_product_api_output', 25);
}

function my_custom_product_api_request($wp) {
//	print_r($wp->query_vars['name']);exit;
  if (
		array_key_exists('api-check-product-by-sku', $wp->query_vars) || 
		$wp->query_vars['name'] == 'api-check-product-by-sku'
		) {
//	print_r($wp->query_vars);exit;
    // Get the SKU from the query string
    $sku = $_GET['sku'];

    // Search for the product with the given SKU
    $product = wc_get_product_id_by_sku($sku);

    // If we found the product, get its data and return a JSON response
    if ($product) {
      $data = wc_get_product($product)->get_data();
      header('Content-Type: application/json');
      echo json_encode($data);
      exit;
    } else {
      // If the product wasn't found, output a "product not found" message
      echo json_encode(['status'=>'not_found']);
		exit;
    }
  }
}

function my_custom_product_api_output() {
  global $wp_query;

  // Check if we have an API response
  if (array_key_exists('api_response', $wp_query->query_vars)) {
    $response = $wp_query->query_vars['api_response'];

    // Get the data from the API response
    $data = json_decode(wp_remote_retrieve_body($response));

    // Check if the product was found
    if ($data->status == 'found') {
      // Output the product data as JSON
      header('Content-Type: application/json');
      echo json_encode($data->product);
      exit;
    } else {
      // Output a "product not found" message
      echo json_encode(['status'=>'not_found']);
		exit;
    }
  }
}
