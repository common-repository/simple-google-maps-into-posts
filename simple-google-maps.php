<?php
/*
  Plugin Name: Simple google maps gor wordpress
  Description: Easily display Google Maps into your posts
  Version: 1.1
  Author: Mahmoud Hussien
  Author Email: phpawy@gmail.com
  Date: 10/07/2016
 */
/*
  Simple Google Maps v.1 - Simply allowing you to add Google Maps to your posts.
  Copyright (C) 2016  Mahmoud Hussien

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function add_gmaps_meta_box() {
    add_meta_box("gmaps-meta-box", "Google Maps", "gmaps_meta_box_markup", "post", "side", "high", null);
}

function gmaps_meta_box_markup() {
    global $post;
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");
    ?>
    <div>
        <label for="meta-box-lat">Latitude</label>
        <input name="meta-box-lat" type="text" value="<?php echo get_post_meta($post->ID, "meta-box-lat", true); ?>">
        <br>
        <label for="meta-box-long">Longitude</label>
        <input name="meta-box-long" type="text" value="<?php echo get_post_meta($post->ID, "meta-box-long", true); ?>">
        <br>
        <label for="meta-box-zoom">Zoom Level</label>
        <select name="meta-box-zoom">
            <?php
            for ($i = 3; $i <= 21; $i++) {
                if ($i == get_post_meta($post->ID, "meta-box-zoom", true)) {
                    ?>
                    <option value="<?php echo $i; ?>" selected><?php echo $i; ?></option>
                    <?php
                } else {
                    ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php
                }
            }
            ?>
        </select>
        <br>
    </div>
    <?php
}

function save_gmaps_meta_box($post_id, $post, $update) {
    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if (!current_user_can("edit_post", $post_id))
        return $post_id;

    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "post";
    if ($slug != $post->post_type)
        return $post_id;

    $meta_box_lat_value = "";
    $meta_box_long_value = "";
    $meta_box_zoom_value = "";

    if (isset($_POST["meta-box-lat"])) {
        if (is_numeric($_POST["meta-box-lat"]))
            $meta_box_lat_value = sanitize_text_field($_POST["meta-box-lat"]);
    }
    update_post_meta($post_id, "meta-box-lat", $meta_box_lat_value);

    if (isset($_POST["meta-box-long"])) {
        if (is_numeric($_POST["meta-box-lat"]))
            $meta_box_long_value = sanitize_text_field($_POST["meta-box-long"]);
    }
    update_post_meta($post_id, "meta-box-long", $meta_box_long_value);

    if (isset($_POST["meta-box-zoom"])) {
        if (is_numeric($_POST["meta-box-lat"]))
            $meta_box_zoom_value = sanitize_text_field($_POST["meta-box-zoom"]);
    }
    update_post_meta($post_id, "meta-box-zoom", $meta_box_zoom_value);
}

function inject_map_code($data) {
    global $post;
    // start Simple Google Maps Plugin
    $gmaps_lat = get_post_meta($post->ID, 'meta-box-lat');
    $gmaps_long = get_post_meta($post->ID, 'meta-box-long');
    $gmaps_zoom = get_post_meta($post->ID, 'meta-box-zoom');
    if ($gmaps_lat > 0 && $gmaps_long > 0 && $gmaps_zoom > 0) {
        $output = $data . "
        <script src = 'http://maps.googleapis.com/maps/api/js?key=".esc_attr(get_option('sgm_api_key'))."'></script>
        <script>
            function initialize() {
                var mapProp = {
                    center: new google.maps.LatLng(" . htmlentities($gmaps_lat[0]) . ", " . htmlentities($gmaps_long[0]) . "),
                    zoom: " . htmlentities($gmaps_zoom[0]) . ",
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                var map = new google.maps.Map(document.getElementById('googleMap'), mapProp);
            }
            google.maps.event.addDomListener(window, 'load', initialize);
        </script>
        <div id='googleMap' style='width:500px;height:380px;'></div>
        ";
    }
    // end Simple Google Maps Plugin
    return $output;
}

function sgm_menu() {
    add_options_page(
            'Simple Google Maps', 'Simple Google Maps', 'manage_options', 'sgm-plugin.php', 'sgm_settings_page'
    );
}

function sgm_settings_page() {
    ?>
    <div class="wrap">
        <h2>Simple Google Maps settings</h2>
        <form method="post" action="options.php"> 
            <?php settings_fields('sgm_options'); ?>
            <?php do_settings_sections('sgm_options'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Your Google Maps API Key (you can obtain one <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key#key">here</a>)</th>
                    <td><input type="text" name="sgm_api_key" value="<?php echo esc_attr(get_option('sgm_api_key')); ?>" /></td>
                </tr>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    </div>
    <?php
}

function sgm_register_setting() {
    register_setting('sgm_options', 'sgm_api_key', 'htmlspecialchars');
}

add_action('admin_init', 'sgm_register_setting');
add_action('admin_menu', 'sgm_menu');
add_action("save_post", "save_gmaps_meta_box", 10, 3);
add_action("add_meta_boxes", "add_gmaps_meta_box");
add_filter('the_content', 'inject_map_code');

