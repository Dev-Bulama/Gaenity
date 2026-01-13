<?php
/**
 * Elementor widget wrapper for the Gaenity Support Hub shortcode.
 *
 * @package GaenitySupportHub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( '\\Elementor\\Widget_Base' ) ) {
    /**
     * Simple Elementor widget to output the hub shortcode.
     */
    class Gaenity_Support_Hub_Elementor_Widget extends \Elementor\Widget_Base {

        /**
         * Widget name.
         */
        public function get_name() {
            return 'gaenity-support-hub';
        }

        /**
         * Widget title.
         */
        public function get_title() {
            return __( 'Gaenity Support Hub', 'gaenity-community' );
        }

        /**
         * Widget icon.
         */
        public function get_icon() {
            return 'eicon-site-title';
        }

        /**
         * Widget categories.
         */
        public function get_categories() {
            return array( 'general' );
        }

        /**
         * Render widget output.
         */
        protected function render() {
            echo do_shortcode( '[gaenity_support_hub]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
