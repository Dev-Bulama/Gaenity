<?php
/**
 * Single Discussion Template - WITH WORKING LIKE BUTTON
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$is_logged_in = is_user_logged_in();
$register_url = get_option( 'gaenity_register_url', wp_registration_url() );
$login_url = wp_login_url( get_permalink() );

while ( have_posts() ) : the_post();
    $post_id = get_the_ID();
    $author_id = get_post_field( 'post_author', $post_id );
    $author_name = get_the_author_meta( 'display_name', $author_id );
    $regions = wp_get_post_terms( $post_id, 'gaenity_region', array( 'fields' => 'names' ) );
    $industries = wp_get_post_terms( $post_id, 'gaenity_industry', array( 'fields' => 'names' ) );
    $votes = get_post_meta( $post_id, '_gaenity_discussion_votes', true );
    $votes = $votes ? absint( $votes ) : 0;
    $views = get_post_meta( $post_id, '_gaenity_discussion_views', true );
    $views = $views ? absint( $views ) + 1 : 1;
    update_post_meta( $post_id, '_gaenity_discussion_views', $views );
    
    // Check if user has liked this discussion
    $user_has_liked = false;
    if ( $is_logged_in ) {
        $user_id = get_current_user_id();
        $liked_by = get_post_meta( $post_id, '_gaenity_discussion_liked_by', true );
        if ( ! is_array( $liked_by ) ) {
            $liked_by = array();
        }
        $user_has_liked = in_array( $user_id, $liked_by );
    }
?>

<style>
.gaenity-single-discussion {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
}
.gaenity-single-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem 1rem;
}
.gaenity-discussion-header {
    background: #ffffff;
    border-radius: 1.25rem;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid #e2e8f0;
}
.gaenity-discussion-title {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 1rem 0;
    line-height: 1.3;
}
.gaenity-discussion-meta-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    font-size: 0.9375rem;
    color: #64748b;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 1.5rem;
}
.gaenity-meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.gaenity-discussion-content {
    font-size: 1.0625rem;
    line-height: 1.8;
    color: #334155;
}
.gaenity-discussion-content p {
    margin-bottom: 1rem;
}
.gaenity-discussion-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1.5rem;
}
.gaenity-tag {
    background: #f1f5f9;
    color: #475569;
    padding: 0.375rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
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
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f1f5f9;
}
.gaenity-action-btn {
    padding: 0.75rem 1.5rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.gaenity-action-btn:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
    transform: translateY(-2px);
}
.gaenity-action-btn.liked {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}
.gaenity-action-btn.liked:hover {
    background: #fbbf24;
    border-color: #f59e0b;
}
.gaenity-reply-cta {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 2px solid #c7d2fe;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    margin-bottom: 2rem;
    text-align: center;
}
.gaenity-reply-btn {
    background: #4f46e5;
    color: #ffffff;
    padding: 0.875rem 2rem;
    border: none;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
}
.gaenity-reply-btn:hover {
    background: #4338ca;
    transform: translateY(-2px);
    color: #ffffff;
}
.gaenity-comments-section {
    background: #ffffff;
    border-radius: 1.25rem;
    padding: 2rem;
    border: 1px solid #e2e8f0;
}
.gaenity-comments-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 1.5rem 0;
}
.gaenity-comment {
    padding: 1.5rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.gaenity-comment:last-child {
    border-bottom: none;
}
.gaenity-comment-author {
    font-weight: 600;
    color: #0f172a;
    font-size: 1rem;
}
.gaenity-comment-date {
    color: #94a3b8;
    font-size: 0.875rem;
    margin-left: 0.75rem;
}
.gaenity-comment-content {
    margin: 0.75rem 0;
    color: #475569;
    line-height: 1.7;
}
.gaenity-comment-actions {
    display: flex;
    gap: 1rem;
    margin-top: 0.75rem;
}
.gaenity-comment-btn {
    padding: 0.375rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.gaenity-comment-btn:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}
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
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.2s ease;
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
.children {
    margin-left: 2rem;
    border-left: 2px solid #e2e8f0;
    padding-left: 1.5rem;
}
@media (max-width: 640px) {
    .gaenity-discussion-title {
        font-size: 1.5rem;
    }
    .children {
        margin-left: 1rem;
        padding-left: 1rem;
    }
}
</style>

<div class="gaenity-single-discussion">
    <div class="gaenity-single-container">
        <!-- Discussion Header -->
        <div class="gaenity-discussion-header">
            <h1 class="gaenity-discussion-title"><?php the_title(); ?></h1>
            
            <div class="gaenity-discussion-meta-bar">
                <span class="gaenity-meta-item">
                    👤 <?php echo esc_html( $author_name ); ?>
                </span>
                <span class="gaenity-meta-item">
                    📅 <?php echo get_the_date(); ?>
                </span>
                <?php if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) : ?>
                    <span class="gaenity-meta-item">
                        📍 <?php echo esc_html( $regions[0] ); ?>
                    </span>
                <?php endif; ?>
                <?php if ( ! empty( $industries ) && ! is_wp_error( $industries ) ) : ?>
                    <span class="gaenity-meta-item">
                        🏢 <?php echo esc_html( $industries[0] ); ?>
                    </span>
                <?php endif; ?>
                <span class="gaenity-meta-item">
                    👁️ <?php echo esc_html( $views ); ?> Views
                </span>
                <span class="gaenity-meta-item">
                    👍 <span id="likes-count-<?php echo esc_attr( $post_id ); ?>"><?php echo esc_html( $votes ); ?></span> Likes
                </span>
            </div>

            <div class="gaenity-discussion-content">
                <?php the_content(); ?>
            </div>

            <?php if ( ! empty( $regions ) || ! empty( $industries ) ) : ?>
                <div class="gaenity-discussion-tags">
                    <?php if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) : ?>
                        <span class="gaenity-tag region"><?php echo esc_html( $regions[0] ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $industries ) && ! is_wp_error( $industries ) ) : ?>
                        <span class="gaenity-tag industry"><?php echo esc_html( $industries[0] ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="gaenity-discussion-actions">
                <?php if ( $is_logged_in ) : ?>
                    <a href="#respond" class="gaenity-action-btn">
                        💬 Reply
                    </a>
                    <button 
                        class="gaenity-action-btn<?php echo $user_has_liked ? ' liked' : ''; ?>" 
                        id="like-btn-<?php echo esc_attr( $post_id ); ?>"
                        onclick="toggleLike(<?php echo esc_js( $post_id ); ?>, <?php echo $user_has_liked ? 'true' : 'false'; ?>)"
                    >
                        <span id="like-icon-<?php echo esc_attr( $post_id ); ?>"><?php echo $user_has_liked ? '❤️' : '👍'; ?></span>
                        <span id="like-text-<?php echo esc_attr( $post_id ); ?>"><?php echo $user_has_liked ? 'Liked' : 'Like'; ?></span>
                    </button>
                <?php else : ?>
                    <button class="gaenity-action-btn" onclick="showAuthPopup()">
                        💬 Reply
                    </button>
                    <button class="gaenity-action-btn" onclick="showAuthPopup()">
                        👍 Like
                    </button>
                <?php endif; ?>
                <button class="gaenity-action-btn" onclick="shareDiscussion('<?php echo esc_js( get_the_permalink() ); ?>')">
                    🔗 Share
                </button>
            </div>
        </div>

        <!-- Reply CTA -->
        <div class="gaenity-reply-cta">
            <?php if ( $is_logged_in ) : ?>
                <a href="#respond" class="gaenity-reply-btn">
                    💬 Reply to this Question
                </a>
            <?php else : ?>
                <button class="gaenity-reply-btn" onclick="showAuthPopup()">
                    💬 Reply to this Question
                </button>
            <?php endif; ?>
        </div>

        <!-- Comments Section -->
        <div class="gaenity-comments-section">
            <h2 class="gaenity-comments-title">
                <?php
                $comment_count = get_comments_number();
                printf( 
                    _n( '%s Reply', '%s Replies', $comment_count, 'gaenity-community' ),
                    number_format_i18n( $comment_count )
                );
                ?>
            </h2>

            <?php if ( $is_logged_in ) : ?>
                <?php comments_template(); ?>
            <?php else : ?>
                <?php if ( have_comments() ) : ?>
                    <ol class="comment-list">
                        <?php
                        wp_list_comments( array(
                            'style' => 'ol',
                            'short_ping' => true,
                            'avatar_size' => 50,
                            'callback' => function( $comment, $args, $depth ) use ( $is_logged_in ) {
                                ?>
                                <li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
                                    <div class="gaenity-comment">
                                        <div>
                                            <span class="gaenity-comment-author"><?php comment_author(); ?></span>
                                            <span class="gaenity-comment-date"><?php comment_date(); ?> at <?php comment_time(); ?></span>
                                        </div>
                                        <div class="gaenity-comment-content">
                                            <?php comment_text(); ?>
                                        </div>
                                        <div class="gaenity-comment-actions">
                                            <button class="gaenity-comment-btn" onclick="showAuthPopup()">
                                                💬 Reply
                                            </button>
                                            <button class="gaenity-comment-btn" onclick="showAuthPopup()">
                                                👍 Like
                                            </button>
                                            <button class="gaenity-comment-btn" onclick="showAuthPopup()">
                                                🚩 Report
                                            </button>
                                        </div>
                                    </div>
                                <?php
                            }
                        ) );
                        ?>
                    </ol>
                <?php endif; ?>

                <div style="background: #fef3c7; border: 2px solid #fbbf24; border-radius: 1rem; padding: 1.5rem; text-align: center; margin-top: 2rem;">
                    <p style="margin: 0 0 1rem 0; font-weight: 600; color: #92400e;">
                        <?php esc_html_e( 'You need to register or sign in to participate in the discussion.', 'gaenity-community' ); ?>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="<?php echo esc_url( $register_url ); ?>" class="gaenity-reply-btn">
                            <?php esc_html_e( 'Join Now', 'gaenity-community' ); ?>
                        </a>
                        <a href="<?php echo esc_url( $login_url ); ?>" class="gaenity-reply-btn" style="background: #64748b;">
                            <?php esc_html_e( 'Sign In', 'gaenity-community' ); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Auth Popup Modal -->
<div id="gaenity-auth-popup" class="gaenity-popup-overlay" onclick="if(event.target === this) closeAuthPopup()">
    <div class="gaenity-popup">
        <h2 class="gaenity-popup-title"><?php esc_html_e( 'Join the Discussion', 'gaenity-community' ); ?></h2>
        <p class="gaenity-popup-text">"<?php esc_html_e( 'You need to register or sign in to participate in the discussion.', 'gaenity-community' ); ?>"</p>
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
function showAuthPopup() {
    document.getElementById('gaenity-auth-popup').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeAuthPopup() {
    document.getElementById('gaenity-auth-popup').classList.remove('active');
    document.body.style.overflow = '';
}
function shareDiscussion(url) {
    if (navigator.share) {
        navigator.share({
            title: 'Check out this discussion',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(function() {
            alert('Link copied to clipboard!');
        });
    }
}

// Like/Unlike functionality
function toggleLike(postId, isLiked) {
    const btn = document.getElementById('like-btn-' + postId);
    const icon = document.getElementById('like-icon-' + postId);
    const text = document.getElementById('like-text-' + postId);
    const count = document.getElementById('likes-count-' + postId);
    
    // Disable button while processing
    btn.disabled = true;
    
    // Prepare data
    const formData = new FormData();
    formData.append('action', 'gaenity_toggle_discussion_like');
    formData.append('post_id', postId);
    formData.append('nonce', '<?php echo wp_create_nonce( "gaenity_like_" . $post_id ); ?>');
    
    // Send AJAX request
    fetch('<?php echo admin_url( "admin-ajax.php" ); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const newCount = parseInt(count.textContent) + (data.data.liked ? 1 : -1);
            count.textContent = newCount;
            
            if (data.data.liked) {
                btn.classList.add('liked');
                icon.textContent = '❤️';
                text.textContent = 'Liked';
            } else {
                btn.classList.remove('liked');
                icon.textContent = '👍';
                text.textContent = 'Like';
            }
            
            // Update onclick attribute for next toggle
            btn.setAttribute('onclick', 'toggleLike(' + postId + ', ' + data.data.liked + ')');
        } else {
            alert(data.data || 'Error liking post');
        }
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing like');
        btn.disabled = false;
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAuthPopup();
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>