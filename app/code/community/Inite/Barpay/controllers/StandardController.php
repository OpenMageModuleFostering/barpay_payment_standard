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
class Inite_Barpay_StandardController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	
	public function payAction()
	{
		$orderId = $this->_getCheckout()->getLastOrderId();
		$rCode = Mage::getModel('barpay/standard')->payOrder($orderId);
			
		if( $rCode == true){
			Mage::getSingleton('customer/session')->setData('barpay_order_id', $orderId);
			
		}
		elseif($rCode >= 500) {
			Mage::getSingleton('customer/session')->addError(Mage::helper('barpay')->__('Order has been canceled due to a permanent error.'));
			$this->_redirect('/');
		}
		elseif($rCode == 300) {
			Mage::getSingleton('customer/session')->addError(Mage::helper('barpay')->__('Payment gateway could not be reached.'));
			$this->_redirect('/');
		}
		
		$this->_forward('index');
	}
	
	public function fileAction()
	{
		$orderId = $this->getRequest()->getParam('order');
		$fileKey = $this->getRequest()->getParam('key');
		
		$order = Mage::getModel('sales/order')->load($orderId);
		$orderIncId = $order->getIncrementId();
		
		$file = Mage::helper('barpay')->getFilePath($orderId);
		
		Mage::getSingleton('core/session', array('name'=>'frontend'));
		$c_session = Mage::getSingleton('customer/session', array('name'=>'frontend'));
		
		Mage::getSingleton('core/session', array('name'=>'adminhtml'));
		$a_session = Mage::getSingleton('admin/session', array('name'=>'adminhtml'));
		
		$isProtected = ($fileKey == Mage::helper('barpay')->getFileKey($orderId)) ? false : true;
		$isAdmin = ($a_session && $a_session->isLoggedIn()) ? true : false;
		$isCustomer = ($c_session && $c_session->isLoggedIn()) ? true : false;
		
		if( !$isAdmin && !$isCustomer && $isProtected && !file_exists($file) ){
			$current_url = Mage::getUrl('barpay/standard/file', array('_current'=>true));
			$c_session->setBeforeAuthUrl($current_url);
			
			$this->getResponse()->setRedirect(Mage::helper('customer')->getLoginUrl());
			$this->getResponse()->sendHeaders();
			exit;
		}
				
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$customerId = $customer->getId();
		
		$frontFilename = 'barpay_voucher_'.$orderIncId.'.pdf';
		
		if( file_exists($file) && ($customerId == $order->getCustomerId() || $isAdmin || !$isProtected) ){

			if(function_exists("mime_content_type")){
				$mime_type = mime_content_type($file);
			}
			else {
				$mime_type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
			}

			if (file_exists($file))
			{
				$this->getResponse()
				->setHeader('Content-Disposition', 'attachment; filename='.$frontFilename)
				->setHeader('Content-Transfer-Encoding', 'binary')
				->setHeader('Content-Length', filesize($file))
				->setHeader('Content-type', $mime_type);
					
				$this->getResponse()->sendHeaders();
					
				readfile($file);
				exit;
			}
		}
	}
	
	
	public function callbackAction()
	{
		$params = $this->getRequest()->getParams();
		$success = Mage::getModel('barpay/standard')->callback($params);
		if($success){
			$this->getResponse()->setHeader('RETC', '0');
			$this->getResponse()->sendHeaders();
			echo 'RETC=0';
		}
		else {
			$this->getResponse()->setHeader('RETC', '500');
			$this->getResponse()->sendHeaders();
			echo 'RETC=500';
		}
		exit;
	}
	
	protected function _getCheckout()
	{
		return Mage::getSingleton('checkout/session');
	}

}
