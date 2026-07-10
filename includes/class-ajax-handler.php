<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class XOPSE_AJAX_Handler {

    public function __construct() {
        add_action( 'wp_ajax_xopse_handle_like', array( $this, 'handle_like' ) );
        add_action( 'wp_ajax_nopriv_xopse_handle_like', array( $this, 'handle_like' ) );
        add_action( 'wp_ajax_xopse_add_comment', array( $this, 'add_comment' ) );
        add_action( 'wp_ajax_nopriv_xopse_add_comment', array( $this, 'add_comment' ) );
        add_action( 'wp_ajax_xopse_load_comments', array( $this, 'load_comments' ) );
        add_action( 'wp_ajax_nopriv_xopse_load_comments', array( $this, 'load_comments' ) );
    }

    public function handle_like() {
        if ( ! check_ajax_referer( 'xopse_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
        }

        global $xopse_db;
        if ( ! isset( $xopse_db ) ) {
            $xopse_db = new XOPSE_Database();
        }

        $has_liked = $xopse_db->has_user_liked( $post_id );

        if ( $has_liked ) {
            $result = $xopse_db->remove_like( $post_id );
            $action = 'unliked';
        } else {
            $result = $xopse_db->add_like( $post_id );
            $action = 'liked';
        }

        if ( $result ) {
            $new_count = $xopse_db->get_likes_count( $post_id );

            wp_send_json_success( array(
                'action' => $action,
                'count'  => $new_count,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to process like' ) );
        }
    }

    public function add_comment() {
        if ( ! check_ajax_referer( 'xopse_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $comment = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

        if ( ! $post_id || empty( $comment ) ) {
            wp_send_json_error( array( 'message' => 'Invalid data' ) );
        }

        $settings = get_option( 'xopse_settings', array() );
        $status   = ! empty( $settings['comment_approval'] ) ? 'pending' : 'approved';

        $user_id = get_current_user_id();

        if ( $user_id ) {
            $user       = get_userdata( $user_id );
            $user_name  = $user->display_name;
            $user_email = $user->user_email;
        } else {
            $user_name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : 'Anonymous';
            $user_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        }

        global $xopse_db;
        if ( ! isset( $xopse_db ) ) {
            $xopse_db = new XOPSE_Database();
        }

        $comment_data = array(
            'post_id'      => $post_id,
            'user_name'    => $user_name,
            'user_email'   => $user_email,
            'comment_text' => $comment,
            'status'       => $status,
        );

        $result = $xopse_db->add_comment( $comment_data );

        if ( $result ) {
            $new_count = $xopse_db->get_comments_count( $post_id );

            $message = ( 'pending' === $status )
                ? 'Comment awaiting approval'
                : 'Comment added successfully';

            wp_send_json_success( array(
                'message' => $message,
                'count'   => $new_count,
                'status'  => $status,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to add comment' ) );
        }
    }

    public function load_comments() {
        if ( ! check_ajax_referer( 'xopse_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error();
        }

        global $xopse_db;
        if ( ! isset( $xopse_db ) ) {
            $xopse_db = new XOPSE_Database();
        }

        $comments = $xopse_db->get_comments( $post_id );

        ob_start();

        if ( empty( $comments ) ) {
            echo '<div class="pse-no-comments">No comments yet. Be the first to comment!</div>';
        } else {
            foreach ( $comments as $comment ) {
                ?>
                <div class="pse-comment-item">
                    <div class="pse-comment-author"><?php echo esc_html( $comment->user_name ); ?></div>
                    <div class="pse-comment-date"><?php echo esc_html( human_time_diff( strtotime( $comment->created_at ) ) . ' ago' ); ?></div>
                    <div class="pse-comment-text"><?php echo esc_html( $comment->comment_text ); ?></div>
                </div>
                <?php
            }
        }

        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'  => $html,
            'count' => count( $comments ),
        ) );
    }
}

// Initialize AJAX handler
new XOPSE_AJAX_Handler();
