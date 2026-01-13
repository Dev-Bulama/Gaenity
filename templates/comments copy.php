<?php
/**
 * Custom Comments Template for Gaenity Discussions
 */

if ( post_password_required() ) {
    return;
}

// Define custom comment callback function
if ( ! function_exists( 'gaenity_custom_comment' ) ) {
    function gaenity_custom_comment( $comment, $args, $depth ) {
        $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
        ?>
        <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?>>
            <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
                <footer class="comment-meta">
                    <div class="comment-author vcard">
                        <?php
                        if ( 0 != $args['avatar_size'] ) {
                            echo get_avatar( $comment, $args['avatar_size'] );
                        }
                        ?>
                        <b class="fn"><?php echo get_comment_author_link( $comment ); ?></b>
                        <span class="says"><?php esc_html_e( 'says:', 'gaenity-community' ); ?></span>
                    </div>

                    <div class="comment-metadata">
                        <a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
                            <time datetime="<?php comment_time( 'c' ); ?>">
                                <?php
                                printf(
                                    esc_html__( '%s ago', 'gaenity-community' ),
                                    human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) )
                                );
                                ?>
                            </time>
                        </a>
                        <?php edit_comment_link( __( 'Edit', 'gaenity-community' ), '<span class="edit-link">', '</span>' ); ?>
                    </div>
                </footer>

                <div class="comment-content">
                    <?php comment_text(); ?>
                </div>

                <?php
                if ( '1' == $comment->comment_approved ) :
                    comment_reply_link( array_merge( $args, array(
                        'add_below' => 'div-comment',
                        'depth'     => $depth,
                        'max_depth' => $args['max_depth'],
                        'before'    => '<div class="reply">',
                        'after'     => '</div>',
                    ) ) );
                endif;
                ?>
            </article>
        <?php
    }
}
?>

<style>
    .gaenity-comments-wrapper {
        margin-top: 2rem;
    }
    .gaenity-comments-wrapper h3 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: #1e293b;
    }
    .comment-list {
        list-style: none;
        padding: 0;
        margin: 0 0 2rem 0;
    }
    .comment-list .comment {
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 0;
    }
    .comment-list .comment:last-child {
        border-bottom: none;
    }
    .comment-list .children {
        list-style: none;
        margin-left: 2rem;
        padding-left: 2rem;
        border-left: 3px solid #e0e7ff;
        margin-top: 1rem;
    }
    .comment-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
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
    .comment-author .says {
        display: none;
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
        margin-bottom: 0.75rem;
    }
    .reply {
        margin-top: 0.75rem;
    }
    .comment-reply-link {
        background: #f1f5f9;
        color: #1e293b;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
        transition: all 0.2s ease;
    }
    .comment-reply-link:hover {
        background: #1d4ed8;
        color: #fff;
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
    }
    .comment-form textarea {
        min-height: 120px;
        resize: vertical;
    }
    .comment-form .form-submit {
        margin-top: 1rem;
    }
    .comment-form .submit {
        background: #1d4ed8;
        color: #fff;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .comment-form .submit:hover {
        background: #1e40af;
        transform: translateY(-2px);
    }
    .comment-reply-title {
        font-size: 1.3rem;
        margin-bottom: 1rem;
        color: #1e293b;
    }
    .comment-reply-title small a {
        font-size: 0.8rem;
        color: #ef4444;
        text-decoration: none;
        margin-left: 1rem;
    }
    .gaenity-no-comments {
        background: #f8fafc;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        color: #64748b;
    }
    .comment-navigation {
        margin: 2rem 0;
        display: flex;
        justify-content: space-between;
    }
    .comment-navigation a {
        color: #1d4ed8;
        text-decoration: none;
        font-weight: 600;
    }
</style>

<div id="comments" class="gaenity-comments-wrapper">

    <?php if ( have_comments() ) : ?>
        <h3>
            <?php
            $comment_count = get_comments_number();
            if ( '1' === $comment_count ) {
                printf( esc_html__( '1 Reply', 'gaenity-community' ) );
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
                'callback'    => 'gaenity_custom_comment',
            ) );
            ?>
        </ol>

        <?php
        the_comments_navigation( array(
            'prev_text' => __( '← Older Replies', 'gaenity-community' ),
            'next_text' => __( 'Newer Replies →', 'gaenity-community' ),
        ) );
        ?>

    <?php endif; ?>

    <?php
    if ( comments_open() ) :
        comment_form( array(
            'title_reply'        => __( 'Leave a Reply', 'gaenity-community' ),
            'title_reply_to'     => __( 'Reply to %s', 'gaenity-community' ),
            'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
            'title_reply_after'  => '</h3>',
            'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . __( 'Your Reply *', 'gaenity-community' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
            'label_submit'       => __( 'Post Reply', 'gaenity-community' ),
            'class_submit'       => 'submit',
            'logged_in_as'       => is_user_logged_in() ? '<p class="logged-in-as">' . sprintf( __( 'Logged in as %s. <a href="%s">Log out?</a>', 'gaenity-community' ), wp_get_current_user()->display_name, wp_logout_url( get_permalink() ) ) . '</p>' : '',        ) );
    elseif ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
        ?>
        <p class="gaenity-no-comments"><?php esc_html_e( 'Comments are closed.', 'gaenity-community' ); ?></p>
    <?php endif; ?>

</div>