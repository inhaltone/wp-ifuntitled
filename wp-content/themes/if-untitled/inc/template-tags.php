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

/**
 * The 11-character video ID of a YouTube URL (watch, youtu.be, embed, shorts, live), or '' if it isn't one.
 */
function if_untitled_youtube_id(string $url): string
{
    $pattern = '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~';

    return preg_match($pattern, $url, $match) ? $match[1] : '';
}

/**
 * A column range "start-end" (content_grid) as [start, end], or null if invalid.
 *
 * @return array{0: int, 1: int}|null
 */
function if_untitled_column_range(string $range): ?array
{
    if (!preg_match('/^([1-5])-([1-5])$/', $range, $match) || $match[1] > $match[2]) {
        return null;
    }
    return [(int) $match[1], (int) $match[2]];
}
