<?php
/**
 * Plugin Name:       UnfoldWP Import Companion
 * Plugin URI:        https://wordpress.org/plugins/unfoldwp-import-companion/
 * Description:       Awesome starter templates for themes made by UnfoldWP
 * Version:           1.2.7
 * Author:            UnfoldWP
 * Author URI:        https://unfoldwp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       unfoldwp-import-companion
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP: 5.2
 * Tested up to: 6.5
 *
 * @package Unfoldwp_Import_Companion
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );  // prevent direct access.

// Define some Constants.
define( 'UF_IC_VERSION', '1.2.7' );
define( 'UF_IC_URL', plugin_dir_url( __FILE__ ) );
define( 'UF_IC_DIR', plugin_dir_path( __FILE__ ) );

if ( ! class_exists( 'Unfoldwp_Import_Companion' ) ) :
	/**
	 * Main class.
	 *
	 * @since 1.2.0
	 */
	class Unfoldwp_Import_Companion {

		/**
		 * Instance
		 *
		 * @access private
		 * @var null $instance
		 * @since 1.2.0
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.2.0
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Hold the config settings
		 *
		 * @access private
		 * @var array $config_data
		 * @since 1.2.0
		 */
		private $config_data;

		/**
		 * Current theme
		 *
		 * @access private
		 * @var string $current_theme
		 * @since 1.2.0
		 */
		private $current_theme;

		/**
		 * Current template
		 *
		 * @access private
		 * @var string $current_template
		 * @since 1.2.0
		 */
		private $current_template;

		/**
		 * Theme url
		 *
		 * @access private
		 * @var string $theme_url
		 * @since 1.2.0
		 */
		private $theme_url;

		/**
		 * Github base url
		 *
		 * @access private
		 * @var string $base_url
		 * @since 1.2.0
		 */
		private $base_url;

		/**
		 * Constructor.
		 *
		 * @since 1.2.0
		 */
		public function __construct() {

			add_action( 'plugins_loaded', array( $this, 'import_init' ) );
			add_action( 'unfold_starter_templates', array( $this, 'display_templates' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
			add_filter(
				'admin_body_class',
				function( $classes ) {
					$classes .= ' uf-companion ';
					return $classes;
				}
			);
		}

		/**
		 * Check for OCDI plugin and start setup.
		 *
		 * @since 1.2.0
		 */
		public function import_init() {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			load_plugin_textdomain( 'unfoldwp-import-companion', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			if ( ! class_exists( 'OCDI_Plugin' ) ) {
				if ( is_multisite() ) {
					add_action( 'network_admin_notices', array( $this, 'ocdi_notice' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'ocdi_notice' ) );
				}
			} else {
				$this->setup_init();
			}

		}

		/**
		 * Show notice if OCDI is not installed.
		 *
		 * @since 1.2.0
		 */
		public function ocdi_notice() {
			?>
			<div class="error">
				<p><?php esc_html_e( 'UnfoldWP Import Companion is not effective. Please install & activate One click Demo Import plugin first.', 'unfoldwp-import-companion' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Setup OCDI Filters
		 *
		 * @since 1.2.0
		 */
		public function setup_init() {

			// Only execute on the admin side.
			if ( is_admin() ) {

				// Get Current Theme.
				$current_theme = wp_get_theme();
				if ( $current_theme->exists() && $current_theme->parent() ) {
					$parent_theme = $current_theme->parent();
					if ( $parent_theme->exists() ) {
						$this->current_theme = $parent_theme->get_stylesheet();
					}
					// Set current theme template for child theme.
					$this->current_template = $current_theme->get_stylesheet();
				} elseif ( $current_theme->exists() ) {
					$this->current_theme = $current_theme->get_stylesheet();
					// Set current theme template.
					$this->current_template = $this->current_theme;
				}

				$this->theme_url = 'https://unfoldwp.com/products/' . $this->current_theme;

				// Base url of the repository.
				$this->base_url = 'https://raw.githubusercontent.com/unfoldwp/free-themes-templates/master/';

				// Get the json file to populate proper demo setup settings.
				$config_file = $this->base_url . $this->current_template . '/init.json';

				$data = wp_remote_get( $config_file );

				// Only execute if our config is loaded properly.
				if ( is_array( $data ) && ! is_wp_error( $data ) ) {

					$data              = wp_remote_retrieve_body( $data );
					$this->config_data = json_decode( $data, true );

					add_filter( 'ocdi/plugin_page_title', array( $this, 'disable_ocdi_title' ) );
					add_filter( 'ocdi/plugin_intro_text', array( $this, 'disable_ocdi_intro' ) );

					add_filter( 'ocdi/register_plugins', array( $this, 'ocdi_register_plugins' ) );
					add_filter( 'ocdi/plugin_page_setup', array( $this, 'ocdi_setup' ) );
					add_filter( 'ocdi/import_files', array( $this, 'manage_import' ) );
					add_action( 'ocdi/before_content_import', array( $this, 'before_content_import' ) );
					add_action( 'ocdi/before_widgets_import', array( $this, 'before_widgets_import' ) );
					add_action( 'ocdi/after_import', array( $this, 'after_import' ) );
				}
			}

		}

		/**
		 * Disable OCDI title
		 *
		 * @param string $plugin_title OCDI Title.
		 * @since 1.2.0
		 */
		public function disable_ocdi_title( $plugin_title ) {
			$plugin_title = '';
			return $plugin_title;
		}

		/**
		 * Disable OCDI Intro Text.
		 *
		 * @param string $plugin_intro_text OCDI Intro Text.
		 * @since 1.2.0
		 */
		public function disable_ocdi_intro( $plugin_intro_text ) {
			$plugin_intro_text = '';
			return $plugin_intro_text;
		}

		/**
		 * Register recommended plugins for OCDI.
		 *
		 * @param array $plugins Array of plugins.
		 * @since 1.2.0
		 */
		public function ocdi_register_plugins( $plugins ) {

			if ( isset( $this->config_data['plugins'] ) ) {

				$recommended_plugins = array();

				foreach ( $this->config_data['plugins'] as $plugin_data ) {
					$recommended_plugins[] = array(
						'name'        => $plugin_data['name'],
						'slug'        => $plugin_data['slug'],
						'required'    => $plugin_data['required'],
						'preselected' => $plugin_data['preselected'],
					);
				}

				return array_merge( $plugins, $recommended_plugins );

			} else {
				return $plugins;
			}
		}

		/**
		 * Change OCDI default texts
		 *
		 * @param array $default_settings Default text array.
		 * @since 1.2.0
		 */
		public function ocdi_setup( $default_settings ) {

			$default_settings['page_title'] = esc_html__( 'Demo Import', 'unfoldwp-import-companion' );
			$default_settings['menu_slug']  = $this->current_theme . '-demo-import';

			return $default_settings;
		}

		/**
		 * Init array for the OCDI demos
		 *
		 * @since 1.2.0
		 */
		public function manage_import() {

			$output = array();

			if ( $this->config_data && isset( $this->config_data['import_files'] ) ) {
				$free_demos = $this->config_data['import_files']['free'];
				foreach ( $free_demos as $demo_data ) {
					$file_url = $this->base_url . $this->current_template . '/' . $demo_data['import_path'] . '/';
					$output[] = array(
						'import_file_name'           => $demo_data['import_name'],
						'import_file_url'            => $file_url . 'content.xml',
						'import_widget_file_url'     => $file_url . 'widgets.wie',
						'import_customizer_file_url' => $file_url . 'customizer.dat',
						'import_preview_image_url'   => $file_url . 'screenshot.webp',
						'preview_url'                => $demo_data['preview_url'],
						'import_notice'              => esc_html( 'Make sure to leave the preselected plugins as it is to make the starter sites working as in our preview sites. Other plugins are optional and you can install them only if you need them.', 'unfoldwp-import-companion' ),
					);
				}
			}

			return $output;
		}

		/**
		 * Before Content import.
		 *
		 * @since 1.2.6
		 */
		function before_content_import() {
			// Trash default "hello word" post.
			$post = get_post( 1 );
			$slug = isset( $post->post_name ) ? $post->post_name : '';
			if ( 'hello-world' == $slug ) {
				wp_trash_post( 1 );
			}
		}

		/**
		 * Before widgets import.
		 *
		 * @since 1.2.6
		 */
		function before_widgets_import() {
			// Empty default sidebar widgetarea.
			$registered_sidebars = get_option( 'sidebars_widgets' );
			if ( isset( $registered_sidebars['sidebar-1'] ) && ! empty( $registered_sidebars['sidebar-1'] ) ) {
				update_option( 'sidebars_widgets', array( 'sidebar-1' => array() ) );
			}
		}

		/**
		 * Setup after finishing demo import
		 *
		 * @since 1.2.0
		 */
		public function after_import() {

			// Assign front page and posts page (blog page) if any.
			$front_page_id = null;
			$blog_page_id  = null;

			$front_page = new WP_Query(
				array(
					'post_type'              => 'page',
					'title'                  => 'Homepage',
					'post_status'            => 'all',
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'orderby'                => 'post_date ID',
					'order'                  => 'ASC',
				)
			);

			if ( ! empty( $front_page->post ) ) {
				$front_page_id = $front_page->post->ID;
			}

			$blog_page = new WP_Query(
				array(
					'post_type'              => 'page',
					'title'                  => 'Blog',
					'post_status'            => 'all',
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'orderby'                => 'post_date ID',
					'order'                  => 'ASC',
				)
			);

			if ( ! empty( $blog_page->post ) ) {
				$blog_page_id = $blog_page->post->ID;
			}

			if ( $front_page_id && $blog_page_id ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $front_page_id );
				update_option( 'page_for_posts', $blog_page_id );
			}

			// Assign navigation menu locations.
			$menu_location_details = array(
				'top-menu',
				'primary-menu',
				'social-menu',
				'footer-menu',
			);

			if ( ! empty( $menu_location_details ) ) {
				$navigation_settings      = array();
				$current_navigation_menus = wp_get_nav_menus();
				if ( ! empty( $current_navigation_menus ) && ! is_wp_error( $current_navigation_menus ) ) {
					foreach ( $current_navigation_menus as $menu ) {
						if ( in_array( $menu->slug, $menu_location_details ) ) {
							$navigation_settings[ $menu->slug ] = $menu->term_id;
						}
					}
				}
				set_theme_mod( 'nav_menu_locations', $navigation_settings );
			}
		}

		/**
		 * Render Site templates
		 *
		 * @since 1.2.0
		 */
		public function display_templates() {
			if ( $this->config_data && isset( $this->config_data['import_files'] ) ) {
				$free_demos = $this->config_data['import_files']['free'];
				$pro_demos  = $this->config_data['import_files']['pro'];
				?>
					<div class="uf-template-info">
						<h2><?php esc_html_e( 'Starter Templates', 'unfoldwp-import-companion' ); ?></h2>
						<p class="uf-template-desc">
							<?php esc_html_e( 'Get access to carefully crafted professional and visually appealing website templates, saving you valuable time and effort in the development process.', 'unfoldwp-import-companion' ); ?>
						</p>
					</div>
					<div class="uf-template-data ocdi__gl  js-ocdi-gl">
						<div class="ocdi__gl-item-container js-ocdi-gl-item-container">
							<?php
							foreach ( $free_demos as $index => $demo_data ) {
								$file_url   = $this->base_url . $this->current_template . '/' . $demo_data['import_path'] . '/';
								$import_url = add_query_arg(
									array(
										'page'   => $this->current_theme . '-demo-import',
										'step'   => 'import',
										'import' => esc_attr( $index ),
									),
									admin_url( 'themes.php' )
								);
								?>
								<div class="ocdi__gl-item js-ocdi-gl-item">
									<div class="ocdi__gl-item-image-container">
										<img class="ocdi__gl-item-image" src="<?php echo esc_url( $file_url . 'screenshot.webp' ); ?>">
									</div>
									<div class="ocdi__gl-item-footer  ocdi__gl-item-footer--with-preview">
										<h4 class="ocdi__gl-item-title" title="<?php echo esc_attr( $demo_data['import_name'] ); ?>"><?php echo esc_html( $demo_data['import_name'] ); ?></h4>
										<span class="ocdi__gl-item-buttons">
											<a class="ocdi__gl-item-button  button" href="<?php echo esc_url( $demo_data['preview_url'] ); ?>" target="_blank"><?php esc_html_e( 'Preview Demo', 'one-click-demo-import' ); ?></a>
											<a class="ocdi__gl-item-button  button  button-primary" href="<?php echo esc_url( $import_url ); ?>"><?php esc_html_e( 'Import Demo', 'one-click-demo-import' ); ?></a>
										</span>
									</div>
								</div>
								<?php
							}
							foreach ( $pro_demos as $index => $demo_data ) {
								$file_url = $this->base_url . $this->current_template . '/' . $demo_data['import_path'] . '/';
								?>
								<div class="ocdi__gl-item js-ocdi-gl-item uf-pro-template">
									<span class="uf-pro-badge"><?php esc_html_e( 'Premium', 'unfoldwp-import-companion' ); ?></span>
									<div class="ocdi__gl-item-image-container">
										<img class="ocdi__gl-item-image" src="<?php echo esc_url( $file_url . 'screenshot.webp' ); ?>">
									</div>
									<div class="ocdi__gl-item-footer  ocdi__gl-item-footer--with-preview">
										<h4 class="ocdi__gl-item-title" title="<?php echo esc_attr( $demo_data['import_name'] ); ?>"><?php echo esc_html( $demo_data['import_name'] ); ?></h4>
										<span class="ocdi__gl-item-buttons">
											<a class="ocdi__gl-item-button  button" href="<?php echo esc_url( $demo_data['preview_url'] ); ?>" target="_blank"><?php esc_html_e( 'Preview Demo', 'one-click-demo-import' ); ?></a>
											<a class="ocdi__gl-item-button  button  button-primary" href="<?php echo esc_url( $this->theme_url . '/?utm_source=wp&utm_medium=theme-dashboard&utm_campaign=templates' ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'one-click-demo-import' ); ?></a>
										</span>
									</div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				<?php
			}
		}

		/**
		 * Enqueue admin scripts and styles
		 *
		 * @since 1.2.0
		 */
		public function enqueue_scripts_and_styles() {
			wp_enqueue_style( 'unfoldwp-import-companion', UF_IC_URL . 'admin/css/unfoldwp-import-companion-admin.css', array(), UF_IC_VERSION, 'all' );
		}
	}

endif;

Unfoldwp_Import_Companion::get_instance();
