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
        add_action( 'init', array( $this, 'register_member_dashboard' ) );  // ← ADD THIS
        add_action( 'init', array( $this, 'register_expert_directory' ) );
        add_action( 'init', array( $this, 'register_roles' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
add_filter( 'get_post_type_archive_link', array( $this, 'fix_discussion_archive_link' ), 10, 2 ); // ADD THIS
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
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );  // ← FIND THIS LINE

        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
        add_filter( 'template_include', array( $this, 'load_plugin_templates' ) );
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
        // Create comment reactions table
        $reactions_table = $wpdb->prefix . 'gaenity_comment_reactions';
        $sql_reactions   = "CREATE TABLE $reactions_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comment_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT 0,
            ip_address VARCHAR(45) DEFAULT '' NOT NULL,
            reaction_type VARCHAR(20) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY comment_id (comment_id),
            KEY user_id (user_id),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        dbDelta( $sql_reactions );

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

        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Resource Downloads', 'gaenity-community' ); ?></h1>
            
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
                                <td><?php echo $item->consent ? '✓' : '—'; ?></td>
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
                                <td><?php echo esc_html( $item->display_name ); ?><?php echo $item->is_anonymous ? ' 🔒' : ''; ?></td>
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

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gaenity-resources' ) ); ?>" class="page-title-action">← <?php esc_html_e( 'Back to Resources', 'gaenity-community' ); ?></a>
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
                                        —
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
                                    'EUR' => 'Euro (€)',
                                    'GBP' => 'British Pound (£)',
                                    'NGN' => 'Nigerian Naira (₦)',
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

                <p class="submit">

        <button type="submit" name="gaenity_save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'gaenity-community' ); ?></button>
                </p>
            </form>

            <hr style="margin: 40px 0;">
            <div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a;">
                <h2><?php esc_html_e( 'Demo Content', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Add dummy content for testing (resources, discussions, polls, experts, etc.)', 'gaenity-community' ); ?></p>
                <?php if ( get_option( 'gaenity_dummy_seeded' ) ) : ?>
                    <p style="color: #00a32a; font-weight: 600;">✓ <?php esc_html_e( 'Dummy data already seeded!', 'gaenity-community' ); ?></p>
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
    public function render_member_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<div style="text-align: center; padding: 3rem; background: #f7fafc; border-radius: 12px; margin: 2rem 0;">
                <p style="font-size: 1.2rem; color: #475569; margin-bottom: 1.5rem;">' . esc_html__( 'Please log in to access your dashboard.', 'gaenity-community' ) . '</p>
                <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" style="display: inline-block; padding: 0.75rem 2rem; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">' . esc_html__( 'Log In', 'gaenity-community' ) . '</a>
            </div>';
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Get user stats
        $user_discussions = new WP_Query( array(
            'post_type' => 'gaenity_discussion',
            'author' => $user_id,
            'posts_per_page' => -1,
        ) );
        
        global $wpdb;
        $downloads_count = $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_resource_downloads WHERE email = %s", 
            $user->user_email 
        ) );

        $expert_requests = $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(*) FROM {$wpdb->prefix}gaenity_expert_requests WHERE user_id = %d", 
            $user_id 
        ) );

        ob_start();
        ?>
        <style>
            .gaenity-dashboard {
                max-width: 1200px;
                margin: 2rem auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .gaenity-dashboard-header {
                background: linear-gradient(135deg, #1d4ed8, #7c3aed);
                color: #fff;
                padding: 2.5rem;
                border-radius: 16px;
                margin-bottom: 2rem;
            }
            .gaenity-dashboard-welcome {
                font-size: 2rem;
                margin: 0 0 0.5rem 0;
                font-weight: 700;
            }
            .gaenity-dashboard-email {
                opacity: 0.9;
                font-size: 1.1rem;
            }
            .gaenity-dashboard-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            .gaenity-stat-box {
                background: #fff;
                padding: 1.5rem;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                text-align: center;
                border: 2px solid #e2e8f0;
                transition: all 0.3s ease;
            }
            .gaenity-stat-box:hover {
                border-color: #1d4ed8;
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(29,78,216,0.15);
            }
            .gaenity-stat-number {
                font-size: 2.5rem;
                font-weight: 800;
                color: #1d4ed8;
                margin-bottom: 0.5rem;
            }
            .gaenity-stat-text {
                color: #64748b;
                font-weight: 600;
                font-size: 0.95rem;
            }
            .gaenity-dashboard-section {
                background: #fff;
                padding: 2rem;
                border-radius: 12px;
                margin-bottom: 2rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            }
            .gaenity-dashboard-section h3 {
                margin-top: 0;
                font-size: 1.4rem;
                color: #1e293b;
                margin-bottom: 1.5rem;
            }
            .gaenity-discussion-item-dashboard {
                padding: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 1rem;
                transition: all 0.2s ease;
            }
            .gaenity-discussion-item-dashboard:hover {
                border-color: #1d4ed8;
                background: #f8fafc;
                transform: translateX(4px);
            }
            .gaenity-discussion-item-dashboard h4 {
                margin: 0 0 0.5rem 0;
                font-size: 1.1rem;
            }
            .gaenity-discussion-item-dashboard h4 a {
                color: #1e293b;
                text-decoration: none;
            }
            .gaenity-discussion-item-dashboard h4 a:hover {
                color: #1d4ed8;
            }
            .gaenity-discussion-item-dashboard .meta {
                font-size: 0.85rem;
                color: #64748b;
            }
            .gaenity-quick-actions {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
                margin-top: 1.5rem;
            }
            .gaenity-quick-action-btn {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 1rem 1.5rem;
                background: #f1f5f9;
                border-radius: 10px;
                text-decoration: none;
                color: #1e293b;
                font-weight: 600;
                transition: all 0.2s ease;
                border: 2px solid transparent;
            }
            .gaenity-quick-action-btn:hover {
                background: #1d4ed8;
                color: #fff;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(29,78,216,0.3);
            }
        </style>

        <div class="gaenity-dashboard">
            <div class="gaenity-dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 class="gaenity-dashboard-welcome"><?php echo sprintf( esc_html__( 'Welcome back, %s!', 'gaenity-community' ), esc_html( $user->display_name ) ); ?></h2>
                        <p class="gaenity-dashboard-email"><?php echo esc_html( $user->user_email ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="padding: 0.75rem 1.5rem; background: #ef4444; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <?php esc_html_e( 'Log Out', 'gaenity-community' ); ?>
                    </a>
                </div>
            </div>

            <div class="gaenity-dashboard-stats">
                <div class="gaenity-stat-box">
                    <div class="gaenity-stat-number"><?php echo esc_html( $user_discussions->found_posts ); ?></div>
                    <div class="gaenity-stat-text"><?php esc_html_e( 'Discussions Posted', 'gaenity-community' ); ?></div>
                </div>
                <div class="gaenity-stat-box">
                    <div class="gaenity-stat-number"><?php echo esc_html( $downloads_count ); ?></div>
                    <div class="gaenity-stat-text"><?php esc_html_e( 'Resources Downloaded', 'gaenity-community' ); ?></div>
                </div>
                <div class="gaenity-stat-box">
                    <div class="gaenity-stat-number"><?php echo esc_html( $expert_requests ); ?></div>
                    <div class="gaenity-stat-text"><?php esc_html_e( 'Expert Requests', 'gaenity-community' ); ?></div>
                </div>
            </div>

            <div class="gaenity-dashboard-section">
                <h3><?php esc_html_e( 'Quick Actions', 'gaenity-community' ); ?></h3>
                <div class="gaenity-quick-actions">
                    <a href="#gaenity-discussion-form" class="gaenity-quick-action-btn">
                        <span>💬</span> <?php esc_html_e( 'Post Discussion', 'gaenity-community' ); ?>
                    </a>
                    <a href="#gaenity-resources" class="gaenity-quick-action-btn">
                        <span>📚</span> <?php esc_html_e( 'Browse Resources', 'gaenity-community' ); ?>
                    </a>
                    <a href="#gaenity-ask-expert" class="gaenity-quick-action-btn">
                        <span>💡</span> <?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?>
                    </a>
                    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="gaenity-quick-action-btn">
                        <span>🚪</span> <?php esc_html_e( 'Log Out', 'gaenity-community' ); ?>
                    </a>
                </div>
            </div>

            <?php if ( $user_discussions->have_posts() ) : ?>
            <div class="gaenity-dashboard-section">
                <h3><?php esc_html_e( 'Your Recent Discussions', 'gaenity-community' ); ?></h3>
                <?php while ( $user_discussions->have_posts() ) : $user_discussions->the_post(); ?>
                    <div class="gaenity-discussion-item-dashboard">
                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                        <p class="meta">
                            <?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'gaenity-community' ); ?> • 
                            <?php comments_number( __( 'No responses', 'gaenity-community' ), __( '1 response', 'gaenity-community' ), __( '% responses', 'gaenity-community' ) ); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
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
                                            <div class="gaenity-payment-logo">💳</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'Credit/Debit Card', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay securely with Stripe', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'paypal', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="paypal" required>
                                            <div class="gaenity-payment-logo">🅿️</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'PayPal', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay with your PayPal account', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'paystack', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="paystack" required>
                                            <div class="gaenity-payment-logo">💰</div>
                                            <div class="gaenity-payment-info">
                                                <h4><?php esc_html_e( 'Paystack', 'gaenity-community' ); ?></h4>
                                                <p><?php esc_html_e( 'Pay with card, bank transfer, or mobile money', 'gaenity-community' ); ?></p>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ( in_array( 'bank_transfer', $enabled_gateways ) ) : ?>
                                        <label class="gaenity-payment-method">
                                            <input type="radio" name="payment_gateway" value="bank_transfer" required>
                                            <div class="gaenity-payment-logo">🏦</div>
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
                                <strong>✓</strong> <?php esc_html_e( 'Secure payment processing', 'gaenity-community' ); ?><br>
                                <strong>✓</strong> <?php esc_html_e( 'Instant access after payment', 'gaenity-community' ); ?><br>
                                <strong>✓</strong> <?php esc_html_e( 'Email receipt included', 'gaenity-community' ); ?>
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
    public function render_polls_page() {
        ob_start();
        ?>
        <style>
            .gaenity-polls-page {
                max-width: 1000px;
                margin: 3rem auto;
                padding: 0 1rem;
            }
            .gaenity-polls-page h2 {
                text-align: center;
                font-size: 2.5rem;
                margin-bottom: 1rem;
                color: #1e293b;
            }
            .gaenity-polls-page > p {
                text-align: center;
                font-size: 1.15rem;
                color: #64748b;
                margin-bottom: 3rem;
            }
        </style>
        <div class="gaenity-polls-page">
            <h2><?php esc_html_e( 'Community Polls', 'gaenity-community' ); ?></h2>
            <p><?php esc_html_e( 'Share your perspective and see how the community responds to key business questions.', 'gaenity-community' ); ?></p>
            <?php echo do_shortcode( '[gaenity_polls]' ); ?>
        </div>
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
                                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">⏱️ <?php echo esc_html( $duration ); ?></p>
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
                    <div class="gaenity-hub-card-icon">✨</div>
                    <h3><?php esc_html_e( 'Join the Community', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Create your account and connect with peers across industries and regions.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Get Started →', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">💬</div>
                    <h3><?php esc_html_e( 'Forum', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Ask questions, share challenges, and learn from community discussions.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Browse Discussions →', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_expert_directory_url', home_url( '/experts' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">🎓</div>
                    <h3><?php esc_html_e( 'Experts', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Connect with vetted professionals for personalized guidance and support.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Meet Experts →', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_polls_url', home_url( '/polls' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">📊</div>
                    <h3><?php esc_html_e( 'Polls', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Participate in community polls and see what others think about key topics.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Take Polls →', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_resources_url', home_url( '/resources' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">📚</div>
                    <h3><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Access templates, guides, and tools to strengthen your business operations.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'Browse Resources →', 'gaenity-community' ); ?></span>
                </a>

                <a href="<?php echo esc_url( get_option( 'gaenity_courses_url', home_url( '/courses' ) ) ); ?>" class="gaenity-hub-card">
                    <div class="gaenity-hub-card-icon">🎯</div>
                    <h3><?php esc_html_e( 'Enablement Courses', 'gaenity-community' ); ?></h3>
                    <p><?php esc_html_e( 'Structured learning programs on risk, finance, and operational readiness.', 'gaenity-community' ); ?></p>
                    <span class="gaenity-hub-card-arrow"><?php esc_html_e( 'View Courses →', 'gaenity-community' ); ?></span>
                </a>
            </div>

            <div class="gaenity-hub-section">
                <h2><?php esc_html_e( 'Community Guidelines', 'gaenity-community' ); ?></h2>
                
                <div class="gaenity-guidelines-grid">
                    <div class="gaenity-guideline-item">
                        <h4>🤝 <?php esc_html_e( 'Be Respectful', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Treat all members with courtesy. Disagreement is fine, but personal attacks are not.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>💡 <?php esc_html_e( 'Share Real Experience', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Contribute authentic insights from your business journey to help others learn.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>🚫 <?php esc_html_e( 'No Spam or Selling', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Self-promotion without context disrupts the community. Share value first.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>🔒 <?php esc_html_e( 'Protect Privacy', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Don\'t share others\' confidential information. Respect anonymity when requested.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>🎯 <?php esc_html_e( 'Stay On Topic', 'gaenity-community' ); ?></h4>
                        <p><?php esc_html_e( 'Keep discussions relevant to business challenges, growth, and community support.', 'gaenity-community' ); ?></p>
                    </div>

                    <div class="gaenity-guideline-item">
                        <h4>⚠️ <?php esc_html_e( 'Report Issues', 'gaenity-community' ); ?></h4>
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
                                <span class="gaenity-expert-region">📍 <?php echo esc_html( $region ); ?></span>
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
            'gaenity_comment_reaction'  => 'handle_comment_reaction',  // ← ADD THIS LINE
        );

        foreach ( $actions as $action => $method ) {
            add_action( 'wp_ajax_' . $action, array( $this, $method ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
        }
    }    /**
     * Handle resource download submission.
     */
    public function handle_resource_download() {
        $this->verify_nonce();

        $resource_id = isset( $_POST['resource_id'] ) ? absint( $_POST['resource_id'] ) : 0;
        $email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
        $industry    = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
        $other       = isset( $_POST['industry_other'] ) ? sanitize_text_field( wp_unslash( $_POST['industry_other'] ) ) : '';
        $consent     = isset( $_POST['consent'] ) ? 1 : 0;
        $download    = isset( $_POST['download_url'] ) ? esc_url_raw( wp_unslash( $_POST['download_url'] ) ) : '';

        if ( empty( $resource_id ) || empty( $email ) || empty( $role ) || empty( $industry ) ) {
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

        wp_send_json_success(
            array(
                'message'      => __( 'Thanks! Your download will begin shortly.', 'gaenity-community' ),
                'download_url' => $download,
            )
        );
    }

    /**
     * Handle community registration.
     */
    public function handle_user_registration() {
        $this->verify_nonce();

        $required = array( 'full_name', 'display_name', 'email', 'password', 'role', 'region', 'country', 'industry', 'challenge', 'goals' );
        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
            }
        }

        if ( empty( $_POST['guidelines'] ) ) {
            wp_send_json_error( array( 'message' => __( 'You must agree to the community guidelines.', 'gaenity-community' ) ) );
        }

        $email        = sanitize_email( wp_unslash( $_POST['email'] ) );
        $display_name = sanitize_text_field( wp_unslash( $_POST['display_name'] ) );
        $full_name    = sanitize_text_field( wp_unslash( $_POST['full_name'] ) );
        $password     = wp_unslash( $_POST['password'] );
        $role         = sanitize_text_field( wp_unslash( $_POST['role'] ) );
        $region       = sanitize_text_field( wp_unslash( $_POST['region'] ) );
        $country      = sanitize_text_field( wp_unslash( $_POST['country'] ) );
        $industry     = sanitize_text_field( wp_unslash( $_POST['industry'] ) );
        $challenge    = sanitize_text_field( wp_unslash( $_POST['challenge'] ) );
        $goals        = wp_kses_post( wp_unslash( $_POST['goals'] ) );
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
                'redirect' => apply_filters( 'gaenity_registration_redirect', home_url() ),
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

        $fields = array( 'name', 'email', 'role', 'region', 'country', 'industry', 'challenge', 'description', 'budget', 'preference' );
        foreach ( $fields as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
            }
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_expert_requests',
            array(
                'user_id'    => get_current_user_id(),
                'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
                'email'      => sanitize_email( wp_unslash( $_POST['email'] ) ),
                'role'       => sanitize_text_field( wp_unslash( $_POST['role'] ) ),
                'region'     => sanitize_text_field( wp_unslash( $_POST['region'] ) ),
                'country'    => sanitize_text_field( wp_unslash( $_POST['country'] ) ),
                'industry'   => sanitize_text_field( wp_unslash( $_POST['industry'] ) ),
                'challenge'  => sanitize_text_field( wp_unslash( $_POST['challenge'] ) ),
                'description'=> wp_kses_post( wp_unslash( $_POST['description'] ) ),
                'budget'     => sanitize_text_field( wp_unslash( $_POST['budget'] ) ),
                'preference' => sanitize_text_field( wp_unslash( $_POST['preference'] ) ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        wp_send_json_success( array( 'message' => __( 'Your request has been submitted. We will be in touch soon.', 'gaenity-community' ) ) );
    }

    /**
     * Handle expert registration submissions.
     */
    public function handle_expert_registration() {
        $this->verify_nonce();

        $fields = array( 'name', 'email', 'expertise', 'profile_url' );
        foreach ( $fields as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
            }
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_expert_requests',
            array(
                'user_id'    => get_current_user_id(),
                'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
                'email'      => sanitize_email( wp_unslash( $_POST['email'] ) ),
                'role'       => 'Expert Applicant',
                'region'     => '',
                'country'    => '',
                'industry'   => '',
                'challenge'  => 'expert_registration',
                'description'=> wp_kses_post( wp_unslash( $_POST['expertise'] ) ),
                'budget'     => sanitize_text_field( wp_unslash( $_POST['profile_url'] ) ),
                'preference' => 'expert_registration',
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        wp_send_json_success( array( 'message' => __( 'Thanks! Our team will review your expert application.', 'gaenity-community' ) ) );
    }

    /**
     * Handle contact form submissions.
     */
    public function handle_contact_submission() {
        $this->verify_nonce();

        $fields = array( 'name', 'email', 'subject', 'message' );
        foreach ( $fields as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => __( 'Please complete all required fields.', 'gaenity-community' ) ) );
            }
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gaenity_contact_messages',
            array(
                'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
                'email'   => sanitize_email( wp_unslash( $_POST['email'] ) ),
                'subject' => sanitize_text_field( wp_unslash( $_POST['subject'] ) ),
                'message' => wp_kses_post( wp_unslash( $_POST['message'] ) ),
                'updates' => ! empty( $_POST['updates'] ) ? 1 : 0,
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

        $output  = '<div class="gaenity-resources-section">';
        $output .= '<div class="gaenity-section-header">';
        $output .= '<h2>' . esc_html__( 'Practical tools that turn ideas into action.', 'gaenity-community' ) . '</h2>';
        $output .= '<p>' . esc_html__( 'From risk management checklists to finance enablement guides and operational templates, each resource is designed to help businesses build resilience, prepare for growth, and make measurable progress.', 'gaenity-community' ) . '</p>';
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
                        $output .= '<a href="' . esc_url( add_query_arg( array( 'type' => 'resource', 'resource_id' => $resource_id ), get_option( 'gaenity_checkout_url', home_url( '/checkout' ) ) ) ) . '" class="gaenity-button">' . esc_html__( 'Buy Now', 'gaenity-community' ) . '</a>';
                        $output .= '</div>';
                    }
                    $output .= '</div>';
                    $output .= '</article>';

                    if ( 'free' === $type && ! empty( $download_url ) ) {
                        $output .= $this->get_resource_form_markup( $resource_id, $download_url );
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
                        <label for="gaenity_industry_other_<?php echo esc_attr( $resource_id ); ?>" class="gaenity-hidden">&nbsp;</label>
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
     * Render community home.
     */
   /**
     * Render community home with enhanced filtering and stats.
     */
    /**
     * Render community home with enhanced styling.
     */
    public function render_community_home_shortcode() {
        // Get discussion counts by taxonomy
        $region_counts = $this->get_taxonomy_discussion_counts( 'gaenity_region' );
        $industry_counts = $this->get_taxonomy_discussion_counts( 'gaenity_industry' );
        $challenge_counts = $this->get_taxonomy_discussion_counts( 'gaenity_challenge' );
        
        // Get total discussions
        $total_discussions = wp_count_posts( 'gaenity_discussion' )->publish;
        $total_members = count_users()['total_users'];
        
        ob_start();
        ?>
        <style>
            .gaenity-community-home {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 1rem;
            }
            .gaenity-home-header {
                background: linear-gradient(135deg, rgba(29, 78, 216, 0.08), rgba(139, 92, 246, 0.08));
                border-radius: 20px;
                padding: 3rem 2.5rem;
                margin-bottom: 3rem;
            }
            .gaenity-home-intro h2 {
                font-size: 2.5rem;
                margin: 0 0 1rem 0;
                color: #1a202c;
                font-weight: 700;
            }
            .gaenity-home-intro p {
                font-size: 1.15rem;
                color: #4a5568;
                max-width: 900px;
                line-height: 1.7;
                margin: 0;
            }
            .gaenity-home-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                margin: 2.5rem 0;
            }
            .gaenity-stat-card {
                background: #fff;
                padding: 1.75rem;
                border-radius: 16px;
                display: flex;
                align-items: center;
                gap: 1.25rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid rgba(29, 78, 216, 0.1);
            }
            .gaenity-stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 30px rgba(29, 78, 216, 0.15);
            }
            .gaenity-stat-icon {
                font-size: 2.5rem;
                line-height: 1;
            }
            .gaenity-stat-value {
                font-size: 2rem;
                font-weight: 800;
                color: #1d4ed8;
                line-height: 1;
                margin-bottom: 0.3rem;
            }
            .gaenity-stat-label {
                font-size: 0.9rem;
                color: #64748b;
                font-weight: 500;
            }
            .gaenity-cta-group {
                display: flex;
                flex-wrap: wrap;
                gap: 1.25rem;
                margin-top: 2.5rem;
            }
            .gaenity-button {
                display: inline-flex;
                align-items: center;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #1d4ed8, #1e40af);
                color: #fff;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 600;
                font-size: 1rem;
                transition: all 0.3s ease;
                border: 2px solid transparent;
                box-shadow: 0 4px 15px rgba(29, 78, 216, 0.3);
            }
            .gaenity-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(29, 78, 216, 0.4);
                color: #fff;
            }
            .gaenity-button.ghost {
                background: transparent;
                color: #1d4ed8;
                border: 2px solid #1d4ed8;
                box-shadow: none;
            }
            .gaenity-button.ghost:hover {
                background: #1d4ed8;
                color: #fff;
            }
            .gaenity-button-icon {
                margin-right: 0.6rem;
                font-size: 1.2rem;
            }
            .gaenity-forum-structure {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin: 3rem 0;
            }
            .gaenity-forum-section {
                background: #fff;
                border-radius: 16px;
                padding: 2rem;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
                border: 1px solid #e2e8f0;
                transition: all 0.3s ease;
            }
            .gaenity-forum-section:hover {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }
            .gaenity-section-title {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1.5rem;
                padding-bottom: 1.25rem;
                border-bottom: 3px solid #f1f5f9;
            }
            .gaenity-section-icon {
                font-size: 1.8rem;
            }
            .gaenity-section-title h3 {
                margin: 0;
                font-size: 1.25rem;
                color: #1e293b;
                font-weight: 700;
            }
            .gaenity-quick-links,
            .gaenity-topic-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 0.5rem;
            }
            .gaenity-quick-links li a {
                display: flex;
                align-items: center;
                gap: 0.9rem;
                padding: 0.9rem 1.1rem;
                border-radius: 10px;
                transition: all 0.2s ease;
                color: #475569;
                text-decoration: none;
                font-weight: 500;
            }
            .gaenity-quick-links li a:hover {
                background: linear-gradient(135deg, rgba(29, 78, 216, 0.08), rgba(139, 92, 246, 0.08));
                color: #1d4ed8;
                transform: translateX(6px);
            }
            .gaenity-link-icon {
                font-size: 1.3rem;
            }
            .gaenity-topic-link {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.8rem 1.1rem;
                border-radius: 10px;
                transition: all 0.2s ease;
                color: #475569;
                text-decoration: none;
                border: 1px solid transparent;
                font-weight: 500;
            }
            .gaenity-topic-link:hover {
                background: linear-gradient(135deg, rgba(29, 78, 216, 0.06), rgba(139, 92, 246, 0.06));
                border-color: rgba(29, 78, 216, 0.3);
                color: #1d4ed8;
                transform: translateX(4px);
            }
            .gaenity-topic-name {
                flex: 1;
            }
            .gaenity-topic-count {
                background: #e0e7ff;
                color: #3730a3;
                padding: 0.25rem 0.7rem;
                border-radius: 999px;
                font-size: 0.85rem;
                font-weight: 700;
                min-width: 32px;
                text-align: center;
            }
            .gaenity-topic-link:hover .gaenity-topic-count {
                background: #1d4ed8;
                color: #fff;
            }
            .gaenity-recent-activity {
                background: #fff;
                border-radius: 16px;
                padding: 2.5rem;
                margin-top: 3rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: 1px solid #e2e8f0;
            }
            .gaenity-recent-activity h3 {
                margin: 0 0 2rem 0;
                font-size: 1.6rem;
                color: #1e293b;
                font-weight: 700;
            }
            .gaenity-recent-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 1.25rem;
            }
            .gaenity-recent-item {
                display: block;
                padding: 1.25rem 1.5rem;
                border-radius: 12px;
                border: 1px solid #e2e8f0;
                transition: all 0.3s ease;
                text-decoration: none;
                background: #fafbfc;
            }
            .gaenity-recent-item:hover {
                border-color: #1d4ed8;
                background: #fff;
                transform: translateX(6px);
                box-shadow: 0 6px 20px rgba(29, 78, 216, 0.12);
            }
            .gaenity-recent-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 0.65rem;
            }
            .gaenity-recent-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.8rem;
                align-items: center;
                font-size: 0.9rem;
            }
            .gaenity-meta-badge {
                background: #e0e7ff;
                color: #3730a3;
                padding: 0.25rem 0.7rem;
                border-radius: 6px;
                font-weight: 600;
                font-size: 0.85rem;
            }
            .gaenity-meta-date {
                color: #64748b;
                font-weight: 500;
            }
            .gaenity-view-all {
                margin-top: 2rem;
                text-align: center;
            }
            @media (max-width: 768px) {
                .gaenity-home-header {
                    padding: 2rem 1.5rem;
                }
                .gaenity-home-intro h2 {
                    font-size: 1.8rem;
                }
                .gaenity-forum-structure {
                    grid-template-columns: 1fr;
                }
                .gaenity-cta-group {
                    flex-direction: column;
                }
                .gaenity-cta-group .gaenity-button {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>

        <section class="gaenity-community-home">
            <header class="gaenity-home-header">
                <div class="gaenity-home-intro">
                    <h2><?php esc_html_e( 'Welcome to the Gaenity Community', 'gaenity-community' ); ?></h2>
                    <p><?php esc_html_e( 'Connect with business owners, entrepreneurs, and professionals who share practical solutions. Join to ask questions, post challenges, and learn from peers and experts worldwide.', 'gaenity-community' ); ?></p>
                </div>
                
                <div class="gaenity-home-stats">
                    <div class="gaenity-stat-card">
                        <span class="gaenity-stat-icon">👥</span>
                        <div>
                            <div class="gaenity-stat-value"><?php echo esc_html( number_format( $total_members ) ); ?></div>
                            <div class="gaenity-stat-label"><?php esc_html_e( 'Active Members', 'gaenity-community' ); ?></div>
                        </div>
                    </div>
                    <div class="gaenity-stat-card">
                        <span class="gaenity-stat-icon">💬</span>
                        <div>
                            <div class="gaenity-stat-value"><?php echo esc_html( number_format( $total_discussions ) ); ?></div>
                            <div class="gaenity-stat-label"><?php esc_html_e( 'Discussions', 'gaenity-community' ); ?></div>
                        </div>
                    </div>
                    <div class="gaenity-stat-card">
                        <span class="gaenity-stat-icon">🌍</span>
                        <div>
                            <div class="gaenity-stat-value"><?php echo esc_html( count( $this->get_region_options() ) ); ?></div>
                            <div class="gaenity-stat-label"><?php esc_html_e( 'Regions', 'gaenity-community' ); ?></div>
                        </div>
                    </div>
                </div>

               <div class="gaenity-cta-group">
                    <a class="gaenity-button" href="<?php echo esc_url( get_option( 'gaenity_register_url', '#gaenity-register' ) ); ?>">
                        <span class="gaenity-button-icon">✨</span>
                        <?php esc_html_e( 'Create your account', 'gaenity-community' ); ?>
                    </a>
                    <a class="gaenity-button ghost" href="<?php echo esc_url( get_option( 'gaenity_ask_expert_url', '#gaenity-ask-expert' ) ); ?>">
                        <span class="gaenity-button-icon">💡</span>
                        <?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?>
                    </a>
                    <a class="gaenity-button ghost" href="<?php echo esc_url( get_option( 'gaenity_become_expert_url', '#gaenity-register-expert' ) ); ?>">
                        <span class="gaenity-button-icon">🎓</span>
                        <?php esc_html_e( 'Become an Expert', 'gaenity-community' ); ?>
                    </a>
                </div>
            </header>

            <div class="gaenity-forum-structure">
                <div class="gaenity-forum-section">
                    <div class="gaenity-section-title">
                        <span class="gaenity-section-icon">🚀</span>
                        <h3><?php esc_html_e( 'Getting Started', 'gaenity-community' ); ?></h3>
                    </div>
                    <ul class="gaenity-quick-links">
                        <li>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>">
                                <span class="gaenity-link-icon">👋</span>
                                <?php esc_html_e( 'Introductions', 'gaenity-community' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>">
                                <span class="gaenity-link-icon">📢</span>
                                <?php esc_html_e( 'Community Updates', 'gaenity-community' ); ?>
                            </a>
                        </li>
                       <li>
                            <a href="<?php echo esc_url( get_option( 'gaenity_resources_url', '#gaenity-resources' ) ); ?>">
                                <span class="gaenity-link-icon">📚</span>
                                <?php esc_html_e( 'Browse Resources', 'gaenity-community' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="gaenity-forum-section">
                    <div class="gaenity-section-title">
                        <span class="gaenity-section-icon">🌍</span>
                        <h3><?php esc_html_e( 'Regions', 'gaenity-community' ); ?></h3>
                    </div>
                    <ul class="gaenity-topic-list">
                        <?php foreach ( $this->get_region_options() as $region ) : 
                            $count = isset( $region_counts[ $region ] ) ? $region_counts[ $region ] : 0;
                            $url = add_query_arg( 
                                array( 'gaenity_region' => rawurlencode( $region ) ), 
                                get_post_type_archive_link( 'gaenity_discussion' ) 
                            );
                        ?>
                            <li>
                                <a href="<?php echo esc_url( $url ); ?>" class="gaenity-topic-link">
                                    <span class="gaenity-topic-name"><?php echo esc_html( $region ); ?></span>
                                    <span class="gaenity-topic-count"><?php echo esc_html( $count ); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="gaenity-forum-section">
                    <div class="gaenity-section-title">
                        <span class="gaenity-section-icon">🏢</span>
                        <h3><?php esc_html_e( 'Industries', 'gaenity-community' ); ?></h3>
                    </div>
                    <ul class="gaenity-topic-list">
                        <?php foreach ( $this->get_industry_options() as $industry ) : 
                            $count = isset( $industry_counts[ $industry ] ) ? $industry_counts[ $industry ] : 0;
                            $url = add_query_arg( 
                                array( 'gaenity_industry' => rawurlencode( $industry ) ), 
                                get_post_type_archive_link( 'gaenity_discussion' ) 
                            );
                        ?>
                            <li>
                                <a href="<?php echo esc_url( $url ); ?>" class="gaenity-topic-link">
                                    <span class="gaenity-topic-name"><?php echo esc_html( $industry ); ?></span>
                                    <span class="gaenity-topic-count"><?php echo esc_html( $count ); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-topic-link">
                                <span class="gaenity-topic-name"><?php esc_html_e( 'Other Industries', 'gaenity-community' ); ?></span>
                                <span class="gaenity-topic-count">—</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="gaenity-forum-section">
                    <div class="gaenity-section-title">
                        <span class="gaenity-section-icon">🎯</span>
                        <h3><?php esc_html_e( 'Common Challenges', 'gaenity-community' ); ?></h3>
                    </div>
                    <ul class="gaenity-topic-list">
                        <?php foreach ( $this->get_challenge_options() as $challenge ) : 
                            $count = isset( $challenge_counts[ $challenge ] ) ? $challenge_counts[ $challenge ] : 0;
                            $url = add_query_arg( 
                                array( 'gaenity_challenge' => rawurlencode( $challenge ) ), 
                                get_post_type_archive_link( 'gaenity_discussion' ) 
                            );
                        ?>
                            <li>
                                <a href="<?php echo esc_url( $url ); ?>" class="gaenity-topic-link">
                                    <span class="gaenity-topic-name"><?php echo esc_html( $challenge ); ?></span>
                                    <span class="gaenity-topic-count"><?php echo esc_html( $count ); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="gaenity-recent-activity">
                <h3><?php esc_html_e( 'Recent Discussions', 'gaenity-community' ); ?></h3>
                <?php
                $recent_discussions = new WP_Query( array(
                    'post_type' => 'gaenity_discussion',
                    'posts_per_page' => 5,
                    'post_status' => 'publish',
                ) );

                if ( $recent_discussions->have_posts() ) :
                ?>
                    <ul class="gaenity-recent-list">
                        <?php while ( $recent_discussions->have_posts() ) : $recent_discussions->the_post(); 
                            $regions = wp_get_post_terms( get_the_ID(), 'gaenity_region', array( 'fields' => 'names' ) );
                            $industries = wp_get_post_terms( get_the_ID(), 'gaenity_industry', array( 'fields' => 'names' ) );
                        ?>
                            <li>
                                <a href="<?php the_permalink(); ?>" class="gaenity-recent-item">
                                    <div class="gaenity-recent-title"><?php the_title(); ?></div>
                                    <div class="gaenity-recent-meta">
                                        <?php if ( ! empty( $regions ) ) : ?>
                                            <span class="gaenity-meta-badge">📍 <?php echo esc_html( $regions[0] ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $industries ) ) : ?>
                                            <span class="gaenity-meta-badge">🏢 <?php echo esc_html( $industries[0] ); ?></span>
                                        <?php endif; ?>
                                        <span class="gaenity-meta-date"><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> ago</span>
                                    </div>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <div class="gaenity-view-all">
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'gaenity_discussion' ) ); ?>" class="gaenity-button ghost">
                            <?php esc_html_e( 'View All Discussions →', 'gaenity-community' ); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <p style="text-align: center; padding: 2rem; color: #64748b;"><?php esc_html_e( 'No discussions yet. Be the first to start a conversation!', 'gaenity-community' ); ?></p>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </section>
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
            return '<p class="gaenity-notice">' . esc_html__( 'You are already part of the community.', 'gaenity-community' ) . '</p>';
        }

        ob_start();
        ?>
        <form id="gaenity-register" class="gaenity-form gaenity-ajax-form" data-success-redirect="<?php echo esc_url( home_url() ); ?>">
            <h3><?php esc_html_e( 'Join the Gaenity Community', 'gaenity-community' ); ?></h3>
            <input type="hidden" name="action" value="gaenity_user_register" />
            <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
            <p>
                <label for="gaenity_full_name"><?php esc_html_e( 'Full Name', 'gaenity-community' ); ?></label>
                <input type="text" id="gaenity_full_name" name="full_name" required />
            </p>
            <p>
                <label for="gaenity_display_name"><?php esc_html_e( 'Display Name', 'gaenity-community' ); ?></label>
                <input type="text" id="gaenity_display_name" name="display_name" required />
            </p>
            <p>
                <label for="gaenity_email_register"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                <input type="email" id="gaenity_email_register" name="email" required />
            </p>
            <p>
                <label for="gaenity_password"><?php esc_html_e( 'Password', 'gaenity-community' ); ?></label>
                <input type="password" id="gaenity_password" name="password" required />
            </p>
            <p>
                <label for="gaenity_role_title"><?php esc_html_e( 'Role / Title', 'gaenity-community' ); ?></label>
                <select id="gaenity_role_title" name="role" required>
                    <option value=""><?php esc_html_e( 'Select role', 'gaenity-community' ); ?></option>
                    <option value="Business Owner"><?php esc_html_e( 'Business Owner', 'gaenity-community' ); ?></option>
                    <option value="Employed Professional"><?php esc_html_e( 'Employed Professional', 'gaenity-community' ); ?></option>
                    <option value="Forum Expert"><?php esc_html_e( 'Forum Expert', 'gaenity-community' ); ?></option>
                </select>
            </p>
            <p>
                <label for="gaenity_region"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                <select id="gaenity_region" name="region" required>
                    <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_region_options() as $region ) : ?>
                        <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="gaenity_country"><?php esc_html_e( 'Country', 'gaenity-community' ); ?></label>
                <input type="text" id="gaenity_country" name="country" required />
            </p>
            <p>
                <label for="gaenity_industry"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                <select id="gaenity_industry" name="industry" required>
                    <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                        <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                    <?php endforeach; ?>
                    <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                </select>
            </p>
            <p>
                <label for="gaenity_primary_challenge"><?php esc_html_e( 'Primary challenge right now', 'gaenity-community' ); ?></label>
                <select id="gaenity_primary_challenge" name="challenge" required>
                    <option value=""><?php esc_html_e( 'Select challenge', 'gaenity-community' ); ?></option>
                    <?php foreach ( $this->get_challenge_options() as $challenge ) : ?>
                        <option value="<?php echo esc_attr( $challenge ); ?>"><?php echo esc_html( $challenge ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="gaenity_goals"><?php esc_html_e( 'Goals for joining', 'gaenity-community' ); ?></label>
                <textarea id="gaenity_goals" name="goals" rows="3" required></textarea>
            </p>
            <p class="gaenity-checkbox">
                <label>
                    <input type="checkbox" name="guidelines" value="1" required />
                    <?php esc_html_e( 'I agree to the community guidelines', 'gaenity-community' ); ?>
                </label>
            </p>
            <p class="gaenity-checkbox">
                <label>
                    <input type="checkbox" name="updates" value="1" />
                    <?php esc_html_e( 'I agree to receive updates from Gaenity', 'gaenity-community' ); ?>
                </label>
            </p>
            <div class="gaenity-community-guidelines">
                <h4><?php esc_html_e( 'Community guidelines', 'gaenity-community' ); ?></h4>
                <ul>
                    <li><?php esc_html_e( 'Be respectful and constructive', 'gaenity-community' ); ?></li>
                    <li><?php esc_html_e( 'Share real experiences', 'gaenity-community' ); ?></li>
                    <li><?php esc_html_e( 'No spam or selling', 'gaenity-community' ); ?></li>
                    <li><?php esc_html_e( 'Protect privacy', 'gaenity-community' ); ?></li>
                    <li><?php esc_html_e( 'Repeated violations may result in removal', 'gaenity-community' ); ?></li>
                </ul>
            </div>
            <p>
                <button type="submit" class="gaenity-button"><?php esc_html_e( 'Join now', 'gaenity-community' ); ?></button>
            </p>
            <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
        </form>
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
    public function render_expert_request_form() {
        ob_start();
        ?>
        <section id="gaenity-ask-expert" class="gaenity-expert-request">
            <h3><?php esc_html_e( 'Ask an Expert', 'gaenity-community' ); ?></h3>
            <p class="gaenity-intro"><?php esc_html_e( 'Need guidance beyond the community? Our vetted experts are here to help. Post your question, set your budget, and get actionable advice. Experts are rated by members and paid fairly for their insights.', 'gaenity-community' ); ?></p>
            <ol class="gaenity-mini-process">
                <li><strong><?php esc_html_e( 'Post your request', 'gaenity-community' ); ?></strong> — <?php esc_html_e( 'Share your challenge in Risk, Finance, or Operations.', 'gaenity-community' ); ?></li>
                <li><strong><?php esc_html_e( 'Connect with an expert', 'gaenity-community' ); ?></strong> — <?php esc_html_e( 'We\'ll match you with the right advisor.', 'gaenity-community' ); ?></li>
                <li><strong><?php esc_html_e( 'Pay securely', 'gaenity-community' ); ?></strong> — <?php esc_html_e( 'Experts are compensated, and you get clear answers.', 'gaenity-community' ); ?></li>
            </ol>
            <form class="gaenity-form gaenity-ajax-form">
                <input type="hidden" name="action" value="gaenity_expert_request" />
                <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                <p>
                    <label for="gaenity_request_name"><?php esc_html_e( 'Your name', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_request_name" name="name" required />
                </p>
                <p>
                    <label for="gaenity_request_email"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                    <input type="email" id="gaenity_request_email" name="email" required />
                </p>
                <p>
                    <label for="gaenity_request_role"><?php esc_html_e( 'Role', 'gaenity-community' ); ?></label>
                    <select id="gaenity_request_role" name="role" required>
                        <option value=""><?php esc_html_e( 'Select role', 'gaenity-community' ); ?></option>
                        <option value="Business Owner"><?php esc_html_e( 'Business Owner', 'gaenity-community' ); ?></option>
                        <option value="Professional"><?php esc_html_e( 'Professional', 'gaenity-community' ); ?></option>
                    </select>
                </p>
                <p>
                    <label for="gaenity_request_region"><?php esc_html_e( 'Region', 'gaenity-community' ); ?></label>
                    <select id="gaenity_request_region" name="region" required>
                        <option value=""><?php esc_html_e( 'Select region', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_region_options() as $region ) : ?>
                            <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="gaenity_request_country"><?php esc_html_e( 'Country', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_request_country" name="country" required />
                </p>
                <p>
                    <label for="gaenity_request_industry"><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></label>
                    <select id="gaenity_request_industry" name="industry" required>
                        <option value=""><?php esc_html_e( 'Select industry', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_industry_options() as $industry ) : ?>
                            <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                        <?php endforeach; ?>
                        <option value="Other"><?php esc_html_e( 'Other', 'gaenity-community' ); ?></option>
                    </select>
                </p>
                <p>
                    <label for="gaenity_request_challenge"><?php esc_html_e( 'Challenge / Question', 'gaenity-community' ); ?></label>
                    <select id="gaenity_request_challenge" name="challenge" required>
                        <option value=""><?php esc_html_e( 'Select challenge area', 'gaenity-community' ); ?></option>
                        <?php foreach ( $this->get_challenge_options() as $challenge ) : ?>
                            <option value="<?php echo esc_attr( $challenge ); ?>"><?php echo esc_html( $challenge ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="gaenity_request_description"><?php esc_html_e( 'Describe your challenge', 'gaenity-community' ); ?></label>
                    <textarea id="gaenity_request_description" name="description" rows="4" required></textarea>
                </p>
                <p>
                    <label for="gaenity_request_budget"><?php esc_html_e( 'Preferred budget', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_request_budget" name="budget" placeholder="<?php esc_attr_e( 'e.g. $150 for email advice', 'gaenity-community' ); ?>" required />
                </p>
                <p>
                    <label for="gaenity_request_preference"><?php esc_html_e( 'Preferred engagement', 'gaenity-community' ); ?></label>
                    <select id="gaenity_request_preference" name="preference" required>
                        <option value=""><?php esc_html_e( 'Select option', 'gaenity-community' ); ?></option>
                        <option value="email"><?php esc_html_e( 'Email consultation', 'gaenity-community' ); ?></option>
                        <option value="virtual_meeting"><?php esc_html_e( '30 minute virtual meeting', 'gaenity-community' ); ?></option>
                    </select>
                </p>
                <p>
                    <button type="submit" class="gaenity-button"><?php esc_html_e( 'Submit request', 'gaenity-community' ); ?></button>
                </p>
                <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
            </form>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render expert registration form.
     */
    public function render_expert_register_form() {
        ob_start();
        ?>
        <section id="gaenity-register-expert" class="gaenity-expert-register">
            <h3><?php esc_html_e( 'Register as an Expert', 'gaenity-community' ); ?></h3>
            <p class="gaenity-intro"><?php esc_html_e( 'Share your experience with entrepreneurs who need practical advice in risk, finance, and operations. Approved experts receive tailored requests and fair compensation.', 'gaenity-community' ); ?></p>
            <form class="gaenity-form gaenity-ajax-form">
                <input type="hidden" name="action" value="gaenity_expert_register" />
                <?php wp_nonce_field( 'gaenity-community', 'gaenity_nonce' ); ?>
                <p>
                    <label for="gaenity_expert_name"><?php esc_html_e( 'Full name', 'gaenity-community' ); ?></label>
                    <input type="text" id="gaenity_expert_name" name="name" required />
                </p>
                <p>
                    <label for="gaenity_expert_email"><?php esc_html_e( 'Email', 'gaenity-community' ); ?></label>
                    <input type="email" id="gaenity_expert_email" name="email" required />
                </p>
                <p>
                    <label for="gaenity_expert_expertise"><?php esc_html_e( 'Areas of expertise', 'gaenity-community' ); ?></label>
                    <textarea id="gaenity_expert_expertise" name="expertise" rows="3" required></textarea>
                </p>
                <p>
                    <label for="gaenity_expert_linkedin"><?php esc_html_e( 'LinkedIn or portfolio URL', 'gaenity-community' ); ?></label>
                    <input type="url" id="gaenity_expert_linkedin" name="profile_url" required />
                </p>
                <p>
                    <button type="submit" class="gaenity-button"><?php esc_html_e( 'Submit application', 'gaenity-community' ); ?></button>
                </p>
                <div class="gaenity-form-feedback" role="alert" aria-live="polite"></div>
            </form>
        </section>
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
    }
    /**
     * Fix discussion archive link for back button
     */
    public function fix_discussion_archive_link( $link, $post_type ) {
        if ( 'gaenity_discussion' === $post_type ) {
            // Check if there's a custom discussions page set
            $custom_page_id = get_option( 'gaenity_discussions_page_id' );
            if ( $custom_page_id ) {
                return get_permalink( $custom_page_id );
            }
        }
        return $link;
    }

    /**
     * Custom comment callback with reactions
     */
    public function custom_comment_callback( $comment, $args, $depth ) {
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
/**
     * Handle comment reaction AJAX request
     */
    public function handle_comment_reaction() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'gaenity-comment-reaction' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'gaenity-community' ) ) );
        }

        // Get parameters
        $comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
        $reaction_type = isset( $_POST['reaction_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reaction_type'] ) ) : '';

        if ( ! $comment_id || ! in_array( $reaction_type, array( 'like', 'dislike' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'gaenity-community' ) ) );
        }

        // Verify comment exists
        $comment = get_comment( $comment_id );
        if ( ! $comment ) {
            wp_send_json_error( array( 'message' => __( 'Comment not found', 'gaenity-community' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'gaenity_comment_reactions';
        
        $user_id = get_current_user_id();
        $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        // Check if user already reacted
        $existing_reaction = null;
        if ( $user_id ) {
            $existing_reaction = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_name WHERE comment_id = %d AND user_id = %d",
                $comment_id,
                $user_id
            ) );
        } elseif ( $ip_address ) {
            $existing_reaction = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_name WHERE comment_id = %d AND ip_address = %s",
                $comment_id,
                $ip_address
            ) );
        }

        $current_reaction = null;

        if ( $existing_reaction ) {
            if ( $existing_reaction->reaction_type === $reaction_type ) {
                // Same reaction - remove it (toggle off)
                $wpdb->delete( $table_name, array( 'id' => $existing_reaction->id ), array( '%d' ) );
            } else {
                // Different reaction - update it
                $wpdb->update(
                    $table_name,
                    array( 'reaction_type' => $reaction_type ),
                    array( 'id' => $existing_reaction->id ),
                    array( '%s' ),
                    array( '%d' )
                );
                $current_reaction = $reaction_type;
            }
        } else {
            // New reaction - insert it
            $wpdb->insert(
                $table_name,
                array(
                    'comment_id' => $comment_id,
                    'user_id' => $user_id,
                    'ip_address' => $ip_address,
                    'reaction_type' => $reaction_type,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s' )
            );
            $current_reaction = $reaction_type;
        }

        // Get updated counts
        $likes = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE comment_id = %d AND reaction_type = 'like'",
            $comment_id
        ) );
        
        $dislikes = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE comment_id = %d AND reaction_type = 'dislike'",
            $comment_id
        ) );

        wp_send_json_success( array(
            'likes' => absint( $likes ),
            'dislikes' => absint( $dislikes ),
            'user_reaction' => $current_reaction,
            'message' => __( 'Reaction recorded!', 'gaenity-community' ),
        ) );
    }
}
endif;