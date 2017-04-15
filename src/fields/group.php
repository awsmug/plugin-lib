<?php
/**
 * Group field class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Fields;

use Leaves_And_Love\Plugin_Lib\Fields\Interfaces\Field_Manager_Interface;
use WP_Error;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Fields\Group' ) ) :

/**
 * Class for a group field.
 *
 * @since 1.0.0
 */
class Group extends Field implements Field_Manager_Interface {
	/**
	 * Fields that are part of this group.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Backbone view class name to use for this field.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $backbone_view = 'GroupFieldView';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\Fields\Field_Manager $manager Field manager instance.
	 * @param string                                          $id      Field identifier.
	 * @param array                                           $args    {
	 *     Optional. Field arguments. Anything you pass in addition to the default supported arguments
	 *     will be used as an attribute on the input. Default empty array.
	 *
	 *     @type string          $section       Section identifier this field belongs to. Default empty.
	 *     @type string          $label         Field label. Default empty.
	 *     @type string          $description   Field description. Default empty.
	 *     @type array           $fields        Sub-fields that should be part of this group. Must be an array where
	 *                                          each key is the identifier of a sub-field and the value is an
	 *                                          array of field arguments, including an additional `type` argument.
	 *                                          Default empty.
	 *     @type bool|int        $repeatable    Whether this should be a repeatable field. An integer can also
	 *                                          be passed to set the limit of repetitions allowed. Default false.
	 *     @type callable        $validate      Custom validation callback. Will be executed after doing the regular
	 *                                          validation if no errors occurred in the meantime. Default none.
	 *     @type callable|string $before        Callback or string that should be used to generate output that will
	 *                                          be printed before the field. Default none.
	 *     @type callable|string $after         Callback or string that should be used to generate output that will
	 *                                          be printed after the field. Default none.
	 * }
	 */
	public function __construct( $manager, $id, $args = array() ) {
		if ( isset( $args['fields'] ) ) {
			$field_instances = array();
			foreach ( $args['fields'] as $field_id => $field_args ) {
				$type = 'text';
				if ( isset( $field_args['type'] ) ) {
					$type = $field_args['type'];
					unset( $field_args['type'] );
				}

				if ( 'group' === $type || ! Field_Manager::is_field_type_registered( $type ) ) {
					continue;
				}

				// Sub-fields have some additional argument restrictions.
				$field_args['repeatable'] = false;
				$field_args['before'] = null;
				$field_args['after'] = null;
				if ( isset( $field_args['dependencies'] ) ) {
					unset( $field_args['dependencies'] );
				}

				$class_name = Field_Manager::get_registered_field_type( $type );
				$field_instances[ $field_id ] = new $class_name( $this, $field_id, $field_args );
			}
		}

		parent::__construct( $manager, $id, $args );

		// The slug must contain the ID to ensure all required templates are printed.
		$this->slug .= '-' . $this->id;

		// Labels are not actual labels, but rather headings for their groups.
		$this->label_mode = 'no_assoc';
	}

	/**
	 * Creates the id attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string          $id    Field identifier.
	 * @param int|string|null $index Optional. Index of the field, in case it is a repeatable field.
	 *                               Default null.
	 * @return string Field id attribute.
	 */
	public function make_id( $id, $index = null ) {
		$field_id = $this->manager->make_id( $this->id, $this->index );

		return $field_id . '-' . str_replace( '_', '-', $id );
	}

	/**
	 * Creates the name attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string          $id    Field identifier.
	 * @param int|string|null $index Optional. Index of the field, in case it is a repeatable field.
	 *                               Default null.
	 * @return string Field name attribute.
	 */
	public function make_name( $id, $index ) {
		$field_name = $this->manager->make_name( $this->id, $this->index );

		return $field_name . '[' . $id . ']';
	}

	/**
	 * Returns a specific manager message.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $identifier Identifier for the message.
	 * @param bool   $noop       Optional. Whether this is a noop message. Default false.
	 * @return string|array Translated message, or array if $noop, or empty string if
	 *                      invalid identifier.
	 */
	public function get_message( $identifier, $noop = false ) {
		$this->manager->get_message( $identifier, $noop );
	}

