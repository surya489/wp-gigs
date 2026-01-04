<?php
// Template for displaying the list of gigs
if ( $query->have_posts() ) : ?>
    <ul class="wg-gig-list">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php $price = get_post_meta( get_the_ID(), '_wg_gig_price', true ); ?>
            <li class="wg-gig-item">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="wg-gig-image">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <div class="wg-gig-content">
                    <h3 class="wg-gig-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <?php if ( $price !== '' ) : ?>
                        <p class="wg-gig-price">$<?php echo number_format_i18n( (float) $price, 2 ); ?></p>
                    <?php endif; ?>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>

    <nav class="wg-pagination">
        <?php
        echo paginate_links( array(
            'total'   => $query->max_num_pages,
            'current' => max( 1, get_query_var( 'paged' ) ),
            'prev_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
        ) );
        ?>
    </nav>

<?php else : ?>
    <p><?php _e( 'No gigs available at the moment.', 'wp-gigs' ); ?></p>
<?php endif; ?>

<?php wp_reset_postdata(); ?>