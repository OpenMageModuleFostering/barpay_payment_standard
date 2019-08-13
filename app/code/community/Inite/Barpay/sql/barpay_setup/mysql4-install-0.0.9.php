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

$installer = $this;

$statusTable        = $installer->getTable('sales/order_status');
$statusStateTable   = $installer->getTable('sales/order_status_state');
$statusLabelTable   = $installer->getTable('sales/order_status_label');

$data = array(
    array('status' => 'pending_barpay', 'label' => 'Pending Barpay')
);
$installer->getConnection()->insertArray($statusTable, array('status', 'label'), $data);

$data = array(
    array('status' => 'pending_barpay', 'state' => 'pending_barpay', 'is_default' => 1)
);
$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);

?>