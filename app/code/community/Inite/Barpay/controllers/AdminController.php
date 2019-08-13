<?php

/**
 * 
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * @VERSION Barpay 0.1.2
 *
**/

?>
<?php
class Inite_Barpay_AdminController extends Mage_Adminhtml_Controller_Action
{
	public function repayAction()
	{
		$orderId = $this->getRequest()->getParam('order_id',false);
		
		if($orderId){
			
			$rCode = Mage::getModel('barpay/standard')->payOrder($orderId);
				
			if( $rCode === true){
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('barpay')->__('Payment has been initialized.'));
			}
			elseif($rCode >= 500) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('barpay')->__('Order has been canceled due to a permanent error.'));
			}
			elseif($rCode == 300) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('barpay')->__('Payment gateway could not be reached.'));
			}
		}	
		$this->_redirect('adminhtml/sales_order/view',array('_current'=>true));
	}
}