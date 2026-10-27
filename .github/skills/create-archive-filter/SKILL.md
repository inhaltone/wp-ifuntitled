---
name: create-archive-filter
description: Add a taxonomy filter bar (All + terms) to a post or custom-post-type archive in the if-untitled theme using real term-archive URLs, no AJAX or plugins. Use when asked to filter projects, journal posts or any archive by category/taxonomy.
---

# Archive filter (All + taxonomy terms)

Each filter item is a **real WordPress term archive URL** — no query strings, no AJAX endpoint, no plugin.
It works without JS, is shareable/indexable and keeps native pagination per term.

## 1. Templates

The archive and its term archive render the same markup and share the archive's route id:

| Archive     | "All" template      | Term template             | `main#id`       |
| ----------- | ------------------- | ------------------------- | --------------- |
| Posts       | `home.php`          | `category.php`            | e.g. `journal`  |
| CPT `{cpt}` | `archive-{cpt}.php` | `taxonomy-{taxonomy}.php` | `archive-{cpt}` |

Create them with the `create-page-template` skill. Put the shared markup in components — don't duplicate it.

Term URLs come from the taxonomy `rewrite` slug (see `register-custom-post-type`), e.g. `/work/category/{slug}/`.
Query tweaks (posts per page, order) go in `pre_get_posts` for **both** the archive and the term archive.

## 2. Helper — `inc/template-tags.php`

```php
/**
 * Filter items for an archive: "All" + non-empty terms in their manual order.
 *
 * @return array<int, array{label: string, url: string, is_active: bool}>
 */
function if_untitled_archive_filter(string $taxonomy, string $all_url, string $all_label): array
{
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'term_order',
        'order'      => 'ASC',
    ]);
    $is_term_view = $taxonomy === 'category' ? is_category() : is_tax($taxonomy);

    $items = [['label' => $all_label, 'url' => $all_url, 'is_active' => !$is_term_view]];

    foreach (is_wp_error($terms) ? [] : $terms as $term) {
        $items[] = [
            'label'     => $term->name,
            'url'       => get_term_link($term),
            'is_active' => $is_term_view && get_queried_object_id() === $term->term_id,
        ];
    }

    return $items;
}
```

- Term order is the drag & drop order from the ordering plugin (Simple Custom Post Order → `term_order`), not
  alphabetical. Without that plugin, use an ACF number field `sort_order` on the taxonomy
  (`'meta_key' => 'sort_order', 'orderby' => 'meta_value_num'` — terms without a value are excluded).

## 3. Component — `components/archive-filter.php`

Args: `taxonomy`, `all_url`, `all_label`, `label` (nav `aria-label`).

```php
<?php
/**
 * Archive filter bar: "All" + taxonomy terms as links.
 * Args: ['taxonomy' => string, 'all_url' => string, 'all_label' => string, 'label' => string]
 *
 * @package if-untitled
 */

$items = if_untitled_archive_filter(
    $args['taxonomy'],
    $args['all_url'],
    $args['all_label'] ?? __('All', 'if-untitled')
);
?>
<nav class="archive-filter" aria-label="<?php echo esc_attr($args['label'] ?? __('Filter', 'if-untitled')); ?>">
    <ul class="archive-filter__list">
        <?php foreach ($items as $item) : ?>
            <li class="archive-filter__item">
                <a class="archive-filter__link" href="<?php echo esc_url($item['url']); ?>"
                    <?php echo $item['is_active'] ? 'aria-current="page"' : ''; ?>>
                    <?php echo esc_html($item['label']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
```

Style it as a normal component (`create-theme-component`); the active state is `[aria-current='page']`, not a class.

## 4. Intro text

"All" shows the archive intro (Theme Options field); a term view shows `term_description()` if set.

## 5. JS

None required. Filter links go through the existing `RouterTransition` page fade. Swapping only the grid via
`fetch` is an optional later enhancement — the links must keep working without it.

## 6. Verify

`npm run check` in the theme folder, then open the archive and each term URL: correct items, `aria-current` on the
active one, pagination (`/page/2/`) works per term, empty terms hidden.
