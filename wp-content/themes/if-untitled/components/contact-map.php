<?php
/**
 * Contact map: Google map (dark cloud style via Map ID, one marker per location) filling the first viewport, with
 * the title, intro and locations in a panel over col 5. The map is loaded by src/js/components/ContactMap.js
 * (lazy, only where [data-contact-map] exists); without a key or JS the panel is shown on the dark background.
 * $args: ['title' => string, 'text' => string (HTML)]
 *
 * Constants (wp-config.php): IF_UNTITLED_GOOGLE_MAPS_KEY (required for the map), IF_UNTITLED_GOOGLE_MAPS_ID (Map ID
 * with the dark style; falls back to Google's DEMO_MAP_ID).
 *
 * @package if-untitled
 */

$locations = if_untitled_locations();
$markers   = array_values(array_filter(array_map(
    fn(array $location): ?array => $location['lat'] !== null && $location['lng'] !== null
        ? ['lat' => $location['lat'], 'lng' => $location['lng'], 'title' => $location['city']]
        : null,
    $locations
)));
$key       = defined('IF_UNTITLED_GOOGLE_MAPS_KEY') ? (string) IF_UNTITLED_GOOGLE_MAPS_KEY : '';
$map_id    = defined('IF_UNTITLED_GOOGLE_MAPS_ID') ? (string) IF_UNTITLED_GOOGLE_MAPS_ID : 'DEMO_MAP_ID';
?>
<section class="contact-map">
    <?php if ($key && $markers) : ?>
        <div class="contact-map__canvas" data-contact-map data-key="<?php echo esc_attr($key); ?>" data-map-id="<?php echo esc_attr($map_id); ?>" data-markers="<?php echo esc_attr(wp_json_encode($markers)); ?>" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="contact-map__overlay grid">
        <div class="contact-map__panel grid__item" style="--col-start: 5; --col-span: 1">
            <?php if (!empty($args['title'])) : ?>
                <h1 class="contact-map__title"><?php echo esc_html($args['title']); ?></h1>
            <?php endif; ?>
            <?php if (!empty($args['text'])) : ?>
                <div class="contact-map__text"><?php echo wp_kses_post($args['text']); ?></div>
            <?php endif; ?>
            <?php foreach ($locations as $location) : ?>
                <?php if_untitled_component('location', ['location' => $location]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
