<?php
/**
 * Featured items side by side in one row across the section content (Studio / Advisory): image (4:3), title linking
 * to the item, short description.
 * $args: ['items' => array<int, array{title: string, description: string, image: int, url: string}>]
 *
 * @package if-untitled
 */

$items = $args['items'] ?? [];
if (!$items) {
    return;
}
$context = 'span-' . max(1, intdiv(4, count($items)));
?>
<ul class="featured">
    <?php foreach ($items as $item) : ?>
        <li class="featured__item">
            <?php if ($item['image']) : ?>
                <?php if ($item['url']) : ?>
                    <?php // The title names the same target. ?>
                    <a class="featured__media" href="<?php echo esc_url($item['url']); ?>" tabindex="-1" aria-hidden="true">
                <?php endif; ?>
                <?php if_untitled_component('image', ['id' => $item['image'], 'context' => $context, 'ratio' => '4x3']); ?>
                <?php if ($item['url']) : ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <h3 class="featured__title">
                <?php if ($item['url']) : ?>
                    <a class="featured__link" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                <?php else : ?>
                    <?php echo esc_html($item['title']); ?>
                <?php endif; ?>
            </h3>
            <?php if (trim($item['description']) !== '') : ?>
                <div class="featured__description"><?php echo wp_kses_post(wpautop($item['description'])); ?></div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
