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

class Inite_Barpay_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getPdfUrl($orderId){
						
		if($this->getVoucherUrl($orderId)){
			return $this->getVoucherUrl($orderId);
		}
		
		elseif(file_exists($this->getFilePath($orderId))){
			
			$params = array( '_secure'=>true,'order'=>$orderId);
			
			$fileKey = $this->getFileKey($orderId);
			if($fileKey) $params['key'] = $fileKey;
			
			return Mage::getUrl('barpay/standard/file', $params );
		}
		
		else {
			return '';
		}
	}
	
	public function canDownloadPdf($orderId)
	{
		if(file_exists($this->getFilePath($orderId))){
			return true;
		}
		
		elseif($this->getVoucherUrl($orderId)) {
			return true;
		}
		
		else 
			return false;
		
	}
	
	public function getFilePath($orderId){
		$config = Mage::getModel('barpay/standard')->getConfigVars();
		
		$filename = 'barpay_voucher_'.$orderId.'.pdf';
		$file = $config->file_save_path.$filename;
		
		return $file;
	}
	
	public function getFileKey($orderId){
		$order = Mage::getModel('sales/order')->load($orderId);
			
		if(!$order || !$order->getPayment()) return false;
			
		$transaction = $order->getPayment()->getAuthorizationTransaction();
		
		if($transaction){
			return $transaction->getAdditionalInformation('voucher_filekey');
		}
		else return false;
	}

	public function getVoucherUrl($orderId){
		$order = Mage::getModel('sales/order')->load($orderId);
			
		if(!$order || !$order->getPayment()) return false;
			
		$transaction = $order->getPayment()->getAuthorizationTransaction();
		
		if($transaction){
			return $transaction->getAdditionalInformation('voucher_url');
		}
		else return false;
	}
}