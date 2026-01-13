<?php
/**
 * Core loader for the Gaenity Support Hub plugin.
 *
 * @package GaenitySupportHub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 */
class Gaenity_Support_Hub {

    /**
     * Plugin version.
     */
    const VERSION = '3.0.0';

    /**
     * Option key for landing page.
     *
     * @var string
     */
    const PAGE_OPTION = 'gaenity_support_hub_page_id';

    /**
     * Singleton instance.
     *
     * @var Gaenity_Support_Hub|null
     */
    protected static $instance = null;

    /**
     * Retrieve singleton instance.
     *
     * @return Gaenity_Support_Hub
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }

        return self::$instance;
    }

    /**
     * Activation callback.
     */
    public static function activate() {
        $page_id = (int) get_option( self::PAGE_OPTION );

        if ( $page_id && 'page' === get_post_type( $page_id ) ) {
            return;
        }

        $page_args = array(
            'post_title'   => __( 'Gaenity Support Hub', 'gaenity-community' ),
            'post_content' => '[gaenity_support_hub]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $page_id = wp_insert_post( $page_args );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( self::PAGE_OPTION, (int) $page_id );
        }
    }

    /**
     * Deactivation callback.
     */
    public static function deactivate() {
        $page_id = (int) get_option( self::PAGE_OPTION );

        if ( $page_id && 'page' === get_post_type( $page_id ) ) {
            wp_trash_post( $page_id );
        }

        delete_option( self::PAGE_OPTION );
    }

