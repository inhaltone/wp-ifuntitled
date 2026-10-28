<?php
/**
 * Clients (Theme Options `clients`) as a typographic list: names in a vertical list in the first column of the
 * section content (Studio / Advisory col 2), each linking to the client's site in a new tab when it has one.
 *
 * @package if-untitled
 */

$clients = if_untitled_clients();
if (!$clients) {
    return;
}
?>
<ul class="clients">
    <?php foreach ($clients as $client) : ?>
        <li class="clients__item">
            <?php if ($client['url']) : ?>
                <a class="clients__link" href="<?php echo esc_url($client['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($client['name']); ?></a>
            <?php else : ?>
                <?php echo esc_html($client['name']); ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
