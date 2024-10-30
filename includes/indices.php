<?php

use IndexAlgolia\Handler;
use IndexAlgolia\Tools;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$indicePageUrl = menu_page_url('index-algolia-indices', false);
$postTypes = get_post_types(['public' => 'true'], 'objects');
$indicesGlobals = get_option('index_algolia_glob', []);

if (!empty($_GET) && isset($_GET['_ind_alg_nonce']) && wp_verify_nonce($_GET['_ind_alg_nonce'], 'ind-alg-admin-nonce')) {
    if (isset($_GET['reindex'])) {
        $handler = new Handler();
        $reindexResult = $handler->reindexIndice($_GET['reindex'], $_GET['isGlob'] ?? false);
    }
    if (isset($_GET['backup'])) {
        $handler = new Handler();
        $backupPath = $handler->backupSettings($_GET['backup']);
    }
    if (isset($_GET['push'])) {
        $handler = new Handler();
        $pushResult = $handler->pushSettings($_GET['push']);
    }
    if (isset($_GET['remove'])) {
        $handler = new Handler();
        $reindexResult = $handler->removeIndice($_GET['remove']);
    }
}

if (!empty($_POST) && isset($_POST['_ind_alg_nonce']) && wp_verify_nonce($_POST['_ind_alg_nonce'], 'ind-alg-admin-nonce')) {
    $indicesGlobForms = [];
    foreach ($_POST as $name => $value) {
        switch ($name) {
            case 'submit':
            case '_wp_http_referer':
            case '_ind_alg_nonce':
            case'indice_glob_indices':
                continue 2;
            case'indice_glob_name':
                if (!empty($_POST['indice_glob_indices'])) {
                    $indiceToSaves[$_POST['indice_glob_name']] = [
                        'name'    => $_POST['indice_glob_post_type'],
                        'indices' => $_POST['indice_glob_indices'],
                    ];
                }
                break;
            default:
                update_option('index_algolia_'.$name, $value);
                break;
        }
    }
    if (!empty($indiceToSaves)) {
        foreach ($indicesGlobals as $key => $indicesGlobal) {
            foreach ($indiceToSaves as $keySave => $indiceToSave) {
                if ($key !== $keySave) {
                    $indiceToSaves[$key] = $indicesGlobal;
                }
            }
        }

        update_option('index_algolia_glob', $indiceToSaves);
    }

    if (empty($_POST['indices'])) {
        update_option('algolia_indexing_indices', []);
    }
}

?>

