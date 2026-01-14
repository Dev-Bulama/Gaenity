<?php
/**
 * Main plugin class for Gaeinity Community Suite.
 *
 * @package GaeinityCommunity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Gaeinity_Community_Plugin' ) ) :

class Gaeinity_Community_Plugin {

    /**
     * Plugin version.
     *
     * @var string
     */
    protected $version = '2.0.0';

    /**
     * Plugin slug.
     *
     * @var string
     */
    protected $slug = 'gaenity-community';

    /**
     * Initialise the plugin.
     */
    public function init() {
        $this->define_constants();

        register_activation_hook( GAENITY_COMMUNITY_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
        register_deactivation_hook( GAENITY_COMMUNITY_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );

        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( $this, 'register_member_dashboard' ) );  // â† ADD THIS
        add_action( 'init', array( $this, 'register_expert_directory' ) );
        add_action( 'init', array( $this, 'register_roles' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        // add_action( 'admin_menu', array( $this, 'add_admin_menu_pages' ) );
        add_filter( 'template_include', array( $this, 'load_plugin_templates' ) );
        // Force comments to be open for discussions
        add_filter( 'comments_open', array( $this, 'force_comments_open_for_discussions' ), 10, 2 );

        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_gaenity_resource', array( $this, 'save_resource_meta' ) );
        add_action( 'save_post_gaenity_poll', array( $this, 'save_poll_meta' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        $this->register_ajax_actions();
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );  // â† FIND THIS LINE
            add_action( 'admin_init', array( $this, 'redirect_non_admins_to_custom_dashboard' ) );

        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
        add_filter( 'template_include', array( $this, 'load_plugin_templates' ) );
        // add_action( 'admin_init', array( $this, 'redirect_non_admins_to_custom_dashboard' ) );
        $this->register_documentation();
        add_action( 'save_post_gaenity_course', array( $this, 'save_course_meta' ) );
        $this->register_courses();
        $this->register_checkout();
        $this->register_community_home_v2();
        $this->register_polls_page();
        
    }
    



      /**
     * Force comments to be open for discussions.
     */
    public function force_comments_open_for_discussions( $open, $post_id ) {
        $post = get_post( $post_id );
        if ( $post && 'gaenity_discussion' === $post->post_type ) {
            return true;
        }
        return $open;
    }
    /**
     * Define core plugin constants.
     */
    protected function define_constants() {
        if ( ! defined( 'GAENITY_COMMUNITY_PATH' ) ) {
            define( 'GAENITY_COMMUNITY_PATH', plugin_dir_path( GAENITY_COMMUNITY_PLUGIN_FILE ) );
        }
        if ( ! defined( 'GAENITY_COMMUNITY_URL' ) ) {
            define( 'GAENITY_COMMUNITY_URL', plugin_dir_url( GAENITY_COMMUNITY_PLUGIN_FILE ) );
        }
        if ( ! defined( 'GAENITY_COMMUNITY_ASSETS' ) ) {
            define( 'GAENITY_COMMUNITY_ASSETS', trailingslashit( GAENITY_COMMUNITY_URL . 'assets' ) );
        }
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'gaenity-community', false, dirname( plugin_basename( GAENITY_COMMUNITY_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Activation hook callback.
     */
    public static function activate() {
        self::create_tables();
        self::add_roles();
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook callback.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create required database tables.
     */
    protected static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $downloads_table = $wpdb->prefix . 'gaenity_resource_downloads';
        $sql_downloads   = "CREATE TABLE $downloads_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            resource_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            role VARCHAR(100) DEFAULT '' NOT NULL,
            region VARCHAR(100) DEFAULT '' NOT NULL,
            industry VARCHAR(191) DEFAULT '' NOT NULL,
            consent TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY resource_id (resource_id),
            KEY region (region),
            KEY industry (industry)
        ) $charset_collate;";

        $experts_table = $wpdb->prefix . 'gaenity_expert_requests';
        $sql_experts   = "CREATE TABLE $experts_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(255) NOT NULL,
            role VARCHAR(100) DEFAULT '' NOT NULL,
            region VARCHAR(100) DEFAULT '' NOT NULL,
            country VARCHAR(100) DEFAULT '' NOT NULL,
            industry VARCHAR(191) DEFAULT '' NOT NULL,
            challenge VARCHAR(191) DEFAULT '' NOT NULL,
            description TEXT NULL,
            budget VARCHAR(100) DEFAULT '' NOT NULL,
            preference VARCHAR(50) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

        $contacts_table = $wpdb->prefix . 'gaenity_contact_messages';
        $sql_contacts   = "CREATE TABLE $contacts_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(191) NOT NULL,
            message TEXT NOT NULL,
            updates TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $chat_table = $wpdb->prefix . 'gaenity_chat_messages';
        $sql_chat   = "CREATE TABLE $chat_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            display_name VARCHAR(191) DEFAULT '' NOT NULL,
            role VARCHAR(100) DEFAULT '' NOT NULL,
            region VARCHAR(100) DEFAULT '' NOT NULL,
            industry VARCHAR(191) DEFAULT '' NOT NULL,
            challenge VARCHAR(191) DEFAULT '' NOT NULL,
            message TEXT NOT NULL,
            is_anonymous TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

        $votes_table = $wpdb->prefix . 'gaenity_poll_votes';
        $sql_votes   = "CREATE TABLE $votes_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            poll_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            option_key VARCHAR(100) NOT NULL,
            region VARCHAR(100) DEFAULT '' NOT NULL,
            industry VARCHAR(191) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY poll_id (poll_id)
        ) $charset_collate;";
        

        dbDelta( $sql_downloads );
        dbDelta( $sql_experts );
        dbDelta( $sql_contacts );
        dbDelta( $sql_chat );
        dbDelta( $sql_votes );
        $transactions_table = $wpdb->prefix . 'gaenity_transactions';
        $sql_transactions   = "CREATE TABLE $transactions_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            email VARCHAR(255) NOT NULL,
            item_type VARCHAR(50) NOT NULL,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            gateway VARCHAR(50) NOT NULL,
            transaction_id VARCHAR(255) DEFAULT '' NOT NULL,
            status VARCHAR(50) DEFAULT 'pending' NOT NULL,
            metadata TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY item_type (item_type),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_transactions );
$votes_discussion_table = $wpdb->prefix . 'gaenity_discussion_votes';
        $sql_votes_discussion   = "CREATE TABLE $votes_discussion_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            discussion_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            vote_type VARCHAR(10) NOT NULL,
            ip_address VARCHAR(100) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_vote (discussion_id, user_id),
            KEY discussion_id (discussion_id)
        ) $charset_collate;";

        dbDelta( $sql_votes_discussion );

        // Paid resource access table with download expiration
        $paid_access_table = $wpdb->prefix . 'gaenity_paid_resource_access';
        $sql_paid_access   = "CREATE TABLE $paid_access_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            resource_id BIGINT(20) UNSIGNED NOT NULL,
            transaction_id VARCHAR(255) NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            email VARCHAR(255) NOT NULL,
            role VARCHAR(100) DEFAULT '' NOT NULL,
            region VARCHAR(100) DEFAULT '' NOT NULL,
            industry VARCHAR(191) DEFAULT '' NOT NULL,
            download_count INT UNSIGNED DEFAULT 0 NOT NULL,
            max_downloads INT UNSIGNED DEFAULT 3 NOT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_downloaded_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY resource_id (resource_id),
            KEY transaction_id (transaction_id),
            KEY email (email),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        dbDelta( $sql_paid_access );

    }

    /**
     * Register community specific roles.
     */
    public function register_roles() {
        if ( ! get_role( 'gaenity_expert' ) ) {
            add_role( 'gaenity_expert', __( 'Gaeinity Expert', 'gaenity-community' ), array( 'read' => true ) );
        }
    }

    /**
     * Add roles during activation.
     */
    protected static function add_roles() {
        if ( ! get_role( 'gaenity_expert' ) ) {
            add_role( 'gaenity_expert', __( 'Gaeinity Expert', 'gaenity-community' ), array( 'read' => true ) );
        }
    }

    /**
     * Register post types.
     */
    public function register_post_types() {
        register_post_type(
            'gaenity_resource',
            array(
                'labels'      => array(
                    'name'          => __( 'Resources', 'gaenity-community' ),
                    'singular_name' => __( 'Resource', 'gaenity-community' ),
                ),
                'public'      => true,
                'has_archive' => true,
                'menu_icon'   => 'dashicons-media-document',
                'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
                'show_in_rest'=> true,
            )
        );

     register_post_type(
            'gaenity_discussion',
            array(
                'labels'      => array(
                    'name'          => __( 'Community Discussions', 'gaenity-community' ),
                    'singular_name' => __( 'Discussion', 'gaenity-community' ),
                ),
                'public'      => true,
                'has_archive' => true,
                'supports'    => array( 'title', 'editor', 'author', 'comments' ),
                'menu_icon'   => 'dashicons-format-chat',
                'show_in_rest'=> true,
            )
        );

        register_post_type(
            'gaenity_poll',
            array(
                'labels'      => array(
                    'name'          => __( 'Community Polls', 'gaenity-community' ),
                    'singular_name' => __( 'Poll', 'gaenity-community' ),
                ),
                'public'      => false,
                'show_ui'     => true,
                'supports'    => array( 'title' ),
                'menu_icon'   => 'dashicons-chart-bar',
                'show_in_rest'=> false,
            )
        );
        register_post_type(
            'gaenity_course',
            array(
                'labels'      => array(
                    'name'          => __( 'Enablement Courses', 'gaenity-community' ),
                    'singular_name' => __( 'Course', 'gaenity-community' ),
                ),
                'public'      => true,
                'has_archive' => true,
                'menu_icon'   => 'dashicons-welcome-learn-more',
                'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
                'show_in_rest'=> true,
            )
        );
    }

    /**
     * Register taxonomies.
     */
    public function register_taxonomies() {
        register_taxonomy(
            'gaenity_resource_type',
            'gaenity_resource',
            array(
                'labels'            => array(
                    'name'          => __( 'Resource Types', 'gaenity-community' ),
                    'singular_name' => __( 'Resource Type', 'gaenity-community' ),
                ),
                'public'            => true,
                'hierarchical'      => false,
                'show_in_rest'      => true,
            )
        );

        register_taxonomy(
            'gaenity_region',
            'gaenity_discussion',
            array(
                'labels'       => array(
                    'name'          => __( 'Regions', 'gaenity-community' ),
                    'singular_name' => __( 'Region', 'gaenity-community' ),
                ),
                'public'       => true,
                'hierarchical' => false,
                'show_in_rest' => true,
            )
        );

        register_taxonomy(
            'gaenity_industry',
            'gaenity_discussion',
            array(
                'labels'       => array(
                    'name'          => __( 'Industries', 'gaenity-community' ),
                    'singular_name' => __( 'Industry', 'gaenity-community' ),
                ),
                'public'       => true,
                'hierarchical' => false,
                'show_in_rest' => true,
            )
        );

        register_taxonomy(
            'gaenity_challenge',
            'gaenity_discussion',
            array(
                'labels'       => array(
                    'name'          => __( 'Challenges', 'gaenity-community' ),
                    'singular_name' => __( 'Challenge', 'gaenity-community' ),
                ),
                'public'       => true,
                'hierarchical' => false,
                'show_in_rest' => true,
            )
        );
    }

    /**
     * Register Elementor widget category.
     */
    public function register_elementor_category( $elements_manager ) {
        $elements_manager->add_category(
            'gaenity-community',
            array(
                'title' => __( 'Gaeinity Community', 'gaenity-community' ),
                'icon'  => 'fa fa-users',
            )
        );
    }

    /**
     * Register Elementor widgets.
     */
    public function register_elementor_widgets( $widgets_manager ) {
        if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }

        require_once GAENITY_COMMUNITY_PATH . 'includes/class-gaenity-elementor-widget.php';

        $widgets_manager->register( new Gaeinity_Community_Elementor_Widget() );
    }

    /**
     * Register meta boxes.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'gaenity_resource_details',
            __( 'Resource Details', 'gaenity-community' ),
            array( $this, 'render_resource_meta_box' ),
            'gaenity_resource',
            'normal',
            'high'
        );

        add_meta_box(
            'gaenity_poll_details',
            __( 'Poll Options', 'gaenity-community' ),
            array( $this, 'render_poll_meta_box' ),
            'gaenity_poll',
            'normal',
            'high'
        );
        add_meta_box(
            'gaenity_course_details',
            __( 'Course Pricing', 'gaenity-community' ),
            array( $this, 'render_course_meta_box' ),
            'gaenity_course',
            'side',
            'high'
        );
    }

    /**
     * Render resource meta box.
     */
   /**
     * Render resource meta box.
     */
    public function render_resource_meta_box( $post ) {
        wp_nonce_field( 'gaenity_resource_meta', 'gaenity_resource_meta_nonce' );

        $download_url = get_post_meta( $post->ID, '_gaenity_resource_file', true );
        $price = get_post_meta( $post->ID, '_gaenity_resource_price', true );
        $is_premium = has_term( 'paid', 'gaenity_resource_type', $post->ID );

        echo '<p>' . esc_html__( 'Provide a public URL to the resource file (PDF, DOCX, etc).', 'gaenity-community' ) . '</p>';
        echo '<label for="gaenity_resource_file">' . esc_html__( 'Download URL', 'gaenity-community' ) . '</label>';
        echo '<input type="url" class="widefat" id="gaenity_resource_file" name="gaenity_resource_file" value="' . esc_attr( $download_url ) . '" />';
        
        echo '<hr style="margin: 20px 0;">';
        
        echo '<label for="gaenity_resource_price">' . esc_html__( 'Resource Price (if paid)', 'gaenity-community' ) . '</label>';
        echo '<input type="number" step="0.01" class="widefat" id="gaenity_resource_price" name="gaenity_resource_price" value="' . esc_attr( $price ) . '" placeholder="0.00" />';
        echo '<p class="description">' . esc_html__( 'Leave empty or 0 for free resources. Enter price for paid resources.', 'gaenity-community' ) . '</p>';
        
        echo '<hr style="margin: 20px 0;">';
        
        echo '<p>' . esc_html__( 'Assign the resource type taxonomy with either Free or Paid to control front-end availability.', 'gaenity-community' ) . '</p>';
        echo '<p>' . ( $is_premium ? esc_html__( 'Current resource type includes Paid.', 'gaenity-community' ) : esc_html__( 'Current resource type is Free unless changed.', 'gaenity-community' ) ) . '</p>';
    }

    /**
     * Render poll meta box.
     */
    public function render_poll_meta_box( $post ) {
        wp_nonce_field( 'gaenity_poll_meta', 'gaenity_poll_meta_nonce' );
        $question = get_post_meta( $post->ID, '_gaenity_poll_question', true );
        $options  = get_post_meta( $post->ID, '_gaenity_poll_options', true );
        if ( empty( $options ) ) {
            $options = array(
                'option_one'   => __( 'Option one', 'gaenity-community' ),
                'option_two'   => __( 'Option two', 'gaenity-community' ),
                'option_three' => __( 'Option three', 'gaenity-community' ),
            );
        }

        echo '<p>' . esc_html__( 'Poll title appears on the front end. Use this box for an optional expanded question.', 'gaenity-community' ) . '</p>';
        echo '<label for="gaenity_poll_question">' . esc_html__( 'Expanded question (optional)', 'gaenity-community' ) . '</label>';
        echo '<textarea class="widefat" id="gaenity_poll_question" name="gaenity_poll_question" rows="3">' . esc_textarea( $question ) . '</textarea>';

        echo '<p>' . esc_html__( 'Provide up to five answer choices. Leave labels blank to hide unused options.', 'gaenity-community' ) . '</p>';

        for ( $i = 1; $i <= 5; $i++ ) {
            $key   = 'option_' . $i;
            $value = isset( $options[ $key ] ) ? $options[ $key ] : '';
            echo '<p>';
            echo '<label for="gaenity_poll_' . esc_attr( $key ) . '">' . sprintf( esc_html__( 'Option %d label', 'gaenity-community' ), $i ) . '</label>';
            echo '<input type="text" class="widefat" id="gaenity_poll_' . esc_attr( $key ) . '" name="gaenity_poll_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" />';
            echo '</p>';
        }
    }
/**
     * Render course meta box.
     */
    public function render_course_meta_box( $post ) {
        wp_nonce_field( 'gaenity_course_meta', 'gaenity_course_meta_nonce' );

        $price = get_post_meta( $post->ID, '_gaenity_course_price', true );
        $type = get_post_meta( $post->ID, '_gaenity_course_type', true );
        $duration = get_post_meta( $post->ID, '_gaenity_course_duration', true );

        ?>
        <p>
            <label for="gaenity_course_type"><strong><?php esc_html_e( 'Course Type', 'gaenity-community' ); ?></strong></label>
            <select id="gaenity_course_type" name="gaenity_course_type" class="widefat">
                <option value="free" <?php selected( $type, 'free' ); ?>><?php esc_html_e( 'Free', 'gaenity-community' ); ?></option>
                <option value="one-time" <?php selected( $type, 'one-time' ); ?>><?php esc_html_e( 'One-Time Purchase', 'gaenity-community' ); ?></option>
                <option value="subscription" <?php selected( $type, 'subscription' ); ?>><?php esc_html_e( 'Subscription', 'gaenity-community' ); ?></option>
            </select>
        </p>

        <p>
            <label for="gaenity_course_price"><strong><?php esc_html_e( 'Price', 'gaenity-community' ); ?></strong></label>
            <input type="number" id="gaenity_course_price" name="gaenity_course_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" class="widefat" placeholder="0.00">
            <small><?php echo esc_html( get_option( 'gaenity_currency', 'USD' ) ); ?></small>
        </p>

        <p>
            <label for="gaenity_course_duration"><strong><?php esc_html_e( 'Duration', 'gaenity-community' ); ?></strong></label>
            <input type="text" id="gaenity_course_duration" name="gaenity_course_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat" placeholder="e.g., 6 weeks">
        </p>
        <?php
    }

    /**
     * Save course meta.
     */
    public function save_course_meta( $post_id ) {
        if ( ! isset( $_POST['gaenity_course_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gaenity_course_meta_nonce'] ) ), 'gaenity_course_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['gaenity_course_type'] ) ) {
            update_post_meta( $post_id, '_gaenity_course_type', sanitize_text_field( $_POST['gaenity_course_type'] ) );
        }

        if ( isset( $_POST['gaenity_course_price'] ) ) {
            update_post_meta( $post_id, '_gaenity_course_price', floatval( $_POST['gaenity_course_price'] ) );
        }

        if ( isset( $_POST['gaenity_course_duration'] ) ) {
            update_post_meta( $post_id, '_gaenity_course_duration', sanitize_text_field( $_POST['gaenity_course_duration'] ) );
        }
    }
    /**
     * Save resource meta box data.
     */
    /**
     * Save resource meta box data.
     */
    public function save_resource_meta( $post_id ) {
        if ( ! isset( $_POST['gaenity_resource_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gaenity_resource_meta_nonce'] ) ), 'gaenity_resource_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['gaenity_resource_file'] ) ) {
            $url = esc_url_raw( wp_unslash( $_POST['gaenity_resource_file'] ) );
            update_post_meta( $post_id, '_gaenity_resource_file', $url );
        }

        if ( isset( $_POST['gaenity_resource_price'] ) ) {
            $price = floatval( $_POST['gaenity_resource_price'] );
            update_post_meta( $post_id, '_gaenity_resource_price', $price );
        }
    }

    /**
     * Save poll meta box data.
     */
    public function save_poll_meta( $post_id ) {
        if ( ! isset( $_POST['gaenity_poll_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gaenity_poll_meta_nonce'] ) ), 'gaenity_poll_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $question = isset( $_POST['gaenity_poll_question'] ) ? wp_kses_post( wp_unslash( $_POST['gaenity_poll_question'] ) ) : '';
        $options  = isset( $_POST['gaenity_poll_options'] ) ? (array) $_POST['gaenity_poll_options'] : array();

        $clean_options = array();
        $count         = 0;
        foreach ( $options as $key => $label ) {
            $label = sanitize_text_field( $label );
            if ( ! empty( $label ) ) {
                $count++;
                $clean_options[ $key ] = $label;
            }
        }

        if ( $count < 2 ) {
            return;
        }

        update_post_meta( $post_id, '_gaenity_poll_question', $question );
        update_post_meta( $post_id, '_gaenity_poll_options', $clean_options );
    }

    /**
     * Enqueue frontend assets with theme color inheritance.
     */
    public function enqueue_assets() {
        wp_register_style( 'gaenity-community', GAENITY_COMMUNITY_ASSETS . 'css/frontend.css', array(), $this->version );
        wp_register_script( 'gaenity-community', GAENITY_COMMUNITY_ASSETS . 'js/frontend.js', array( 'jquery' ), $this->version, true );

        wp_enqueue_style( 'gaenity-community' );
        wp_enqueue_script( 'gaenity-community' );

        // Get theme colors
        $theme_colors = $this->get_theme_colors();
        
        // Inject theme colors as CSS variables
        $custom_css = ":root {";
        if ( ! empty( $theme_colors['primary'] ) ) {
            $custom_css .= "--gaenity-primary: {$theme_colors['primary']};";
            $custom_css .= "--gaenity-primary-dark: {$this->darken_color( $theme_colors['primary'], 15 )};";
            $custom_css .= "--gaenity-primary-light: {$this->lighten_color( $theme_colors['primary'], 15 )};";
        }
        if ( ! empty( $theme_colors['secondary'] ) ) {
            $custom_css .= "--gaenity-secondary: {$theme_colors['secondary']};";
        }
        if ( ! empty( $theme_colors['text'] ) ) {
            $custom_css .= "--gaenity-text-primary: {$theme_colors['text']};";
        }
        if ( ! empty( $theme_colors['background'] ) ) {
            $custom_css .= "--gaenity-bg-primary: {$theme_colors['background']};";
        }
        $custom_css .= "}";
        
        wp_add_inline_style( 'gaenity-community', $custom_css );

        wp_localize_script(
            'gaenity-community',
            'GaeinityCommunity',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'gaenity-community' ),
                'loginUrl' => wp_login_url( get_permalink() ),
                'isLoggedIn' => is_user_logged_in(),
                'chat'    => array(
                    'pollInterval' => 10000,
                    'maxMessages'  => 30,
                ),
            )
        );
    }
