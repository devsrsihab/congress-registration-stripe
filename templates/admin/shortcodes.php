<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php _e('Available Shortcodes', 'crscngres'); ?></h1>
    
    <div class="crs-admin-card">
        <h2>[congress_registration]</h2>
        <p><?php _e('Displays the 8-step congress registration form.', 'crscngres'); ?></p>
        <p><strong><?php _e('Usage:', 'crscngres'); ?></strong> <code>[congress_registration]</code></p>
        <p><strong><?php _e('Parameters:', 'crscngres'); ?></strong> <?php _e('None (congress ID is taken from URL: ?congress_id=123)', 'crscngres'); ?></p>
    </div>
    
    <div class="crs-admin-card">
        <h2>[my_registrations]</h2>
        <p><?php _e('Displays the user\'s registrations and documents dashboard.', 'crscngres'); ?></p>
        <p><strong><?php _e('Usage:', 'crscngres'); ?></strong> <code>[my_registrations]</code></p>
        <p><strong><?php _e('Note:', 'crscngres'); ?></strong> <?php _e('User must be logged in', 'crscngres'); ?></p>
    </div>
    
    <div class="crs-admin-card">
        <h2>[congress_list]</h2>
        <p><?php _e('Displays a list of available congresses with registration buttons.', 'crscngres'); ?></p>
        <p><strong><?php _e('Usage:', 'crscngres'); ?></strong> <code>[congress_list limit="10" show_past="no"]</code></p>
    </div>
</div>

<style>
    .crs-admin-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 20px;
        margin: 20px 0;
    }
    .crs-admin-card h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        font-family: monospace;
        background: #f6f7f7;
        padding: 10px;
    }
    .crs-admin-card code {
        background: #f0f0f1;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 13px;
    }
</style>