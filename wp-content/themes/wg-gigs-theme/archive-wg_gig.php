<?php get_header(); ?>

<main class="container">
  <h1>Available Gigs</h1>

  <?php if ( have_posts() ) : ?>
    <div class="gig-grid">

      <?php while ( have_posts() ) : the_post(); ?>
        <article class="gig-card">
          <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('medium'); ?>
            <h3><?php the_title(); ?></h3>
            <p>$<?php echo esc_html( get_post_meta( get_the_ID(), '_wg_gig_price', true ) ); ?></p>
          </a>
        </article>
      <?php endwhile; ?>

    </div>

    <?php the_posts_pagination(); ?>

  <?php else : ?>
    <p>No gigs found.</p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>
