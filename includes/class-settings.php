<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PSE_Settings {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_pse_approve_comment', array( $this, 'handle_comment_approval' ) );
        add_action( 'admin_post_pse_delete_comment', array( $this, 'handle_comment_delete' ) );
    }
    
    public function register_settings() {
        register_setting( 'pse_settings_group', 'pse_settings', array( $this, 'sanitize_settings' ) );
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __( 'Post Social Engagement', 'post-social-engagement' ),
            __( 'Social Engagement', 'post-social-engagement' ),
            'manage_options',
            'post-social-engagement',
            array( $this, 'render_dashboard_page' ),
            'dashicons-share',
            30
        );
        
        add_submenu_page(
            'post-social-engagement',
            __( 'Dashboard', 'post-social-engagement' ),
            __( 'Dashboard', 'post-social-engagement' ),
            'manage_options',
            'post-social-engagement',
            array( $this, 'render_dashboard_page' )
        );
        
        add_submenu_page(
            'post-social-engagement',
            __( 'Comments', 'post-social-engagement' ),
            __( 'Comments', 'post-social-engagement' ) . ' <span class="awaiting-mod">' . $this->get_pending_count() . '</span>',
            'manage_options',
            'pse-comments',
            array( $this, 'render_comments_page' )
        );
        
        add_submenu_page(
            'post-social-engagement',
            __( 'Settings', 'post-social-engagement' ),
            __( 'Settings', 'post-social-engagement' ),
            'manage_options',
            'pse-settings',
            array( $this, 'render_settings_page' )
        );
    }
    
    private function get_pending_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
        return $count ? $count : 0;
    }
    
    public function render_dashboard_page() {
        // Enqueue admin CSS
        wp_enqueue_style( 'pse-admin-style', PSE_PLUGIN_URL . 'assets/css/admin-style.css', array(), PSE_VERSION );
        
        global $pse_db;
        
        if ( ! isset( $pse_db ) ) {
            $pse_db = new PSE_Database();
        }
        
        $total_likes = $pse_db->get_total_likes();
        $total_comments = $pse_db->get_total_comments();
        $pending_comments = $this->get_pending_count();
        $top_posts = $pse_db->get_top_engaged_posts();
        ?>
        <div class="wrap pse-dashboard-wrap">
            <h1 class="pse-dashboard-title">📊 <?php esc_html_e( 'Post Social Engagement Dashboard', 'post-social-engagement' ); ?></h1>
            
            <!-- Stats Cards -->
            <div class="pse-stats-grid">
                <div class="pse-stat-card pse-stat-card-likes">
                    <div class="pse-stat-icon">👍</div>
                    <div class="pse-stat-info">
                        <span class="pse-stat-label"><?php esc_html_e( 'Total Likes', 'post-social-engagement' ); ?></span>
                        <span class="pse-stat-number"><?php echo esc_html( $total_likes ); ?></span>
                    </div>
                    <div class="pse-stat-trend">
                        <span class="trend-up">↑ +<?php echo esc_html( $this->get_likes_trend() ); ?>%</span>
                    </div>
                </div>
                
                <div class="pse-stat-card pse-stat-card-comments">
                    <div class="pse-stat-icon">💬</div>
                    <div class="pse-stat-info">
                        <span class="pse-stat-label"><?php esc_html_e( 'Total Comments', 'post-social-engagement' ); ?></span>
                        <span class="pse-stat-number"><?php echo esc_html( $total_comments ); ?></span>
                    </div>
                    <div class="pse-stat-trend">
                        <span class="trend-up">↑ +<?php echo esc_html( $this->get_comments_trend() ); ?>%</span>
                    </div>
                </div>
                
                <div class="pse-stat-card pse-stat-card-pending">
                    <div class="pse-stat-icon">⏳</div>
                    <div class="pse-stat-info">
                        <span class="pse-stat-label"><?php esc_html_e( 'Pending Comments', 'post-social-engagement' ); ?></span>
                        <span class="pse-stat-number"><?php echo esc_html( $pending_comments ); ?></span>
                    </div>
                    <div class="pse-stat-trend">
                        <?php if ( $pending_comments > 0 ) : ?>
                            <span class="trend-pending">⏰ <?php esc_html_e( 'Need Action', 'post-social-engagement' ); ?></span>
                        <?php else : ?>
                            <span class="trend-good">✓ <?php esc_html_e( 'All Good', 'post-social-engagement' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="pse-stat-card pse-stat-card-engagement">
                    <div class="pse-stat-icon">📈</div>
                    <div class="pse-stat-info">
                        <span class="pse-stat-label"><?php esc_html_e( 'Total Engagement', 'post-social-engagement' ); ?></span>
                        <span class="pse-stat-number"><?php echo esc_html( $total_likes + $total_comments ); ?></span>
                    </div>
                    <div class="pse-stat-trend">
                        <span class="trend-up">🔥 <?php esc_html_e( 'Active', 'post-social-engagement' ); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Top Engaged Posts -->
            <div class="pse-top-posts-section">
                <div class="pse-section-header">
                    <h2>🏆 <?php esc_html_e( 'Top Engaged Posts', 'post-social-engagement' ); ?></h2>
                    <p class="pse-section-desc"><?php esc_html_e( 'Posts with the highest user engagement (likes + comments)', 'post-social-engagement' ); ?></p>
                </div>
                
                <div class="pse-table-container">
                    <table class="pse-modern-table">
                        <thead>
                            <tr>
                                <th class="rank-column">#</th>
                                <th class="post-column"><?php esc_html_e( 'Post Title', 'post-social-engagement' ); ?></th>
                                <th class="likes-column">👍 <?php esc_html_e( 'Likes', 'post-social-engagement' ); ?></th>
                                <th class="comments-column">💬 <?php esc_html_e( 'Comments', 'post-social-engagement' ); ?></th>
                                <th class="engagement-column">⚡ <?php esc_html_e( 'Total', 'post-social-engagement' ); ?></th>
                                <th class="action-column"><?php esc_html_e( 'Action', 'post-social-engagement' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $top_posts ) ) : ?>
                                <?php $rank = 1; ?>
                                <?php foreach ( $top_posts as $post_data ) : ?>
                                    <?php 
                                    $total_engagement = $post_data->likes + $post_data->comments;
                                    $post_title = get_the_title( $post_data->post_id );
                                    $post_url = get_permalink( $post_data->post_id );
                                    ?>
                                    <tr class="pse-post-row rank-<?php echo esc_attr( $rank ); ?>">
                                        <td class="rank-column">
                                            <?php if ( 1 === $rank ) : ?>
                                                <span class="rank-badge gold">🥇 <?php echo esc_html( $rank ); ?></span>
                                            <?php elseif ( 2 === $rank ) : ?>
                                                <span class="rank-badge silver">🥈 <?php echo esc_html( $rank ); ?></span>
                                            <?php elseif ( 3 === $rank ) : ?>
                                                <span class="rank-badge bronze">🥉 <?php echo esc_html( $rank ); ?></span>
                                            <?php else : ?>
                                                <span class="rank-badge normal">#<?php echo esc_html( $rank ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="post-column">
                                            <a href="<?php echo esc_url( $post_url ); ?>" target="_blank" class="post-title-link">
                                                <?php echo esc_html( $post_title ? $post_title : __( '(No title)', 'post-social-engagement' ) ); ?>
                                            </a>
                                        </td>
                                        <td class="likes-column">
                                            <span class="pse-badge pse-badge-likes">
                                                👍 <?php echo esc_html( $post_data->likes ); ?>
                                            </span>
                                        </td>
                                        <td class="comments-column">
                                            <span class="pse-badge pse-badge-comments">
                                                💬 <?php echo esc_html( $post_data->comments ); ?>
                                            </span>
                                        </td>
                                        <td class="engagement-column">
                                            <div class="engagement-bar">
                                                <div class="engagement-fill" style="width: <?php echo esc_attr( min( 100, $total_engagement * 5 ) ); ?>%"></div>
                                                <span class="engagement-score"><?php echo esc_html( $total_engagement ); ?></span>
                                            </div>
                                        </td>
                                        <td class="action-column">
                                            <a href="<?php echo esc_url( $post_url ); ?>" target="_blank" class="pse-action-btn view-btn">
                                                👁️ <?php esc_html_e( 'View', 'post-social-engagement' ); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php $rank++; ?>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="empty-state">
                                    <td colspan="6">
                                        <div class="pse-empty-state">
                                            <span class="empty-icon">📭</span>
                                            <p><?php esc_html_e( 'No engagement data yet.', 'post-social-engagement' ); ?></p>
                                            <p class="empty-hint"><?php esc_html_e( 'When users like or comment on your posts, they will appear here.', 'post-social-engagement' ); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="pse-quick-actions">
                <h3>⚡ <?php esc_html_e( 'Quick Actions', 'post-social-engagement' ); ?></h3>
                <div class="action-buttons">
                    <a href="<?php echo admin_url( 'admin.php?page=pse-settings' ); ?>" class="quick-action-btn settings-btn">
                        ⚙️ <?php esc_html_e( 'Settings', 'post-social-engagement' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=pse-comments&status=pending' ); ?>" class="quick-action-btn comments-btn">
                        💬 <?php esc_html_e( 'Manage Comments', 'post-social-engagement' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'edit.php' ); ?>" class="quick-action-btn posts-btn">
                        📝 <?php esc_html_e( 'All Posts', 'post-social-engagement' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_likes_trend() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_likes';
        $last_week = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
        $previous_week = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)" );
        
        if ( $previous_week == 0 ) return 100;
        return round( ( ( $last_week - $previous_week ) / $previous_week ) * 100 );
    }

    private function get_comments_trend() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        $last_week = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'approved'" );
        $previous_week = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'approved'" );
        
        if ( $previous_week == 0 ) return 100;
        return round( ( ( $last_week - $previous_week ) / $previous_week ) * 100 );
    }
    
    public function render_comments_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        // Handle bulk actions
        if ( isset( $_POST['bulk_action'] ) && isset( $_POST['comment_ids'] ) && ! empty( $_POST['bulk_action'] ) ) {
            $this->handle_bulk_actions();
        }
        
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'pending';
        $where = '';
        
        if ( 'pending' === $status ) {
            $where = "WHERE status = 'pending'";
        } elseif ( 'approved' === $status ) {
            $where = "WHERE status = 'approved'";
        }
        
        $comments = $wpdb->get_results( "
            SELECT * FROM {$table} 
            {$where} 
            ORDER BY created_at DESC 
            LIMIT 100
        " );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Comments', 'post-social-engagement' ); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="?page=pse-comments&status=pending" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">
                        <?php esc_html_e( 'Pending', 'post-social-engagement' ); ?> 
                        <span class="count">(<?php echo esc_html( $this->get_pending_count() ); ?>)</span>
                    </a>
                </li>
                <li>|</li>
                <li>
                    <a href="?page=pse-comments&status=approved" class="<?php echo $status === 'approved' ? 'current' : ''; ?>">
                        <?php esc_html_e( 'Approved', 'post-social-engagement' ); ?>
                    </a>
                </li>
            </ul>
            
            <form method="post">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value=""><?php esc_html_e( 'Bulk Actions', 'post-social-engagement' ); ?></option>
                            <option value="approve"><?php esc_html_e( 'Approve', 'post-social-engagement' ); ?></option>
                            <option value="delete"><?php esc_html_e( 'Delete', 'post-social-engagement' ); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'post-social-engagement' ); ?>">
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="50"><input type="checkbox" id="select-all"></th>
                            <th><?php esc_html_e( 'Comment', 'post-social-engagement' ); ?></th>
                            <th width="150"><?php esc_html_e( 'Author', 'post-social-engagement' ); ?></th>
                            <th width="150"><?php esc_html_e( 'Post', 'post-social-engagement' ); ?></th>
                            <th width="150"><?php esc_html_e( 'Date', 'post-social-engagement' ); ?></th>
                            <th width="150"><?php esc_html_e( 'Actions', 'post-social-engagement' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $comments ) ) : ?>
                            <tr>
                                <td colspan="6"><?php esc_html_e( 'No comments found.', 'post-social-engagement' ); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $comments as $comment ) : ?>
                                <tr>
                                    <td><input type="checkbox" name="comment_ids[]" value="<?php echo esc_attr( $comment->id ); ?>"></td>
                                    <td>>
                                        <strong><?php echo esc_html( $comment->comment_text ); ?></strong>
                                        <div class="row-actions">
                                            <?php if ( 'pending' === $comment->status ) : ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pse_approve_comment&id=' . $comment->id ), 'pse_approve_comment' ) ); ?>">
                                                    <?php esc_html_e( 'Approve', 'post-social-engagement' ); ?>
                                                </a> |
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pse_delete_comment&id=' . $comment->id ), 'pse_delete_comment' ) ); ?>" 
                                               onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'post-social-engagement' ); ?>')">
                                                <?php esc_html_e( 'Delete', 'post-social-engagement' ); ?>
                                            </a>
                                            <?php if ( 'approved' === $comment->status ) : ?>
                                                | <a href="<?php echo esc_url( get_permalink( $comment->post_id ) ); ?>" target="_blank">
                                                    <?php esc_html_e( 'View Post', 'post-social-engagement' ); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( $comment->user_name ); ?><br>
                                        <small><?php echo esc_html( $comment->user_email ); ?></small>
                                    </td>
                                    <td>>
                                        <a href="<?php echo esc_url( get_permalink( $comment->post_id ) ); ?>" target="_blank">
                                            <?php echo esc_html( get_the_title( $comment->post_id ) ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( human_time_diff( strtotime( $comment->created_at ) ) . ' ago' ); ?></td>
                                    <td>>
                                        <?php if ( 'pending' === $comment->status ) : ?>
                                            <span class="button button-small" style="background: #f0ad4e; color: #fff; border: none;">
                                                <?php esc_html_e( 'Pending', 'post-social-engagement' ); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="button button-small" style="background: #5cb85c; color: #fff; border: none;">
                                                <?php esc_html_e( 'Approved', 'post-social-engagement' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#select-all').on('click', function() {
                $('input[name="comment_ids[]"]').prop('checked', this.checked);
            });
        });
        </script>
        <style>
            .subsubsub { margin: 0 0 10px 0; }
            .row-actions { visibility: visible; margin-top: 5px; }
        </style>
        <?php
    }
    
    private function handle_bulk_actions() {
        if ( ! isset( $_POST['comment_ids'] ) || empty( $_POST['comment_ids'] ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_POST['bulk_action'] );
        $comment_ids = array_map( 'intval', $_POST['comment_ids'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        if ( 'approve' === $action ) {
            $ids = implode( ',', $comment_ids );
            $wpdb->query( "UPDATE {$table} SET status = 'approved' WHERE id IN ({$ids})" );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Comments approved successfully!', 'post-social-engagement' ) . '</p></div>';
        } elseif ( 'delete' === $action ) {
            $ids = implode( ',', $comment_ids );
            $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids})" );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Comments deleted successfully!', 'post-social-engagement' ) . '</p></div>';
        }
    }
    
    public function handle_comment_approval() {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'pse_approve_comment' ) ) {
            wp_die( 'Security check failed' );
        }
        
        $comment_id = intval( $_GET['id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        $wpdb->update( $table, array( 'status' => 'approved' ), array( 'id' => $comment_id ) );
        
        wp_redirect( admin_url( 'admin.php?page=pse-comments&status=pending' ) );
        exit;
    }
    
    public function handle_comment_delete() {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'pse_delete_comment' ) ) {
            wp_die( 'Security check failed' );
        }
        
        $comment_id = intval( $_GET['id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        $wpdb->delete( $table, array( 'id' => $comment_id ) );
        
        wp_redirect( admin_url( 'admin.php?page=pse-comments' ) );
        exit;
    }
    
    public function render_settings_page() {
        $settings = get_option( 'pse_settings', array() );
        
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'pse_settings_action' ) ) {
            $settings = $this->sanitize_settings( $_POST );
            update_option( 'pse_settings', $settings );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'post-social-engagement' ) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Post Social Engagement Settings', 'post-social-engagement' ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'pse_settings_action' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Features', 'post-social-engagement' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_likes" <?php checked( isset( $settings['enable_likes'] ) ? $settings['enable_likes'] : true, true ); ?>>
                                <?php esc_html_e( 'Enable Likes', 'post-social-engagement' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="enable_comments" <?php checked( isset( $settings['enable_comments'] ) ? $settings['enable_comments'] : true, true ); ?>>
                                <?php esc_html_e( 'Enable Comments', 'post-social-engagement' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="enable_shares" <?php checked( isset( $settings['enable_shares'] ) ? $settings['enable_shares'] : true, true ); ?>>
                                <?php esc_html_e( 'Enable Shares', 'post-social-engagement' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Button Position', 'post-social-engagement' ); ?></th>
                        <td>
                            <select name="button_position">
                                <option value="top" <?php selected( isset( $settings['button_position'] ) ? $settings['button_position'] : 'bottom', 'top' ); ?>>
                                    <?php esc_html_e( 'Top of content', 'post-social-engagement' ); ?>
                                </option>
                                <option value="bottom" <?php selected( isset( $settings['button_position'] ) ? $settings['button_position'] : 'bottom', 'bottom' ); ?>>
                                    <?php esc_html_e( 'Bottom of content', 'post-social-engagement' ); ?>
                                </option>
                                <option value="both" <?php selected( isset( $settings['button_position'] ) ? $settings['button_position'] : 'bottom', 'both' ); ?>>
                                    <?php esc_html_e( 'Both top and bottom', 'post-social-engagement' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Comment Settings', 'post-social-engagement' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="comment_approval" <?php checked( isset( $settings['comment_approval'] ) ? $settings['comment_approval'] : false, true ); ?>>
                                <?php esc_html_e( 'Require admin approval for comments', 'post-social-engagement' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'When enabled, comments will need admin approval before appearing on the site.', 'post-social-engagement' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'post-social-engagement' ); ?>">
                </p>
            </form>
        </div>
        <?php
    }
    
    private function sanitize_settings( $input ) {
        $sanitized = array();
        
        $sanitized['enable_likes']     = isset( $input['enable_likes'] );
        $sanitized['enable_comments']  = isset( $input['enable_comments'] );
        $sanitized['enable_shares']    = isset( $input['enable_shares'] );
        $sanitized['comment_approval'] = isset( $input['comment_approval'] );
        $sanitized['show_on_home']     = true;
        $sanitized['show_on_archive']  = true;
        
        if ( isset( $input['button_position'] ) ) {
            $sanitized['button_position'] = sanitize_text_field( $input['button_position'] );
        } else {
            $sanitized['button_position'] = 'bottom';
        }
        
        return $sanitized;
    }
}

// Initialize Settings
new PSE_Settings();