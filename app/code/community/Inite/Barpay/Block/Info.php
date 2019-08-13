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
class Inite_Barpay_Block_Info extends Mage_Payment_Block_Info
{

	private $_order;
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('barpay/info.phtml');	
    }
	
	private function getOrder()
	{
		//this code is ugly but it works
		try{
			$this->_order = $this->getInfo()->getOrder();
		} catch(Exception $e) { }
		
		return $this->_order;
	}
	
	public function getPdfUrl()
	{
		if($this->getOrder())
			return Mage::helper('barpay')->getPdfUrl($this->getOrder()->getId());
	}
	
	public function canDownloadPdf()
	{
		if($this->getOrder())
			return Mage::helper('barpay')->canDownloadPdf($this->getOrder()->getId());
		else 
			return false;
	}
	
	public function canRepay()
	{
		$session = Mage::getSingleton('admin/session');
		if($session->getUser() && $this->getOrder() && $this->getOrder()->getState() == 'pending_payment')
			return true;
		else
			return false;
	}
	
	public function getRepayUrl()
	{
		if($this->getOrder())
			return Mage::helper('adminhtml')->getUrl('barpayadmin/admin/repay', array('order_id'=>$this->getOrder()->getId()));
	}
}