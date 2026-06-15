<?php
/**
 * Search Form Template
 *
 * @package NCLLC_Pro
 */
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <div class="search-form-inner">
        <label class="screen-reader-text" for="s"><?php esc_html_e('Search for:', 'ncllc-pro'); ?></label>
        <input
            type="search"
            id="s"
            class="search-field"
            placeholder="<?php echo esc_attr_x('Search…', 'placeholder', 'ncllc-pro'); ?>"
            value="<?php echo esc_attr(get_search_query()); ?>"
            name="s"
        />
        <button type="submit" class="search-submit">
            <?php echo esc_html_x('Search', 'submit button', 'ncllc-pro'); ?>
        </button>
    </div>
</form>
