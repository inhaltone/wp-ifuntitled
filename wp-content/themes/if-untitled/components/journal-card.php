<?php
/**
 * Journal archive card: featured image (2:3 crop), title, excerpt, entry type + date. Runs inside The Loop.
 * $args: ['priority' => bool (first card only — LCP)]
 *
 * @package if-untitled
 */

$categories = get_the_category();
$type       = $categories ? $categories[0]->name : '';
?>
<article class="journal-card" data-infinite-scroll-item>
    <a class="journal-card__link" href="<?php the_permalink(); ?>">
        <?php
        if_untitled_component('image', [
            'id'       => (int) get_post_thumbnail_id(),
            'context'  => 'card',
            'ratio'    => '2x3',
            'alt'      => '',
            'priority' => !empty($args['priority']),
        ]);
        ?>
        <h2 class="journal-card__title"><?php the_title(); ?></h2>
    </a>
    <?php if (has_excerpt()) : ?>
        <p class="journal-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
    <?php endif; ?>
    <p class="journal-card__meta">
        <?php if ($type) : ?>
            <span class="journal-card__type"><?php echo esc_html($type); ?></span>
        <?php endif; ?>
        <time class="journal-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('F Y')); ?></time>
    </p>
</article>
