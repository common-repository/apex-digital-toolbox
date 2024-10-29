<?php

if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}

// Ensure there are no conflicts
if ( ! class_exists( 'toolboxYoast' ) ) {
    class toolboxHookYoast extends toolboxHookController {
        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'Yoast' );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'wp_loaded', 'shortFields', array(
                'label'       => 'Add Yoast Variables for Short Title & Excerpt',
                'description' => 'Add Yoast variables for truncated title & excerpt. These fields return the start of their non-shortened counterparts up to a configurable length<br>The available fields are %%short_title%% and %%short_excerpt%%'
            ) );
        }

        public function shortFields() {
            YoastShortFields::init( $this->Toolbox );
        }
    }

    class YoastShortFields {
        private const TITLE_LENGTH_OPTION_KEY = 'yoast_short_fields_title_length';
        private const TITLE_LENGTH_DEFAULT = 49;
        private const TITLE_YOAST_VARIABLE_NAME = 'short_title';

        private const EXCERPT_LENGTH_OPTION_KEY = 'yoast_short_fields_excerpt_length';
        private const EXCERPT_LENGTH_DEFAULT = 155;
        private const EXCERPT_YOAST_VARIABLE_NAME = 'short_excerpt';

        private const VARIABLE_SECTION_NAME = 'Yoast Short Variables';

        private static $toolbox = null;

        /**
         * Initialize the Yoast Short Fields feature
         */
        public static function init( $toolbox ) {
            self::$toolbox = $toolbox;

            self::$toolbox->addSetting([
                'name' => self::TITLE_LENGTH_OPTION_KEY,
                'label' => "Short Title Length",
                'type' => "number",
                'value' => self::getShortTitleLength(),
                'description' => "How many characters should the short title be"
            ], self::VARIABLE_SECTION_NAME);

            self::$toolbox->addSetting([
                'name' => self::EXCERPT_LENGTH_OPTION_KEY,
                'label' => "Short Excerpt Length",
                'type' => "number",
                'value' => self::getShortExcerptLength(),
                'description' => "How many characters should the short excerpt be"
            ], self::VARIABLE_SECTION_NAME);

            if ( class_exists( 'WPSEO_Replace_Vars' ) ) {
                WPSEO_Replace_Vars::register_replacement( self::TITLE_YOAST_VARIABLE_NAME, array( 'YoastShortFields', 'replaceShortTitle') );
                WPSEO_Replace_Vars::register_replacement( self::EXCERPT_YOAST_VARIABLE_NAME, array( 'YoastShortFields', 'replaceShortExcerpt') );
            }
        }

        public static function replaceShortTitle( $var, $post ) {
            return self::truncateTextAtWhitespace(
                get_the_title( $post->ID ),
                self::getShortTitleLength()
            );
        }

        public static function replaceShortExcerpt( $var, $post ) {
            return self::truncateTextAtWhitespace(
                get_the_excerpt( $post->ID ),
                self::getShortExcerptLength()
            );
        }

        private static function getShortTitleLength() {
            $value = self::$toolbox->getOption( self::TITLE_LENGTH_OPTION_KEY );

            if ( $value === false )  {
                $value = self::TITLE_LENGTH_DEFAULT;
            }

            return $value;
        }

        private static function getShortExcerptLength() {
            $value = self::$toolbox->getOption( self::EXCERPT_LENGTH_OPTION_KEY );

            if ( $value === false )  {
                $value = self::EXCERPT_LENGTH_DEFAULT;
            }

            return $value;
        }

        /**
         * Truncate the `$content` to at most `$truncationLength` ending on a space character if possible.
         *
         * @return string
         */
        private static function truncateTextAtWhitespace( $content, $truncationLength ) {
            if ( empty( $content ) ) {
                return "";
            }

            if ( strlen( $content ) <= $truncationLength ) {
                return $content;
            }

            $reverseSearchIndex = -( strlen( $content ) - $truncationLength );

            $actualTruncationLength = strrpos( $content, ' ', $reverseSearchIndex );

            // If there are no spaces within the first $truncationLength characters just truncate
            // without regard for space characters
            if ( $actualTruncationLength === false ) {
                $actualTruncationLength = $truncationLength;
            }

            return substr( $content, 0, $actualTruncationLength );
        }
    }
}