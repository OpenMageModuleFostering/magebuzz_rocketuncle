<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Rocketuncle_Model_System_Config_Weightunit {
	public function toOptionArray() {
		return array(
			'kg' => Mage::helper('rocketuncle')->__('Kg'),
			'lb' => Mage::helper('rocketuncle')->__('Lb')
		);
	}
}