<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$featured_post_type = post_type_exists('book') ? 'book' : 'post';
$home_books = get_posts([
    'post_type'           => $featured_post_type,
    'numberposts'         => 8,
    'post_status'         => 'publish',
    'orderby'             => 'date',
    'order'               => 'DESC',
    'suppress_filters'    => false,
    'ignore_sticky_posts' => true,
]);

$hero_book = $home_books !== [] ? array_shift($home_books) : null;
$hero_book_id = $hero_book instanceof WP_Post ? (int) $hero_book->ID : 0;
$hero_description = '';

if ($hero_book_id > 0) {
    $hero_description = trim((string) get_the_excerpt($hero_book_id));

    if ($hero_description === '') {
        $hero_description = wp_trim_words(wp_strip_all_tags((string) get_post_field('post_content', $hero_book_id)), 40);
    }
}

$hero_buy_links = $hero_book_id > 0 ? artbooks_get_buy_links($hero_book_id) : [];
?>
<section class="ab-home-hero">
    <div class="ab-container ab-home-hero-grid">
        <div class="ab-home-hero-copy">
            <p class="ab-home-kicker"><?php esc_html_e('Artbooks Publishing House', 'artbooks'); ?></p>

            <?php if ($hero_book_id > 0) : ?>
                <h1><?php echo esc_html(get_the_title($hero_book_id)); ?></h1>
                <p class="ab-home-lead"><?php echo esc_html($hero_description !== '' ? $hero_description : __('A newly featured title from Artbooks is available now.', 'artbooks')); ?></p>
                <p class="ab-home-author"><?php echo esc_html(artbooks_get_book_author_name($hero_book_id)); ?></p>
            <?php else : ?>
                <h1><?php esc_html_e('Stories Crafted as Objects of Art', 'artbooks'); ?></h1>
                <p class="ab-home-lead"><?php esc_html_e('Artbooks curates contemporary fiction and illustrated editions with distinctive visual worlds and thoughtful production.', 'artbooks'); ?></p>
            <?php endif; ?>

            <?php if ($hero_buy_links !== []) : ?>
                <ul class="ab-buy-grid" aria-label="<?php esc_attr_e('Where to buy', 'artbooks'); ?>">
                    <?php foreach ($hero_buy_links as $link) : ?>
                        <li>
                            <a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html($link['label']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="ab-home-actions">
                <a class="ab-btn ab-btn-solid" href="<?php echo esc_url(artbooks_primary_cta_url()); ?>"><?php esc_html_e('Explore Books', 'artbooks'); ?></a>
                <a class="ab-btn ab-btn-outline" href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About Artbooks', 'artbooks'); ?></a>
            </div>
        </div>

        <div class="ab-home-hero-cover-wrap">
            <?php if ($hero_book_id > 0) : ?>
                <a class="ab-home-hero-cover" href="<?php echo esc_url(get_permalink($hero_book_id)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open %s', 'artbooks'), get_the_title($hero_book_id))); ?>">
                    <?php if (has_post_thumbnail($hero_book_id)) : ?>
                        <?php echo get_the_post_thumbnail($hero_book_id, 'large', ['loading' => 'eager']); ?>
                    <?php else : ?>
                        <span class="ab-book-cover-placeholder"></span>
                    <?php endif; ?>
                </a>
            <?php else : ?>
                <div class="ab-home-hero-cover" aria-hidden="true">
                    <span class="ab-book-cover-placeholder"></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="ab-home-intro">
    <div class="ab-container ab-home-intro-grid">
        <h2><?php esc_html_e('Publishing with personality, precision, and permanence.', 'artbooks'); ?></h2>
        <p><?php esc_html_e('We collaborate with writers, artists, and translators to release books that read beautifully and look unforgettable. Our catalog balances literary voice with visual identity.', 'artbooks'); ?></p>
    </div>
</section>

<section class="ab-home-books">
    <div class="ab-container">
        <div class="ab-home-section-head">
            <h2><?php esc_html_e('Also from Artbooks', 'artbooks'); ?></h2>
            <a href="<?php echo esc_url(artbooks_primary_cta_url()); ?>"><?php esc_html_e('View full catalog', 'artbooks'); ?></a>
        </div>

        <?php if ($home_books !== []) : ?>
            <div class="ab-home-books-grid">
                <?php foreach ($home_books as $book) : ?>
                    <article class="ab-home-book-card">
                        <a class="ab-home-book-cover" href="<?php echo esc_url(get_permalink($book->ID)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open %s', 'artbooks'), get_the_title($book->ID))); ?>">
                            <?php if (has_post_thumbnail($book->ID)) : ?>
                                <?php echo get_the_post_thumbnail($book->ID, 'medium_large', ['loading' => 'lazy']); ?>
                            <?php else : ?>
                                <span class="ab-book-cover-placeholder"></span>
                            <?php endif; ?>
                        </a>
                        <h3><a href="<?php echo esc_url(get_permalink($book->ID)); ?>"><?php echo esc_html(get_the_title($book->ID)); ?></a></h3>
                        <p><?php echo esc_html(artbooks_get_book_author_name((int) $book->ID)); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif ($hero_book_id > 0) : ?>
            <p class="ab-empty"><?php esc_html_e('More books are coming to the catalog soon.', 'artbooks'); ?></p>
        <?php else : ?>
            <p class="ab-empty"><?php esc_html_e('The catalog will appear here as soon as books are published.', 'artbooks'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
