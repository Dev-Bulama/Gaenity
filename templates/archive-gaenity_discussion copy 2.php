<?php
/**
 * Template for Discussion Archive (Forum Page)
 * 
 * @package GaeinityCommunity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Get filters
$selected_region = isset( $_GET['region'] ) ? sanitize_text_field( $_GET['region'] ) : '';
$selected_industry = isset( $_GET['industry'] ) ? sanitize_text_field( $_GET['industry'] ) : '';
$selected_topic = isset( $_GET['topic'] ) ? sanitize_text_field( $_GET['topic'] ) : '';
$sort_by = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'new';
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Build query args
$query_args = array(
    'post_type' => 'gaenity_discussion',
    'posts_per_page' => 15,
    'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
    'post_status' => 'publish',
);

// Apply search
if ( ! empty( $search_query ) ) {
    $query_args['s'] = $search_query;
}

// Apply sorting
switch ( $sort_by ) {
    case 'trending':
        $query_args['orderby'] = 'comment_count';
        $query_args['order'] = 'DESC';
        break;
    case 'top':
        $query_args['meta_key'] = '_gaenity_discussion_votes';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'new':
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
}

// Apply taxonomy filters
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
if ( $selected_topic ) {
    $tax_query[] = array(
        'taxonomy' => 'gaenity_challenge',
        'field' => 'slug',
        'terms' => $selected_topic,
    );
}

if ( ! empty( $tax_query ) ) {
    $query_args['tax_query'] = $tax_query;
}

$discussions = new WP_Query( $query_args );

// Get plugin instance for helper methods
global $gaenity_community_plugin;
$is_logged_in = is_user_logged_in();
$register_url = get_option( 'gaenity_register_url', wp_registration_url() );
$login_url = wp_login_url( get_permalink() );
$discussion_form_url = get_option( 'gaenity_discussion_form_url', '#' );
$ask_expert_url = get_option( 'gaenity_ask_expert_url', '#' );
$become_expert_url = get_option( 'gaenity_become_expert_url', '#' );
$polls_url = get_option( 'gaenity_polls_url', '#' );
?>

<style>
/* Forum Page Styles */
.gaenity-forum-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
}

/* Header Section */
.gaenity-forum-header {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 2.5rem 0;
}
.gaenity-forum-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}
.gaenity-forum-title {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.75rem 0;
    letter-spacing: -0.025em;
}
.gaenity-forum-blurb {
    font-size: 1.125rem;
    color: #64748b;
    line-height: 1.7;
    font-style: italic;
    margin: 0;
}

/* Join Conversation Bar */
.gaenity-join-bar {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 2px solid #c7d2fe;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    margin: 2rem 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.gaenity-join-text {
    font-size: 1rem;
    color: #1e40af;
    font-weight: 600;
    margin: 0;
}
.gaenity-join-btn {
    background: #4f46e5;
    color: #ffffff;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
}
.gaenity-join-btn:hover {
    background: #4338ca;
    transform: translateY(-2px);
    color: #ffffff;
}

/* Filter Bar */
.gaenity-filter-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.gaenity-filter-form {
    display: grid;
    grid-template-columns: 2fr repeat(4, 1fr) auto;
    gap: 1rem;
    align-items: end;
}
.gaenity-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.gaenity-filter-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #475569;
}
.gaenity-filter-input,
.gaenity-filter-select {
    padding: 0.625rem 0.875rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    font-family: inherit;
    background: #f8fafc;
    transition: all 0.2s ease;
}
.gaenity-filter-input:focus,
.gaenity-filter-select:focus {
    outline: none;
    border-color: #4f46e5;
    background: #ffffff;
}
.gaenity-filter-submit {
    padding: 0.625rem 1.25rem;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s ease;
}
.gaenity-filter-submit:hover {
    background: #4338ca;
}

/* Main Layout */
.gaenity-forum-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-top: 2rem;
}

