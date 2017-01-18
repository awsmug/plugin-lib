<?php

namespace Leaves_And_Love\Sample_DB_Objects;

use Leaves_And_Love\Plugin_Lib\DB_Objects\Model;
use Leaves_And_Love\Plugin_Lib\DB_Objects\Traits\Sitewide_Model_Trait;
use Leaves_And_Love\Plugin_Lib\DB_Objects\Traits\Type_Model_Trait;

class Sample extends Model {
	use Sitewide_Model_Trait, Type_Model_Trait;

	protected $id = 0;

	protected $type = '';

	protected $title = '';

	protected $content = '';

	protected $parent_id = 0;

	protected $priority = 0.0;

	protected $active = false;

	public function __construct( $manager, $db_obj = null ) {
		parent::__construct( $manager, $db_obj );

		$this->primary_property = 'id';
		$this->type_property    = 'type';
	}
}
