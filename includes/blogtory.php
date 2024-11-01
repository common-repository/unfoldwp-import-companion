<?php
/**
 * OCDI Setup Changes
 *
 * @since 1.0.7
 */
function unfold_import_ocdi_setup( $default_settings ) {
    $default_settings['parent_slug'] = 'themes.php';
    $default_settings['page_title']  = esc_html__( 'Blogtory Demo Import' , 'unfoldwp-import-companion' );
    $default_settings['menu_title']  = esc_html__( 'Import Demo Data' , 'unfoldwp-import-companion' );
    $default_settings['capability']  = 'import';
    $default_settings['menu_slug']   = 'blogtory-demo-import';
 
    return $default_settings;
}
add_filter( 'ocdi/plugin_page_setup', 'unfold_import_ocdi_setup' );

/**
 * OCDI files.
 *
 * @since 1.0.0
 *
 * @return array Files.
 */
function unfold_import_blogtory() {

    $demo_base_url = 'https://unfoldwp.com/demo-content/blogtory';
    $demo_preview_url = 'https://preview.unfoldwp.com/blogtory/';

    return apply_filters( 'blogtory_demo_files', array(
        array(
            'import_file_name'             => esc_html__( 'V1 - Default', 'unfoldwp-import-companion' ),
            'import_file_url'              => $demo_base_url.'/v1/blogtory.xml',
            'import_widget_file_url'       => $demo_base_url.'/v1/blogtory.wie',
            'import_customizer_file_url'   => $demo_base_url.'/v1/blogtory.dat',
            'import_preview_image_url'     => $demo_base_url.'/images/v1.png',
            'preview_url'                  => $demo_preview_url,
        ),
    ));
}
add_filter( 'pt-ocdi/import_files', 'unfold_import_blogtory' );

/**
 * OCDI after import.
 *
 * @since 1.0.0
 */

function unfold_after_import_setup() {
    // Assign front page and posts page (blog page).
    $front_page_id = null;
    $blog_page_id  = null;

    $front_page = get_page_by_title( 'Homepage' );

    if ( $front_page ) {
        if ( is_array( $front_page ) ) {
            $first_page = array_shift( $front_page );
            $front_page_id = $first_page->ID;
        } else {
            $front_page_id = $front_page->ID;
        }
    }

    $blog_page = get_page_by_title( 'Blog' );

    if ( $blog_page ) {
        if ( is_array( $blog_page ) ) {
            $first_page = array_shift( $blog_page );
            $blog_page_id = $first_page->ID;
        } else {
            $blog_page_id = $blog_page->ID;
        }
    }

    if ( $front_page_id && $blog_page_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_page_id );
        update_option( 'page_for_posts', $blog_page_id );
    }

    // Assign navigation menu locations.
    $menu_location_details = array(
        'primary-menu' => 'primary-menu',
        'footer-menu' => 'footer-menu',
        'top-menu' => 'top-menu',
        'social-menu' => 'social-menu',
    );

    if ( ! empty( $menu_location_details ) ) {
        $navigation_settings = array();
        $current_navigation_menus = wp_get_nav_menus();
        if ( ! empty( $current_navigation_menus ) && ! is_wp_error( $current_navigation_menus ) ) {
            foreach ( $current_navigation_menus as $menu ) {
                foreach ( $menu_location_details as $location => $menu_slug ) {
                    if ( $menu->slug === $menu_slug ) {
                        $navigation_settings[ $location ] = $menu->term_id;
                    }
                }
            }
        }
        set_theme_mod( 'nav_menu_locations', $navigation_settings );
    }
}
add_action( 'pt-ocdi/after_import', 'unfold_after_import_setup' );