/* Discussion Cards */
.gaenity-discussions-area {
    min-width: 0;
}
.gaenity-discussion-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}
.gaenity-discussion-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.gaenity-discussion-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}
.gaenity-discussion-title a {
    color: #0f172a;
    text-decoration: none;
    transition: color 0.2s ease;
}
.gaenity-discussion-title a:hover {
    color: #4f46e5;
}
.gaenity-discussion-excerpt {
    color: #64748b;
    font-size: 0.9375rem;
    line-height: 1.6;
    margin: 0 0 1rem 0;
}
.gaenity-discussion-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 1rem;
}
.gaenity-meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.gaenity-discussion-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.gaenity-tag {
    background: #f1f5f9;
    color: #475569;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
}
.gaenity-tag.region {
    background: #dbeafe;
    color: #1e40af;
}
.gaenity-tag.industry {
    background: #fef3c7;
    color: #92400e;
}
.gaenity-discussion-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.gaenity-action-btn {
    padding: 0.5rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.gaenity-action-btn:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}

/* Sidebar */
.gaenity-forum-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.gaenity-sidebar-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
}
.gaenity-sidebar-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem 0;
}
.gaenity-sidebar-text {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 1rem 0;
}
.gaenity-sidebar-btn {
    width: 100%;
    padding: 0.75rem;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 0.625rem;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.2s ease;
}
.gaenity-sidebar-btn:hover {
    background: #4338ca;
    color: #ffffff;
    transform: translateY(-2px);
}
.gaenity-sidebar-btn.secondary {
    background: #f1f5f9;
    color: #475569;
}
.gaenity-sidebar-btn.secondary:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.gaenity-spotlight-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.gaenity-spotlight-item {
    padding: 0.625rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.gaenity-spotlight-item:last-child {
    border-bottom: none;
}
.gaenity-spotlight-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #475569;
    text-decoration: none;
    font-size: 0.9375rem;
    transition: color 0.2s ease;
}
.gaenity-spotlight-link:hover {
    color: #4f46e5;
}
.gaenity-spotlight-count {
    background: #f1f5f9;
    color: #64748b;
    padding: 0.125rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 600;
}

/* Popup Modal */
.gaenity-popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.gaenity-popup-overlay.active {
    display: flex;
}
.gaenity-popup {
    background: #ffffff;
    border-radius: 1.25rem;
    padding: 2rem;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}
.gaenity-popup-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem 0;
}
.gaenity-popup-text {
    font-size: 1rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}
.gaenity-popup-actions {
    display: flex;
    gap: 0.75rem;
}
.gaenity-popup-btn {
    flex: 1;
    padding: 0.875rem;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.gaenity-popup-btn.primary {
    background: #4f46e5;
    color: #ffffff;
}
.gaenity-popup-btn.primary:hover {
    background: #4338ca;
    color: #ffffff;
}
.gaenity-popup-btn.secondary {
    background: #f1f5f9;
    color: #475569;
}
.gaenity-popup-btn.secondary:hover {
    background: #e2e8f0;
}

/* Empty State */
.gaenity-empty-state {
    background: #ffffff;
    border: 2px dashed #e2e8f0;
    border-radius: 1rem;
    padding: 3rem 2rem;
    text-align: center;
    color: #64748b;
    font-size: 1rem;
}

/* Pagination */
.gaenity-pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
}
.gaenity-page-link {
    padding: 0.5rem 1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    color: #475569;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}
.gaenity-page-link:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}
.gaenity-page-link.current {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}

