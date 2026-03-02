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
    $illustrator_name = '';
    $illustrator_url = '';

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

        $illustrator_field = get_field('illustrator', $book_id);
        $illustrator_id = 0;

        if (is_numeric($illustrator_field)) {
            $illustrator_id = (int) $illustrator_field;
        } elseif (is_array($illustrator_field) && isset($illustrator_field['ID']) && is_numeric($illustrator_field['ID'])) {
            $illustrator_id = (int) $illustrator_field['ID'];
        } elseif (is_object($illustrator_field) && isset($illustrator_field->ID) && is_numeric($illustrator_field->ID)) {
            $illustrator_id = (int) $illustrator_field->ID;
        } elseif (is_string($illustrator_field) && trim($illustrator_field) !== '') {
            $illustrator_name = trim($illustrator_field);
        }

        if ($illustrator_id > 0) {
            $illustrator_title = get_the_title($illustrator_id);
            if (is_string($illustrator_title) && $illustrator_title !== '') {
                $illustrator_name = $illustrator_title;
            }

            if (get_post_status($illustrator_id) === 'publish') {
                $candidate_url = get_permalink($illustrator_id);
                if (is_string($candidate_url)) {
                    $illustrator_url = $candidate_url;
                }
            }
        }

        if ($illustrator_name === '') {
            $illustrator_meta = get_post_meta($book_id, 'illustrator', true);
            if (is_string($illustrator_meta) && trim($illustrator_meta) !== '') {
                $illustrator_name = trim($illustrator_meta);
            }
        }
    }

    $gallery_raw = $acf_pick_first($book_id, ['illustrations_gallery', 'illustrations', 'gallery']);
    $gallery_items = [];

    if (is_array($gallery_raw)) {
        foreach ($gallery_raw as $item) {
            $image_id = 0;
            $image_url = '';
            $image_full_url = '';
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
                $candidate_full_url = wp_get_attachment_image_url($image_id, 'full');
                if (is_string($candidate_full_url)) {
                    $image_full_url = $candidate_full_url;
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
                'full_url' => $image_full_url !== '' ? $image_full_url : $image_url,
                'alt' => $image_alt,
            ];

            if (count($gallery_items) === 12) {
                break;
            }
        }
    }

    $slider_images = [];
    $slider_seen_urls = [];

    $extract_slider_images_from_html = static function (string $html): array {
        if ($html === '') {
            return [];
        }

        $images = [];
        $matches = [];
        preg_match_all('/<img[^>]*>/i', $html, $matches);

        if (! isset($matches[0]) || ! is_array($matches[0])) {
            return [];
        }

        foreach ($matches[0] as $tag) {
            if (! is_string($tag) || $tag === '') {
                continue;
            }

            $src = '';
            $full_url = '';
            $alt = '';
            $image_id = 0;

            if (preg_match('/\ssrc=["\']([^"\']+)["\']/i', $tag, $src_match) === 1 && isset($src_match[1])) {
                $src = trim((string) $src_match[1]);
            }

            if (preg_match('/\salt=["\']([^"\']*)["\']/i', $tag, $alt_match) === 1 && isset($alt_match[1])) {
                $alt = trim((string) $alt_match[1]);
            }

            if (preg_match('/wp-image-(\d+)/i', $tag, $id_match) === 1 && isset($id_match[1])) {
                $image_id = absint($id_match[1]);
            }

            if ($image_id > 0) {
                $large_url = wp_get_attachment_image_url($image_id, 'large');
                $candidate_full_url = wp_get_attachment_image_url($image_id, 'full');

                if (is_string($large_url) && $large_url !== '') {
                    $src = $large_url;
                }

                if (is_string($candidate_full_url) && $candidate_full_url !== '') {
                    $full_url = $candidate_full_url;
                }

                if ($alt === '') {
                    $candidate_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    if (is_string($candidate_alt)) {
                        $alt = trim($candidate_alt);
                    }
                }
            }

            if ($src === '') {
                continue;
            }

            $images[] = [
                'url' => $src,
                'full_url' => $full_url !== '' ? $full_url : $src,
                'alt' => $alt,
            ];

            if (count($images) === 12) {
                break;
            }
        }

        return $images;
    };

    foreach ($gallery_items as $gallery_item) {
        if (! isset($gallery_item['url']) || ! is_string($gallery_item['url']) || $gallery_item['url'] === '') {
            continue;
        }

        if (in_array($gallery_item['url'], $slider_seen_urls, true)) {
            continue;
        }

        $slider_images[] = [
            'url' => $gallery_item['url'],
            'full_url' => isset($gallery_item['full_url']) && is_string($gallery_item['full_url']) && $gallery_item['full_url'] !== '' ? $gallery_item['full_url'] : $gallery_item['url'],
            'alt' => isset($gallery_item['alt']) && is_string($gallery_item['alt']) ? $gallery_item['alt'] : '',
        ];
        $slider_seen_urls[] = $gallery_item['url'];
    }

    if ($slider_images === []) {
        $content_sources = [];

        if (is_string($book_description) && trim($book_description) !== '') {
            $content_sources[] = $book_description;
        }

        $post_content_raw = (string) get_post_field('post_content', $book_id);
        if (trim($post_content_raw) !== '') {
            $content_sources[] = $post_content_raw;
        }

        foreach ($content_sources as $source_html) {
            if (! is_string($source_html) || trim($source_html) === '') {
                continue;
            }

            $content_images = $extract_slider_images_from_html($source_html);
            foreach ($content_images as $content_image) {
                if (! isset($content_image['url']) || ! is_string($content_image['url']) || $content_image['url'] === '') {
                    continue;
                }

                if (in_array($content_image['url'], $slider_seen_urls, true)) {
                    continue;
                }

                $slider_images[] = [
                    'url' => $content_image['url'],
                    'full_url' => isset($content_image['full_url']) && is_string($content_image['full_url']) && $content_image['full_url'] !== '' ? $content_image['full_url'] : $content_image['url'],
                    'alt' => isset($content_image['alt']) && is_string($content_image['alt']) ? $content_image['alt'] : '',
                ];
                $slider_seen_urls[] = $content_image['url'];

                if (count($slider_images) === 12) {
                    break 2;
                }
            }
        }
    }

    $book_description_for_display = $book_description;
    if (is_string($book_description_for_display) && $book_description_for_display !== '') {
        $book_description_for_display = preg_replace('/<figure\b[^>]*>.*?<\/figure>/is', '', $book_description_for_display);
        $book_description_for_display = preg_replace('/<img\b[^>]*>/i', '', (string) $book_description_for_display);
        $book_description_for_display = trim((string) $book_description_for_display);
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
    $book_page_classes = ['ab-book-page'];
    if ($is_light_theme) {
        $book_page_classes[] = 'is-light';
    }
    if ($desktop_background_url !== '' || $mobile_background_url !== '') {
        $book_page_classes[] = 'has-book-bg';
    }
    ?>
    <article class="<?php echo esc_attr(implode(' ', $book_page_classes)); ?>" style="<?php echo esc_attr(implode('; ', $book_background_style)); ?>">
        <div class="ab-container ab-book-wrap">
            <nav class="ab-book-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'artbooks'); ?>">
                <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Books', 'artbooks'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php the_title(); ?></span>
            </nav>

            <header class="ab-book-hero">
                <div class="ab-book-media">
                    <div class="ab-book-cover">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', ['loading' => 'eager']); ?>
                        <?php else : ?>
                            <span class="ab-book-cover-placeholder"></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($slider_images !== []) : ?>
                        <section class="ab-product-images-slider-section" aria-label="<?php esc_attr_e('Book preview images', 'artbooks'); ?>">
                            <div class="ab-product-images-slider" data-ab-book-slider tabindex="0">
                                <div class="ab-product-images-slider-stage">
                                    <?php if (count($slider_images) > 1) : ?>
                                        <button class="ab-product-images-nav ab-product-images-nav-prev" type="button" aria-label="<?php esc_attr_e('Previous image', 'artbooks'); ?>" data-ab-book-slider-prev>&larr;</button>
                                    <?php endif; ?>

                                    <div class="ab-product-images-track" data-ab-book-slider-track>
                                        <?php foreach ($slider_images as $index => $image) : ?>
                                            <figure class="ab-product-image-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-ab-book-slider-slide="<?php echo esc_attr((string) $index); ?>">
                                                <a href="<?php echo esc_url($image['full_url']); ?>">
                                                    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy">
                                                </a>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (count($slider_images) > 1) : ?>
                                        <button class="ab-product-images-nav ab-product-images-nav-next" type="button" aria-label="<?php esc_attr_e('Next image', 'artbooks'); ?>" data-ab-book-slider-next>&rarr;</button>
                                    <?php endif; ?>
                                </div>

                                <?php if (count($slider_images) > 1) : ?>
                                    <div class="ab-product-images-thumbs" data-ab-book-slider-thumbs>
                                        <?php foreach ($slider_images as $index => $image) : ?>
                                            <button class="ab-product-image-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-ab-book-slider-thumb="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open image %d', 'artbooks'), $index + 1)); ?>">
                                                <img src="<?php echo esc_url($image['url']); ?>" alt="" loading="lazy">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="ab-product-images-dots" data-ab-book-slider-dots>
                                        <?php foreach ($slider_images as $index => $image) : ?>
                                            <button class="ab-product-image-dot<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-ab-book-slider-dot="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr(sprintf(__('Go to image %d', 'artbooks'), $index + 1)); ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="ab-book-headline">
                    <div class="ab-book-topline">
                        <p class="ab-book-kicker"><?php esc_html_e('Book Page', 'artbooks'); ?></p>

                        <div class="ab-book-anchor-nav">
                            <a href="#about"><?php esc_html_e('About', 'artbooks'); ?></a>
                            <a href="#author"><?php esc_html_e('Author', 'artbooks'); ?></a>
                            <?php if ($illustrator_name !== '') : ?>
                                <a href="#illustrator"><?php esc_html_e('Illustrator', 'artbooks'); ?></a>
                            <?php endif; ?>
                            <a href="#buy"><?php esc_html_e('Where to buy', 'artbooks'); ?></a>
                        </div>
                    </div>

                    <h1><?php the_title(); ?></h1>
                    <p class="ab-book-author"><?php echo esc_html($author_name); ?></p>

                    <section id="about" class="ab-book-section">
                        <h2><?php esc_html_e('About', 'artbooks'); ?></h2>
                        <div class="ab-book-richtext"><?php echo wp_kses_post(wpautop($book_description_for_display)); ?></div>
                    </section>

                    <section id="author" class="ab-book-section">
                        <h2><?php esc_html_e('Author', 'artbooks'); ?></h2>
                        <?php if ($author_url !== '') : ?>
                            <p><a class="ab-book-author-link" href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a></p>
                        <?php else : ?>
                            <p><?php echo esc_html($author_name); ?></p>
                        <?php endif; ?>
                    </section>

                    <?php if ($illustrator_name !== '') : ?>
                        <section id="illustrator" class="ab-book-section">
                            <h2><?php esc_html_e('Illustrator', 'artbooks'); ?></h2>
                            <?php if ($illustrator_url !== '') : ?>
                                <p><a class="ab-book-author-link" href="<?php echo esc_url($illustrator_url); ?>"><?php echo esc_html($illustrator_name); ?></a></p>
                            <?php else : ?>
                                <p><?php echo esc_html($illustrator_name); ?></p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

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
            </header>

        </div>
    </article>
<?php endwhile; ?>
<?php
get_footer();
