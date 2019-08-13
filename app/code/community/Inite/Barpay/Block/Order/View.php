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
class Inite_Barpay_Block_Order_View extends Mage_Sales_Block_Order_View
{
    protected function _construct()
    {
        parent::_construct();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

		if ($this->getOrder()->getPayment()->getMethod() == 'barpay') {
            
			$this->setChild(
				'payment_info',
				$this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
			);
		}	
		
    }
   

}
