<p>Click 'Pay with Card' to complete payment.</p>
<?php
    $payment_method = $order->payment_method;
    $payment_method->define_form_fields();
?>
<?= open_form(array('id'=>'card-checkout-payment')) ?>
    <?= flash_message() ?>
    <script 
        data-key="<?= $payment_method->api_key ?>"
        data-amount="<?= $order->total*100 ?>"
        data-name="Order #<?= $order->id ?>"
        data-image = "<?= Tdstripecheckout_Checkout_Payment::data_url($payment_method) ?>"
        src="<?= Tdstripecheckout_Checkout_Payment::STRIPE_JS ?>" 
        class="stripe-button"
    ></script>
    <?php
    if ( isset($_POST['stripeToken']) ):
    ?>
        <input type="hidden" name="stripeToken" value="<?= preg_replace("/[^A-Za-z0-9_]/", '', $_POST['stripeToken']) ?>"/>
        <script>
            $(document).ready(function(){
                $('#card-checkout-payment').getForm().sendRequest('shop:on_pay');
            });
        </script>
    <?php
    endif;
    ?>
</form>