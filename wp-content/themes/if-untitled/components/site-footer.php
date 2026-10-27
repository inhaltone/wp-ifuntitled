<?php
/**
 * Site footer: menu, Studio services, addresses, enquiries, social + portals (Theme Options), copyright.
 * Empty columns are skipped.
 *
 * @package if-untitled
 */

$services  = if_untitled_studio_services();
$locations = if_untitled_locations();
$enquiries = if_untitled_enquiries();
$lists     = [
    __('Social', 'if-untitled')  => if_untitled_external_links('social'),
    __('Portals', 'if-untitled') => if_untitled_external_links('portals'),
];
?>
<footer class="site-footer">
    <div class="site-footer__columns grid">
        <?php if (has_nav_menu('footer_menu')) : ?>
            <div class="site-footer__column">
                <h2 class="site-footer__title"><?php esc_html_e('Menu', 'if-untitled'); ?></h2>
                <?php
                wp_nav_menu([
                    'theme_location'       => 'footer_menu',
                    'container'            => 'nav',
                    'container_class'      => 'site-footer__nav',
                    'container_aria_label' => __('Footer', 'if-untitled'),
                    'menu_class'           => 'site-footer__list',
                    'menu_id'              => '',
                    'depth'                => 1,
                    'fallback_cb'          => false,
                    'if_untitled_block'    => 'site-footer',
                ]);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($services) : ?>
            <div class="site-footer__column">
                <h2 class="site-footer__title"><?php esc_html_e('Studio', 'if-untitled'); ?></h2>
                <ul class="site-footer__list">
                    <?php foreach ($services as $service) : ?>
                        <li class="site-footer__item"><a class="site-footer__link" href="<?php echo esc_url($service['url']); ?>"><?php echo esc_html($service['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($locations) : ?>
            <div class="site-footer__column">
                <h2 class="site-footer__title"><?php esc_html_e('Address', 'if-untitled'); ?></h2>
                <?php foreach ($locations as $location) : ?>
                    <address class="site-footer__address">
                        <?php foreach ($location['address'] ?: [$location['city']] as $line) : ?>
                            <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                        <?php if ($location['phone']) : ?>
                            <a class="site-footer__line" href="tel:<?php echo esc_attr($location['phone_href']); ?>"><?php echo esc_html($location['phone']); ?></a>
                        <?php endif; ?>
                        <?php if ($location['email']) : ?>
                            <a class="site-footer__line" href="mailto:<?php echo esc_attr($location['email']); ?>"><?php echo esc_html($location['email']); ?></a>
                        <?php endif; ?>
                    </address>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($enquiries) : ?>
            <div class="site-footer__column">
                <h2 class="site-footer__title"><?php esc_html_e('Enquiries', 'if-untitled'); ?></h2>
                <ul class="site-footer__list">
                    <?php foreach ($enquiries as $enquiry) : ?>
                        <li class="site-footer__item"><a class="site-footer__link" href="<?php echo esc_url($enquiry['href'], ['mailto', 'tel', 'https']); ?>"><?php echo esc_html($enquiry['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (array_filter($lists)) : ?>
            <div class="site-footer__column">
                <?php foreach (array_filter($lists) as $title => $links) : ?>
                    <div class="site-footer__group">
                        <h2 class="site-footer__title"><?php echo esc_html($title); ?></h2>
                        <ul class="site-footer__list">
                            <?php foreach ($links as $link) : ?>
                                <li class="site-footer__item"><a class="site-footer__link" href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($link['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <p class="site-footer__copy">
        <?php
        /* translators: %s: current year. */
        echo esc_html(sprintf(__('©%s IF_UNTITLED. All rights reserved.', 'if-untitled'), wp_date('Y')));
        ?>
    </p>
</footer>
