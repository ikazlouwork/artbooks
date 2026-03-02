<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$privacy_page = null;
$privacy_page_id = (int) get_option('wp_page_for_privacy_policy');

if ($privacy_page_id > 0) {
    $candidate = get_post($privacy_page_id);
    if ($candidate instanceof WP_Post && $candidate->post_type === 'page') {
        $privacy_page = $candidate;
    }
}

if (! ($privacy_page instanceof WP_Post)) {
    $candidate = get_page_by_path('privacy-policy', OBJECT, 'page');
    if ($candidate instanceof WP_Post) {
        $privacy_page = $candidate;
    }
}

$privacy_title = __('Privacy Policy', 'artbooks');
$privacy_content = '';

if ($privacy_page instanceof WP_Post) {
    $title_candidate = get_the_title($privacy_page);
    if (is_string($title_candidate) && $title_candidate !== '') {
        $privacy_title = $title_candidate;
    }

    if ($privacy_page->post_status === 'publish') {
        $privacy_content = apply_filters('the_content', (string) $privacy_page->post_content);
    }
}
?>
<section class="ab-about-page">
    <div class="ab-container">
        <nav class="ab-about-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'artbooks'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'artbooks'); ?></a>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($privacy_title); ?></span>
        </nav>

        <article class="ab-about-article ab-privacy-article">
            <h1><?php echo esc_html($privacy_title); ?></h1>
            <?php if ($privacy_content !== '') : ?>
                <div class="ab-about-content"><?php echo wp_kses_post($privacy_content); ?></div>
            <?php else : ?>
                <p><?php esc_html_e('Publish a page with slug "privacy-policy" (or assign Privacy Policy page in Settings) to manage this content from admin.', 'artbooks'); ?></p>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php
get_footer();
