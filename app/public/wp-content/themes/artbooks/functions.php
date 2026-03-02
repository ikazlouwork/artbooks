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

    if (is_singular('book')) {
        $slider_js_path = get_template_directory() . '/assets/js/book-images-slider.js';
        $slider_js_version = file_exists($slider_js_path) ? (string) filemtime($slider_js_path) : '0.1.0';

        wp_enqueue_script('artbooks-book-images-slider', get_template_directory_uri() . '/assets/js/book-images-slider.js', [], $slider_js_version, true);
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

function artbooks_register_public_routes(): void
{
    add_rewrite_rule('^about/?$', 'index.php?artbooks_about=1', 'top');
    add_rewrite_rule('^privacy-policy/?$', 'index.php?artbooks_privacy=1', 'top');

    if (! get_option('artbooks_public_routes_ready_v2')) {
        flush_rewrite_rules(false);
        update_option('artbooks_public_routes_ready_v2', '1');
    }
}
add_action('init', 'artbooks_register_public_routes');

function artbooks_register_query_vars(array $vars): array
{
    $vars[] = 'artbooks_about';
    $vars[] = 'artbooks_privacy';

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

function artbooks_privacy_template_include(string $template): string
{
    if ((int) get_query_var('artbooks_privacy') !== 1) {
        return $template;
    }

    $privacy_template = get_template_directory() . '/page-privacy-policy.php';

    if (! file_exists($privacy_template)) {
        return $template;
    }

    status_header(200);

    global $wp_query;
    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
    }

    return $privacy_template;
}
add_filter('template_include', 'artbooks_privacy_template_include');

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

function artbooks_privacy_page_body_class(array $classes): array
{
    if ((int) get_query_var('artbooks_privacy') !== 1 && ! is_page('privacy-policy')) {
        return $classes;
    }

    $classes[] = 'ab-about-route';

    return array_values(array_unique($classes));
}
add_filter('body_class', 'artbooks_privacy_page_body_class');

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
    $possible_fields = ['where_to_buy', 'where_to_buy_links', 'buy_links'];
    $max_links = 24;
    $acf_available = function_exists('get_field');
    $label_meta_keys = ['store_name', 'title', 'label', 'name', 'store', 'shop', 'retailer', 'buy_label', 'where_to_buy_label'];
    $url_meta_keys = ['url', 'link', 'store_url', 'store_link', 'buy_url', 'buy_link', 'where_to_buy_url'];

    $normalize_url = static function ($value): string {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            foreach (['url', 'link', 'value'] as $key) {
                if (! empty($value[$key]) && is_string($value[$key])) {
                    return trim($value[$key]);
                }
            }
        }

        return '';
    };

    foreach ($possible_fields as $field_name) {
        $normalized = [];

        if ($acf_available) {
            $rows = get_field($field_name, $post_id);

            if (is_array($rows) && $rows !== []) {
                if (isset($rows['links']) && is_array($rows['links'])) {
                    $rows = $rows['links'];
                } elseif (isset($rows['items']) && is_array($rows['items'])) {
                    $rows = $rows['items'];
                } elseif (isset($rows['rows']) && is_array($rows['rows'])) {
                    $rows = $rows['rows'];
                } elseif (
                    isset($rows['url'])
                    || isset($rows['link'])
                    || isset($rows['store_url'])
                    || isset($rows['store_link'])
                    || isset($rows['buy_url'])
                    || isset($rows['buy_link'])
                ) {
                    $rows = [$rows];
                }

                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $label = '';
                    foreach ($label_meta_keys as $label_key) {
                        if (! empty($row[$label_key]) && is_string($row[$label_key])) {
                            $label = trim($row[$label_key]);
                            break;
                        }
                    }

                    $url = '';
                    foreach ($url_meta_keys as $url_key) {
                        if (! isset($row[$url_key])) {
                            continue;
                        }

                        $url = $normalize_url($row[$url_key]);
                        if ($url !== '') {
                            if ($label === '' && is_array($row[$url_key]) && ! empty($row[$url_key]['title']) && is_string($row[$url_key]['title'])) {
                                $label = trim($row[$url_key]['title']);
                            }
                            break;
                        }
                    }

                    if ($label !== '' && $url !== '') {
                        $normalized[] = [
                            'label' => $label,
                            'url'   => $url,
                        ];
                    }

                    if (count($normalized) === $max_links) {
                        break;
                    }
                }
            }
        }

        if ($normalized !== []) {
            return $normalized;
        }

        $meta_row_count = absint(get_post_meta($post_id, $field_name, true));
        if ($meta_row_count < 1) {
            continue;
        }

        $meta_normalized = [];

        for ($i = 0; $i < $meta_row_count; $i++) {
            $label = '';
            foreach ($label_meta_keys as $label_key) {
                $label_value = get_post_meta($post_id, $field_name . '_' . $i . '_' . $label_key, true);
                if (is_string($label_value) && trim($label_value) !== '') {
                    $label = trim($label_value);
                    break;
                }
            }

            $url = '';
            foreach ($url_meta_keys as $url_key) {
                $url_value = get_post_meta($post_id, $field_name . '_' . $i . '_' . $url_key, true);
                $url = $normalize_url($url_value);

                if ($url !== '') {
                    if ($label === '' && is_array($url_value) && ! empty($url_value['title']) && is_string($url_value['title'])) {
                        $label = trim($url_value['title']);
                    }
                    break;
                }
            }

            if ($label !== '' && $url !== '') {
                $meta_normalized[] = [
                    'label' => $label,
                    'url'   => $url,
                ];
            }

            if (count($meta_normalized) === $max_links) {
                break;
            }
        }

        if ($meta_normalized !== []) {
            return $meta_normalized;
        }
    }

    $all_meta = get_post_meta($post_id);
    if (! is_array($all_meta) || $all_meta === []) {
        return [];
    }

    $detected_prefixes = [];

    foreach (array_keys($all_meta) as $meta_key) {
        if (! is_string($meta_key) || $meta_key === '' || $meta_key[0] === '_') {
            continue;
        }

        if (preg_match('/^(.+)_\d+_(url|link|store_url|store_link|buy_url|buy_link)$/', $meta_key, $match) === 1 && isset($match[1])) {
            $detected_prefixes[] = $match[1];
        }
    }

    $detected_prefixes = array_values(array_unique($detected_prefixes));

    foreach ($detected_prefixes as $prefix) {
        $row_count = absint(get_post_meta($post_id, $prefix, true));
        if ($row_count < 1) {
            continue;
        }

        $normalized = [];

        for ($i = 0; $i < $row_count; $i++) {
            $label = '';
            foreach ($label_meta_keys as $label_key) {
                $label_value = get_post_meta($post_id, $prefix . '_' . $i . '_' . $label_key, true);
                if (is_string($label_value) && trim($label_value) !== '') {
                    $label = trim($label_value);
                    break;
                }
            }

            $url = '';
            foreach ($url_meta_keys as $url_key) {
                $url_value = get_post_meta($post_id, $prefix . '_' . $i . '_' . $url_key, true);
                $url = $normalize_url($url_value);

                if ($url !== '') {
                    break;
                }
            }

            if ($label !== '' && $url !== '') {
                $normalized[] = [
                    'label' => $label,
                    'url'   => $url,
                ];
            }

            if (count($normalized) === $max_links) {
                break;
            }
        }

        if ($normalized !== []) {
            return $normalized;
        }
    }

    $indexed_candidates = [];

    foreach (array_keys($all_meta) as $meta_key) {
        if (! is_string($meta_key) || $meta_key === '' || $meta_key[0] === '_') {
            continue;
        }

        if (preg_match('/^([a-z0-9_]+)_(\d+)$/i', $meta_key, $match) !== 1 || ! isset($match[1], $match[2])) {
            continue;
        }

        $base_key = strtolower($match[1]);
        $index = absint($match[2]);

        if ($index < 1 || $index > $max_links) {
            continue;
        }

        if (in_array($base_key, $label_meta_keys, true) || in_array($base_key, $url_meta_keys, true)) {
            $indexed_candidates[$index] = true;
        }
    }

    if ($indexed_candidates !== []) {
        $indexed_links = [];
        $indices = array_keys($indexed_candidates);
        sort($indices, SORT_NUMERIC);

        foreach ($indices as $index) {
            $label = '';
            foreach ($label_meta_keys as $label_key) {
                $label_value = get_post_meta($post_id, $label_key . '_' . $index, true);
                if (is_string($label_value) && trim($label_value) !== '') {
                    $label = trim($label_value);
                    break;
                }
            }

            $url = '';
            foreach ($url_meta_keys as $url_key) {
                $url_value = get_post_meta($post_id, $url_key . '_' . $index, true);
                $url = $normalize_url($url_value);
                if ($url !== '') {
                    break;
                }
            }

            if ($label !== '' && $url !== '') {
                $indexed_links[] = [
                    'label' => $label,
                    'url'   => $url,
                ];
            }

            if (count($indexed_links) === $max_links) {
                break;
            }
        }

        if ($indexed_links !== []) {
            return $indexed_links;
        }
    }

    $single_label = '';
    foreach ($label_meta_keys as $label_key) {
        $label_value = get_post_meta($post_id, $label_key, true);
        if (is_string($label_value) && trim($label_value) !== '') {
            $single_label = trim($label_value);
            break;
        }
    }

    $single_url = '';
    foreach ($url_meta_keys as $url_key) {
        $url_value = get_post_meta($post_id, $url_key, true);
        $single_url = $normalize_url($url_value);
        if ($single_url !== '') {
            break;
        }
    }

    if ($single_label !== '' && $single_url !== '') {
        return [[
            'label' => $single_label,
            'url'   => $single_url,
        ]];
    }

    return [];
}

