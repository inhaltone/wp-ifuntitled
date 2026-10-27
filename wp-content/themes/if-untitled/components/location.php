<?php
/**
 * One office location (footer, Contact): address lines (or the city when there are none), phone, email.
 * $args: ['location' => if_untitled_locations() item]
 *
 * @package if-untitled
 */

$location = $args['location'] ?? null;
if (!$location) {
    return;
}
?>
<address class="location">
    <?php foreach ($location['address'] ?: array_filter([$location['city']]) as $line) : ?>
        <span class="location__line"><?php echo esc_html($line); ?></span>
    <?php endforeach; ?>
    <?php if ($location['phone']) : ?>
        <a class="location__line location__link" href="tel:<?php echo esc_attr($location['phone_href']); ?>"><?php echo esc_html($location['phone']); ?></a>
    <?php endif; ?>
    <?php if ($location['email']) : ?>
        <a class="location__line location__link" href="mailto:<?php echo esc_attr($location['email']); ?>"><?php echo esc_html($location['email']); ?></a>
    <?php endif; ?>
</address>
