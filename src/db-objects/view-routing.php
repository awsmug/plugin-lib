<?php
/**
 * View_Routing manager class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects;

use Leaves_And_Love\Plugin_Lib\Service;
use Leaves_And_Love\Plugin_Lib\Traits\Container_Service_Trait;
use Leaves_And_Love\Plugin_Lib\Traits\Hooks_Trait;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\View_Routing' ) ) :

/**
 * Base class for a routing manager
 *
 * This class represents a general routing manager.
 *
 * @since 1.0.0
 */
abstract class View_Routing extends Service {
	use Container_Service_Trait, Hooks_Trait;

	/**
	 * The base string to use.
	 *
	 * This will be used for the archive slug as well as for the base for all
	 * singular model views.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $base = '';

	/**
	 * Permalink structure.
	 *
	 * Will be appended to the base string.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $permalink = '';

	/**
	 * Query variable name for a singular page. This will only be used if pretty permalinks are not enabled.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $singular_query_var = '';

	/**
	 * Query variable name for an archive page. This will only be used if pretty permalinks are not enabled.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $archive_query_var = '';

	/**
	 * Name of the template file for singular views.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $singular_template_name = '';

	/**
	 * Name of the template file for archive views.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $archive_template_name = '';

	/**
	 * Name for the current model variable that is used to pass it to the singular view template.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $model_var_name = '';

	/**
	 * Name for the current collection variable that is used to pass it to the archive view template.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $collection_var_name = '';

	/**
	 * Manager instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Manager
	 */
	protected $manager = null;

	/**
	 * Holds the current model for a request.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Model|null
	 */
	protected $current_model = null;

	/**
	 * Holds the current collection for a request.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Collection|null
	 */
	protected $current_collection = null;

	/**
	 * Whether the current request is for a singular model.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var bool
	 */
	protected $is_singular = false;

	/**
	 * Whether the current request is for a model archive.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var bool
	 */
	protected $is_archive = false;

	/**
	 * The page number for an archive request.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var int
	 */
	protected $paged = 1;

	/**
	 * Router service definition.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var string
	 */
	protected static $service_router = 'Leaves_And_Love\Plugin_Lib\Router';

	/**
	 * Template service definition.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var string
	 */
	protected static $service_template = 'Leaves_And_Love\Plugin_Lib\Template';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $prefix   The instance prefix.
	 * @param array  $services {
	 *     Array of service instances.
	 *
	 *     @type Leaves_And_Love\Plugin_Lib\Template      $template      The template instance.
	 *     @type Leaves_And_Love\Plugin_Lib\Error_Handler $error_handler The error handler instance.
	 * }
	 */
	public function __construct( $prefix, $services ) {
		$this->set_prefix( $prefix );
		$this->set_services( $services );
	}

	/**
	 * Returns the permalink for a given model.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model $model The model object.
	 * @return string Permalink for the model view.
	 */
	public function get_model_permalink( $model ) {
		if ( '' != get_option( 'permalink_structure' ) ) {
			$permalink = $this->base;

			$date_property = '';
			$special_date_parts = array();
			if ( method_exists( $this->manager, 'get_date_property' ) ) {
				$date_property = $this->manager->get_date_property();

				$special_date_parts = array(
					'year'  => 'Y',
					'month' => 'm',
					'day'   => 'd',
				);
			}

			$permalink_parts = explode( '/', $this->permalink );
			foreach ( $permalink_parts as $permalink_part ) {
				if ( preg_match( '/^%([a-z0-9_]+)%$/', $permalink_part, $matches ) ) {
					if ( ! empty( $date_property ) && isset( $special_date_parts[ $matches[1] ] ) ) {
						$permalink .= '/' . mysql2date( $special_date_parts[ $matches[1] ], $model->$date_property, false );
					} else {
						$property_name = $matches[1];
						$permalink .= '/' . $model->$property_name;
					}
				} else {
					$permalink .= '/' . $permalink_part;
				}
			}

			$permalink .= '/';

			return home_url( $permalink );
		}

		$primary_property = $this->manager->get_primary_property();

		return add_query_arg( $this->singular_query_var, $model->$primary_property, home_url( '/' ) );
	}

	/**
	 * Returns the permalink for the model archive.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int $page Optional. Page number to get its archive permalink. Default 1.
	 * @return string Permalink for the archive view.
	 */
	public function get_archive_permalink( $page = 1 ) {
		if ( '' != get_option( 'permalink_structure' ) ) {
			$permalink = $this->base . '/';

			if ( $page > 1 ) {
				$permalink .= 'page/' . $page . '/';
			}

			return home_url( $permalink );
		}

		$query_args = array( $this->archive_query_var => '1' );
		if ( $page > 1 ) {
			$query_args['paged'] = $page;
		}

		return add_query_arg( $query_args, home_url( '/' ) );
	}

	/**
	 * Checks whether the current request is for a singular model.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool True if the request is for a singular model, false otherwise.
	 */
	public function is_singular() {
		return $this->is_singular;
	}

