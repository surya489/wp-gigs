<?php get_header(); ?>

<main class="container single-gig">

<?php while ( have_posts() ) : the_post(); ?>

  <h1><?php the_title(); ?></h1>

  <?php the_post_thumbnail('large'); ?>

  <p class="price">
    Price: $<?php echo esc_html( get_post_meta( get_the_ID(), '_wg_gig_price', true ) ); ?>
  </p>

  <div class="content">
    <?php the_content(); ?>
  </div>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
