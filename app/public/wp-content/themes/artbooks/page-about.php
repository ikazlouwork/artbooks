<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$about_page = get_page_by_path('about', OBJECT, 'page');
$about_title = __('About Us', 'artbooks');
$about_content = '';

if ($about_page instanceof WP_Post) {
    $about_title_candidate = get_the_title($about_page);
    if (is_string($about_title_candidate) && $about_title_candidate !== '') {
        $about_title = $about_title_candidate;
    }

    if ($about_page->post_status === 'publish') {
        $about_content = apply_filters('the_content', (string) $about_page->post_content);
    }
}
?>
<section class="ab-about-page">
    <div class="ab-container">
        <nav class="ab-about-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'artbooks'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'artbooks'); ?></a>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($about_title); ?></span>
        </nav>

        <article class="ab-about-article">
            <h1><?php echo esc_html($about_title); ?></h1>
            <?php if ($about_content !== '') : ?>
                <div class="ab-about-content"><?php echo wp_kses_post($about_content); ?></div>
            <?php else : ?>
                <p><?php esc_html_e('Create and publish a page with slug "about" to manage this content from admin.', 'artbooks'); ?></p>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php
get_footer();
