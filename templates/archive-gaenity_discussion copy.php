<?php
/**
 * Archive Template for Discussions (Forum Page)
 */

get_header();

// Get filters
$selected_region = isset( $_GET['region'] ) ? sanitize_text_field( $_GET['region'] ) : '';
$selected_industry = isset( $_GET['industry'] ) ? sanitize_text_field( $_GET['industry'] ) : '';

// Build query args
$query_args = array(
    'post_type' => 'gaenity_discussion',
    'posts_per_page' => 20,
    'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
);

$tax_query = array();
if ( $selected_region ) {
    $tax_query[] = array(
        'taxonomy' => 'gaenity_region',
        'field' => 'slug',
        'terms' => $selected_region,
    );
}
if ( $selected_industry ) {
    $tax_query[] = array(
        'taxonomy' => 'gaenity_industry',
        'field' => 'slug',
        'terms' => $selected_industry,
    );
}

if ( ! empty( $tax_query ) ) {
    $query_args['tax_query'] = $tax_query;
}

$discussions = new WP_Query( $query_args );
?>

<style>
    .gaenity-forum {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 1rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .gaenity-forum-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .gaenity-forum-header h1 {
        font-size: 2.5rem;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    .gaenity-forum-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 2rem;
    }
    .gaenity-forum-main {
        min-width: 0;
    }
    .gaenity-forum-filters {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .gaenity-filter-group {
        flex: 1;
        min-width: 200px;
    }
    .gaenity-filter-group label {
        display: block;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    .gaenity-filter-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 1rem;
    }
    .gaenity-filter-btn {
        padding: 0.75rem 1.5rem;
        background: #1d4ed8;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .gaenity-discussion-card {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }
    .gaenity-discussion-card:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .gaenity-discussion-card h3 {
        margin: 0 0 0.75rem 0;
        font-size: 1.25rem;
    }
    .gaenity-discussion-card h3 a {
        color: #1e293b;
        text-decoration: none;
    }
    .gaenity-discussion-card h3 a:hover {
        color: #1d4ed8;
    }
    .gaenity-discussion-excerpt {
        color: #64748b;
        margin-bottom: 1rem;
        line-height: 1.6;
    }
    .gaenity-discussion-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .gaenity-discussion-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .gaenity-tag {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.85rem;
    }
    .gaenity-discussion-stats {
        display: flex;
        gap: 1rem;
        color: #64748b;
        font-size: 0.9rem;
    }
    .gaenity-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .gaenity-sidebar-widget {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .gaenity-sidebar-widget h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        font-size: 1.2rem;
        color: #1e293b;
    }
    .gaenity-sidebar-btn {
        display: block;
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #1d4ed8, #7c3aed);
        color: #fff;
        text-align: center;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }
    .gaenity-sidebar-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(29,78,216,0.3);
        color: #fff;
    }
    .gaenity-sidebar-btn.secondary {
        background: #f1f5f9;
        color: #1e293b;
    }
    @media (max-width: 1024px) {
        .gaenity-forum-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="gaenity-forum">
    <div class="gaenity-forum-header">
        <h1><?php esc_html_e( 'Community Forum', 'gaenity-community' ); ?></h1>
        <p><?php esc_html_e( 'Share challenges, ask questions, and learn from peers across industries and regions', 'gaenity-community' ); ?></p>
    </div>

    <div class="gaenity-forum-layout">
        <div class="gaenity-forum-main">
            <form class="gaenity-forum-filters" method="get">
                <div class="gaenity-filter-group">
                    <label for="industry-filter"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                    <select id="industry-filter" name="industry">
                        <option value=""><?php esc_html_e( 'All Industries', 'gaenity-community' ); ?></option>
                        <?php
                        $industries = get_terms( array( 'taxonomy' => 'gaenity_industry', 'hide_empty' => false ) );
                        foreach ( $industries as $industry ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $industry->slug ),
                                selected( $selected_industry, $industry->slug, false ),
                                esc_html( $industry->name )
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="gaenity-filter-group">
                    <label for="region-filter"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                    <select id="region-filter" name="region">
                        <option value=""><?php esc_html_e( 'All Regions', 'gaenity-community' ); ?></option>
                        <?php
                        $regions = get_terms( array( 'taxonomy' => 'gaenity_region', 'hide_empty' => false ) );
                        foreach ( $regions as $region ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $region->slug ),
                                selected( $selected_region, $region->slug, false ),
                                esc_html( $region->name )
                            );
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="gaenity-filter-btn"><?php esc_html_e( 'Filter', 'gaenity-community' ); ?></button>
            </form>

            <?php if ( $discussions->have_posts() ) : ?>
                <?php while ( $discussions->have_posts() ) : $discussions->the_post();
                    $discussion_id = get_the_ID();
                    $regions = wp_get_post_terms( $discussion_id, 'gaenity_region', array( 'fields' => 'names' ) );
                    $industries = wp_get_post_terms( $discussion_id, 'gaenity_industry', array( 'fields' => 'names' ) );
                    $comment_count = get_comments_number( $discussion_id );
                    
                    // Get vote score
                    global $wpdb;
                    $votes_table = $wpdb->prefix . 'gaenity_discussion_votes';
                    $upvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE discussion_id = %d AND vote_type = 'up'", $discussion_id ) );
                    $downvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE discussion_id = %d AND vote_type = 'down'", $discussion_id ) );
                    $score = $upvotes - $downvotes;
                ?>
                    <article class="gaenity-discussion-card">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        
                        <div class="gaenity-discussion-excerpt">
                            <?php echo wp_trim_words( get_the_excerpt(), 30 ); ?>
                        </div>

                        <div class="gaenity-discussion-footer">
                            <div class="gaenity-discussion-tags">
                                <?php if ( ! empty( $regions ) ) : ?>
                                    <span class="gaenity-tag">📍 <?php echo esc_html( $regions[0] ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $industries ) ) : ?>
                                    <span class="gaenity-tag">🏢 <?php echo esc_html( $industries[0] ); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="gaenity-discussion-stats">
                                <span>👍 <?php echo esc_html( $score ); ?></span>
                                <span>💬 <?php echo esc_html( $comment_count ); ?></span>
                                <span>⏱️ <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ago' ); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>

                <?php
                // Pagination
                the_posts_pagination( array(
                    'mid_size' => 2,
                    'prev_text' => __( '← Previous', 'gaenity-community' ),
                    'next_text' => __( 'Next →', 'gaenity-community' ),
                ) );
                ?>
            <?php else : ?>
                <div style="text-align: center; padding: 4rem; background: #f8fafc; border-radius: 12px;">
                    <p style="font-size: 1.2rem; color: #64748b;"><?php esc_html_e( 'No discussions found. Be the first to start a conversation!', 'gaenity-community' ); ?></p>
                    <a href="<?php echo esc_url( get_option( 'gaenity_discussion_form_url', '#' ) ); ?>" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 2rem; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Start a Discussion', 'gaenity-community' ); ?></a>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>

        <aside class="gaenity-sidebar">
            <!-- Quick Actions -->
            <div class="gaenity-sidebar-widget">
                <h3><?php esc_html_e( 'Quick Actions', 'gaenity-community' ); ?></h3>
                <a href="<?php echo esc_url( get_option( 'gaenity_discussion_form_url', '#' ) ); ?>" class="gaenity-sidebar-btn">
                    💬 <?php esc_html_e( 'Start Discussion', 'gaenity-community' ); ?>
                </a>
                <a href="<?php echo esc_url( get_option( 'gaenity_ask_expert_url', '#' ) ); ?>" class="gaenity-sidebar-btn">
                    💡 <?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?>
                </a>
                <a href="<?php echo esc_url( get_option( 'gaenity_become_expert_url', '#' ) ); ?>" class="gaenity-sidebar-btn secondary">
                    🎓 <?php esc_html_e( 'Become an Expert', 'gaenity-community' ); ?>
                </a>
            </div>

            <!-- Polls Widget -->
            <?php echo do_shortcode( '[gaenity_polls]' ); ?>
        </aside>
    </div>
</div>

<?php get_footer(); ?>