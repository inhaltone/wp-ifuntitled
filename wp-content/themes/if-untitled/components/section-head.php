<?php
/**
 * Section with its label in col 1 (display L) and a component in cols 2–5 (Studio / Advisory: Team, Clients, Featured),
 * or below the label across all columns with `full` (Services content grid).
 * $args: ['title' => string, 'id' => string (anchor, optional), 'full' => bool,
 *         'content' => ['name' => component, 'args' => array]]
 *
 * @package if-untitled
 */

$title   = $args['title'] ?? '';
$content = $args['content'] ?? [];
$full    = !empty($args['full']);
if (empty($content['name'])) {
    return;
}
?>
<section class="section-head grid<?php echo $full ? ' section-head--full' : ''; ?>"<?php echo !empty($args['id']) ? ' id="' . esc_attr($args['id']) . '"' : ''; ?>>
    <?php if ($title) : ?>
        <h2 class="section-head__title grid__item" style="--col-start: 1; --col-span: 1"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <div class="section-head__body grid__item" style="<?php echo $full ? '--col-start: 1; --col-span: 5' : '--col-start: 2; --col-span: 4'; ?>">
        <?php if_untitled_component($content['name'], $content['args'] ?? []); ?>
    </div>
</section>
