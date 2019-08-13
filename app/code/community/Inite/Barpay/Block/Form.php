<?php

/**
 * 
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * @VERSION Barpay 0.1.5
 *
**/

?>
<?php

class Inite_Barpay_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('barpay/form.phtml');
    }
	
	public function getMethodLabelAfterHtml() {
		$config = Mage::getModel('barpay/standard')->getConfigVars();
		
		if($config->show_logo){
			$mark = Mage::getConfig()->getBlockClassName('core/template');
			$mark = new $mark;
			$mark->setTemplate('barpay/payment/mark.phtml');
				
			return $mark->toHtml();
		}
		return '';
	}
	
	public function getMethodTitle() {
		$config = Mage::getModel('barpay/standard')->getConfigVars();
		
		if($config->show_logo){
			return '';
		}
		return Mage::getModel('barpay/standard')->getTitle();
	}

	public function hasMethodTitle() {
		return true;
	}
}
