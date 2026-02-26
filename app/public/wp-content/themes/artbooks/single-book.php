<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $book_id = (int) get_the_ID();

    $acf_value_to_url = static function ($value): string {
        if (is_numeric($value)) {
            $url = wp_get_attachment_image_url((int) $value, 'full');
            return is_string($url) ? $url : '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            if (! empty($value['url']) && is_string($value['url'])) {
                return trim($value['url']);
            }

            if (! empty($value['ID']) && is_numeric($value['ID'])) {
                $url = wp_get_attachment_image_url((int) $value['ID'], 'full');
                return is_string($url) ? $url : '';
            }
        }

        if (is_object($value) && isset($value->ID) && is_numeric($value->ID)) {
            $url = wp_get_attachment_image_url((int) $value->ID, 'full');
            return is_string($url) ? $url : '';
        }

        return '';
    };

    $acf_pick_first = static function (int $post_id, array $field_names) {
        if (! function_exists('get_field')) {
            return null;
        }

        foreach ($field_names as $field_name) {
            $value = get_field($field_name, $post_id);
            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return null;
    };

    $desktop_background_raw = $acf_pick_first($book_id, ['background_image_desktop', 'background_desktop', 'background_image', 'book_background_desktop', 'background']);
    $mobile_background_raw = $acf_pick_first($book_id, ['background_image_mobile', 'background_mobile', 'book_background_mobile']);

    $desktop_background_url = $acf_value_to_url($desktop_background_raw);
    $mobile_background_url = $acf_value_to_url($mobile_background_raw);

    $overlay_opacity_raw = $acf_pick_first($book_id, ['overlay_opacity', 'background_overlay_opacity']);
    $overlay_opacity = 0.36;
    if (is_numeric($overlay_opacity_raw)) {
        $normalized = (float) $overlay_opacity_raw;
        if ($normalized > 1) {
            $normalized = $normalized / 100;
        }
        $overlay_opacity = max(0.0, min(0.92, $normalized));
    }

    $accent_color_raw = $acf_pick_first($book_id, ['accent_color']);
    $accent_color = is_string($accent_color_raw) && preg_match('/^#[a-f0-9]{3,8}$/i', trim($accent_color_raw)) ? trim($accent_color_raw) : '#fff324';

    $overlay_theme_raw = $acf_pick_first($book_id, ['overlay_theme', 'theme']);
    $overlay_theme = is_string($overlay_theme_raw) ? strtolower(trim($overlay_theme_raw)) : 'dark';
    $is_light_theme = in_array($overlay_theme, ['light', 'bright'], true);

    $book_description_raw = $acf_pick_first($book_id, ['description']);
    $book_description = '';
    if (is_string($book_description_raw) && trim($book_description_raw) !== '') {
        $book_description = $book_description_raw;
    } else {
        $book_description = (string) get_the_content();
    }

    $author_name = artbooks_get_book_author_name($book_id);
    $author_url = '';

    if (function_exists('get_field')) {
        $author_field = get_field('author', $book_id);
        $author_id = 0;

        if (is_numeric($author_field)) {
            $author_id = (int) $author_field;
        } elseif (is_array($author_field) && isset($author_field['ID']) && is_numeric($author_field['ID'])) {
            $author_id = (int) $author_field['ID'];
        } elseif (is_object($author_field) && isset($author_field->ID) && is_numeric($author_field->ID)) {
            $author_id = (int) $author_field->ID;
        }

        if ($author_id > 0 && get_post_type($author_id) === 'author') {
            $candidate_url = get_permalink($author_id);
            if (is_string($candidate_url)) {
                $author_url = $candidate_url;
            }
        }
    }

    $gallery_raw = $acf_pick_first($book_id, ['illustrations_gallery', 'illustrations', 'gallery']);
    $gallery_items = [];

    if (is_array($gallery_raw)) {
        foreach ($gallery_raw as $item) {
            $image_id = 0;
            $image_url = '';
            $image_alt = '';

            if (is_numeric($item)) {
                $image_id = (int) $item;
            } elseif (is_array($item)) {
                if (! empty($item['ID']) && is_numeric($item['ID'])) {
                    $image_id = (int) $item['ID'];
                }
                if (! empty($item['url']) && is_string($item['url'])) {
                    $image_url = trim($item['url']);
                }
                if (! empty($item['alt']) && is_string($item['alt'])) {
                    $image_alt = trim($item['alt']);
                }
            } elseif (is_object($item) && isset($item->ID) && is_numeric($item->ID)) {
                $image_id = (int) $item->ID;
            }

            if ($image_id > 0) {
                $candidate_url = wp_get_attachment_image_url($image_id, 'large');
                if (is_string($candidate_url)) {
                    $image_url = $candidate_url;
                }
                if ($image_alt === '') {
                    $candidate_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    if (is_string($candidate_alt)) {
                        $image_alt = trim($candidate_alt);
                    }
                }
            }

            if ($image_url === '') {
                continue;
            }

            $gallery_items[] = [
                'url' => $image_url,
                'alt' => $image_alt,
            ];

            if (count($gallery_items) === 12) {
                break;
            }
        }
    }

    $buy_links = artbooks_get_buy_links($book_id);

    $book_background_style = [];
    if ($desktop_background_url !== '') {
        $book_background_style[] = '--ab-book-bg-desktop: url(' . esc_url_raw($desktop_background_url) . ')';
    }
    if ($mobile_background_url !== '') {
        $book_background_style[] = '--ab-book-bg-mobile: url(' . esc_url_raw($mobile_background_url) . ')';
    }
    $book_background_style[] = '--ab-book-overlay-opacity: ' . esc_attr((string) $overlay_opacity);
    $book_background_style[] = '--ab-book-accent: ' . esc_attr($accent_color);
    ?>
    <article class="ab-book-page<?php echo $is_light_theme ? ' is-light' : ''; ?>" style="<?php echo esc_attr(implode('; ', $book_background_style)); ?>">
        <div class="ab-container ab-book-wrap">
            <nav class="ab-book-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'artbooks'); ?>">
                <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Books', 'artbooks'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php the_title(); ?></span>
            </nav>

            <header class="ab-book-hero">
                <div class="ab-book-cover">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', ['loading' => 'eager']); ?>
                    <?php else : ?>
                        <span class="ab-book-cover-placeholder"></span>
                    <?php endif; ?>
                </div>

                <div class="ab-book-headline">
                    <p class="ab-book-kicker"><?php esc_html_e('Book Page', 'artbooks'); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <p class="ab-book-author"><?php echo esc_html($author_name); ?></p>

                    <div class="ab-book-anchor-nav">
                        <a href="#about"><?php esc_html_e('About', 'artbooks'); ?></a>
                        <a href="#author"><?php esc_html_e('Author', 'artbooks'); ?></a>
                        <a href="#illustrations"><?php esc_html_e('Illustrations', 'artbooks'); ?></a>
                        <a href="#buy"><?php esc_html_e('Where to buy', 'artbooks'); ?></a>
                    </div>
                </div>
            </header>

            <section id="about" class="ab-book-section">
                <h2><?php esc_html_e('About', 'artbooks'); ?></h2>
                <div class="ab-book-richtext"><?php echo wp_kses_post(wpautop($book_description)); ?></div>
            </section>

            <section id="author" class="ab-book-section">
                <h2><?php esc_html_e('Author', 'artbooks'); ?></h2>
                <?php if ($author_url !== '') : ?>
                    <p><a class="ab-book-author-link" href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a></p>
                <?php else : ?>
                    <p><?php echo esc_html($author_name); ?></p>
                <?php endif; ?>
            </section>

            <section id="illustrations" class="ab-book-section">
                <h2><?php esc_html_e('Illustrations', 'artbooks'); ?></h2>
                <?php if ($gallery_items !== []) : ?>
                    <div class="ab-book-gallery">
                        <?php foreach ($gallery_items as $image) : ?>
                            <figure>
                                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy" decoding="async">
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Illustrations will be added soon.', 'artbooks'); ?></p>
                <?php endif; ?>
            </section>

            <section id="buy" class="ab-book-section">
                <h2><?php esc_html_e('Where to buy', 'artbooks'); ?></h2>
                <?php if ($buy_links !== []) : ?>
                    <ul class="ab-book-buy-links">
                        <?php foreach ($buy_links as $link) : ?>
                            <li>
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html($link['label']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php esc_html_e('Store links will appear here after publication setup.', 'artbooks'); ?></p>
                <?php endif; ?>
            </section>
        </div>
    </article>
<?php endwhile; ?>
<?php
get_footer();
