<?php
/*
  Field Name: Product event date time
 */
?>
<?php

global $product;
$event_date_time = get_post_meta($product->id, 'event-date-time', true);
if ($event_date_time) {
    print '<span class="date-time">' . date_i18n(get_option('date_format'), $event_date_time) . '</span>';
}
?>