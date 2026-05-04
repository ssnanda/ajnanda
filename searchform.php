<?php
/**
 * Search Form Template
 * 
 * @package NCLLC_Pro
 */
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>" style="max-width: 500px; margin: 0 auto;">
    <div style="display: flex; gap: 0.5rem;">
        <input 
            type="search" 
            class="search-field" 
            placeholder="<?php echo esc_attr_x('Search...', 'placeholder', 'ncllc-pro'); ?>" 
            value="<?php echo get_search_query(); ?>" 
            name="s"
            style="flex: 1; padding: 0.75rem 1rem; border: 2px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; transition: all 0.3s ease;"
            onfocus="this.style.borderColor='var(--primary)'; this.style.outline='none';"
            onblur="this.style.borderColor='var(--gray-300)';"
        />
        <button 
            type="submit" 
            class="search-submit"
            style="padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
            onmouseover="this.style.background='var(--primary-dark)'; this.style.transform='translateY(-2px)';"
            onmouseout="this.style.background='var(--primary)'; this.style.transform='translateY(0)';"
        >
            <?php echo esc_html_x('Search', 'submit button', 'ncllc-pro'); ?>
        </button>
    </div>
</form>
