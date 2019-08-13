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
class Inite_Barpay_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('barpay/info.phtml');
    }
	
	public function getPdfUrl()
	{
		return Mage::helper('barpay')->getPdfUrl($this->getInfo()->getId());
	}
	
	public function canDownloadPdf()
	{
		return Mage::helper('barpay')->canDownloadPdf($this->getInfo()->getId());
	}
	
	public function canRepay()
	{
		$session = Mage::getSingleton('admin/session');
		if($session->getUser() && $this->getInfo()->getOrder()->getState() == 'pending_payment')
			return true;
		
		return false;
	}
	
	public function getRepayUrl()
	{
		return Mage::helper('adminhtml')->getUrl('barpayadmin/admin/repay', array('order_id'=>$this->getInfo()->getId()));
	}
}