<?php
/**
 * Content grid (Project, Journal entry, Home): rows of image and/or text blocks on the 5-column grid.
 * $args: ['rows' => if_untitled_content_grid()]. Blocks are output in column order so they stack in reading order.
 *
 * @package if-untitled
 */

$rows = $args['rows'] ?? [];
if (!$rows) {
    return;
}
?>
<div class="content-grid">
    <?php foreach ($rows as $row) :
        $link   = $row['link'];
        $blocks = array_filter(['image' => $row['image'], 'text' => $row['text']]);
        uasort($blocks, fn(array $a, array $b): int => $a['start'] <=> $b['start']);
        ?>
        <div class="content-grid__row grid">
            <?php foreach ($blocks as $kind => $block) :
                $place = sprintf('--col-start: %d; --col-span: %d', $block['start'], $block['span']);
                ?>
                <?php if ($kind === 'image') : ?>
                    <div class="content-grid__media grid__item" style="<?php echo esc_attr($place); ?>">
                        <?php if ($link) : ?>
                            <?php // The heading carries the link text when there is one; the image link is then a duplicate. ?>
                            <a class="content-grid__link" href="<?php echo esc_url($link['url']); ?>"<?php echo $row['text'] ? ' tabindex="-1" aria-hidden="true"' : ''; ?>>
                        <?php endif; ?>
                        <?php
                        if_untitled_component('image', [
                            'id'      => (int) ($block['block']['image'] ?? 0),
                            'context' => $block['context'],
                        ]);
                        ?>
                        <?php if ($link) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else :
                    $classes = sprintf(
                        'content-grid__text grid__item content-grid__text--%s content-grid__text--box-%s content-grid__text--x-%s content-grid__text--y-%s',
                        $block['size'],
                        $block['box'],
                        $block['align_x'],
                        $block['align_y']
                    );
                    ?>
                    <div class="<?php echo esc_attr($classes); ?>" style="<?php echo esc_attr($place); ?>">
                        <?php if ($block['heading'] !== '') : ?>
                            <h2 class="content-grid__heading">
                                <?php if ($link) : ?>
                                    <a class="content-grid__link" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($block['heading']); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($block['heading']); ?>
                                <?php endif; ?>
                            </h2>
                        <?php endif; ?>
                        <?php if (trim($block['body']) !== '') : ?>
                            <div class="content-grid__body"><?php echo wp_kses_post($block['body']); ?></div>
                        <?php endif; ?>
                        <?php if ($link && $block['heading'] === '') : ?>
                            <a class="content-grid__link content-grid__more" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
