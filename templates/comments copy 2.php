<?php
/**
 * Custom Comments Template for Gaenity Discussions
 */

if ( post_password_required() ) {
    return;
}

// Get current user info
$current_user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();
?>

<style>
    .gaenity-comments-wrapper {
        margin-top: 2rem;
    }
    .gaenity-comments-wrapper > h3 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.75rem;
    }
    
    /* Join Conversation Box for Visitors */
    .gaenity-join-conversation {
        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        border: 2px solid #3b82f6;
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 3rem;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.15);
    }
    .gaenity-join-conversation h3 {
        font-size: 2rem;
        color: #1e40af;
        margin: 0 0 1rem 0;
        font-weight: 700;
    }
    .gaenity-join-conversation p {
        font-size: 1.1rem;
        color: #475569;
        margin-bottom: 2rem;
        line-height: 1.6;
    }
    .gaenity-join-btn {
        display: inline-block;
        background: #1d4ed8;
        color: #fff;
        padding: 1rem 2.5rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(29, 78, 216, 0.3);
        margin: 0 0.5rem;
    }
    .gaenity-join-btn:hover {
        background: #1e40af;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(29, 78, 216, 0.4);
        color: #fff;
    }
    .gaenity-register-link {
        display: block;
        margin-top: 1.5rem;
        color: #1e40af;
        font-size: 1rem;
    }
    .gaenity-register-link a {
        color: #1d4ed8;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: border-color 0.2s;
    }
    .gaenity-register-link a:hover {
        border-bottom-color: #1d4ed8;
    }
    
    /* Comment List Styles */
    .comment-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
    }
    .comment-list li {
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 0;
    }
    .comment-list > li:last-child {
        border-bottom: none;
    }
    .comment-list .children {
        list-style: none;
        margin-left: 0;
        padding-left: 3rem;
        border-left: 3px solid #e0e7ff;
        margin-top: 1.5rem;
    }
    .comment-body {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
        position: relative;
    }
    .comment-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .comment-author img {
        border-radius: 50%;
    }
    .comment-author .fn {
        font-weight: 700;
        color: #1e293b;
        font-style: normal;
        font-size: 1rem;
    }
    .comment-author .fn a {
        color: #1e293b;
        text-decoration: none;
    }
    .comment-author .fn a:hover {
        color: #1d4ed8;
    }
    .comment-metadata {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }
    .comment-metadata a {
        color: inherit;
        text-decoration: none;
    }
    .comment-metadata a:hover {
        color: #1d4ed8;
    }
    .comment-content {
        line-height: 1.6;
        color: #475569;
        margin: 0.75rem 0;
    }
    .comment-content p {
        margin: 0 0 0.75rem 0;
    }
    .comment-content p:last-child {
        margin-bottom: 0;
    }
    
    /* Comment Actions */
    .comment-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    /* Comment Reactions */
    .comment-reactions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fff;
        padding: 0.5rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .comment-reaction-btn {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.75rem;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        font-weight: 600;
        color: #64748b;
    }
    .comment-reaction-btn:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    .comment-reaction-btn.active-like {
        background: #dcfce7;
        border-color: #10b981;
        color: #065f46;
    }
    .comment-reaction-btn.active-dislike {
        background: #fee2e2;
        border-color: #ef4444;
        color: #991b1b;
    }
    .comment-reaction-btn .emoji {
        font-size: 1.1rem;
    }
    .comment-reaction-btn .count {
        font-size: 0.85rem;
    }
    
    .reply {
        margin-top: 0;
    }
    .comment-reply-link {
        background: #1d4ed8;
        color: #fff;
        padding: 0.5rem 1.25rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .comment-reply-link:hover {
        background: #1e40af;
        transform: translateY(-2px);
        color: #fff;
    }
    
    /* Comment Form Styles */
    #respond {
        background: #fff;
        padding: 2rem;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        margin-bottom: 3rem;
    }
    .comment-reply-title {
        font-size: 1.3rem;
        margin: 0 0 1.5rem 0;
        color: #1e293b;
    }
    .comment-reply-title small {
        font-size: 0.8rem;
        margin-left: 1rem;
    }
    .comment-reply-title small a {
        color: #ef4444;
        text-decoration: none;
        font-weight: 600;
    }
    .comment-reply-title small a:hover {
        text-decoration: underline;
    }
    .comment-form p {
        margin-bottom: 1rem;
    }
    .comment-form label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1e293b;
    }
    .comment-form input[type="text"],
    .comment-form input[type="email"],
    .comment-form input[type="url"],
    .comment-form textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        font-family: inherit;
        transition: border-color 0.2s ease;
    }
    .comment-form input:focus,
    .comment-form textarea:focus {
        outline: none;
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
    }
    .comment-form textarea {
        min-height: 120px;
        resize: vertical;
    }
    .form-submit {
        margin-top: 1rem;
    }
    .form-submit .submit {
        background: #1d4ed8;
        color: #fff;
        border: none;
        padding: 0.875rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .form-submit .submit:hover {
        background: #1e40af;
        transform: translateY(-2px);
    }
    .logged-in-as {
        background: #eff6ff;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: #1e40af;
        border-left: 3px solid #3b82f6;
    }
    .logged-in-as a {
        color: #ef4444;
        font-weight: 600;
        text-decoration: none;
    }
    .gaenity-no-comments {
        background: #f8fafc;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        color: #64748b;
    }
    
    /* Existing Comments Section */
    .gaenity-existing-comments {
        margin-top: 3rem;
    }
    
    @media (max-width: 768px) {
        .gaenity-join-conversation {
            padding: 2rem 1.5rem;
        }
        .gaenity-join-conversation h3 {
            font-size: 1.5rem;
        }
        .gaenity-join-btn {
            display: block;
            margin: 0.5rem 0;
        }
        .comment-list .children {
            padding-left: 1.5rem;
        }
    }
</style>

<div id="comments" class="gaenity-comments-wrapper">

    <?php
    // STEP 1: SHOW REPLY FORM OR LOGIN PROMPT
    if ( comments_open() ) :
        if ( $is_logged_in ) :
            // Show comment form for logged-in users
            $commenter = wp_get_current_commenter();
            $req = get_option( 'require_name_email' );
            $aria_req = ( $req ? " aria-required='true'" : '' );
            
            $fields = array(
                'author' => '<p class="comment-form-author">' .
                            '<label for="author">' . __( 'Name', 'gaenity-community' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
                            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
                'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'gaenity-community' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
                            '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
                'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website', 'gaenity-community' ) . '</label> ' .
                            '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
            );
            
            comment_form( array(
                'title_reply'        => __( 'Leave a Reply', 'gaenity-community' ),
                'title_reply_to'     => __( 'Reply to %s', 'gaenity-community' ),
                'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
                'title_reply_after'  => '</h3>',
                'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . __( 'Your Reply', 'gaenity-community' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
                'fields'             => $fields,
                'label_submit'       => __( 'Post Reply', 'gaenity-community' ),
                'logged_in_as'       => '<p class="logged-in-as">' . sprintf( 
                    __( 'Logged in as %s. <a href="%s">Log out?</a>', 'gaenity-community' ), 
                    wp_get_current_user()->display_name,
                    wp_logout_url( get_permalink() )
                ) . '</p>',
                'comment_notes_before' => '',
                'comment_notes_after'  => '',
            ) );
        else :
            // Show "Join the Conversation" box for visitors
            $login_url = wp_login_url( get_permalink() );
            $register_url = wp_registration_url();
            ?>
            <div class="gaenity-join-conversation">
                <h3><?php esc_html_e( 'Join the Conversation', 'gaenity-community' ); ?></h3>
                <p><?php esc_html_e( 'Log in to share your thoughts, ask follow-up questions, and connect with the community.', 'gaenity-community' ); ?></p>
                <a href="<?php echo esc_url( $login_url ); ?>" class="gaenity-join-btn">
                    <?php esc_html_e( 'Log In to Reply', 'gaenity-community' ); ?>
                </a>
                <div class="gaenity-register-link">
                    <?php esc_html_e( "Don't have an account?", 'gaenity-community' ); ?> 
                    <a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Register here', 'gaenity-community' ); ?></a>
                </div>
            </div>
            <?php
        endif;
    elseif ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
        ?>
        <p class="gaenity-no-comments"><?php esc_html_e( 'Comments are closed.', 'gaenity-community' ); ?></p>
    <?php endif; ?>

    <?php 
    // STEP 2: SHOW EXISTING COMMENTS LIST (AFTER THE FORM/PROMPT)
    if ( have_comments() ) : ?>
        <div class="gaenity-existing-comments">
            <h3>
                <?php
                $comment_count = get_comments_number();
                if ( 1 === $comment_count ) {
                    esc_html_e( '1 Reply', 'gaenity-community' );
                } else {
                    printf(
                        esc_html( _n( '%s Reply', '%s Replies', $comment_count, 'gaenity-community' ) ),
                        number_format_i18n( $comment_count )
                    );
                }
                ?>
            </h3>

            <ol class="comment-list">
                <?php
                wp_list_comments( array(
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 50,
                    'callback'    => 'gaenity_custom_comment_callback',
                ) );
                ?>
            </ol>

            <?php
            the_comments_navigation( array(
                'prev_text' => __( '← Older Replies', 'gaenity-community' ),
                'next_text' => __( 'Newer Replies →', 'gaenity-community' ),
            ) );
            ?>
        </div>
    <?php endif; ?>

</div>

<?php
/**
 * Custom comment callback with reactions
 */
function gaenity_custom_comment_callback( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    $comment_id = get_comment_ID();
    
    // Get reaction counts
    global $wpdb;
    $reactions_table = $wpdb->prefix . 'gaenity_comment_reactions';
    $likes = $wpdb->get_var( $wpdb->prepare( 
        "SELECT COUNT(*) FROM $reactions_table WHERE comment_id = %d AND reaction_type = 'like'", 
        $comment_id 
    ) );
    $dislikes = $wpdb->get_var( $wpdb->prepare( 
        "SELECT COUNT(*) FROM $reactions_table WHERE comment_id = %d AND reaction_type = 'dislike'", 
        $comment_id 
    ) );
    
    // Check user's reaction
    $user_id = get_current_user_id();
    $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $user_reaction = '';
    
    if ( $user_id || $ip_address ) {
        $user_reaction = $wpdb->get_var( $wpdb->prepare(
            "SELECT reaction_type FROM $reactions_table WHERE comment_id = %d AND " . 
            ( $user_id ? "user_id = %d" : "ip_address = %s" ),
            $comment_id,
            $user_id ? $user_id : $ip_address
        ) );
    }
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
                    <b class="fn"><?php comment_author_link(); ?></b>
                    <span class="says"><?php esc_html_e( 'says:', 'gaenity-community' ); ?></span>
                </div>
                <div class="comment-metadata">
                    <a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
                        <time datetime="<?php comment_time( 'c' ); ?>">
                            <?php printf( esc_html__( '%s ago', 'gaenity-community' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
                        </time>
                    </a>
                </div>
            </footer>

            <div class="comment-content">
                <?php comment_text(); ?>
            </div>

            <div class="comment-actions">
                <!-- Reaction Buttons -->
                <div class="comment-reactions">
                    <button class="comment-reaction-btn like-btn <?php echo $user_reaction === 'like' ? 'active-like' : ''; ?>" 
                            data-comment-id="<?php echo esc_attr( $comment_id ); ?>" 
                            data-reaction="like">
                        <span class="emoji">👍</span>
                        <span class="count like-count"><?php echo esc_html( $likes ); ?></span>
                    </button>
                    <button class="comment-reaction-btn dislike-btn <?php echo $user_reaction === 'dislike' ? 'active-dislike' : ''; ?>" 
                            data-comment-id="<?php echo esc_attr( $comment_id ); ?>" 
                            data-reaction="dislike">
                        <span class="emoji">👎</span>
                        <span class="count dislike-count"><?php echo esc_html( $dislikes ); ?></span>
                    </button>
                </div>

                <?php
                comment_reply_link( array_merge( $args, array(
                    'add_below' => 'div-comment',
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                ) ) );
                ?>
            </div>
        </article>
    <?php
}
?>

<script>
// Comment Reactions Handler
document.addEventListener('DOMContentLoaded', function() {
    const reactionButtons = document.querySelectorAll('.comment-reaction-btn');
    const ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
    const nonce = '<?php echo esc_js( wp_create_nonce( 'gaenity-comment-reaction' ) ); ?>';
    
    reactionButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const commentId = this.dataset.commentId;
            const reactionType = this.dataset.reaction;
            
            // Disable button during request
            btn.disabled = true;
            btn.style.opacity = '0.6';
            
            const formData = new URLSearchParams({
                action: 'gaenity_comment_reaction',
                comment_id: commentId,
                reaction_type: reactionType,
                nonce: nonce
            });
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Find parent comment-reactions div
                    const reactionsDiv = btn.closest('.comment-reactions');
                    const likeBtn = reactionsDiv.querySelector('.like-btn');
                    const dislikeBtn = reactionsDiv.querySelector('.dislike-btn');
                    const likeCount = likeBtn.querySelector('.like-count');
                    const dislikeCount = dislikeBtn.querySelector('.dislike-count');
                    
                    // Update counts
                    likeCount.textContent = data.data.likes;
                    dislikeCount.textContent = data.data.dislikes;
                    
                    // Remove all active states
                    likeBtn.classList.remove('active-like');
                    dislikeBtn.classList.remove('active-dislike');
                    
                    // Add active state if reaction exists
                    if (data.data.user_reaction === 'like') {
                        likeBtn.classList.add('active-like');
                    } else if (data.data.user_reaction === 'dislike') {
                        dislikeBtn.classList.add('active-dislike');
                    }
                } else {
                    alert(data.data.message || 'Failed to record reaction');
                }
                
                // Re-enable button
                btn.disabled = false;
                btn.style.opacity = '1';
            })
            .catch(error => {
                console.error('Reaction error:', error);
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        });
    });
});
</script>