<?php
/**
 * Contact map: Google map (dark black-and-white style, no place labels, one marker per location) filling the first viewport, with
 * the title, intro and locations in a panel over col 5. The map is loaded by src/js/components/ContactMap.js
 * (lazy, only where [data-contact-map] exists); without a key or JS the panel is shown on the dark background.
 * $args: ['title' => string, 'text' => string (HTML)]
 *
 * Key and Map ID: if_untitled_google_maps() (Theme Options → Google Maps, overridable by wp-config.php constants). Without
 * a Map ID the style is set in code (ContactMap.js); a Map ID switches to its Google Cloud style instead.
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
$maps      = if_untitled_google_maps();
$key       = $maps['key'];
$map_id    = $maps['map_id'];
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
