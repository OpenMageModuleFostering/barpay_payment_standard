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
class Inite_Barpay_Block_Checkout_Success extends Mage_Core_Block_Template
{

    public function getPdfUrl(){
		$orderId = Mage::getSingleton('customer/session')->getData('barpay_order_id');
		return Mage::helper('barpay')->getPdfUrl($orderId);
	}

	public function canDownloadPdf()
	{
		$orderId = Mage::getSingleton('customer/session')->getData('barpay_order_id');
		return Mage::helper('barpay')->canDownloadPdf($orderId);
	}
}