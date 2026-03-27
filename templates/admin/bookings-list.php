<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Bookings Management</h1>
    <form method="post">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
        <?php
        $bookings_table->search_box('Search Bookings', 'booking-search');
        $bookings_table->display();
        ?>
    </form>
</div>