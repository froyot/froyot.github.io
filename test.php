<?php

class TestMagical {
	public $name;
	private $nickname;

	public function __set($property, $value) {
		echo "set property " . $property . ",value is " . $value;
	}

	public function __isset($property) {
		echo "check isset property " . $property;
		return true;
	}
}

$test = new TestMagical();
$test->nickname = "tttt";
var_dump(isset($test->name));