<?php
/**
 * Template Name: Home Page
 */
get_header();
?>

<main class="home">

  <section class="hero">
    <div class="hero-content">
      <h1>Hire Top Freelance Gigs</h1>
      <p>Browse high-quality gigs created by professionals.</p>
      <a href="<?php echo site_url('/gigs'); ?>" class="btn-primary">
        Explore Gigs
      </a>
    </div>
  </section>

  <section class="featured-gigs">
    <h2>Latest Gigs</h2>

    <div class="gig-grid">
      <?php
      $gigs = new WP_Query([
        'post_type'      => 'wg_gig',
        'posts_per_page' => 2,
      ]);

      while ($gigs->have_posts()) :
        $gigs->the_post();
      ?>
        <article class="gig-card">
          <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('medium'); ?>
            <div class="wg-gig-body">
                <h3><?php the_title(); ?></h3>
                <?php 
                $price = get_post_meta( get_the_ID(), '_wg_gig_price', true );
                if ( $price !== '' ) : ?>
                    <p class="price">$<?php echo esc_html( $price ); ?></p>
                <?php endif; ?>
            </div>
          </a>
        </article>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

  </section>

</main>

<?php get_footer(); ?>
