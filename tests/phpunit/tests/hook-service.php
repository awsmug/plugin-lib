<?php
/**
 * @package LeavesAndLovePluginLib
 * @subpackage Tests
 */

namespace Leaves_And_Love\Plugin_Lib\Tests;

/**
 * @group hook-service
 */
class Tests_Hook_Service extends Unit_Test_Case {
	public function data_hooks() {
		return array(
			array(
				'foo_filter',
				'__return_true',
				'filter',
				false,
			),
			array(
				'bar_filter',
				array( $this, 'arrayify' ),
				'filter',
				'initial value',
			),
			array(
				'foo_action',
				array( $this, 'increase_foo_option' ),
				'action',
				0,
			),
		);
	}

	/**
	 * @dataProvider data_hooks
	 */
	public function test_hooks( $name, $callback, $type, $value ) {
		require_once LALPL_TESTS_DATA . 'test-hook-service-class.php';

		$prefix = 'foo_bar_';
		$hooks = array(
			array(
				'name'     => $name,
				'callback' => $callback,
				'type'     => $type,
			),
		);

		$hook_service = new \Test_Hook_Service_Class( $prefix, $hooks );

		if ( 'filter' === $type ) {
			$modified = apply_filters( $name, $value );
			$this->assertSame( $value, $modified );
		} else {
			$value = call_user_func( $callback, false );
			do_action( $name );
			$modified = call_user_func( $callback, false );
			$this->assertSame( $value, $modified );
		}

		$this->assertFalse( $hook_service->remove_hooks() );
		$this->assertTrue( $hook_service->add_hooks() );
		$this->assertFalse( $hook_service->add_hooks() );

		if ( 'filter' === $type ) {
			$new_value = call_user_func( $callback, $value );
			$modified = apply_filters( $name, $value );
			$this->assertSame( $new_value, $modified );
		} else {
			$value = call_user_func( $callback, false, true );
			do_action( $name );
			$modified = call_user_func( $callback, false );
		}

		$this->assertTrue( $hook_service->remove_hooks() );

		if ( 'filter' === $type ) {
			$modified = apply_filters( $name, $value );
			$this->assertSame( $value, $modified );
		} else {
			$value = call_user_func( $callback, false );
			do_action( $name );
			$modified = call_user_func( $callback, false );
			$this->assertSame( $value, $modified );
		}
	}

	public function arrayify( $item ) {
		return array( $item );
	}

	public function increase_foo_option( $change = true, $try_change = false ) {
		$old_option = (int) get_option( 'foo', 0 );

		$new_option = $old_option + 1;

		if ( $try_change ) {
			return $new_option;
		}

		if ( $change ) {
			update_option( 'foo', $new_option );

			return $new_option;
		}

		return $old_option;
	}
}
