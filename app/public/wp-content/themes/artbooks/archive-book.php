<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wp_query;

$request_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$request_year = isset($_GET['year']) ? absint(wp_unslash($_GET['year'])) : 0;
$request_author = isset($_GET['author']) ? absint(wp_unslash($_GET['author'])) : 0;

$year_options = artbooks_get_books_archive_years();
$author_options = artbooks_get_books_archive_authors();

$archive_url = get_post_type_archive_link('book');
$current_page = max(1, (int) get_query_var('paged'));
$add_args = array_filter([
    's'      => $request_search !== '' ? $request_search : null,
    'year'   => $request_year > 0 ? $request_year : null,
    'author' => $request_author > 0 ? $request_author : null,
]);
?>
<section class="ab-books-archive">
    <div class="ab-container">
        <nav class="ab-book-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'artbooks'); ?>">
            <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Books', 'artbooks'); ?></a>
            <span aria-hidden="true">/</span>
            <span><?php esc_html_e('Books Catalog', 'artbooks'); ?></span>
        </nav>

        <div class="ab-books-shell">
            <header class="ab-books-head">
                <form class="ab-books-filters" method="get" action="<?php echo esc_url($archive_url); ?>">
                    <label>
                        <span><?php esc_html_e('Search', 'artbooks'); ?></span>
                        <input type="search" name="s" value="<?php echo esc_attr($request_search); ?>" placeholder="<?php esc_attr_e('Title or keyword', 'artbooks'); ?>">
                    </label>

                    <?php if ($author_options !== []) : ?>
                        <label>
                            <span><?php esc_html_e('Author', 'artbooks'); ?></span>
                            <select name="author">
                                <option value="0"><?php esc_html_e('All authors', 'artbooks'); ?></option>
                                <?php foreach ($author_options as $author_id => $author_name) : ?>
                                    <option value="<?php echo esc_attr((string) $author_id); ?>"<?php selected($request_author, (int) $author_id); ?>><?php echo esc_html($author_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>

                    <?php if ($year_options !== []) : ?>
                        <label>
                            <span><?php esc_html_e('Year', 'artbooks'); ?></span>
                            <select name="year">
                                <option value="0"><?php esc_html_e('All years', 'artbooks'); ?></option>
                                <?php foreach ($year_options as $year) : ?>
                                    <option value="<?php echo esc_attr((string) $year); ?>"<?php selected($request_year, (int) $year); ?>><?php echo esc_html((string) $year); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>

                    <div class="ab-books-filter-actions">
                        <button class="ab-btn ab-btn-solid" type="submit"><?php esc_html_e('Apply', 'artbooks'); ?></button>
                        <a class="ab-btn ab-btn-outline" href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('Reset', 'artbooks'); ?></a>
                    </div>
                </form>
            </header>

            <?php if (have_posts()) : ?>
                <div class="ab-books-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php $book_id = (int) get_the_ID(); ?>
                        <article class="ab-book-card">
                            <a class="ab-book-card-cover" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open %s', 'artbooks'), get_the_title())); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
                                <?php else : ?>
                                    <span class="ab-book-cover-placeholder"></span>
                                <?php endif; ?>
                            </a>
                            <div class="ab-book-card-meta">
                                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <p class="ab-book-card-author"><?php echo esc_html(artbooks_get_book_author_name($book_id)); ?></p>
                                <p class="ab-book-card-year"><?php echo esc_html(get_the_date('Y')); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                $pagination = paginate_links([
                    'current'   => $current_page,
                    'total'     => max(1, (int) $wp_query->max_num_pages),
                    'type'      => 'list',
                    'mid_size'  => 1,
                    'prev_text' => __('Prev', 'artbooks'),
                    'next_text' => __('Next', 'artbooks'),
                    'add_args'  => $add_args,
                ]);
                ?>

                <?php if (is_string($pagination) && $pagination !== '') : ?>
                    <nav class="ab-books-pagination" aria-label="<?php esc_attr_e('Books pagination', 'artbooks'); ?>">
                        <?php echo wp_kses_post($pagination); ?>
                    </nav>
                <?php endif; ?>
            <?php else : ?>
                <div class="ab-books-empty">
                    <h2><?php esc_html_e('No books found', 'artbooks'); ?></h2>
                    <p><?php esc_html_e('Try adjusting search terms or filters to find titles in the catalog.', 'artbooks'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
get_footer();
