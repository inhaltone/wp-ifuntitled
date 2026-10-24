<?php
/**
 * Small reusable helpers for templates. Prefix everything with `if_untitled_`.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

/**
 * Whether a date string is in the past.
 */
function if_untitled_is_date_passed(string $date): bool
{
    try {
        return new DateTimeImmutable($date) < new DateTimeImmutable('now', wp_timezone());
    } catch (Exception) {
        return false;
    }
}

/**
 * Render a component from /components with typed args.
 * Usage: if_untitled_component('post-card', ['post' => $post]);
 */
function if_untitled_component(string $name, array $args = []): void
{
    get_template_part('components/' . $name, null, $args);
}
