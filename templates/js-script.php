<?php $prefix = get_option('index_algolia_index_prefix'); ?>

<script type="text/javascript">
    var algolia_vars = {
        "application_id": "<?php echo esc_js(get_option('index_algolia_application_id')); ?>",
        "search_api_key": "<?php echo esc_js(get_option('index_algolia_search_api_key')); ?>",
        "indices": [
            <?php foreach (get_option('index_algolia_indices') as $indice): ?>
            "<?php echo esc_js($prefix . $indice); ?>",
            <?php endforeach; ?>
        ]
    };
</script>
