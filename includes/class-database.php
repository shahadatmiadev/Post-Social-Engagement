<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PSE_Database {
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_likes = $wpdb->prefix . 'pse_likes';
        $table_comments = $wpdb->prefix . 'pse_comments';
        
        // Drop existing table if needed (temporarily for debugging)
        // $wpdb->query( "DROP TABLE IF EXISTS {$table_likes}" );
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_likes} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_ip (user_ip)
        ) {$charset_collate};
        
        CREATE TABLE IF NOT EXISTS {$table_comments} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_name varchar(100) DEFAULT NULL,
            user_email varchar(100) DEFAULT NULL,
            comment_text text NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) {$charset_collate}";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Run the query
        $result = dbDelta( $sql );
        
        // Debug: Log result
        error_log( 'PSE Table Creation Result: ' . print_r( $result, true ) );
        
        // Verify table was created
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_likes}'" );
        if ( $table_exists != $table_likes ) {
            // Try direct query if dbDelta fails
            $wpdb->query( "
                CREATE TABLE IF NOT EXISTS {$table_likes} (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) NOT NULL,
                    user_ip varchar(45) NOT NULL,
                    user_id bigint(20) DEFAULT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY post_id (post_id),
                    KEY user_ip (user_ip)
                ) {$charset_collate}
            " );
        }
    }
    
    public function get_likes_count( $post_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_likes';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
            $this->create_tables();
            return 0;
        }
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = %d",
            $post_id
        ) );
        
        return $count ? intval( $count ) : 0;
    }
    
    public function has_user_liked( $post_id ) {
        $user_ip = $this->get_user_ip();
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'pse_likes';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
            $this->create_tables();
            return false;
        }
        
        if ( $user_id ) {
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE post_id = %d AND (user_ip = %s OR user_id = %d)",
                $post_id,
                $user_ip,
                $user_id
            ) );
        } else {
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE post_id = %d AND user_ip = %s",
                $post_id,
                $user_ip
            ) );
        }
        
        return $count && $count > 0;
    }
    
    public function add_like( $post_id ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pse_likes';
        $user_ip = $this->get_user_ip();
        $user_id = get_current_user_id();
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
            $this->create_tables();
        }
        
        $data = array(
            'post_id'    => $post_id,
            'user_ip'    => $user_ip,
            'user_id'    => $user_id ? $user_id : null,
            'created_at' => current_time( 'mysql' ),
        );
        
        $result = $wpdb->insert( $table, $data );
        
        if ( $result === false ) {
            error_log( 'PSE Add Like Error: ' . $wpdb->last_error );
            return false;
        }
        
        return $result;
    }
    
    public function remove_like( $post_id ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pse_likes';
        $user_ip = $this->get_user_ip();
        $user_id = get_current_user_id();
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
            $this->create_tables();
            return false;
        }
        
        if ( $user_id ) {
            $result = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table} WHERE post_id = %d AND (user_id = %d OR user_ip = %s)",
                $post_id,
                $user_id,
                $user_ip
            ) );
        } else {
            $result = $wpdb->delete( $table, array(
                'post_id' => $post_id,
                'user_ip' => $user_ip,
            ) );
        }
        
        if ( $result === false ) {
            error_log( 'PSE Remove Like Error: ' . $wpdb->last_error );
            return false;
        }
        
        return $result;
    }
    
    public function get_comments_count( $post_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = %d AND status = 'approved'",
            $post_id
        ) );
        
        return $count ? intval( $count ) : 0;
    }
    
    public function add_comment( $data ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pse_comments';
        
        $defaults = array(
            'post_id'      => 0,
            'user_name'    => '',
            'user_email'   => '',
            'comment_text' => '',
            'user_ip'      => $this->get_user_ip(),
            'user_id'      => get_current_user_id() ? get_current_user_id() : null,
            'status'       => 'pending',
            'created_at'   => current_time( 'mysql' ),
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        $result = $wpdb->insert( $table, $data );
        
        if ( $result === false ) {
            error_log( 'PSE Add Comment Error: ' . $wpdb->last_error );
            return false;
        }
        
        return $result;
    }
    
    public function get_comments( $post_id, $limit = 50 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT user_name, comment_text, created_at 
            FROM {$table} 
            WHERE post_id = %d AND status = 'approved' 
            ORDER BY created_at DESC 
            LIMIT %d",
            $post_id,
            $limit
        ) );
    }
    
    private function get_user_ip() {
        $ip = '127.0.0.1';
        
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        
        return $ip;
    }
    
    public function get_total_likes() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_likes';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
            return 0;
        }
        
        return intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
    }
    
    public function get_total_comments() {
        global $wpdb;
        $table = $wpdb->prefix . 'pse_comments';
        return intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'approved'" ) );
    }
    
    public function get_top_engaged_posts( $limit = 10 ) {
        global $wpdb;
        
        $likes_table = $wpdb->prefix . 'pse_likes';
        $comments_table = $wpdb->prefix . 'pse_comments';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                p.ID as post_id,
                COUNT(DISTINCT l.id) as likes,
                COUNT(DISTINCT c.id) as comments
            FROM {$wpdb->posts} p
            LEFT JOIN {$likes_table} l ON p.ID = l.post_id
            LEFT JOIN {$comments_table} c ON p.ID = c.post_id AND c.status = 'approved'
            WHERE p.post_type = 'post' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY (likes + comments) DESC
            LIMIT %d",
            $limit
        ) );
    }
}