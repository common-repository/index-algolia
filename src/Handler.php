<?php

namespace IndexAlgolia;

use Exception;
use IndexAlgolia;
use WP_Post;
use WP_Query;

class Handler
{
    public function reindexIndice(string $postType, $isGlob = false): array
    {
        if (!$isGlob && !Tools::checkIfAllowedIndice($postType)) {
            throw new Exception(__('Not allowed indice', 'index-algolia'));
        }

        $time_start = microtime(true);

        $searchClient = IndexAlgolia::getSearchClient();
        $indicePrefix = get_option('index_algolia_index_prefix');

        // Get algolia index
        $index = $searchClient->initIndex($indicePrefix.$postType);

        // Clear whole object in index
        $index->clearObjects()->wait();

        $paged = 1;
        $count = 0;

        do {
            $args = [
                'posts_per_page'   => 100,
                'paged'            => $paged,
                'post_status'      => 'publish',
                'suppress_filters' => true,
            ];

            if (!$isGlob) {
                $args['post_type'] = $postType;
            } else {
                $indicesGlobs = get_option('index_algolia_glob', []);

                $indiceGlob = $indicesGlobs[$postType];

                if ($indiceGlob) {
                    $indices = $indiceGlob['indices'];
                }

                $args['post_type'] = $indices;
            }

            $posts = new WP_Query($args);

            if (!$posts->have_posts()) {
                break;
            }

            // Fill record array
            $records = [];
            foreach ($posts->posts as $post) {
                $record = (array)apply_filters('index_algolia_post_to_record', $post);

                if (!isset($record['objectID'])) {
                    $record['objectID'] = $post->ID;
                }

                $records[] = $record;
                $count++;
            }

            // Save record on algolia
            $index->saveObjects($records);

            $paged++;
        } while (true);

        $time_end = microtime(true);

        return [
            'count'          => $count,
            'indice_name'    => $indicePrefix.$postType,
            'execution_time' => $time_end - $time_start,
        ];
    }

    public function indexPost(WP_Post $post)
    {
        if (Tools::checkIfAllowedIndice($post->post_type)) {
            $searchClient = IndexAlgolia::getSearchClient();
            $indicePrefix = get_option('index_algolia_index_prefix');

            // Get algolia index
            $index = $searchClient->initIndex($indicePrefix.$post->post_type);
            $record = (array)apply_filters('index_algolia_post_to_record', $post);

            if (!isset($record['objectID'])) {
                $record['objectID'] = $post->ID;
            }

            if ('trash' == $post->post_status) {
                $index->deleteObject($record['objectID']);
            } else {
                $index->saveObject($record);
            }

            $indicesGlobs = get_option('index_algolia_glob', []);
            foreach ($indicesGlobs as $key => $indiceGlob) {
                if (in_array($post->post_type, $indiceGlob['indices'])) {
                    $this->reindexIndice($key, true);
                }
            }
        }
    }

    public function backupSettings(string $postType, $isReplica = false, $isGlob = false): string
    {
        if (!Tools::checkIfAllowedIndice($postType, $isReplica) && !$isGlob) {
            throw new Exception('Not allowed indice');
        }

        $searchClient = IndexAlgolia::getSearchClient();
        $indicePrefix = get_option('index_algolia_index_prefix');
        // Get algolia index
        $index = $searchClient->initIndex($indicePrefix.$postType);

        // Get settings
        $settings = $index->getSettings();

        // Handle replicas
        if (array_key_exists('replicas', $settings)) {
            foreach ($settings['replicas'] as $replica) {
                $replica = $this->removePrefixFromIndexName($replica);

                // Backup replica settings
                $this->backupSettings($replica, true);
            }
        }
        // Save as file
        $settingsFolder = get_stylesheet_directory().'/algolia-settings';

        if (!is_dir($settingsFolder)) {
            mkdir($settingsFolder);
        }

        $filePath = $settingsFolder."/{$postType}.json";
        file_put_contents($filePath, json_encode($settings, JSON_PRETTY_PRINT));

        return $filePath;
    }

    public function pushSettings(string $postType, $isReplica = false, $isGlob = false): bool
    {
        if (!Tools::checkIfAllowedIndice($postType, $isReplica) && !$isGlob) {
            throw new Exception('Not allowed indice');
        }

        $searchClient = IndexAlgolia::getSearchClient();
        $indicePrefix = get_option('index_algolia_index_prefix');

        // Get algolia index

        $filePath = get_stylesheet_directory()."/algolia-settings/{$postType}.json";

        if (is_readable($filePath)) {
            $index = $searchClient->initIndex($indicePrefix.$postType);
            $settings = json_decode(file_get_contents($filePath), true);

            if (array_key_exists('replicas', $settings)) {
                foreach ($settings['replicas'] as $replica) {
                    $replica = $indicePrefix.$replica;
                    // Backup replica settings
                    $this->pushSettings($replica, true);
                }
            }

            $index->setSettings($settings);

            return true;
        }

        return false;
    }

    public function removeIndice(string $postType): void
    {
        $indiceGlobs = get_option('index_algolia_glob');

        foreach ($indiceGlobs as $key => $indiceGlob) {
            if ($key == $postType) {
                unset($indiceGlobs[$key]);
            }
        }
        update_option('index_algolia_glob', $indiceGlobs);

        $searchClient = IndexAlgolia::getSearchClient();
        $indicePrefix = get_option('index_algolia_index_prefix');

        // Remove algolia index
        $index = $searchClient->initIndex($indicePrefix.$postType);
        $index->delete();
    }

    /**
     * @param string $indexName
     *
     * @return string|string[]|null
     */
    private function removePrefixFromIndexName($indexName)
    {
        $indicePrefix = get_option('index_algolia_index_prefix');

        return preg_replace('/^'.preg_quote($indicePrefix, '/').'/', '', $indexName);
    }
}
