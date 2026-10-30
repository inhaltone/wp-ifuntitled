<?php
/**
 * Clients (Theme Options `clients`) as a horizontal row of logos across the section content (Studio / Advisory),
 * each linking to the client's site in a new tab when it has one. A client without a logo shows its name instead.
 * --logo-ratio (width / height) lets the CSS give every logo a similar visual weight.
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
        <li class="clients__item"<?php echo $client['logo'] ? ' style="--logo-ratio: ' . esc_attr(round($client['logo_ratio'], 3)) . '"' : ''; ?>>
            <?php if ($client['url']) : ?>
                <a class="clients__link" href="<?php echo esc_url($client['url']); ?>" target="_blank" rel="noopener">
            <?php endif; ?>
            <?php if ($client['logo']) : ?>
                <?php echo if_untitled_image($client['logo'], 'logo', ['alt' => $client['name'], 'class' => 'clients__logo']); // Core markup, already escaped. ?>
            <?php else : ?>
                <span class="clients__name"><?php echo esc_html($client['name']); ?></span>
            <?php endif; ?>
            <?php if ($client['url']) : ?>
                </a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
