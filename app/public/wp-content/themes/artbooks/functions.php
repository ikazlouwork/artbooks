<?php

if (! defined('ABSPATH')) {
    exit;
}

function artbooks_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('Primary Menu', 'artbooks'),
    ]);
}
add_action('after_setup_theme', 'artbooks_theme_setup');

function artbooks_enqueue_assets(): void
{
    $main_css_path = get_template_directory() . '/assets/css/main.css';
    $main_css_version = file_exists($main_css_path) ? (string) filemtime($main_css_path) : '0.2.0';

    wp_enqueue_style('artbooks-fonts', 'https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,500&family=Jost:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('artbooks-main', get_template_directory_uri() . '/assets/css/main.css', ['artbooks-fonts'], $main_css_version);

    if (is_front_page()) {
        $hero_js_path = get_template_directory() . '/assets/js/home-hero-rotator.js';
        $hero_js_version = file_exists($hero_js_path) ? (string) filemtime($hero_js_path) : '0.1.0';

        wp_enqueue_script('artbooks-home-hero-rotator', get_template_directory_uri() . '/assets/js/home-hero-rotator.js', [], $hero_js_version, true);
    }
}
add_action('wp_enqueue_scripts', 'artbooks_enqueue_assets');

function artbooks_register_content_post_types(): void
{
    if (post_type_exists('book')) {
        return;
    }

    $book_labels = [
        'name'               => __('Books', 'artbooks'),
        'singular_name'      => __('Book', 'artbooks'),
        'menu_name'          => __('Books', 'artbooks'),
        'name_admin_bar'     => __('Book', 'artbooks'),
        'add_new'            => __('Add New', 'artbooks'),
        'add_new_item'       => __('Add New Book', 'artbooks'),
        'new_item'           => __('New Book', 'artbooks'),
        'edit_item'          => __('Edit Book', 'artbooks'),
        'view_item'          => __('View Book', 'artbooks'),
        'all_items'          => __('All Books', 'artbooks'),
        'search_items'       => __('Search Books', 'artbooks'),
        'not_found'          => __('No books found.', 'artbooks'),
        'not_found_in_trash' => __('No books found in Trash.', 'artbooks'),
    ];

    register_post_type('book', [
        'labels'              => $book_labels,
        'public'              => true,
        'has_archive'         => 'books',
        'rewrite'             => ['slug' => 'books'],
        'show_in_rest'        => true,
        'menu_position'       => 21,
        'menu_icon'           => 'dashicons-book-alt',
        'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'exclude_from_search' => false,
    ]);
}
add_action('init', 'artbooks_register_content_post_types', 5);

function artbooks_flush_rewrite_rules_after_switch(): void
{
    artbooks_register_content_post_types();
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'artbooks_flush_rewrite_rules_after_switch');

function artbooks_books_archive_query(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive('book')) {
        return;
    }

    $query->set('posts_per_page', 12);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');

    $request_year = isset($_GET['year']) ? absint(wp_unslash($_GET['year'])) : 0;
    if ($request_year >= 1000 && $request_year <= 9999) {
        $query->set('year', $request_year);
    }

    $request_author = isset($_GET['author']) ? absint(wp_unslash($_GET['author'])) : 0;
    if ($request_author > 0) {
        $existing_meta_query = $query->get('meta_query');
        $meta_query = is_array($existing_meta_query) ? $existing_meta_query : [];

        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'author',
                'value'   => (string) $request_author,
                'compare' => '=',
            ],
            [
                'key'     => 'author',
                'value'   => '"' . (string) $request_author . '"',
                'compare' => 'LIKE',
            ],
        ];

        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'artbooks_books_archive_query');

function artbooks_get_books_archive_years(): array
{
    global $wpdb;

    if (! ($wpdb instanceof wpdb)) {
        return [];
    }

    $years = $wpdb->get_col(
        "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts} WHERE post_type = 'book' AND post_status = 'publish' ORDER BY post_date DESC"
    );

    if (! is_array($years)) {
        return [];
    }

    $normalized = array_values(array_filter(array_map('absint', $years)));

    return array_values(array_unique($normalized));
}

