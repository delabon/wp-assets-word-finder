<?php
/**
 * Plugin Name: .Debug - Assets List
 * Plugin URI: https://delabon.com/store
 * Description: 
 * Author: Sabri Taieb
 * Author URI: https://delabon.com/
 * Version: 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! function_exists('dal_dumper') ){
    function dal_dumper( $data, $die = true ){

        echo '<pre>';
        var_dump( $data );
        echo '</pre>';

        if( $die ) die;
    }
}

class DBG_Assets_List{

    private $url_no_protocol;
    private $url;
    private $keyword;
    private $pattern;
    
    /**
     * Constructor
     */
    function __construct(){

        $this->url = get_site_url();
        $this->url_no_protocol = preg_replace('/https?:\/\//', '', $this->url);

        // list all assets
        if( isset( $_GET['debug-assets-list'] ) ){
            add_action( 'wp_print_scripts', array( $this, 'list_scripts' ), PHP_INT_MAX );
            add_action( 'wp_print_footer_scripts', array( $this, 'list_scripts' ), 9 );
            add_action( 'wp_print_styles', array( $this, 'list_styles' ), PHP_INT_MAX );
            add_action( 'wp_print_footer_scripts', array( $this, 'list_styles' ), 9 );
        }

        // search inside all assets
        if( isset( $_GET['debug-assets-search'] ) ){

            $this->keyword = urldecode($_GET['debug-assets-search']);

            if( $this->keyword === '' ){
                die('Keyword is empty, ex: mysite.com?debug-assets-search=Hello, World');
            }

            echo '<strong>[Debug - Assets List] Searching for: ' . $this->keyword . '</strong><br><br>';

            $this->pattern = '/' .$this->keyword. '/';
            
            if( isset( $_GET['only_styles'] ) ){
                add_action( 'wp_print_styles', array( $this, 'search_styles' ), PHP_INT_MAX );
                add_action( 'wp_print_footer_scripts', array( $this, 'search_styles' ), 9 );
            }
            elseif( isset( $_GET['only_scripts'] ) ){
                add_action( 'wp_print_scripts', array( $this, 'search_scripts' ), PHP_INT_MAX );
                add_action( 'wp_print_footer_scripts', array( $this, 'search_scripts' ), 9 );
            }
            else{
                add_action( 'wp_print_styles', array( $this, 'search_styles' ), PHP_INT_MAX );
                add_action( 'wp_print_footer_scripts', array( $this, 'search_styles' ), 9 );
                add_action( 'wp_print_scripts', array( $this, 'search_scripts' ), PHP_INT_MAX );
                add_action( 'wp_print_footer_scripts', array( $this, 'search_scripts' ), 9 );
            }

        }

    }

    /**
     * It list all script links
     */
    function list_scripts(){

        $wp_scripts = wp_scripts();
        $scripts = clone($wp_scripts);
        $scripts->all_deps($scripts->queue);

        foreach( $scripts->to_do as $handle ){
            $dependency = $wp_scripts->registered[ $handle ];

            if( empty( $dependency->src ) ) continue;

            echo $dependency->src . '<br>';
        }
    }

    /**
     * It list all style links
     */
    function list_styles(){
	    $wp_styles = wp_styles();
		$styles = clone($wp_styles);
		$styles->all_deps($styles->queue);

        foreach( $styles->to_do as $handle ){
	        $dependency = $wp_styles->registered[ $handle ];

            if( empty( $dependency->src ) ) continue;
            echo $dependency->src . '<br>';
        }
    }

    /**
     * Search inside all scripts for a keyword
     */
    function search_scripts(){

        $wp_scripts = wp_scripts();
        $scripts = clone($wp_scripts);
        $scripts->all_deps($scripts->queue);

        foreach( $scripts->to_do as $handle ){
            $dependency = $wp_scripts->registered[ $handle ];

            if( empty( $dependency->src ) ) continue;

            $this->search_for_keyword( $dependency );
        }
    }

    /**
     * Search inside all styles for a keyword
     */
    function search_styles(){
	    $wp_styles = wp_styles();
		$styles = clone($wp_styles);
		$styles->all_deps($styles->queue);

        foreach( $styles->to_do as $handle ){
	        $dependency = $wp_styles->registered[ $handle ];

            if( empty( $dependency->src ) ) continue;

            $this->search_for_keyword( $dependency );
        }
    }

    private function search_for_keyword( $dependency ){

        $src = $dependency->src;

        if( strpos( $src, '/' ) === 0 ) {
            $src = $this->url . $src;
        }
        
        $content = @file_get_contents( $src );
        
        if( ! $content ) {
            echo '[Access error] -> ' . $src . '<br>';
        }
        else{
            
            $pos = strpos( $content, $this->keyword );
            $first_line = strstr( $content, $this->keyword, true);

            if( false !== $first_line ) {
                $line = count(explode( PHP_EOL, $first_line ));

                echo '[Found][Line:'.$line.'][Pos:'.$pos.'] -> ' . $src . '<br>';
            }
        }
    }
}

new DBG_Assets_List();
