<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!empty($_POST) && isset($_POST['_ind_alg_nonce']) && wp_verify_nonce($_POST['_ind_alg_nonce'], 'ind-alg-admin-nonce')) {
    foreach ($_POST as $name => $value) {
        switch ($name) {
            case 'submit':
            case '_wp_http_referer':
            case '_ind_alg_nonce':
                continue 2;
            default:
                update_option('index_algolia_'.$name, sanitize_text_field($value));
                break;
        }
    }
}

?>

<div class="wrap">
  <h1><?php echo __('Algolia', 'index-algolia'); ?></h1>

  <div class="informations">
    <h2><?php echo __('Settings', 'index-algolia'); ?></h2>

    <form method="post" autocomplete="off">
      <table class="form-table">
        <tbody>
        <tr>
          <th scope="row"><label for="ind-alg-application_id"><?php echo __('Application ID', 'index-algolia'); ?></label></th>
          <td><input name="application_id" type="text" id="ind-alg-application_id" value="<?php echo esc_html(get_option('index_algolia_application_id')); ?>" class="regular-text" autocomplete="off"></td>
        </tr>
        <tr>
          <th scope="row"><label for="ind-alg-search_api_key"><?php echo __('Search API Key', 'index-algolia'); ?></label></th>
          <td><input name="search_api_key" type="text" id="ind-alg-search_api_key" value="<?php echo esc_html(get_option('index_algolia_search_api_key')); ?>" class="regular-text" autocomplete="off"></td>
        </tr>
        <tr>
          <th scope="row"><label for="ind-alg-write_api_key"><?php echo __('Write API Key', 'index-algolia'); ?></label></th>
          <td><input name="write_api_key" type="password" id="ind-alg-write_api_key" value="<?php echo esc_html(get_option('index_algolia_write_api_key')); ?>" class="regular-text" autocomplete="new-password"></td>
        </tr>
        <tr>
          <th scope="row"><label for="ind-alg-index_prefix"><?php echo __('Index prefix', 'index-algolia'); ?></label></th>
          <td><input name="index_prefix" type="text" id="ind-alg-index_prefix" value="<?php echo esc_html(get_option('index_algolia_index_prefix')); ?>" class="regular-text" autocomplete="off"></td>
        </tr>
        <tr>
          <th scope="row"><label><?php echo __('Add variables for JS usage', 'index-algolia'); ?></label></th>
          <td>
            <div class="option">
              <input type="radio" id="ind-alg-js_vars-yes" value="1" name="js_vars"<?php echo get_option('index_algolia_js_vars') == 1 ? ' checked' : ''; ?>>
              <label for="ind-alg-js_vars-yes"><?php echo __('Yes', 'index-algolia'); ?></label>
            </div>
            <div>
              <input type="radio" id="ind-alg-js_vars-no" value="0" name="js_vars"<?php echo get_option('index_algolia_js_vars') == 0 ? ' checked' : ''; ?>>
              <label for="ind-alg-js_vars-no"><?php echo __('No', 'index-algolia'); ?></label>
            </div>
        </tr>
        </tbody>
      </table>

        <?php wp_nonce_field('ind-alg-admin-nonce', '_ind_alg_nonce'); ?>

      <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Save Changes', 'index-algolia'); ?>">
      </p>
    </form>
  </div>
</div>


