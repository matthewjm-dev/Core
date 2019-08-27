<?php // PayPal Instant Payment Notification

use PayPal\Api\Webhook;
use PayPal\Api\WebhookEventType;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Capture;

use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;

use PayPal\Api\Agreement;
use PayPal\Api\ShippingAddress;

/*
 * TODO: all webhook URLs MUST be https
 */

class ipsCore_paypal
{
    protected $sandbox = false; // Indicates if the sandbox endpoint is used.
    protected $currency = 'GBP';

    protected $client_id = false;
    protected $client_secret = false;

    protected $url_return;
    protected $url_cancel;
    protected $url_notify;
    
    protected $url_request_live = 'https://www.paypal.com/cgi-bin/webscr';
    protected $url_request_sandbox = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    protected $api_auth;
    protected $api_context;

    protected $url_webhook;
    protected $webhook;

    public function __construct($args)
    {
        if (isset($args['urls'])) {
            if (!$args['urls']) {
                $args['urls'] = [
                    'return' => ipsCore::$app->get_uri_slashed() . '/paypal/response_return/',
                    'cancel' => ipsCore::$app->get_uri_slashed() . '/paypal/response_cancel/',
                    'notify' => ipsCore::$app->get_uri_slashed() . '/paypal/response_notify/',
                ];
            }

            if (isset($args['urls']['return'])) {
                $this->url_return = $args['urls']['return'];
            } else {
                ipsCore::add_error('Paypal Return URL is required', true);
            }

            if (isset($args['urls']['cancel'])) {
                $this->url_cancel = $args['urls']['cancel'];
            } else {
                ipsCore::add_error('Paypal Cancel URL is required', true);
            }

            if (isset($args['urls']['notify'])) {
                $this->url_notify = $args['urls']['notify'];
            } else {
                ipsCore::add_error('Paypal Notify URL is required', true);
            }
        }

        if (isset($args['currency'])) {
            $this->currency = $args['currency'];
        }

        if (isset($args['client_id'])) {
            $this->client_id = $args['client_id'];
        }

        if (isset($args['client_secret'])) {
            $this->client_secret = $args['client_secret'];
        }

        if ($this->client_id && $this->client_secret) {

            $this->api_auth = new OAuthTokenCredential($this->client_id, $this->client_secret);

            $this->api_context = new ApiContext($this->api_auth);

            $this->setup_webhook();
        } else {
            ipsCore::add_error('Paypal Client ID or Secret missing', true);
        }
    }

    public function setup_webhook() {
        $this->webhook = new Webhook();

        $this->webhook->setUrl($this->url_notify);

        // Set webhooks to subscribe to
        $event_types = [];

        $event_types[] = new WebhookEventType('{
            "name":"PAYMENT.SALE.COMPLETED"
        }');

        $event_types[] = new WebhookEventType('{
            "name":"PAYMENT.SALE.DENIED"
        }');

        $this->webhook->setEventTypes($event_types);

        /*try {
            $output = $this->webhook->create($this->api_context);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            if ($ex->getData() !== null) {
                $error = json_decode($ex->getData());
                if ($error->name != 'WEBHOOK_URL_ALREADY_EXISTS') {
                    ipsCore::add_error($error->message, true);
                }
            } else {
                ipsCore::add_error($ex->getMessage(), true);
            }
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }*/

        return true;
    }

    public function redirect_to_paypal($url) {
        header('Location: ' . $url);
        exit();
    }

    public function setup_payment($amount = false, $description = false, $amount_total) {
        if (!$amount) {
            ipsCore::add_error('Payment requires an amount (setup_payment)', true);
        }

        if (!$description) {
            ipsCore::add_error('Payment requires a description (setup_payment)', true);
        }

        if (!$amount_total) {
            ipsCore::add_error('A Payment requires an Amount Total (setup_payment)', true);
        } elseif (!is_number($amount_total)) {
            ipsCore::add_error('Payment Amount Total must be a number (setup_payment)', true);
        }

        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // Set redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->url_return)->setCancelUrl($this->url_cancel);

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($amount_total);

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setDescription($description);

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);

