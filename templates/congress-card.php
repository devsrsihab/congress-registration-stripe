<?php
/**
 * Template for displaying a single congress card
 */
?>
<div class="cr-congress-card">
    <?php if (has_post_thumbnail()): ?>
        <div class="cr-congress-image">
            <?php the_post_thumbnail('medium'); ?>
        </div>
    <?php endif; ?>
    
    <h3 class="cr-congress-title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h3>
    
    <div class="cr-congress-meta">
        <?php
        $start_date = get_post_meta(get_the_ID(), 'start_date', true);
        $end_date = get_post_meta(get_the_ID(), 'end_date', true);
        $location = get_post_meta(get_the_ID(), 'location', true);
        ?>
        
        <?php if ($start_date && $end_date): ?>
            <p class="cr-congress-dates">
                <strong><?php _e('Dates:', CR_TEXT_DOMAIN); ?></strong>
                <?php echo date_i18n('F j, Y', strtotime($start_date)) . ' - ' . date_i18n('F j, Y', strtotime($end_date)); ?>
            </p>
        <?php endif; ?>
        
        <?php if ($location): ?>
            <p class="cr-congress-location">
                <strong><?php _e('Location:', CR_TEXT_DOMAIN); ?></strong>
                <?php echo esc_html($location); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="cr-congress-excerpt">
        <?php the_excerpt(); ?>
    </div>
    
    <div class="cr-congress-actions">
        <a href="<?php echo add_query_arg('congress_id', get_the_ID(), get_permalink(get_page_by_path('congress-registration'))); ?>" class="cr-btn cr-btn-primary">
            <?php _e('Register Now', CR_TEXT_DOMAIN); ?>
        </a>
        <a href="<?php the_permalink(); ?>" class="cr-btn cr-btn-secondary">
            <?php _e('Learn More', CR_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>