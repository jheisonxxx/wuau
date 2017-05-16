<div id="modal-search-wrapper" class="modal-search-wrapper">
    <form method="get" class="searchform" action="<?php print esc_url(home_url('/')); ?>">
        <div class="searchform-wrapper">        
            <label class="screen-reader-text"><?php esc_html_e('Search for:', 'AZEXO'); ?></label>
            <input type="text" value="<?php print get_search_query(); ?>" name="s" placeholder="<?php esc_html_e('Type to search', 'AZEXO'); ?>" />
            <div class="submit"><input type="submit" value="<?php esc_attr_e('Search', 'AZEXO'); ?>"></div>
        </div>
    </form>
    <a href="#" class="trigger modal-search" data-trigger-class="open" data-trigger-on="#modal-search-wrapper" data-trigger-off="#modal-search-wrapper"></a>
</div>