        // Create payment with valid API context
        try {
            $payment->create($this->api_context);

            // Get PayPal redirect URL and redirect the customer
            $approval_url = $payment->getApprovalLink();

            // Redirect to PayPal
            $this->redirect_to_paypal($approval_url);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function execute_payment($payment_id = false, $payer_id = false) {
        if (!$payment_id) {
            ipsCore::add_error('Payment ID is required (execute_payment)', true);
        }

        if (!$payer_id) {
            ipsCore::add_error('Payer ID is required (execute_payment)', true);
        }

        // Get payment object by passing paymentId
        $payment = Payment::get($payment_id, $this->api_context);

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payer_id);

        try {
            // Execute payment
            $result = $payment->execute($execution, $this->api_context);
            return($result);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function setup_order(array $items = [], $amount_total = false, $amount_shipping = false, $tax_rate = false, $amount_subtotal = false) {
        if (empty($items)) {
            ipsCore::add_error('An order requires items (setup_order)', true);
        }

        if (!$amount_total) {
            ipsCore::add_error('An order requires an Amount Total (setup_order)', true);
        } elseif (!is_number($amount_total)) {
            ipsCore::add_error('Order Amount Total must be a number (setup_order)', true);
        }

        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // Set redirect urls
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->url_return)->setCancelUrl($this->url_cancel);

        // Set item list
        $items_array = [];

        foreach ($items as $item) {
            $temp_item = new Item();
            $temp_item->setName($item['title'])
                ->setCurrency($this->currency)
                ->setQuantity($item['quantity'])
                ->setPrice($item['price']);

            $items_array[] = $temp_item;
        }

        $itemList = new ItemList();
        $itemList->setItems($items_array);

        // Set payment details
        $details = new Details();

        if ($amount_shipping) {
            $details->setShipping($amount_shipping);
        }

        if ($tax_rate) {
            $details->setTax($tax_rate);
        }

        if ($amount_subtotal) {
            $details->setSubtotal($amount_subtotal);
        }

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($amount_total)->setDetails($details);

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription("Payment description")->setInvoiceNumber(uniqid());

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent("order")->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);

        // Create payment with valid API context
        try {
            $payment->create($this->api_context);

            // Get paypal redirect URL and redirect user
            $approval_url = $payment->getApprovalLink();

            // Redirect to PayPal
            $this->redirect_to_paypal($approval_url);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function execute_order($payment_id = false, $payer_id = false, $amount_total = false) {
        if (!$payment_id) {
            ipsCore::add_error('Order Payment ID is required (execute_order)', true);
        }

        if (!$payer_id) {
            ipsCore::add_error('Order Payer ID is required (execute_order)', true);
        }

        if (!$amount_total) {
            ipsCore::add_error('Order Amount Total is required (execute_order)', true);
        } elseif (!is_number($amount_total)) {
            ipsCore::add_error('Order Amount Total must be a number (execute_order)', true);
        }

        // Get payment object by passing paymentId
        $payment = Payment::get($payment_id, $this->api_context);

        // Execute payment with payer id
        $execution = new PaymentExecution();
        $execution->setPayerId($payer_id);

        try {
            // Execute payment
            $result = $payment->execute($execution, $this->api_context);

            // Extract order
            $order = $payment->transactions[0]->related_resources[0]->order;

            $this->capture_order($order, $amount_total);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function capture_order($order = false, $amount_total = false) {
        if (!$order) {
            ipsCore::add_error('Order object is required (capture_order)', true);
        }

        if (!$amount_total) {
            ipsCore::add_error('Order Amount Total is required (capture_order)', true);
        } elseif (!is_number($amount_total)) {
            ipsCore::add_error('Order Amount Total must be a number (setup_billing)', true);
        }

        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($amount_total);

        // Set capture details
        $captureDetails = new Authorization();
        $captureDetails->setAmount($amount);

        try {
            $result = $order->capture($captureDetails, $this->api_context);
            print_r($result);
            // TODO: Do something here
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function setup_billing($args) {
        $args = array_merge([
            'title' => false,
            'description' => false,
            'payment_title' => false,
            'frequency' => false,
            'interval' => false,
            'cycles' => false,
            'amount_total' => false,
            'amount_shipping' => false,
            'amount_setupfee' => false
        ], $args);

        if (!$args['title']) {
            ipsCore::add_error('Billing setup requires a Title (setup_billing)', true);
        }

        if (!$args['description']) {
            ipsCore::add_error('Billing setup requires a Description (setup_billing)', true);
        }

        if (!$args['payment_title']) { // e.g. "Regular Payments"
            ipsCore::add_error('Billing setup requires a Payment Title (setup_billing)', true);
        }

        if (!$args['frequency']) { // e.g. "MONTH"
            ipsCore::add_error('Billing setup requires a Frequency (setup_billing)', true);
        }

        if (!$args['interval']) {
            ipsCore::add_error('Billing setup requires an Interval (setup_billing)', true);
        } elseif (!is_number($args['interval'])) {
            ipsCore::add_error('Billing setup Interval must be a number (setup_billing)', true);
        }

        if ($args['cycles'] && !is_number($args['cycles'])) {
            ipsCore::add_error('Billing setup Cycle must be a number (setup_billing)', true);
        }

        if (!$args['amount_total']) {
            ipsCore::add_error('Billing setup requires a Amount Total (setup_billing)', true);
        } elseif (!is_number($args['amount_total'])) {
            ipsCore::add_error('Billing setup Amount Total must be a number (setup_billing)', true);
        }

        // Create a new billing plan
        $plan = new Plan();
        $plan->setName($args['title'])->setDescription($args['description'])->setType('fixed');

        // Set billing plan definitions
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName($args['payment_title'])
            ->setType('REGULAR')
            ->setFrequency($args['frequency'])
            ->setFrequencyInterval($args['interval'])
            ->setAmount(new Currency(['value' => $args['amount_total'], 'currency' => $this->currency]));

        if ($args['cycles']) {
            $paymentDefinition->setCycles($args['cycles']);
        }

        if ($args['amount_shipping']) {
            // Set charge models
            $chargeModel = new ChargeModel();
            $chargeModel->setType('SHIPPING')->setAmount(new Currency(['value' => $args['amount_shipping'], 'currency' => $this->currency]));
            $paymentDefinition->setChargeModels([$chargeModel]);
        }

        // Set merchant preferences
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl($this->url_return)->setCancelUrl($this->url_cancel)
            ->setAutoBillAmount('yes')->setInitialFailAmountAction('CONTINUE')->setMaxFailAttempts('0');

        if ($args['amount_setupfee']) {
            $merchantPreferences->setSetupFee(new Currency(['value' => $args['amount_setupfee'], 'currency' => $this->currency]));
        }

        $plan->setPaymentDefinitions([$paymentDefinition]);
        $plan->setMerchantPreferences($merchantPreferences);

        //create plan
        try {
            $createdPlan = $plan->create($this->api_context);

            try {
                $patch = new Patch();
                $value = new PayPalModel('{"state":"ACTIVE"}');
                $patch->setOp('replace')
                    ->setPath('/')
                    ->setValue($value);
                $patchRequest = new PatchRequest();
                $patchRequest->addPatch($patch);
                $createdPlan->update($patchRequest, $this->api_context);
                $plan = Plan::get($createdPlan->getId(), $this->api_context);

                // Output plan id
                echo $plan->getId();


            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                echo $ex->getCode();
                echo $ex->getData();
                ipsCore::add_error($ex, true);
            } catch (Exception $ex) {
                ipsCore::add_error($ex, true);
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            ipsCore::add_error($ex, true);
        } catch (Exception $ex) {
            ipsCore::add_error($ex, true);
        }
    }

    public function execute_billing() { // TODO: This function needs work
        // Create new agreement
        $agreement = new Agreement();
        $agreement->setName('Base Agreement')
            ->setDescription('Basic Agreement')
            ->setStartDate('2019-06-17T9:45:04Z');

        // Set plan id
        $plan = new Plan();
        $plan->setId('P-1WJ68935LL406420PUTENA2I');
        $agreement->setPlan($plan);

        // Add payer type
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        // Adding shipping details
        $shippingAddress = new ShippingAddress();
        $shippingAddress->setLine1('111 First Street')
            ->setCity('Saratoga')
            ->setState('CA')
            ->setPostalCode('95070')
            ->setCountryCode('US');
        $agreement->setShippingAddress($shippingAddress);

        try {
            // Create agreement
            $agreement = $agreement->create($this->api_context);

            // Extract approval URL to redirect user
            $approval_url = $agreement->getApprovalLink();

            // Redirect to PayPal
            $this->redirect_to_paypal($approval_url);
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
    }

}