function artbooks_book_page_bg_meta_key(string $device): string
{
    return '_ab_book_page_bg_' . $device;
}

function artbooks_get_book_page_backgrounds(int $post_id): array
{
    $backgrounds = [
        'desktop' => '',
        'mobile' => '',
    ];

    foreach (['desktop', 'mobile'] as $device) {
        $value = get_post_meta($post_id, artbooks_book_page_bg_meta_key($device), true);

        if (is_string($value)) {
            $backgrounds[$device] = trim($value);
        }
    }

    return $backgrounds;
}

function artbooks_register_book_background_meta_box(): void
{
    add_meta_box(
        'artbooks-book-page-background',
        __('Book Page Background', 'artbooks'),
        'artbooks_render_book_background_meta_box',
        'book',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'artbooks_register_book_background_meta_box');

function artbooks_render_book_background_meta_box(WP_Post $post): void
{
    wp_nonce_field('artbooks_save_book_page_backgrounds', 'artbooks_book_page_backgrounds_nonce');

    $devices = [
        'desktop' => __('Desktop', 'artbooks'),
        'mobile' => __('Mobile', 'artbooks'),
    ];

    echo '<p>' . esc_html__('Set one background for the whole book page. Leave empty to use existing ACF background fields.', 'artbooks') . '</p>';
    echo '<div class="ab-book-bg-grid">';
    echo '<section class="ab-book-bg-card">';

    foreach ($devices as $device_key => $device_label) {
        $meta_key = artbooks_book_page_bg_meta_key($device_key);
        $current_url = get_post_meta($post->ID, $meta_key, true);
        $value = is_string($current_url) ? trim($current_url) : '';

        echo '<label class="ab-book-bg-field">';
        echo '<span>' . esc_html($device_label) . '</span>';
        echo '<input type="url" class="widefat" name="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '" data-ab-media-url>';
        echo '<div class="ab-book-bg-actions">';
        echo '<button type="button" class="button" data-ab-media-open>' . esc_html__('Choose image', 'artbooks') . '</button>';
        echo '<button type="button" class="button-link-delete" data-ab-media-clear>' . esc_html__('Remove', 'artbooks') . '</button>';
        echo '</div>';
        echo '<div class="ab-book-bg-preview" data-ab-media-preview' . ($value !== '' ? ' style="background-image:url(' . esc_url($value) . ');"' : '') . '></div>';
        echo '</label>';
    }

    echo '</section>';
    echo '</div>';
}

function artbooks_save_book_background_meta_box(int $post_id): void
{
    if (! isset($_POST['artbooks_book_page_backgrounds_nonce'])) {
        return;
    }

    $nonce_raw = wp_unslash($_POST['artbooks_book_page_backgrounds_nonce']);
    $nonce = is_string($nonce_raw) ? sanitize_text_field($nonce_raw) : '';

    if (! wp_verify_nonce($nonce, 'artbooks_save_book_page_backgrounds')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (['desktop', 'mobile'] as $device) {
        $meta_key = artbooks_book_page_bg_meta_key($device);

        if (! isset($_POST[$meta_key])) {
            delete_post_meta($post_id, $meta_key);
            continue;
        }

        $raw_value = wp_unslash($_POST[$meta_key]);
        $value = is_string($raw_value) ? trim($raw_value) : '';
        $sanitized = $value !== '' ? esc_url_raw($value) : '';

        if ($sanitized === '') {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $sanitized);
        }
    }
}
add_action('save_post_book', 'artbooks_save_book_background_meta_box');

function artbooks_enqueue_book_admin_assets(string $hook): void
{
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (! ($screen instanceof WP_Screen) || $screen->post_type !== 'book') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery');

    wp_add_inline_script('jquery', <<<'JS'
jQuery(function ($) {
  const createFrame = () => wp.media({
    title: 'Choose background image',
    button: { text: 'Use image' },
    multiple: false,
    library: { type: 'image' }
  });

  $(document).on('click', '[data-ab-media-open]', function () {
    const $field = $(this).closest('.ab-book-bg-field');
    const $input = $field.find('[data-ab-media-url]');
    const $preview = $field.find('[data-ab-media-preview]');
    const frame = createFrame();

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      if (!attachment || !attachment.url) {
        return;
      }

      $input.val(attachment.url);
      $preview.css('background-image', 'url(' + attachment.url + ')').addClass('is-visible');
    });

    frame.open();
  });

  $(document).on('click', '[data-ab-media-clear]', function () {
    const $field = $(this).closest('.ab-book-bg-field');
    $field.find('[data-ab-media-url]').val('');
    $field.find('[data-ab-media-preview]').css('background-image', 'none').removeClass('is-visible');
  });

  $('[data-ab-media-preview]').each(function () {
    const image = $(this).css('background-image');
    if (image && image !== 'none') {
      $(this).addClass('is-visible');
    }
  });
});
JS);

    wp_register_style('artbooks-book-admin-meta', false);
    wp_enqueue_style('artbooks-book-admin-meta');
    wp_add_inline_style('artbooks-book-admin-meta', <<<'CSS'
.ab-book-bg-grid {
  display: grid;
  gap: 14px;
}

.ab-book-bg-card {
  margin: 0;
  padding: 12px;
  border: 1px solid #dcdcde;
  border-radius: 6px;
  background: #fff;
}

.ab-book-bg-field {
  display: block;
  margin-top: 10px;
}

.ab-book-bg-field > span {
  display: inline-block;
  margin-bottom: 4px;
  font-weight: 600;
}

.ab-book-bg-actions {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ab-book-bg-preview {
  margin-top: 8px;
  width: 100%;
  min-height: 90px;
  border: 1px dashed #c3c4c7;
  border-radius: 4px;
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
}

.ab-book-bg-preview.is-visible {
  border-style: solid;
}
CSS);
}
add_action('admin_enqueue_scripts', 'artbooks_enqueue_book_admin_assets');
