<?php
/**
 * Client logos in a row (Theme Options `clients`), each linking to the client's site in a new tab.
 * Without a logo the client's name is shown instead.
 *
 * @package if-untitled
 */

$clients = if_untitled_clients();
if (!$clients) {
    return;
}
?>
<ul class="clients">
    <?php foreach ($clients as $client) :
        $tag = $client['url'] ? 'a' : 'span';
        ?>
        <li class="clients__item">
            <<?php echo $tag; ?> class="clients__link"<?php echo $client['url'] ? ' href="' . esc_url($client['url']) . '" target="_blank" rel="noopener"' : ''; ?>>
                <?php if ($client['logo']) : ?>
                    <?php echo if_untitled_image($client['logo'], 'logo', ['alt' => $client['name'], 'class' => 'clients__logo']); ?>
                <?php else : ?>
                    <span class="clients__name"><?php echo esc_html($client['name']); ?></span>
                <?php endif; ?>
            </<?php echo $tag; ?>>
        </li>
    <?php endforeach; ?>
</ul>
