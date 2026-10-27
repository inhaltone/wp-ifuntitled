<?php
/**
 * Studio services: outlined boxes, two per row, each anchored for the header submenu + footer
 * (title display M, subtitle, description, optional image).
 * $args: ['services' => if_untitled_studio_services()]
 *
 * @package if-untitled
 */

$services = $args['services'] ?? [];
if (!$services) {
    return;
}
?>
<ul class="services">
    <?php foreach ($services as $service) : ?>
        <li class="services__item" id="<?php echo esc_attr($service['anchor']); ?>">
            <?php if ($service['image']) : ?>
                <?php if_untitled_component('image', ['id' => $service['image'], 'context' => 'span-2', 'ratio' => '3x2']); ?>
            <?php endif; ?>
            <h3 class="services__title"><?php echo esc_html($service['title']); ?></h3>
            <?php if ($service['subtitle'] !== '') : ?>
                <p class="services__subtitle"><?php echo esc_html($service['subtitle']); ?></p>
            <?php endif; ?>
            <?php if (trim($service['description']) !== '') : ?>
                <div class="services__description"><?php echo wp_kses_post($service['description']); ?></div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
