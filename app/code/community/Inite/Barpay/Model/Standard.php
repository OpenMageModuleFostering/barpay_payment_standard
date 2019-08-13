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
 
class Inite_Barpay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
 
	protected $_code = 'barpay';
	protected $_infoBlockType = 'barpay/info';	 
	protected $_formBlockType = 'barpay/form';
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
 
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('barpay/standard/pay', array('_secure' => true));
	}

	public function getConfigVars()
	{
		$obj = new StdClass();
		$storeId = Mage::app()->getRequest()->getParam('store', Mage::app()->getStore()->getStoreId());
		
		if(Mage::getStoreConfig('payment/barpay/sandbox',$storeId)){
			$obj->is_sandbox_mode = true;
			$obj->product_id = '4260284350017';
			$obj->submit_url = 'https://test.barpay-system.de/BarPayGateWay/BarPayIssue.php?wsdl';
			$obj->callback_allowed_ips = array('46.51.205.62','176.34.143.159');
		}
		else {
			$obj->is_sandbox_mode = false;
			$obj->product_id = Mage::getStoreConfig('payment/barpay/product_id',$storeId);
			$obj->submit_url = Mage::getStoreConfig('payment/barpay/submit_url',$storeId);
			$obj->callback_allowed_ips = array('49.51.207.28', '46.51.207.29');
		}
		
		$obj->preshared_key = Mage::getStoreConfig('payment/barpay/preshared_key',$storeId); 
		$obj->bi_id = Mage::getStoreConfig('payment/barpay/merchant_id',$storeId);
		$obj->bi_pwd = Mage::getStoreConfig('payment/barpay/merchant_pwd',$storeId);
		$obj->valdat = is_numeric(Mage::getStoreConfig('payment/barpay/valid_days',$storeId)) && intval(Mage::getStoreConfig('payment/barpay/valid_days',$storeId)) > 0 ? intval(Mage::getStoreConfig('payment/barpay/valid_days',$storeId)) : 30;

		$obj->show_logo = Mage::getStoreConfig('payment/barpay/show_logo',$storeId);
		
		$obj->file_save_path = rtrim(Mage::getStoreConfig('payment/barpay/save_path',$storeId),"\\/").'/';
		$obj->file_save_enabled = Mage::getStoreConfig('payment/barpay/save_enabled',$storeId);
		
		return $obj;
	}

	public function callback($params){
		
		$clientIp = !empty($_SERVER['REMOTE_ADDR']) ?  $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_CLIENT_IP'];
		$config = $this->getConfigVars();
		
		if(!in_array($clientIp, $config->callback_allowed_ips)) return false;
		
		if(isset($params['CODE'],$params['PAYC'], $params['PAYMOD'],$params['DESCRIPTION'],$params['PAYCODE_HISTORY_1'],$params['SHASIG'])){
			$transactionId = $params['PAYC'];
			$respCode = $params['CODE'];
			
			$signature = $params['SHASIG']; 
			
			$i=1;
			while(isset($params['PAYCODE_HISTORY_'.$i])){
				$meta = $params['PAYCODE_HISTORY_'.$i];
				$i++;
			}
			
			$params['REF'] = preg_replace('/.*REF=([0-9]*)||.*\z/', '$1', $meta);
			$params['PAYVAL'] = preg_replace('/.*PAYVAL=([0-9\.]*)||.*\z/', '$1', $meta);
			$params['TIMEAG'] = preg_replace('/.*TIMEAG=(.*)\s*\z/', '$1', $meta);
			 
			$transactionCollection = Mage::getModel('sales/order_payment_transaction')->getCollection();
			$transactionCollection->getSelect()->where('txn_id = ?',$transactionId);
			$transaction = $transactionCollection->getFirstItem();
			
			$payMode = $params['PAYMOD'];
			
			if($transaction->getOrderId() && $respCode == '000'){
				
				$order = Mage::getModel('sales/order')->load($transaction->getOrderId());
								
				if($signature == $this->getSignature($transaction->getOrderId(), $payMode, $params) ){
					
					$transaction->setOrderPaymentObject($order->getPayment())->close();
					
					$state = 'processing';
					$status = 'processing';
					$comment = $params['DESCRIPTION'];
					$isCustomerNotified = false;
					$order->setState($state, $status, $comment, $isCustomerNotified);
					$order->save();
					
					return true;
				}
			}
			
		}
		
		return false;
	}
	
	
	private function hasTransaction($orderId){
		$transactionCollection = Mage::getModel('sales/order_payment_transaction')->getCollection();
		$transactionCollection->getSelect()->where('order_id = ?',$orderId);
		return $transactionCollection->count();
	}
	
	public function payOrder($orderId){
		$order = Mage::getModel('sales/order')->load($orderId);
		$payment = $order->getPayment();
		$config = $this->getConfigVars();
		
		if(!$this->hasTransaction($orderId)){
			$rCode = $this->apiCall($orderId);
			
			if( $rCode == 0){
				$state = 'pending_barpay';
				$status = 'pending_barpay';
				$comment = 'Set state to pending BarPay payment';
				$isCustomerNotified = false;
				$order->setState($state, $status, $comment, $isCustomerNotified);
				$order->save();
				
				if($config->file_save_enabled){
					$order->sendNewOrderEmailWithAttachment();
				}
				else{
					$order->sendNewOrderEmail();
				}
				return true;
			}
			elseif($rCode >= 500) {
				$state = 'canceled';
				$status = 'canceled';
				$comment = 'Set state to canceled (error: '.$rCode.')';
				$isCustomerNotified = false;
				$order->setState($state, $status, $comment, $isCustomerNotified);
				$order->save();
			}
			elseif($rCode == 300) {
				$state = 'pending_payment';
				$status = 'pending_payment';
				$comment = 'Gateway error: Retry to init payment process';
				$isCustomerNotified = false;
				$order->setState($state, $status, $comment, $isCustomerNotified);
				$order->save();
			}
			
			return $rCode;
		}
		
		return false;
	}
	
	private function getSignature($orderId, $payMode, $params = array())
	{
	
		$order = Mage::getModel('sales/order')->load($orderId);
		$config = $this->getConfigVars();
		
		$preSharedKey = $config->preshared_key; 
		$biid = $config->bi_id;
		$bipwd = $config->bi_pwd;
		$prodid = $config->product_id;
		$payval = sprintf("%01.2f", $order->getBaseGrandTotal());
		$invbi = $order->getIncrementId();
		$tracebi = $order->getId();
		
		if($payMode == '10000'){
			$sigValues = $preSharedKey.$biid.$bipwd.$tracebi.$payMode.$prodid.$payval.$invbi;
			
		}

		elseif($payMode == '10500'){
			$sigValues = $preSharedKey.$biid.$bipwd.$tracebi.$payMode.$params['CODE'].$params['REF'].$params['PAYVAL'].$params['TIMEAG'];
			 
		}

			
		return  hash('sha256', $sigValues);
	}
	
	
	private function apiCall($orderId)
	{
		$order = Mage::getModel('sales/order')->load($orderId);
		$config = $this->getConfigVars();
				
		$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
		
		$billing_address_id= $order->getData('billing_address_id');
		$address = Mage::getModel('sales/order_address')->load($billing_address_id);
		
		$payMode = "10000";
		$biid = $config->bi_id;
		$bipwd = $config->bi_pwd;
		$prodid = $config->product_id;
		$payc = "";
		$payval = sprintf("%01.2f", $order->getBaseGrandTotal());
		$invbi = $order->getIncrementId();
		$tracebi = $order->getId();
		$billdat = Mage::getModel('core/date')->date('d-m-Y', strtotime(now()));
		$bicphone = $address->getData('telephone');
		$bicemail = $config->is_sandbox_mode ? 'installation@ezv-gmbh.de' : $customer->getData('email');
		if(!$config->file_save_enabled){
			$bicemail = 'URL#'.$bicemail;
		}
		$bicecity = $address->getCity();
		$bicplz = $address->getPostcode();
		
		$bicstrt = $address->getStreetFull();
		$bicstrtnr = '';
		
		$bicnum = $customer->getId();
		$binoturl = 'POST#'.Mage::getUrl('barpay/standard/callback', array('_secure'=>true, 'store'=>Mage::app()->getStore()->getStoreId()));
		
		$valdat = date("d-m-Y",Mage::app()->getLocale()->storeTimeStamp() + $config->valdat * 24 * 60 * 60);
		
		$hash = $this->getSignature($orderId, $payMode);
		
		try{
		
		$client = new SoapClient($config->submit_url); 
		$response = $client->Issue($payMode, $biid, $bipwd, $prodid, $payval, $invbi, $tracebi, $valdat, $billdat, $bicphone, $bicemail, $bicecity, $bicplz, $bicstrt, $bicstrtnr, $binoturl,"$hash");

		} catch (Exception $e) {
			return 300;
		}
		
		if($response->RECF){
			
			if(!$config->file_save_enabled){
				$voucherUrl = $response->RECF;
			}
			else {
				
				$file = Mage::helper('barpay')->getFilePath($orderId);
				
				$voucherFileKey = md5($file.time());
				
				$base64Data = preg_replace('/^\s*<!\[CDATA\[([\s\S]*)\]\]>\s*\z/', '$1', $response->RECF);
				file_put_contents($file, base64_decode($base64Data));
			}
		}
		
		if($response->PAYCODE){
			$payment = $order->getPayment();
			$grandTotal = $order->getBaseGrandTotal();
			$payment->setTransactionId($response->PAYCODE)
				->setPreparedMessage("Waiting for payment")
				->setIsTransactionClosed(0)
				->registerAuthorizationNotification($grandTotal);
			$order->save();
			
			
			$transaction =	Mage::getModel('sales/order_payment_transaction')
					->setOrder($order)
					->setOrderPaymentObject($order->getPayment())
					->loadByTxnId($response->PAYCODE);
					
			if(isset($voucherUrl)){
				$transaction->setAdditionalInformation('voucher_url',$voucherUrl)->save();
			}
			elseif(isset($voucherFileKey)){
				$transaction->setAdditionalInformation('voucher_filekey',$voucherFileKey)->save();
			}
		}
		
		return $response->RETC;
	}

	public function getTitle()
	{
		return $this->getConfigData('title');
	}
}