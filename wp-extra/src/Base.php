<?php
namespace WPEXtra;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Base {
	protected $features = [];

	public function __construct() {
		foreach ( $this->features as $feature ) {
			if ( Helper::is_feature_active( $feature ) && method_exists( $this, $feature ) ) {
				$this->$feature();
			}
		}
	}
}
