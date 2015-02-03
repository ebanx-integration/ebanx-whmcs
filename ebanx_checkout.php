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

function ebanx_checkout_config()
{
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"EBANX - Boleto Bancário, TEF, PagoEfectivo, SafetyPay"),
     "integration_key" => array("FriendlyName" => "Integration Key", "Type" => "text", "Size" => "100", ),
     "installments" => array("FriendlyName" => "Enable Installments", "Type" => "yesno", "Description" => "Enable installments", ),
     "maxinstallments" => array("FriendlyName" => "Max Installments", "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9,10,11,12",),
     "installmentsrate" => array("FriendlyName" => "Installments Rate (%):", "Type" => "text", "Size" => "3", ),
     "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test", ),
    );

    return $configarray;
}

function ebanx_checkout_link($params)
{
    $integration_key = $params['integration_key'];
    $testmode = $params['testmode'];

    $invoiceid = $params['invoiceid'];
    $amount = $params['amount'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $name = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $country = $params['clientdetails']['country'];
    $systemurl = $params['systemurl'];
    $currency = $params['currency'];

    $code = '<form action="' . $params['systemurl'] . '/modules/gateways/ebanx/ebanx.php" method="post">
             <input type="hidden" id="invoiceid" name="invoiceid" value="' . $invoiceid . '" />
             <input type="hidden" id="name" name="name"  value="' . $name . '" />
             <input type="hidden" id="integration_key" name="integration_key"  value="' . $integration_key . '" />
             <input type="hidden" id="testmode" name="testmode"  value="' . $testmode . '" />
             <input type="hidden" id="country" name="country"  value="' . $country . '" />
             <input type="hidden" id="email" name="email"  value="' . $email . '" />
             <input type="hidden" id="amount" name="amount"    value="' . $amount . '" /> 
             <input type="hidden" id="currency" name="currency"    value="' . $currency . '" />  
             <input type="submit" value="' . $params['langpaynow'] . '">
             </form>';
    
    return $code;
}

function ebanx_checkout_refund($params)
{
    require( ROOTDIR."/modules/gateways/ebanx/ebanx-php/src/autoload.php");
    # Gateway Specific Variables
    $integration_key = $params['integration_key'];
    $testmode = $params['testmode'];

    \Ebanx\Config::set(array(
     'integrationKey' => $integration_key,
     'testMode'       => ($testmode == 'on') ? true : false
    ,'directMode'     => false
    ));

    # Invoice Variables
    $transid = $params['transid'];
    $amount = $params['amount']; # Format: ##.##

    $query = \Ebanx\Ebanx::doQuery(array('hash' => $transid));
    $hash = $query->payment->hash;

    $refund = \Ebanx\Ebanx::doRefund([
                'hash'        =>  $hash
               ,'operation'   => 'request'
               ,'amount'      => $amount
               ,'description' => 'Refunded by Direct API'
  ]);

    # Perform Refund Here & Generate $results Array, eg:
    $results = array();
    if($refund->status == 'SUCCESS')
    {
        $results["status"] = "success";
        $results["transid"] = $transid; 
    }

    # Return Results
    if ($results["status"]=="success")
    {
        return array("status"=>"success","transid"=>$results["transid"],"rawdata"=>$results);
    } elseif ($gatewayresult=="declined") {
        return array("status"=>"declined","rawdata"=>$results);
    } else
    {
        return array("status"=>"error","rawdata"=>$results);
    }
}

?>