	/**
	 * Checks whether the current request is for a model archive.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool True if the request is for a model archive, false otherwise.
	 */
	public function is_archive() {
		return $this->is_archive;
	}

	/**
	 * Sets the manager instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Manager instance.
	 */
	public function set_manager( $manager ) {
		$this->manager = $manager;

		$this->setup_vars();
		$this->register_routes();
	}

	/**
	 * Handles a request for a singular model.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $query_vars Array of query variables.
	 * @return bool True if a singular model for the query variables was found, false otherwise.
	 */
	public function handle_singular_request( $query_vars ) {
		if ( $this->is_singular ) {
			return true;
		}

		$this->is_singular = true;

		if ( isset( $query_vars[ $this->singular_query_var ] ) ) {
			$primary_property_value = absint( $query_vars[ $this->singular_query_var ] );
			if ( 0 === $primary_property_value ) {
				return false;
			}

			$query_vars['include'] = array( $primary_property_value );
			unset( $query_vars[ $this->singular_query_var ] );
		}

		$query_params = $this->get_query_params( $query_vars );

		$collection = $this->manager->query( $query_params );

		$this->current_model = $collection->current();
		if ( null === $this->current_model ) {
			return false;
		}

		return true;
	}

	/**
	 * Handles a request for a model archive.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $query_vars Array of query variables.
	 * @return bool Always returns true.
	 */
	public function handle_archive_request( $query_vars ) {
		if ( $this->is_archive ) {
			return true;
		}

		$this->is_archive = true;

		if ( isset( $query_vars[ $this->archive_query_var ] ) ) {
			unset( $query_vars[ $this->archive_query_var ] );
		}

		if ( isset( $query_vars['paged'] ) ) {
			$paged = absint( $query_vars['paged'] );
			if ( $paged > 1 ) {
				$this->paged = $paged;
			}

			unset( $query_vars['paged'] );
		}

		$query_params = $this->get_query_params( $query_vars );

		$this->current_collection = $this->manager->query( $query_params );

		return true;
	}

	/**
	 * Sets up the current view.
	 *
	 * This method is invoked on every successfully routed request. It adds the hooks to adjust the
	 * regular behavior of WordPress in order to correctly handle the custom model content.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function setup_view() {
		$this->add_filter( 'pre_get_document_title', array( $this, 'set_document_title' ), 1, 1 );
		$this->add_filter( 'wp_head', array( $this, 'rel_canonical' ), 10, 0 );
		$this->add_action( 'template_redirect', array( $this, 'load_template' ), 1, 0 );
	}

	/**
	 * Returns the document title for a model singular or archive view.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $title Original title to be overridden.
	 * @return string New document title.
	 */
	protected function set_document_title( $title ) {
		if ( ! empty( $title ) ) {
			return $title;
		}

		$title = array();

		if ( $this->is_archive() ) {
			$title['title'] = $this->manager->get_message( 'view_routing_archive_title' );
			if ( $this->paged > 1 ) {
				$title['page'] = sprintf( $this->manager->get_message( 'view_routing_archive_title_page_suffix' ), number_format_i18n( $this->paged ) );
			}
		} else {
			if ( method_exists( $this->manager, 'get_title_property' ) ) {
				$title_property = $this->manager->get_title_property();
				$title['title'] = $this->current_model->$title_property;
			} else {
				$primary_property = $this->manager->get_primary_property();
				$title['title'] = sprintf( $this->manager->get_message( 'view_routing_singular_fallback_title' ), number_format_i18n( $this->current_model->$primary_property ) );
			}
		}

		$title['site'] = get_bloginfo( 'name', 'display' );

		/** This filter is documented in wp-includes/general-template.php */
		$sep = apply_filters( 'document_title_separator', '-' );

		/** This filter is documented in wp-includes/general-template.php */
		$title = apply_filters( 'document_title_parts', $title );

		$title = implode( " $sep ", array_filter( $title ) );
		$title = wptexturize( $title );
		$title = convert_chars( $title );
		$title = esc_html( $title );
		$title = capital_P_dangit( $title );

		return $title;
	}

	/**
	 * Prints the canonical header for a singular view.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function rel_canonical() {
		if ( ! $this->is_singular() ) {
			return;
		}

		$permalink = $this->get_model_permalink( $this->current_model );

		echo '<link rel="canonical" href="' . esc_url( $permalink ) . '">' . "\n";
	}

	/**
	 * Loads the template for a singular view.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function load_template() {
		/** This filter is documented in wp-includes/template-loader.php */
		if ( 'HEAD' === $_SERVER['REQUEST_METHOD'] && apply_filters( 'exit_on_http_head', true ) ) {
			exit;
		}

