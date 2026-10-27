<?php
/**
 * Team members, two per row: landscape image (3:2), "Name + role" heading, short bio.
 * $args: ['ids' => int[] (team_member post IDs in order)]
 *
 * @package if-untitled
 */

$members = array_filter(array_map('if_untitled_team_member', array_map('intval', $args['ids'] ?? [])));
if (!$members) {
    return;
}
?>
<ul class="team">
    <?php foreach ($members as $member) : ?>
        <li class="team__member">
            <?php if_untitled_component('image', ['id' => $member['image'], 'context' => 'span-2', 'ratio' => '3x2']); ?>
            <h3 class="team__name">
                <?php echo esc_html($member['name']); ?>
                <?php if ($member['role']) : ?>
                    <span class="team__role"><?php echo esc_html($member['role']); ?></span>
                <?php endif; ?>
            </h3>
            <?php if ($member['bio']) : ?>
                <div class="team__bio"><?php echo wp_kses_post($member['bio']); ?></div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
