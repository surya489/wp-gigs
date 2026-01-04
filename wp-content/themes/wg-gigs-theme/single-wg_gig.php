<?php get_header(); ?>

<main class="single-gig">
    <?php while (have_posts()) : the_post(); ?>

        <section class="gig-hero">
            <div class="gig-image">
                <?php 
                if (has_post_thumbnail()) {
                    the_post_thumbnail('large', ['alt' => get_the_title()]);
                } else {
                    echo '<img src="https://via.placeholder.com/800x600?text=No+Image" alt="No image available">';
                }
                ?>
            </div>

            <div class="gig-summary">
                <h1><?php the_title(); ?></h1>

                <p class="gig-price">
                    Starting at $<span><?php echo esc_html(get_post_meta(get_the_ID(), '_wg_gig_price', true)); ?></span>
                </p>

                <?php 
                $content = get_the_content();
                $content_stripped = trim(wp_strip_all_tags($content));
                ?>

                <?php if (!empty($content_stripped)) : ?>
                    <div class="gig-description">
                        <h2>About This Gig</h2>
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
<!-- 
                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="gig-cta">
                    Contact Seller / Order Now
                </a> -->
            </div>
        </section>

    <?php endwhile; ?>
</main>

<?php get_footer(); ?>