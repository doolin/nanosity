<?php
/**
 * Plugin name: List Stores
 * 
 * Description: Rewrite rule to list stores as children of Stores page
 * 
 * This is all code from Professional WP Plugin Development, Ch. 14.
 */


register_activation_hook(__FILE__, 'boj_rrs_activate');
function boj_rrs_activate() {
    boj_rrs_add_rules();
    boj_ep_add_rules();
    boj_addfeed_add_feed();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'boj_rrs_deactivate');
function boj_rrs_deactivate() {
    flush_rewrite_rules();
}


add_action('init', 'boj_rrs_add_rules');
function boj_rrs_add_rules() {
    //add_rewrite_tag('%stores%','([^&]+)');
    add_rewrite_rule('stores/?([^/]*)',
                     'index.php?pagename=stores&store_id=$matches[1]',
                     'top');
}

add_filter('query_vars', 'boj_rrs_add_query_var');
function boj_rrs_add_query_var ($vars) {
    $vars[] = 'store_id';
    return $vars;
}


// Create new tag %product% and handle /shop/%product% URLs
add_action('init', 'boj_products_rewrite');
function boj_products_rewrite() {
    add_rewrite_tag('%product%', '([^/]+)');
    add_permastruct('product', 'shop'.'/%product%');
}

add_action('template_redirect', 'boj_products_display');
function boj_products_display() {
    if ($product = get_query_var('product')) {
        // include somepage.php
        echo "Here is information on product <strong>$product</strong>";
        exit;
    }
}


// Add endpoint rewrite rules
add_filter('init', 'boj_ep_add_rules');
function boj_ep_add_rules() {
  //echo 'Adding rewrite rules...';
  add_rewrite_endpoint('format', EP_ALL);
}

// Handle custom format display if needed
add_filter('template_redirect', 'boj_ep_template_redirect');
function boj_ep_template_redirect() {
    switch (get_query_var('format')) {
        case 'qr':
            boj_ep_display_qr();
            exit;
        case 'json':
            if (is_singular()) {
                boj_ep_display_json();
                exit;
            }
    }
}

// Display JSON information about the post
function boj_ep_display_json() {
    global $post;
    // Tell the browser this is a json file
    header('Content-type: application/json');
    echo json_encode($post);
    exit;
}


// Display a QR code
function boj_ep_display_qr() {
    // get current location and strip /format/qr from the code
    $url = (is_ssl() ? 'https://' : 'http://')
         . $_SERVER['HTTP_HOST']
         . preg_replace('!/format/qr/$!', '/', $_SERVER['REQUEST_URI']);
    $url = urlencode($url);
    $qr = "http://chart.apis.google.com/chart?chs=150x150&cht=qr&ch1=".$url."&chld=L|0";
    $image = wp_remote_retrieve_body(wp_remote_get($qr));

    // Display QR code image
    header('Content-type: image/png');
    echo $image;
    exit;
}

/// Registering a new feed
add_filter('init', 'boj_addfeed_add_feed');
function boj_addfeed_add_feed() {
    add_feed('img', 'boj_addfeed_do_feed');
}

function boj_addfeed_do_feed() {

    // Make custom query to get latest attachments
    query_posts(array('post_type' => 'attachment', 'post_status' => 'inherit'));

    // send content header and start ATOM output
    header('Content-type: application/atom+xml');
    echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
    ?>
<feed xmlns="http://www.w3.org/2005/atom">
    <title type="text">Latest images on <?php bloginfo_rss('name'); ?></title>
    <?php
    // start the loop
    while (have_posts()) : the_post();
    ?>
    <entry>
        <title><![CDATA[<?php the_title_rss(); ?>]]></title>
        <link href="<?php the_permalink_rss(); ?>" />
        <published><?php echo get_post_time('Y-m-d\TH:i:s\Z'); ?></published>
        <content type="html"><![CDATA[<?php the_content(); ?>]]></content>
    </entry>

    <?php
    // End the loop
    endwhile;
    ?>
</feed>
    <?php
}

?>
