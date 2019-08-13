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
class Inite_Barpay_Model_Email_Template_Mailer extends Mage_Core_Model_Email_Template_Mailer
{
	protected $_afile = array();
 
	public function addAttachment($fileContents,$fileName,$fileMime)
	{
		$tmp = array();
		$tmp['fileContents'] = $fileContents;
		$tmp['fileName'] = $fileName;
		$tmp['fileMime'] = $fileMime;
		$this->_afile = $tmp;
		return $this;
	}
	
	public function send()
    {
        $emailTemplate = Mage::getModel('core/email_template');

        while (!empty($this->_emailInfos)) {
            $emailInfo = array_pop($this->_emailInfos);
            $emailTemplate->addBcc($emailInfo->getBccEmails());
            
			if(!empty($this->_afile))
			{
				$emailTemplate->setEmAttachments($this->_afile); 
			}
			
            $emailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))
                ->sendTransactional(
                $this->getTemplateId(),
                $this->getSender(),
                $emailInfo->getToEmails(),
                $emailInfo->getToNames(),
                $this->getTemplateParams(),
                $this->getStoreId()
            );
        }
        return $this;
    }

}