function artbooks_get_books_archive_authors(): array
{
    if (! post_type_exists('author')) {
        return [];
    }

    $authors = get_posts([
        'post_type'           => 'author',
        'posts_per_page'      => -1,
        'orderby'             => 'title',
        'order'               => 'ASC',
        'post_status'         => 'publish',
        'suppress_filters'    => false,
        'ignore_sticky_posts' => true,
    ]);

    if (! is_array($authors) || $authors === []) {
        return [];
    }

    $options = [];

    foreach ($authors as $author) {
        if (! $author instanceof WP_Post) {
            continue;
        }

        $author_id = (int) $author->ID;
        $author_title = get_the_title($author_id);

        if ($author_id > 0 && is_string($author_title) && $author_title !== '') {
            $options[$author_id] = $author_title;
        }
    }

    return $options;
}

function artbooks_register_about_route(): void
{
    add_rewrite_rule('^about/?$', 'index.php?artbooks_about=1', 'top');

    if (! get_option('artbooks_about_route_ready')) {
        flush_rewrite_rules(false);
        update_option('artbooks_about_route_ready', '1');
    }
}
add_action('init', 'artbooks_register_about_route');

function artbooks_register_query_vars(array $vars): array
{
    $vars[] = 'artbooks_about';

    return $vars;
}
add_filter('query_vars', 'artbooks_register_query_vars');

function artbooks_about_template_include(string $template): string
{
    if ((int) get_query_var('artbooks_about') !== 1) {
        return $template;
    }

    $about_template = get_template_directory() . '/page-about.php';

    if (! file_exists($about_template)) {
        return $template;
    }

    status_header(200);

    global $wp_query;
    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
    }

    return $about_template;
}
add_filter('template_include', 'artbooks_about_template_include');

function artbooks_about_body_class(array $classes): array
{
    if ((int) get_query_var('artbooks_about') !== 1) {
        return $classes;
    }

    $filtered = array_filter(
        $classes,
        static fn (string $class): bool => ! in_array($class, ['home', 'blog'], true)
    );

    $filtered[] = 'ab-about-route';

    return array_values(array_unique($filtered));
}
add_filter('body_class', 'artbooks_about_body_class');

function artbooks_primary_cta_url(): string
{
    if (post_type_exists('book')) {
        return home_url('/books/');
    }

    return home_url('/');
}

function artbooks_get_book_author_name(int $post_id): string
{
    if (function_exists('get_field')) {
        $author_field = get_field('author', $post_id);

        if (is_numeric($author_field)) {
            $author_title = get_the_title((int) $author_field);
            if (! empty($author_title)) {
                return $author_title;
            }
        }

        if (is_array($author_field) && isset($author_field['ID'])) {
            $author_title = get_the_title((int) $author_field['ID']);
            if (! empty($author_title)) {
                return $author_title;
            }
        }

        if (is_object($author_field) && isset($author_field->ID)) {
            $author_title = get_the_title((int) $author_field->ID);
            if (! empty($author_title)) {
                return $author_title;
            }
        }

        if (is_string($author_field) && $author_field !== '') {
            return $author_field;
        }
    }

    $fallback = get_post_meta($post_id, 'book_author_name', true);
    if (is_string($fallback) && $fallback !== '') {
        return $fallback;
    }

    return __('Author to be announced', 'artbooks');
}

function artbooks_get_buy_links(int $post_id): array
{
    if (! function_exists('get_field')) {
        return [];
    }

    $possible_fields = ['where_to_buy', 'where_to_buy_links', 'buy_links'];

    foreach ($possible_fields as $field_name) {
        $rows = get_field($field_name, $post_id);

        if (! is_array($rows) || $rows === []) {
            continue;
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = '';
            foreach (['store_name', 'title', 'label', 'name'] as $label_key) {
                if (! empty($row[$label_key]) && is_string($row[$label_key])) {
                    $label = trim($row[$label_key]);
                    break;
                }
            }

            $url = '';
            foreach (['url', 'link'] as $url_key) {
                if (! empty($row[$url_key]) && is_string($row[$url_key])) {
                    $url = trim($row[$url_key]);
                    break;
                }
            }

            if ($label !== '' && $url !== '') {
                $normalized[] = [
                    'label' => $label,
                    'url'   => $url,
                ];
            }

            if (count($normalized) === 6) {
                break;
            }
        }

        if ($normalized !== []) {
            return $normalized;
        }
    }

    return [];
}
