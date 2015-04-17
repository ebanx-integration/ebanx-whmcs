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
require( ROOTDIR."/includes/functions.php" );
require( ROOTDIR."/includes/gatewayfunctions.php" );
require( ROOTDIR."/includes/invoicefunctions.php" );
require( ROOTDIR."/includes/clientfunctions.php");
require( ROOTDIR."/modules/gateways/ebanx/ebanx-php/src/autoload.php");

\Ebanx\Config::set(array(
     'integrationKey' => $_REQUEST['integration_key'],
     'testMode'       => ($_REQUEST['testmode'] == 'on') ? true : false
    ,'directMode'     => false
));

$country = $_REQUEST['country'];
$redirect =  explode('?', $_REQUEST['url']);

if($country == 'BR' || $country == 'PE' || $country == 'MX')
{
	$response;
	$params = array(
		 	'name'                   =>  $_REQUEST['name']
		   ,'email'                  =>  $_REQUEST['email']
		   ,'country'                =>  $_REQUEST['country']
		   ,'payment_type_code'      => '_all'
		   ,'merchant_payment_code'  => time()
		   ,'amount'                 => $_REQUEST['amount']
		   ,'currency_code'          => $_REQUEST['currency']
		   ,'order_number'           => $_REQUEST['invoiceid']
	);

    	$response = \Ebanx\Ebanx::doRequest($params);

    	if($response->status == "ERROR")
    	{
    		redirect($response->status_message , $redirect);
    	}
    	else
    	{
    		header("Location: $response->redirect_url");
    		exit;
    	}
}
else
{
	$message = 'Payment not enabled in your country!';
	redirect($message,$redirect);
}

function redirect($message, $redirect)
{
	die('<html>
		 <body>

		 <script type="text/javascript">
			var count = 6;
			var redirect = "' . 'http://' . $redirect[0] . '";
 
			function countDown(){
    			var timer = document.getElementById("timer");
    			if(count > 0){
        			count--;
        			timer.innerHTML = "<p>This page will redirect in "+count+" seconds.</p>";
        			setTimeout("countDown()", 1000);
    			}else{
        			window.location = redirect;
    			}
			}
		</script>
		<br>
		<div  align="center">' . 
			$message . '
			<span id="timer">
			</span>
		</div>

		<script type="text/javascript">countDown();</script>
		</body>
		</html>
	');
}