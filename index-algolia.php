<?php
/*
    Plugin Name: Index on Algolia
    Description: Allow to index Wordpress content in Algolia
    Author: Vigicorp
    Version: 1.1
    Author URI: https://www.vigicorp.fr
*/

use Algolia\AlgoliaSearch\SearchClient;
use IndexAlgolia\Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('IND_ALG_DIR', plugin_dir_path(__FILE__));
define('IND_ALG_VERSION', '1.1');

require_once __DIR__.'/vendor/autoload.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__.'/wp-cli.php';
}

new IndexAlgolia();

class IndexAlgolia
{
    private static SearchClient $searchClient;

    public function __construct()
    {
        add_action("admin_menu", [$this, 'adminMenu']);
        add_action('save_post', [$this, 'indexPost'], 10, 3);
        add_action('update_post_meta', [$this, 'indexPostFromMeta'], 10, 4);
        add_action('wp_head', [$this, 'addJSVars']);
    }

    public function adminMenu()
    {
        add_menu_page(
            __('Algolia', 'index-algolia'),
            __('Algolia', 'index-algolia'),
            'manage_options',
            'index-algolia-menu',
            '',
            'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MDAgNTAwLjM0Ij48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6I2ZmZjt9PC9zdHlsZT48L2RlZnM+PHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMjUwLDBDMTEzLjM4LDAsMiwxMTAuMTYsLjAzLDI0Ni4zMmMtMiwxMzguMjksMTEwLjE5LDI1Mi44NywyNDguNDksMjUzLjY3LDQyLjcxLC4yNSw4My44NS0xMC4yLDEyMC4zOC0zMC4wNSwzLjU2LTEuOTMsNC4xMS02LjgzLDEuMDgtOS41MmwtMjMuMzktMjAuNzRjLTQuNzUtNC4yMi0xMS41Mi01LjQxLTE3LjM3LTIuOTItMjUuNSwxMC44NS01My4yMSwxNi4zOS04MS43NiwxNi4wNC0xMTEuNzUtMS4zNy0yMDIuMDQtOTQuMzUtMjAwLjI2LTIwNi4xLDEuNzYtMTEwLjMzLDkyLjA2LTE5OS41NSwyMDIuOC0xOTkuNTVoMjAyLjgzVjQwNy42OGwtMTE1LjA4LTEwMi4yNWMtMy43Mi0zLjMxLTkuNDMtMi42Ni0xMi40MywxLjMxLTE4LjQ3LDI0LjQ2LTQ4LjU2LDM5LjY3LTgxLjk4LDM3LjM2LTQ2LjM2LTMuMi04My45Mi00MC41Mi04Ny40LTg2Ljg2LTQuMTUtNTUuMjgsMzkuNjUtMTAxLjU4LDk0LjA3LTEwMS41OCw0OS4yMSwwLDg5Ljc0LDM3Ljg4LDkzLjk3LDg2LjAxLC4zOCw0LjI4LDIuMzEsOC4yOCw1LjUzLDExLjEzbDI5Ljk3LDI2LjU3YzMuNCwzLjAxLDguOCwxLjE3LDkuNjMtMy4zLDIuMTYtMTEuNTUsMi45Mi0yMy42LDIuMDctMzUuOTUtNC44My03MC4zOS02MS44NC0xMjcuMDEtMTMyLjI2LTEzMS4zNS04MC43My00Ljk4LTE0OC4yMyw1OC4xOC0xNTAuMzcsMTM3LjM1LTIuMDksNzcuMTUsNjEuMTIsMTQzLjY2LDEzOC4yOCwxNDUuMzYsMzIuMjEsLjcxLDYyLjA3LTkuNDIsODYuMi0yNi45N2wxNTAuMzYsMTMzLjI5YzYuNDUsNS43MSwxNi42MiwxLjE0LDE2LjYyLTcuNDhWOS40OUM1MDAsNC4yNSw0OTUuNzUsMCw0OTAuNTEsMEgyNTBaIi8+PC9zdmc+'
        );

        // Settings page
        add_submenu_page(
            'index-algolia-menu',
            __('Settings', 'index-algolia'),
            __('Settings', 'index-algolia'),
            'manage_options',
            'index-algolia-settings',
            [$this, 'settingsPage'],
        );

        // Indices management page
        add_submenu_page(
            'index-algolia-menu',
            __('Indices', 'index-algolia'),
            __('Indices', 'index-algolia'),
            'manage_options',
            'index-algolia-indices',
            [$this, 'indicesPage'],
        );

        // Remove useless auto sub page
        remove_submenu_page('index-algolia-menu', 'index-algolia-menu');
    }

    public function settingsPage()
    {
        require IND_ALG_DIR.'includes/settings.php';
    }

    public function indicesPage()
    {
        require IND_ALG_DIR.'includes/indices.php';
    }

    public static function getSearchClient()
    {
        if (!isset(self::$searchClient)) {
            self::$searchClient = SearchClient::create(get_option('index_algolia_application_id'), get_option('index_algolia_write_api_key'));;
        }

        return self::$searchClient;
    }

    public function indexPost($id, WP_Post $post, $update)
    {
        if (!wp_is_post_revision($id) && !wp_is_post_autosave($id)) {
            $handler = new Handler();
            $handler->indexPost($post);
        }
    }

    public function indexPostFromMeta($meta_id, $object_id, $meta_key, $_meta_value)
    {
        $post = get_post($object_id);

        $handler = new Handler();
        $handler->indexPost($post);
    }

    public function addJSVars()
    {
        if (get_option('index_algolia_js_vars') != 1) {
            return;
        }

        require IND_ALG_DIR.'/templates/js-script.php';
    }
}
