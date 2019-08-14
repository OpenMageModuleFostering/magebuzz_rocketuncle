<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Rocketuncle_Block_Adminhtml_System_Config_Button extends Mage_Adminhtml_Block_System_Config_Form_Field{
	protected function _construct() {
		parent::_construct();
		$this->setTemplate('rocketuncle/system/config/button.phtml');
	}
	
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		return $this->_toHtml();
	}
	
	public function getAjaxCheckUrl() {
			return Mage::helper('adminhtml')->getUrl('rocketuncle/adminhtml_rocketuncle/get_access_token');
	}

	/**
	 * Generate button html
	 *
	 * @return string
	 */
	public function getButtonHtml() {
		$button = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
			'id'        => 'rocketuncle_button',
			'label'     => $this->helper('adminhtml')->__('Get Access Token'),
			'onclick'   => 'javascript:check(); return false;'
		));

		return $button->toHtml();
	}
}