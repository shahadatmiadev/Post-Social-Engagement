<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PSE_Frontend {
    
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'the_content', array( $this, 'add_engagement_buttons' ), 9999 );
    }
    
    public function enqueue_scripts() {
        if ( is_singular( 'post' ) || is_home() || is_archive() || is_search() ) {
            wp_enqueue_script( 'jquery' );
            
            wp_enqueue_script(
                'pse-script',
                PSE_PLUGIN_URL . 'assets/js/pse-script.js',
                array( 'jquery' ),
                PSE_VERSION,
                true
            );
            
            wp_localize_script(
                'pse-script',
                'pse_ajax',
                array(
                    'ajax_url'  => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'pse_nonce' ),
                )
            );
            
            wp_enqueue_style(
                'pse-style',
                PSE_PLUGIN_URL . 'assets/css/pse-style.css',
                array(),
                PSE_VERSION
            );
        }
    }
    
    public function add_engagement_buttons( $content ) {
        global $post;
        
        // Only on single post pages
        if ( ! is_singular( 'post' ) ) {
            return $content;
        }
        
        // Get settings
        $settings = get_option( 'pse_settings', array() );
        
        // Debug: Log settings (remove after testing)
        error_log( 'PSE Settings: ' . print_r( $settings, true ) );
        
        // Get button position - default 'bottom'
        $button_position = isset( $settings['button_position'] ) ? $settings['button_position'] : 'bottom';
        
        // Generate buttons HTML
        $buttons_html = $this->generate_buttons_html( $post->ID );
        
        // Apply position
        if ( 'top' === $button_position ) {
            return $buttons_html . $content;
        } elseif ( 'bottom' === $button_position ) {
            return $content . $buttons_html;
        } elseif ( 'both' === $button_position ) {
            return $buttons_html . $content . $buttons_html;
        } else {
            // Default to bottom
            return $content . $buttons_html;
        }
    }
    
    private function generate_buttons_html( $post_id ) {
        $settings = get_option( 'pse_settings', array() );
        
        global $pse_db;
        
        if ( ! isset( $pse_db ) ) {
            $pse_db = new PSE_Database();
        }
        
        $likes      = $pse_db->get_likes_count( $post_id );
        $comments   = $pse_db->get_comments_count( $post_id );
        $user_liked = $pse_db->has_user_liked( $post_id );
        
        ob_start();
        ?>
        <div class="pse-engagement-wrapper" data-post-id="<?php echo esc_attr( $post_id ); ?>">
            <div class="pse-buttons-container">
                <?php if ( ! empty( $settings['enable_likes'] ) ) : ?>
                    <button type="button" class="pse-button pse-like-btn <?php echo $user_liked ? 'pse-liked' : ''; ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                        <span class="pse-icon">👍</span>
                        <span class="pse-text"><?php echo $user_liked ? esc_html__( 'Liked', 'post-social-engagement' ) : esc_html__( 'Like', 'post-social-engagement' ); ?></span>
                        <span class="pse-count"><?php echo esc_html( $likes ); ?></span>
                    </button>
                <?php endif; ?>
                
                <?php if ( ! empty( $settings['enable_comments'] ) ) : ?>
                    <button type="button" class="pse-button pse-comment-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                        <span class="pse-icon">💬</span>
                        <span class="pse-text"><?php esc_html_e( 'Comment', 'post-social-engagement' ); ?></span>
                        <span class="pse-count"><?php echo esc_html( $comments ); ?></span>
                    </button>
                <?php endif; ?>
                
                <?php if ( ! empty( $settings['enable_shares'] ) ) : ?>
                    <button type="button" class="pse-button pse-share-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                        <span class="pse-icon">📤</span>
                        <span class="pse-text"><?php esc_html_e( 'Share', 'post-social-engagement' ); ?></span>
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ( ! empty( $settings['enable_comments'] ) ) : ?>
                <div class="pse-comments-area" data-post-id="<?php echo esc_attr( $post_id ); ?>" style="display: none;">
                    <div class="pse-comments-list"></div>
                    <div class="pse-comment-form">
                        <textarea placeholder="<?php esc_attr_e( 'Write a comment...', 'post-social-engagement' ); ?>" rows="3"></textarea>
                        <button type="button" class="pse-submit-comment"><?php esc_html_e( 'Post Comment', 'post-social-engagement' ); ?></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pse-share-modal" style="display: none;">
            <div class="pse-share-modal-content">
                <h3><?php esc_html_e( 'Share this post', 'post-social-engagement' ); ?></h3>
                <div class="pse-share-buttons">
                    <button class="pse-share-fb"><?php esc_html_e( 'Facebook', 'post-social-engagement' ); ?></button>
                    <button class="pse-share-tw"><?php esc_html_e( 'Twitter', 'post-social-engagement' ); ?></button>
                    <button class="pse-share-li"><?php esc_html_e( 'LinkedIn', 'post-social-engagement' ); ?></button>
                    <button class="pse-share-wa"><?php esc_html_e( 'WhatsApp', 'post-social-engagement' ); ?></button>
                </div>
                <button class="pse-modal-close"><?php esc_html_e( 'Close', 'post-social-engagement' ); ?></button>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}

// Initialize Frontend.
new PSE_Frontend();