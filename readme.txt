=== Index on Algolia ===
Contributors: theurtin
Requires at least: 5.0
Tested up to: 6.5.2
Requires PHP: 8.0
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow to index posts into Algolia indices

== Description ==
Allow to index posts into Algolia indices

You will access two pages :
- Settings : need to fill all informations needed for Algolia to work (Application ID, Search API Key, ...)
- Indices : you can choose post types you want to index

For each indice, you can :
- Index whole posts existing (useful when enable new post type or to fix some previous missing posts)
- Backup settings in JSON file in order to sync between multiple environments
- Push settings from JSON into Algolia

These action can be done from administration or with WP CLI (see `wp index-algolia` command)

All post created, updated or deleted will be automatically synchronized with Algolia.

By default, plugin while index data existing in WP_Post entity.
In order to control which data you would index, you can use `index_algolia_post_to_record` filter which give you post object and waiting an array in return.
If you use filter, your return array SHOULD at least have `objectID` value.

Exemple :
<?php
    function index_algolia_post_to_record(WP_Post $post)
    {
        $record = [
            'objectID'   => $post->ID,
            'post_title' => $post->post_title,
            'post_date'  => get_post_time( 'U', false, $post ),
            'link'       => get_the_permalink($post),
        ];

        return $record;
    }
?>

== Installation ==
1. Upload plugin folder to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Update url in plugin settings page


== Changelog ==
= 1.0 =
* Initial release.
= 1.1 =
* Handle backup & push settings for replica
= 1.2 =
* Allow to create indice for multiple post types