    /**
     * Hook registration.
     */
    protected function setup_hooks() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( GAENITY_SUPPORT_HUB_FILE ), array( $this, 'action_links' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );
    }

    /**
     * Register scripts and styles.
     */
    public function register_assets() {
        wp_register_style(
            'gaenity-support-hub',
            GAENITY_SUPPORT_HUB_URL . 'assets/css/support-hub.css',
            array(),
            self::VERSION
        );

        wp_register_script(
            'gaenity-support-hub',
            GAENITY_SUPPORT_HUB_URL . 'assets/js/support-hub.js',
            array( 'jquery' ),
            self::VERSION,
            true
        );
    }

    /**
     * Add helpful plugin action links.
     *
     * @param array $links Existing links.
     *
     * @return array
     */
    public function action_links( $links ) {
        $page_id = (int) get_option( self::PAGE_OPTION );

        if ( $page_id ) {
            $links[] = '<a href="' . esc_url( get_edit_post_link( $page_id ) ) . '">' . esc_html__( 'Edit Support Hub Page', 'gaenity-community' ) . '</a>';
        }

        $links[] = '<a href="https://gaenity.com" target="_blank" rel="noopener">' . esc_html__( 'Need help?', 'gaenity-community' ) . '</a>';

        return $links;
    }

    /**
     * Register Elementor widget wrapper if Elementor is active.
     */
    public function register_elementor_widget( $widgets_manager ) {
        if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }

        if ( ! class_exists( 'Gaenity_Support_Hub_Elementor_Widget', false ) ) {
            require_once GAENITY_SUPPORT_HUB_PATH . 'includes/class-gaenity-support-hub-elementor-widget.php';
        }

        $widgets_manager->register( new Gaenity_Support_Hub_Elementor_Widget() );
    }

    /**
     * Register plugin shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'gaenity_support_hub', array( $this, 'render_support_hub' ) );
        add_shortcode( 'gaenity_community', array( $this, 'render_community_block' ) );
        add_shortcode( 'gaenity_register', array( $this, 'render_register_block' ) );
        add_shortcode( 'gaenity_login', array( $this, 'render_login_block' ) );
        add_shortcode( 'gaenity_chat', array( $this, 'render_chat_block' ) );
    }

    /**
     * Ensure scripts and styles are loaded.
     */
    protected function enqueue_assets() {
        wp_enqueue_style( 'gaenity-support-hub' );
        wp_enqueue_script( 'gaenity-support-hub' );
    }

    /**
     * Render the one-page experience.
     *
     * @return string
     */
    public function render_support_hub() {
        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="gaenity-support-hub" data-gaenity-component="support-hub">
            <?php echo $this->render_hero(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_community_block( array( 'block' => 'community_home' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_resources_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_polls_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_experts_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_register_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_login_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_chat_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->render_contact_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render hero section.
     *
     * @return string
     */
    protected function render_hero() {
        $this->enqueue_assets();

        ob_start();
        ?>
        <section class="gaenity-support-hub__hero">
            <div class="gaenity-support-hub__hero-content">
                <p class="gaenity-support-hub__eyebrow"><?php esc_html_e( 'Community & Enablement', 'gaenity-community' ); ?></p>
                <h1 class="gaenity-support-hub__headline"><?php esc_html_e( 'Gaenity Support Hub', 'gaenity-community' ); ?></h1>
                <p class="gaenity-support-hub__intro"><?php esc_html_e( 'A polished, one-stop community portal for entrepreneurs, professionals, and experts to collaborate, learn, and grow.', 'gaenity-community' ); ?></p>
                <div class="gaenity-support-hub__hero-actions">
                    <a class="gaenity-support-hub__btn" href="#gaenity-register"><?php esc_html_e( 'Join the Community', 'gaenity-community' ); ?></a>
                    <a class="gaenity-support-hub__btn gaenity-support-hub__btn--ghost" href="#gaenity-resources"><?php esc_html_e( 'Browse Resources', 'gaenity-community' ); ?></a>
                </div>
            </div>
            <div class="gaenity-support-hub__hero-panel">
                <ul class="gaenity-support-hub__hero-highlights">
                    <li>
                        <span class="gaenity-support-hub__highlight-label"><?php esc_html_e( 'Members', 'gaenity-community' ); ?></span>
                        <span class="gaenity-support-hub__highlight-value">5,200+</span>
                    </li>
                    <li>
                        <span class="gaenity-support-hub__highlight-label"><?php esc_html_e( 'Expert Sessions Delivered', 'gaenity-community' ); ?></span>
                        <span class="gaenity-support-hub__highlight-value">940</span>
                    </li>
                    <li>
                        <span class="gaenity-support-hub__highlight-label"><?php esc_html_e( 'Resources Downloaded', 'gaenity-community' ); ?></span>
                        <span class="gaenity-support-hub__highlight-value">12k</span>
                    </li>
                </ul>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render community block based on attribute.
     *
     * @param array|string $atts Attributes.
     *
     * @return string
     */
    public function render_community_block( $atts = array() ) {
        $this->enqueue_assets();

        $atts = shortcode_atts(
            array(
                'block' => 'community_home',
            ),
            $atts,
            'gaenity_community'
        );

        if ( 'community_home' !== $atts['block'] ) {
            return '<div class="gaenity-support-hub__notice">' . esc_html__( 'This community block is not available yet. Displaying the community home instead.', 'gaenity-community' ) . '</div>' . $this->render_community_block( array( 'block' => 'community_home' ) );
        }

        $demo_discussions = $this->get_demo_discussions();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-community">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Community Home', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Connect with peers in your region or industry, share challenges, and access curated discussions.', 'gaenity-community' ); ?></p>
            </header>
            <div class="gaenity-support-hub__tabs" role="tablist">
                <?php
                $tabs = array(
                    'regions'     => __( 'Regions', 'gaenity-community' ),
                    'industries'  => __( 'Industries', 'gaenity-community' ),
                    'challenges'  => __( 'Common Challenges', 'gaenity-community' ),
                );
                foreach ( $tabs as $slug => $label ) :
                    ?>
                    <button class="gaenity-support-hub__tab" type="button" role="tab" data-gaenity-tab="<?php echo esc_attr( $slug ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </button>
                    <?php
                endforeach;
                ?>
            </div>
            <div class="gaenity-support-hub__tab-panels">
                <?php echo $this->render_tab_panel( 'regions', $this->get_demo_regions() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $this->render_tab_panel( 'industries', $this->get_demo_industries() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $this->render_tab_panel( 'challenges', $this->get_demo_challenges() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <div class="gaenity-support-hub__discussion-grid">
                <?php foreach ( $demo_discussions as $discussion ) : ?>
                    <article class="gaenity-support-hub__discussion-card">
                        <div class="gaenity-support-hub__discussion-meta">
                            <span class="gaenity-support-hub__badge"><?php echo esc_html( $discussion['topic'] ); ?></span>
                            <span class="gaenity-support-hub__badge gaenity-support-hub__badge--ghost"><?php echo esc_html( $discussion['region'] ); ?></span>
                        </div>
                        <h3><?php echo esc_html( $discussion['title'] ); ?></h3>
                        <p><?php echo esc_html( $discussion['excerpt'] ); ?></p>
                        <footer class="gaenity-support-hub__discussion-footer">
                            <span class="gaenity-support-hub__avatar" aria-hidden="true">👤</span>
                            <span><?php echo esc_html( $discussion['author'] ); ?></span>
                            <span class="gaenity-support-hub__meta-divider" aria-hidden="true">·</span>
                            <span><?php echo esc_html( $discussion['responses'] ); ?> <?php esc_html_e( 'responses', 'gaenity-community' ); ?></span>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render register block.
     *
     * @return string
     */
    public function render_register_block() {
        $this->enqueue_assets();
        $demo_roles = $this->get_demo_roles();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-register">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Create Your Account', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Tell us a little about yourself to personalise your community journey.', 'gaenity-community' ); ?></p>
            </header>
            <form class="gaenity-support-hub__form" method="post" data-success="<?php esc_attr_e( 'Registration submitted! Check your email to confirm your account.', 'gaenity-community' ); ?>">
                <div class="gaenity-support-hub__grid">
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Full Name', 'gaenity-community' ); ?></span>
                        <input type="text" name="full_name" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Display Name', 'gaenity-community' ); ?></span>
                        <input type="text" name="display_name" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Email', 'gaenity-community' ); ?></span>
                        <input type="email" name="email" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Role / Title', 'gaenity-community' ); ?></span>
                        <select name="role" required>
                            <?php foreach ( $demo_roles as $role ) : ?>
                                <option value="<?php echo esc_attr( $role ); ?>"><?php echo esc_html( $role ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Region', 'gaenity-community' ); ?></span>
                        <select name="region" required>
                            <?php foreach ( $this->get_demo_regions() as $region ) : ?>
                                <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Country', 'gaenity-community' ); ?></span>
                        <input type="text" name="country" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Industry', 'gaenity-community' ); ?></span>
                        <select name="industry" required>
                            <?php foreach ( $this->get_demo_industries() as $industry ) : ?>
                                <option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Primary Challenge', 'gaenity-community' ); ?></span>
                        <select name="challenge" required>
                            <?php foreach ( $this->get_demo_challenges() as $challenge ) : ?>
                                <option value="<?php echo esc_attr( $challenge ); ?>"><?php echo esc_html( $challenge ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="gaenity-support-hub__field gaenity-support-hub__field--full">
                        <span><?php esc_html_e( 'Goals for Joining', 'gaenity-community' ); ?></span>
                        <textarea name="goals" rows="3" required></textarea>
                    </label>
                    <label class="gaenity-support-hub__choice">
                        <input type="checkbox" name="guidelines" required />
                        <span><?php esc_html_e( 'I agree to the community guidelines', 'gaenity-community' ); ?></span>
                    </label>
                    <label class="gaenity-support-hub__choice">
                        <input type="checkbox" name="updates" />
                        <span><?php esc_html_e( 'I agree to receive updates from Gaenity', 'gaenity-community' ); ?></span>
                    </label>
                </div>
                <button class="gaenity-support-hub__btn" type="submit"><?php esc_html_e( 'Submit Registration', 'gaenity-community' ); ?></button>
                <p class="gaenity-support-hub__form-notice" aria-live="polite"></p>
            </form>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render login block.
     *
     * @return string
     */
    public function render_login_block() {
        $this->enqueue_assets();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-login">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Member Login', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Access exclusive discussions, polls, and expert insights tailored to your profile.', 'gaenity-community' ); ?></p>
            </header>
            <form class="gaenity-support-hub__form" method="post" data-success="<?php esc_attr_e( 'Welcome back! You are now signed in.', 'gaenity-community' ); ?>">
                <div class="gaenity-support-hub__grid">
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Email', 'gaenity-community' ); ?></span>
                        <input type="email" name="login_email" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Password', 'gaenity-community' ); ?></span>
                        <input type="password" name="login_password" required />
                    </label>
                </div>
                <div class="gaenity-support-hub__form-footer">
                    <label class="gaenity-support-hub__choice">
                        <input type="checkbox" name="remember" />
                        <span><?php esc_html_e( 'Keep me signed in', 'gaenity-community' ); ?></span>
                    </label>
                    <a class="gaenity-support-hub__link" href="#gaenity-register"><?php esc_html_e( 'Need an account?', 'gaenity-community' ); ?></a>
                </div>
                <button class="gaenity-support-hub__btn" type="submit"><?php esc_html_e( 'Sign In', 'gaenity-community' ); ?></button>
                <p class="gaenity-support-hub__form-notice" aria-live="polite"></p>
            </form>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render chat block.
     *
     * @return string
     */
    public function render_chat_block() {
        $this->enqueue_assets();

        $messages = $this->get_demo_chat_messages();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-chat">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Community Chat', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Drop a quick question or respond to peers in real time. Anonymous mode available for sensitive topics.', 'gaenity-community' ); ?></p>
            </header>
            <div class="gaenity-support-hub__chat">
                <div class="gaenity-support-hub__chat-feed" role="log" aria-live="polite">
                    <?php foreach ( $messages as $message ) : ?>
                        <div class="gaenity-support-hub__chat-message">
                            <div class="gaenity-support-hub__chat-meta">
                                <span class="gaenity-support-hub__badge"><?php echo esc_html( $message['author'] ); ?></span>
                                <span><?php echo esc_html( $message['timestamp'] ); ?></span>
                            </div>
                            <p><?php echo esc_html( $message['content'] ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form class="gaenity-support-hub__form gaenity-support-hub__chat-form" method="post" data-success="<?php esc_attr_e( 'Message posted!', 'gaenity-community' ); ?>">
                    <label class="gaenity-support-hub__choice">
                        <input type="checkbox" name="anonymous" />
                        <span><?php esc_html_e( 'Post anonymously', 'gaenity-community' ); ?></span>
                    </label>
                    <label class="gaenity-support-hub__field gaenity-support-hub__field--full">
                        <span class="screen-reader-text"><?php esc_html_e( 'Your message', 'gaenity-community' ); ?></span>
                        <textarea name="message" rows="2" placeholder="<?php esc_attr_e( 'Share your update, win, or question…', 'gaenity-community' ); ?>" required></textarea>
                    </label>
                    <button class="gaenity-support-hub__btn" type="submit"><?php esc_html_e( 'Send', 'gaenity-community' ); ?></button>
                    <p class="gaenity-support-hub__form-notice" aria-live="polite"></p>
                </form>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render resources section.
     *
     * @return string
     */
    protected function render_resources_section() {
        $resources = $this->get_demo_resources();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-resources">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Resources', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Guides, templates, and case studies to support smarter business decisions.', 'gaenity-community' ); ?></p>
            </header>
            <div class="gaenity-support-hub__resource-grid">
                <?php foreach ( $resources as $resource ) : ?>
                    <article class="gaenity-support-hub__resource-card">
                        <div class="gaenity-support-hub__resource-illustration" aria-hidden="true">
                            <span><?php echo esc_html( $resource['emoji'] ); ?></span>
                        </div>
                        <div class="gaenity-support-hub__resource-content">
                            <h3><?php echo esc_html( $resource['title'] ); ?></h3>
                            <p><?php echo esc_html( $resource['description'] ); ?></p>
                            <button class="gaenity-support-hub__btn gaenity-support-hub__btn--ghost" type="button">
                                <?php esc_html_e( 'Download sample', 'gaenity-community' ); ?>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render polls section.
     *
     * @return string
     */
    protected function render_polls_section() {
        $polls = $this->get_demo_polls();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-polls">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Pulse Polls', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Collect quick, statistically useful signals from members to understand needs across region and industry.', 'gaenity-community' ); ?></p>
            </header>
            <div class="gaenity-support-hub__poll-grid">
                <?php foreach ( $polls as $poll ) : ?>
                    <article class="gaenity-support-hub__poll-card">
                        <header>
                            <span class="gaenity-support-hub__badge gaenity-support-hub__badge--accent"><?php echo esc_html( $poll['category'] ); ?></span>
                            <h3><?php echo esc_html( $poll['question'] ); ?></h3>
                        </header>
                        <ul class="gaenity-support-hub__poll-options">
                            <?php foreach ( $poll['options'] as $option ) : ?>
                                <li>
                                    <span><?php echo esc_html( $option['label'] ); ?></span>
                                    <span><?php echo esc_html( $option['value'] ); ?>%</span>
                                    <span class="gaenity-support-hub__progress" style="--gaenity-progress: <?php echo esc_attr( $option['value'] ); ?>%;"></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <footer>
                            <button class="gaenity-support-hub__btn gaenity-support-hub__btn--subtle" type="button"><?php esc_html_e( 'Cast your vote', 'gaenity-community' ); ?></button>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render featured experts.
     *
     * @return string
     */
    protected function render_experts_section() {
        $experts = $this->get_demo_experts();

        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-experts">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Featured Experts', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'Connect with vetted advisors for tailored, paid support via email or virtual sessions.', 'gaenity-community' ); ?></p>
            </header>
            <div class="gaenity-support-hub__expert-grid">
                <?php foreach ( $experts as $expert ) : ?>
                    <article class="gaenity-support-hub__expert-card">
                        <div class="gaenity-support-hub__expert-avatar" aria-hidden="true">👩‍💼</div>
                        <h3><?php echo esc_html( $expert['name'] ); ?></h3>
                        <p class="gaenity-support-hub__expert-role"><?php echo esc_html( $expert['role'] ); ?></p>
                        <p><?php echo esc_html( $expert['bio'] ); ?></p>
                        <div class="gaenity-support-hub__expert-tags">
                            <?php foreach ( $expert['focus'] as $focus ) : ?>
                                <span class="gaenity-support-hub__badge gaenity-support-hub__badge--ghost"><?php echo esc_html( $focus ); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="gaenity-support-hub__expert-actions">
                            <button class="gaenity-support-hub__btn gaenity-support-hub__btn--ghost" type="button"><?php esc_html_e( 'Email consultation', 'gaenity-community' ); ?></button>
                            <button class="gaenity-support-hub__btn" type="button"><?php esc_html_e( '30 min virtual meeting', 'gaenity-community' ); ?></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render contact section.
     *
     * @return string
     */
    protected function render_contact_section() {
        ob_start();
        ?>
        <section class="gaenity-support-hub__section" id="gaenity-contact">
            <header class="gaenity-support-hub__section-header">
                <h2><?php esc_html_e( 'Need tailored support?', 'gaenity-community' ); ?></h2>
                <p><?php esc_html_e( 'We welcome questions, ideas, and collaboration. Send us a message and our team will respond shortly.', 'gaenity-community' ); ?></p>
            </header>
            <form class="gaenity-support-hub__form" method="post" data-success="<?php esc_attr_e( 'Thanks for reaching out. We will reply soon.', 'gaenity-community' ); ?>">
                <div class="gaenity-support-hub__grid">
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Name', 'gaenity-community' ); ?></span>
                        <input type="text" name="contact_name" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Email', 'gaenity-community' ); ?></span>
                        <input type="email" name="contact_email" required />
                    </label>
                    <label class="gaenity-support-hub__field">
                        <span><?php esc_html_e( 'Subject', 'gaenity-community' ); ?></span>
                        <input type="text" name="contact_subject" required />
                    </label>
                    <label class="gaenity-support-hub__field gaenity-support-hub__field--full">
                        <span><?php esc_html_e( 'Message', 'gaenity-community' ); ?></span>
                        <textarea name="contact_message" rows="4" required></textarea>
                    </label>
                    <label class="gaenity-support-hub__choice gaenity-support-hub__field--full">
                        <input type="checkbox" name="contact_updates" />
                        <span><?php esc_html_e( 'I agree to receive updates from Gaenity.', 'gaenity-community' ); ?></span>
                    </label>
                </div>
                <button class="gaenity-support-hub__btn" type="submit"><?php esc_html_e( 'Send message', 'gaenity-community' ); ?></button>
                <p class="gaenity-support-hub__form-notice" aria-live="polite"></p>
            </form>
            <div class="gaenity-support-hub__social">
                <span><?php esc_html_e( 'Follow us', 'gaenity-community' ); ?>:</span>
                <a href="https://instagram.com" target="_blank" rel="noopener">Instagram</a>
                <a href="https://facebook.com" target="_blank" rel="noopener">Facebook</a>
                <a href="https://linkedin.com" target="_blank" rel="noopener">LinkedIn</a>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    /**
     * Render tab panel markup.
     *
     * @param string $id    Tab ID.
     * @param array  $items Items to display.
     *
     * @return string
     */
    protected function render_tab_panel( $id, $items ) {
        ob_start();
        ?>
        <div class="gaenity-support-hub__tab-panel" id="gaenity-tab-<?php echo esc_attr( $id ); ?>" role="tabpanel">
            <ul class="gaenity-support-hub__tag-list">
                <?php foreach ( $items as $item ) : ?>
                    <li><span class="gaenity-support-hub__badge gaenity-support-hub__badge--ghost"><?php echo esc_html( $item ); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Demo regions.
     *
     * @return array
     */
    protected function get_demo_regions() {
        return array(
            __( 'Africa', 'gaenity-community' ),
            __( 'North America', 'gaenity-community' ),
            __( 'Europe', 'gaenity-community' ),
            __( 'Middle East', 'gaenity-community' ),
            __( 'Asia Pacific', 'gaenity-community' ),
            __( 'Latin America', 'gaenity-community' ),
        );
    }

    /**
     * Demo industries.
     *
     * @return array
     */
    protected function get_demo_industries() {
        return array(
            __( 'Retail & e-commerce', 'gaenity-community' ),
            __( 'Manufacturing', 'gaenity-community' ),
            __( 'Services', 'gaenity-community' ),
            __( 'Health & wellness', 'gaenity-community' ),
            __( 'Food & hospitality', 'gaenity-community' ),
            __( 'Technology & startups', 'gaenity-community' ),
            __( 'Agriculture', 'gaenity-community' ),
            __( 'Finance & financial services', 'gaenity-community' ),
        );
    }

    /**
     * Demo challenges.
     *
     * @return array
     */
    protected function get_demo_challenges() {
        return array(
            __( 'Cash flow', 'gaenity-community' ),
            __( 'Supplier / customer risk', 'gaenity-community' ),
            __( 'Compliance', 'gaenity-community' ),
            __( 'Operations', 'gaenity-community' ),
            __( 'People', 'gaenity-community' ),
            __( 'Sales & marketing', 'gaenity-community' ),
            __( 'Technology & data', 'gaenity-community' ),
            __( 'Financial controls', 'gaenity-community' ),
            __( 'Credit', 'gaenity-community' ),
            __( 'Fraud', 'gaenity-community' ),
        );
    }

    /**
     * Demo roles.
     *
     * @return array
     */
    protected function get_demo_roles() {
        return array(
            __( 'Business Owner', 'gaenity-community' ),
            __( 'Employed Professional', 'gaenity-community' ),
            __( 'Forum Expert', 'gaenity-community' ),
        );
    }

    /**
     * Demo discussions.
     *
     * @return array
     */
    protected function get_demo_discussions() {
        return array(
            array(
                'title'     => __( 'Preparing for seasonal cash flow swings', 'gaenity-community' ),
                'excerpt'   => __( 'Members share tactics for forecasting and balancing working capital ahead of peak months.', 'gaenity-community' ),
                'topic'     => __( 'Cash flow', 'gaenity-community' ),
                'region'    => __( 'North America', 'gaenity-community' ),
                'author'    => 'Lina A.',
                'responses' => 18,
            ),
            array(
                'title'     => __( 'Supplier diversification checklist', 'gaenity-community' ),
                'excerpt'   => __( 'How three founders reduced dependency on a single supplier within six weeks.', 'gaenity-community' ),
                'topic'     => __( 'Supplier risk', 'gaenity-community' ),
                'region'    => __( 'Europe', 'gaenity-community' ),
                'author'    => 'Marcus K.',
                'responses' => 12,
            ),
            array(
                'title'     => __( 'Hiring fractional CFO support', 'gaenity-community' ),
                'excerpt'   => __( 'Discussing when to bring in part-time finance experts and what to expect in costs.', 'gaenity-community' ),
                'topic'     => __( 'Finance enablement', 'gaenity-community' ),
                'region'    => __( 'Africa', 'gaenity-community' ),
                'author'    => 'Ayodele S.',
                'responses' => 21,
            ),
            array(
                'title'     => __( 'Customer onboarding automation stack', 'gaenity-community' ),
                'excerpt'   => __( 'A breakdown of low-code tools to speed up onboarding for service businesses.', 'gaenity-community' ),
                'topic'     => __( 'Operations', 'gaenity-community' ),
                'region'    => __( 'Asia Pacific', 'gaenity-community' ),
                'author'    => 'Jia L.',
                'responses' => 9,
            ),
            array(
                'title'     => __( 'Keeping remote teams engaged', 'gaenity-community' ),
                'excerpt'   => __( 'Weekly rituals that boost trust and collaboration across time zones.', 'gaenity-community' ),
                'topic'     => __( 'People', 'gaenity-community' ),
                'region'    => __( 'Latin America', 'gaenity-community' ),
                'author'    => 'Gabriela P.',
                'responses' => 27,
            ),
        );
    }

    /**
     * Demo resources.
     *
     * @return array
     */
    protected function get_demo_resources() {
        return array(
            array(
                'emoji'       => '📊',
                'title'       => __( 'Cash flow tracker template', 'gaenity-community' ),
                'description' => __( 'Plan inflows, outflows, and runway with a 12-month rolling forecast spreadsheet.', 'gaenity-community' ),
            ),
            array(
                'emoji'       => '✅',
                'title'       => __( 'Supplier onboarding checklist', 'gaenity-community' ),
                'description' => __( 'Standardise due diligence, contract essentials, and contingency plans.', 'gaenity-community' ),
            ),
            array(
                'emoji'       => '🛡️',
                'title'       => __( 'Risk register template', 'gaenity-community' ),
                'description' => __( 'Surface emerging threats across finance, operations, and compliance with ease.', 'gaenity-community' ),
            ),
            array(
                'emoji'       => '📈',
                'title'       => __( 'Growth metrics dashboard', 'gaenity-community' ),
                'description' => __( 'Monitor KPIs across marketing, retention, and cash conversion in one view.', 'gaenity-community' ),
            ),
            array(
                'emoji'       => '🤝',
                'title'       => __( 'Partnership proposal kit', 'gaenity-community' ),
                'description' => __( 'Structure outreach, value articulation, and success metrics for partnerships.', 'gaenity-community' ),
            ),
        );
    }

    /**
     * Demo polls.
     *
     * @return array
     */
    protected function get_demo_polls() {
        return array(
            array(
                'category' => __( 'Risk Management', 'gaenity-community' ),
                'question' => __( 'Which risk area needs the most support this quarter?', 'gaenity-community' ),
                'options'  => array(
                    array(
                        'label' => __( 'Cash flow resilience', 'gaenity-community' ),
                        'value' => 42,
                    ),
                    array(
                        'label' => __( 'Supplier reliability', 'gaenity-community' ),
                        'value' => 28,
                    ),
                    array(
                        'label' => __( 'Compliance readiness', 'gaenity-community' ),
                        'value' => 30,
                    ),
                ),
            ),
            array(
                'category' => __( 'Finance Enablement', 'gaenity-community' ),
                'question' => __( 'How confident are you in your monthly financial reporting?', 'gaenity-community' ),
                'options'  => array(
                    array(
                        'label' => __( 'Very confident', 'gaenity-community' ),
                        'value' => 35,
                    ),
                    array(
                        'label' => __( 'Somewhat confident', 'gaenity-community' ),
                        'value' => 44,
                    ),
                    array(
                        'label' => __( 'Need major improvements', 'gaenity-community' ),
                        'value' => 21,
                    ),
                ),
            ),
            array(
                'category' => __( 'Operations', 'gaenity-community' ),
                'question' => __( 'Where is automation bringing the most value today?', 'gaenity-community' ),
                'options'  => array(
                    array(
                        'label' => __( 'Customer onboarding', 'gaenity-community' ),
                        'value' => 39,
                    ),
                    array(
                        'label' => __( 'Inventory & fulfilment', 'gaenity-community' ),
                        'value' => 33,
                    ),
                    array(
                        'label' => __( 'Reporting & analytics', 'gaenity-community' ),
                        'value' => 28,
                    ),
                ),
            ),
        );
    }

    /**
     * Demo experts.
     *
     * @return array
     */
    protected function get_demo_experts() {
        return array(
            array(
                'name'  => 'Marissa Okon',
                'role'  => __( 'Risk Strategist · Lagos, NG', 'gaenity-community' ),
                'bio'   => __( 'Helps retail and manufacturing founders build robust contingency plans and audit frameworks.', 'gaenity-community' ),
                'focus' => array( __( 'Risk Management', 'gaenity-community' ), __( 'Compliance', 'gaenity-community' ) ),
            ),
            array(
                'name'  => 'Caleb Montgomery',
                'role'  => __( 'Fractional CFO · Austin, US', 'gaenity-community' ),
                'bio'   => __( 'Works with growing startups to design forecasting cadences and investor-ready dashboards.', 'gaenity-community' ),
                'focus' => array( __( 'Finance Enablement', 'gaenity-community' ), __( 'Investment Readiness', 'gaenity-community' ) ),
            ),
            array(
                'name'  => 'Samira El-Hassan',
                'role'  => __( 'Operations Architect · Dubai, UAE', 'gaenity-community' ),
                'bio'   => __( 'Supports service teams with workflow automation and process documentation.', 'gaenity-community' ),
                'focus' => array( __( 'Operations', 'gaenity-community' ), __( 'Automation', 'gaenity-community' ) ),
            ),
            array(
                'name'  => 'Diego Fernández',
                'role'  => __( 'Customer Success Lead · Bogotá, CO', 'gaenity-community' ),
                'bio'   => __( 'Specialises in customer retention, lifecycle messaging, and feedback programmes.', 'gaenity-community' ),
                'focus' => array( __( 'Customer Success', 'gaenity-community' ), __( 'Service Design', 'gaenity-community' ) ),
            ),
            array(
                'name'  => 'Priya Mehta',
                'role'  => __( 'Data & Insights Partner · Mumbai, IN', 'gaenity-community' ),
                'bio'   => __( 'Transforms messy data into actionable dashboards and predictive insights.', 'gaenity-community' ),
                'focus' => array( __( 'Analytics', 'gaenity-community' ), __( 'Technology & Data', 'gaenity-community' ) ),
            ),
        );
    }

    /**
     * Demo chat messages.
     *
     * @return array
     */
    protected function get_demo_chat_messages() {
        return array(
            array(
                'author'    => __( 'Anonymous founder', 'gaenity-community' ),
                'timestamp' => __( '2 minutes ago', 'gaenity-community' ),
                'content'   => __( 'Anyone implemented supplier scorecards for local producers? Looking for best practices.', 'gaenity-community' ),
            ),
            array(
                'author'    => 'Lina A.',
                'timestamp' => __( '10 minutes ago', 'gaenity-community' ),
                'content'   => __( 'We just published a cash flow automation guide—link in resources!', 'gaenity-community' ),
            ),
            array(
                'author'    => 'Marissa Okon',
                'timestamp' => __( '25 minutes ago', 'gaenity-community' ),
                'content'   => __( 'Happy to review risk registers during office hours tomorrow. Drop your doc in the chat.', 'gaenity-community' ),
            ),
            array(
                'author'    => 'Diego Fernández',
                'timestamp' => __( '1 hour ago', 'gaenity-community' ),
                'content'   => __( 'Shared our onboarding automation SOP under operations discussions.', 'gaenity-community' ),
            ),
            array(
                'author'    => 'Community Bot',
                'timestamp' => __( '3 hours ago', 'gaenity-community' ),
                'content'   => __( 'Benchmark poll: What’s your top priority this month? Vote now in the polls section.', 'gaenity-community' ),
            ),
        );
    }
}
