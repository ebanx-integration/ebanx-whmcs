<?php

/**
 * Copyright (c) 2014, EBANX Tecnologia da Informação Ltda.
 *  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * Neither the name of EBANX nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require( "../../../dbconnect.php" );
require( ROOTDIR."/includes/gatewayfunctions.php" );
include( ROOTDIR."/includes/invoicefunctions.php");
require( ROOTDIR."/modules/gateways/ebanx/ebanx-php/src/autoload.php");

global $CONFIG;

$gateway = getGatewayVariables('ebanx_checkout');

if (!$gateway["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

if(empty($_REQUEST['hash']))
{
    logTransaction($gateway['name'], "Customer returned without hashes", 'Error');
	die("Empty hash in the response URL");
}

\Ebanx\Config::set(array(
    'integrationKey' => $gateway['integration_key']
   ,'testMode'       => ($gateway['testmode'] == 'on') ? true : false
));

$query = \Ebanx\Ebanx::doQuery(array('hash' => $_REQUEST['hash']));

$invoiceid = $query->payment->order_number;

// we can implement some callback logic here, to instantly accept the payment without waiting


if($invoiceid)
{
    $invoiceid = checkCbInvoiceID($invoiceid,$gateway["name"]); # Checks invoice ID is a valid invoice number or ends processing
    
    // accept the payment if CO, else leave it for the callback
    if($query->payment->status == 'CO')
    {
        $invoiceid  = $query->payment->order_number;
        $transid    = $query->payment->hash;
        $amount     = $query->payment->amount_ext;
        $status     = $query->payment->status;

        try {
            $transid_present = mysql_num_rows(select_query('tblaccounts', 'id', array('transid' => $_REQUEST['hash'], 'gateway' => 'ebanx_checkout')));
            if ($transid_present)
            {
                logTransaction($gateway['name'], "Transaction already present {$transid} (#{$invoiceid})", 'Duplicate');
            }
            else
            {
                addInvoicePayment($invoiceid, $transid, $amount, "0", 'ebanx_checkout'); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
                logTransaction($gateway['name'], "Payment complete {$transid} (#{$invoiceid})", 'Complete');
            }
        }
        catch (Exception $e) {
            logTransaction($gateway['name'], "Exception in callback. Raw data:\n" . print_r($query,1), 'Error');
            echo $e->getMessage();
        }
    }

	header("Location: ".$CONFIG['SystemURL']."/viewinvoice.php?id={$invoiceid}");
    exit();
}
else
{
    logTransaction($gateway['name'], "Error contacting EBANX for {$_REQUEST['hash']}", 'Error');
	header("Location: ".$CONFIG['SystemURL']."clientarea.php?action=invoices");
    exit();	
}