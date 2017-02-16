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

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
include(ROOTDIR . "/modules/gateways/ebanx/ebanx-php/src/autoload.php");

if(isset($_REQUEST['hash_codes']) && $_REQUEST['hash_codes'] != null)
{

    $hashes = $_REQUEST['hash_codes'];
    $hashes = explode(',', $hashes);
    $type = $_REQUEST['notification_type'];

    $gatewaymodule = "ebanx_checkout";
    $GATEWAY = getGatewayVariables($gatewaymodule);

    foreach ($hashes as $hash)
    {
    
        $transid_present = mysql_num_rows(select_query('tblaccounts', 'id', array('transid' => $hash, 'gateway' => 'ebanx_checkout')));
        if ($transid_present)
        {
            logTransaction($GATEWAY['name'], "Transaction already present {$hash}", '[Callback] Duplicate');
            echo 'Already processed;';
            continue;
        }
    
        \Ebanx\Config::set(array(
            'integrationKey' => $GATEWAY['integration_key']
           ,'testMode'       => ($GATEWAY['testmode'] == 'on') ? true : false
        ));

        $query = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

        if($query->status == 'SUCCESS')
        {
            $invoiceid = $query->payment->order_number;

            $invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

            if($query->payment->status == 'CO')
            {
                // $id = $query->payment->order_number; // unused?
                
                if($type == 'chargeback')
                {
                    logTransaction($GATEWAY['name'], "Chargeback received for {$hash}. Raw response: " . print_r($query,1), '[Callback] Chargeback');
                    echo 'Chargeback;';
                }

                else if($type == 'refund')
                {
                    logTransaction($GATEWAY['name'], "Refund received for {$hash}. Raw response: " . print_r($query,1), '[Callback] Refund');
                    echo 'Refunded;';
                }

                else
                {
                    $invoiceid  = $query->payment->order_number;
                    $transid    = $query->payment->hash;
                    $amount     = $query->payment->amount_ext;
                    $status     = $query->payment->status;

                    try {

                        addInvoicePayment($invoiceid, $transid, $amount, "0", $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
                        logTransaction($GATEWAY['name'], "Payment complete {$hash} (#{$invoiceid})", '[Callback] Complete');
                        echo 'Payment CO;';
                        
                    } catch (Exception $e) {
                        logTransaction($GATEWAY['name'], "Exception in callback:\n" . $e->getMessage() . "\nRaw data:\n" . print_r($query,1), '[Callback] Error');
                        echo $e->getMessage() . ';';
                    }
                }
            }

            if($query->payment->status == 'CA')
            {
                logTransaction($GATEWAY['name'], "Payment cancelled {$hash} (#{$invoiceid})", '[Callback] Cancelled');
                echo 'Payment CA;';
            }

            if($query->payment->status == 'PE')
            {
                logTransaction($GATEWAY['name'], "Payment still pending {$hash} (#{$invoiceid})", '[Callback] Pending');
                echo 'Payment still PE;';
            }
        }

        else
        {
            logTransaction($GATEWAY['name'], "Error contacting EBANX for {$hash}. Raw data:\n" . print_r($query,1), '[Callback] Error');
            echo 'Failure contacting EBANX;';
        }
    }
}
else
{
    echo 'No hashes appended';
}