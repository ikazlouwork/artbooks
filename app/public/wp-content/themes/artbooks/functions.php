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