/* Responsive */
@media (max-width: 1024px) {
    .gaenity-forum-content {
        grid-template-columns: 1fr;
    }
    .gaenity-filter-form {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .gaenity-join-bar {
        flex-direction: column;
        text-align: center;
    }
    .gaenity-forum-title {
        font-size: 1.5rem;
    }
    .gaenity-forum-blurb {
        font-size: 1rem;
    }
}
</style>

<div class="gaenity-forum-page">
    <!-- Header -->
    <div class="gaenity-forum-header">
        <div class="gaenity-forum-container">
            <h1 class="gaenity-forum-title"><?php esc_html_e( 'Community Forum', 'gaenity-community' ); ?></h1>
            <p class="gaenity-forum-blurb">"<?php esc_html_e( 'Explore questions and answers from real business owners. Share your experience or get advice from peers and experts.', 'gaenity-community' ); ?>"</p>
        </div>
    </div>

    <div class="gaenity-forum-container">
        <!-- Join Conversation Bar -->
        <div class="gaenity-join-bar">
            <p class="gaenity-join-text"><?php esc_html_e( 'Have a question or want to share your experience?', 'gaenity-community' ); ?></p>
            <?php if ( $is_logged_in ) : ?>
                <a href="<?php echo esc_url( $discussion_form_url ); ?>" class="gaenity-join-btn">
                    <?php esc_html_e( 'Post a Question', 'gaenity-community' ); ?>
                </a>
            <?php else : ?>
                <button class="gaenity-join-btn" onclick="showAuthPopup()">
                    <?php esc_html_e( 'Join the Conversation', 'gaenity-community' ); ?>
                </button>
            <?php endif; ?>
        </div>

        <!-- Filter Bar -->
        <div class="gaenity-filter-bar">
            <form class="gaenity-filter-form" method="get">
                <!-- Search -->
                <div class="gaenity-filter-group">
                    <label class="gaenity-filter-label"><?php esc_html_e( 'Search', 'gaenity-community' ); ?></label>
                    <input 
                        type="text" 
                        name="s" 
                        class="gaenity-filter-input" 
                        placeholder="<?php esc_attr_e( 'Search questions...', 'gaenity-community' ); ?>"
                        value="<?php echo esc_attr( $search_query ); ?>"
                    />
                </div>

                <!-- Region -->
                <div class="gaenity-filter-group">
                    <label class="gaenity-filter-label"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                    <select name="region" class="gaenity-filter-select">
                        <option value=""><?php esc_html_e( 'All Regions', 'gaenity-community' ); ?></option>
                        <?php
                        if ( $gaenity_community_plugin ) {
                            foreach ( $gaenity_community_plugin->get_region_options() as $region ) {
                                $slug = sanitize_title( $region );
                                printf(
                                    '<option value="%s"%s>%s</option>',
                                    esc_attr( $slug ),
                                    selected( $selected_region, $slug, false ),
                                    esc_html( $region )
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Industry -->
                <div class="gaenity-filter-group">
                    <label class="gaenity-filter-label"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                    <select name="industry" class="gaenity-filter-select">
                        <option value=""><?php esc_html_e( 'All Industries', 'gaenity-community' ); ?></option>
                        <?php
                        if ( $gaenity_community_plugin ) {
                            foreach ( $gaenity_community_plugin->get_industry_options() as $industry ) {
                                $slug = sanitize_title( $industry );
                                printf(
                                    '<option value="%s"%s>%s</option>',
                                    esc_attr( $slug ),
                                    selected( $selected_industry, $slug, false ),
                                    esc_html( $industry )
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Topic -->
                <div class="gaenity-filter-group">
                    <label class="gaenity-filter-label"><?php esc_html_e( 'Topic', 'gaenity-community' ); ?></label>
                    <select name="topic" class="gaenity-filter-select">
                        <option value=""><?php esc_html_e( 'All Topics', 'gaenity-community' ); ?></option>
                        <?php
                        if ( $gaenity_community_plugin ) {
                            foreach ( $gaenity_community_plugin->get_challenge_options() as $challenge ) {
                                $slug = sanitize_title( $challenge );
                                printf(
                                    '<option value="%s"%s>%s</option>',
                                    esc_attr( $slug ),
                                    selected( $selected_topic, $slug, false ),
                                    esc_html( $challenge )
                                );
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Sort -->
                <div class="gaenity-filter-group">
                    <label class="gaenity-filter-label"><?php esc_html_e( 'Sort by', 'gaenity-community' ); ?></label>
                    <select name="sort" class="gaenity-filter-select">
                        <option value="new"<?php selected( $sort_by, 'new' ); ?>><?php esc_html_e( 'New', 'gaenity-community' ); ?></option>
                        <option value="trending"<?php selected( $sort_by, 'trending' ); ?>><?php esc_html_e( 'Trending', 'gaenity-community' ); ?></option>
                        <option value="top"<?php selected( $sort_by, 'top' ); ?>><?php esc_html_e( 'Top', 'gaenity-community' ); ?></option>
                    </select>
                </div>

                <!-- Submit -->
                <button type="submit" class="gaenity-filter-submit">
                    <?php esc_html_e( 'Filter', 'gaenity-community' ); ?>
                </button>
            </form>
        </div>

        <!-- Main Content Grid -->
        <div class="gaenity-forum-content">
            <!-- Discussions Area -->
            <div class="gaenity-discussions-area">
                <?php if ( $discussions->have_posts() ) : ?>
                    <?php while ( $discussions->have_posts() ) : $discussions->the_post(); 
                        $post_id = get_the_ID();
                        $regions = wp_get_post_terms( $post_id, 'gaenity_region', array( 'fields' => 'names' ) );
                        $industries = wp_get_post_terms( $post_id, 'gaenity_industry', array( 'fields' => 'names' ) );
                        $comment_count = get_comments_number( $post_id );
                        $votes = get_post_meta( $post_id, '_gaenity_discussion_votes', true );
                        $votes = $votes ? absint( $votes ) : 0;
                        $views = get_post_meta( $post_id, '_gaenity_discussion_views', true );
                        $views = $views ? absint( $views ) : 0;
                    ?>
                        <div class="gaenity-discussion-card">
                            <h2 class="gaenity-discussion-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <div class="gaenity-discussion-excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_excerpt(), 25, '...' ) ); ?>
                            </div>

                            <?php if ( ! empty( $regions ) || ! empty( $industries ) ) : ?>
                                <div class="gaenity-discussion-tags">
                                    <?php if ( ! empty( $regions ) ) : ?>
                                        <span class="gaenity-tag region">📍 <?php echo esc_html( $regions[0] ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $industries ) ) : ?>
                                        <span class="gaenity-tag industry">🏢 <?php echo esc_html( $industries[0] ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="gaenity-discussion-meta">
                                <span class="gaenity-meta-item">👍 <?php echo esc_html( $votes ); ?> <?php esc_html_e( 'Likes', 'gaenity-community' ); ?></span>
                                <span class="gaenity-meta-item">💬 <?php echo esc_html( $comment_count ); ?> <?php esc_html_e( 'Replies', 'gaenity-community' ); ?></span>
                                <span class="gaenity-meta-item">👁️ <?php echo esc_html( $views ); ?> <?php esc_html_e( 'Views', 'gaenity-community' ); ?></span>
                                <span class="gaenity-meta-item">⏰ <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'gaenity-community' ); ?></span>
                            </div>

                            <div class="gaenity-discussion-actions">
                                <?php if ( $is_logged_in ) : ?>
                                    <a href="<?php the_permalink(); ?>#respond" class="gaenity-action-btn">
                                        💬 <?php esc_html_e( 'Reply', 'gaenity-community' ); ?>
                                    </a>
                                    <button class="gaenity-action-btn" onclick="likeDiscussion(<?php echo esc_js( $post_id ); ?>)">
                                        👍 <?php esc_html_e( 'Like', 'gaenity-community' ); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="gaenity-action-btn" onclick="showAuthPopup()">
                                        💬 <?php esc_html_e( 'Reply', 'gaenity-community' ); ?>
                                    </button>
                                    <button class="gaenity-action-btn" onclick="showAuthPopup()">
                                        👍 <?php esc_html_e( 'Like', 'gaenity-community' ); ?>
                                    </button>
                                <?php endif; ?>
                                <button class="gaenity-action-btn" onclick="shareDiscussion('<?php echo esc_js( get_the_permalink() ); ?>')">
                                    🔗 <?php esc_html_e( 'Share', 'gaenity-community' ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Pagination -->
                    <?php
                    $total_pages = $discussions->max_num_pages;
                    if ( $total_pages > 1 ) :
                        $current_page = max( 1, get_query_var( 'paged' ) );
                    ?>
                        <div class="gaenity-pagination">
                            <?php if ( $current_page > 1 ) : ?>
                                <a href="<?php echo esc_url( get_pagenum_link( $current_page - 1 ) ); ?>" class="gaenity-page-link">
                                    ‹ <?php esc_html_e( 'Previous', 'gaenity-community' ); ?>
                                </a>
                            <?php endif; ?>

                            <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                                <a href="<?php echo esc_url( get_pagenum_link( $i ) ); ?>" class="gaenity-page-link<?php echo $i === $current_page ? ' current' : ''; ?>">
                                    <?php echo esc_html( $i ); ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ( $current_page < $total_pages ) : ?>
                                <a href="<?php echo esc_url( get_pagenum_link( $current_page + 1 ) ); ?>" class="gaenity-page-link">
                                    <?php esc_html_e( 'Next', 'gaenity-community' ); ?> ›
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="gaenity-empty-state">
                        <p><?php esc_html_e( 'No discussions found. Be the first to start a conversation!', 'gaenity-community' ); ?></p>
                        <?php if ( $is_logged_in ) : ?>
                            <a href="<?php echo esc_url( $discussion_form_url ); ?>" class="gaenity-sidebar-btn" style="margin-top: 1rem; max-width: 300px; margin-left: auto; margin-right: auto;">
                                <?php esc_html_e( 'Post a Question', 'gaenity-community' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>

            <!-- Sidebar -->
            <aside class="gaenity-forum-sidebar">
                <!-- Ask an Expert Box -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?></h3>
                    <p class="gaenity-sidebar-text"><?php esc_html_e( "Didn't find the answer you need? Ask a verified expert and get a detailed response within 48 hours.", 'gaenity-community' ); ?></p>
                    <a href="<?php echo esc_url( $ask_expert_url ); ?>" class="gaenity-sidebar-btn">
                        <?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?>
                    </a>
                </div>

                <!-- Become an Expert Box -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'Become an Expert', 'gaenity-community' ); ?></h3>
                    <p class="gaenity-sidebar-text"><?php esc_html_e( 'Join our expert network and earn by helping small businesses.', 'gaenity-community' ); ?></p>
                    <a href="<?php echo esc_url( $become_expert_url ); ?>" class="gaenity-sidebar-btn secondary">
                        <?php esc_html_e( 'Apply Now', 'gaenity-community' ); ?>
                    </a>
                </div>

                <!-- Polls Box -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'Polls', 'gaenity-community' ); ?></h3>
                    <p class="gaenity-sidebar-text"><?php esc_html_e( 'Vote on current polls and see what others think.', 'gaenity-community' ); ?></p>
                    <a href="<?php echo esc_url( $polls_url ); ?>" class="gaenity-sidebar-btn secondary">
                        <?php esc_html_e( 'View Polls', 'gaenity-community' ); ?>
                    </a>
                </div>

                <!-- By Region Spotlight -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'By Region', 'gaenity-community' ); ?></h3>
                    <ul class="gaenity-spotlight-list">
                        <?php
                        if ( $gaenity_community_plugin ) {
                            $regions = array_slice( $gaenity_community_plugin->get_region_options(), 0, 5 );
                            foreach ( $regions as $region ) {
                                $slug = sanitize_title( $region );
                                $url = add_query_arg( 'region', $slug, get_post_type_archive_link( 'gaenity_discussion' ) );
                                $count = rand( 10, 150 ); // Replace with actual count
                                printf(
                                    '<li class="gaenity-spotlight-item"><a href="%s" class="gaenity-spotlight-link"><span>%s</span><span class="gaenity-spotlight-count">%d</span></a></li>',
                                    esc_url( $url ),
                                    esc_html( $region ),
                                    $count
                                );
                            }
                        }
                        ?>
                    </ul>
                </div>

                <!-- By Industry Spotlight -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'By Industry', 'gaenity-community' ); ?></h3>
                    <ul class="gaenity-spotlight-list">
                        <?php
                        if ( $gaenity_community_plugin ) {
                            $industries = array_slice( $gaenity_community_plugin->get_industry_options(), 0, 5 );
                            foreach ( $industries as $industry ) {
                                $slug = sanitize_title( $industry );
                                $url = add_query_arg( 'industry', $slug, get_post_type_archive_link( 'gaenity_discussion' ) );
                                $count = rand( 10, 120 ); // Replace with actual count
                                printf(
                                    '<li class="gaenity-spotlight-item"><a href="%s" class="gaenity-spotlight-link"><span>%s</span><span class="gaenity-spotlight-count">%d</span></a></li>',
                                    esc_url( $url ),
                                    esc_html( $industry ),
                                    $count
                                );
                            }
                        }
                        ?>
                    </ul>
                </div>

                <!-- Trending Topics Spotlight -->
                <div class="gaenity-sidebar-box">
                    <h3 class="gaenity-sidebar-title"><?php esc_html_e( 'Trending Topics', 'gaenity-community' ); ?></h3>
                    <ul class="gaenity-spotlight-list">
                        <?php
                        if ( $gaenity_community_plugin ) {
                            $challenges = array_slice( $gaenity_community_plugin->get_challenge_options(), 0, 5 );
                            foreach ( $challenges as $challenge ) {
                                $slug = sanitize_title( $challenge );
                                $url = add_query_arg( 'topic', $slug, get_post_type_archive_link( 'gaenity_discussion' ) );
                                $count = rand( 5, 80 ); // Replace with actual count
                                printf(
                                    '<li class="gaenity-spotlight-item"><a href="%s" class="gaenity-spotlight-link"><span>%s</span><span class="gaenity-spotlight-count">%d</span></a></li>',
                                    esc_url( $url ),
                                    esc_html( $challenge ),
                                    $count
                                );
                            }
                        }
                        ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Auth Popup Modal -->
<div id="gaenity-auth-popup" class="gaenity-popup-overlay" onclick="if(event.target === this) closeAuthPopup()">
    <div class="gaenity-popup">
        <h2 class="gaenity-popup-title"><?php esc_html_e( 'Join the Community', 'gaenity-community' ); ?></h2>
        <p class="gaenity-popup-text">"<?php esc_html_e( 'Join the community to reply, vote, or post your own question.', 'gaenity-community' ); ?>"</p>
        <div class="gaenity-popup-actions">
            <a href="<?php echo esc_url( $register_url ); ?>" class="gaenity-popup-btn primary">
                <?php esc_html_e( 'Join Now', 'gaenity-community' ); ?>
            </a>
            <a href="<?php echo esc_url( $login_url ); ?>" class="gaenity-popup-btn secondary">
                <?php esc_html_e( 'Sign In', 'gaenity-community' ); ?>
            </a>
        </div>
    </div>
</div>

<script>
// Show auth popup
function showAuthPopup() {
    document.getElementById('gaenity-auth-popup').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close auth popup
function closeAuthPopup() {
    document.getElementById('gaenity-auth-popup').classList.remove('active');
    document.body.style.overflow = '';
}

// Like discussion (for logged-in users)
function likeDiscussion(postId) {
    // Add your AJAX call here
    alert('Like functionality - implement with AJAX');
}

// Share discussion
function shareDiscussion(url) {
    if (navigator.share) {
        navigator.share({
            title: 'Check out this discussion',
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(function() {
            alert('Link copied to clipboard!');
        });
    }
}

// Close popup on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAuthPopup();
    }
});
</script>

<?php get_footer(); ?>