<?php

use IndexAlgolia\Handler;

/**
 * Command for indexing and handle settings push / backup
 */
class IndexAlgolia_Command extends WP_CLI_Command
{
    /**
     * Import data into algolia
     *
     * ## Options
     *
     * [--post-types=<post_types>]
     * : Comma-separated list of post type names
     *
     */
    public function import($args, $assoc_args)
    {
        // Get indices
        if (!empty($assoc_args['post-types'])) {
            $indices = explode(',', $assoc_args['post-types']);
        } else {
            $indices = get_option('index_algolia_indices', []);
        }

        if (empty($indices)) {
            WP_CLI::line("No data to import.\nPlease configure the post-types in the module configuration page or list indices manually with argument '--post-types'.");
        } else {
            $handler = new Handler();

            foreach ($indices as $indice) {
                WP_CLI::line(sprintf(__("Importing data for %s"), $indice));

                try {
                    $result = $handler->reindexIndice($indice);

                    WP_CLI::success(sprintf(__('%d items successfully imported into %s in %s.', 'index-algolia'), $result['count'], $result['indice_name'], date('i\m s\s', $result['execution_time'])));
                } catch (Exception $e) {
                    WP_CLI::error($e->getMessage(), false);
                }

                WP_CLI::line('');
            }
        }
    }

    /**
     * Backup Algolia settings into JSON files
     *
     * ## Options
     *
     * [--post-types=<post_types>]
     * : Comma-separated list of post type names
     *
     */
    public function settings_backup($args, $assoc_args)
    {
        // Get indices
        if (!empty($assoc_args['post-types'])) {
            $indices = explode(',', $assoc_args['post-types']);
        } else {
            $indices = get_option('index_algolia_indices', []);
        }

        if (empty($indices)) {
            WP_CLI::line("No data to import.\nPlease configure the post-types in the module configuration page or list indices manually with argument '--post-types'.");
        } else {
            $handler = new Handler();

            foreach ($indices as $indice) {
                WP_CLI::line(sprintf(__("Backup settings for %s"), $indice));

                try {
                    $backupPath = $handler->backupSettings($indice);

                    WP_CLI::success(sprintf(__('Algolia settings have been saved in the file %s.', 'index-algolia'), $backupPath));
                } catch (Exception $e) {
                    WP_CLI::error($e->getMessage(), false);
                }

                WP_CLI::line('');
            }
        }
    }

    /**
     * Push settings to Algolia from JSON files
     *
     * ## Options
     *
     * [--post-types=<post_types>]
     * : Comma-separated list of post type names
     *
     */
    public function settings_push($args, $assoc_args)
    {
        // Get indices
        if (!empty($assoc_args['post-types'])) {
            $indices = explode(',', $assoc_args['post-types']);
        } else {
            $indices = get_option('index_algolia_indices', []);
        }

        if (empty($indices)) {
            WP_CLI::line("No data to import.\nPlease configure the post-types in the module configuration page or list indices manually with argument '--post-types'.");
        } else {
            $handler = new Handler();

            foreach ($indices as $indice) {
                WP_CLI::line(sprintf(__("Push settings for %s"), $indice));

                try {
                    $pushResult = $handler->pushSettings($indice);

                    if ($pushResult) {
                        WP_CLI::success(__('Algolia settings have been push.', 'index-algolia'));
                    } else {
                        WP_CLI::error(__('An error occurred during setting push.', 'index-algolia'));
                    }
                } catch (Exception $e) {
                    WP_CLI::error($e->getMessage(), false);
                }

                WP_CLI::line('');
            }
        }
    }
}

WP_CLI::add_command('index-algolia', 'IndexAlgolia_Command');