		if ( $this->is_archive() ) {
			$this->template()->get_partial( $this->archive_template_name, array(
				$this->collection_var_name => $this->current_collection,
				'template'                 => $this->template(),
			) );
		} else {
			$data = array(
				$this->model_var_name => $this->current_model,
				'template'            => $this->template(),
			);

			if ( method_exists( $this->manager, 'get_slug_property' ) ) {
				$slug_property = $this->manager->get_slug_property();

				$data['template_suffix'] = $this->current_model->$slug_property;
			}

			$this->template()->get_partial( $this->singular_template_name, $data );
		}

		exit;
	}

	/**
	 * Maps matches from a route regular expression to query variables.
	 *
	 * This method only works if the regular expression stores all dynamic parts under named array keys.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $matches Array of regular expression matches.
	 * @return array Associative array of query variables.
	 */
	public function map_matches_to_query_vars( $matches ) {
		$query_vars = array();

		foreach ( $matches as $key => $value ) {
			if ( is_numeric( $key ) ) {
				continue;
			}

			$query_vars[ $key ] = $value;
		}

		return $query_vars;
	}

	/**
	 * Sets up the class properties.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function setup_vars() {
		$this->base = $this->manager->get_message( 'view_routing_base' );

		if ( method_exists( $this->manager, 'get_slug_property' ) ) {
			$slug_property = $this->manager->get_slug_property();

			$this->permalink = '%' . $slug_property . '%';
		} else {
			$primary_property = $this->manager->get_primary_property();

			$this->permalink = '%' . $primary_property . '%';
		}

		$singular_slug = $this->manager->get_singular_slug();
		$plural_slug   = $this->manager->get_plural_slug();

		$this->singular_query_var = $this->get_prefix() . $singular_slug . '_' . $this->manager->get_primary_property();
		$this->archive_query_var  = $this->get_prefix() . $plural_slug;

		$this->singular_template_name = $singular_slug;
		$this->archive_template_name  = $plural_slug;

		$this->model_var_name      = $singular_slug;
		$this->collection_var_name = $plural_slug;
	}

	/**
	 * Registers routes for the model singular views and archives.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_routes() {
		if ( ! empty( $this->singular_query_var ) ) {
			$slug = $this->get_prefix() . $this->manager->get_singular_slug();

			$pattern = '^' . $this->base;

			$permalink_parts = explode( '/', $this->permalink );
			foreach ( $permalink_parts as $permalink_part ) {
				if ( preg_match( '/^%([a-z0-9_]+)%$/', $permalink_part, $matches ) ) {
					$pattern .= '/(?P<' . $matches[1] . '>[\w-]+)';
				} else {
					$pattern .= '/' . $permalink_part;
				}
			}

			$pattern .= '/?$';

			$query_vars = array(
				$this->singular_query_var => '^[\d]+$',
			);

			$this->router()->add_route( $slug, $pattern, array( $this, 'map_matches_to_query_vars' ), $query_vars, array( $this, 'handle_singular_request' ) );
		}

		if ( ! empty( $this->archive_query_var ) ) {
			$slug = $this->get_prefix() . $this->manager->get_plural_slug();

			$pattern = '^' . $this->base . '(/page/?(?P<paged>[\d]+)/?)?$';

			$query_vars = array(
				$this->archive_query_var => '^[\w]+$',
				'paged'                  => '^[\d]+$',
			);

			$this->router()->add_route( $slug, $pattern, array( $this, 'map_matches_to_query_vars' ), $query_vars, array( $this, 'handle_archive_request' ) );
		}
	}

	/**
	 * Retrieves the array of parameters for the model query, based on query variables and defaults.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $query_vars Query variables for the current request.
	 * @return array Query parameters for the model query.
	 */
	protected function get_query_params( $query_vars ) {
		$number        = $this->is_singular() ? 1 : absint( get_option( 'posts_per_page' ) );
		$offset        = ( $this->paged - 1 ) * $number;
		$no_found_rows = $this->is_singular() ? true : false;

		$query_params = array(
			'number'        => $number,
			'offset'        => $offset,
			'no_found_rows' => $no_found_rows,
		);

		if ( method_exists( $this->manager, 'get_date_property' ) ) {
			$date_query = array();

			foreach ( array( 'year', 'month', 'day' ) as $date_part ) {
				if ( isset( $query_vars[ $date_part ] ) ) {
					$date_query[ $date_part ] = $query_vars[ $date_part ];
					unset( $query_vars[ $date_part ] );
				}
			}

			if ( ! empty( $date_query ) ) {
				$query_params['date_query'] = array( $date_query );
			}
		}

		$query_params = array_merge( $query_params, $query_vars );

		if ( method_exists( $this->manager, 'get_type_property' ) ) {
			$type_property = $this->manager->get_type_property();

			$query_params[ $type_property ] = $this->manager->types()->get_public();
		}

		if ( method_exists( $this->manager, 'get_status_property' ) ) {
			$status_property = $this->manager->get_status_property();

			$query_params[ $status_property ] = $this->manager->statuses()->get_public();
		}

		return $query_params;
	}
}

endif;