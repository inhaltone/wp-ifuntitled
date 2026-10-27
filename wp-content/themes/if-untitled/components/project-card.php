<?php
/**
 * Work archive card: featured image (natural ratio), title, metadata in 4 columns. Runs inside The Loop.
 * $args: ['priority' => bool (first card only — LCP)]
 *
 * @package if-untitled
 */
?>
<article class="project-card" data-infinite-scroll-item>
    <a class="project-card__link" href="<?php the_permalink(); ?>">
        <?php
        // The title in the same link names the target, so the image is decorative here.
        if_untitled_component('image', [
            'id'       => (int) get_post_thumbnail_id(),
            'context'  => 'card',
            'alt'      => '',
            'priority' => !empty($args['priority']),
        ]);
        ?>
        <h2 class="project-card__title"><?php the_title(); ?></h2>
    </a>
    <?php if_untitled_component('project-meta', ['post_id' => get_the_ID(), 'variant' => 'columns']); ?>
</article>
