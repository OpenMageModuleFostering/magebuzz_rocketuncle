<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Rocketuncle_Model_System_Config_Scope {
	public function toOptionArray() {
		return array(
			'print' => Mage::helper('rocketuncle')->__('Print Labels'),
			'order' => Mage::helper('rocketuncle')->__('Orders')
		);
	}
}