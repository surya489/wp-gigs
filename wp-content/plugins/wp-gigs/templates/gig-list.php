<?php if ( $query->have_posts() ) : ?>
    <ul class="wg-gigs-list">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li class="wg-gig-item">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'thumbnail' ); ?>
                <?php endif; ?>

                <h3><?php the_title(); ?></h3>

                <p>Price: ₹<?php echo esc_html( get_post_meta( get_the_ID(), '_wg_gig_price', true ) ); ?></p>

                <a href="<?php the_permalink(); ?>">View Gig</a>
            </li>
        <?php endwhile; ?>
    </ul>

    <div class="wg-pagination">
        <?php
        echo paginate_links([
            'total' => $query->max_num_pages,
        ]);
        ?>
    </div>

<?php else : ?>
    <p>No gigs found.</p>
<?php endif; ?>
