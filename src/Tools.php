<?php

namespace IndexAlgolia;

use WP_Post;

class Tools
{
    public static function getAcfDataForPost(WP_Post|int $post)
    {
        global $wpdb;

        if (!is_int($post)) {
            $post = $post->ID;
        }

        $request = "SELECT *  FROM ".$wpdb->postmeta." WHERE post_id =".$post." AND substring(meta_key, 1, 1) <> '_'";
        $results = $wpdb->get_results($request);

        $resultsFormatted = [];
        foreach ($results as $result) {
            $resultsFormatted[$result->meta_key] = $result->meta_value;
        }

        return $resultsFormatted;
    }

    public static function checkIfAllowedIndice(string $postType, $replica = false)
    {
        if ($replica) {
            return true;
        }

        $selectedIndices = get_option('index_algolia_indices', []);
        return in_array($postType, $selectedIndices);
    }
}