	/**
	 * Enqueues the necessary assets for the field.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Array where the first element is an array of script handles and the second element
	 *               is an associative array of data to pass to the main script.
	 */
	public function enqueue() {
		$main_dependencies = array();
		$localize_data = array();

		foreach ( $this->fields as $id => $field_instance ) {
			$type = $field_instance->slug;

			if ( ! $this->manager->enqueued( $type ) ) {
				list( $new_dependencies, $new_localize_data ) = $field_instance->enqueue();

				if ( ! empty( $new_dependencies ) ) {
					$main_dependencies = array_merge( $main_dependencies, $new_dependencies );
				}

				if ( ! empty( $new_localize_data ) ) {
					$localize_data = array_merge_recursive( $localize_data, $new_localize_data );
				}

				$this->manager->enqueued( $type, true );
			}
		}

		return array( $main_dependencies, $localize_data );
	}

	/**
	 * Renders a single input for the field.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param mixed $current_value Current field value.
	 */
	protected function render_single_input( $current_value ) {
		$group_id = $this->get_id_attribute();

		$class = '';
		if ( ! empty( $this->input_classes ) ) {
			$class = ' class="' . implode( ' ', $this->input_classes ) . '"';
		}

		?>
		<div id="<?php echo esc_attr( $group_id ); ?>"<?php echo $class; ?>>
			<?php foreach ( $this->fields as $id => $field_instance ) : ?>
				<div<?php echo $field_instance->get_wrap_attrs(); ?>>
					<?php $field_instance->render_label(); ?>
					<?php $field_instance->render_content(); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$this->render_repeatable_remove_button();
	}

	/**
	 * Prints a single input template.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function print_single_input_template() {
		?>
		<div id="{{ data.id }}"<# if ( data.inputAttrs.class ) { #> class="{{ data.inputAttrs.class }}"<# } #>>
			<?php foreach ( $this->fields as $id => $field_instance ) : ?>
				<# _.alias( data.fields.<?php echo $id; ?>, function( data ) { #>
					<div{{{ _.attrs( data.wrapAttrs ) }}}>
						<?php $field_instance->print_label_template(); ?>
						<?php $field_instance->print_content_template(); ?>
					</div>
				<# } ) #>
			<?php endforeach; ?>
		</div>
		<?php
		$this->print_repeatable_remove_button_template();
	}

	/**
	 * Transforms single field data into an array to be passed to JavaScript applications.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param mixed $current_value Current value of the field.
	 * @return array Field data to be JSON-encoded.
	 */
	protected function single_to_json( $current_value ) {
		$data = parent::single_to_json( $current_value );
		$data['fields'] = array();

		foreach ( $this->fields as $id => $field_instance ) {
			$partial_value = is_array( $current_value ) && isset( $current_value[ $id ] ) ? $current_value[ $id ] : $field_instance->default;

			$data['fields'][ $id ] = $field_instance->to_json( $partial_value );
		}

		return $data;
	}

	/**
	 * Validates a single value for the field.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param mixed $value Value to validate. When null is passed, the method
	 *                     assumes no value was sent.
	 * @return mixed|WP_Error The validated value on success, or an error
	 *                        object on failure.
	 */
	protected function validate_single( $value = null ) {
		if ( empty( $value ) ) {
			return array();
		}

		$result = array();
		$errors = new WP_Error();

		foreach ( $this->fields as $id => $field_instance ) {
			$partial_value = is_array( $value ) && isset( $value[ $id ] ) ? $value[ $id ] : null;

			$validated_value = $field_instance->validate( $partial_value );
			if ( is_wp_error( $validated_value ) ) {
				$error = $validated_value;
				$error_data = $error->get_error_data();
				if ( isset( $error_data['validated'] ) ) {
					$result[ $id ] = $error_data['validated'];
				}

				$errors->add( $error->get_error_code(), $error->get_error_message() );
				continue;
			}

			$result[ $id ] = $validated_value;
		}

		if ( ! empty( $errors->errors ) ) {
			if ( ! empty( $result ) ) {
				$main_code = $errors->get_error_code();
				$errors->error_data[ $main_code ] = array( 'validated' => $result );
			}

			return $errors;
		}

		return $result;
	}

	/**
	 * Checks whether a value is considered empty.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param mixed $value Value to check whether its empty.
	 * @return bool True if the value is considered empty, false otherwise.
	 */
	protected function is_value_empty( $value ) {
		foreach ( $this->fields as $id => $field ) {
			$partial_value = is_array( $value ) && isset( $value[ $id ] ) ? $value[ $id ] : $field->default;

			if ( is_string( $partial_value ) ) {
				$partial_value = trim( $partial_value );
			}

			if ( ! empty( $partial_value ) ) {
				return false;
			}
		}

		return true;
	}
}

endif;