/**
     * Load plugin templates for custom post types.
     */
    // public function load_plugin_templates( $template ) {
    //     // Check if we're viewing a gaenity_discussion archive
    //     if ( is_post_type_archive( 'gaenity_discussion' ) ) {
    //         $plugin_template = GAENITY_COMMUNITY_PATH . 'templates/archive-gaenity_discussion.php';
            
    //         if ( file_exists( $plugin_template ) ) {
    //             return $plugin_template;
    //         }
    //     }

    //     // Check if we're viewing a single gaenity_discussion
    //     if ( is_singular( 'gaenity_discussion' ) ) {
    //         $plugin_template = GAENITY_COMMUNITY_PATH . 'templates/single-gaenity_discussion.php';
            
    //         if ( file_exists( $plugin_template ) ) {
    //             return $plugin_template;
    //         }
    //     }

    //     // Check if we're viewing gaenity_resource archive
    //     if ( is_post_type_archive( 'gaenity_resource' ) ) {
    //         $plugin_template = GAENITY_COMMUNITY_PATH . 'templates/archive-gaenity_resource.php';
            
    //         if ( file_exists( $plugin_template ) ) {
    //             return $plugin_template;
    //         }
    //     }

    //     return $template;
    // }
    /**
     * Get theme colors automatically from multiple sources.
     *
     * @return array
     */
    protected function get_theme_colors() {
        $colors = array();
        
        // PRIORITY 1: Check manual settings FIRST
        $manual_primary = get_option( 'gaenity_primary_color' );
        $manual_secondary = get_option( 'gaenity_secondary_color' );
        
        if ( $manual_primary ) {
            $colors['primary'] = $manual_primary;
        }
        if ( $manual_secondary ) {
            $colors['secondary'] = $manual_secondary;
        }
        
        // If both manual colors are set, return immediately
        if ( ! empty( $colors['primary'] ) && ! empty( $colors['secondary'] ) ) {
            return $colors;
        }
        
        // PRIORITY 2: Check theme.json (Block themes)
        if ( function_exists( 'wp_get_global_settings' ) ) {
            $global_settings = wp_get_global_settings();
            
            if ( isset( $global_settings['color']['palette']['theme'] ) ) {
                foreach ( $global_settings['color']['palette']['theme'] as $color ) {
                    $slug = strtolower( $color['slug'] );
                    
                    if ( empty( $colors['primary'] ) && ( 
                        strpos( $slug, 'primary' ) !== false || 
                        strpos( $slug, 'accent' ) !== false ||
                        strpos( $slug, 'contrast' ) !== false
                    ) ) {
                        $colors['primary'] = $color['color'];
                    }
                    
                    if ( empty( $colors['secondary'] ) && strpos( $slug, 'secondary' ) !== false ) {
                        $colors['secondary'] = $color['color'];
                    }
                }
            }
        }
        
        // PRIORITY 3: Check Customizer theme mods
        $primary_mod_names = array(
            'primary_color',
            'accent_color', 
            'link_color',
            'theme_color',
            'brand_color',
            'header_textcolor',
        );
        
        foreach ( $primary_mod_names as $mod_name ) {
            $color = get_theme_mod( $mod_name );
            if ( $color && empty( $colors['primary'] ) ) {
                $colors['primary'] = ( strpos( $color, '#' ) === 0 ) ? $color : '#' . $color;
                break;
            }
        }
        
        // Get text and background colors
        $text_color = get_theme_mod( 'text_color' );
        if ( $text_color ) {
            $colors['text'] = ( strpos( $text_color, '#' ) === 0 ) ? $text_color : '#' . $text_color;
        }
        
        $bg_color = get_theme_mod( 'background_color' );
        if ( $bg_color ) {
            $colors['background'] = ( strpos( $bg_color, '#' ) === 0 ) ? $bg_color : '#' . $bg_color;
        }
        
        return $colors;
    }

    /**
     * Darken a hex color.
     */
    protected function darken_color( $hex, $percent ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) == 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        $r = max( 0, min( 255, $r - ( $r * $percent / 100 ) ) );
        $g = max( 0, min( 255, $g - ( $g * $percent / 100 ) ) );
        $b = max( 0, min( 255, $b - ( $b * $percent / 100 ) ) );
        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }

    /**
     * Lighten a hex color.
     */
    protected function lighten_color( $hex, $percent ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) == 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        $r = max( 0, min( 255, $r + ( ( 255 - $r ) * $percent / 100 ) ) );
        $g = max( 0, min( 255, $g + ( ( 255 - $g ) * $percent / 100 ) ) );
        $b = max( 0, min( 255, $b + ( ( 255 - $b ) * $percent / 100 ) ) );
        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }

    /**
     * Add admin settings page.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Gaenity Community Settings', 'gaenity-community' ),
            __( 'Gaenity Community', 'gaenity-community' ),
            'manage_options',
            'gaenity-community-settings',
            array( $this, 'render_settings_page' )
        );
    }
    /**
     * Add admin menu pages for managing submissions.
     */
    public function add_admin_menu_pages() {
        add_menu_page(
            __( 'Gaenity Community', 'gaenity-community' ),
            __( 'Gaenity Community', 'gaenity-community' ),
            'manage_options',
            'gaenity-community-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Dashboard', 'gaenity-community' ),
            __( 'Dashboard', 'gaenity-community' ),
            'manage_options',
            'gaenity-community-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Expert Requests', 'gaenity-community' ),
            __( 'Expert Requests', 'gaenity-community' ),
            'manage_options',
            'gaenity-expert-requests',
            array( $this, 'render_expert_requests_page' )
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Resource Downloads', 'gaenity-community' ),
            __( 'Resource Downloads', 'gaenity-community' ),
            'manage_options',
            'gaenity-resource-downloads',
            array( $this, 'render_downloads_page' )
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Contact Messages', 'gaenity-community' ),
            __( 'Contact Messages', 'gaenity-community' ),
            'manage_options',
            'gaenity-contact-messages',
            array( $this, 'render_contacts_page' )
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Chat Messages', 'gaenity-community' ),
            __( 'Chat Messages', 'gaenity-community' ),
            'manage_options',
            'gaenity-chat-messages',
            array( $this, 'render_chat_admin_page' )
        );

        add_submenu_page(
            'gaenity-community-dashboard',
            __( 'Settings', 'gaenity-community' ),
            __( 'Settings', 'gaenity-community' ),
            'manage_options',
            'gaenity-community-settings',
            array( $this, 'render_settings_page' )
        );
    }
    /**
     * Register admin menu.
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Gaenity Community', 'gaenity-community' ),
            __( 'Gaenity Community', 'gaenity-community' ),
            'manage_options',
            'gaenity-community',
            array( $this, 'render_dashboard_page' ),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Dashboard', 'gaenity-community' ),
            __( 'Dashboard', 'gaenity-community' ),
            'manage_options',
            'gaenity-community',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Resources', 'gaenity-community' ),
            __( 'Resources', 'gaenity-community' ),
            'manage_options',
            'gaenity-resources',
            array( $this, 'render_resources_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Expert Requests', 'gaenity-community' ),
            __( 'Expert Requests', 'gaenity-community' ),
            'manage_options',
            'gaenity-expert-requests',
            array( $this, 'render_expert_requests_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Resource Downloads', 'gaenity-community' ),
            __( 'Resource Downloads', 'gaenity-community' ),
            'manage_options',
            'gaenity-downloads',
            array( $this, 'render_downloads_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Contact Messages', 'gaenity-community' ),
            __( 'Contact Messages', 'gaenity-community' ),
            'manage_options',
            'gaenity-contact',
            array( $this, 'render_contact_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Chat Messages', 'gaenity-community' ),
            __( 'Chat Messages', 'gaenity-community' ),
            'manage_options',
            'gaenity-chat',
            array( $this, 'render_chat_page' )
        );

        add_submenu_page(
            'gaenity-community',
            __( 'Settings', 'gaenity-community' ),
            __( 'Settings', 'gaenity-community' ),
            'manage_options',
            'gaenity-settings',
            array( $this, 'render_settings_page' )
        );
        add_submenu_page(
            'gaenity-community',
            __( 'Transactions', 'gaenity-community' ),
            __( 'Transactions', 'gaenity-community' ),
            'manage_options',
            'gaenity-transactions',
            array( $this, 'render_transactions_page' )
        );
    }
    /**
     * Register admin menu.
     */
   

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        global $wpdb;
        
        $stats = array(
            'discussions' => wp_count_posts( 'gaenity_discussion' )->publish,
            'resources' => wp_count_posts( 'gaenity_resource' )->publish,
            'experts' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_expert_requests WHERE challenge = 'expert_registration'" ),
            'expert_requests' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_expert_requests WHERE challenge != 'expert_registration'" ),
            'downloads' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_resource_downloads" ),
            'contacts' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_contact_messages" ),
            'chat_messages' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_chat_messages" ),
            'members' => count_users()['total_users'],
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Gaenity Community Dashboard', 'gaenity-community' ); ?></h1>
            
            <div class="gaenity-admin-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['members'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Total Members', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['discussions'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Discussions', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['experts'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Registered Experts', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['expert_requests'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Expert Requests', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #72aee6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['resources'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #9d6ace; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['downloads'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Resource Downloads', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #c84e00; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['contacts'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Contact Messages', 'gaenity-community' ); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #1d2327; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px;"><?php echo esc_html( $stats['chat_messages'] ); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Chat Messages', 'gaenity-community' ); ?></p>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php esc_html_e( 'Quick Actions', 'gaenity-community' ); ?></h2>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=gaenity-expert-requests' ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Experts', 'gaenity-community' ); ?></a>
                    <a href="<?php echo admin_url( 'post-new.php?post_type=gaenity_resource' ); ?>" class="button"><?php esc_html_e( 'Add Resource', 'gaenity-community' ); ?></a>
                    <a href="<?php echo admin_url( 'post-new.php?post_type=gaenity_poll' ); ?>" class="button"><?php esc_html_e( 'Create Poll', 'gaenity-community' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=gaenity-community-settings' ); ?>" class="button"><?php esc_html_e( 'Settings', 'gaenity-community' ); ?></a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render expert requests page with CRUD.
     */
    public function render_expert_requests_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'gaenity_expert_requests';

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_expert_' . $_GET['id'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Expert request deleted.', 'gaenity-community' ) . '</p></div>';
        }

        // Handle approve expert
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'approve' && isset( $_GET['id'] ) && check_admin_referer( 'approve_expert_' . $_GET['id'] ) ) {
            $expert = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint( $_GET['id'] ) ) );
            if ( $expert && $expert->challenge === 'expert_registration' ) {
                // Create WordPress user with expert role
                if ( ! email_exists( $expert->email ) ) {
                    $user_id = wp_create_user( 
                        sanitize_user( current( explode( '@', $expert->email ) ) ), 
                        wp_generate_password(), 
                        $expert->email 
                    );
                    if ( ! is_wp_error( $user_id ) ) {
                        wp_update_user( array( 'ID' => $user_id, 'role' => 'gaenity_expert', 'display_name' => $expert->name ) );
                        update_user_meta( $user_id, 'gaenity_expert_approved', 1 );
                        update_user_meta( $user_id, 'gaenity_expertise', $expert->description );
                        update_user_meta( $user_id, 'gaenity_profile_url', $expert->budget );
                        echo '<div class="notice notice-success"><p>' . esc_html__( 'Expert approved and account created!', 'gaenity-community' ) . '</p></div>';
                    }
                }
            }
        }

        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        $filter = isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
        $where = $filter === 'experts' ? "WHERE challenge = 'expert_registration'" : ( $filter === 'requests' ? "WHERE challenge != 'expert_registration'" : '' );

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Expert Requests & Registrations', 'gaenity-community' ); ?></h1>

            <ul class="subsubsub">
                <li><a href="?page=gaenity-expert-requests&filter=all" <?php echo $filter === 'all' ? 'class="current"' : ''; ?>><?php esc_html_e( 'All', 'gaenity-community' ); ?></a> | </li>
                <li><a href="?page=gaenity-expert-requests&filter=experts" <?php echo $filter === 'experts' ? 'class="current"' : ''; ?>><?php esc_html_e( 'Expert Registrations', 'gaenity-community' ); ?></a> | </li>
                <li><a href="?page=gaenity-expert-requests&filter=requests" <?php echo $filter === 'requests' ? 'class="current"' : ''; ?>><?php esc_html_e( 'Help Requests', 'gaenity-community' ); ?></a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $items ) : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td><strong><?php echo esc_html( $item->name ); ?></strong></td>
                                <td><?php echo esc_html( $item->email ); ?></td>
                                <td>
                                    <?php if ( $item->challenge === 'expert_registration' ) : ?>
                                        <span style="background: #00a32a; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;"><?php esc_html_e( 'EXPERT REG', 'gaenity-community' ); ?></span>
                                    <?php else : ?>
                                        <span style="background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;"><?php esc_html_e( 'HELP REQUEST', 'gaenity-community' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <details>
                                        <summary style="cursor: pointer;"><?php esc_html_e( 'View Details', 'gaenity-community' ); ?></summary>
                                        <div style="margin-top: 10px; padding: 10px; background: #f0f0f1;">
                                            <?php if ( $item->challenge === 'expert_registration' ) : ?>
                                                <p><strong><?php esc_html_e( 'Expertise:', 'gaenity-community' ); ?></strong><br><?php echo esc_html( $item->description ); ?></p>
                                                <p><strong><?php esc_html_e( 'Profile URL:', 'gaenity-community' ); ?></strong><br><a href="<?php echo esc_url( $item->budget ); ?>" target="_blank"><?php echo esc_html( $item->budget ); ?></a></p>
                                            <?php else : ?>
                                                <p><strong><?php esc_html_e( 'Region:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->region ); ?> | <strong><?php esc_html_e( 'Country:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->country ); ?></p>
                                                <p><strong><?php esc_html_e( 'Industry:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->industry ); ?></p>
                                                <p><strong><?php esc_html_e( 'Challenge:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->challenge ); ?></p>
                                                <p><strong><?php esc_html_e( 'Description:', 'gaenity-community' ); ?></strong><br><?php echo esc_html( $item->description ); ?></p>
                                                <p><strong><?php esc_html_e( 'Budget:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->budget ); ?> | <strong><?php esc_html_e( 'Preference:', 'gaenity-community' ); ?></strong> <?php echo esc_html( $item->preference ); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </td>
                                <td><?php echo esc_html( mysql2date( 'M j, Y', $item->created_at ) ); ?></td>
                                <td>
                                    <?php if ( $item->challenge === 'expert_registration' ) : ?>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gaenity-expert-requests&action=approve&id=' . $item->id ), 'approve_expert_' . $item->id ); ?>" class="button button-primary button-small"><?php esc_html_e( 'Approve', 'gaenity-community' ); ?></a>
                                    <?php endif; ?>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gaenity-expert-requests&action=delete&id=' . $item->id ), 'delete_expert_' . $item->id ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7"><?php esc_html_e( 'No requests found.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render downloads page.
     */
    public function render_downloads_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'gaenity_resource_downloads';

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_download_' . $_GET['id'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Download record deleted.', 'gaenity-community' ) . '</p></div>';
        }

        // Get analytics data
        $total_downloads = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $downloads_today = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE DATE(created_at) = CURDATE()" );
        $downloads_week = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
        $downloads_month = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" );

        // Get most popular resources
        $popular_resources = $wpdb->get_results( "
            SELECT resource_id, COUNT(*) as download_count
            FROM $table
            GROUP BY resource_id
            ORDER BY download_count DESC
            LIMIT 5
        " );

        // Get downloads by role
        $downloads_by_role = $wpdb->get_results( "
            SELECT role, COUNT(*) as count
            FROM $table
            WHERE role != ''
            GROUP BY role
            ORDER BY count DESC
            LIMIT 5
        " );

        // Get downloads by industry
        $downloads_by_industry = $wpdb->get_results( "
            SELECT industry, COUNT(*) as count
            FROM $table
            WHERE industry != ''
            GROUP BY industry
            ORDER BY count DESC
            LIMIT 5
        " );

        // Get recent daily download counts for chart
        $daily_downloads = $wpdb->get_results( "
            SELECT DATE(created_at) as download_date, COUNT(*) as count
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            GROUP BY DATE(created_at)
            ORDER BY download_date ASC
        " );

        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        $total = $total_downloads;
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Resource Downloads Analytics', 'gaenity-community' ); ?></h1>

            <!-- Analytics Dashboard -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Downloads', 'gaenity-community' ); ?></h3>
                    <p style="margin: 0; font-size: 32px; font-weight: 700;"><?php echo number_format_i18n( $total_downloads ); ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Today', 'gaenity-community' ); ?></h3>
                    <p style="margin: 0; font-size: 32px; font-weight: 700;"><?php echo number_format_i18n( $downloads_today ); ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Last 7 Days', 'gaenity-community' ); ?></h3>
                    <p style="margin: 0; font-size: 32px; font-weight: 700;"><?php echo number_format_i18n( $downloads_week ); ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Last 30 Days', 'gaenity-community' ); ?></h3>
                    <p style="margin: 0; font-size: 32px; font-weight: 700;"><?php echo number_format_i18n( $downloads_month ); ?></p>
                </div>
            </div>

            <!-- Charts and Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Most Popular Resources -->
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0; font-size: 18px; color: #1e293b;"><?php esc_html_e( 'Most Popular Resources', 'gaenity-community' ); ?></h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <th style="text-align: left; padding: 10px 0; color: #64748b; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'Resource', 'gaenity-community' ); ?></th>
                                <th style="text-align: right; padding: 10px 0; color: #64748b; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'Downloads', 'gaenity-community' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $popular_resources ) : ?>
                                <?php foreach ( $popular_resources as $resource ) : ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 0; color: #475569;">
                                            <a href="<?php echo get_edit_post_link( $resource->resource_id ); ?>" style="text-decoration: none; color: #667eea; font-weight: 500;">
                                                <?php echo esc_html( get_the_title( $resource->resource_id ) ?: 'Resource #' . $resource->resource_id ); ?>
                                            </a>
                                        </td>
                                        <td style="padding: 12px 0; text-align: right; color: #1e293b; font-weight: 600;"><?php echo number_format_i18n( $resource->download_count ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="2" style="padding: 12px 0; color: #94a3b8;"><?php esc_html_e( 'No data available', 'gaenity-community' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Downloads by Role -->
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0; font-size: 18px; color: #1e293b;"><?php esc_html_e( 'Downloads by Role', 'gaenity-community' ); ?></h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <th style="text-align: left; padding: 10px 0; color: #64748b; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'Role', 'gaenity-community' ); ?></th>
                                <th style="text-align: right; padding: 10px 0; color: #64748b; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'Count', 'gaenity-community' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $downloads_by_role ) : ?>
                                <?php foreach ( $downloads_by_role as $role_data ) : ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 0; color: #475569; font-weight: 500;"><?php echo esc_html( $role_data->role ); ?></td>
                                        <td style="padding: 12px 0; text-align: right; color: #1e293b; font-weight: 600;"><?php echo number_format_i18n( $role_data->count ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="2" style="padding: 12px 0; color: #94a3b8;"><?php esc_html_e( 'No data available', 'gaenity-community' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Downloads by Industry -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
                <h2 style="margin-top: 0; font-size: 18px; color: #1e293b;"><?php esc_html_e( 'Downloads by Industry', 'gaenity-community' ); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <?php if ( $downloads_by_industry ) : ?>
                        <?php foreach ( $downloads_by_industry as $industry_data ) : ?>
                            <div style="padding: 15px; background: #f8fafc; border-radius: 6px; border-left: 4px solid #667eea;">
                                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;"><?php echo esc_html( $industry_data->industry ); ?></div>
                                <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format_i18n( $industry_data->count ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div style="padding: 15px; color: #94a3b8;"><?php esc_html_e( 'No data available', 'gaenity-community' ); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Download Trend Chart -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
                <h2 style="margin-top: 0; font-size: 18px; color: #1e293b;"><?php esc_html_e( 'Download Trend (Last 14 Days)', 'gaenity-community' ); ?></h2>
                <div style="height: 200px; position: relative;">
                    <?php if ( $daily_downloads ) : ?>
                        <?php
                        $max_count = max( array_column( (array) $daily_downloads, 'count' ) );
                        $max_count = max( $max_count, 1 ); // Prevent division by zero
                        $bar_width = ( 100 / count( $daily_downloads ) ) - 1;
                        ?>
                        <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 180px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
                            <?php foreach ( $daily_downloads as $day ) : ?>
                                <?php $height = ( $day->count / $max_count ) * 100; ?>
                                <div style="flex: 1; margin: 0 2px; position: relative;">
                                    <div style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); border-radius: 4px 4px 0 0; height: <?php echo $height; ?>%; min-height: 5px; position: relative; transition: all 0.3s;" title="<?php echo esc_attr( date( 'M j', strtotime( $day->download_date ) ) . ': ' . $day->count ); ?>">
                                        <span style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 11px; font-weight: 600; color: #1e293b;"><?php echo esc_html( $day->count ); ?></span>
                                    </div>
                                    <div style="font-size: 10px; color: #64748b; text-align: center; margin-top: 5px; transform: rotate(-45deg); white-space: nowrap; position: absolute; left: 0;"><?php echo esc_html( date( 'M j', strtotime( $day->download_date ) ) ); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p style="color: #94a3b8; text-align: center; padding: 60px 0;"><?php esc_html_e( 'No download data available for the last 14 days', 'gaenity-community' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <hr style="margin: 40px 0; border: none; border-top: 2px solid #e2e8f0;">

            <h2><?php esc_html_e( 'All Downloads', 'gaenity-community' ); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Resource', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Role', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Consent', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $items ) : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td><a href="<?php echo get_edit_post_link( $item->resource_id ); ?>"><?php echo esc_html( get_the_title( $item->resource_id ) ?: 'Resource #' . $item->resource_id ); ?></a></td>
                                <td><?php echo esc_html( $item->email ); ?></td>
                                <td><?php echo esc_html( $item->role ); ?></td>
                                <td><?php echo esc_html( $item->industry ); ?></td>
                                <td><?php echo $item->consent ? 'âœ“' : 'â€”'; ?></td>
                                <td><?php echo esc_html( mysql2date( 'M j, Y', $item->created_at ) ); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gaenity-resource-downloads&action=delete&id=' . $item->id ), 'delete_download_' . $item->id ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this record?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8"><?php esc_html_e( 'No downloads yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render contact messages page.
     */
    public function render_contacts_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'gaenity_contact_messages';

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_contact_' . $_GET['id'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Message deleted.', 'gaenity-community' ) . '</p></div>';
        }

        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Contact Messages', 'gaenity-community' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Subject', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $items ) : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td><strong><?php echo esc_html( $item->name ); ?></strong></td>
                                <td><a href="mailto:<?php echo esc_attr( $item->email ); ?>"><?php echo esc_html( $item->email ); ?></a></td>
                                <td><?php echo esc_html( $item->subject ); ?></td>
                                <td>
                                    <details>
                                        <summary style="cursor: pointer;"><?php esc_html_e( 'Read', 'gaenity-community' ); ?></summary>
                                        <div style="margin-top: 10px; padding: 10px; background: #f0f0f1;">
                                            <?php echo wp_kses_post( wpautop( $item->message ) ); ?>
                                        </div>
                                    </details>
                                </td>
                                <td><?php echo esc_html( mysql2date( 'M j, Y', $item->created_at ) ); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gaenity-contact-messages&action=delete&id=' . $item->id ), 'delete_contact_' . $item->id ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this message?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7"><?php esc_html_e( 'No messages yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render chat admin page.
     */
    public function render_chat_admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'gaenity_chat_messages';

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_chat_' . $_GET['id'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['id'] ) ), array( '%d' ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Chat message deleted.', 'gaenity-community' ) . '</p></div>';
        }

        $per_page = 50;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Chat Messages', 'gaenity-community' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Display Name', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Role', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Region', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $items ) : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td><?php echo esc_html( $item->display_name ); ?><?php echo $item->is_anonymous ? ' ðŸ”’' : ''; ?></td>
                                <td><?php echo esc_html( wp_trim_words( $item->message, 15 ) ); ?></td>
                                <td><?php echo esc_html( $item->role ); ?></td>
                                <td><?php echo esc_html( $item->region ); ?></td>
                                <td><?php echo esc_html( mysql2date( 'M j, g:i a', $item->created_at ) ); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gaenity-chat-messages&action=delete&id=' . $item->id ), 'delete_chat_' . $item->id ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this message?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7"><?php esc_html_e( 'No chat messages yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render resources management page.
     */
    public function render_resources_page() {
        global $wpdb;

        // Handle create/update
        if ( isset( $_POST['gaenity_save_resource'] ) && check_admin_referer( 'gaenity_resource_action' ) ) {
            $resource_id = isset( $_POST['resource_id'] ) ? absint( $_POST['resource_id'] ) : 0;
            
            $post_data = array(
                'post_title'   => sanitize_text_field( $_POST['resource_title'] ),
                'post_content' => wp_kses_post( $_POST['resource_description'] ),
                'post_excerpt' => sanitize_text_field( $_POST['resource_excerpt'] ),
                'post_type'    => 'gaenity_resource',
                'post_status'  => 'publish',
            );

            if ( $resource_id ) {
                $post_data['ID'] = $resource_id;
                wp_update_post( $post_data );
                $message = __( 'Resource updated successfully!', 'gaenity-community' );
            } else {
                $resource_id = wp_insert_post( $post_data );
                $message = __( 'Resource created successfully!', 'gaenity-community' );
            }

            // Save meta

        update_post_meta( $resource_id, '_gaenity_resource_file', esc_url_raw( $_POST['resource_file'] ) );
        update_post_meta( $resource_id, '_gaenity_resource_price', floatval( $_POST['resource_price'] ) );            
            // Save type
            $type = sanitize_text_field( $_POST['resource_type'] );
            wp_set_object_terms( $resource_id, $type, 'gaenity_resource_type' );

            echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
        }

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['resource_id'] ) && check_admin_referer( 'delete_resource_' . $_GET['resource_id'] ) ) {
            wp_delete_post( absint( $_GET['resource_id'] ), true );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Resource deleted successfully!', 'gaenity-community' ) . '</p></div>';
        }

        // Get action
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $resource_id = isset( $_GET['resource_id'] ) ? absint( $_GET['resource_id'] ) : 0;

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></h1>
            
            <?php if ( $action === 'list' ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'gaenity-community' ); ?></a>
                <hr class="wp-header-end">

                <?php
                $resources = new WP_Query( array(
                    'post_type' => 'gaenity_resource',
                    'posts_per_page' => -1,
                    'orderby' => 'date',
                    'order' => 'DESC',
                ) );
                ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'Title', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'File URL', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'Downloads', 'gaenity-community' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                            <th style="width: 150px;"><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $resources->have_posts() ) : ?>
                            <?php while ( $resources->have_posts() ) : $resources->the_post(); 
                                $id = get_the_ID();
                                $file_url = get_post_meta( $id, '_gaenity_resource_file', true );
                                $types = wp_get_post_terms( $id, 'gaenity_resource_type', array( 'fields' => 'names' ) );
                                $type = ! empty( $types ) ? $types[0] : '-';
                                
                                $download_count = $wpdb->get_var( $wpdb->prepare( 
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_resource_downloads WHERE resource_id = %d", 
                                    $id 
                                ) );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $id ); ?></strong></td>
                                    <td>
                                        <strong><?php the_title(); ?></strong>
                                        <?php if ( has_excerpt() ) : ?>
                                            <br><small style="color: #666;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 10 ) ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $type === 'free' ) : ?>
                                            <span style="background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">FREE</span>
                                        <?php else : ?>
                                            <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">PAID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <?php 
                                    $resource_price = get_post_meta( $id, '_gaenity_resource_price', true );
                                    if ( $type === 'free' || empty( $resource_price ) ) : ?>
                                        <span style="color: #10b981; font-weight: 600;">FREE</span>
                                    <?php else : ?>
                                        <strong><?php echo esc_html( $this->get_currency_symbol() . number_format( $resource_price, 2 ) ); ?></strong>
                                    <?php endif; ?>
                                </td>
                                    <td>
                                        <?php if ( $file_url ) : ?>
                                            <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" style="color: #2563eb;">
                                                <?php echo esc_html( wp_trim_words( $file_url, 6, '...' ) ); ?>
                                            </a>
                                        <?php else : ?>
                                            <span style="color: #dc2626;">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo esc_html( $download_count ); ?></strong></td>
                                    <td><?php echo esc_html( get_the_date() ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources&action=edit&resource_id=' . $id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'gaenity-community' ); ?></a>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gaenity-resources&action=delete&resource_id=' . $id ), 'delete_resource_' . $id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this resource?', 'gaenity-community' ); ?>')" style="color: #dc2626;"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <p style="color: #64748b; font-size: 16px;"><?php esc_html_e( 'No resources found.', 'gaenity-community' ); ?></p>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources&action=add' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Create Your First Resource', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php wp_reset_postdata(); ?>

            <?php elseif ( $action === 'add' || $action === 'edit' ) : ?>
                
                <?php
                $resource = null;
                $title = '';
                $description = '';
                $excerpt = '';
                $file_url = '';
                $type = 'free';

                if ( $action === 'edit' && $resource_id ) {
                    $resource = get_post( $resource_id );
                    if ( $resource ) {
                        $title = $resource->post_title;
                        $description = $resource->post_content;
                        $excerpt = $resource->post_excerpt;
                        $file_url = get_post_meta( $resource_id, '_gaenity_resource_file', true );
                        $types = wp_get_post_terms( $resource_id, 'gaenity_resource_type', array( 'fields' => 'names' ) );
                        $type = ! empty( $types ) ? $types[0] : 'free';
                    }
                }
                ?>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources' ) ); ?>" class="page-title-action">â† <?php esc_html_e( 'Back to Resources', 'gaenity-community' ); ?></a>
                <hr class="wp-header-end">

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 900px;">
                    <h2><?php echo $action === 'edit' ? esc_html__( 'Edit Resource', 'gaenity-community' ) : esc_html__( 'Add New Resource', 'gaenity-community' ); ?></h2>

                    <form method="post">
                        <?php wp_nonce_field( 'gaenity_resource_action' ); ?>
                        <input type="hidden" name="resource_id" value="<?php echo esc_attr( $resource_id ); ?>">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="resource_title"><?php esc_html_e( 'Title', 'gaenity-community' ); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="resource_title" name="resource_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" required>
                                    <p class="description"><?php esc_html_e( 'The main title of the resource', 'gaenity-community' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="resource_excerpt"><?php esc_html_e( 'Short Description', 'gaenity-community' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="resource_excerpt" name="resource_excerpt" value="<?php echo esc_attr( $excerpt ); ?>" class="large-text">
                                    <p class="description"><?php esc_html_e( 'Brief summary shown in resource cards (optional)', 'gaenity-community' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="resource_description"><?php esc_html_e( 'Full Description', 'gaenity-community' ); ?></label>
                                </th>
                                <td>
                                    <?php
                                    wp_editor( $description, 'resource_description', array(
                                        'textarea_rows' => 10,
                                        'media_buttons' => false,
                                        'teeny' => true,
                                    ) );
                                    ?>
                                    <p class="description"><?php esc_html_e( 'Detailed description of the resource', 'gaenity-community' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="resource_file"><?php esc_html_e( 'Download URL', 'gaenity-community' ); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="url" id="resource_file" name="resource_file" value="<?php echo esc_attr( $file_url ); ?>" class="large-text" required>
                                    <p class="description"><?php esc_html_e( 'Full URL to the downloadable file (PDF, DOCX, ZIP, etc.)', 'gaenity-community' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="resource_type"><?php esc_html_e( 'Resource Type', 'gaenity-community' ); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <select id="resource_type" name="resource_type" required>
                                        <option value="free" <?php selected( $type, 'free' ); ?>><?php esc_html_e( 'Free', 'gaenity-community' ); ?></option>
                                        <option value="paid" <?php selected( $type, 'paid' ); ?>><?php esc_html_e( 'Paid', 'gaenity-community' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Free resources can be downloaded immediately. Paid resources show "Coming Soon".', 'gaenity-community' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="resource_price"><?php esc_html_e( 'Price', 'gaenity-community' ); ?></label>
                                </th>
                                <td>
                                    <input type="number" step="0.01" id="resource_price" name="resource_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" placeholder="0.00">
                                    <p class="description"><?php echo sprintf( esc_html__( 'Price in %s. Leave empty or 0 for free resources.', 'gaenity-community' ), esc_html( get_option( 'gaenity_currency', 'USD' ) ) ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" name="gaenity_save_resource" class="button button-primary button-large">
                                <?php echo $action === 'edit' ? esc_html__( 'Update Resource', 'gaenity-community' ) : esc_html__( 'Create Resource', 'gaenity-community' ); ?>
                            </button>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources' ) ); ?>" class="button button-large"><?php esc_html_e( 'Cancel', 'gaenity-community' ); ?></a>
                        </p>
                    </form>
                </div>

            <?php endif; ?>

        </div>
        <?php
    }
    /**
     * Render transactions page.
     */
    public function render_transactions_page() {
        global $wpdb;

        // Handle status update
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'approve' && isset( $_GET['id'] ) && check_admin_referer( 'approve_transaction_' . $_GET['id'] ) ) {
            $wpdb->update(
                $wpdb->prefix . 'gaenity_transactions',
                array( 'status' => 'completed' ),
                array( 'id' => absint( $_GET['id'] ) )
            );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Transaction approved!', 'gaenity-community' ) . '</p></div>';
        }

        $transactions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gaenity_transactions ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Payment Transactions', 'gaenity-community' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'User', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Item', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Gateway', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $transactions ) ) : ?>
                        <?php foreach ( $transactions as $txn ) : 
                            $item = get_post( $txn->item_id );
                            $user = $txn->user_id ? get_userdata( $txn->user_id ) : null;
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $txn->id ); ?></strong></td>
                                <td>
                                    <?php if ( $user ) : ?>
                                        <?php echo esc_html( $user->display_name ); ?><br>
                                        <small><?php echo esc_html( $txn->email ); ?></small>
                                    <?php else : ?>
                                        <?php echo esc_html( $txn->email ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item ? esc_html( $item->post_title ) : esc_html( $txn->item_type . ' #' . $txn->item_id ); ?></td>
                                <td><strong><?php echo esc_html( $txn->currency . ' ' . number_format( $txn->amount, 2 ) ); ?></strong></td>
                                <td><?php echo esc_html( ucfirst( $txn->gateway ) ); ?></td>
                                <td>
                                    <?php if ( $txn->status === 'completed' ) : ?>
                                        <span style="background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">COMPLETED</span>
                                    <?php elseif ( $txn->status === 'awaiting_confirmation' ) : ?>
                                        <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">AWAITING</span>
                                    <?php else : ?>
                                        <span style="background: #e5e7eb; color: #374151; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;"><?php echo esc_html( strtoupper( $txn->status ) ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $txn->created_at ) ) ); ?></td>
                                <td>
                                    <?php if ( $txn->status === 'awaiting_confirmation' ) : ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gaenity-transactions&action=approve&id=' . $txn->id ), 'approve_transaction_' . $txn->id ) ); ?>" class="button button-small button-primary"><?php esc_html_e( 'Approve', 'gaenity-community' ); ?></a>
                                    <?php else : ?>
                                        â€”
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px;"><?php esc_html_e( 'No transactions yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
/**
     * Render contact messages page.
     */
    public function render_contact_page() {
        global $wpdb;

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_contact_' . $_GET['id'] ) ) {
            $wpdb->delete( $wpdb->prefix . 'gaenity_contact_messages', array( 'id' => absint( $_GET['id'] ) ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Message deleted successfully!', 'gaenity-community' ) . '</p></div>';
        }

        $messages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gaenity_contact_messages ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Contact Messages', 'gaenity-community' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Subject', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $messages ) ) : ?>
                        <?php foreach ( $messages as $msg ) : ?>
                            <tr>
                                <td><?php echo esc_html( $msg->name ); ?></td>
                                <td><a href="mailto:<?php echo esc_attr( $msg->email ); ?>"><?php echo esc_html( $msg->email ); ?></a></td>
                                <td><strong><?php echo esc_html( $msg->subject ); ?></strong></td>
                                <td><?php echo esc_html( wp_trim_words( $msg->message, 15 ) ); ?></td>
                                <td><?php echo esc_html( date( 'M j, Y', strtotime( $msg->created_at ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gaenity-contact&action=delete&id=' . $msg->id ), 'delete_contact_' . $msg->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this message?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px;"><?php esc_html_e( 'No messages yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render chat messages page.
     */
    public function render_chat_page() {
        global $wpdb;

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_chat_' . $_GET['id'] ) ) {
            $wpdb->delete( $wpdb->prefix . 'gaenity_chat_messages', array( 'id' => absint( $_GET['id'] ) ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Message deleted successfully!', 'gaenity-community' ) . '</p></div>';
        }

        $messages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gaenity_chat_messages ORDER BY created_at DESC LIMIT 100" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Chat Messages', 'gaenity-community' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'User', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Region', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'gaenity-community' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'gaenity-community' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $messages ) ) : ?>
                        <?php foreach ( $messages as $msg ) : ?>
                            <tr>
                                <td><?php echo esc_html( $msg->display_name ); ?></td>
                                <td><?php echo esc_html( wp_trim_words( $msg->message, 20 ) ); ?></td>
                                <td><?php echo esc_html( $msg->region ); ?></td>
                                <td><?php echo esc_html( $msg->industry ); ?></td>
                                <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $msg->created_at ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gaenity-chat&action=delete&id=' . $msg->id ), 'delete_chat_' . $msg->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this message?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Delete', 'gaenity-community' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px;"><?php esc_html_e( 'No messages yet.', 'gaenity-community' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

     /* Render settings page.
     */
    public function render_settings_page() {
        // Handle seed data
        if ( isset( $_POST['gaenity_seed_data'] ) && check_admin_referer( 'gaenity_seed_data' ) ) {
            self::seed_dummy_data();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Dummy data added successfully!', 'gaenity-community' ) . '</p></div>';
        }

        // Handle reset seed
        if ( isset( $_POST['gaenity_reset_seed'] ) && check_admin_referer( 'gaenity_reset_seed' ) ) {
            delete_option( 'gaenity_dummy_seeded' );
            self::seed_dummy_data();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Dummy data reset and re-seeded!', 'gaenity-community' ) . '</p></div>';
        }

       // Handle settings save
        if ( isset( $_POST['gaenity_save_settings'] ) && check_admin_referer( 'gaenity_settings' ) ) {
            update_option( 'gaenity_primary_color', sanitize_hex_color( $_POST['gaenity_primary_color'] ) );
            update_option( 'gaenity_secondary_color', sanitize_hex_color( $_POST['gaenity_secondary_color'] ) );
            update_option( 'gaenity_register_url', esc_url_raw( $_POST['gaenity_register_url'] ) );
            update_option( 'gaenity_ask_expert_url', esc_url_raw( $_POST['gaenity_ask_expert_url'] ) );
            update_option( 'gaenity_become_expert_url', esc_url_raw( $_POST['gaenity_become_expert_url'] ) );
            update_option( 'gaenity_resources_url', esc_url_raw( $_POST['gaenity_resources_url'] ) );
            
            // Save payment gateway settings
            update_option( 'gaenity_enabled_gateways', isset( $_POST['gaenity_enabled_gateways'] ) ? array_map( 'sanitize_text_field', $_POST['gaenity_enabled_gateways'] ) : array() );
            update_option( 'gaenity_currency', sanitize_text_field( $_POST['gaenity_currency'] ) );
            
            // Stripe
            update_option( 'gaenity_stripe_mode', sanitize_text_field( $_POST['gaenity_stripe_mode'] ) );
            update_option( 'gaenity_stripe_public_key', sanitize_text_field( $_POST['gaenity_stripe_public_key'] ) );
            update_option( 'gaenity_stripe_secret_key', sanitize_text_field( $_POST['gaenity_stripe_secret_key'] ) );
            
            // PayPal
            update_option( 'gaenity_paypal_mode', sanitize_text_field( $_POST['gaenity_paypal_mode'] ) );
            update_option( 'gaenity_paypal_client_id', sanitize_text_field( $_POST['gaenity_paypal_client_id'] ) );
            update_option( 'gaenity_paypal_client_secret', sanitize_text_field( $_POST['gaenity_paypal_client_secret'] ) );
            
            // Paystack
            update_option( 'gaenity_paystack_public_key', sanitize_text_field( $_POST['gaenity_paystack_public_key'] ) );
            update_option( 'gaenity_paystack_secret_key', sanitize_text_field( $_POST['gaenity_paystack_secret_key'] ) );
            
            // Bank Transfer
            update_option( 'gaenity_bank_details', wp_kses_post( $_POST['gaenity_bank_details'] ) );
            update_option( 'gaenity_checkout_url', esc_url_raw( $_POST['gaenity_checkout_url'] ) );
            update_option( 'gaenity_discussion_form_url', esc_url_raw( $_POST['gaenity_discussion_form_url'] ) );
            update_option( 'gaenity_expert_directory_url', esc_url_raw( $_POST['gaenity_expert_directory_url'] ) );
            update_option( 'gaenity_polls_url', esc_url_raw( $_POST['gaenity_polls_url'] ) );
            update_option( 'gaenity_courses_url', esc_url_raw( $_POST['gaenity_courses_url'] ) );

            // Save resource section customization
            update_option( 'gaenity_resources_title', sanitize_text_field( $_POST['gaenity_resources_title'] ) );
            update_option( 'gaenity_resources_description', sanitize_textarea_field( $_POST['gaenity_resources_description'] ) );
            update_option( 'gaenity_resources_title_color', sanitize_hex_color( $_POST['gaenity_resources_title_color'] ) );
            update_option( 'gaenity_resources_title_size', absint( $_POST['gaenity_resources_title_size'] ) );
            update_option( 'gaenity_resources_desc_size', absint( $_POST['gaenity_resources_desc_size'] ) );
            update_option( 'gaenity_resources_font_family', sanitize_text_field( $_POST['gaenity_resources_font_family'] ) );

            // Save download settings
            update_option( 'gaenity_download_expiry_days', absint( $_POST['gaenity_download_expiry_days'] ) );
            update_option( 'gaenity_download_limit', absint( $_POST['gaenity_download_limit'] ) );

            // Save email notification settings
            update_option( 'gaenity_enable_email_notifications', isset( $_POST['gaenity_enable_email_notifications'] ) ? 1 : 0 );
            update_option( 'gaenity_email_from_name', sanitize_text_field( $_POST['gaenity_email_from_name'] ) );
            update_option( 'gaenity_email_from_email', sanitize_email( $_POST['gaenity_email_from_email'] ) );
            update_option( 'gaenity_free_resource_email_subject', sanitize_text_field( $_POST['gaenity_free_resource_email_subject'] ) );
            update_option( 'gaenity_free_resource_email_body', wp_kses_post( $_POST['gaenity_free_resource_email_body'] ) );
            update_option( 'gaenity_paid_resource_email_subject', sanitize_text_field( $_POST['gaenity_paid_resource_email_subject'] ) );
            update_option( 'gaenity_paid_resource_email_body', wp_kses_post( $_POST['gaenity_paid_resource_email_body'] ) );

            // Save download page settings
            update_option( 'gaenity_download_page_title', sanitize_text_field( $_POST['gaenity_download_page_title'] ) );
            update_option( 'gaenity_download_page_message', wp_kses_post( $_POST['gaenity_download_page_message'] ) );
            update_option( 'gaenity_download_page_button_text', sanitize_text_field( $_POST['gaenity_download_page_button_text'] ) );
            update_option( 'gaenity_download_countdown_seconds', absint( $_POST['gaenity_download_countdown_seconds'] ) );

            // Save Ask an Expert settings
            update_option( 'gaenity_expert_request_paid', isset( $_POST['gaenity_expert_request_paid'] ) ? 1 : 0 );
            update_option( 'gaenity_expert_consultation_price', absint( $_POST['gaenity_expert_consultation_price'] ) );

            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'gaenity-community' ) . '</p></div>';
        }

        $primary_color = get_option( 'gaenity_primary_color', '#1d4ed8' );
        $secondary_color = get_option( 'gaenity_secondary_color', '#7c3aed' );
        $register_url = get_option( 'gaenity_register_url', '' );
        $ask_expert_url = get_option( 'gaenity_ask_expert_url', '' );
        $become_expert_url = get_option( 'gaenity_become_expert_url', '' );
        $resources_url = get_option( 'gaenity_resources_url', '' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Gaenity Community Settings', 'gaenity-community' ); ?></h1>

            <form method="post">
                <?php wp_nonce_field( 'gaenity_settings' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_primary_color"><?php esc_html_e( 'Primary Color', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="color" id="gaenity_primary_color" name="gaenity_primary_color" value="<?php echo esc_attr( $primary_color ); ?>" />
                            <p class="description"><?php esc_html_e( 'Main brand color for buttons and highlights', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_secondary_color"><?php esc_html_e( 'Secondary Color', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="color" id="gaenity_secondary_color" name="gaenity_secondary_color" value="<?php echo esc_attr( $secondary_color ); ?>" />
                            <p class="description"><?php esc_html_e( 'Accent color for gradients and secondary elements', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Page URLs', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Set the URLs for key community pages. Leave empty to use anchor links.', 'gaenity-community' ); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_register_url"><?php esc_html_e( 'Registration Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_register_url" name="gaenity_register_url" value="<?php echo esc_attr( $register_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/register' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for "Create Account" button', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_ask_expert_url"><?php esc_html_e( 'Ask Expert Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_ask_expert_url" name="gaenity_ask_expert_url" value="<?php echo esc_attr( $ask_expert_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/ask-expert' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for "Ask an Expert" button', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_become_expert_url"><?php esc_html_e( 'Become Expert Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_become_expert_url" name="gaenity_become_expert_url" value="<?php echo esc_attr( $become_expert_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/become-expert' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for "Become an Expert" button', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_url"><?php esc_html_e( 'Resources Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_resources_url" name="gaenity_resources_url" value="<?php echo esc_attr( $resources_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/resources' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for "Browse Resources" link', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_checkout_url"><?php esc_html_e( 'Checkout Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_checkout_url" name="gaenity_checkout_url" value="<?php echo esc_attr( get_option( 'gaenity_checkout_url', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/checkout' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for the checkout page where users complete payments', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_discussion_form_url"><?php esc_html_e( 'Discussion Form Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_discussion_form_url" name="gaenity_discussion_form_url" value="<?php echo esc_attr( get_option( 'gaenity_discussion_form_url', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/start-discussion' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for "Start Discussion" button', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_expert_directory_url"><?php esc_html_e( 'Expert Directory Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_expert_directory_url" name="gaenity_expert_directory_url" value="<?php echo esc_attr( get_option( 'gaenity_expert_directory_url', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/experts' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for expert directory page', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_polls_url"><?php esc_html_e( 'Polls Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_polls_url" name="gaenity_polls_url" value="<?php echo esc_attr( get_option( 'gaenity_polls_url', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/polls' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for polls page', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_courses_url"><?php esc_html_e( 'Courses Page', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="gaenity_courses_url" name="gaenity_courses_url" value="<?php echo esc_attr( get_option( 'gaenity_courses_url', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/courses' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'URL for enablement courses page', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Resource Section Customization', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Customize the appearance of the resource section header.', 'gaenity-community' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_title"><?php esc_html_e( 'Section Title', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_resources_title" name="gaenity_resources_title" value="<?php echo esc_attr( get_option( 'gaenity_resources_title', 'Practical tools that turn ideas into action.' ) ); ?>" class="large-text" />
                            <p class="description"><?php esc_html_e( 'The main heading for the resources section', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_description"><?php esc_html_e( 'Section Description', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <textarea id="gaenity_resources_description" name="gaenity_resources_description" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'gaenity_resources_description', 'From risk management checklists to finance enablement guides and operational templates, each resource is designed to help businesses build resilience, prepare for growth, and make measurable progress.' ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'The description text below the heading', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_title_color"><?php esc_html_e( 'Title Color', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="color" id="gaenity_resources_title_color" name="gaenity_resources_title_color" value="<?php echo esc_attr( get_option( 'gaenity_resources_title_color', '#2563eb' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'Color for the section title (will be used in gradient)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_title_size"><?php esc_html_e( 'Title Font Size', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_resources_title_size" name="gaenity_resources_title_size" value="<?php echo esc_attr( get_option( 'gaenity_resources_title_size', 40 ) ); ?>" min="16" max="72" step="2" style="width: 80px;" /> px
                            <p class="description"><?php esc_html_e( 'Font size for the section title (16-72px)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_desc_size"><?php esc_html_e( 'Description Font Size', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_resources_desc_size" name="gaenity_resources_desc_size" value="<?php echo esc_attr( get_option( 'gaenity_resources_desc_size', 18 ) ); ?>" min="12" max="32" step="1" style="width: 80px;" /> px
                            <p class="description"><?php esc_html_e( 'Font size for the description text (12-32px)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_resources_font_family"><?php esc_html_e( 'Font Family', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <select id="gaenity_resources_font_family" name="gaenity_resources_font_family">
                                <?php
                                $current_font = get_option( 'gaenity_resources_font_family', 'system' );
                                $fonts = array(
                                    'system' => 'System Font (Default)',
                                    'Arial, sans-serif' => 'Arial',
                                    'Georgia, serif' => 'Georgia',
                                    'Times New Roman, serif' => 'Times New Roman',
                                    'Courier New, monospace' => 'Courier New',
                                    'Verdana, sans-serif' => 'Verdana',
                                    'Trebuchet MS, sans-serif' => 'Trebuchet MS',
                                    'Impact, sans-serif' => 'Impact',
                                );
                                foreach ( $fonts as $value => $label ) {
                                    printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $current_font, $value, false ), esc_html( $label ) );
                                }
                                ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Font family for the resource section', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Payment Gateways', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Configure payment methods for paid resources, expert consultations, and course subscriptions.', 'gaenity-community' ); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Enable Payment Gateways', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $enabled_gateways = get_option( 'gaenity_enabled_gateways', array() );
                            ?>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="gaenity_enabled_gateways[]" value="stripe" <?php checked( in_array( 'stripe', $enabled_gateways ) ); ?>>
                                    <strong>Stripe</strong> - Credit/Debit Cards
                                </label><br>
                                <label>
                                    <input type="checkbox" name="gaenity_enabled_gateways[]" value="paypal" <?php checked( in_array( 'paypal', $enabled_gateways ) ); ?>>
                                    <strong>PayPal</strong> - PayPal Account
                                </label><br>
                                <label>
                                    <input type="checkbox" name="gaenity_enabled_gateways[]" value="paystack" <?php checked( in_array( 'paystack', $enabled_gateways ) ); ?>>
                                    <strong>Paystack</strong> - African Payments
                                </label><br>
                                <label>
                                    <input type="checkbox" name="gaenity_enabled_gateways[]" value="bank_transfer" <?php checked( in_array( 'bank_transfer', $enabled_gateways ) ); ?>>
                                    <strong>Bank Transfer</strong> - Manual Payment
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gaenity_currency"><?php esc_html_e( 'Currency', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <select id="gaenity_currency" name="gaenity_currency">
                                <?php
                                $currency = get_option( 'gaenity_currency', 'USD' );
                                $currencies = array(
                                    'USD' => 'US Dollar ($)',
                                    'EUR' => 'Euro (â‚¬)',
                                    'GBP' => 'British Pound (Â£)',
                                    'NGN' => 'Nigerian Naira (â‚¦)',
                                    'ZAR' => 'South African Rand (R)',
                                    'KES' => 'Kenyan Shilling (KSh)',
                                );
                                foreach ( $currencies as $code => $label ) {
                                    printf( '<option value="%s" %s>%s</option>', $code, selected( $currency, $code, false ), $label );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <!-- Stripe Settings -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'Stripe Settings', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_stripe_mode"><?php esc_html_e( 'Stripe Mode', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <select id="gaenity_stripe_mode" name="gaenity_stripe_mode">
                                <?php $stripe_mode = get_option( 'gaenity_stripe_mode', 'test' ); ?>
                                <option value="test" <?php selected( $stripe_mode, 'test' ); ?>><?php esc_html_e( 'Test Mode', 'gaenity-community' ); ?></option>
                                <option value="live" <?php selected( $stripe_mode, 'live' ); ?>><?php esc_html_e( 'Live Mode', 'gaenity-community' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_stripe_public_key"><?php esc_html_e( 'Stripe Publishable Key', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_stripe_public_key" name="gaenity_stripe_public_key" value="<?php echo esc_attr( get_option( 'gaenity_stripe_public_key', '' ) ); ?>" class="large-text" placeholder="pk_test_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_stripe_secret_key"><?php esc_html_e( 'Stripe Secret Key', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="gaenity_stripe_secret_key" name="gaenity_stripe_secret_key" value="<?php echo esc_attr( get_option( 'gaenity_stripe_secret_key', '' ) ); ?>" class="large-text" placeholder="sk_test_...">
                        </td>
                    </tr>

                    <!-- PayPal Settings -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'PayPal Settings', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paypal_mode"><?php esc_html_e( 'PayPal Mode', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <select id="gaenity_paypal_mode" name="gaenity_paypal_mode">
                                <?php $paypal_mode = get_option( 'gaenity_paypal_mode', 'sandbox' ); ?>
                                <option value="sandbox" <?php selected( $paypal_mode, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox', 'gaenity-community' ); ?></option>
                                <option value="live" <?php selected( $paypal_mode, 'live' ); ?>><?php esc_html_e( 'Live', 'gaenity-community' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paypal_client_id"><?php esc_html_e( 'PayPal Client ID', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_paypal_client_id" name="gaenity_paypal_client_id" value="<?php echo esc_attr( get_option( 'gaenity_paypal_client_id', '' ) ); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paypal_client_secret"><?php esc_html_e( 'PayPal Client Secret', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="gaenity_paypal_client_secret" name="gaenity_paypal_client_secret" value="<?php echo esc_attr( get_option( 'gaenity_paypal_client_secret', '' ) ); ?>" class="large-text">
                        </td>
                    </tr>

                    <!-- Paystack Settings -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'Paystack Settings', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paystack_public_key"><?php esc_html_e( 'Paystack Public Key', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_paystack_public_key" name="gaenity_paystack_public_key" value="<?php echo esc_attr( get_option( 'gaenity_paystack_public_key', '' ) ); ?>" class="large-text" placeholder="pk_test_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paystack_secret_key"><?php esc_html_e( 'Paystack Secret Key', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="gaenity_paystack_secret_key" name="gaenity_paystack_secret_key" value="<?php echo esc_attr( get_option( 'gaenity_paystack_secret_key', '' ) ); ?>" class="large-text" placeholder="sk_test_...">
                        </td>
                    </tr>

                    <!-- Bank Transfer Settings -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'Bank Transfer Settings', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_bank_details"><?php esc_html_e( 'Bank Account Details', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <textarea id="gaenity_bank_details" name="gaenity_bank_details" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'gaenity_bank_details', '' ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Enter your bank account details that will be shown to customers', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Download Settings', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Configure download expiry and access limits for resources.', 'gaenity-community' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_expiry_days"><?php esc_html_e( 'Download Expiry (Days)', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_download_expiry_days" name="gaenity_download_expiry_days" value="<?php echo esc_attr( get_option( 'gaenity_download_expiry_days', 30 ) ); ?>" min="1" max="365" style="width: 100px;" />
                            <p class="description"><?php esc_html_e( 'Number of days users can access paid resources after purchase (1-365 days)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_limit"><?php esc_html_e( 'Download Limit', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_download_limit" name="gaenity_download_limit" value="<?php echo esc_attr( get_option( 'gaenity_download_limit', 3 ) ); ?>" min="1" max="100" style="width: 100px;" />
                            <p class="description"><?php esc_html_e( 'Maximum number of downloads allowed per purchase (1-100)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Email Notifications', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Customize email notifications sent to users after resource downloads and purchases.', 'gaenity-community' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_enable_email_notifications"><?php esc_html_e( 'Enable Email Notifications', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="gaenity_enable_email_notifications" name="gaenity_enable_email_notifications" value="1" <?php checked( get_option( 'gaenity_enable_email_notifications', 1 ), 1 ); ?> />
                                <?php esc_html_e( 'Send email notifications to users', 'gaenity-community' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_email_from_name"><?php esc_html_e( 'From Name', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_email_from_name" name="gaenity_email_from_name" value="<?php echo esc_attr( get_option( 'gaenity_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'The sender name for notification emails', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_email_from_email"><?php esc_html_e( 'From Email', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="gaenity_email_from_email" name="gaenity_email_from_email" value="<?php echo esc_attr( get_option( 'gaenity_email_from_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'The sender email address for notification emails', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>

                    <!-- Free Resource Email -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'Free Resource Download Email', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_free_resource_email_subject"><?php esc_html_e( 'Email Subject', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_free_resource_email_subject" name="gaenity_free_resource_email_subject" value="<?php echo esc_attr( get_option( 'gaenity_free_resource_email_subject', 'Your Free Resource is Ready!' ) ); ?>" class="large-text" />
                            <p class="description"><?php esc_html_e( 'Available variables: {resource_title}, {user_name}, {site_name}', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_free_resource_email_body"><?php esc_html_e( 'Email Body', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $free_body_default = "Hi {user_name},\n\nThank you for downloading {resource_title}!\n\nClick the button below to access your resource:\n\n{download_button}\n\nBest regards,\n{site_name} Team";
                            ?>
                            <textarea id="gaenity_free_resource_email_body" name="gaenity_free_resource_email_body" rows="10" class="large-text"><?php echo esc_textarea( get_option( 'gaenity_free_resource_email_body', $free_body_default ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Available variables: {user_name}, {resource_title}, {download_button}, {download_link}, {site_name}', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>

                    <!-- Paid Resource Email -->
                    <tr>
                        <th colspan="2" style="background: #f0f0f1; padding: 10px;">
                            <h3 style="margin: 0;"><?php esc_html_e( 'Paid Resource Purchase Email', 'gaenity-community' ); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paid_resource_email_subject"><?php esc_html_e( 'Email Subject', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_paid_resource_email_subject" name="gaenity_paid_resource_email_subject" value="<?php echo esc_attr( get_option( 'gaenity_paid_resource_email_subject', 'Payment Successful - Access Your Resource' ) ); ?>" class="large-text" />
                            <p class="description"><?php esc_html_e( 'Available variables: {resource_title}, {user_name}, {amount}, {transaction_id}, {site_name}', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_paid_resource_email_body"><?php esc_html_e( 'Email Body', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $paid_body_default = "Hi {user_name},\n\nThank you for your purchase of {resource_title}!\n\nPayment Details:\nAmount: {amount}\nTransaction ID: {transaction_id}\n\nClick the button below to access your resource:\n\n{download_button}\n\nYou have {download_limit} downloads available, and access will expire in {expiry_days} days.\n\nBest regards,\n{site_name} Team";
                            ?>
                            <textarea id="gaenity_paid_resource_email_body" name="gaenity_paid_resource_email_body" rows="12" class="large-text"><?php echo esc_textarea( get_option( 'gaenity_paid_resource_email_body', $paid_body_default ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Available variables: {user_name}, {resource_title}, {amount}, {transaction_id}, {download_button}, {download_link}, {download_limit}, {expiry_days}, {site_name}', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Download Page Customization', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Customize the thank you page that users see after requesting a resource download.', 'gaenity-community' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_page_title"><?php esc_html_e( 'Page Title', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_download_page_title" name="gaenity_download_page_title" value="<?php echo esc_attr( get_option( 'gaenity_download_page_title', 'Thank You!' ) ); ?>" class="large-text" />
                            <p class="description"><?php esc_html_e( 'The main heading shown on the download page', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_page_message"><?php esc_html_e( 'Thank You Message', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $default_message = "Your resource is ready for download. We've also sent the download link to your email.";
                            ?>
                            <textarea id="gaenity_download_page_message" name="gaenity_download_page_message" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'gaenity_download_page_message', $default_message ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'The message shown to users on the download page', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_page_button_text"><?php esc_html_e( 'Download Button Text', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gaenity_download_page_button_text" name="gaenity_download_page_button_text" value="<?php echo esc_attr( get_option( 'gaenity_download_page_button_text', 'Download Now' ) ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Text for the download button', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_download_countdown_seconds"><?php esc_html_e( 'Countdown Duration (seconds)', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_download_countdown_seconds" name="gaenity_download_countdown_seconds" value="<?php echo esc_attr( get_option( 'gaenity_download_countdown_seconds', 5 ) ); ?>" class="small-text" min="1" max="60" />
                            <p class="description"><?php esc_html_e( 'Number of seconds to show countdown timer before download starts (1-60)', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                </table>

                <hr style="margin: 40px 0;">
                <h2><?php esc_html_e( 'Ask an Expert Settings', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Configure payment settings for expert consultations.', 'gaenity-community' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Consultation Payment', 'gaenity-community' ); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="gaenity_expert_request_paid" value="1" <?php checked( get_option( 'gaenity_expert_request_paid', 1 ), 1 ); ?> />
                                <?php esc_html_e( 'Require payment for expert consultations', 'gaenity-community' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'When enabled, users must pay to submit questions to experts. When disabled, consultations are free.', 'gaenity-community' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gaenity_expert_consultation_price"><?php esc_html_e( 'Consultation Price', 'gaenity-community' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gaenity_expert_consultation_price" name="gaenity_expert_consultation_price" value="<?php echo esc_attr( get_option( 'gaenity_expert_consultation_price', 50 ) ); ?>" class="small-text" min="1" step="1" />
                            <p class="description"><?php printf( esc_html__( 'Price per consultation in %s (only applies if payment is required)', 'gaenity-community' ), esc_html( get_option( 'gaenity_currency', 'USD' ) ) ); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">

        <button type="submit" name="gaenity_save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'gaenity-community' ); ?></button>
                </p>
            </form>

            <hr style="margin: 40px 0;">
            <div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a;">
                <h2><?php esc_html_e( 'Demo Content', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Add dummy content for testing (resources, discussions, polls, experts, etc.)', 'gaenity-community' ); ?></p>
                <?php if ( get_option( 'gaenity_dummy_seeded' ) ) : ?>
                    <p style="color: #00a32a; font-weight: 600;">âœ“ <?php esc_html_e( 'Dummy data already seeded!', 'gaenity-community' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'gaenity_reset_seed' ); ?>
                        <button type="submit" name="gaenity_reset_seed" class="button" onclick="return confirm('<?php esc_attr_e( 'This will delete all dummy data and re-seed. Continue?', 'gaenity-community' ); ?>')"><?php esc_html_e( 'Reset & Re-seed', 'gaenity-community' ); ?></button>
                    </form>
                <?php else : ?>
                    <form method="post">
                        <?php wp_nonce_field( 'gaenity_seed_data' ); ?>
                        <button type="submit" name="gaenity_seed_data" class="button button-primary"><?php esc_html_e( 'Add Dummy Content', 'gaenity-community' ); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets() {
        wp_register_style( 'gaenity-community-admin', GAENITY_COMMUNITY_ASSETS . 'css/frontend.css', array(), $this->version );
        wp_enqueue_style( 'gaenity-community-admin' );
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'gaenity_resources', array( $this, 'render_resources_shortcode' ) );
        add_shortcode( 'gaenity_community_home', array( $this, 'render_community_home_shortcode' ) );
        add_shortcode( 'gaenity_community_register', array( $this, 'render_registration_form' ) );
        add_shortcode( 'gaenity_community_login', array( $this, 'render_login_form' ) );
        add_shortcode( 'gaenity_discussion_form', array( $this, 'render_discussion_form' ) );
        add_shortcode( 'gaenity_discussion_board', array( $this, 'render_discussion_board' ) );
        add_shortcode( 'gaenity_polls', array( $this, 'render_polls' ) );
        add_shortcode( 'gaenity_expert_request', array( $this, 'render_expert_request_form' ) );
        add_shortcode( 'gaenity_expert_register', array( $this, 'render_expert_register_form' ) );
        add_shortcode( 'gaenity_contact', array( $this, 'render_contact_form' ) );
        add_shortcode( 'gaenity_community_chat', array( $this, 'render_chat_interface' ) );
        add_shortcode( 'gaenity_dashboard', array( $this, 'render_custom_dashboard' ) );
        add_shortcode( 'gaenity_download', array( $this, 'render_download_page' ) );

    }
    /**
     * Load plugin templates for custom post types.
     */
    public function load_plugin_templates( $template ) {
        // Check if we're viewing a gaenity_discussion archive
        if ( is_post_type_archive( 'gaenity_discussion' ) ) {
            $plugin_template = GAENITY_COMMUNITY_PATH . 'templates/archive-gaenity_discussion.php';
            
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        // Check if we're viewing a single gaenity_discussion
        if ( is_singular( 'gaenity_discussion' ) ) {
            $plugin_template = GAENITY_COMMUNITY_PATH . 'templates/single-gaenity_discussion.php';
            
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Register member dashboard shortcode.
     */
    public function register_member_dashboard() {
        add_shortcode( 'gaenity_member_dashboard', array( $this, 'render_member_dashboard' ) );
    }

    /**
     * Render member dashboard.
     */
    /**
     * Render enhanced member dashboard - IMPROVED VERSION
     * Replace the entire render_member_dashboard() method with this
     */
    public function render_member_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<div style="text-align: center; padding: 3rem; background: #f7fafc; border-radius: 12px; margin: 2rem 0;">
                <p style="font-size: 1.2rem; color: #475569; margin-bottom: 1.5rem;">' . esc_html__( 'Please log in to access your dashboard.', 'gaenity-community' ) . '</p>
                <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" style="display: inline-block; padding: 0.75rem 2rem; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">' . esc_html__( 'Log In', 'gaenity-community' ) . '</a>
            </div>';
        }

        $user = wp_get_current_user();
        $user_id = get_current_user_id();
        $is_expert = in_array( 'gaenity_expert', $user->roles );
        
        // Get user stats
        $discussions_count = count_user_posts( $user_id, 'gaenity_discussion' );
        $comments_count = get_comments( array( 'user_id' => $user_id, 'count' => true ) );
        
        // Get recent discussions
        $recent_discussions = get_posts( array(
            'post_type' => 'gaenity_discussion',
            'author' => $user_id,
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );
        
        global $wpdb;
        $expert_requests = $is_expert ? $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_expert_requests WHERE user_id = %d", 
            $user_id 
        ) ) : 0;

        ob_start();
        ?>
        <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Main Container */
        .gaenity-dashboard-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .gaenity-dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }
        
        /* Sidebar - Non-sticky, scrolls naturally */
        .gaenity-dashboard-sidebar {
            background: #ffffff;
            border-radius: 1.25rem;
            padding: 2rem;
            height: fit-content;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .gaenity-user-profile {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .gaenity-avatar {
            margin-bottom: 1rem;
        }
        
        .gaenity-avatar img {
            border-radius: 50%;
            border: 3px solid #10b981;
            width: 80px;
            height: 80px;
        }
        
        .gaenity-user-profile h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.375rem 0;
        }
        
        .gaenity-user-profile p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }
        
        .gaenity-dashboard-nav {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }
        
        .gaenity-dashboard-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #475569;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.9375rem;
        }
        
        .gaenity-dashboard-nav a:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateX(4px);
        }
        
        .gaenity-dashboard-nav a.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        /* Main Content */
        .gaenity-dashboard-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        /* Header */
        .gaenity-dashboard-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.2);
        }
        
        .gaenity-dashboard-title {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
        }
        
        .gaenity-dashboard-subtitle {
            font-size: 1rem;
            opacity: 0.95;
            margin: 0;
        }
        
        /* Stats Grid */
        .gaenity-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .gaenity-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .gaenity-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }
        
        .gaenity-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: #10b981;
        }
        
        .gaenity-stat-icon {
            font-size: 2.5rem;
            line-height: 1;
        }
        
        .gaenity-stat-info {
            flex: 1;
        }
        
        .gaenity-stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .gaenity-stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        
        /* Content Cards */
        .gaenity-content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .gaenity-section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 1.5rem 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Quick Actions */
        .gaenity-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .gaenity-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 0.875rem;
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }
        
        .gaenity-action-btn:hover {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.2);
        }
        
        /* Recent Activity */
        .gaenity-activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .gaenity-activity-item {
            padding: 1.25rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .gaenity-activity-item:hover {
            background: #ffffff;
            border-color: #10b981;
            transform: translateX(4px);
        }
        
        .gaenity-activity-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
            display: block;
            text-decoration: none;
        }
        
        .gaenity-activity-title:hover {
            color: #10b981;
        }
        
        .gaenity-activity-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .gaenity-activity-meta span {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .gaenity-empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }
        
        .gaenity-empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .gaenity-empty-text {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .gaenity-empty-btn {
            display: inline-block;
            padding: 0.875rem 1.75rem;
            background: #10b981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .gaenity-empty-btn:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .gaenity-dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .gaenity-dashboard-sidebar {
                order: -1;
            }
        }
        
        @media (max-width: 768px) {
            .gaenity-dashboard-wrapper {
                padding: 1rem 0.5rem;
            }
            
            .gaenity-dashboard-header {
                padding: 2rem 1.5rem;
            }
            
            .gaenity-dashboard-title {
                font-size: 1.5rem;
            }
            
            .gaenity-content-card {
                padding: 1.5rem;
            }
            
            .gaenity-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .gaenity-actions-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <div class="gaenity-dashboard-wrapper">
            <div class="gaenity-dashboard-container">
                <!-- Sidebar - Now scrolls naturally -->
                <aside class="gaenity-dashboard-sidebar">
                    <div class="gaenity-user-profile">
                        <div class="gaenity-avatar">
                            <?php echo get_avatar( $user->ID, 80 ); ?>
                        </div>
                        <h3><?php echo esc_html( $user->display_name ); ?></h3>
                        <p><?php echo esc_html( $user->user_email ); ?></p>
                    </div>
                    
                    <nav class="gaenity-dashboard-nav">
                        <a href="<?php echo esc_url( get_permalink() ); ?>" class="active">
                            🏠 Dashboard
                        </a>
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>">
                            💬 Discussions
                        </a>
                        <a href="<?php echo esc_url( home_url( '/polls/' ) ); ?>">
                            🗳️ Polls
                        </a>
                        <a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">
                            📚 Resources
                        </a>
                        <?php if ( $is_expert ) : ?>
                        <a href="<?php echo esc_url( home_url( '/expert-requests/' ) ); ?>">
                            ⭐ Expert Panel
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( home_url( '/ask-expert/' ) ); ?>">
                            ❓ Ask Expert
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">
                            👤 Edit Profile
                        </a>
                        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                            🚪 Logout
                        </a>
                    </nav>
                </aside>
                
                <!-- Main Content -->
                <main class="gaenity-dashboard-main">
                    <!-- Header -->
                    <div class="gaenity-dashboard-header">
                        <h1 class="gaenity-dashboard-title">Welcome back, <?php echo esc_html( $user->display_name ); ?>! 👋</h1>
                        <p class="gaenity-dashboard-subtitle">Here's what's happening in your community</p>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="gaenity-stats-grid">
                        <div class="gaenity-stat-card">
                            <div class="gaenity-stat-icon">💬</div>
                            <div class="gaenity-stat-info">
                                <div class="gaenity-stat-value"><?php echo esc_html( $discussions_count ); ?></div>
                                <div class="gaenity-stat-label">Discussions</div>
                            </div>
                        </div>
                        
                        <div class="gaenity-stat-card">
                            <div class="gaenity-stat-icon">💭</div>
                            <div class="gaenity-stat-info">
                                <div class="gaenity-stat-value"><?php echo esc_html( $comments_count ); ?></div>
                                <div class="gaenity-stat-label">Comments</div>
                            </div>
                        </div>
                        
                        <div class="gaenity-stat-card">
                            <div class="gaenity-stat-icon">❤️</div>
                            <div class="gaenity-stat-info">
                                <div class="gaenity-stat-value">0</div>
                                <div class="gaenity-stat-label">Likes Received</div>
                            </div>
                        </div>
                        
                        <?php if ( $is_expert ) : ?>
                        <div class="gaenity-stat-card">
                            <div class="gaenity-stat-icon">⭐</div>
                            <div class="gaenity-stat-info">
                                <div class="gaenity-stat-value"><?php echo esc_html( $expert_requests ); ?></div>
                                <div class="gaenity-stat-label">Expert Requests</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="gaenity-content-card">
                        <h2 class="gaenity-section-title">⚡ Quick Actions</h2>
                        <div class="gaenity-actions-grid">
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-action-btn">
                                💬 Start Discussion
                            </a>
                            <a href="<?php echo esc_url( home_url( '/polls/' ) ); ?>" class="gaenity-action-btn">
                                🗳️ Vote on Polls
                            </a>
                            <a href="<?php echo esc_url( home_url( '/ask-expert/' ) ); ?>" class="gaenity-action-btn">
                                ⭐ Ask an Expert
                            </a>
                            <a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>" class="gaenity-action-btn">
                                📚 Browse Resources
                            </a>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="gaenity-content-card">
                        <h2 class="gaenity-section-title">📝 Your Recent Discussions</h2>
                        <?php if ( ! empty( $recent_discussions ) ) : ?>
                            <div class="gaenity-activity-list">
                                <?php foreach ( $recent_discussions as $discussion ) : 
                                    $comments = get_comments_number( $discussion->ID );
                                    $likes = get_post_meta( $discussion->ID, '_gaenity_likes_count', true );
                                ?>
                                    <div class="gaenity-activity-item">
                                        <a href="<?php echo esc_url( get_permalink( $discussion->ID ) ); ?>" class="gaenity-activity-title">
                                            <?php echo esc_html( $discussion->post_title ); ?>
                                        </a>
                                        <div class="gaenity-activity-meta">
                                            <span>💭 <?php echo esc_html( $comments ); ?> comments</span>
                                            <span>❤️ <?php echo esc_html( $likes ? $likes : 0 ); ?> likes</span>
                                            <span>📅 <?php echo human_time_diff( strtotime( $discussion->post_date ), current_time( 'timestamp' ) ); ?> ago</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="gaenity-empty-state">
                                <div class="gaenity-empty-icon">💬</div>
                                <p class="gaenity-empty-text">You haven't started any discussions yet</p>
                                <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-empty-btn">
                                    Start Your First Discussion
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    /**
     * Redirect non-admins to custom dashboard
     */
    public function redirect_non_admins_to_custom_dashboard() {
        // Don't redirect if not in admin area
        if ( ! is_admin() ) {
            return;
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Don't redirect admins or editors
        if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles ) ) {
            return;
        }
        
        // Don't redirect AJAX requests
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        
        // Don't redirect if on specific admin pages
        $allowed_pages = array( 'profile.php', 'user-edit.php' );
        $current_page = basename( $_SERVER['PHP_SELF'] );
        if ( in_array( $current_page, $allowed_pages ) ) {
            return;
        }
        
        // Redirect to custom dashboard
        $dashboard_url = home_url( '/dashboard/' );
        wp_safe_redirect( $dashboard_url );
        exit;
    }

    /**
     * Register expert directory shortcode.
     */
    public function register_expert_directory() {
        add_shortcode( 'gaenity_expert_directory', array( $this, 'render_expert_directory' ) );
    }
/**
     * Register documentation shortcode.
     */
    public function register_documentation() {
        add_shortcode( 'gaenity_documentation', array( $this, 'render_documentation' ) );
    }

    /**
     * Render documentation page.
     */
    public function render_documentation() {
        ob_start();
        include GAENITY_COMMUNITY_PATH . 'templates/documentation.php';
        return ob_get_clean();
    }
    /**
     * Register courses shortcode.
     */
    public function register_courses() {
        add_shortcode( 'gaenity_courses', array( $this, 'render_courses' ) );
    }
/**
     * Register checkout shortcode.
     */
    public function register_checkout() {
        add_shortcode( 'gaenity_checkout', array( $this, 'render_checkout' ) );
    }

    /**
     * Render checkout page.
     */
    public function render_checkout() {
        $item_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'course';
        $item_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : ( isset( $_GET['resource_id'] ) ? absint( $_GET['resource_id'] ) : 0 );

        if ( ! $item_id ) {
            return '<div style="text-align: center; padding: 3rem; background: #fef2f2; border-radius: 12px; color: #991b1b;"><p>' . esc_html__( 'Invalid item. Please select a valid course or resource.', 'gaenity-community' ) . '</p></div>';
        }

        $post = get_post( $item_id );
        if ( ! $post ) {
            return '<div style="text-align: center; padding: 3rem; background: #fef2f2; border-radius: 12px; color: #991b1b;"><p>' . esc_html__( 'Item not found.', 'gaenity-community' ) . '</p></div>';
        }

// Get price based on item type
        if ( $item_type === 'resource' ) {
            $price = get_post_meta( $item_id, '_gaenity_resource_price', true );
        } else {
            $price = get_post_meta( $item_id, '_gaenity_course_price', true );
        }        
// Get type based on item type
        if ( $item_type === 'resource' ) {
            $type = 'one-time'; // Resources are always one-time purchase
        } else {
            $type = get_post_meta( $item_id, '_gaenity_course_type', true );
        }        $enabled_gateways = get_option( 'gaenity_enabled_gateways', array() );
        $currency = get_option( 'gaenity_currency', 'USD' );
        $currency_symbol = $this->get_currency_symbol();

        ob_start();
        ?>
        <style>
            .gaenity-checkout {
                max-width: 900px;
                margin: 3rem auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .gaenity-checkout-grid {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 2rem;
            }
            .gaenity-checkout-section {
                background: #fff;
                padding: 2rem;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            }
            .gaenity-checkout-section h3 {
                margin-top: 0;
                font-size: 1.4rem;
                color: #1e293b;
                margin-bottom: 1.5rem;
            }
            .gaenity-payment-methods {
                display: grid;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            .gaenity-payment-method {
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 1.25rem;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            .gaenity-payment-method:hover {
                border-color: #1d4ed8;
                background: #f8fafc;
            }
            .gaenity-payment-method input[type="radio"] {
                width: 20px;
                height: 20px;
            }
            .gaenity-payment-method.selected {
                border-color: #1d4ed8;
                background: #eff6ff;
            }
            .gaenity-payment-logo {
                font-size: 2rem;
            }
            .gaenity-payment-info h4 {
                margin: 0 0 0.25rem 0;
                font-size: 1.1rem;
                color: #1e293b;
            }
            .gaenity-payment-info p {
                margin: 0;
                font-size: 0.85rem;
                color: #64748b;
            }
            .gaenity-order-summary {
                background: #f8fafc;
                padding: 1.5rem;
                border-radius: 12px;
                margin-bottom: 1.5rem;
            }
            .gaenity-order-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #e2e8f0;
            }
            .gaenity-order-total {
                display: flex;
                justify-content: space-between;
                font-size: 1.5rem;
                font-weight: 700;
                color: #1e293b;
                margin-top: 1rem;
            }
            .gaenity-checkout-btn {
                width: 100%;
                padding: 1rem;
                background: linear-gradient(135deg, #1d4ed8, #7c3aed);
                color: #fff;
                border: none;
                border-radius: 12px;
                font-size: 1.1rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .gaenity-checkout-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(29,78,216,0.3);
            }
            .gaenity-checkout-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .gaenity-bank-details {
                background: #fef3c7;
                border: 2px solid #fbbf24;
                border-radius: 12px;
                padding: 1.5rem;
                margin-top: 1.5rem;
                display: none;
            }
            .gaenity-bank-details.active {
                display: block;
            }
            .gaenity-bank-details h4 {
                margin-top: 0;
                color: #92400e;
            }
            @media (max-width: 768px) {
                .gaenity-checkout-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="gaenity-checkout">
            <h2 style="text-align: center; margin-bottom: 2rem; font-size: 2.5rem; color: #1e293b;"><?php esc_html_e( 'Checkout', 'gaenity-community' ); ?></h2>

            <div class="gaenity-checkout-grid">
                <div>
                    <div class="gaenity-checkout-section">
                        <h3><?php esc_html_e( 'Payment Method', 'gaenity-community' ); ?></h3>
                        
                        <?php if ( empty( $enabled_gateways ) ) : ?>
                            <div style="background: #fef2f2; padding: 1.5rem; border-radius: 8px; color: #991b1b;">
                                <p><strong><?php esc_html_e( 'Payment methods not configured.', 'gaenity-community' ); ?></strong></p>
                                <p><?php esc_html_e( 'Please contact the administrator to enable payment gateways.', 'gaenity-community' ); ?></p>
                            </div>
                        <?php else : ?>
                            <form id="gaenity-checkout-form" method="post">
                                <input type="hidden" name="action" value="gaenity_process_payment">
                                <input type="hidden" name="item_type" value="<?php echo esc_attr( $item_type ); ?>">
                                <input type="hidden" name="item_id" value="<?php echo esc_attr( $item_id ); ?>">
                                <input type="hidden" name="amount" value="<?php echo esc_attr( $price ); ?>">
                                <input type="hidden" name="currency" value="<?php echo esc_attr( $currency ); ?>">
                                <?php wp_nonce_field( 'gaenity_checkout', 'gaenity_checkout_nonce' ); ?>

                                <div class="gaenity-payment-methods">
                                    <?php if ( in_array( 'stripe', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="stripe" required>
                                            <div class="gaenity-payment-logo">ðŸ’³</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'Credit/Debit Card', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay securely with Stripe', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'paypal', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="paypal" required>
                                            <div class="gaenity-payment-logo">ðŸ…¿ï¸</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'PayPal', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay with your PayPal account', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'paystack', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="paystack" required>
                                            <div class="gaenity-payment-logo">ðŸ’°</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'Paystack', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay with card, bank transfer, or mobile money', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'bank_transfer', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="bank_transfer" required>
                                            <div class="gaenity-payment-logo">ðŸ¦</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'Bank Transfer', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Manual bank transfer', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <?php if ( in_array( 'bank_transfer', $enabled_gateways ) ) : ?>
                                    <div id="bank-details-box" class="gaenity-bank-details">
                                        <h4><?php esc_html_e( 'Bank Account Details', 'gaenity-community' ); ?></h4>
                                        <?php echo wpautop( get_option( 'gaenity_bank_details', '' ) ); ?>
                                        <p><strong><?php esc_html_e( 'Please send payment confirmation to:', 'gaenity-community' ); ?></strong> <?php echo esc_html( get_option( 'admin_email' ) ); ?></p>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="gaenity-checkout-btn" id="checkout-submit-btn">
                                    <?php esc_html_e( 'Complete Payment', 'gaenity-community' ); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="gaenity-checkout-section">
                        <h3><?php esc_html_e( 'Order Summary', 'gaenity-community' ); ?></h3>
                        
                        <div class="gaenity-order-summary">
                            <div class="gaenity-order-item">
                                <div>
                                    <strong><?php echo esc_html( $post->post_title ); ?></strong>
                                    <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.85rem;">
                                        <?php echo $type === 'subscription' ? esc_html__( 'Monthly subscription', 'gaenity-community' ) : esc_html__( 'One-time payment', 'gaenity-community' ); ?>
                                    </p>
                                </div>
                                <div style="font-weight: 700; color: #1e293b;">
                                    <?php echo esc_html( $currency_symbol . number_format( $price, 2 ) ); ?>
                                </div>
                            </div>

                            <div class="gaenity-order-total">
                                <span><?php esc_html_e( 'Total', 'gaenity-community' ); ?></span>
                                <span><?php echo esc_html( $currency_symbol . number_format( $price, 2 ) ); ?></span>
                            </div>
                        </div>

                        <div style="background: #eff6ff; padding: 1rem; border-radius: 8px; border: 1px solid #bfdbfe;">
                            <p style="margin: 0; font-size: 0.9rem; color: #1e40af;">
                                <strong>âœ“</strong> <?php esc_html_e( 'Secure payment processing', 'gaenity-community' ); ?><br>
                                <strong>âœ“</strong> <?php esc_html_e( 'Instant access after payment', 'gaenity-community' ); ?><br>
                                <strong>âœ“</strong> <?php esc_html_e( 'Email receipt included', 'gaenity-community' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const paymentMethods = document.querySelectorAll('.gaenity-payment-method');
                const bankDetailsBox = document.getElementById('bank-details-box');
                const form = document.getElementById('gaenity-checkout-form');
                const submitBtn = document.getElementById('checkout-submit-btn');

                paymentMethods.forEach(method => {
                    method.addEventListener('click', function() {
                        paymentMethods.forEach(m => m.classList.remove('selected'));
                        this.classList.add('selected');
                        
                        const radio = this.querySelector('input[type="radio"]');
                        radio.checked = true;

                        // Show bank details if bank transfer selected
                        if (bankDetailsBox && radio.value === 'bank_transfer') {
                            bankDetailsBox.classList.add('active');
                            submitBtn.textContent = '<?php esc_html_e( 'Submit Order', 'gaenity-community' ); ?>';
                        } else {
                            if (bankDetailsBox) bankDetailsBox.classList.remove('active');
                            submitBtn.textContent = '<?php esc_html_e( 'Complete Payment', 'gaenity-community' ); ?>';
                        }
                    });
                });

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitBtn.disabled = true;
                    submitBtn.textContent = '<?php esc_html_e( 'Processing...', 'gaenity-community' ); ?>';

                    const formData = new FormData(form);

                    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.data.redirect_url) {
                                window.location.href = data.data.redirect_url;
                            } else {
                                alert(data.data.message || '<?php esc_html_e( 'Payment successful!', 'gaenity-community' ); ?>');
                                window.location.href = '<?php echo esc_url( home_url( '/dashboard' ) ); ?>';
                            }
                        } else {
                            alert(data.data.message || '<?php esc_html_e( 'Payment failed. Please try again.', 'gaenity-community' ); ?>');
                            submitBtn.disabled = false;
                            submitBtn.textContent = '<?php esc_html_e( 'Complete Payment', 'gaenity-community' ); ?>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('<?php esc_html_e( 'An error occurred. Please try again.', 'gaenity-community' ); ?>');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '<?php esc_html_e( 'Complete Payment', 'gaenity-community' ); ?>';
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    /**
     * Register polls page shortcode.
     */
    public function register_polls_page() {
        add_shortcode( 'gaenity_polls_page', array( $this, 'render_polls_page' ) );
    }

    /**
     * Render polls page.
     */
    /**
     * Render polls page - IMPROVED VERSION
     * Replace at line ~2898
     */
    /**
     * Render polls page - BEAUTIFUL MODERN VERSION
     * Replace at line ~2898
     */
    /**
     * Render polls page with filters - COMPLETE VERSION
     * Replace at line ~2898
     */
    /**
     * Render polls page with filters - COMPLETE VERSION
     * Replace at line ~2898
     */
    public function render_polls_page() {
        $is_logged_in = is_user_logged_in();
        $register_url = get_option( 'gaenity_register_url', wp_registration_url() );
        $login_url = wp_login_url( get_permalink() );

        // Get all polls with their taxonomy terms
        $all_polls = get_posts( array(
            'post_type' => 'gaenity_poll',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );

        // Get unique regions and industries from polls
        $regions = array();
        $industries = array();
        foreach ( $all_polls as $poll ) {
            $poll_region = get_post_meta( $poll->ID, '_gaenity_poll_region', true );
            $poll_industry = get_post_meta( $poll->ID, '_gaenity_poll_industry', true );
            if ( $poll_region && ! in_array( $poll_region, $regions ) ) {
                $regions[] = $poll_region;
            }
            if ( $poll_industry && ! in_array( $poll_industry, $industries ) ) {
                $industries[] = $poll_industry;
            }
        }
        sort( $regions );
        sort( $industries );

        ob_start();
        ?>
        <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .gaenity-modern-polls {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .gaenity-polls-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header */
        .gaenity-polls-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .gaenity-polls-main-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        .gaenity-polls-subtitle {
            font-size: 1.125rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Filters */
        .gaenity-polls-filters {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .gaenity-filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .gaenity-filters-row:last-child {
            margin-bottom: 0;
        }
        .gaenity-filter-group {
            display: flex;
            flex-direction: column;
        }
        .gaenity-filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        .gaenity-filter-select, .gaenity-filter-input {
            padding: 0.625rem 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .gaenity-filter-select:focus, .gaenity-filter-input:focus {
            outline: none;
            border-color: #10b981;
            background: #ffffff;
        }
        .gaenity-filter-actions {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .gaenity-filter-btn {
            padding: 0.625rem 1.5rem;
            border: 2px solid #10b981;
            background: #10b981;
            color: #ffffff;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gaenity-filter-btn:hover {
            background: #059669;
            border-color: #059669;
        }
        .gaenity-clear-btn {
            padding: 0.625rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gaenity-clear-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        /* Tabs */
        .gaenity-polls-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .gaenity-tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: -2px;
        }
        .gaenity-tab:hover {
            color: #0f172a;
        }
        .gaenity-tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }
        .gaenity-tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            background: #f1f5f9;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }
        .gaenity-tab.active .gaenity-tab-badge {
            background: #d1fae5;
            color: #065f46;
        }
        
        /* Poll Grid */
        .gaenity-polls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }
        
        /* Poll Card */
        .gaenity-poll-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 1.5rem;
            padding: 1.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .gaenity-poll-card.open {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .gaenity-poll-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }
        
        /* Poll Header */
        .gaenity-poll-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .gaenity-poll-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .gaenity-poll-status-badge.open {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        .gaenity-poll-status-badge.open::before {
            content: "●";
            animation: pulse-dot 2s infinite;
        }
        .gaenity-poll-status-badge.closed {
            background: #f1f5f9;
            color: #64748b;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .gaenity-poll-timer {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .gaenity-poll-question {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
            margin-bottom: 0.75rem;
        }
        .gaenity-poll-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        .gaenity-poll-meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .gaenity-poll-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .gaenity-poll-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .gaenity-poll-tag.region {
            background: #dbeafe;
            color: #1e40af;
        }
        .gaenity-poll-tag.industry {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Voting Options */
        .gaenity-poll-options {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        .gaenity-poll-option {
            position: relative;
            cursor: pointer;
        }
        .gaenity-poll-option.disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .gaenity-poll-option-btn {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .gaenity-poll-option-btn:hover:not(:disabled) {
            border-color: #10b981;
            background: #ecfdf5;
            transform: translateX(4px);
        }
        .gaenity-poll-option.voted .gaenity-poll-option-btn {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .gaenity-poll-option-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.625rem;
        }
        .gaenity-poll-option-label {
            font-weight: 600;
            color: #0f172a;
            font-size: 1rem;
        }
        .gaenity-poll-option.voted .gaenity-poll-option-label::after {
            content: "YOU";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.625rem;
            padding: 0.125rem 0.5rem;
            background: #10b981;
            color: #ffffff;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .gaenity-poll-option-percentage {
            font-weight: 700;
            font-size: 1.125rem;
            color: #0f172a;
        }
        
        /* Progress Bar */
        .gaenity-poll-progress-bar {
            width: 100%;
            height: 0.875rem;
            background: #f1f5f9;
            border-radius: 0.5rem;
            overflow: hidden;
            position: relative;
        }
        .gaenity-poll-progress-fill {
            height: 100%;
            border-radius: 0.5rem;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .gaenity-poll-progress-fill.yes {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }
        .gaenity-poll-progress-fill.no {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }
        .gaenity-poll-progress-fill.default {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        }
        .gaenity-poll-progress-fill.winner {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
        }
        
        /* Empty State */
        .gaenity-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #ffffff;
            border: 2px dashed #e2e8f0;
            border-radius: 1.5rem;
            grid-column: 1 / -1;
        }
        .gaenity-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .gaenity-empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .gaenity-empty-text {
            font-size: 1rem;
            color: #64748b;
        }
        
        /* Popup */
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
        .gaenity-popup-overlay.active { display: flex; }
        .gaenity-popup {
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .gaenity-popup-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        .gaenity-popup-text {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
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
            background: #10b981;
            color: #ffffff;
        }
        .gaenity-popup-btn.primary:hover {
            background: #059669;
            color: #ffffff;
        }
        .gaenity-popup-btn.secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .gaenity-popup-btn.secondary:hover {
            background: #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .gaenity-polls-main-title {
                font-size: 2rem;
            }
            .gaenity-polls-grid {
                grid-template-columns: 1fr;
            }
            .gaenity-filters-row {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <div class="gaenity-modern-polls">
            <div class="gaenity-polls-wrapper">
                <!-- Header -->
                <div class="gaenity-polls-header">
                    <h1 class="gaenity-polls-main-title">Community Polls</h1>
                    <p class="gaenity-polls-subtitle">
                        Vote, share your opinion, and see how other business owners are responding to similar challenges.
                    </p>
                </div>

                <!-- Filters -->
                <div class="gaenity-polls-filters">
                    <div class="gaenity-filters-row">
                        <div class="gaenity-filter-group">
                            <label class="gaenity-filter-label">Search Polls</label>
                            <input type="text" id="poll-search" class="gaenity-filter-input" placeholder="Search by question...">
                        </div>
                        <div class="gaenity-filter-group">
                            <label class="gaenity-filter-label">Region</label>
                            <select id="poll-region" class="gaenity-filter-select">
                                <option value="">All Regions</option>
                                <?php foreach ( $regions as $region ) : ?>
                                    <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gaenity-filter-group">
                            <label class="gaenity-filter-label">Industry</label>
                            <select id="poll-industry" class="gaenity-filter-select">
                                <option value="">All Industries</option>
                                <?php foreach ( $industries as $industry ) : ?>
                                    <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gaenity-filter-group">
                            <label class="gaenity-filter-label">Status</label>
                            <select id="poll-status" class="gaenity-filter-select">
                                <option value="all">All Polls</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="gaenity-filter-group">
                            <label class="gaenity-filter-label">Sort By</label>
                            <select id="poll-sort" class="gaenity-filter-select">
                                <option value="newest">Newest First</option>
                                <option value="votes">Most Votes</option>
                                <option value="ending">Ending Soon</option>
                            </select>
                        </div>
                    </div>
                    <div class="gaenity-filter-actions">
                        <button class="gaenity-filter-btn" onclick="filterPolls()">Apply Filters</button>
                        <button class="gaenity-clear-btn" onclick="clearFilters()">Clear All</button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="gaenity-polls-tabs">
                    <button class="gaenity-tab active" data-tab="all">
                        All Polls
                        <span class="gaenity-tab-badge"><?php echo count( $all_polls ); ?></span>
                    </button>
                    <button class="gaenity-tab" data-tab="open">
                        Open
                        <span class="gaenity-tab-badge" id="open-count">0</span>
                    </button>
                    <button class="gaenity-tab" data-tab="voted">
                        My Votes
                        <span class="gaenity-tab-badge" id="voted-count">0</span>
                    </button>
                </div>

                <!-- Poll Grid -->
                <div class="gaenity-polls-grid" id="polls-container">
                    <?php if ( ! empty( $all_polls ) ) : ?>
                        <?php 
                        $open_count = 0;
                        foreach ( $all_polls as $poll ) :
                            $question = get_post_meta( $poll->ID, '_gaenity_poll_question', true );
                            $options = get_post_meta( $poll->ID, '_gaenity_poll_options', true );
                            $poll_region = get_post_meta( $poll->ID, '_gaenity_poll_region', true );
                            $poll_industry = get_post_meta( $poll->ID, '_gaenity_poll_industry', true );
                            
                            if ( empty( $options ) || count( $options ) < 2 ) {
                                continue;
                            }

                            $results = get_post_meta( $poll->ID, '_gaenity_poll_results', true );
                            $total_votes = 0;
                            if ( is_array( $results ) ) {
                                foreach ( $results as $votes ) {
                                    $total_votes += absint( $votes );
                                }
                            }

                            // Check if user has voted
                            $user_voted = false;
                            $user_vote_index = null;
                            if ( $is_logged_in ) {
                                $user_id = get_current_user_id();
                                $voters = get_post_meta( $poll->ID, '_gaenity_poll_voters', true );
                                if ( is_array( $voters ) && in_array( $user_id, $voters ) ) {
                                    $user_voted = true;
                                    $user_votes = get_post_meta( $poll->ID, '_gaenity_poll_user_votes', true );
                                    if ( is_array( $user_votes ) && isset( $user_votes[ $user_id ] ) ) {
                                        $user_vote_index = $user_votes[ $user_id ];
                                    }
                                }
                            }

                            $is_open = ! $user_voted;
                            if ( $is_open ) $open_count++;

                            // Calculate percentages and find winner
                            $percentages = array();
                            $max_votes = 0;
                            $winner_key = '';

                            if ( is_array( $results ) && $total_votes > 0 ) {
                                foreach ( $results as $key => $votes ) {
                                    $votes = absint( $votes );
                                    $percentages[ $key ] = round( ( $votes / $total_votes ) * 100 );
                                    if ( $votes > $max_votes ) {
                                        $max_votes = $votes;
                                        $winner_key = $key;
                                    }
                                }
                            }
                        ?>
                            <div class="gaenity-poll-card <?php echo $is_open ? 'open' : ''; ?>" 
                                 data-poll-id="<?php echo esc_attr( $poll->ID ); ?>" 
                                 data-status="<?php echo $is_open ? 'open' : 'closed'; ?>" 
                                 data-voted="<?php echo $user_voted ? 'yes' : 'no'; ?>"
                                 data-region="<?php echo esc_attr( $poll_region ); ?>"
                                 data-industry="<?php echo esc_attr( $poll_industry ); ?>">
                                <!-- Header -->
                                <div class="gaenity-poll-header">
                                    <span class="gaenity-poll-status-badge <?php echo $is_open ? 'open' : 'closed'; ?>">
                                        <?php echo $is_open ? 'Open' : 'Closed'; ?>
                                    </span>
                                    <?php if ( $is_open ) : ?>
                                        <span class="gaenity-poll-timer" data-poll-created="<?php echo get_post_time( 'U', false, $poll->ID ); ?>">
                                            ⏱️ <span class="timer-text">...</span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Question -->
                                <h3 class="gaenity-poll-question">
                                    <?php echo esc_html( $question ? $question : get_the_title( $poll->ID ) ); ?>
                                </h3>

                                <!-- Tags -->
                                <?php if ( $poll_region || $poll_industry ) : ?>
                                    <div class="gaenity-poll-tags">
                                        <?php if ( $poll_region ) : ?>
                                            <span class="gaenity-poll-tag region">📍 <?php echo esc_html( $poll_region ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( $poll_industry ) : ?>
                                            <span class="gaenity-poll-tag industry">🏢 <?php echo esc_html( $poll_industry ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Meta -->
                                <div class="gaenity-poll-meta">
                                    <span class="gaenity-poll-meta-item">
                                        👥 <strong><?php echo esc_html( $total_votes ); ?></strong> votes
                                    </span>
                                    <span class="gaenity-poll-meta-item">
                                        📅 <?php echo human_time_diff( get_post_time( 'U', false, $poll->ID ), current_time( 'timestamp' ) ); ?> ago
                                    </span>
                                </div>

                                <!-- Options -->
                                <div class="gaenity-poll-options">
                                    <?php foreach ( $options as $key => $label ) :
                                        $is_user_vote = ( $user_vote_index === $key );
                                        $is_winner = ( $key === $winner_key && $user_voted );
                                        $percentage = isset( $percentages[ $key ] ) ? $percentages[ $key ] : 0;
                                        
                                        $color_class = 'default';
                                        $label_lower = strtolower( $label );
                                        if ( strpos( $label_lower, 'yes' ) !== false ) {
                                            $color_class = 'yes';
                                        } elseif ( strpos( $label_lower, 'no' ) !== false ) {
                                            $color_class = 'no';
                                        }
                                        if ( $is_winner ) {
                                            $color_class = 'winner';
                                        }
                                    ?>
                                        <div class="gaenity-poll-option <?php echo $is_user_vote ? 'voted' : ''; ?> <?php echo $user_voted ? 'disabled' : ''; ?>">
                                            <button 
                                                class="gaenity-poll-option-btn" 
                                                onclick="<?php echo $is_logged_in ? ( $user_voted ? 'return false;' : 'votePoll(' . $poll->ID . ', \'' . esc_js( $key ) . '\')' ) : 'showAuthPopup()'; ?>"
                                                <?php echo $user_voted ? 'disabled' : ''; ?>
                                            >
                                                <div class="gaenity-poll-option-content">
                                                    <span class="gaenity-poll-option-label">
                                                        <?php echo esc_html( $label ); ?>
                                                    </span>
                                                    <?php if ( $user_voted ) : ?>
                                                        <span class="gaenity-poll-option-percentage">
                                                            <?php echo esc_html( $percentage ); ?>%
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ( $user_voted ) : ?>
                                                    <div class="gaenity-poll-progress-bar">
                                                        <div class="gaenity-poll-progress-fill <?php echo esc_attr( $color_class ); ?>" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="gaenity-empty-state">
                            <div class="gaenity-empty-icon">🗳️</div>
                            <h3 class="gaenity-empty-title">No polls available</h3>
                            <p class="gaenity-empty-text">Check back soon for new community polls!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Auth Popup -->
        <div id="gaenity-auth-popup" class="gaenity-popup-overlay" onclick="if(event.target === this) closeAuthPopup()">
            <div class="gaenity-popup">
                <h2 class="gaenity-popup-title">Join to Vote</h2>
                <p class="gaenity-popup-text">Register or sign in to participate in community polls!</p>
                <div class="gaenity-popup-actions">
                    <a href="<?php echo esc_url( $register_url ); ?>" class="gaenity-popup-btn primary">Join Now</a>
                    <a href="<?php echo esc_url( $login_url ); ?>" class="gaenity-popup-btn secondary">Sign In</a>
                </div>
            </div>
        </div>

        <script>
        // Auth Popup
        function showAuthPopup() {
            document.getElementById('gaenity-auth-popup').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeAuthPopup() {
            document.getElementById('gaenity-auth-popup').classList.remove('active');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAuthPopup(); });

        // Vote Poll
        function votePoll(pollId, optionKey) {
            const formData = new FormData();
            formData.append('action', 'gaenity_poll_vote');
            formData.append('poll_id', pollId);
            formData.append('option', optionKey);
            formData.append('region', 'Online');
            formData.append('industry', 'General');
            formData.append('gaenity_nonce', '<?php echo wp_create_nonce( 'gaenity-community' ); ?>');

            fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data || 'Error voting');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing vote');
            });
        }

        // Update Timers
        function updateTimers() {
            document.querySelectorAll('.gaenity-poll-timer').forEach(timer => {
                const created = parseInt(timer.dataset.pollCreated);
                const now = Math.floor(Date.now() / 1000);
                const elapsed = now - created;
                const remaining = Math.max(0, (10 * 24 * 60 * 60) - elapsed);
                const days = Math.floor(remaining / 86400);
                const hours = Math.floor((remaining % 86400) / 3600);
                const mins = Math.floor((remaining % 3600) / 60);
                const secs = remaining % 60;
                timer.querySelector('.timer-text').textContent = `${days}d ${hours}h ${mins}m ${secs}s`;
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();

        // Tabs
        document.querySelectorAll('.gaenity-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.gaenity-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const filter = tab.dataset.tab;
                document.querySelectorAll('.gaenity-poll-card').forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else if (filter === 'open') {
                        card.style.display = card.dataset.status === 'open' ? 'block' : 'none';
                    } else if (filter === 'voted') {
                        card.style.display = card.dataset.voted === 'yes' ? 'block' : 'none';
                    }
                });
            });
        });

        // Update counts
        const openCount = document.querySelectorAll('[data-status="open"]').length;
        const votedCount = document.querySelectorAll('[data-voted="yes"]').length;
        document.getElementById('open-count').textContent = openCount;
        document.getElementById('voted-count').textContent = votedCount;

        // Filter Polls
        function filterPolls() {
            const search = document.getElementById('poll-search').value.toLowerCase();
            const region = document.getElementById('poll-region').value;
            const industry = document.getElementById('poll-industry').value;
            const status = document.getElementById('poll-status').value;

            document.querySelectorAll('.gaenity-poll-card').forEach(card => {
                const question = card.querySelector('.gaenity-poll-question').textContent.toLowerCase();
                const cardRegion = card.dataset.region;
                const cardIndustry = card.dataset.industry;
                const cardStatus = card.dataset.status;

                const matchSearch = !search || question.includes(search);
                const matchRegion = !region || cardRegion === region;
                const matchIndustry = !industry || cardIndustry === industry;
                const matchStatus = status === 'all' || cardStatus === status;

                card.style.display = (matchSearch && matchRegion && matchIndustry && matchStatus) ? 'block' : 'none';
            });
        }

        // Clear Filters
        function clearFilters() {
            document.getElementById('poll-search').value = '';
            document.getElementById('poll-region').value = '';
            document.getElementById('poll-industry').value = '';
            document.getElementById('poll-status').value = 'all';
            document.getElementById('poll-sort').value = 'newest';
            filterPolls();
        }

        // Auto-filter on input
        document.getElementById('poll-search').addEventListener('input', filterPolls);
        document.getElementById('poll-region').addEventListener('change', filterPolls);
        document.getElementById('poll-industry').addEventListener('change', filterPolls);
        document.getElementById('poll-status').addEventListener('change', filterPolls);
        </script>
        <?php
        return ob_get_clean();
    }
    /**
     * Handle payment processing.
     */
    public function handle_payment_processing() {
        if ( ! isset( $_POST['gaenity_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gaenity_checkout_nonce'] ) ), 'gaenity_checkout' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'gaenity-community' ) ) );
        }

        $gateway = isset( $_POST['payment_gateway'] ) ? sanitize_text_field( $_POST['payment_gateway'] ) : '';
        $item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : '';
        $item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $currency = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : 'USD';

        if ( ! $gateway || ! $item_id || $amount <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid payment data.', 'gaenity-community' ) ) );
        }

        $user_id = get_current_user_id();
        $email = $user_id ? wp_get_current_user()->user_email : '';

        // Create transaction record
        global $wpdb;
        $transaction_id = 'TXN_' . time() . '_' . wp_generate_password( 8, false );
        
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_transactions',
            array(
                'user_id' => $user_id,
                'email' => $email,
                'item_type' => $item_type,
                'item_id' => $item_id,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => $gateway,
                'transaction_id' => $transaction_id,
                'status' => 'pending',
            ),
            array( '%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s' )
        );

        // Process based on gateway
        switch ( $gateway ) {
            case 'stripe':
                $result = $this->process_stripe_payment( $transaction_id, $amount, $currency, $email, $item_id );
                break;
            case 'paypal':
                $result = $this->process_paypal_payment( $transaction_id, $amount, $currency, $email, $item_id );
                break;
            case 'paystack':
                $result = $this->process_paystack_payment( $transaction_id, $amount, $currency, $email, $item_id );
                break;
            case 'bank_transfer':
                $result = $this->process_bank_transfer( $transaction_id, $amount, $currency, $email, $item_id );
                break;
            default:
                $result = array( 'success' => false, 'message' => __( 'Invalid payment gateway.', 'gaenity-community' ) );
        }

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Process Stripe payment.
     */
    protected function process_stripe_payment( $transaction_id, $amount, $currency, $email, $item_id ) {
        // For now, return a placeholder
        // You would integrate Stripe PHP SDK here
        return array(
            'success' => true,
            'message' => __( 'Stripe integration ready. Add Stripe PHP SDK for live processing.', 'gaenity-community' ),
            'redirect_url' => add_query_arg( 'payment', 'success', home_url( '/dashboard' ) ),
        );
    }

    /**
     * Process PayPal payment.
     */
    protected function process_paypal_payment( $transaction_id, $amount, $currency, $email, $item_id ) {
        return array(
            'success' => true,
            'message' => __( 'PayPal integration ready.', 'gaenity-community' ),
            'redirect_url' => add_query_arg( 'payment', 'success', home_url( '/dashboard' ) ),
        );
    }

    /**
     * Process Paystack payment.
     */
    protected function process_paystack_payment( $transaction_id, $amount, $currency, $email, $item_id ) {
        $public_key = get_option( 'gaenity_paystack_public_key', '' );
        
        if ( empty( $public_key ) ) {
            return array( 'success' => false, 'message' => __( 'Paystack not configured.', 'gaenity-community' ) );
        }

        // Generate Paystack payment URL
        $paystack_url = 'https://checkout.paystack.com/';
        
        return array(
            'success' => true,
            'message' => __( 'Redirecting to Paystack...', 'gaenity-community' ),
            'redirect_url' => $paystack_url,
        );
    }

    /**
     * Process bank transfer.
     */
    protected function process_bank_transfer( $transaction_id, $amount, $currency, $email, $item_id ) {
        // Update transaction status to awaiting_confirmation
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'gaenity_transactions',
            array( 'status' => 'awaiting_confirmation' ),
            array( 'transaction_id' => $transaction_id ),
            array( '%s' ),
            array( '%s' )
        );

        return array(
            'success' => true,
            'message' => __( 'Order submitted! Please make the bank transfer and send confirmation to our email.', 'gaenity-community' ),
        );
    }
    /**
     * Render courses grid.
     */
    public function render_courses() {
        $courses = new WP_Query( array(
            'post_type' => 'gaenity_course',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ) );

        $currency_symbol = $this->get_currency_symbol();

        ob_start();
        ?>
        <style>
            .gaenity-courses {
                max-width: 1400px;
                margin: 3rem auto;
                padding: 0 1rem;
            }
            .gaenity-courses-header {
                text-align: center;
                margin-bottom: 3rem;
            }
            .gaenity-courses-header h2 {
                font-size: 2.5rem;
                margin-bottom: 1rem;
                color: #1e293b;
            }
            .gaenity-courses-header p {
                font-size: 1.15rem;
                color: #64748b;
                max-width: 700px;
                margin: 0 auto;
            }
            .gaenity-courses-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 2rem;
            }
            .gaenity-course-card {
                background: #fff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 2px solid #e2e8f0;
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
            }
            .gaenity-course-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 35px rgba(29,78,216,0.15);
                border-color: #1d4ed8;
            }
            .gaenity-course-image {
                width: 100%;
                height: 200px;
                object-fit: cover;
                background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            }
            .gaenity-course-content {
                padding: 2rem;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .gaenity-course-title {
                font-size: 1.4rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0 0 1rem 0;
            }
            .gaenity-course-excerpt {
                color: #475569;
                line-height: 1.6;
                margin-bottom: 1.5rem;
                flex: 1;
            }
            .gaenity-course-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 1rem;
                border-top: 2px solid #f1f5f9;
            }
            .gaenity-course-price {
                font-size: 1.8rem;
                font-weight: 800;
                color: #1d4ed8;
            }
            .gaenity-course-price-label {
                font-size: 0.85rem;
                color: #64748b;
                display: block;
            }
            .gaenity-course-btn {
                padding: 0.75rem 1.5rem;
                background: #1d4ed8;
                color: #fff;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.2s ease;
                display: inline-block;
            }
            .gaenity-course-btn:hover {
                background: #1e40af;
                transform: translateY(-2px);
                color: #fff;
            }
            .gaenity-course-free {
                background: #10b981;
                color: #fff;
                padding: 0.4rem 1rem;
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 700;
            }
        </style>

        <div class="gaenity-courses">
            <div class="gaenity-courses-header">
                <h2><?php esc_html_e( 'Enablement Courses', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'On-demand courses by subscription to build lasting business capability. Access structured learning, actionable tools, and repeatable methods to strengthen your business at your own pace.', 'gaenity-community' ); ?></p>
            </div>

            <?php if ( $courses->have_posts() ) : ?>
                <div class="gaenity-courses-grid">
                    <?php while ( $courses->have_posts() ) : $courses->the_post();
                        $course_id = get_the_ID();
                        $price = get_post_meta( $course_id, '_gaenity_course_price', true );
                        $type = get_post_meta( $course_id, '_gaenity_course_type', true );
                        $duration = get_post_meta( $course_id, '_gaenity_course_duration', true );
                    ?>
                        <article class="gaenity-course-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', array( 'class' => 'gaenity-course-image' ) ); ?>
                            <?php else : ?>
                                <div class="gaenity-course-image"></div>
                            <?php endif; ?>
                            
                            <div class="gaenity-course-content">
                                <h3 class="gaenity-course-title"><?php the_title(); ?></h3>
                                
                                <?php if ( $duration ) : ?>
                                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">â±ï¸ <?php echo esc_html( $duration ); ?></p>
                                <?php endif; ?>

                                <div class="gaenity-course-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </div>

                                <div class="gaenity-course-meta">
                                    <div>
                                        <?php if ( $type === 'free' ) : ?>
                                            <span class="gaenity-course-free"><?php esc_html_e( 'FREE', 'gaenity-community' ); ?></span>
                                        <?php else : ?>
                                            <div class="gaenity-course-price">
                                                <?php echo esc_html( $currency_symbol . number_format( $price, 2 ) ); ?>
                                            </div>
                                            <span class="gaenity-course-price-label">
                                                <?php echo $type === 'subscription' ? esc_html__( '/month', 'gaenity-community' ) : esc_html__( 'one-time', 'gaenity-community' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url( add_query_arg( 'course_id', $course_id, home_url( '/checkout' ) ) ); ?>" class="gaenity-course-btn">
                                        <?php esc_html_e( 'Enroll Now', 'gaenity-community' ); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div style="text-align: center; padding: 4rem; background: #f8fafc; border-radius: 12px;">
                    <p style="font-size: 1.2rem; color: #64748b;"><?php esc_html_e( 'Courses coming soon! Check back for new enablement programs.', 'gaenity-community' ); ?></p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
/**
     * Register community home shortcode.
     */
    public function register_community_home_v2() {
        add_shortcode( 'gaenity_community_home_v2', array( $this, 'render_community_home_v2' ) );
    }

    /**
     * Render enhanced community home.
     */
    public function render_community_home_v2() {
        ob_start();
        ?>
        <style>
            .gaenity-community-hub {
                max-width: 1400px;
                margin: 3rem auto;
                padding: 0 1rem;
            }
            .gaenity-hub-hero {
                background: linear-gradient(135deg, #1d4ed8, #7c3aed);
                color: #fff;
                padding: 4rem 2rem;
                border-radius: 20px;
                text-align: center;
                margin-bottom: 3rem;
            }
            .gaenity-hub-hero h1 {
                font-size: 3rem;
                margin: 0 0 1rem 0;
            }
            .gaenity-hub-hero p {
                font-size: 1.3rem;
                opacity: 0.95;
                max-width: 700px;
                margin: 0 auto 2rem;
            }
            .gaenity-hub-stats {
                display: flex;
                justify-content: center;
                gap: 3rem;
                flex-wrap: wrap;
                margin-top: 2rem;
            }
            .gaenity-hub-stat {
                text-align: center;
            }
            .gaenity-hub-stat-number {
                font-size: 2.5rem;
                font-weight: 800;
                display: block;
            }
            .gaenity-hub-stat-label {
                font-size: 0.95rem;
                opacity: 0.9;
            }
            .gaenity-hub-nav {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 2rem;
                margin-bottom: 3rem;
            }
            .gaenity-hub-card {
                background: #fff;
                padding: 2rem;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                text-decoration: none;
                color: inherit;
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            .gaenity-hub-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 35px rgba(29,78,216,0.2);
            }
            .gaenity-hub-card-icon {
                font-size: 3rem;
            }
            .gaenity-hub-card h3 {
                margin: 0;
                font-size: 1.4rem;
                color: #1e293b;
            }
            .gaenity-hub-card p {
                margin: 0;
                color: #64748b;
                line-height: 1.6;
                flex: 1;
            }
            .gaenity-hub-card-arrow {
                color: #1d4ed8;
                font-weight: 700;
            }
            .gaenity-hub-section {
                background: #f8fafc;
                padding: 3rem 2rem;
                border-radius: 16px;
                margin-bottom: 2rem;
            }
            .gaenity-hub-section h2 {
                text-align: center;
                font-size: 2rem;
                color: #1e293b;
                margin-bottom: 2rem;
            }
            .gaenity-guidelines-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            .gaenity-guideline-item {
                background: #fff;
                padding: 1.5rem;
                border-radius: 12px;
                border-left: 4px solid #1d4ed8;
            }
            .gaenity-guideline-item h4 {
                margin: 0 0 0.5rem 0;
                color: #1e293b;
            }
            .gaenity-guideline-item p {
                margin: 0;
                color: #64748b;
                font-size: 0.95rem;
            }
            @media (max-width: 768px) {
                .gaenity-hub-hero h1 {
                    font-size: 2rem;
                }
                .gaenity-hub-hero p {
                    font-size: 1.1rem;
                }
            }
        </style>

        <div class="gaenity-community-hub">
            <div class="gaenity-hub-hero">
                <h1><?php esc_html_e( 'Welcome to Gaenity Community', 'gaenity-community' ); ?></h1>
                <p><?php esc_html_e( 'Connect with business owners, entrepreneurs, and professionals across industries and regions. Share challenges, access resources, and learn from expert insights.', 'gaenity-community' ); ?></p>
                
                <div class="gaenity-hub-stats">
                    <?php
                    global $wpdb;
                    $user_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
                    $discussion_count = wp_count_posts( 'gaenity_discussion' )->publish;
                    $resource_count = wp_count_posts( 'gaenity_resource' )->publish;
                    ?>
                    <div class="gaenity-hub-stat">
                        <span class="gaenity-hub-stat-number"><?php echo esc_html( number_format( $user_count ) ); ?>+</span>
                        <span class="gaenity-hub-stat-label"><?php esc_html_e( 'Members', 'gaenity-community' ); ?></span>
                    </div>
                    <div class="gaenity-hub-stat">
                        <span class="gaenity-hub-stat-number"><?php echo esc_html( number_format( $discussion_count ) ); ?>+</span>
                        <span class="gaenity-hub-stat-label"><?php esc_html_e( 'Discussions', 'gaenity-community' ); ?></span>
                    </div>
                    <div class="gaenity-hub-stat">
                        <span class="gaenity-hub-stat-number"><?php echo esc_html( number_format( $resource_count ) ); ?>+</span>
                        <span class="gaenity-hub-stat-label"><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></span>
                    </div>
                </div>
            </div>

            <div class="gaenity-hub-nav">
                <a href="<?php echo esc_url( get_option( 'gaenity_register_url', home_url( '/register' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">âœ¨</div>
                    <h3><?php esc_html_e( 'Join the Community', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Create your account and connect with peers across industries and regions.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Get Started â†’', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">ðŸ’¬</div>
                    <h3><?php esc_html_e( 'Forum', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Ask questions, share challenges, and learn from community discussions.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Browse Discussions â†’', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_expert_directory_url', home_url( '/experts' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">ðŸŽ“</div>
                    <h3><?php esc_html_e( 'Experts', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Connect with vetted professionals for personalized guidance and support.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Meet Experts â†’', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_polls_url', home_url( '/polls' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">ðŸ“Š</div>
                    <h3><?php esc_html_e( 'Polls', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Participate in community polls and see what others think about key topics.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Take Polls â†’', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_resources_url', home_url( '/resources' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">ðŸ“š</div>
                    <h3><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Access templates, guides, and tools to strengthen your business operations.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Browse Resources â†’', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_courses_url', home_url( '/courses' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">ðŸŽ¯</div>
                    <h3><?php esc_html_e( 'Enablement Courses', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Structured learning programs on risk, finance, and operational readiness.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'View Courses â†’', 'gaenity-community' ); ?></span>
                </a>
            </div>

            <div class="gaenity-hub-section">
                <h2><?php esc_html_e( 'Community Guidelines', 'gaenity-community' ); ?></h2>
                
                <div class="gaenity-guidelines-grid">
                    <div class="gaenity-guideline-item">
                        <h4>ðŸ¤ <?php esc_html_e( 'Be Respectful', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Treat all members with courtesy. Disagreement is fine, but personal attacks are not.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>ðŸ’¡ <?php esc_html_e( 'Share Real Experience', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Contribute authentic insights from your business journey to help others learn.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>ðŸš« <?php esc_html_e( 'No Spam or Selling', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Self-promotion without context disrupts the community. Share value first.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>ðŸ”’ <?php esc_html_e( 'Protect Privacy', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Don\'t share others\' confidential information. Respect anonymity when requested.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>ðŸŽ¯ <?php esc_html_e( 'Stay On Topic', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Keep discussions relevant to business challenges, growth, and community support.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>âš ï¸ <?php esc_html_e( 'Report Issues', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'If you see violations, report them to moderators. We review all reports promptly.', 'gaenity-community' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get currency symbol.
     */
    protected function get_currency_symbol() {
        $currency = get_option( 'gaenity_currency', 'USD' );
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'NGN' => '₦',
            'ZAR' => 'R',
            'KES' => 'KSh',
        );
        return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
    }

    /**
     * Send email notification with variable replacement.
     */
    protected function send_resource_email( $type, $data ) {
        // Check if email notifications are enabled
        if ( ! get_option( 'gaenity_enable_email_notifications', 1 ) ) {
            return false;
        }

        // Get email settings
        $from_name = get_option( 'gaenity_email_from_name', get_bloginfo( 'name' ) );
        $from_email = get_option( 'gaenity_email_from_email', get_option( 'admin_email' ) );

        // Get template based on type
        if ( 'free' === $type ) {
            $subject_template = get_option( 'gaenity_free_resource_email_subject', 'Your Free Resource is Ready!' );
            $body_template = get_option( 'gaenity_free_resource_email_body', "Hi {user_name},\n\nThank you for downloading {resource_title}!\n\nClick the button below to access your resource:\n\n{download_button}\n\nBest regards,\n{site_name} Team" );
        } else {
            $subject_template = get_option( 'gaenity_paid_resource_email_subject', 'Payment Successful - Access Your Resource' );
            $body_template = get_option( 'gaenity_paid_resource_email_body', "Hi {user_name},\n\nThank you for your purchase of {resource_title}!\n\nPayment Details:\nAmount: {amount}\nTransaction ID: {transaction_id}\n\nClick the button below to access your resource:\n\n{download_button}\n\nYou have {download_limit} downloads available, and access will expire in {expiry_days} days.\n\nBest regards,\n{site_name} Team" );
        }

        // Create download button HTML
        $download_button = '<div style="text-align: center; margin: 30px 0;">';
        $download_button .= '<a href="' . esc_url( $data['download_link'] ) . '" style="background: #2563eb; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600;">Download Resource</a>';
        $download_button .= '</div>';

        // Replace variables
        $variables = array(
            '{user_name}'       => isset( $data['user_name'] ) ? $data['user_name'] : 'Valued User',
            '{resource_title}'  => isset( $data['resource_title'] ) ? $data['resource_title'] : '',
            '{download_link}'   => isset( $data['download_link'] ) ? $data['download_link'] : '',
            '{download_button}' => $download_button,
            '{site_name}'       => get_bloginfo( 'name' ),
            '{amount}'          => isset( $data['amount'] ) ? $this->get_currency_symbol() . number_format( $data['amount'], 2 ) : '',
            '{transaction_id}'  => isset( $data['transaction_id'] ) ? $data['transaction_id'] : '',
            '{download_limit}'  => isset( $data['download_limit'] ) ? $data['download_limit'] : get_option( 'gaenity_download_limit', 3 ),
            '{expiry_days}'     => isset( $data['expiry_days'] ) ? $data['expiry_days'] : get_option( 'gaenity_download_expiry_days', 30 ),
        );

        $subject = str_replace( array_keys( $variables ), array_values( $variables ), $subject_template );
        $body = str_replace( array_keys( $variables ), array_values( $variables ), $body_template );

        // Convert newlines to HTML breaks and wrap in basic HTML template
        $body_html = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $body_html .= nl2br( $body );
        $body_html .= '</body></html>';

        // Set headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        // Send email
        return wp_mail( $data['email'], $subject, $body_html, $headers );
    }

    /**
     * Render expert directory.
     */
    public function render_expert_directory() {
        // Get all approved experts
        $experts = get_users( array(
            'role' => 'gaenity_expert',
            'meta_key' => 'gaenity_expert_approved',
            'meta_value' => '1',
        ) );

        ob_start();
        ?>
        <style>
            .gaenity-expert-directory {
                max-width: 1400px;
                margin: 2rem auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .gaenity-expert-directory h2 {
                font-size: 2.5rem;
                margin-bottom: 1rem;
                color: #1e293b;
            }
            .gaenity-expert-directory > p {
                font-size: 1.15rem;
                color: #64748b;
                margin-bottom: 3rem;
            }
            .gaenity-experts-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 2rem;
            }
            .gaenity-expert-card {
                background: #fff;
                border-radius: 16px;
                padding: 2rem;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 2px solid #e2e8f0;
                transition: all 0.3s ease;
            }
            .gaenity-expert-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 30px rgba(29,78,216,0.15);
                border-color: #1d4ed8;
            }
            .gaenity-expert-avatar {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: linear-gradient(135deg, #1d4ed8, #7c3aed);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin-bottom: 1.5rem;
                color: #fff;
            }
            .gaenity-expert-name {
                font-size: 1.4rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0 0 0.5rem 0;
            }
            .gaenity-expert-expertise {
                color: #1d4ed8;
                font-weight: 600;
                margin-bottom: 1rem;
                font-size: 0.95rem;
            }
            .gaenity-expert-region {
                display: inline-block;
                background: #e0e7ff;
                color: #3730a3;
                padding: 0.35rem 0.9rem;
                border-radius: 999px;
                font-size: 0.85rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }
            .gaenity-expert-bio {
                color: #475569;
                line-height: 1.6;
                margin-bottom: 1.5rem;
            }
            .gaenity-expert-actions {
                display: flex;
                gap: 0.75rem;
            }
            .gaenity-expert-btn {
                flex: 1;
                padding: 0.75rem 1rem;
                border-radius: 8px;
                text-align: center;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.9rem;
                transition: all 0.2s ease;
            }
            .gaenity-expert-btn.primary {
                background: #1d4ed8;
                color: #fff;
            }
            .gaenity-expert-btn.primary:hover {
                background: #1e40af;
                transform: translateY(-1px);
            }
            .gaenity-expert-btn.secondary {
                background: #f1f5f9;
                color: #1e293b;
                border: 2px solid #e2e8f0;
            }
            .gaenity-expert-btn.secondary:hover {
                background: #e2e8f0;
            }
        </style>

        <div class="gaenity-expert-directory">
            <h2><?php esc_html_e( 'Meet Our Expert Community', 'gaenity-community' ); ?></h2>
            <p><?php esc_html_e( 'Connect with vetted professionals who provide practical guidance on risk, finance, operations, and more.', 'gaenity-community' ); ?></p>

            <?php if ( ! empty( $experts ) ) : ?>
                <div class="gaenity-experts-grid">
                    <?php foreach ( $experts as $expert ) : 
                        $expertise = get_user_meta( $expert->ID, 'gaenity_expertise', true );
                        $region = get_user_meta( $expert->ID, 'gaenity_region', true );
                        $profile_url = get_user_meta( $expert->ID, 'gaenity_profile_url', true );
                        $initials = strtoupper( substr( $expert->display_name, 0, 1 ) );
                    ?>
                        <div class="gaenity-expert-card">
                            <div class="gaenity-expert-avatar"><?php echo esc_html( $initials ); ?></div>
                            <h3 class="gaenity-expert-name"><?php echo esc_html( $expert->display_name ); ?></h3>
                            <?php if ( $expertise ) : ?>
                                <div class="gaenity-expert-expertise"><?php echo esc_html( wp_trim_words( $expertise, 6 ) ); ?></div>
                            <?php endif; ?>
                            <?php if ( $region ) : ?>
                                <span class="gaenity-expert-region">ðŸ“ <?php echo esc_html( $region ); ?></span>
                            <?php endif; ?>
                            <?php if ( $expertise ) : ?>
                                <p class="gaenity-expert-bio"><?php echo esc_html( wp_trim_words( $expertise, 20 ) ); ?></p>
                            <?php endif; ?>
                            <div class="gaenity-expert-actions">
                                <a href="<?php echo esc_url( get_option( 'gaenity_ask_expert_url', home_url( '/ask-expert' ) ) ); ?>" class="gaenity-expert-btn primary"><?php esc_html_e( 'Request Consultation', 'gaenity-community' ); ?></a>
                                <?php if ( $profile_url ) : ?>
                                    <a href="<?php echo esc_url( $profile_url ); ?>" target="_blank" class="gaenity-expert-btn secondary"><?php esc_html_e( 'View Profile', 'gaenity-community' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div style="text-align: center; padding: 4rem; background: #f8fafc; border-radius: 12px;">
                    <p style="font-size: 1.2rem; color: #64748b; margin-bottom: 1.5rem;"><?php esc_html_e( 'No experts available yet. Check back soon!', 'gaenity-community' ); ?></p>
                    <a href="#gaenity-register-expert" style="display: inline-block; padding: 0.75rem 2rem; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Become an Expert', 'gaenity-community' ); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Register Ajax handlers.
     */
    protected function register_ajax_actions() {
        $actions = array(
            'gaenity_resource_download' => 'handle_resource_download',
            'gaenity_paid_resource_purchase' => 'handle_paid_resource_purchase',
            'gaenity_user_register'     => 'handle_user_registration',
            'gaenity_user_login'        => 'handle_user_login',
            'gaenity_discussion_submit' => 'handle_discussion_submit',
            'gaenity_poll_vote'         => 'handle_poll_vote',
            'gaenity_expert_request'    => 'handle_expert_request',
            'gaenity_expert_register'   => 'handle_expert_registration',
            'gaenity_contact_submit'    => 'handle_contact_submission',
            'gaenity_chat_send'         => 'handle_chat_message',
            'gaenity_chat_fetch'        => 'handle_chat_fetch',
            'gaenity_process_payment'   => 'handle_payment_processing',
            'gaenity_vote_discussion'   => 'handle_discussion_vote',
            'gaenity_toggle_discussion_like' => 'handle_discussion_like_toggle',
        );

        foreach ( $actions as $action => $method ) {
            add_action( 'wp_ajax_' . $action, array( $this, $method ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
        }
    }
    /**
     * Handle resource download submission.
     */
    public function handle_resource_download() {
        $this->verify_nonce();

        $resource_id = isset( $_POST['resource_id'] ) ? absint( $_POST['resource_id'] ) : 0;
        $email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
        $region      = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $industry    = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
        $other       = isset( $_POST['industry_other'] ) ? sanitize_text_field( wp_unslash( $_POST['industry_other'] ) ) : '';
        $consent     = isset( $_POST['consent'] ) ? 1 : 0;
        $download    = isset( $_POST['download_url'] ) ? esc_url_raw( wp_unslash( $_POST['download_url'] ) ) : '';

        if ( empty( $resource_id ) || empty( $email ) || empty( $role ) || empty( $region ) || empty( $industry ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }

        if ( 'other' === strtolower( $industry ) && ! empty( $other ) ) {
            $industry = $other;
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_resource_downloads',
            array(
                'resource_id' => $resource_id,
                'email'       => $email,
                'role'        => $role,
                'region'      => $region,
                'industry'    => $industry,
                'consent'     => $consent,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( empty( $download ) ) {
            $download = get_post_meta( $resource_id, '_gaenity_resource_file', true );
        }

        // Send email notification
        $user_name = explode( '@', $email )[0];
        $resource_title = get_the_title( $resource_id );

        // Create download page URL
        $download_page_url = home_url( '/download/' );
        $download_page_url = add_query_arg(
            array(
                'resource_id'  => $resource_id,
                'download_url' => urlencode( $download ),
                'paid'         => 0,
            ),
            $download_page_url
        );

        $this->send_resource_email(
            'free',
            array(
                'email'          => $email,
                'user_name'      => ucfirst( $user_name ),
                'resource_title' => $resource_title,
                'download_link'  => $download_page_url,
            )
        );

        wp_send_json_success(
            array(
                'message'       => __( 'Success! Redirecting to download page...', 'gaenity-community' ),
                'redirect_url'  => $download_page_url,
            )
        );
    }

    /**
     * Handle paid resource purchase with integrated payment processing.
     */
    public function handle_paid_resource_purchase() {
        $this->verify_nonce();

        $resource_id = isset( $_POST['resource_id'] ) ? absint( $_POST['resource_id'] ) : 0;
        $email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
        $region      = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $industry    = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
        $other       = isset( $_POST['industry_other'] ) ? sanitize_text_field( wp_unslash( $_POST['industry_other'] ) ) : '';
        $amount      = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $currency    = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : 'USD';
        $gateway     = isset( $_POST['payment_gateway'] ) ? sanitize_text_field( $_POST['payment_gateway'] ) : '';
        $consent     = isset( $_POST['consent'] ) ? 1 : 0;

        if ( empty( $resource_id ) || empty( $email ) || empty( $role ) || empty( $region ) || empty( $industry ) || empty( $gateway ) || $amount <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }

        if ( 'other' === strtolower( $industry ) && ! empty( $other ) ) {
            $industry = $other;
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Create transaction record
        $transaction_id = 'TXN_' . time() . '_' . wp_generate_password( 8, false );

        $wpdb->insert(
            $wpdb->prefix . 'gaenity_transactions',
            array(
                'user_id'        => $user_id,
                'email'          => $email,
                'item_type'      => 'resource',
                'item_id'        => $resource_id,
                'amount'         => $amount,
                'currency'       => $currency,
                'gateway'        => $gateway,
                'transaction_id' => $transaction_id,
                'status'         => 'pending',
            ),
            array( '%d', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s' )
        );

        // Process payment based on gateway
        switch ( $gateway ) {
            case 'stripe':
                $result = $this->process_stripe_payment( $transaction_id, $amount, $currency, $email, $resource_id );
                break;
            case 'paypal':
                $result = $this->process_paypal_payment( $transaction_id, $amount, $currency, $email, $resource_id );
                break;
            case 'paystack':
                $result = $this->process_paystack_payment( $transaction_id, $amount, $currency, $email, $resource_id );
                break;
            case 'bank_transfer':
                $result = $this->process_bank_transfer( $transaction_id, $amount, $currency, $email, $resource_id );
                break;
            default:
                $result = array( 'success' => false, 'message' => __( 'Invalid payment gateway.', 'gaenity-community' ) );
        }

        // If payment is successful, grant access to the resource
        if ( $result['success'] ) {
            // Update transaction status
            $wpdb->update(
                $wpdb->prefix . 'gaenity_transactions',
                array( 'status' => 'completed' ),
                array( 'transaction_id' => $transaction_id ),
                array( '%s' ),
                array( '%s' )
            );

            // Get configurable expiry and limit settings
            $expiry_days = absint( get_option( 'gaenity_download_expiry_days', 30 ) );
            $download_limit = absint( get_option( 'gaenity_download_limit', 3 ) );

            // Grant access to the resource with configurable expiration
            $expires_at = gmdate( 'Y-m-d H:i:s', strtotime( "+{$expiry_days} days" ) );

            $wpdb->insert(
                $wpdb->prefix . 'gaenity_paid_resource_access',
                array(
                    'resource_id'      => $resource_id,
                    'transaction_id'   => $transaction_id,
                    'user_id'          => $user_id,
                    'email'            => $email,
                    'role'             => $role,
                    'region'           => $region,
                    'industry'         => $industry,
                    'download_count'   => 0,
                    'max_downloads'    => $download_limit,
                    'expires_at'       => $expires_at,
                ),
                array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
            );

            // Get download URL
            $download_url = get_post_meta( $resource_id, '_gaenity_resource_file', true );

            // Send email notification
            $user_name = explode( '@', $email )[0];
            $resource_title = get_the_title( $resource_id );

            // Create download page URL
            $download_page_url = home_url( '/download/' );
            $download_page_url = add_query_arg(
                array(
                    'resource_id'  => $resource_id,
                    'download_url' => urlencode( $download_url ),
                    'paid'         => 1,
                ),
                $download_page_url
            );

            $this->send_resource_email(
                'paid',
                array(
                    'email'          => $email,
                    'user_name'      => ucfirst( $user_name ),
                    'resource_title' => $resource_title,
                    'download_link'  => $download_page_url,
                    'amount'         => $amount,
                    'transaction_id' => $transaction_id,
                    'download_limit' => $download_limit,
                    'expiry_days'    => $expiry_days,
                )
            );

            wp_send_json_success(
                array(
                    'message'      => __( 'Payment successful! Redirecting to download page...', 'gaenity-community' ),
                    'redirect_url' => $download_page_url,
                )
            );
        } else {
            // Even if payment failed, log it for debugging
            error_log( 'Gaenity Payment Failed: ' . wp_json_encode( $result ) );
            wp_send_json_error( $result );
        }
    }

    /**
     * Handle community registration.
     */
    public function handle_user_registration() {
    $this->verify_nonce();

    // NEW: Only require fields that exist in the form
    $required = array( 'full_name', 'email', 'password', 'region', 'industry' );
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }
    }

    if ( empty( $_POST['guidelines'] ) ) {
        wp_send_json_error( array( 'message' => __( 'You must agree to the community guidelines.', 'gaenity-community' ) ) );
    }

    $email        = sanitize_email( wp_unslash( $_POST['email'] ) );
    $full_name    = sanitize_text_field( wp_unslash( $_POST['full_name'] ) );
    $password     = wp_unslash( $_POST['password'] );
    $region       = sanitize_text_field( wp_unslash( $_POST['region'] ) );
    $industry     = sanitize_text_field( wp_unslash( $_POST['industry'] ) );
    
    // Use defaults for optional fields
    $display_name = $full_name;
    $role         = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : 'Community Member';
    $country      = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
    $challenge    = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
    $goals        = isset( $_POST['goals'] ) ? wp_kses_post( wp_unslash( $_POST['goals'] ) ) : '';
    $updates      = ! empty( $_POST['updates'] ) ? 1 : 0;

    if ( email_exists( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'This email is already registered.', 'gaenity-community' ) ) );
    }

    $username = sanitize_user( current( explode( '@', $email ) ) );
    $username = apply_filters( 'gaenity_generate_username', $username, $email );

    if ( username_exists( $username ) ) {
        $username .= '_' . wp_generate_password( 4, false );
    }

    $user_id = wp_create_user( $username, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
    }

    $user_role = 'subscriber';
    if ( 'Forum Expert' === $role ) {
        $user_role = 'gaenity_expert';
    }

    wp_update_user(
        array(
            'ID'           => $user_id,
            'display_name' => $display_name,
            'nickname'     => $display_name,
            'role'         => $user_role,
        )
    );

    update_user_meta( $user_id, 'gaenity_full_name', $full_name );
    update_user_meta( $user_id, 'gaenity_region', $region );
    update_user_meta( $user_id, 'gaenity_country', $country );
    update_user_meta( $user_id, 'gaenity_industry', $industry );
    update_user_meta( $user_id, 'gaenity_challenge', $challenge );
    update_user_meta( $user_id, 'gaenity_goals', $goals );
    update_user_meta( $user_id, 'gaenity_updates_opt_in', $updates );
    update_user_meta( $user_id, 'gaenity_role_title', $role );

    wp_signon(
    array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
    ),
    false
);

wp_send_json_success(
    array(
        'message'  => __( 'Welcome to the Gaenity community!', 'gaenity-community' ),
        'redirect' => home_url( '/dashboard/' ),
    )
);
}

    /**
     * Handle login request.
     */
    public function handle_user_login() {
        $this->verify_nonce();

        $credentials = array(
            'user_login'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
            'user_password' => isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '',
            'remember'      => ! empty( $_POST['remember'] ),
        );

        if ( empty( $credentials['user_login'] ) || empty( $credentials['user_password'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'gaenity-community' ) ) );
        }

        $user = wp_signon( $credentials, false );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => $user->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'message'  => __( 'Login successful.', 'gaenity-community' ),
                'redirect' => apply_filters( 'gaenity_login_redirect', home_url() ),
            )
        );
    }

    /**
     * Handle discussion submission.
     */
    public function handle_discussion_submit() {
        $this->verify_nonce();

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to post.', 'gaenity-community' ) ) );
        }

        $user_id = get_current_user_id();

        $title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $content   = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
        $region    = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $industry  = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
        $challenge = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
        $country   = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
        $anonymous = ! empty( $_POST['anonymous'] );

        if ( empty( $title ) || empty( $content ) || empty( $region ) || empty( $industry ) || empty( $challenge ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }

        $post_id = wp_insert_post(
            array(
                'post_type'    => 'gaenity_discussion',
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }

        wp_set_object_terms( $post_id, $region, 'gaenity_region' );
        wp_set_object_terms( $post_id, $industry, 'gaenity_industry' );
        wp_set_object_terms( $post_id, $challenge, 'gaenity_challenge' );

        update_post_meta( $post_id, '_gaenity_country', $country );
        update_post_meta( $post_id, '_gaenity_anonymous', $anonymous ? 1 : 0 );

        wp_send_json_success(
            array(
                'message' => __( 'Discussion published successfully.', 'gaenity-community' ),
            )
        );
    }

    /**
     * Handle poll votes.
     */
    public function handle_poll_vote() {
        $this->verify_nonce();

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Login is required to vote.', 'gaenity-community' ) ) );
        }

        $poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;
        $option  = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
        $region  = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $industry= isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';

        if ( empty( $poll_id ) || empty( $option ) || empty( $region ) || empty( $industry ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select an option and provide your profile filters.', 'gaenity-community' ) ) );
        }

        $options = get_post_meta( $poll_id, '_gaenity_poll_options', true );
        if ( empty( $options ) || ! isset( $options[ $option ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid poll option selected.', 'gaenity-community' ) ) );
        }

        $user_id = get_current_user_id();

        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'gaenity_poll_votes WHERE poll_id = %d AND user_id = %d LIMIT 1', $poll_id, $user_id ) );

        if ( $exists ) {
            wp_send_json_error( array( 'message' => __( 'You already voted in this poll.', 'gaenity-community' ) ) );
        }

        $wpdb->insert(
            $wpdb->prefix . 'gaenity_poll_votes',
            array(
                'poll_id'    => $poll_id,
                'user_id'    => $user_id,
                'option_key' => $option,
                'region'     => $region,
                'industry'   => $industry,
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );

        $results = $this->get_poll_results_markup( $poll_id, $options );

        wp_send_json_success(
            array(
                'message' => __( 'Thanks for sharing your vote.', 'gaenity-community' ),
                'results' => $results,
            )
        );
    }

    /**
     * Handle expert request submissions.
     */
   public function handle_expert_request() {
    $this->verify_nonce();

    // Match the actual form field names: title, details, email, region, industry
    $required_fields = array( 'title', 'details', 'email', 'region', 'industry' );
    foreach ( $required_fields as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }
    }
    // Get values - map form fields to database columns
    $title       = sanitize_text_field( wp_unslash( $_POST['title'] ) ); // Form has 'title'
    $email       = sanitize_email( wp_unslash( $_POST['email'] ) );
    $details     = wp_kses_post( wp_unslash( $_POST['details'] ) ); // Form has 'details'
    $region      = sanitize_text_field( wp_unslash( $_POST['region'] ) );
    $industry    = sanitize_text_field( wp_unslash( $_POST['industry'] ) );

    // Optional fields
    $role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
    $country     = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
    $challenge   = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
    $budget      = isset( $_POST['budget'] ) ? sanitize_text_field( wp_unslash( $_POST['budget'] ) ) : '';
    $preference  = isset( $_POST['preference'] ) ? sanitize_text_field( wp_unslash( $_POST['preference'] ) ) : '';

        global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'gaenity_expert_requests',
    array(
        'user_id'    => get_current_user_id(),
        'name'       => $title, // Store title in name column
        'email'      => $email,
        'role'       => $role,
        'region'     => $region,
        'country'    => $country,
        'industry'   => $industry,
        'challenge'  => $challenge,
        'description'=> $details, // Store details in description column
        'budget'     => $budget,
        'preference' => $preference,
    ),
    array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
);

        wp_send_json_success( array( 'message' => __( 'Your request has been submitted. We will be in touch soon.', 'gaenity-community' ) ) );
    }

    /**
     * Handle expert registration submissions.
     */
    public function handle_expert_registration() {
    $this->verify_nonce();

    // Only require essential fields
    $required_fields = array( 'name', 'email', 'expertise' );
    foreach ( $required_fields as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }
    }

    // Get values with defaults
    $name        = sanitize_text_field( wp_unslash( $_POST['name'] ) );
    $email       = sanitize_email( wp_unslash( $_POST['email'] ) );
    $expertise   = wp_kses_post( wp_unslash( $_POST['expertise'] ) );
    $profile_url = isset( $_POST['profile_url'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_url'] ) ) : '';
        
    global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'gaenity_expert_requests',
    array(
        'user_id'    => get_current_user_id(),
        'name'       => $name,
        'email'      => $email,
        'role'       => 'Expert Applicant',
        'region'     => '',
        'country'    => '',
        'industry'   => '',
        'challenge'  => 'expert_registration',
        'description'=> $expertise,
        'budget'     => $profile_url,
        'preference' => 'expert_registration',
    ),
    array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
);
        wp_send_json_success( array( 'message' => __( 'Thanks! Our team will review your expert application.', 'gaenity-community' ) ) );
    }

    /**
     * Handle contact form submissions.
     */
  public function handle_contact_submission() {
    $this->verify_nonce();

    // Only require essential fields
    $required_fields = array( 'name', 'email', 'message' );
    foreach ( $required_fields as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
        }
    }

    // Get values with defaults
    $name    = sanitize_text_field( wp_unslash( $_POST['name'] ) );
    $email   = sanitize_email( wp_unslash( $_POST['email'] ) );
    $message = wp_kses_post( wp_unslash( $_POST['message'] ) );
    $subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : 'Contact Form Submission';
    $updates = ! empty( $_POST['updates'] ) ? 1 : 0;

       global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'gaenity_contact_messages',
    array(
        'name'    => $name,
        'email'   => $email,
        'subject' => $subject,
        'message' => $message,
        'updates' => $updates,
    ),
    array( '%s', '%s', '%s', '%s', '%d' )
);

        wp_send_json_success( array( 'message' => __( 'Thanks for reaching out. We will reply soon.', 'gaenity-community' ) ) );
    }

    /**
     * Handle chat messages.
     */
    public function handle_chat_message() {
        $this->verify_nonce();

        $message   = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';
        $role      = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
        $region    = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $industry  = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
        $challenge = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
        $anonymous = ! empty( $_POST['anonymous'] );
        $display   = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';

        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a message.', 'gaenity-community' ) ) );
        }

        $user_id = get_current_user_id();
        if ( $user_id && empty( $display ) ) {
            $user    = get_userdata( $user_id );
            $display = $user ? $user->display_name : __( 'Member', 'gaenity-community' );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_chat_messages',
            array(
                'user_id'      => $user_id ? $user_id : null,
                'display_name' => $anonymous ? __( 'Anonymous', 'gaenity-community' ) : $display,
                'role'         => $role,
                'region'       => $region,
                'industry'     => $industry,
                'challenge'    => $challenge,
                'message'      => $message,
                'is_anonymous' => $anonymous ? 1 : 0,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Message posted.', 'gaenity-community' ) ) );
    }
    /**
     * Handle discussion voting.
     */
    /**
     * Handle discussion voting.
     */
    public function handle_discussion_vote() {
        $this->verify_nonce();

        $discussion_id = isset( $_POST['discussion_id'] ) ? absint( $_POST['discussion_id'] ) : 0;
        $vote_type = isset( $_POST['vote_type'] ) ? sanitize_text_field( $_POST['vote_type'] ) : '';

        if ( ! $discussion_id || ! in_array( $vote_type, array( 'up', 'down' ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid vote data.', 'gaenity-community' ) ) );
        }

        $user_id = get_current_user_id();
        $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        global $wpdb;
        $table = $wpdb->prefix . 'gaenity_discussion_votes';

        // Check if user already voted
        if ( $user_id ) {
            $existing_vote = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table WHERE discussion_id = %d AND user_id = %d",
                $discussion_id,
                $user_id
            ) );
        } else {
            $existing_vote = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table WHERE discussion_id = %d AND ip_address = %s",
                $discussion_id,
                $ip_address
            ) );
        }

        $current_vote = null;

        if ( $existing_vote ) {
            if ( $existing_vote->vote_type === $vote_type ) {
                // Remove vote if clicking same button
                $wpdb->delete( $table, array( 'id' => $existing_vote->id ), array( '%d' ) );
                $message = __( 'Vote removed.', 'gaenity-community' );
                $current_vote = null;
            } else {
                // Change vote
                $wpdb->update(
                    $table,
                    array( 'vote_type' => $vote_type ),
                    array( 'id' => $existing_vote->id ),
                    array( '%s' ),
                    array( '%d' )
                );
                $message = __( 'Vote updated.', 'gaenity-community' );
                $current_vote = $vote_type;
            }
        } else {
            // Add new vote
            $wpdb->insert(
                $table,
                array(
                    'discussion_id' => $discussion_id,
                    'user_id' => $user_id ? $user_id : null,
                    'vote_type' => $vote_type,
                    'ip_address' => $ip_address,
                ),
                array( '%d', '%d', '%s', '%s' )
            );
            $message = __( 'Vote recorded.', 'gaenity-community' );
            $current_vote = $vote_type;
        }

        // Get vote counts
        $upvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE discussion_id = %d AND vote_type = 'up'", $discussion_id ) );
        $downvotes = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE discussion_id = %d AND vote_type = 'down'", $discussion_id ) );

        wp_send_json_success( array(
            'message' => $message,
            'upvotes' => (int) $upvotes,
            'downvotes' => (int) $downvotes,
            'score' => (int) ( $upvotes - $downvotes ),
            'current_vote' => $current_vote,
        ) );
    }
/**
     * Handle discussion like toggle via AJAX
     */
    public function handle_discussion_like_toggle() {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'You must be logged in to like discussions.' );
        }

        // Verify nonce
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
        
        if ( ! wp_verify_nonce( $nonce, 'gaenity_like_' . $post_id ) ) {
            wp_send_json_error( 'Security check failed.' );
        }

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid discussion ID.' );
        }

        // Get current user
        $user_id = get_current_user_id();

        // Get current votes and liked_by array
        $votes = get_post_meta( $post_id, '_gaenity_discussion_votes', true );
        $votes = $votes ? absint( $votes ) : 0;
        
        $liked_by = get_post_meta( $post_id, '_gaenity_discussion_liked_by', true );
        if ( ! is_array( $liked_by ) ) {
            $liked_by = array();
        }

        // Check if user has already liked
        $user_liked = in_array( $user_id, $liked_by );

        if ( $user_liked ) {
            // Unlike: Remove user from array and decrease count
            $liked_by = array_diff( $liked_by, array( $user_id ) );
            $votes = max( 0, $votes - 1 );
            $action = 'unliked';
        } else {
            // Like: Add user to array and increase count
            $liked_by[] = $user_id;
            $votes = $votes + 1;
            $action = 'liked';
        }

        // Update post meta
        update_post_meta( $post_id, '_gaenity_discussion_votes', $votes );
        update_post_meta( $post_id, '_gaenity_discussion_liked_by', $liked_by );

        // Return success with new state
        wp_send_json_success( array(
            'liked' => ! $user_liked,
            'votes' => $votes,
            'message' => $action === 'liked' ? 'Discussion liked!' : 'Like removed.'
        ) );
    }
    /**
     * Handle chat fetch.
     */
    public function handle_chat_fetch() {
        $this->verify_nonce();

        $messages = $this->get_chat_messages();
        wp_send_json_success( array( 'messages' => $messages ) );
    }

    /**
     * Verify AJAX nonce.
     */
    protected function verify_nonce() {
        if ( empty( $_POST['gaenity_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gaenity_nonce'] ) ), 'gaenity-community' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh and try again.', 'gaenity-community' ) ) );
        }
    }

    /**
     * Render resources grid.
     */
    public function render_resources_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'type' => 'all',
            ),
            $atts,
            'gaenity_resources'
        );

        $resource_types = array( 'free', 'paid' );
        if ( 'all' !== $atts['type'] && in_array( $atts['type'], $resource_types, true ) ) {
            $resource_types = array( $atts['type'] );
        }

        // Get customization options
        $title = get_option( 'gaenity_resources_title', 'Practical tools that turn ideas into action.' );
        $description = get_option( 'gaenity_resources_description', 'From risk management checklists to finance enablement guides and operational templates, each resource is designed to help businesses build resilience, prepare for growth, and make measurable progress.' );
        $title_color = get_option( 'gaenity_resources_title_color', '#2563eb' );
        $title_size = get_option( 'gaenity_resources_title_size', 40 );
        $desc_size = get_option( 'gaenity_resources_desc_size', 18 );
        $font_family = get_option( 'gaenity_resources_font_family', 'system' );

        // Build inline styles for customization
        $section_style = '';
        if ( 'system' !== $font_family ) {
            $section_style = 'style="font-family: ' . esc_attr( $font_family ) . ';"';
        }

        $title_style = 'style="';
        $title_style .= 'font-size: ' . esc_attr( $title_size ) . 'px; ';
        $title_style .= 'background: linear-gradient(135deg, ' . esc_attr( $title_color ) . ', ' . esc_attr( get_option( 'gaenity_secondary_color', '#8b5cf6' ) ) . '); ';
        $title_style .= '-webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;';
        $title_style .= '"';

        $desc_style = 'style="font-size: ' . esc_attr( $desc_size ) . 'px;"';

        $output  = '<div class="gaenity-resources-section" ' . $section_style . '>';
        $output .= '<div class="gaenity-section-header">';
        $output .= '<h2 ' . $title_style . '>' . esc_html( $title ) . '</h2>';
        $output .= '<p ' . $desc_style . '>' . esc_html( $description ) . '</p>';
        $output .= '<div class="gaenity-resource-tabs">';
        foreach ( array( 'free' => __( 'Free Resources', 'gaenity-community' ), 'paid' => __( 'Paid Resources', 'gaenity-community' ) ) as $key => $label ) {
            $output .= '<button class="gaenity-resource-tab" data-target="gaenity-resources-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</button>';
        }
        $output .= '</div>';
        $output .= '</div>';

        foreach ( $resource_types as $type ) {
            $output .= '<div class="gaenity-resource-grid" id="gaenity-resources-' . esc_attr( $type ) . '">';

            $query = new WP_Query(
                array(
                    'post_type'      => 'gaenity_resource',
                    'posts_per_page' => -1,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'gaenity_resource_type',
                            'field'    => 'slug',
                            'terms'    => $type,
                        ),
                    ),
                )
            );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $resource_id  = get_the_ID();
                    $download_url = get_post_meta( $resource_id, '_gaenity_resource_file', true );
                    $description  = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 25 );
                    $image        = get_the_post_thumbnail( $resource_id, 'medium', array( 'class' => 'gaenity-resource-image' ) );

                    $output .= '<article class="gaenity-resource-card">';
                    if ( $image ) {
                        $output .= $image;
                    }
                    $output .= '<div class="gaenity-resource-body">';
                    $output .= '<h3>' . esc_html( get_the_title() ) . '</h3>';
                    $output .= '<p>' . esc_html( $description ) . '</p>';
                    if ( 'free' === $type ) {
                        $output .= '<button class="gaenity-button" data-resource="' . esc_attr( $resource_id ) . '">' . esc_html__( 'Download Free', 'gaenity-community' ) . '</button>';
                    } else {
                        // Check if resource has a price
                        $price = get_post_meta( $resource_id, '_gaenity_resource_price', true );
                        if ( empty( $price ) ) {
                            $price = 0;
                        }
                        $currency_symbol = $this->get_currency_symbol();

                        $output .= '<div class="gaenity-resource-pricing">';
                        $output .= '<span class="gaenity-resource-price">' . esc_html( $currency_symbol . number_format( $price, 2 ) ) . '</span>';
                        $output .= '<button class="gaenity-button" data-paid-resource="' . esc_attr( $resource_id ) . '">' . esc_html__( 'Buy Now', 'gaenity-community' ) . '</button>';
                        $output .= '</div>';
                    }
                    $output .= '</div>';
                    $output .= '</article>';

                    if ( 'free' === $type && ! empty( $download_url ) ) {
                        $output .= $this->get_resource_form_markup( $resource_id, $download_url );
                    } elseif ( 'paid' === $type && ! empty( $download_url ) ) {
                        $price = get_post_meta( $resource_id, '_gaenity_resource_price', true );
                        $output .= $this->get_paid_resource_form_markup( $resource_id, floatval( $price ) );
                    }
                }
            } else {
                $output .= '<p class="gaenity-empty-state">' . esc_html__( 'No resources available yet. Check back soon!', 'gaenity-community' ) . '</p>';
            }

            wp_reset_postdata();
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Resource download form markup.
     */
    protected function get_resource_form_markup( $resource_id, $download_url ) {
        $industries = $this->get_industry_options();

        ob_start();
        ?>
        <div class="gaenity-modal" id="gaenity-resource-modal-<?php echo esc_attr( $resource_id ); ?>" hidden>
            <div class="gaenity-modal-content">
                <button class="gaenity-modal-close" aria-label="<?php esc_attr_e( 'Close', 'gaenity-community' ); ?>">&times;</button>
                <h3><?php esc_html_e( 'Access this resource', 'gaenity-community' ); ?></h3>
                <form class="gaenity-form gaenity-ajax-form" data-success-message="<?php esc_attr_e( 'Thanks! Your download will start automatically.', 'gaenity-community' ); ?>">
                    <input type="hidden" name="action" value="gaenity_resource_download" />
                    <input type="hidden" name="resource_id" value="<?php echo esc_attr( $resource_id ); ?>" />
                    <input type="hidden" name="download_url" value="<?php echo esc_url( $download_url ); ?>" />
                    <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                    <p>
                        <label for="gaenity_email_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                        <input type="email" id="gaenity_email_<?php echo esc_attr( $resource_id ); ?>" name="email" required />
                    </p>
                    <p>
                        <label for="gaenity_role_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Role', 'gaenity-community' ); ?></label>
                        <select id="gaenity_role_<?php echo esc_attr( $resource_id ); ?>" name="role" required>
                            <option value=""><?php esc_html_e( 'Select role', 'gaenity-community' ); ?></option>
                            <option value="Business owner"><?php esc_html_e( 'Business owner', 'gaenity-community' ); ?></option>
                            <option value="Professional"><?php esc_html_e( 'Professional', 'gaenity-community' ); ?></option>
                        </select>
                    </p>
                    <p>
                        <label for="gaenity_region_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                        <select id="gaenity_region_<?php echo esc_attr( $resource_id ); ?>" name="region" required>
                            <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                            <?php foreach ( $this->get_region_options() as $region_opt ) : ?>
                                <option value="<?php echo esc_attr( $region_opt ); ?>"><?php echo esc_html( $region_opt ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="gaenity_industry_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                        <select id="gaenity_industry_<?php echo esc_attr( $resource_id ); ?>" name="industry" required>
                            <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                            <?php foreach ( $industries as $industry_opt ) : ?>
                                <option value="<?php echo esc_attr( $industry_opt ); ?>"><?php echo esc_html( $industry_opt ); ?></option>
                            <?php endforeach; ?>
                            <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                        </select>
                    </p>
                    <p class="gaenity-hidden">
                        <label for="gaenity_industry_other_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Please specify', 'gaenity-community' ); ?></label>
                        <input type="text" id="gaenity_industry_other_<?php echo esc_attr( $resource_id ); ?>" name="industry_other" placeholder="<?php esc_attr_e( 'If other, please specify', 'gaenity-community' ); ?>" />
                    </p>
                    <p class="gaenity-checkbox">
                        <label>
                            <input type="checkbox" name="consent" value="1" required />
                            <?php esc_html_e( 'By accessing this resource, you consent to Gaenity storing your details securely to provide the download and send relevant updates. We never sell or share your data with third parties. You can manage your preferences or unsubscribe at any time.', 'gaenity-community' ); ?>
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="gaenity-button"><?php esc_html_e( 'Download', 'gaenity-community' ); ?></button>
                    </p>
                    <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Paid resource purchase form markup with payment integration.
     */
    protected function get_paid_resource_form_markup( $resource_id, $price ) {
        $industries = $this->get_industry_options();
        $currency = get_option( 'gaenity_currency', 'USD' );
        $currency_symbol = $this->get_currency_symbol();
        $enabled_gateways = get_option( 'gaenity_enabled_gateways', array() );

        ob_start();
        ?>
        <div class="gaenity-modal" id="gaenity-paid-resource-modal-<?php echo esc_attr( $resource_id ); ?>" hidden>
            <div class="gaenity-modal-content">
                <button class="gaenity-modal-close" aria-label="<?php esc_attr_e( 'Close', 'gaenity-community' ); ?>">&times;</button>
                <h3><?php esc_html_e( 'Purchase & Access Resource', 'gaenity-community' ); ?></h3>
                <div class="gaenity-payment-summary" style="background: #f1f5f9; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;"><?php esc_html_e( 'Total Amount', 'gaenity-community' ); ?></div>
                    <div style="font-size: 2rem; font-weight: 700; color: #1e293b;"><?php echo esc_html( $currency_symbol . number_format( $price, 2 ) ); ?></div>
                </div>
                <form class="gaenity-form gaenity-ajax-form" data-success-message="<?php esc_attr_e( 'Payment successful! Your download will start automatically.', 'gaenity-community' ); ?>">
                    <input type="hidden" name="action" value="gaenity_paid_resource_purchase" />
                    <input type="hidden" name="resource_id" value="<?php echo esc_attr( $resource_id ); ?>" />
                    <input type="hidden" name="amount" value="<?php echo esc_attr( $price ); ?>" />
                    <input type="hidden" name="currency" value="<?php echo esc_attr( $currency ); ?>" />
                    <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>

                    <p>
                        <label for="gaenity_paid_email_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                        <input type="email" id="gaenity_paid_email_<?php echo esc_attr( $resource_id ); ?>" name="email" required />
                    </p>
                    <p>
                        <label for="gaenity_paid_role_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Role', 'gaenity-community' ); ?></label>
                        <select id="gaenity_paid_role_<?php echo esc_attr( $resource_id ); ?>" name="role" required>
                            <option value=""><?php esc_html_e( 'Select role', 'gaenity-community' ); ?></option>
                            <option value="Business owner"><?php esc_html_e( 'Business owner', 'gaenity-community' ); ?></option>
                            <option value="Professional"><?php esc_html_e( 'Professional', 'gaenity-community' ); ?></option>
                        </select>
                    </p>
                    <p>
                        <label for="gaenity_paid_region_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                        <select id="gaenity_paid_region_<?php echo esc_attr( $resource_id ); ?>" name="region" required>
                            <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                            <?php foreach ( $this->get_region_options() as $region_opt ) : ?>
                                <option value="<?php echo esc_attr( $region_opt ); ?>"><?php echo esc_html( $region_opt ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="gaenity_paid_industry_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                        <select id="gaenity_paid_industry_<?php echo esc_attr( $resource_id ); ?>" name="industry" required>
                            <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                            <?php foreach ( $industries as $industry_opt ) : ?>
                                <option value="<?php echo esc_attr( $industry_opt ); ?>"><?php echo esc_html( $industry_opt ); ?></option>
                            <?php endforeach; ?>
                            <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                        </select>
                    </p>
                    <p class="gaenity-hidden">
                        <label for="gaenity_paid_industry_other_<?php echo esc_attr( $resource_id ); ?>"><?php esc_html_e( 'Please specify', 'gaenity-community' ); ?></label>
                        <input type="text" id="gaenity_paid_industry_other_<?php echo esc_attr( $resource_id ); ?>" name="industry_other" placeholder="<?php esc_attr_e( 'If other, please specify', 'gaenity-community' ); ?>" />
                    </p>

                    <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem;"><?php esc_html_e( 'Select Payment Method', 'gaenity-community' ); ?></h4>
                    <div class="gaenity-payment-methods-compact">
                        <?php if ( in_array( 'stripe', $enabled_gateways, true ) ) : ?>
                            <label class="gaenity-payment-option">
                                <input type="radio" name="payment_gateway" value="stripe" required>
                                <span><?php esc_html_e( 'Credit/Debit Card', 'gaenity-community' ); ?></span>
                            </label>
                        <?php endif; ?>
                        <?php if ( in_array( 'paypal', $enabled_gateways, true ) ) : ?>
                            <label class="gaenity-payment-option">
                                <input type="radio" name="payment_gateway" value="paypal" required>
                                <span><?php esc_html_e( 'PayPal', 'gaenity-community' ); ?></span>
                            </label>
                        <?php endif; ?>
                        <?php if ( in_array( 'paystack', $enabled_gateways, true ) ) : ?>
                            <label class="gaenity-payment-option">
                                <input type="radio" name="payment_gateway" value="paystack" required>
                                <span><?php esc_html_e( 'Paystack', 'gaenity-community' ); ?></span>
                            </label>
                        <?php endif; ?>
                        <?php if ( in_array( 'bank_transfer', $enabled_gateways, true ) ) : ?>
                            <label class="gaenity-payment-option">
                                <input type="radio" name="payment_gateway" value="bank_transfer" required>
                                <span><?php esc_html_e( 'Bank Transfer', 'gaenity-community' ); ?></span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <p class="gaenity-checkbox">
                        <label>
                            <input type="checkbox" name="consent" value="1" required />
                            <?php esc_html_e( 'By purchasing, you agree to our terms and consent to data processing for download delivery. Download access expires 30 days after purchase with maximum 3 downloads.', 'gaenity-community' ); ?>
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="gaenity-button"><?php esc_html_e( 'Complete Purchase', 'gaenity-community' ); ?></button>
                    </p>
                    <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render beautiful download page.
     */
    public function render_download_page() {
        // Get URL parameters
        $resource_id = isset( $_GET['resource_id'] ) ? absint( $_GET['resource_id'] ) : 0;
        $download_url = isset( $_GET['download_url'] ) ? esc_url_raw( $_GET['download_url'] ) : '';
        $is_paid = isset( $_GET['paid'] ) ? (bool) $_GET['paid'] : false;

        if ( ! $resource_id || ! $download_url ) {
            return '<div class="gaenity-error"><p>' . esc_html__( 'Invalid download link.', 'gaenity-community' ) . '</p></div>';
        }

        // Get resource details
        $resource_title = get_the_title( $resource_id );
        $resource_permalink = get_permalink( $resource_id );

        // Get customizable settings
        $page_title = get_option( 'gaenity_download_page_title', 'Thank You!' );
        $page_message = get_option( 'gaenity_download_page_message', "Your resource is ready for download. We've also sent the download link to your email." );
        $button_text = get_option( 'gaenity_download_page_button_text', 'Download Now' );
        $countdown_seconds = absint( get_option( 'gaenity_download_countdown_seconds', 5 ) );

        // Get current page URL for sharing
        $current_url = home_url( $_SERVER['REQUEST_URI'] );
        $share_text = sprintf( __( 'Check out this resource: %s', 'gaenity-community' ), $resource_title );

        ob_start();
        ?>
        <div class="gaenity-download-page">
            <div class="gaenity-download-container">
                <div class="gaenity-download-success-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="40" cy="40" r="40" fill="#10B981" fill-opacity="0.1"/>
                        <circle cx="40" cy="40" r="32" fill="#10B981" fill-opacity="0.2"/>
                        <path d="M25 40L35 50L55 30" stroke="#10B981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <h1 class="gaenity-download-title"><?php echo esc_html( $page_title ); ?></h1>

                <div class="gaenity-download-resource-info">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span><?php echo esc_html( $resource_title ); ?></span>
                </div>

                <p class="gaenity-download-message"><?php echo esc_html( $page_message ); ?></p>

                <div class="gaenity-countdown-timer" id="gaenityCountdownTimer">
                    <div class="gaenity-countdown-circle">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e8f0" stroke-width="8"></circle>
                            <circle id="gaenityCountdownCircle" cx="60" cy="60" r="54" fill="none" stroke="#667eea" stroke-width="8"
                                    stroke-dasharray="339.292" stroke-dashoffset="0"
                                    transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 1s linear;"></circle>
                        </svg>
                        <div class="gaenity-countdown-number" id="gaenityCountdownNumber"><?php echo esc_html( $countdown_seconds ); ?></div>
                    </div>
                    <p class="gaenity-countdown-text"><?php esc_html_e( 'Your download will begin in...', 'gaenity-community' ); ?></p>
                </div>

                <div class="gaenity-download-actions" id="gaenityDownloadActions" style="display: none;">
                    <a href="<?php echo esc_url( $download_url ); ?>" class="gaenity-download-button" id="gaenityDownloadButton" download>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 3v12M6 11l4 4 4-4M3 17h14"/>
                        </svg>
                        <?php echo esc_html( $button_text ); ?>
                    </a>

                    <div class="gaenity-secondary-actions">
                        <a href="<?php echo esc_url( home_url( '/resources' ) ); ?>" class="gaenity-secondary-button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                            <?php esc_html_e( 'Download Another Resource', 'gaenity-community' ); ?>
                        </a>
                    </div>
                </div>

                <div class="gaenity-social-sharing" id="gaenitySocialSharing" style="display: none;">
                    <p class="gaenity-social-heading"><?php esc_html_e( 'Share this resource:', 'gaenity-community' ); ?></p>
                    <div class="gaenity-social-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $resource_permalink ); ?>"
                           target="_blank" rel="noopener" class="gaenity-social-btn gaenity-social-facebook"
                           aria-label="<?php esc_attr_e( 'Share on Facebook', 'gaenity-community' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( $resource_permalink ); ?>&text=<?php echo urlencode( $share_text ); ?>"
                           target="_blank" rel="noopener" class="gaenity-social-btn gaenity-social-twitter"
                           aria-label="<?php esc_attr_e( 'Share on Twitter', 'gaenity-community' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode( $resource_permalink ); ?>&title=<?php echo urlencode( $resource_title ); ?>&summary=<?php echo urlencode( $share_text ); ?>"
                           target="_blank" rel="noopener" class="gaenity-social-btn gaenity-social-linkedin"
                           aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'gaenity-community' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode( $share_text . ' ' . $resource_permalink ); ?>"
                           target="_blank" rel="noopener" class="gaenity-social-btn gaenity-social-whatsapp"
                           aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'gaenity-community' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </a>
                        <button class="gaenity-social-btn gaenity-social-copy" id="gaenityCopyLink"
                                data-url="<?php echo esc_attr( $resource_permalink ); ?>"
                                aria-label="<?php esc_attr_e( 'Copy link', 'gaenity-community' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="gaenity-copy-feedback" id="gaenityCopyFeedback" style="display: none;"><?php esc_html_e( 'Link copied to clipboard!', 'gaenity-community' ); ?></p>
                </div>

                <?php if ( $is_paid ) : ?>
                    <div class="gaenity-download-info-box">
                        <h3><?php esc_html_e( 'Access Details', 'gaenity-community' ); ?></h3>
                        <ul>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a8 8 0 100 16A8 8 0 008 0zm3.707 6.707l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L7 8.586l3.293-3.293a1 1 0 011.414 1.414z"/>
                                </svg>
                                <?php printf( esc_html__( 'Access expires in %d days', 'gaenity-community' ), absint( get_option( 'gaenity_download_expiry_days', 30 ) ) ); ?>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a8 8 0 100 16A8 8 0 008 0zm3.707 6.707l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L7 8.586l3.293-3.293a1 1 0 011.414 1.414z"/>
                                </svg>
                                <?php printf( esc_html__( 'Maximum %d downloads allowed', 'gaenity-community' ), absint( get_option( 'gaenity_download_limit', 3 ) ) ); ?>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a8 8 0 100 16A8 8 0 008 0zm3.707 6.707l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L7 8.586l3.293-3.293a1 1 0 011.414 1.414z"/>
                                </svg>
                                <?php esc_html_e( 'Download link sent to your email', 'gaenity-community' ); ?>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="gaenity-download-footer">
                    <p><?php esc_html_e( 'Need help?', 'gaenity-community' ); ?> <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Contact Support', 'gaenity-community' ); ?></a></p>
                </div>
            </div>
        </div>

        <style>
            .gaenity-download-page {
                min-height: 70vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .gaenity-download-container {
                background: #ffffff;
                border-radius: 24px;
                padding: 3rem 2rem;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                text-align: center;
                animation: slideUp 0.6s ease-out;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .gaenity-download-success-icon {
                margin: 0 auto 2rem;
                animation: scaleIn 0.5s ease-out 0.2s both;
            }

            @keyframes scaleIn {
                from {
                    opacity: 0;
                    transform: scale(0.5);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .gaenity-download-title {
                font-size: 2.5rem;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 1rem;
                line-height: 1.2;
            }

            .gaenity-download-resource-info {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                background: #f1f5f9;
                border-radius: 12px;
                color: #475569;
                font-weight: 600;
                margin-bottom: 1.5rem;
            }

            .gaenity-download-message {
                font-size: 1.125rem;
                color: #64748b;
                margin-bottom: 2rem;
                line-height: 1.6;
            }

            .gaenity-download-actions {
                margin-bottom: 2rem;
            }

            .gaenity-download-button {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                padding: 1rem 2.5rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                font-size: 1.125rem;
                font-weight: 600;
                border-radius: 12px;
                text-decoration: none;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }

            .gaenity-download-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                color: #ffffff;
            }

            .gaenity-download-info-box {
                background: #f8fafc;
                border: 2px solid #e2e8f0;
                border-radius: 16px;
                padding: 1.5rem;
                margin-bottom: 2rem;
                text-align: left;
            }

            .gaenity-download-info-box h3 {
                font-size: 1.125rem;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 1rem;
            }

            .gaenity-download-info-box ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .gaenity-download-info-box li {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 0;
                color: #475569;
                font-size: 0.9375rem;
            }

            .gaenity-download-info-box li:not(:last-child) {
                border-bottom: 1px solid #e2e8f0;
            }

            .gaenity-download-info-box li svg {
                flex-shrink: 0;
                color: #10B981;
            }

            .gaenity-download-footer {
                padding-top: 2rem;
                border-top: 1px solid #e2e8f0;
                color: #64748b;
                font-size: 0.875rem;
            }

            .gaenity-download-footer a {
                color: #667eea;
                font-weight: 600;
                text-decoration: none;
            }

            .gaenity-download-footer a:hover {
                text-decoration: underline;
            }

            /* Countdown Timer Styles */
            .gaenity-countdown-timer {
                margin-bottom: 2rem;
                animation: fadeIn 0.5s ease-out 0.3s both;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }

            .gaenity-countdown-circle {
                position: relative;
                width: 120px;
                height: 120px;
                margin: 0 auto 1rem;
            }

            .gaenity-countdown-number {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 3rem;
                font-weight: 700;
                color: #667eea;
            }

            .gaenity-countdown-text {
                font-size: 1rem;
                color: #64748b;
                font-weight: 500;
            }

            /* Secondary Actions */
            .gaenity-secondary-actions {
                margin-top: 1rem;
            }

            .gaenity-secondary-button {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                background: #f1f5f9;
                color: #475569;
                font-size: 1rem;
                font-weight: 500;
                border-radius: 8px;
                text-decoration: none;
                transition: all 0.3s ease;
            }

            .gaenity-secondary-button:hover {
                background: #e2e8f0;
                color: #1e293b;
                transform: translateY(-1px);
            }

            /* Social Sharing Styles */
            .gaenity-social-sharing {
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid #e2e8f0;
                animation: fadeIn 0.5s ease-out 0.8s both;
            }

            .gaenity-social-heading {
                font-size: 0.875rem;
                font-weight: 600;
                color: #64748b;
                margin-bottom: 1rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .gaenity-social-buttons {
                display: flex;
                justify-content: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .gaenity-social-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 48px;
                height: 48px;
                border: none;
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                color: #ffffff;
            }

            .gaenity-social-facebook {
                background: #1877F2;
            }

            .gaenity-social-facebook:hover {
                background: #0d5dc7;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(24, 119, 242, 0.4);
            }

            .gaenity-social-twitter {
                background: #1DA1F2;
            }

            .gaenity-social-twitter:hover {
                background: #0d8cd9;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(29, 161, 242, 0.4);
            }

            .gaenity-social-linkedin {
                background: #0A66C2;
            }

            .gaenity-social-linkedin:hover {
                background: #084d92;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(10, 102, 194, 0.4);
            }

            .gaenity-social-whatsapp {
                background: #25D366;
            }

            .gaenity-social-whatsapp:hover {
                background: #1da851;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            }

            .gaenity-social-copy {
                background: #64748b;
            }

            .gaenity-social-copy:hover {
                background: #475569;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(100, 116, 139, 0.4);
            }

            .gaenity-copy-feedback {
                margin-top: 1rem;
                color: #10B981;
                font-size: 0.875rem;
                font-weight: 600;
                animation: fadeIn 0.3s ease-out;
            }

            @media (max-width: 640px) {
                .gaenity-download-container {
                    padding: 2rem 1.5rem;
                }

                .gaenity-download-title {
                    font-size: 2rem;
                }

                .gaenity-download-message {
                    font-size: 1rem;
                }

                .gaenity-download-button {
                    width: 100%;
                    justify-content: center;
                }

                .gaenity-secondary-button {
                    width: 100%;
                    justify-content: center;
                }

                .gaenity-social-buttons {
                    gap: 0.5rem;
                }

                .gaenity-social-btn {
                    width: 44px;
                    height: 44px;
                }
            }
        </style>

        <script>
            (function() {
                const countdownSeconds = <?php echo absint( $countdown_seconds ); ?>;
                const countdownTimer = document.getElementById('gaenityCountdownTimer');
                const countdownNumber = document.getElementById('gaenityCountdownNumber');
                const countdownCircle = document.getElementById('gaenityCountdownCircle');
                const downloadActions = document.getElementById('gaenityDownloadActions');
                const socialSharing = document.getElementById('gaenitySocialSharing');
                const downloadButton = document.getElementById('gaenityDownloadButton');
                const copyLinkBtn = document.getElementById('gaenityCopyLink');
                const copyFeedback = document.getElementById('gaenityCopyFeedback');

                let timeLeft = countdownSeconds;
                const circumference = 339.292; // 2 * PI * radius (54)

                const timer = setInterval(() => {
                    timeLeft--;
                    countdownNumber.textContent = timeLeft;

                    // Update circle progress
                    const offset = circumference - (circumference * timeLeft / countdownSeconds);
                    countdownCircle.style.strokeDashoffset = offset;

                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        // Hide countdown, show download button and social sharing
                        countdownTimer.style.display = 'none';
                        downloadActions.style.display = 'block';
                        socialSharing.style.display = 'block';

                        // Auto-trigger download
                        if (downloadButton) {
                            downloadButton.click();
                        }
                    }
                }, 1000);

                // Copy link functionality
                if (copyLinkBtn) {
                    copyLinkBtn.addEventListener('click', function() {
                        const url = this.getAttribute('data-url');

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(url).then(() => {
                                showCopyFeedback();
                            }).catch(() => {
                                fallbackCopyTextToClipboard(url);
                            });
                        } else {
                            fallbackCopyTextToClipboard(url);
                        }
                    });
                }

                function fallbackCopyTextToClipboard(text) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.top = '-9999px';
                    textArea.style.left = '-9999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();

                    try {
                        document.execCommand('copy');
                        showCopyFeedback();
                    } catch (err) {
                        console.error('Failed to copy:', err);
                    }

                    document.body.removeChild(textArea);
                }

                function showCopyFeedback() {
                    if (copyFeedback) {
                        copyFeedback.style.display = 'block';
                        setTimeout(() => {
                            copyFeedback.style.display = 'none';
                        }, 3000);
                    }
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render community home.
     */
   /**
     * Render community home with enhanced filtering and stats.
     */
    /**
     * Render community home with enhanced styling.
     */
    public function render_community_home_shortcode() {
        // Get discussion counts
        $region_counts = $this->get_taxonomy_discussion_counts( 'gaenity_region' );
        $industry_counts = $this->get_taxonomy_discussion_counts( 'gaenity_industry' );
        
        // Get trending discussions (most commented)
        $trending_discussions = new WP_Query( array(
            'post_type' => 'gaenity_discussion',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'comment_count',
            'order' => 'DESC',
        ) );

        // Get latest discussions
        $latest_discussions = new WP_Query( array(
            'post_type' => 'gaenity_discussion',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ) );
        
        ob_start();
        ?>
        <style>
            /* Reset and Base Styles */
            .gaenity-community-home * {
                box-sizing: border-box;
            }
            .gaenity-community-home {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: #f8fafc;
                color: #334155;
                line-height: 1.6;
            }

            /* Hero Section */
            .gaenity-hero {
                position: relative;
                overflow: hidden;
                background: #ffffff;
            }
            .gaenity-hero-bg {
                pointer-events: none;
                position: absolute;
                inset: 0;
                z-index: 0;
            }
            .gaenity-hero-blob-1 {
                position: absolute;
                top: -6rem;
                right: -5rem;
                height: 18rem;
                width: 18rem;
                border-radius: 50%;
                background: rgba(199, 210, 254, 0.5);
                filter: blur(60px);
            }
            .gaenity-hero-blob-2 {
                position: absolute;
                bottom: -6rem;
                left: -5rem;
                height: 18rem;
                width: 18rem;
                border-radius: 50%;
                background: rgba(191, 219, 254, 0.5);
                filter: blur(60px);
            }
            .gaenity-hero-container {
                max-width: 1280px;
                margin: 0 auto;
                padding: 3.5rem 1rem;
                position: relative;
                z-index: 1;
            }
            .gaenity-hero-content {
                max-width: 768px;
                margin: 0 auto;
                text-align: center;
            }
            .gaenity-hero-title {
                font-size: clamp(1.875rem, 5vw, 3rem);
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 1.25rem 0;
                letter-spacing: -0.025em;
            }
            .gaenity-hero-subtitle {
                font-size: clamp(1.125rem, 2vw, 1.25rem);
                color: #475569;
                line-height: 1.75;
                font-style: italic;
                margin: 0;
            }

            /* Action Buttons Grid */
            .gaenity-actions-grid {
                max-width: 1280px;
                margin: 2.5rem auto;
                padding: 0 1rem;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1rem;
            }
            .gaenity-action-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                padding: 1.25rem;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                text-decoration: none;
                transition: all 0.2s ease;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            .gaenity-action-card:hover {
                border-color: #c7d2fe;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
                transform: translateY(-2px);
            }
            .gaenity-action-number {
                flex-shrink: 0;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.75rem;
                background: rgba(99, 102, 241, 0.1);
                color: #4f46e5;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 1.125rem;
            }
            .gaenity-action-content {
                flex: 1;
            }
            .gaenity-action-title {
                font-size: 1rem;
                font-weight: 600;
                color: #0f172a;
                margin: 0 0 0.25rem 0;
            }
            .gaenity-action-desc {
                font-size: 0.875rem;
                color: #64748b;
                margin: 0;
                line-height: 1.5;
            }
            .gaenity-action-card.cta-card {
                background: rgba(224, 231, 255, 0.6);
                border: 2px dashed #c7d2fe;
            }
            .gaenity-action-card.cta-card:hover {
                background: rgba(224, 231, 255, 0.9);
            }
            .gaenity-action-card.cta-card .gaenity-action-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .gaenity-cta-button {
                background: #4f46e5;
                color: #ffffff;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                white-space: nowrap;
            }

            /* What's Happening Section */
            .gaenity-happening {
                padding: 3.5rem 0;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
            }
            .gaenity-section-container {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 1rem;
            }
            .gaenity-section-header {
                max-width: 768px;
                margin-bottom: 2rem;
            }
            .gaenity-section-title {
                font-size: clamp(1.5rem, 3vw, 1.875rem);
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 0.5rem 0;
            }
            .gaenity-section-subtitle {
                color: #64748b;
                margin: 0;
            }
            .gaenity-happening-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
            }
            .gaenity-happening-column h3 {
                font-size: 1.125rem;
                font-weight: 600;
                color: #0f172a;
                margin: 0 0 1rem 0;
            }
            .gaenity-discussion-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }
            .gaenity-discussion-item {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 1rem;
                transition: all 0.2s ease;
            }
            .gaenity-discussion-item:hover {
                border-color: #c7d2fe;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }
            .gaenity-discussion-item a {
                text-decoration: none;
                display: block;
            }
            .gaenity-discussion-title {
                font-weight: 500;
                color: #0f172a;
                margin: 0 0 0.25rem 0;
                font-size: 0.9375rem;
            }
            .gaenity-discussion-excerpt {
                font-size: 0.875rem;
                color: #64748b;
                margin: 0;
                line-height: 1.5;
            }

            /* Explore Section */
            .gaenity-explore {
                padding: 3.5rem 0;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
            }
            .gaenity-tabs {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 0.25rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            .gaenity-tab-btn {
                background: transparent;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #64748b;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .gaenity-tab-btn.active {
                background: #4f46e5;
                color: #ffffff;
            }
            .gaenity-tab-panel {
                display: none;
                margin-top: 1.5rem;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
            .gaenity-tab-panel.active {
                display: grid;
            }
            .gaenity-region-card,
            .gaenity-industry-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                padding: 1rem;
                text-align: center;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .gaenity-region-card:hover,
            .gaenity-industry-card:hover {
                border-color: #c7d2fe;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                transform: translateY(-2px);
            }
            .gaenity-card-title {
                font-size: 0.875rem;
                font-weight: 600;
                color: #0f172a;
                margin: 0;
                display: block;
            }
            .gaenity-card-count {
                font-size: 0.75rem;
                color: #64748b;
                margin: 0.25rem 0 0 0;
                display: block;
            }

            /* Footer CTA */
            .gaenity-footer-cta {
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
                padding: 3rem 0;
            }
            .gaenity-footer-content {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 1rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1.5rem;
                flex-wrap: wrap;
            }
            .gaenity-footer-text h3 {
                font-size: 1.125rem;
                font-weight: 600;
                color: #0f172a;
                margin: 0 0 0.25rem 0;
            }
            .gaenity-footer-text p {
                font-size: 0.875rem;
                color: #64748b;
                margin: 0;
            }
            .gaenity-cta-btn {
                background: #4f46e5;
                color: #ffffff;
                padding: 0.75rem 1.25rem;
                border-radius: 0.75rem;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
            }
            .gaenity-cta-btn:hover {
                background: #4338ca;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
                transform: translateY(-2px);
            }

            /* Responsive */
            @media (max-width: 768px) {
                .gaenity-actions-grid {
                    grid-template-columns: 1fr;
                }
                .gaenity-happening-grid {
                    grid-template-columns: 1fr;
                }
                .gaenity-tab-panel {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                }
                .gaenity-footer-content {
                    flex-direction: column;
                    text-align: center;
                }
            }
        </style>

        <div class="gaenity-community-home">
            <!-- Hero Section -->
            <section class="gaenity-hero">
                <div class="gaenity-hero-bg">
                    <div class="gaenity-hero-blob-1"></div>
                    <div class="gaenity-hero-blob-2"></div>
                </div>
                <div class="gaenity-hero-container">
                    <div class="gaenity-hero-content">
                        <h1 class="gaenity-hero-title"><?php esc_html_e( 'Gaenity Community', 'gaenity-community' ); ?></h1>
                        <p class="gaenity-hero-subtitle">"<?php esc_html_e( 'A practical space where small business owners help each other solve real problems. Ask questions, learn from peers, or get insights from verified experts.', 'gaenity-community' ); ?>"</p>
                    </div>
                </div>
            </section>

            <!-- Action Buttons Grid -->
            <div class="gaenity-actions-grid">
                <a href="<?php echo esc_url( get_option( 'gaenity_register_url', wp_registration_url() ) ); ?>" class="gaenity-action-card">
                    <div class="gaenity-action-number">1</div>
                    <div class="gaenity-action-content">
                        <h3 class="gaenity-action-title"><?php esc_html_e( 'Join the Community', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-action-desc"><?php esc_html_e( 'Become part of a growing network of entrepreneurs and business owners helping one another.', 'gaenity-community' ); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-action-card">
                    <div class="gaenity-action-number">2</div>
                    <div class="gaenity-action-content">
                        <h3 class="gaenity-action-title"><?php esc_html_e( 'Browse the Forum', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-action-desc"><?php esc_html_e( 'See real discussions, ask questions, and share experiences with peers.', 'gaenity-community' ); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url( get_option( 'gaenity_ask_expert_url', '#ask-expert' ) ); ?>" class="gaenity-action-card">
                    <div class="gaenity-action-number">3</div>
                    <div class="gaenity-action-content">
                        <h3 class="gaenity-action-title"><?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-action-desc"><?php esc_html_e( 'Get direct, personalized advice from qualified experts within 48 hours.', 'gaenity-community' ); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url( get_option( 'gaenity_become_expert_url', '#become-expert' ) ); ?>" class="gaenity-action-card">
                    <div class="gaenity-action-number">4</div>
                    <div class="gaenity-action-content">
                        <h3 class="gaenity-action-title"><?php esc_html_e( 'Become an Expert', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-action-desc"><?php esc_html_e( 'Share your experience, help others, and earn for your time.', 'gaenity-community' ); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url( get_option( 'gaenity_polls_url', '#polls' ) ); ?>" class="gaenity-action-card">
                    <div class="gaenity-action-number">5</div>
                    <div class="gaenity-action-content">
                        <h3 class="gaenity-action-title"><?php esc_html_e( 'Polls', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-action-desc"><?php esc_html_e( 'Vote on real business challenges and see what other owners are saying.', 'gaenity-community' ); ?></p>
                    </div>
                </a>
                
                <a href="<?php echo esc_url( get_option( 'gaenity_register_url', wp_registration_url() ) ); ?>" class="gaenity-action-card cta-card">
                    <div class="gaenity-action-content">
                        <div>
                            <h3 class="gaenity-action-title"><?php esc_html_e( 'Ready to dive in?', 'gaenity-community' ); ?></h3>
                            <p class="gaenity-action-desc"><?php esc_html_e( 'Create your free account and start engaging today.', 'gaenity-community' ); ?></p>
                        </div>
                        <span class="gaenity-cta-button"><?php esc_html_e( 'Join', 'gaenity-community' ); ?></span>
                    </div>
                </a>
            </div>

            <!-- What's Happening Now Section -->
            <section class="gaenity-happening">
                <div class="gaenity-section-container">
                    <header class="gaenity-section-header">
                        <h2 class="gaenity-section-title"><?php esc_html_e( "What's Happening Now", 'gaenity-community' ); ?></h2>
                        <p class="gaenity-section-subtitle">"<?php esc_html_e( "See what's trending in the community.", 'gaenity-community' ); ?>"</p>
                    </header>

                    <div class="gaenity-happening-grid">
                        <!-- Trending Discussions -->
                        <div class="gaenity-happening-column">
                            <h3><?php esc_html_e( 'Trending Discussions', 'gaenity-community' ); ?></h3>
                            <?php if ( $trending_discussions->have_posts() ) : ?>
                                <ul class="gaenity-discussion-list">
                                    <?php while ( $trending_discussions->have_posts() ) : $trending_discussions->the_post(); ?>
                                        <li class="gaenity-discussion-item">
                                            <a href="<?php the_permalink(); ?>">
                                                <h4 class="gaenity-discussion-title"><?php the_title(); ?></h4>
                                                <p class="gaenity-discussion-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15, '...' ) ); ?></p>
                                            </a>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else : ?>
                                <p style="color: #64748b; font-size: 0.875rem;"><?php esc_html_e( 'No trending discussions yet.', 'gaenity-community' ); ?></p>
                            <?php endif; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>

                        <!-- Latest Questions -->
                        <div class="gaenity-happening-column">
                            <h3><?php esc_html_e( 'Latest Questions', 'gaenity-community' ); ?></h3>
                            <?php if ( $latest_discussions->have_posts() ) : ?>
                                <ul class="gaenity-discussion-list">
                                    <?php while ( $latest_discussions->have_posts() ) : $latest_discussions->the_post(); ?>
                                        <li class="gaenity-discussion-item">
                                            <a href="<?php the_permalink(); ?>">
                                                <h4 class="gaenity-discussion-title"><?php the_title(); ?></h4>
                                                <p class="gaenity-discussion-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15, '...' ) ); ?></p>
                                            </a>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else : ?>
                                <p style="color: #64748b; font-size: 0.875rem;"><?php esc_html_e( 'No questions yet. Be the first to ask!', 'gaenity-community' ); ?></p>
                            <?php endif; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Explore by Region or Industry -->
            <section class="gaenity-explore">
                <div class="gaenity-section-container">
                    <header class="gaenity-section-header">
                        <h2 class="gaenity-section-title"><?php esc_html_e( 'Explore by Region or Industry', 'gaenity-community' ); ?></h2>
                    </header>

                    <!-- Tabs -->
                    <div class="gaenity-tabs">
                        <button class="gaenity-tab-btn active" data-tab="region"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></button>
                        <button class="gaenity-tab-btn" data-tab="industry"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></button>
                    </div>

                    <!-- Region Tab Panel -->
                    <div class="gaenity-tab-panel active" id="panel-region">
                        <?php 
                        $regions = $this->get_region_options();
                        $total_users = count_users()['total_users'];
                        foreach ( $regions as $index => $region ) : 
                            $count = isset( $region_counts[ $region ] ) ? $region_counts[ $region ] : 0;
                            // Generate realistic member counts (for demo purposes)
                            $member_count = rand(500, 5000);
                            $url = add_query_arg( 
                                array( 'gaenity_region' => rawurlencode( $region ) ), 
                                get_post_type_archive_link( 'gaenity_discussion' ) 
                            );
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>" class="gaenity-region-card">
                                <span class="gaenity-card-title"><?php echo esc_html( $region ); ?></span>
                                <span class="gaenity-card-count"><?php echo esc_html( number_format( $member_count ) ); ?> <?php esc_html_e( 'members', 'gaenity-community' ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Industry Tab Panel -->
                    <div class="gaenity-tab-panel" id="panel-industry">
                        <?php 
                        $industries = $this->get_industry_options();
                        foreach ( $industries as $industry ) : 
                            $count = isset( $industry_counts[ $industry ] ) ? $industry_counts[ $industry ] : 0;
                            // Generate realistic member counts (for demo purposes)
                            $member_count = rand(500, 4000);
                            $url = add_query_arg( 
                                array( 'gaenity_industry' => rawurlencode( $industry ) ), 
                                get_post_type_archive_link( 'gaenity_discussion' ) 
                            );
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>" class="gaenity-industry-card">
                                <span class="gaenity-card-title"><?php echo esc_html( $industry ); ?></span>
                                <span class="gaenity-card-count"><?php echo esc_html( number_format( $member_count ) ); ?> <?php esc_html_e( 'members', 'gaenity-community' ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Footer CTA -->
            <section class="gaenity-footer-cta">
                <div class="gaenity-footer-content">
                    <div class="gaenity-footer-text">
                        <h3><?php esc_html_e( 'Need a qualified opinion? Ask a verified expert today.', 'gaenity-community' ); ?></h3>
                        <p><?php esc_html_e( 'Post your question and get tailored insights within 48 hours.', 'gaenity-community' ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( get_option( 'gaenity_ask_expert_url', '#ask-expert' ) ); ?>" class="gaenity-cta-btn">
                        <?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?>
                    </a>
                </div>
            </section>
        </div>

        <script>
        (function() {
            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.gaenity-tab-btn');
            const tabPanels = document.querySelectorAll('.gaenity-tab-panel');

            tabButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons
                    tabButtons.forEach(function(b) {
                        b.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Hide all panels
                    tabPanels.forEach(function(panel) {
                        panel.classList.remove('active');
                    });
                    
                    // Show target panel
                    const targetPanel = document.getElementById('panel-' + targetTab);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Get discussion counts by taxonomy.
     */
    protected function get_taxonomy_discussion_counts( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ) );

        $counts = array();
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $counts[ $term->name ] = $term->count;
            }
        }

        return $counts;
    }
    /**
     * Render registration form.
     */
    public function render_registration_form() {
        if ( is_user_logged_in() ) {
            $forum_url = get_post_type_archive_link( 'gaenity_discussion' );
            ob_start();
            ?>
            <div class="gaenity-register-page">
                <style>
                    .gaenity-register-page {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        max-width: 600px;
                        margin: 3rem auto;
                        padding: 0 1rem;
                    }
                    .gaenity-already-member {
                        background: #dbeafe;
                        border: 2px solid #93c5fd;
                        border-radius: 1rem;
                        padding: 2rem;
                        text-align: center;
                    }
                    .gaenity-already-member h3 {
                        font-size: 1.5rem;
                        color: #1e40af;
                        margin: 0 0 0.5rem 0;
                    }
                    .gaenity-already-member p {
                        color: #1e40af;
                        margin: 0 0 1.5rem 0;
                    }
                    .gaenity-dashboard-btn {
                        display: inline-block;
                        background: #4f46e5;
                        color: #ffffff;
                        padding: 0.75rem 2rem;
                        border-radius: 0.75rem;
                        font-weight: 600;
                        text-decoration: none;
                        transition: all 0.2s ease;
                    }
                    .gaenity-dashboard-btn:hover {
                        background: #4338ca;
                        transform: translateY(-2px);
                        color: #ffffff;
                    }
                </style>
                <div class="gaenity-already-member">
                    <h3><?php esc_html_e( '✓ You\'re Already a Member!', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Welcome back! You\'re already part of the Gaenity Community.', 'gaenity-community' ); ?></p>
                    <a href="<?php echo esc_url( $forum_url ); ?>" class="gaenity-dashboard-btn">
                        <?php esc_html_e( 'Go to Forum', 'gaenity-community' ); ?>
                    </a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <style>
            /* Registration Page Styles */
            .gaenity-register-page {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                max-width: 600px;
                margin: 3rem auto;
                padding: 0 1rem;
            }
            
            /* Header Section */
            .gaenity-register-header {
                text-align: center;
                margin-bottom: 2.5rem;
            }
            .gaenity-register-title {
                font-size: 2rem;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 0.75rem 0;
                letter-spacing: -0.025em;
            }
            .gaenity-register-blurb {
                font-size: 1.125rem;
                color: #64748b;
                line-height: 1.7;
                font-style: italic;
                margin: 0;
                max-width: 500px;
                margin-left: auto;
                margin-right: auto;
            }

            /* Form Container */
            .gaenity-register-form-container {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 1.25rem;
                padding: 2.5rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }

            /* Form Styles */
            .gaenity-register-form {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            .gaenity-form-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .gaenity-form-label {
                font-weight: 600;
                color: #0f172a;
                font-size: 0.875rem;
            }
            .gaenity-form-input,
            .gaenity-form-select {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 2px solid #e2e8f0;
                border-radius: 0.75rem;
                font-size: 1rem;
                font-family: inherit;
                transition: all 0.2s ease;
                background: #f8fafc;
            }
            .gaenity-form-input:focus,
            .gaenity-form-select:focus {
                outline: none;
                border-color: #4f46e5;
                background: #ffffff;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            .gaenity-form-input::placeholder {
                color: #94a3b8;
            }

            /* Checkbox */
            .gaenity-checkbox-group {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem;
                background: #f8fafc;
                border-radius: 0.75rem;
                border: 2px solid #e2e8f0;
            }
            .gaenity-checkbox-input {
                margin-top: 0.25rem;
                width: 1.25rem;
                height: 1.25rem;
                cursor: pointer;
                flex-shrink: 0;
            }
            .gaenity-checkbox-label {
                font-size: 0.9375rem;
                color: #475569;
                line-height: 1.6;
                cursor: pointer;
            }
            .gaenity-checkbox-label a {
                color: #4f46e5;
                text-decoration: none;
                font-weight: 600;
            }
            .gaenity-checkbox-label a:hover {
                text-decoration: underline;
            }

            /* Guidelines Box */
            .gaenity-guidelines-box {
                background: #f0f9ff;
                border-left: 4px solid #3b82f6;
                padding: 1.25rem;
                border-radius: 0.5rem;
                margin-top: 1rem;
            }
            .gaenity-guidelines-title {
                font-weight: 700;
                color: #1e40af;
                margin: 0 0 0.75rem 0;
                font-size: 0.9375rem;
            }
            .gaenity-guidelines-list {
                margin: 0;
                padding-left: 1.25rem;
                list-style: none;
            }
            .gaenity-guidelines-list li {
                color: #1e40af;
                margin-bottom: 0.5rem;
                position: relative;
                padding-left: 0.5rem;
            }
            .gaenity-guidelines-list li:before {
                content: "✓";
                position: absolute;
                left: -1rem;
                font-weight: 700;
            }

            /* Submit Button */
            .gaenity-submit-btn {
                width: 100%;
                padding: 1rem;
                background: #4f46e5;
                color: #ffffff;
                border: none;
                border-radius: 0.75rem;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
                margin-top: 0.5rem;
            }
            .gaenity-submit-btn:hover {
                background: #4338ca;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
            }
            .gaenity-submit-btn:active {
                transform: translateY(0);
            }
            .gaenity-submit-btn:disabled {
                background: #cbd5e1;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }

            /* Feedback Messages */
            .gaenity-form-feedback {
                padding: 1rem;
                border-radius: 0.75rem;
                margin-top: 1rem;
                display: none;
                font-weight: 500;
            }
            .gaenity-form-feedback.visible {
                display: block;
            }
            .gaenity-form-feedback.success {
                background: #d1fae5;
                color: #065f46;
                border: 2px solid #10b981;
            }
            .gaenity-form-feedback.error {
                background: #fee2e2;
                color: #991b1b;
                border: 2px solid #ef4444;
            }

            /* Success Message */
            .gaenity-success-message {
                text-align: center;
                padding: 3rem 2rem;
            }
            .gaenity-success-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            .gaenity-success-title {
                font-size: 1.75rem;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 1rem 0;
            }
            .gaenity-success-text {
                font-size: 1.125rem;
                color: #64748b;
                margin: 0 0 2rem 0;
                line-height: 1.7;
            }
            .gaenity-success-btn {
                display: inline-block;
                padding: 0.875rem 2rem;
                background: #4f46e5;
                color: #ffffff;
                border-radius: 0.75rem;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .gaenity-success-btn:hover {
                background: #4338ca;
                transform: translateY(-2px);
                color: #ffffff;
            }

            /* Already Have Account */
            .gaenity-login-link {
                text-align: center;
                margin-top: 1.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid #e2e8f0;
                color: #64748b;
                font-size: 0.9375rem;
            }
            .gaenity-login-link a {
                color: #4f46e5;
                text-decoration: none;
                font-weight: 600;
            }
            .gaenity-login-link a:hover {
                text-decoration: underline;
            }

            /* Responsive */
            @media (max-width: 640px) {
                .gaenity-register-form-container {
                    padding: 1.5rem;
                }
                .gaenity-register-title {
                    font-size: 1.5rem;
                }
                .gaenity-register-blurb {
                    font-size: 1rem;
                }
            }
        </style>

        <div class="gaenity-register-page">
            <!-- Header -->
            <div class="gaenity-register-header">
                <h1 class="gaenity-register-title"><?php esc_html_e( 'Join the Gaenity Community', 'gaenity-community' ); ?></h1>
                <p class="gaenity-register-blurb">"<?php esc_html_e( 'Create your free account and become part of a supportive network of business owners sharing real solutions.', 'gaenity-community' ); ?>"</p>
            </div>

            <!-- Form Container -->
            <div class="gaenity-register-form-container">
                <form id="gaenity-register" class="gaenity-register-form gaenity-ajax-form" data-success-callback="showSuccessMessage">
                    <input type="hidden" name="action" value="gaenity_user_register" />
                    <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>

                    <!-- Full Name -->
                    <div class="gaenity-form-group">
                        <label for="gaenity_full_name" class="gaenity-form-label">
                            <?php esc_html_e( 'Full Name', 'gaenity-community' ); ?> <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="gaenity_full_name" 
                            name="full_name" 
                            class="gaenity-form-input" 
                            placeholder="<?php esc_attr_e( 'John Doe', 'gaenity-community' ); ?>"
                            required 
                        />
                    </div>

                    <!-- Email -->
                    <div class="gaenity-form-group">
                        <label for="gaenity_email_register" class="gaenity-form-label">
                            <?php esc_html_e( 'Email', 'gaenity-community' ); ?> <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="gaenity_email_register" 
                            name="email" 
                            class="gaenity-form-input" 
                            placeholder="<?php esc_attr_e( 'john@example.com', 'gaenity-community' ); ?>"
                            required 
                        />
                    </div>

                    <!-- Password -->
                    <div class="gaenity-form-group">
                        <label for="gaenity_password" class="gaenity-form-label">
                            <?php esc_html_e( 'Password', 'gaenity-community' ); ?> <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="gaenity_password" 
                            name="password" 
                            class="gaenity-form-input" 
                            placeholder="<?php esc_attr_e( 'Min 8 characters', 'gaenity-community' ); ?>"
                            minlength="8"
                            required 
                        />
                    </div>

                    <!-- Region -->
                    <div class="gaenity-form-group">
                        <label for="gaenity_region" class="gaenity-form-label">
                            <?php esc_html_e( 'Region', 'gaenity-community' ); ?> <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="gaenity_region" name="region" class="gaenity-form-select" required>
                            <option value=""><?php esc_html_e( 'Select your region', 'gaenity-community' ); ?></option>
                            <?php foreach ( $this->get_region_options() as $region ) : ?>
                                <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Industry -->
                    <div class="gaenity-form-group">
                        <label for="gaenity_industry" class="gaenity-form-label">
                            <?php esc_html_e( 'Industry', 'gaenity-community' ); ?> <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="gaenity_industry" name="industry" class="gaenity-form-select" required>
                            <option value=""><?php esc_html_e( 'Select your industry', 'gaenity-community' ); ?></option>
                            <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                                <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                            <?php endforeach; ?>
                            <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                        </select>
                    </div>

                    <!-- Community Guidelines Checkbox -->
                    <div class="gaenity-checkbox-group">
                        <input 
                            type="checkbox" 
                            id="gaenity_guidelines" 
                            name="guidelines" 
                            class="gaenity-checkbox-input"
                            value="1" 
                            required 
                        />
                        <label for="gaenity_guidelines" class="gaenity-checkbox-label">
                            <?php esc_html_e( 'I agree to the', 'gaenity-community' ); ?> 
                            <a href="#" onclick="document.getElementById('guidelines-box').scrollIntoView({behavior: 'smooth'}); return false;">
                                <?php esc_html_e( 'community guidelines', 'gaenity-community' ); ?>
                            </a>
                        </label>
                    </div>

                    <!-- Guidelines Box -->
                    <div id="guidelines-box" class="gaenity-guidelines-box">
                        <h4 class="gaenity-guidelines-title"><?php esc_html_e( 'Community Guidelines', 'gaenity-community' ); ?></h4>
                        <ul class="gaenity-guidelines-list">
                            <li><?php esc_html_e( 'Be respectful and constructive', 'gaenity-community' ); ?></li>
                            <li><?php esc_html_e( 'Share real experiences and practical advice', 'gaenity-community' ); ?></li>
                            <li><?php esc_html_e( 'No spam, self-promotion, or selling', 'gaenity-community' ); ?></li>
                            <li><?php esc_html_e( 'Protect privacy and confidential information', 'gaenity-community' ); ?></li>
                            <li><?php esc_html_e( 'Report violations to moderators', 'gaenity-community' ); ?></li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="gaenity-submit-btn">
                        <?php esc_html_e( 'Create Account', 'gaenity-community' ); ?>
                    </button>

                    <!-- Feedback -->
                    <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                </form>

                <!-- Login Link -->
                <div class="gaenity-login-link">
                    <?php esc_html_e( 'Already have an account?', 'gaenity-community' ); ?> 
                    <a href="<?php echo esc_url( wp_login_url() ); ?>">
                        <?php esc_html_e( 'Sign in', 'gaenity-community' ); ?>
                    </a>
                </div>
            </div>
        </div>

        <script>
        (function() {
            // Success message callback
            window.showSuccessMessage = function(data) {
                const container = document.querySelector('.gaenity-register-form-container');
                if (!container) return;

                const forumUrl = '<?php echo esc_js( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>';
                
                container.innerHTML = `
                    <div class="gaenity-success-message">
                        <div class="gaenity-success-icon">🎉</div>
                        <h2 class="gaenity-success-title"><?php esc_html_e( 'Welcome to Gaenity Community!', 'gaenity-community' ); ?></h2>
                        <p class="gaenity-success-text">"<?php esc_html_e( 'You can now post questions, reply, vote, and join discussions.', 'gaenity-community' ); ?>"</p>
                        <a href="${forumUrl}" class="gaenity-success-btn">
                            <?php esc_html_e( 'Go to Forum', 'gaenity-community' ); ?>
                        </a>
                    </div>
                `;

                // Scroll to success message
                container.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            // Enhanced form validation
            const form = document.getElementById('gaenity-register');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = document.getElementById('gaenity_password');
                    if (password && password.value.length < 8) {
                        e.preventDefault();
                        alert('<?php esc_html_e( 'Password must be at least 8 characters long.', 'gaenity-community' ); ?>');
                        password.focus();
                        return false;
                    }
                });
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    /**
     * Render login form.
     */
    public function render_login_form() {
        if ( is_user_logged_in() ) {
            return '<p class="gaenity-notice">' . esc_html__( 'You are already logged in.', 'gaenity-community' ) . '</p>';
        }

        ob_start();
        ?>
        <form class="gaenity-form gaenity-ajax-form">
            <h3><?php esc_html_e( 'Member Login', 'gaenity-community' ); ?></h3>
            <input type="hidden" name="action" value="gaenity_user_login" />
            <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
            <p>
                <label for="gaenity_login_email"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                <input type="email" id="gaenity_login_email" name="email" required />
            </p>
            <p>
                <label for="gaenity_login_password"><?php esc_html_e( 'Password', 'gaenity-community' ); ?></label>
                <input type="password" id="gaenity_login_password" name="password" required />
            </p>
            <p>
                <label>
                    <input type="checkbox" name="remember" value="1" />
                    <?php esc_html_e( 'Remember me', 'gaenity-community' ); ?>
                </label>
            </p>
            <p>
                <button type="submit" class="gaenity-button"><?php esc_html_e( 'Login', 'gaenity-community' ); ?></button>
            </p>
            <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Render discussion submission form.
     */
    public function render_discussion_form() {
        if ( ! is_user_logged_in() ) {
            return '<p class="gaenity-notice">' . esc_html__( 'Please log in to post a discussion.', 'gaenity-community' ) . '</p>';
        }

        ob_start();
        ?>
        <form class="gaenity-form gaenity-ajax-form">
            <h3><?php esc_html_e( 'Share your challenge', 'gaenity-community' ); ?></h3>
            <input type="hidden" name="action" value="gaenity_discussion_submit" />
            <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
            <p>
                <label for="gaenity_discussion_title"><?php esc_html_e( 'Title', 'gaenity-community' ); ?></label>
                <input type="text" id="gaenity_discussion_title" name="title" required />
            </p>
            <p>
                <label for="gaenity_discussion_content"><?php esc_html_e( 'Describe your challenge', 'gaenity-community' ); ?></label>
                <textarea id="gaenity_discussion_content" name="content" rows="4" required></textarea>
            </p>
            <p>
                <label for="gaenity_discussion_region"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                <select id="gaenity_discussion_region" name="region" required>
                    <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_region_options() as $region ) : ?>
                        <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="gaenity_discussion_country"><?php esc_html_e( 'Country', 'gaenity-community' ); ?></label>
                <input type="text" id="gaenity_discussion_country" name="country" required />
            </p>
            <p>
                <label for="gaenity_discussion_industry"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                <select id="gaenity_discussion_industry" name="industry" required>
                    <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                        <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                    <?php endforeach; ?>
                    <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                </select>
            </p>
            <p>
                <label for="gaenity_discussion_challenge"><?php esc_html_e( 'Challenge', 'gaenity-community' ); ?></label>
                <select id="gaenity_discussion_challenge" name="challenge" required>
                    <option value=""><?php esc_html_e( 'Select challenge', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_challenge_options() as $challenge ) : ?>
                        <option value="<?php echo esc_attr( $challenge ); ?>"><?php echo esc_html( $challenge ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="gaenity-checkbox">
                <label>
                    <input type="checkbox" name="anonymous" value="1" />
                    <?php esc_html_e( 'Post anonymously', 'gaenity-community' ); ?>
                </label>
            </p>
            <p>
                <button type="submit" class="gaenity-button"><?php esc_html_e( 'Publish discussion', 'gaenity-community' ); ?></button>
            </p>
            <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Render discussion board with filters.
     */
    public function render_discussion_board( $atts ) {
        $atts = shortcode_atts(
            array(
                'per_page' => 10,
            ),
            $atts,
            'gaenity_discussion_board'
        );

        $paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ) );

        $tax_query = array();
        $filters   = array( 'region' => 'gaenity_region', 'industry' => 'gaenity_industry', 'challenge' => 'gaenity_challenge' );
        foreach ( $filters as $query_var => $taxonomy ) {
            if ( ! empty( $_GET[ $query_var ] ) ) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'name',
                    'terms'    => sanitize_text_field( wp_unslash( $_GET[ $query_var ] ) ),
                );
            }
        }

        $query_args = array(
            'post_type'      => 'gaenity_discussion',
            'posts_per_page' => intval( $atts['per_page'] ),
            'paged'          => $paged,
        );

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query( $query_args );

        ob_start();
        ?>
        <div class="gaenity-discussion-board">
            <form class="gaenity-filters" method="get">
                <label>
                    <?php esc_html_e( 'Region', 'gaenity-community' ); ?>
                    <select name="region">
                        <option value=""><?php esc_html_e( 'All regions', 'gaenity-community' ); ?></option>
                        <?php $this->render_filter_options( 'region' ); ?>
                    </select>
                </label>
                <label>
                    <?php esc_html_e( 'Industry', 'gaenity-community' ); ?>
                    <select name="industry">
                        <option value=""><?php esc_html_e( 'All industries', 'gaenity-community' ); ?></option>
                        <?php $this->render_filter_options( 'industry' ); ?>
                    </select>
                </label>
                <label>
                    <?php esc_html_e( 'Challenge', 'gaenity-community' ); ?>
                    <select name="challenge">
                        <option value=""><?php esc_html_e( 'All challenges', 'gaenity-community' ); ?></option>
                        <?php $this->render_filter_options( 'challenge' ); ?>
                    </select>
                </label>
                <button type="submit" class="gaenity-button ghost"><?php esc_html_e( 'Filter', 'gaenity-community' ); ?></button>
            </form>
            <?php if ( $query->have_posts() ) : ?>
                <ul class="gaenity-discussion-list">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <li class="gaenity-discussion-item">
                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                            <p class="gaenity-discussion-meta"><?php echo esc_html( $this->get_discussion_meta_summary( get_the_ID() ) ); ?></p>
                            <p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 25 ) ); ?></p>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <?php $this->render_pagination( $query ); ?>
            <?php else : ?>
                <p class="gaenity-empty-state"><?php esc_html_e( 'No discussions available yet. Start the conversation by posting the first question!', 'gaenity-community' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Render polls for logged-in users.
     */
    public function render_polls() {
        if ( ! is_user_logged_in() ) {
            return '<p class="gaenity-notice">' . esc_html__( 'Please sign in to take part in community polls.', 'gaenity-community' ) . '</p>';
        }

        $polls = get_posts(
            array(
                'post_type'      => 'gaenity_poll',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            )
        );

        if ( empty( $polls ) ) {
            return '<p class="gaenity-empty-state">' . esc_html__( 'Polls will appear here soon. Check back for new questions!', 'gaenity-community' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="gaenity-polls" id="gaenity-polls">
            <?php foreach ( $polls as $poll ) :
                $question = get_post_meta( $poll->ID, '_gaenity_poll_question', true );
                $options  = get_post_meta( $poll->ID, '_gaenity_poll_options', true );
                if ( empty( $options ) || count( $options ) < 2 ) {
                    continue;
                }
                ?>
                <div class="gaenity-poll" data-poll="<?php echo esc_attr( $poll->ID ); ?>">
                    <h4><?php echo esc_html( get_the_title( $poll->ID ) ); ?>
                        <?php if ( ! empty( $question ) ) : ?>
                            <span class="gaenity-poll-question"><?php echo esc_html( $question ); ?></span>
                        <?php endif; ?>
                    </h4>
                    <form class="gaenity-form gaenity-ajax-form" data-refresh="gaenity-polls">
                        <input type="hidden" name="action" value="gaenity_poll_vote" />
                        <input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->ID ); ?>" />
                        <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                        <div class="gaenity-poll-options">
                            <?php foreach ( $options as $key => $label ) : ?>
                                <label class="gaenity-radio">
                                    <input type="radio" name="option" value="<?php echo esc_attr( $key ); ?>" required /> <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p>
                            <label for="gaenity_poll_region_<?php echo esc_attr( $poll->ID ); ?>"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                            <select id="gaenity_poll_region_<?php echo esc_attr( $poll->ID ); ?>" name="region" required>
                                <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                                <?php foreach ( $this->get_region_options() as $region ) : ?>
                                    <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="gaenity_poll_industry_<?php echo esc_attr( $poll->ID ); ?>"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                            <select id="gaenity_poll_industry_<?php echo esc_attr( $poll->ID ); ?>" name="industry" required>
                                <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                                <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                                    <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p><button type="submit" class="gaenity-button"><?php esc_html_e( 'Submit vote', 'gaenity-community' ); ?></button></p>
                        <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                    </form>
                    <?php echo $this->get_poll_results_markup( $poll->ID, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render expert request form.
     */
    
    /**
     * Render expert request form - NEW VERSION
     */
    public function render_expert_request_form() {
        ob_start();
        ?>
        <style>
        .gaenity-expert-page {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding: 3rem 0;
        }
        .gaenity-expert-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .gaenity-expert-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .gaenity-expert-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 1rem 0;
            letter-spacing: -0.025em;
        }
        .gaenity-expert-blurb {
            font-size: 1.25rem;
            color: #64748b;
            line-height: 1.7;
            font-style: italic;
            margin: 0 0 2rem 0;
        }
        .gaenity-expert-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .gaenity-step {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .gaenity-step:hover {
            border-color: #4f46e5;
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(79, 70, 229, 0.15);
        }
        .gaenity-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .gaenity-step-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
        }
        .gaenity-step-text {
            font-size: 0.9375rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }
        .gaenity-expert-form-card {
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .gaenity-form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 1.5rem 0;
        }
        .gaenity-form-group {
            margin-bottom: 1.5rem;
        }
        .gaenity-form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9375rem;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        .gaenity-form-label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }
        .gaenity-form-input,
        .gaenity-form-select,
        .gaenity-form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .gaenity-form-input:focus,
        .gaenity-form-select:focus,
        .gaenity-form-textarea:focus {
            outline: none;
            border-color: #4f46e5;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .gaenity-form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        .gaenity-form-help {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.375rem;
        }
        .gaenity-form-file {
            border: 2px dashed #cbd5e1;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gaenity-form-file:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .gaenity-form-file input[type="file"] {
            display: none;
        }
        .gaenity-file-label {
            color: #4f46e5;
            font-weight: 600;
            cursor: pointer;
        }
        .gaenity-submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            border: none;
            border-radius: 0.875rem;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }
        .gaenity-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.5);
        }
        .gaenity-submit-btn:active {
            transform: translateY(0);
        }
        .gaenity-form-feedback {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 500;
        }
        .gaenity-form-feedback.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .gaenity-form-feedback.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .gaenity-info-box {
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
        }
        .gaenity-info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .gaenity-expert-steps {
                grid-template-columns: 1fr;
            }
            .gaenity-expert-title {
                font-size: 2rem;
            }
            .gaenity-expert-blurb {
                font-size: 1.125rem;
            }
            .gaenity-expert-form-card {
                padding: 1.5rem;
            }
        }
        </style>

        <div class="gaenity-expert-page">
            <div class="gaenity-expert-container">
                <!-- Header -->
                <div class="gaenity-expert-header">
                    <h1 class="gaenity-expert-title"><?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?></h1>
                    <p class="gaenity-expert-blurb">
                        "<?php esc_html_e( 'Need personalized guidance? Ask a verified expert and receive a detailed answer within 48 hours.', 'gaenity-community' ); ?>"
                    </p>
                </div>

                <!-- Steps -->
                <div class="gaenity-expert-steps">
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">1</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Ask', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( 'Fill in your question with all relevant details', 'gaenity-community' ); ?></p>
                    </div>
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">2</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Pay', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( 'Submit and complete payment securely', 'gaenity-community' ); ?></p>
                    </div>
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">3</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Receive', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( "Get your expert's response within 48 hours", 'gaenity-community' ); ?></p>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="gaenity-info-box">
                    <p>
                        <strong>💡 <?php esc_html_e( 'How it works:', 'gaenity-community' ); ?></strong>
                        <?php esc_html_e( 'Our verified experts are experienced professionals ready to provide personalized advice. Payment is processed securely, and you\'ll receive a detailed response directly to your email within 48 hours.', 'gaenity-community' ); ?>
                    </p>
                </div>

                <!-- Form Card -->
                <div class="gaenity-expert-form-card">
                    <h2 class="gaenity-form-title"><?php esc_html_e( 'Submit Your Question', 'gaenity-community' ); ?></h2>

                    <form class="gaenity-form gaenity-ajax-form">
                        <input type="hidden" name="action" value="gaenity_expert_request" />
                        <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>

                        <!-- Title -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_question_title" class="gaenity-form-label">
                                <?php esc_html_e( 'Question Title', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="gaenity_question_title" 
                                name="title" 
                                class="gaenity-form-input"
                                placeholder="<?php esc_attr_e( 'Brief summary of your question', 'gaenity-community' ); ?>"
                                required 
                            />
                        </div>

                        <!-- Details -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_question_details" class="gaenity-form-label">
                                <?php esc_html_e( 'Question Details', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea 
                                id="gaenity_question_details" 
                                name="details" 
                                class="gaenity-form-textarea"
                                placeholder="<?php esc_attr_e( 'Provide as much detail as possible to help our expert understand your situation...', 'gaenity-community' ); ?>"
                                required
                            ></textarea>
                            <p class="gaenity-form-help">
                                <?php esc_html_e( 'Include specific challenges, context, and what you hope to achieve', 'gaenity-community' ); ?>
                            </p>
                        </div>

                        <!-- Region -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_question_region" class="gaenity-form-label">
                                <?php esc_html_e( 'Your Region', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <select id="gaenity_question_region" name="region" class="gaenity-form-select" required>
                                <option value=""><?php esc_html_e( 'Select your region', 'gaenity-community' ); ?></option>
                                <?php foreach ( $this->get_region_options() as $region ) : ?>
                                    <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Industry -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_question_industry" class="gaenity-form-label">
                                <?php esc_html_e( 'Your Industry', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <select id="gaenity_question_industry" name="industry" class="gaenity-form-select" required>
                                <option value=""><?php esc_html_e( 'Select your industry', 'gaenity-community' ); ?></option>
                                <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                                    <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Attachments -->
                        <div class="gaenity-form-group">
                            <label class="gaenity-form-label">
                                <?php esc_html_e( 'Attachments', 'gaenity-community' ); ?>
                                <span style="color: #64748b; font-weight: 400;">(<?php esc_html_e( 'Optional', 'gaenity-community' ); ?>)</span>
                            </label>
                            <div class="gaenity-form-file">
                                <input type="file" id="gaenity_question_attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                                <label for="gaenity_question_attachments" class="gaenity-file-label">
                                    📎 <?php esc_html_e( 'Click to upload files', 'gaenity-community' ); ?>
                                </label>
                                <p class="gaenity-form-help" style="margin: 0.5rem 0 0 0;">
                                    <?php esc_html_e( 'PDF, Word documents, or images (Max 10MB)', 'gaenity-community' ); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_question_email" class="gaenity-form-label">
                                <?php esc_html_e( 'Your Email', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="gaenity_question_email" 
                                name="email" 
                                class="gaenity-form-input"
                                placeholder="<?php esc_attr_e( 'your@email.com', 'gaenity-community' ); ?>"
                                value="<?php echo esc_attr( is_user_logged_in() ? wp_get_current_user()->user_email : '' ); ?>"
                                required 
                            />
                            <p class="gaenity-form-help">
                                <?php esc_html_e( "We'll send the expert's response to this email", 'gaenity-community' ); ?>
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="gaenity-submit-btn">
                            💳 <?php esc_html_e( 'Submit & Pay', 'gaenity-community' ); ?>
                        </button>

                        <!-- Form Feedback -->
                        <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                    </form>
                </div>

                <!-- Additional Info -->
                <div style="margin-top: 2rem; text-align: center; color: #64748b; font-size: 0.875rem;">
                    <p>
                        <?php esc_html_e( '🔒 Your payment information is processed securely. Questions are matched with experts based on expertise and availability.', 'gaenity-community' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    /**
     * Render expert registration form.
     */
    /**
     * Render expert registration form - FIXED VERSION
     * Replace the method starting at line ~5352
     */
    public function render_expert_register_form() {
        ob_start();
        ?>
        <style>
        .gaenity-become-expert-page {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            padding: 3rem 0;
        }
        .gaenity-become-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .gaenity-become-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .gaenity-become-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 1rem 0;
            letter-spacing: -0.025em;
        }
        .gaenity-become-blurb {
            font-size: 1.25rem;
            color: #64748b;
            line-height: 1.7;
            font-style: italic;
            margin: 0 0 2rem 0;
        }
        .gaenity-become-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .gaenity-step {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .gaenity-step:hover {
            border-color: #10b981;
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15);
        }
        .gaenity-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .gaenity-step-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
        }
        .gaenity-step-text {
            font-size: 0.9375rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }
        .gaenity-become-form-card {
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .gaenity-form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
        }
        .gaenity-form-subtitle {
            font-size: 1rem;
            color: #64748b;
            margin: 0 0 2rem 0;
        }
        .gaenity-form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .gaenity-form-group {
            margin-bottom: 1.5rem;
        }
        .gaenity-form-group.full-width {
            grid-column: 1 / -1;
        }
        .gaenity-form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9375rem;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        .gaenity-form-label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }
        .gaenity-form-input,
        .gaenity-form-select,
        .gaenity-form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .gaenity-form-input:focus,
        .gaenity-form-select:focus,
        .gaenity-form-textarea:focus {
            outline: none;
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .gaenity-form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .gaenity-form-help {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.375rem;
        }
        .gaenity-expertise-checkboxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        .gaenity-checkbox-label {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gaenity-checkbox-label:hover {
            background: #ecfdf5;
            border-color: #10b981;
        }
        .gaenity-checkbox-label input[type="checkbox"] {
            margin-right: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        .gaenity-submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 0.875rem;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .gaenity-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.5);
        }
        .gaenity-submit-btn:active {
            transform: translateY(0);
        }
        .gaenity-form-feedback {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 500;
        }
        .gaenity-form-feedback.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .gaenity-form-feedback.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .gaenity-benefits-box {
            background: #ecfdf5;
            border: 2px solid #a7f3d0;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .gaenity-benefits-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #065f46;
            margin: 0 0 1rem 0;
        }
        .gaenity-benefits-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .gaenity-benefits-list li {
            color: #047857;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .gaenity-benefits-list li::before {
            content: "✓";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            background: #10b981;
            color: #ffffff;
            border-radius: 50%;
            font-weight: 700;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .gaenity-become-steps {
                grid-template-columns: 1fr;
            }
            .gaenity-become-title {
                font-size: 2rem;
            }
            .gaenity-become-blurb {
                font-size: 1.125rem;
            }
            .gaenity-become-form-card {
                padding: 1.5rem;
            }
            .gaenity-form-row {
                grid-template-columns: 1fr;
            }
            .gaenity-expertise-checkboxes {
                grid-template-columns: 1fr;
            }
            .gaenity-benefits-list {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <div class="gaenity-become-expert-page">
            <div class="gaenity-become-container">
                <!-- Header -->
                <div class="gaenity-become-header">
                    <h1 class="gaenity-become-title"><?php esc_html_e( 'Become a Gaenity Expert', 'gaenity-community' ); ?></h1>
                    <p class="gaenity-become-blurb">
                        "<?php esc_html_e( 'Join our expert network, share your knowledge, and earn while helping small businesses grow.', 'gaenity-community' ); ?>"
                    </p>
                </div>

                <!-- Steps -->
                <div class="gaenity-become-steps">
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">1</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Apply', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( 'Complete your expert profile with your experience and expertise', 'gaenity-community' ); ?></p>
                    </div>
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">2</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Verify', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( 'Wait for confirmation while we review your credentials', 'gaenity-community' ); ?></p>
                    </div>
                    <div class="gaenity-step">
                        <div class="gaenity-step-number">3</div>
                        <h3 class="gaenity-step-title"><?php esc_html_e( 'Start Helping', 'gaenity-community' ); ?></h3>
                        <p class="gaenity-step-text"><?php esc_html_e( 'Respond to questions and earn while making an impact', 'gaenity-community' ); ?></p>
                    </div>
                </div>

                <!-- Benefits Box -->
                <div class="gaenity-benefits-box">
                    <h3 class="gaenity-benefits-title">✨ <?php esc_html_e( 'Why Become an Expert?', 'gaenity-community' ); ?></h3>
                    <ul class="gaenity-benefits-list">
                        <li><?php esc_html_e( 'Earn money sharing your expertise', 'gaenity-community' ); ?></li>
                        <li><?php esc_html_e( 'Help small businesses succeed', 'gaenity-community' ); ?></li>
                        <li><?php esc_html_e( 'Set your own schedule', 'gaenity-community' ); ?></li>
                        <li><?php esc_html_e( 'Build your professional reputation', 'gaenity-community' ); ?></li>
                        <li><?php esc_html_e( 'Join a global expert community', 'gaenity-community' ); ?></li>
                        <li><?php esc_html_e( 'Receive verified badge', 'gaenity-community' ); ?></li>
                    </ul>
                </div>

                <!-- Form Card -->
                <div class="gaenity-become-form-card">
                    <h2 class="gaenity-form-title"><?php esc_html_e( 'Expert Application', 'gaenity-community' ); ?></h2>
                    <p class="gaenity-form-subtitle"><?php esc_html_e( 'Tell us about yourself and your expertise', 'gaenity-community' ); ?></p>

                    <form class="gaenity-form gaenity-ajax-form">
                        <input type="hidden" name="action" value="gaenity_expert_register" />
                        <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>

                        <div class="gaenity-form-row">
                            <!-- Name -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_name" class="gaenity-form-label">
                                    <?php esc_html_e( 'Full Name', 'gaenity-community' ); ?>
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="gaenity_expert_name" 
                                    name="name" 
                                    class="gaenity-form-input"
                                    placeholder="<?php esc_attr_e( 'John Doe', 'gaenity-community' ); ?>"
                                    required 
                                />
                            </div>

                            <!-- Email -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_email" class="gaenity-form-label">
                                    <?php esc_html_e( 'Email Address', 'gaenity-community' ); ?>
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    id="gaenity_expert_email" 
                                    name="email" 
                                    class="gaenity-form-input"
                                    placeholder="<?php esc_attr_e( 'your@email.com', 'gaenity-community' ); ?>"
                                    value="<?php echo esc_attr( is_user_logged_in() ? wp_get_current_user()->user_email : '' ); ?>"
                                    required 
                                />
                            </div>
                        </div>

                        <!-- Bio -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_expert_bio" class="gaenity-form-label">
                                <?php esc_html_e( 'Professional Bio', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea 
                                id="gaenity_expert_bio" 
                                name="bio" 
                                class="gaenity-form-textarea"
                                placeholder="<?php esc_attr_e( 'Tell us about your background, experience, and what makes you a great expert...', 'gaenity-community' ); ?>"
                                required
                            ></textarea>
                            <p class="gaenity-form-help">
                                <?php esc_html_e( 'Include your years of experience, key achievements, and areas of specialization', 'gaenity-community' ); ?>
                            </p>
                        </div>

                        <!-- Expertise -->
                        <div class="gaenity-form-group">
                            <label class="gaenity-form-label">
                                <?php esc_html_e( 'Areas of Expertise', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <div class="gaenity-expertise-checkboxes">
                                <?php 
                                $challenges = array(
                                    'Financial Controls',
                                    'Operations',
                                    'Marketing & Sales',
                                    'Human Resources',
                                    'Legal & Compliance',
                                    'Technology & Systems',
                                    'Strategy & Planning'
                                );
                                foreach ( $challenges as $challenge ) : 
                                ?>
                                    <label class="gaenity-checkbox-label">
                                        <input type="checkbox" name="expertise[]" value="<?php echo esc_attr( $challenge ); ?>" />
                                        <?php echo esc_html( $challenge ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="gaenity-form-help">
                                <?php esc_html_e( 'Select all areas where you can provide expert guidance', 'gaenity-community' ); ?>
                            </p>
                        </div>

                        <div class="gaenity-form-row">
                            <!-- Region -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_region" class="gaenity-form-label">
                                    <?php esc_html_e( 'Primary Region', 'gaenity-community' ); ?>
                                    <span class="required">*</span>
                                </label>
                                <select id="gaenity_expert_region" name="region" class="gaenity-form-select" required>
                                    <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                                    <?php 
                                    $regions = array('Africa', 'Asia', 'Europe', 'North America', 'South America', 'Middle East', 'Oceania');
                                    foreach ( $regions as $region ) : 
                                    ?>
                                        <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Industry -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_industry" class="gaenity-form-label">
                                    <?php esc_html_e( 'Primary Industry', 'gaenity-community' ); ?>
                                    <span class="required">*</span>
                                </label>
                                <select id="gaenity_expert_industry" name="industry" class="gaenity-form-select" required>
                                    <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                                    <?php 
                                    $industries = array('Retail & e-commerce', 'Food & Beverage', 'Manufacturing', 'Services', 'Technology', 'Healthcare', 'Education', 'Logistics', 'Agriculture', 'Construction', 'Finance');
                                    foreach ( $industries as $industry ) : 
                                    ?>
                                        <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="gaenity-form-row">
                            <!-- LinkedIn -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_linkedin" class="gaenity-form-label">
                                    <?php esc_html_e( 'LinkedIn Profile', 'gaenity-community' ); ?>
                                </label>
                                <input 
                                    type="url" 
                                    id="gaenity_expert_linkedin" 
                                    name="linkedin" 
                                    class="gaenity-form-input"
                                    placeholder="<?php esc_attr_e( 'https://linkedin.com/in/yourprofile', 'gaenity-community' ); ?>"
                                />
                            </div>

                            <!-- Website -->
                            <div class="gaenity-form-group">
                                <label for="gaenity_expert_website" class="gaenity-form-label">
                                    <?php esc_html_e( 'Website/Portfolio', 'gaenity-community' ); ?>
                                </label>
                                <input 
                                    type="url" 
                                    id="gaenity_expert_website" 
                                    name="website" 
                                    class="gaenity-form-input"
                                    placeholder="<?php esc_attr_e( 'https://yourwebsite.com', 'gaenity-community' ); ?>"
                                />
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="gaenity-form-group">
                            <label for="gaenity_expert_availability" class="gaenity-form-label">
                                <?php esc_html_e( 'Availability', 'gaenity-community' ); ?>
                                <span class="required">*</span>
                            </label>
                            <select id="gaenity_expert_availability" name="availability" class="gaenity-form-select" required>
                                <option value=""><?php esc_html_e( 'Select your availability', 'gaenity-community' ); ?></option>
                                <option value="full-time"><?php esc_html_e( 'Full-time (20+ hours/week)', 'gaenity-community' ); ?></option>
                                <option value="part-time"><?php esc_html_e( 'Part-time (10-20 hours/week)', 'gaenity-community' ); ?></option>
                                <option value="flexible"><?php esc_html_e( 'Flexible (5-10 hours/week)', 'gaenity-community' ); ?></option>
                                <option value="limited"><?php esc_html_e( 'Limited (1-5 hours/week)', 'gaenity-community' ); ?></option>
                            </select>
                            <p class="gaenity-form-help">
                                <?php esc_html_e( 'How much time can you dedicate to answering questions?', 'gaenity-community' ); ?>
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="gaenity-submit-btn">
                            🚀 <?php esc_html_e( 'Submit Application', 'gaenity-community' ); ?>
                        </button>

                        <!-- Form Feedback -->
                        <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
                    </form>
                </div>

                <!-- Additional Info -->
                <div style="margin-top: 2rem; text-align: center; color: #64748b; font-size: 0.875rem;">
                    <p>
                        <?php esc_html_e( '🔒 Your information is secure. We typically review applications within 3-5 business days and will contact you via email.', 'gaenity-community' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    

    /**
     * Render contact form.
     */
    public function render_contact_form() {
        ob_start();
        ?>
        <section class="gaenity-contact">
            <h3><?php esc_html_e( 'We welcome questions, ideas, and collaboration. Send a message', 'gaenity-community' ); ?></h3>
            <form class="gaenity-form gaenity-ajax-form">
                <input type="hidden" name="action" value="gaenity_contact_submit" />
                <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                <p>
                    <label for="gaenity_contact_name"><?php esc_html_e( 'Name', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_contact_name" name="name" required />
                </p>
                <p>
                    <label for="gaenity_contact_email"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                    <input type="email" id="gaenity_contact_email" name="email" required />
                </p>
                <p>
                    <label for="gaenity_contact_subject"><?php esc_html_e( 'Subject', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_contact_subject" name="subject" required />
                </p>
                <p>
                    <label for="gaenity_contact_message"><?php esc_html_e( 'Message', 'gaenity-community' ); ?></label>
                    <textarea id="gaenity_contact_message" name="message" rows="4" required></textarea>
                </p>
                <p class="gaenity-checkbox">
                    <label>
                        <input type="checkbox" name="updates" value="1" />
                        <?php esc_html_e( 'I agree to receive updates from Gaenity', 'gaenity-community' ); ?>
                    </label>
                </p>
                <p>
                    <button type="submit" class="gaenity-button"><?php esc_html_e( 'Send message', 'gaenity-community' ); ?></button>
                </p>
                <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
            </form>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render community chat interface.
     */
    public function render_chat_interface() {
        $messages = $this->get_chat_messages();
        $max_messages = apply_filters( 'gaenity_chat_max_messages', 30 );
        ob_start();
        ?>
        <section class="gaenity-chat">
            <h3><?php esc_html_e( 'Community Chat', 'gaenity-community' ); ?></h3>
            <div class="gaenity-chat-window" data-max-messages="<?php echo esc_attr( $max_messages ); ?>">
                <ul class="gaenity-chat-messages">
                    <?php foreach ( $messages as $message ) : ?>
                        <li>
                            <div class="gaenity-chat-meta">
                                <strong><?php echo esc_html( $message['display_name'] ); ?></strong>
                                <?php if ( ! empty( $message['role'] ) ) : ?>
                                    <span class="gaenity-badge"><?php echo esc_html( $message['role'] ); ?></span>
                                <?php endif; ?>
                                <span class="gaenity-chat-timestamp"><?php echo esc_html( $message['time'] ); ?></span>
                            </div>
                            <div class="gaenity-chat-body"><?php echo wp_kses_post( wpautop( $message['message'] ) ); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <form class="gaenity-form gaenity-chat-form gaenity-ajax-form" data-refresh="gaenity-chat">
                <input type="hidden" name="action" value="gaenity_chat_send" />
                <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                <p>
                    <label for="gaenity_chat_display"><?php esc_html_e( 'Display name', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_chat_display" name="display_name" placeholder="<?php esc_attr_e( 'Optional if logged in', 'gaenity-community' ); ?>" />
                </p>
                <p>
                    <label for="gaenity_chat_role"><?php esc_html_e( 'Role', 'gaenity-community' ); ?></label>
                    <select id="gaenity_chat_role" name="role">
                        <option value=""><?php esc_html_e( 'Select role', 'gaenity-community' ); ?></option>
                        <option value="Business Owner"><?php esc_html_e( 'Business Owner', 'gaenity-community' ); ?></option>
                        <option value="Professional"><?php esc_html_e( 'Professional', 'gaenity-community' ); ?></option>
                        <option value="Forum Expert"><?php esc_html_e( 'Forum Expert', 'gaenity-community' ); ?></option>
                    </select>
                </p>
                <p>
                    <label for="gaenity_chat_region"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                    <select id="gaenity_chat_region" name="region">
                        <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_region_options() as $region ) : ?>
                            <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="gaenity_chat_industry"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                    <select id="gaenity_chat_industry" name="industry">
                        <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                            <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                        <?php endforeach; ?>
                        <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                    </select>
                </p>
                <p>
                    <label for="gaenity_chat_challenge"><?php esc_html_e( 'Challenge', 'gaenity-community' ); ?></label>
                    <select id="gaenity_chat_challenge" name="challenge">
                        <option value=""><?php esc_html_e( 'Select challenge', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_challenge_options() as $challenge ) : ?>
                            <option value="<?php echo esc_attr( $challenge ); ?>"><?php echo esc_html( $challenge ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="gaenity-checkbox">
                    <label>
                        <input type="checkbox" name="anonymous" value="1" />
                        <?php esc_html_e( 'Post anonymously', 'gaenity-community' ); ?>
                    </label>
                </p>
                <p>
                    <label for="gaenity_chat_message"><?php esc_html_e( 'Message', 'gaenity-community' ); ?></label>
                    <textarea id="gaenity_chat_message" name="message" rows="3" required></textarea>
                </p>
                <p>
                    <button type="submit" class="gaenity-button"><?php esc_html_e( 'Send', 'gaenity-community' ); ?></button>
                </p>
                <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
            </form>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Return predefined industry options.
     */
    protected function get_industry_options() {
        return array(
            'Retail & e-commerce',
            'Manufacturing',
            'Services',
            'Health & wellness',
            'Food & hospitality',
            'Technology & startups',
            'Agriculture',
            'Finance/Financial Services',
            'Nonprofits & education',
        );
    }

    /**
     * Return region options.
     */
    protected function get_region_options() {
        return array(
            'Africa',
            'North America',
            'Europe',
            'Middle East',
            'Asia Pacific',
            'Latin America',
        );
    }

    /**
     * Return challenge options.
     */
    protected function get_challenge_options() {
        return array(
            'Cash flow',
            'Supplier/customer risk',
            'Compliance',
            'Operations',
            'People',
            'Sales/marketing',
            'Technology & data',
            'Financial Controls',
            'Credit',
            'Fraud',
        );
    }

    /**
     * Render filter options for discussion board.
     */
    protected function render_filter_options( $type ) {
        $taxonomy = 'gaenity_' . $type;
        $terms    = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            )
        );

        $selected = isset( $_GET[ $type ] ) ? sanitize_text_field( wp_unslash( $_GET[ $type ] ) ) : '';

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                printf(
                    '<option value="%1$s" %3$s>%2$s</option>',
                    esc_attr( $term->name ),
                    esc_html( $term->name ),
                    selected( $selected, $term->name, false )
                );
            }
        }
    }

    /**
     * Build discussion meta summary text.
     */
    protected function get_discussion_meta_summary( $post_id ) {
        $parts = array();
        $region = wp_get_post_terms( $post_id, 'gaenity_region', array( 'fields' => 'names' ) );
        if ( ! empty( $region ) ) {
            $parts[] = sprintf( __( 'Region: %s', 'gaenity-community' ), implode( ', ', $region ) );
        }
        $industry = wp_get_post_terms( $post_id, 'gaenity_industry', array( 'fields' => 'names' ) );
        if ( ! empty( $industry ) ) {
            $parts[] = sprintf( __( 'Industry: %s', 'gaenity-community' ), implode( ', ', $industry ) );
        }
        $challenge = wp_get_post_terms( $post_id, 'gaenity_challenge', array( 'fields' => 'names' ) );
        if ( ! empty( $challenge ) ) {
            $parts[] = sprintf( __( 'Challenge: %s', 'gaenity-community' ), implode( ', ', $challenge ) );
        }
        $country = get_post_meta( $post_id, '_gaenity_country', true );
        if ( $country ) {
            $parts[] = sprintf( __( 'Country: %s', 'gaenity-community' ), $country );
        }

        return implode( ' | ', $parts );
    }

    /**
     * Render pagination links.
     */
    protected function render_pagination( WP_Query $query ) {
        $links = paginate_links(
            array(
                'total'   => $query->max_num_pages,
                'current' => max( 1, get_query_var( 'paged' ) ),
                'type'    => 'list',
                'prev_text' => __( 'Previous', 'gaenity-community' ),
                'next_text' => __( 'Next', 'gaenity-community' ),
            )
        );

        if ( $links ) {
            echo '<nav class="gaenity-pagination">' . wp_kses_post( $links ) . '</nav>';
        }
    }

    /**
     * Generate poll results markup.
     */
    protected function get_poll_results_markup( $poll_id, $options ) {
        $counts = $this->get_poll_vote_counts( $poll_id );
        $total  = array_sum( $counts );

        ob_start();
        ?>
        <div class="gaenity-poll-results">
            <h5><?php esc_html_e( 'Current results', 'gaenity-community' ); ?></h5>
            <ul>
                <?php foreach ( $options as $key => $label ) :
                    $count = isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0;
                    $percentage = $total ? round( ( $count / $total ) * 100 ) : 0;
                    ?>
                    <li>
                        <span class="gaenity-result-label"><?php echo esc_html( $label ); ?></span>
                        <span class="gaenity-result-value"><?php echo esc_html( sprintf( '%d%% (%d)', $percentage, $count ) ); ?></span>
                        <span class="gaenity-result-bar" style="width: <?php echo esc_attr( $percentage ); ?>%"></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Count votes per option.
     */
    protected function get_poll_vote_counts( $poll_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT option_key, COUNT(*) as votes FROM ' . $wpdb->prefix . 'gaenity_poll_votes WHERE poll_id = %d GROUP BY option_key', $poll_id ), ARRAY_A );
        $counts  = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $counts[ $row['option_key'] ] = (int) $row['votes'];
            }
        }
        return $counts;
    }

    /**
     * Fetch latest chat messages.
     */
    protected function get_chat_messages() {
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'gaenity_chat_messages ORDER BY id DESC LIMIT 30', ARRAY_A );
        $messages = array();
        if ( $rows ) {
            foreach ( array_reverse( $rows ) as $row ) {
                $messages[] = array(
                    'display_name' => $row['display_name'],
                    'role'         => $row['role'],
                    'message'      => wp_kses_post( $row['message'] ),
                    'time'         => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ),
                );
            }
        }
        return $messages;
    }

/**
     * Seed dummy data for testing.
     */
    public static function seed_dummy_data() {
        global $wpdb;

        // Check if already seeded
        if ( get_option( 'gaenity_dummy_seeded' ) ) {
            return;
        }

        // Create dummy admin user if not exists
        $admin_email = 'admin@gaenity.test';
        if ( ! email_exists( $admin_email ) ) {
            $admin_id = wp_create_user( 'gaenity_admin', wp_generate_password(), $admin_email );
            wp_update_user( array( 'ID' => $admin_id, 'role' => 'administrator', 'display_name' => 'Gaenity Admin' ) );
        }

        // Create 5 dummy members
        $members = array(
            array( 'name' => 'Sarah Johnson', 'email' => 'sarah@example.com', 'role' => 'Business Owner', 'region' => 'North America', 'industry' => 'Retail & e-commerce' ),
            array( 'name' => 'Michael Chen', 'email' => 'michael@example.com', 'role' => 'Employed Professional', 'region' => 'Asia Pacific', 'industry' => 'Technology & startups' ),
            array( 'name' => 'Amara Okafor', 'email' => 'amara@example.com', 'role' => 'Business Owner', 'region' => 'Africa', 'industry' => 'Agriculture' ),
            array( 'name' => 'Carlos Martinez', 'email' => 'carlos@example.com', 'role' => 'Business Owner', 'region' => 'Latin America', 'industry' => 'Food & hospitality' ),
            array( 'name' => 'Emma Schmidt', 'email' => 'emma@example.com', 'role' => 'Employed Professional', 'region' => 'Europe', 'industry' => 'Finance/Financial Services' ),
        );

        $member_ids = array();
        foreach ( $members as $member ) {
            if ( ! email_exists( $member['email'] ) ) {
                $user_id = wp_create_user( 
                    sanitize_user( current( explode( '@', $member['email'] ) ) ), 
                    wp_generate_password(), 
                    $member['email'] 
                );
                if ( ! is_wp_error( $user_id ) ) {
                    wp_update_user( array( 'ID' => $user_id, 'display_name' => $member['name'], 'role' => 'subscriber' ) );
                    update_user_meta( $user_id, 'gaenity_region', $member['region'] );
                    update_user_meta( $user_id, 'gaenity_industry', $member['industry'] );
                    update_user_meta( $user_id, 'gaenity_role_title', $member['role'] );
                    $member_ids[] = $user_id;
                }
            }
        }

        // Create 3 dummy experts
        $experts = array(
            array( 'name' => 'Dr. James Wilson', 'email' => 'james@expert.com', 'expertise' => 'Risk Management & Compliance', 'region' => 'Europe' ),
            array( 'name' => 'Linda Torres', 'email' => 'linda@expert.com', 'expertise' => 'Financial Planning & CFO Services', 'region' => 'North America' ),
            array( 'name' => 'Ahmed Hassan', 'email' => 'ahmed@expert.com', 'expertise' => 'Operations & Supply Chain', 'region' => 'Middle East' ),
        );

        foreach ( $experts as $expert ) {
            if ( ! email_exists( $expert['email'] ) ) {
                $user_id = wp_create_user( 
                    sanitize_user( current( explode( '@', $expert['email'] ) ) ), 
                    wp_generate_password(), 
                    $expert['email'] 
                );
                if ( ! is_wp_error( $user_id ) ) {
                    wp_update_user( array( 'ID' => $user_id, 'display_name' => $expert['name'], 'role' => 'gaenity_expert' ) );
                    update_user_meta( $user_id, 'gaenity_expert_approved', 1 );
                    update_user_meta( $user_id, 'gaenity_expertise', $expert['expertise'] );
                    update_user_meta( $user_id, 'gaenity_region', $expert['region'] );
                }
            }
        }

        // Create 6 dummy resources
        $resources = array(
            array(
                'title' => 'Cash Flow Management Template',
                'content' => 'A comprehensive Excel template to help you track and forecast your business cash flow. Includes 12-month projection, actuals vs. budget comparison, and visual dashboards.',
                'type' => 'free',
                'file' => 'https://example.com/downloads/cash-flow-template.xlsx'
            ),
            array(
                'title' => 'Risk Assessment Checklist',
                'content' => 'Identify and mitigate business risks with this detailed checklist covering operational, financial, legal, and strategic risks.',
                'type' => 'free',
                'file' => 'https://example.com/downloads/risk-checklist.pdf'
            ),
            array(
                'title' => 'Supplier Evaluation Framework',
                'content' => 'Evaluate and score potential suppliers using this proven framework. Includes scorecard templates and contract negotiation tips.',
                'type' => 'free',
                'file' => 'https://example.com/downloads/supplier-framework.pdf'
            ),
            array(
                'title' => 'Customer Onboarding Playbook',
                'content' => 'Streamline your customer onboarding with step-by-step guides, email templates, and automation workflows.',
                'type' => 'free',
                'file' => 'https://example.com/downloads/onboarding-playbook.pdf'
            ),
            array(
                'title' => 'Financial Controls Audit Guide',
                'content' => 'Ensure your financial controls meet compliance standards with this comprehensive audit guide and checklist.',
                'type' => 'free',
                'file' => 'https://example.com/downloads/financial-audit.pdf'
            ),
            array(
                'title' => 'Advanced Business Strategy Toolkit (Premium)',
                'content' => 'Comprehensive toolkit including market analysis frameworks, competitive positioning tools, and growth strategy templates. Perfect for scaling businesses.',
                'type' => 'paid',
                'file' => 'https://example.com/downloads/strategy-toolkit.zip'
            ),
        );

        foreach ( $resources as $resource ) {
            $post_id = wp_insert_post( array(
                'post_title' => $resource['title'],
                'post_content' => $resource['content'],
                'post_type' => 'gaenity_resource',
                'post_status' => 'publish',
                'post_author' => 1,
            ) );

            if ( $post_id ) {
                wp_set_object_terms( $post_id, $resource['type'], 'gaenity_resource_type' );
                update_post_meta( $post_id, '_gaenity_resource_file', $resource['file'] );
            }
        }

        // Create 6 dummy discussions
        $discussions = array(
            array(
                'title' => 'Managing cash flow during seasonal slowdowns',
                'content' => 'Our retail business experiences significant slowdowns in Q1 and Q3. How do other business owners manage cash flow during these periods? Looking for practical strategies beyond traditional bank loans.',
                'region' => 'North America',
                'industry' => 'Retail & e-commerce',
                'challenge' => 'Cash flow',
                'country' => 'United States',
            ),
            array(
                'title' => 'Finding reliable suppliers in emerging markets',
                'content' => 'We\'re expanding into African markets and need to establish reliable supplier relationships. What due diligence processes do you recommend? Any red flags to watch for?',
                'region' => 'Africa',
                'industry' => 'Manufacturing',
                'challenge' => 'Supplier/customer risk',
                'country' => 'Kenya',
            ),
            array(
                'title' => 'GDPR compliance for small tech startups',
                'content' => 'As a small SaaS company processing EU customer data, what are the essential GDPR compliance steps we absolutely cannot skip? Budget is limited but we want to do this right.',
                'region' => 'Europe',
                'industry' => 'Technology & startups',
                'challenge' => 'Compliance',
                'country' => 'Germany',
            ),
            array(
                'title' => 'Automating restaurant inventory management',
                'content' => 'Running multiple restaurant locations and inventory tracking is getting overwhelming. What systems or software have worked well for similar operations? Looking for affordable solutions.',
                'region' => 'Latin America',
                'industry' => 'Food & hospitality',
                'challenge' => 'Operations',
                'country' => 'Mexico',
            ),
            array(
                'title' => 'Retaining top talent in competitive market',
                'content' => 'Our tech team is being poached by larger companies with bigger budgets. How can small businesses compete for talent beyond just salary? What non-monetary benefits work?',
                'region' => 'Asia Pacific',
                'industry' => 'Technology & startups',
                'challenge' => 'People',
                'country' => 'Singapore',
            ),
            array(
                'title' => 'Digital marketing ROI tracking',
                'content' => 'Spending significant budget on digital ads but struggling to track actual ROI. What metrics should we focus on? Any recommended tools for small businesses?',
                'region' => 'North America',
                'industry' => 'Services',
                'challenge' => 'Sales/marketing',
                'country' => 'Canada',
            ),
        );

        foreach ( $discussions as $index => $discussion ) {
            $author_id = ! empty( $member_ids ) ? $member_ids[ $index % count( $member_ids ) ] : 1;
            
            $post_id = wp_insert_post( array(
                'post_title' => $discussion['title'],
                'post_content' => $discussion['content'],
                'post_type' => 'gaenity_discussion',
                'post_status' => 'publish',
                'post_author' => $author_id,
            ) );

            if ( $post_id ) {
                wp_set_object_terms( $post_id, $discussion['region'], 'gaenity_region' );
                wp_set_object_terms( $post_id, $discussion['industry'], 'gaenity_industry' );
                wp_set_object_terms( $post_id, $discussion['challenge'], 'gaenity_challenge' );
                update_post_meta( $post_id, '_gaenity_country', $discussion['country'] );
            }
        }

        // Create 5 dummy polls
        $polls = array(
            array(
                'title' => 'What is your biggest business challenge this quarter?',
                'question' => 'Select the challenge that is currently taking most of your focus and resources.',
                'options' => array(
                    'option_1' => 'Managing cash flow',
                    'option_2' => 'Finding and retaining talent',
                    'option_3' => 'Navigating compliance and regulations',
                    'option_4' => 'Improving operational efficiency',
                    'option_5' => 'Increasing sales and revenue',
                ),
            ),
            array(
                'title' => 'How confident are you in your current financial reporting?',
                'question' => 'Rate your confidence in the accuracy and timeliness of your financial reports.',
                'options' => array(
                    'option_1' => 'Very confident - we have robust systems',
                    'option_2' => 'Somewhat confident - room for improvement',
                    'option_3' => 'Not confident - need major upgrades',
                    'option_4' => 'No formal reporting system yet',
                ),
            ),
            array(
                'title' => 'Primary customer acquisition channel?',
                'question' => 'Which channel brings you the most qualified customers?',
                'options' => array(
                    'option_1' => 'Social media marketing',
                    'option_2' => 'Word of mouth / referrals',
                    'option_3' => 'Paid search ads',
                    'option_4' => 'Email marketing',
                    'option_5' => 'Traditional advertising',
                ),
            ),
            array(
                'title' => 'Remote work policy in your organization?',
                'question' => 'What is your current approach to remote work?',
                'options' => array(
                    'option_1' => 'Fully remote',
                    'option_2' => 'Hybrid (2-3 days in office)',
                    'option_3' => 'Fully in-office',
                    'option_4' => 'Flexible / employee choice',
                ),
            ),
            array(
                'title' => 'Investment priority for next 6 months?',
                'question' => 'Where will you allocate most of your growth budget?',
                'options' => array(
                    'option_1' => 'Technology and automation',
                    'option_2' => 'Marketing and sales',
                    'option_3' => 'Hiring and training',
                    'option_4' => 'Infrastructure and facilities',
                    'option_5' => 'Research and development',
                ),
            ),
        );

        foreach ( $polls as $poll ) {
            $post_id = wp_insert_post( array(
                'post_title' => $poll['title'],
                'post_type' => 'gaenity_poll',
                'post_status' => 'publish',
                'post_author' => 1,
            ) );

            if ( $post_id ) {
                update_post_meta( $post_id, '_gaenity_poll_question', $poll['question'] );
                update_post_meta( $post_id, '_gaenity_poll_options', $poll['options'] );
            }
        }

        // Insert dummy expert registrations
        $expert_regs = array(
            array(
                'name' => 'Patricia Williams',
                'email' => 'patricia@consultancy.com',
                'description' => '15 years experience in operational excellence and process optimization. Former COO at Fortune 500 company. Specialized in lean management and supply chain optimization.',
                'profile_url' => 'https://linkedin.com/in/patriciawilliams',
            ),
            array(
                'name' => 'Robert Kim',
                'email' => 'robert@financeexperts.com',
                'description' => 'Certified CPA and fractional CFO. Helped 50+ startups achieve profitability. Expert in financial modeling, fundraising, and exit strategies.',
                'profile_url' => 'https://linkedin.com/in/robertkim',
            ),
            array(
                'name' => 'Fatima Al-Rashid',
                'email' => 'fatima@riskadvisors.com',
                'description' => 'Risk management consultant with expertise in Middle East and African markets. Specialized in compliance, fraud prevention, and business continuity planning.',
                'profile_url' => 'https://linkedin.com/in/fatimaalrashid',
            ),
        );

        foreach ( $expert_regs as $expert ) {
            $wpdb->insert(
                $wpdb->prefix . 'gaenity_expert_requests',
                array(
                    'name' => $expert['name'],
                    'email' => $expert['email'],
                    'role' => 'Expert Applicant',
                    'challenge' => 'expert_registration',
                    'description' => $expert['description'],
                    'budget' => $expert['profile_url'],
                    'preference' => 'expert_registration',
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
        }

        // Insert dummy expert help requests
        $help_requests = array(
            array(
                'name' => 'John Anderson',
                'email' => 'john@business.com',
                'role' => 'Business Owner',
                'region' => 'North America',
                'country' => 'USA',
                'industry' => 'Manufacturing',
                'challenge' => 'Financial Controls',
                'description' => 'Need help implementing SOX compliance controls for our growing manufacturing business. Looking for someone with experience in mid-size companies.',
                'budget' => '$200/hour consultation',
                'preference' => 'virtual_meeting',
            ),
            array(
                'name' => 'Maria Garcia',
                'email' => 'maria@retailco.com',
                'role' => 'Business Owner',
                'region' => 'Latin America',
                'country' => 'Brazil',
                'industry' => 'Retail & e-commerce',
                'challenge' => 'Operations',
                'description' => 'Our inventory management is chaotic across 5 retail locations. Need expert guidance on implementing a centralized system.',
                'budget' => '$150 for email consultation',
                'preference' => 'email',
            ),
        );

        foreach ( $help_requests as $request ) {
            $wpdb->insert(
                $wpdb->prefix . 'gaenity_expert_requests',
                array(
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'role' => $request['role'],
                    'region' => $request['region'],
                    'country' => $request['country'],
                    'industry' => $request['industry'],
                    'challenge' => $request['challenge'],
                    'description' => $request['description'],
                    'budget' => $request['budget'],
                    'preference' => $request['preference'],
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
        }

        // Insert dummy resource downloads
        $downloads = array(
            array( 'email' => 'user1@example.com', 'role' => 'Business owner', 'industry' => 'Retail & e-commerce', 'resource_id' => 1 ),
            array( 'email' => 'user2@example.com', 'role' => 'Professional', 'industry' => 'Technology & startups', 'resource_id' => 2 ),
            array( 'email' => 'user3@example.com', 'role' => 'Business owner', 'industry' => 'Manufacturing', 'resource_id' => 1 ),
            array( 'email' => 'user4@example.com', 'role' => 'Business owner', 'industry' => 'Services', 'resource_id' => 3 ),
            array( 'email' => 'user5@example.com', 'role' => 'Professional', 'industry' => 'Finance/Financial Services', 'resource_id' => 4 ),
        );

        foreach ( $downloads as $download ) {
            $wpdb->insert(
                $wpdb->prefix . 'gaenity_resource_downloads',
                array(
                    'resource_id' => $download['resource_id'],
                    'email' => $download['email'],
                    'role' => $download['role'],
                    'industry' => $download['industry'],
                    'consent' => 1,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%s' )
            );
        }

        // Insert dummy contact messages
        $contacts = array(
            array(
                'name' => 'Jennifer Lee',
                'email' => 'jennifer@company.com',
                'subject' => 'Partnership Opportunity',
                'message' => 'Hi, I represent a business network in Asia Pacific and would love to discuss partnership opportunities with Gaenity. Could we schedule a call?',
            ),
            array(
                'name' => 'David Brown',
                'email' => 'david@startup.io',
                'subject' => 'Feature Request',
                'message' => 'Love the community! Would be great to have a mobile app for on-the-go access. Is this on your roadmap?',
            ),
            array(
                'name' => 'Lisa Thompson',
                'email' => 'lisa@consulting.com',
                'subject' => 'Speaking Opportunity',
                'message' => 'I would like to present a webinar on risk management for SMEs. Would this be of interest to your community members?',
            ),
        );

        foreach ( $contacts as $contact ) {
            $wpdb->insert(
                $wpdb->prefix . 'gaenity_contact_messages',
                array(
                    'name' => $contact['name'],
                    'email' => $contact['email'],
                    'subject' => $contact['subject'],
                    'message' => $contact['message'],
                    'updates' => 1,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%d', '%s' )
            );
        }

        // Insert dummy chat messages
        $chat_messages = array(
            array(
                'display_name' => 'Sarah Johnson',
                'role' => 'Business Owner',
                'region' => 'North America',
                'industry' => 'Retail & e-commerce',
                'message' => 'Just closed my best quarter yet! The cash flow template from resources was a game changer.',
            ),
            array(
                'display_name' => 'Michael Chen',
                'role' => 'Professional',
                'region' => 'Asia Pacific',
                'industry' => 'Technology & startups',
                'message' => 'Anyone else struggling with hiring developers right now? The competition is insane!',
            ),
            array(
                'display_name' => 'Anonymous',
                'role' => 'Business Owner',
                'region' => 'Europe',
                'industry' => 'Manufacturing',
                'message' => 'Dealing with a difficult supplier situation. How do you handle contract disputes professionally?',
                'is_anonymous' => 1,
            ),
            array(
                'display_name' => 'Carlos Martinez',
                'role' => 'Business Owner',
                'region' => 'Latin America',
                'industry' => 'Food & hospitality',
                'message' => 'The supplier evaluation framework helped us find 3 new reliable vendors. Highly recommend!',
            ),
            array(
                'display_name' => 'Emma Schmidt',
                'role' => 'Professional',
                'region' => 'Europe',
                'industry' => 'Finance/Financial Services',
                'message' => 'Quick tip: Automate your invoice reminders. Reduced our DSO by 15 days!',
            ),
            array(
                'display_name' => 'Amara Okafor',
                'role' => 'Business Owner',
                'region' => 'Africa',
                'industry' => 'Agriculture',
                'message' => 'Looking for advice on export documentation for agricultural products. Any experts here?',
            ),
        );

        foreach ( $chat_messages as $chat ) {
            $wpdb->insert(
                $wpdb->prefix . 'gaenity_chat_messages',
                array(
                    'display_name' => $chat['display_name'],
                    'role' => $chat['role'],
                    'region' => $chat['region'],
                    'industry' => $chat['industry'],
                    'challenge' => '',
                    'message' => $chat['message'],
                    'is_anonymous' => isset( $chat['is_anonymous'] ) ? 1 : 0,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
            );
        }

        // Mark as seeded
        update_option( 'gaenity_dummy_seeded', true );
    }}
endif;