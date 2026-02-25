<?php
if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="ab-site-shell">
    <header class="ab-header">
        <div class="ab-container ab-header-inner">
            <a class="ab-brand" href="<?php echo esc_url(home_url('/')); ?>">Artbooks</a>
            <nav class="ab-nav" aria-label="<?php esc_attr_e('Main navigation', 'artbooks'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'ab-nav-list',
                    'fallback_cb'    => static function () {
                        echo '<ul class="ab-nav-list">';
                        echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
                        echo '<li><a href="' . esc_url(home_url('/books/')) . '">Books</a></li>';
                        echo '<li><a href="' . esc_url(home_url('/about/')) . '">About</a></li>';
                        echo '</ul>';
                    },
                ]);
                ?>
            </nav>
        </div>
    </header>
    <main id="content" class="ab-main" role="main">