<div class="wrap">
    <h1><?php echo __('Algolia', 'index-algolia'); ?></h1>

    <?php if (isset($reindexResult)): ?>
        <div class="notice notice-success">
            <p><?php printf(__('%d items successfully imported into <i>%s</i> in %s.', 'index-algolia'), $reindexResult['count'], $reindexResult['indice_name'], date('i\m s\s', $reindexResult['execution_time'])); ?> </p>
        </div>
    <?php endif; ?>

    <?php if (isset($backupPath)): ?>
        <div class="notice notice-success">
            <p><?php printf(__('Algolia settings have been saved in the file <i>%s</i>.', 'index-algolia'), $backupPath); ?> </p>
        </div>
    <?php endif; ?>

    <?php if (isset($pushResult)): ?>
        <?php if ($pushResult): ?>
            <div class="notice notice-success">
                <p><?php echo __('Algolia settings have been push.', 'index-algolia'); ?> </p>
            </div>
        <?php else: ?>
            <div class="notice notice-error">
                <p><?php echo __('An error occurred during setting push.', 'index-algolia'); ?> </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="informations">
        <h2><?php echo __('Indices', 'index-algolia'); ?></h2>

        <form id="indices" method="post" autocomplete="off">
            <table class="widefat striped">
                <thead>
                <tr>
                    <th style="width: 100px;"><?php echo __('Enable', 'index-algolia'); ?></th>
                    <th><?php echo __('Post type', 'index-algolia'); ?></th>
                    <th><?php echo __('Indice name', 'index-algolia'); ?></th>
                    <th style="text-align: right;"><?php echo __('Actions', 'index-algolia'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($postTypes as $postType): ?>
                    <tr>
                        <td style="width: 100px;"><input name="indices[]" type="checkbox" id="ind-alg-indices-><?php echo esc_html($postType->name); ?>" value="<?php echo esc_html($postType->name); ?>"<?php echo Tools::checkIfAllowedIndice($postType->name) ? ' checked' : ''; ?>></td>
                        <th><?php echo esc_html($postType->label); ?></th>
                        <th><?php echo esc_html($postType->name); ?></th>
                        <td style="text-align: right;">
                            <?php if (Tools::checkIfAllowedIndice($postType->name)): ?>
                                <a href="<?php echo wp_nonce_url(add_query_arg('reindex', $postType->name, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button button-primary" onclick="return confirm('<?php echo __('Be careful, this action will delete all the data of the index before reindexing everything. Continue?', 'index-algolia'); ?>');"><?php echo __('Full indexation', 'index-algolia'); ?></a>
                                <a href="<?php echo wp_nonce_url(add_query_arg('backup', $postType->name, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button"><?php echo __('Backup settings', 'index-algolia'); ?></a>
                                <a href="<?php echo wp_nonce_url(add_query_arg('push', $postType->name, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button"><?php echo __('Push settings', 'index-algolia'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php wp_nonce_field('ind-alg-admin-nonce', '_ind_alg_nonce'); ?>
            <p class="submit">
                <input form="indices" type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Save Changes', 'index-algolia'); ?>">
            </p>
        </form>
    </div>

    <div class="informations">
        <h2><?php echo __('Indices Globaux', 'index-algolia'); ?></h2>

        <form id="indices_glob" method="post" autocomplete="off">
            <table class="form-table" role="presentation">
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="indice_glob_post_type"><?php echo __('Name', 'index-algolia'); ?> :</label>
                    </th>
                    <td>
                        <input type="text" id="indice_glob_post_type" name="indice_glob_post_type" required>
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="indice_glob_name"><?php echo __('Indice name', 'index-algolia'); ?> :</label>
                    </th>
                    <td>
                        <input type="text" id="indice_glob_name" name="indice_glob_name" required><br>
                    </td>
                </tr>
                <tr>
                    <th><label><?php echo __('Post types', 'index-algolia'); ?> :</label></th>
                    <td>
                        <?php foreach ($postTypes as $postType): ?>
                            <input name="indice_glob_indices[]" type="checkbox" id="indice_glob_indices-><?php echo esc_html($postType->name); ?>" value="<?php echo esc_html($postType->name); ?>"><?php echo esc_html($postType->label); ?><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>

            <?php wp_nonce_field('ind-alg-admin-nonce', '_ind_alg_nonce'); ?>
            <input form="indices_glob" type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Add global indice', 'index-algolia'); ?>">
        </form>

        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php echo __('Name', 'index-algolia'); ?></th>
                <th><?php echo __('Indice name', 'index-algolia'); ?></th>
                <th><?php echo __('Post types', 'index-algolia'); ?></th>
                <th style="text-align: right;"><?php echo __('Actions', 'index-algolia'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($indicesGlobals as $key => $indicesGlobal): ?>
                <tr>
                    <th><?php echo esc_html($indicesGlobal['name']); ?></th>
                    <th><?php echo esc_html($key); ?></th>
                    <th>
                        <ul>
                            <?php foreach ($indicesGlobal['indices'] as $indice): ?>
                                <li><?php echo esc_html($indice); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </th>
                    <td style="text-align: right;">
                        <a href="<?php echo wp_nonce_url(add_query_arg(['reindex' => $key, 'isGlob' => true], $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button button-primary" onclick="return confirm('<?php echo __('Be careful, this action will delete all the data of the index before reindexing everything. Continue?', 'index-algolia'); ?>//');"><?php echo __('Full indexation', 'index-algolia'); ?></a>
                        <a href="<?php echo wp_nonce_url(add_query_arg('backup', $key, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button"><?php echo __('Backup settings', 'index-algolia'); ?></a>
                        <a href="<?php echo wp_nonce_url(add_query_arg('push', $key, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button"><?php echo __('Push settings', 'index-algolia'); ?></a>
                        <a href="<?php echo wp_nonce_url(add_query_arg('remove', $key, $indicePageUrl), 'ind-alg-admin-nonce', '_ind_alg_nonce'); ?>" class="button"><?php echo __('Remove indice', 'index-algolia'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>


