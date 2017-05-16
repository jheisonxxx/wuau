<?php
/*
  Field Name: Product working hours
 */
?>
<?php
global $product;
$days = array(
    '1' => esc_html__('Monday', 'AZEXO'),
    '2' => esc_html__('Tuesday', 'AZEXO'),
    '3' => esc_html__('Wednesday', 'AZEXO'),
    '4' => esc_html__('Thursday', 'AZEXO'),
    '5' => esc_html__('Friday', 'AZEXO'),
    '6' => esc_html__('Saturday', 'AZEXO'),
    '7' => esc_html__('Sunday', 'AZEXO'),
);

$working_hours = get_post_meta($product->id, 'working-hours', true);

$options = get_option(AZEXO_FRAMEWORK);

$not_empty = array_filter((array) $working_hours);

if (!empty($not_empty)):
    ?>
    <table class="working-hours">
        <?php print (isset($options['product-working-hours_prefix']) && !empty($options['product-working-hours_prefix'])) ? '<caption>' . esc_html($options['product-working-hours_prefix']) . '</caption>' : ''; ?>
        <thead>
            <tr>
                <th><?php esc_attr_e('Day', 'AZEXO'); ?></th>
                <th><?php esc_attr_e('Open', 'AZEXO'); ?></th>
                <th><?php esc_attr_e('Close', 'AZEXO'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($days as $day => $day_name) {
                ?>
                <tr>
                    <td><label><?php print $day_name; ?></label></td>
                    <?php
                    if (empty($working_hours['open-' . $day]) || empty($working_hours['close-' . $day])) {
                        ?>
                        <td class="closed" colspan="2"><?php esc_attr_e('Closed', 'AZEXO'); ?></td>
                        <?php
                    } else {
                        ?>
                        <td class="open">
                            <?php
                            print date("g:i a", strtotime(esc_html($working_hours['open-' . $day])));
                            ?>
                        </td>
                        <td class="close">
                            <?php
                            print date("g:i a", strtotime(esc_html($working_hours['close-' . $day])));
                            ?>
                        </td>
                        <?php
                    }
                    ?>

                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <?php



endif;