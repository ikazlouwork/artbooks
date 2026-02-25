<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="ab-generic-page">
    <div class="ab-container">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
