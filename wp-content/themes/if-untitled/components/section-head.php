<?php
/**
 * Section with its label in col 1 (display L) and a component in cols 2–5 (Studio / Advisory: Services, Team, Clients).
 * $args: ['title' => string, 'id' => string (anchor, optional), 'content' => ['name' => component, 'args' => array]]
 *
 * @package if-untitled
 */

$title   = $args['title'] ?? '';
$content = $args['content'] ?? [];
if (empty($content['name'])) {
    return;
}
?>
<section class="section-head grid"<?php echo !empty($args['id']) ? ' id="' . esc_attr($args['id']) . '"' : ''; ?>>
    <?php if ($title) : ?>
        <h2 class="section-head__title grid__item" style="--col-start: 1; --col-span: 1"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <div class="section-head__body grid__item" style="--col-start: 2; --col-span: 4">
        <?php if_untitled_component($content['name'], $content['args'] ?? []); ?>
    </div>
</section>
