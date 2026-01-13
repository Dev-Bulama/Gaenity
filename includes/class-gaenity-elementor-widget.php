<?php
/**
 * Elementor widget for Gaeinity Community Suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! class_exists( 'Gaeinity_Community_Elementor_Widget' ) ) :

class Gaeinity_Community_Elementor_Widget extends Widget_Base {

    /**
     * Widget slug.
     */
    public function get_name() {
        return 'gaenity_community_widget';
    }

    /**
     * Widget title.
     */
    public function get_title() {
        return __( 'Gaeinity Community Block', 'gaenity-community' );
    }

    /**
     * Widget icon.
     */
    public function get_icon() {
        return 'eicon-users';
    }

    /**
     * Widget categories.
     */
    public function get_categories() {
        return array( 'gaenity-community' );
    }

    /**
     * Register controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'gaenity-community' ),
            )
        );

        $this->add_control(
            'block_type',
            array(
                'label'   => __( 'Community block', 'gaenity-community' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'community_home',
                'options' => array(
                    'community_home' => __( 'Community Home', 'gaenity-community' ),
                    'resources'      => __( 'Resources', 'gaenity-community' ),
                    'register'       => __( 'Registration form', 'gaenity-community' ),
                    'login'          => __( 'Login form', 'gaenity-community' ),
                    'discussion_form'=> __( 'Discussion submission form', 'gaenity-community' ),
                    'discussion_board'=> __( 'Discussion board', 'gaenity-community' ),
                    'polls'          => __( 'Polls', 'gaenity-community' ),
                    'expert_request' => __( 'Ask an Expert form', 'gaenity-community' ),
                    'expert_register'=> __( 'Register as an Expert form', 'gaenity-community' ),
                    'contact'        => __( 'Contact form', 'gaenity-community' ),
                    'chat'           => __( 'Community chat', 'gaenity-community' ),
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $block    = isset( $settings['block_type'] ) ? $settings['block_type'] : 'community_home';

        switch ( $block ) {
            case 'resources':
                echo do_shortcode( '[gaenity_resources]' );
                break;
            case 'register':
                echo do_shortcode( '[gaenity_community_register]' );
                break;
            case 'login':
                echo do_shortcode( '[gaenity_community_login]' );
                break;
            case 'discussion_form':
                echo do_shortcode( '[gaenity_discussion_form]' );
                break;
            case 'discussion_board':
                echo do_shortcode( '[gaenity_discussion_board]' );
                break;
            case 'polls':
                echo do_shortcode( '[gaenity_polls]' );
                break;
            case 'expert_request':
                echo do_shortcode( '[gaenity_expert_request]' );
                break;
            case 'expert_register':
                echo do_shortcode( '[gaenity_expert_register]' );
                break;
            case 'contact':
                echo do_shortcode( '[gaenity_contact]' );
                break;
            case 'chat':
                echo do_shortcode( '[gaenity_community_chat]' );
                break;
            case 'community_home':
            default:
                echo do_shortcode( '[gaenity_community_home]' );
                break;
        }
    }
}

endif;