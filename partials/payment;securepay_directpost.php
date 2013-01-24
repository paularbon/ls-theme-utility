<?
	/*
	 * SecurePay Direct Post payment form
	 * 
	 * That page is handling 3 steps :
	 * 		- Submits form with (hidden) fields
	 * 		- Handles POST params from hidden callback to update the Order
	 * 		- Handles GET params after gateway Redirection to display the result message
	 */


	// Redirect to Receipt page if Order is paid
	if ( $order->payment_processed() )
 		Phpr::$response->redirect( root_url($order->payment_method->receipt_page->url.'/'.$order->order_hash) );

	
	// Form field values
	$post_url = $order->payment_method->test_mode ? 
		'https://api.securepay.com.au/test/directpost/authorise' :
		'https://api.securepay.com.au/live/directpost/authorise';
	$merchant_id = $order->payment_method->merchantId;
	$transaction_password = $order->payment_method->merchantPassword;

	$reference_id = 'order#'.$order->id;
	$page_url = root_url($_SERVER['REQUEST_URI'], true);
	$timestamp = date('YmdHis');
	$fingerprint = sha1( $merchant_id.'|'.$transaction_password.'|0|'.$reference_id.'|'.$order->total.'|'.$timestamp );


	/*
	 * GET Validation
	 */
	$explode_url = explode('?', urldecode($_SERVER['REQUEST_URI']));
	if ( count($explode_url) > 1 )
	{
		parse_str(end($explode_url), $get);
		$page_url = root_url($explode_url[0], true);

		if ( count($get) && isset($get['fingerprint']) && isset($get['timestamp']) && isset($get['summarycode']) )
		{
			$check_fingerprint = sha1($merchant_id.'|'.$transaction_password.'|'.$reference_id.'|'.$order->total.'|'.$get['timestamp'].'|'.$get['summarycode']);
			
			if ( $get['fingerprint'] != $check_fingerprint )
				print Backend_Html::flash_message('Security error, fraud suspected. The order amount returned by the SecurePay Gateway may be different from the original order amount.', 'alert-box alert error');
			else
			{
				switch ( $get['summarycode'] ) {
					case '2':
						print Backend_Html::flash_message('Declined by the bank. ' . $get['restext'], 'alert-box alert error'); break;					
					case '3':
						print Backend_Html::flash_message('Payment Declined. ' . $get['restext'], 'alert-box alert error'); break;
					default:
						print Backend_Html::flash_message('Sorry there has been an error trying to update the Order. Please contact Administrator.', 'alert-box'); break;
				}
			}
		}
	}

	/*
	 * POST Validation
	 */
	if ( post('summarycode') && post('fingerprint') && post('timestamp') )
	{
		$check_fingerprint = sha1($merchant_id.'|'.$transaction_password.'|'.$reference_id.'|'.$order->total.'|'.post('timestamp').'|'.post('summarycode'));

		if ( post('summarycode') == '1' && post('fingerprint') == $check_fingerprint )
		{
			traceLog('Payment accepted. Order : '. $order->id);
			$paid_status_id = Shop_OrderStatus::get_status_paid()->id;

			Shop_OrderStatusLog::create_record($paid_status_id, $order);
			Shop_PaymentTransaction::update_transaction($order, $order->payment_method->id, $order->id, 'paid', $paid_status_id);
			$order->set_payment_processed();
		}

		exit();
	}
?>

<p>Please provide your credit card information.</p>

<form action="<?= $post_url ?>" method="post" onsubmit="return checkDirectPostForm()">

	<input type="hidden" name="EPS_MERCHANT" value="<?= $merchant_id ?>" />

	<input type="hidden" name="EPS_TXNTYPE" value="0" />
	<input type="hidden" name="EPS_REFERENCEID" value="<?= $reference_id ?>" />
	<input type="hidden" name="EPS_AMOUNT" value="<?= $order->total ?>" />
	<input type="hidden" name="EPS_TIMESTAMP" value="<?= $timestamp ?>" />	
	<input type="hidden" name="EPS_FINGERPRINT" value="<?= $fingerprint ?>" />

	<input type="hidden" name="EPS_CALLBACKURL" value="<?= $page_url ?>" />
	<input type="hidden" name="EPS_REDIRECT" value="TRUE" />
	<input type="hidden" name="EPS_RESULTURL" value="<?= $page_url ?>" />

	<ul class="form">
		<li class="field text">
			<label for="EPS_CARDNUMBER">Credit Card Number</label>
			<div><input autocomplete="off" name="EPS_CARDNUMBER" value="" id="EPS_CARDNUMBER" type="text" class="text" /></div>
		</li>
		
		<li class="field select left">
			<label for="EPS_EXPIRYMONTH">Expiration Date - Month</label>
			<select autocomplete="off" name="EPS_EXPIRYMONTH" id="EPS_EXPIRYMONTH">
				<? for ($month=1; $month <= 12; $month++): ?>
					<option value="<?= str_pad($month, 2, "0", STR_PAD_LEFT) ?>"><?= $month ?></option>
				<? endfor ?> 
			</select>
		</li>

		<li class="field text right">
			<label for="EPS_EXPIRYYEAR">Expiration Date - Year</label>

			<select autocomplete="off" name="EPS_EXPIRYYEAR" id="EPS_EXPIRYYEAR">
				<?
				  $startYear = Phpr_DateTime::now()->getYear();
				  for ($year=$startYear; $year <= $startYear + 10; $year++): ?>
					<option value="<?= $year ?>"><?= $year ?></option>
				<? endfor ?> 
			</select>
		</li>

		<li class="field text">
			<label for="EPS_CCV">
				CCV
				<span class="comment">For MasterCard, Visa, and Discover, the CSC is the last three digits in the signature area on the back of your card. For American Express, it's the four digits on the front of the card.</span>
			</label>
			
			<div><input autocomplete="off" name="EPS_CCV" value="" id="EPS_CCV" type="text" class="text" /></div>
		</li>

	</ul>
	<div class="clear"></div>
	<input type="submit" value="Submit Payment" />
</form>

<script type="text/javascript">
	function checkDirectPostForm()
	{
		var cardnumber = document.getElementById('EPS_CARDNUMBER')

		if ( cardnumber.value.length < 13 || cardnumber.value.length > 16 )
		{
			alert('Invalid Credit Card Number.')
			cardnumber.focus()
			return false
		}

		var ccv = document.getElementById('EPS_CCV')

		if ( ccv.value.length < 3 || ccv.value.length > 4 )
		{
			alert('Invalid CCV Number.')
			ccv.focus()
			return false
		}

		return true
	}
</script>