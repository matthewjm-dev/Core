<?php // PayPal Instant Payment Notification

use PayPal\Api\Webhook;
use \PayPal\Api\WebhookEvent;
use PayPal\Api\WebhookEventType;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use \PayPal\Api\VerifyWebhookSignature;

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

    protected $debug = false;
    protected $return_redirect = false;

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

    public function set_debug($set = true) {
        $this->debug = $set;
    }

    public function set_sandbox($set = true) {
        $this->sandbox = $set;
    }

    public function set_return_redirect($set = true) {
        $this->return_redirect = $set;
    }

    public function __construct($args)
    {
        if (isset($args['debug']) && $args['debug'] == true) {
            $this->set_debug();
        }

        if (isset($args['return_redirect'])) {
            $this->set_return_redirect($args['return_redirect']);
        }

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
        } else {
            ipsCore::add_error('Paypal Client ID or Secret missing', true);
        }
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#connect-with-paypal
    public function webhook_connected() {
        return $this->setup_webhook([
            'IDENTITY.AUTHORIZATION-CONSENT.REVOKED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#payment-orders
    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#orders
    public function webhook_order() {
        return $this->setup_webhook([
            'PAYMENT.ORDER.CANCELLED',
            'PAYMENT.ORDER.CREATED',
            'CHECKOUT.ORDER.COMPLETED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#checkout-buyer-approval
    public function webhook_checkout_buyer_approval() {
        return $this->setup_webhook([
            'PAYMENTS.PAYMENT.CREATED',
            'CHECKOUT.ORDER.APPROVED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#authorized-and-captured-payments
    public function webhook_payment() {
        return $this->setup_webhook([
            'PAYMENT.AUTHORIZATION.CREATED',
            'PAYMENT.AUTHORIZATION.VOIDED',
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.PENDING',
            'PAYMENT.CAPTURE.REFUNDED',
            'PAYMENT.CAPTURE.REVERSED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#sales
    public function webhook_sale() {
        return $this->setup_webhook([
            'PAYMENT.SALE.COMPLETED',
            'PAYMENT.SALE.DENIED',
            'PAYMENT.SALE.PENDING',
            'PAYMENT.SALE.REFUNDED',
            'PAYMENT.SALE.REVERSED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#batch-payouts
    public function webhook_batch() {
        return $this->setup_webhook([
            'PAYMENT.PAYOUTSBATCH.DENIED',
            'PAYMENT.PAYOUTSBATCH.PROCESSING',
            'PAYMENT.PAYOUTSBATCH.SUCCESS',
            'PAYMENT.PAYOUTS-ITEM.BLOCKED',
            'PAYMENT.PAYOUTS-ITEM.CANCELED',
            'PAYMENT.PAYOUTS-ITEM.DENIED',
            'PAYMENT.PAYOUTS-ITEM.FAILED',
            'PAYMENT.PAYOUTS-ITEM.HELD',
            'PAYMENT.PAYOUTS-ITEM.REFUNDED',
            'PAYMENT.PAYOUTS-ITEM.RETURNED',
            'PAYMENT.PAYOUTS-ITEM.SUCCEEDED',
            'PAYMENT.PAYOUTS-ITEM.UNCLAIMED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#billing-plans-and-agreements
    public function webhook_billing() {
        return $this->setup_webhook([
            'BILLING_AGREEMENTS.AGREEMENT.CREATED',
            'BILLING_AGREEMENTS.AGREEMENT.CANCELLED',
            'BILLING.PLAN.CREATED',
            'BILLING.PLAN.UPDATED',
            'BILLING.SUBSCRIPTION.CANCELLED',
            'BILLING.SUBSCRIPTION.CREATED',
            'BILLING.SUBSCRIPTION.RE-ACTIVATED',
            'BILLING.SUBSCRIPTION.SUSPENDED',
            'BILLING.SUBSCRIPTION.UPDATED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#invoicing
    public function webhook_invoice() {
        return $this->setup_webhook([
            'INVOICING.INVOICE.CANCELLED',
            'INVOICING.INVOICE.CREATED',
            'INVOICING.INVOICE.PAID',
            'INVOICING.INVOICE.REFUNDED',
            'INVOICING.INVOICE.SCHEDULED',
            'INVOICING.INVOICE.UPDATED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#disputes
    public function webhook_dispute() {
        return $this->setup_webhook([
            'CUSTOMER.DISPUTE.CREATED',
            'CUSTOMER.DISPUTE.RESOLVED',
            'CUSTOMER.DISPUTE.UPDATED',
            'RISK.DISPUTE.CREATED',
        ]);
    }

    // SOURCE: https://developer.paypal.com/docs/integration/direct/webhooks/event-names/#merchant-onboarding
    public function webhook_merchant() {
        return $this->setup_webhook([
            'MERCHANT.ONBOARDING.COMPLETED',
            'MERCHANT.PARTNER-CONSENT.REVOKED',
        ]);
    }

    private function setup_webhook($hooks = []) {
        $output = false;

        $this->webhook = new Webhook();

        $this->webhook->setUrl($this->url_notify);

        $events = [];
        foreach ($hooks as $hook) {
            $events[] = new WebhookEventType('{"name":"' . $hook . '"}');
        }

        $this->webhook->setEventTypes($events);

        if (!$this->debug) {
            try {
                $output = $this->webhook->create($this->api_context);
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                if ($ex->getData() !== null) {
                    $error = json_decode($ex->getData());
                    if ($error->name == 'WEBHOOK_URL_ALREADY_EXISTS') {
                        $output = $error;
                    } else {
                        ipsCore::add_error($error->message, true);
                    }
                } else {
                    ipsCore::add_error($ex->getMessage(), true);
                }
            } catch (Exception $ex) {
                ipsCore::add_error($ex, true);
            }
        }

        if ($output) {
            return $output;
        }
        return false;
    }

    public function redirect_to_paypal($url = false) {
        if ($this->return_redirect) {
            return true;
        } else {
            header('Location: ' . $url);
            exit();
            return false;
        }
    }

    public function setup_payment($args, &$errors = []) {
        $this->webhook_payment();

        $args = array_merge([
            'description' => false,
            'amount_total' => false,
        ], $args);

        if (!$args['description']) {
            ipsCore::add_error('Payment requires a description (setup_payment)', true);
        }

        if (!$args['total']) {
            ipsCore::add_error('A Payment requires a Total (setup_payment)', true);
        } elseif (!is_number($args['total'])) {
            ipsCore::add_error('Payment Total must be a number (setup_payment)', true);
        }

        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // Set redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->url_return)->setCancelUrl($this->url_cancel);

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($args['total']);

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setDescription($args['description']);

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);

        if (!$this->debug) {
            // Create payment with valid API context
            try {
                $payment->create($this->api_context);

                // Get PayPal redirect URL and redirect the customer
                $approval_url = $payment->getApprovalLink();

                // Redirect to PayPal
                if ($this->redirect_to_paypal($approval_url)) {
                    return $approval_url;
                }
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                $errors['paypal_exception_code'] = $ex->getCode();
                $errors['paypal_exception_data'] = $ex->getData();
            } catch (Exception $ex) {
                $errors['exception'] = $ex;
            }
        }

        return true;
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

        if (!$this->debug) {
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

        return true;
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

        if (!$this->debug) {
            // Create payment with valid API context
            try {
                $payment->create($this->api_context);

                // Get paypal redirect URL and redirect user
                $approval_url = $payment->getApprovalLink();

                // Redirect to PayPal
                if ($this->redirect_to_paypal($approval_url)) {
                    return $approval_url;
                }
            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                echo $ex->getCode();
                echo $ex->getData();
                ipsCore::add_error($ex, true);
            } catch (Exception $ex) {
                ipsCore::add_error($ex, true);
            }
        }

        return true;
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
        } elseif (!is_numeric($amount_total)) {
            ipsCore::add_error('Order Amount Total must be a number (execute_order)', true);
        }

        // Get payment object by passing paymentId
        $payment = Payment::get($payment_id, $this->api_context);

        // Execute payment with payer id
        $execution = new PaymentExecution();
        $execution->setPayerId($payer_id);

        if (!$this->debug) {
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

        return true;
    }

    public function capture_order($order = false, $amount_total = false) {
        if (!$order) {
            ipsCore::add_error('Order object is required (capture_order)', true);
        }

        if (!$amount_total) {
            ipsCore::add_error('Order Amount Total is required (capture_order)', true);
        } elseif (!is_numeric($amount_total)) {
            ipsCore::add_error('Order Amount Total must be a number (setup_billing_plan)', true);
        }

        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($amount_total);

        // Set capture details
        $captureDetails = new Authorization();
        $captureDetails->setAmount($amount);

        if (!$this->debug) {
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

        return true;
    }

    public function setup_billing_plan($args, &$errors = []) {
        $this->webhook_billing();

        $args = array_merge([
            'title' => false,
            'description' => false,
            'type' => 'INFINITE', // Options: FIXED, INFINITE
            'payment_type' => 'REGULAR', // Options: REGULAR
            'payment_title' => false, // e.g. "Regular Payments"
            'payment_frequency' => false, // Options: DAY, MONTH, YEAR
            'payment_interval' => false,
            'payment_cycles' => false,
            'amount_total' => false,
            'amount_shipping' => false,
            'amount_setupfee' => false,
            'merchant_autobill' => 'yes', // Options: yes, no
            'merchant_failaction' => 'CONTINUE', // Options: CONTINUE
            'merchant_failattempts' => 0,
        ], $args);

        if (!$args['title']) {
            $error = 'Billing setup requires a Title (setup_billing_plan)';
            $errors[] = $error;
        }

        if (!$args['description']) {
            $error = 'Billing setup requires a Description (setup_billing_plan)';
            $errors[] = $error;
        }

        if (!$args['payment_title']) {
            $error = 'Billing setup requires a Payment Title (setup_billing_plan)';
            $errors[] = $error;
        }

        if (!$args['payment_frequency']) {
            $error = 'Billing setup requires a Frequency (setup_billing_plan)';
            $errors[] = $error;
        }

        if ($args['payment_interval'] === false) {
            $error = 'Billing setup requires an Interval (setup_billing_plan)';
            $errors[] = $error;
        } elseif (!is_numeric($args['payment_interval'])) {
            $error = 'Billing setup Interval must be a number (setup_billing_plan)';
            $errors[] = $error;
        }

        if ($args['payment_cycles'] === false) {
            $error = 'Billing setup requires a Cycle (setup_billing_plan)';
            $errors[] = $error;
        } elseif (!is_numeric($args['payment_cycles'])) {
            $error = 'Billing setup Cycle must be a number (setup_billing_plan)';
            $errors[] = $error;
        }

        if ($args['amount_total'] === false) {
            $error = 'Billing setup requires a Amount Total (setup_billing_plan)';
            $errors[] = $error;
        } elseif (!is_numeric($args['amount_total'])) {
            $error = 'Billing setup Amount Total must be a number (setup_billing_plan)';
            $errors[] = $error;
        }

        if ($args['amount_setupfee'] !== false && !is_numeric($args['amount_total'])) {
            $error = 'Billing setup Fee must be a number (setup_billing_plan)';
            $errors[] = $error;
        }

        // Create a new billing plan
        $plan = new Plan();
        $plan->setName($args['title'])
            ->setDescription($args['description'])
            ->setType($args['type']);

        // Set billing plan definitions
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName($args['payment_title'])
            ->setType($args['payment_type'])
            ->setFrequency($args['payment_frequency'])
            ->setFrequencyInterval($args['payment_interval'])
            ->setAmount(new Currency(['value' => $args['amount_total'], 'currency' => $this->currency]));

        if ($args['payment_cycles']) {
            $paymentDefinition->setCycles($args['payment_cycles']);
        }

        if ($args['amount_shipping']) {
            // Set charge models
            $chargeModel = new ChargeModel();
            $chargeModel->setType('SHIPPING')
                ->setAmount(new Currency([
                    'value' => $args['amount_shipping'],
                    'currency' => $this->currency
                ]));
            $paymentDefinition->setChargeModels([$chargeModel]);
        }

        // Set merchant preferences
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl($this->url_return)
            ->setCancelUrl($this->url_cancel)
            ->setAutoBillAmount($args['merchant_autobill'])
            ->setInitialFailAmountAction($args['merchant_failaction'])
            ->setMaxFailAttempts($args['merchant_failattempts']);

        if ($args['amount_setupfee']) {
            $merchantPreferences->setSetupFee(new Currency([
                'value' => $args['amount_setupfee'],
                'currency' => $this->currency
            ]));
        }

        $plan->setPaymentDefinitions([$paymentDefinition]);
        $plan->setMerchantPreferences($merchantPreferences);

        if (empty($errors)) {
            if (!$this->debug) {
                // Create plan
                try {
                    $createdPlan = $plan->create($this->api_context);

                    // Activate plan
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
                        return $plan->getId();

                    } catch (PayPal\Exception\PayPalConnectionException $ex) {
                        $errors[] = 'PayPal Activate plan Exception Code: ' . $ex->getCode();
                        $errors[] = 'PayPal Activate plan Exception Data: ' . json_encode($ex->getData());
                        return false;
                    } catch (Exception $ex) {
                        $errors[] = 'Exception in Activate plan Error: ' . json_encode($ex);
                        return false;
                    }
                } catch (PayPal\Exception\PayPalConnectionException $ex) {
                    $errors[] = 'PayPal Create plan Exception Code: ' . $ex->getCode();
                    $errors[] = 'PayPal Create plan Exception Data: ' . json_encode($ex->getData());
                    return false;
                } catch (Exception $ex) {
                    $errors[] = 'Exception in Create plan Error: ' . json_encode($ex);
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    public function create_plan_agreement($args, &$errors = []) {
        $args = array_merge([
            'plan_id' => false,
            'start_time' => false, // Start at this time (ISO 8601 format)
            'start_in' => false, // Start In this amount of time (in seconds) from now
            'title' => false,
            'description' => false,
            'shipping_address' => false,
        ], $args);

        if (!isset($args['plan_id'])) {
            $error = 'Plan ID is required';
            $errors[] = $error;
        }

        if (!isset($args['title'])) {
            $error = 'Title is required';
            $errors[] = $error;
        }

        if (!$args['start_time']) {
            if (!$args['start_in']) {
                $args['start_time'] = date(DATE_ISO8601, time() + 300); // Default to 5 minutes from now
            } else {
                $args['start_time'] = date(DATE_ISO8601, time() + $args['start_in']);
            }
        }

        // Create new agreement
        $agreement = new Agreement();
        $agreement->setName($args['title'])
            ->setStartDate($args['start_time']);

        if ($args['description']) {
            $agreement->setDescription($args['description']);
        }

        // Set plan id
        $plan = new Plan();
        $plan->setId($args['plan_id']);
        $agreement->setPlan($plan);

        // Add payer type
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        if ($args['shipping_address']) { // Adding shipping details
            $shippingAddress = new ShippingAddress();
            $shippingAddress->setLine1($args['shipping_address']['line1'])
                ->setCity($args['shipping_address']['city'])
                ->setState($args['shipping_address']['state'])
                ->setPostalCode($args['shipping_address']['postcode'])
                ->setCountryCode($args['shipping_address']['countrycode']);
            $agreement->setShippingAddress($shippingAddress);
        }

        if (empty($errors)) {
            if (!$this->debug) {
                try {
                    // Create agreement
                    $agreement = $agreement->create($this->api_context);

                    // Extract approval URL to redirect user
                    $approval_url = $agreement->getApprovalLink();

                    // Redirect to PayPal
                    if ($this->redirect_to_paypal($approval_url)) {
                        return $approval_url;
                    }
                } catch (PayPal\Exception\PayPalConnectionException $ex) {
                    $errors['paypal_exception_code'] = $ex->getCode();
                    $errors['paypal_exception_data'] = $ex->getData();
                    return false;
                } catch (Exception $ex) {
                    $errors['exception'] = $ex;
                    return false;
                }
            } else {
                return $this->url_return;
            }
        }

        return false;
    }

    public function activate_plan_agreement($token, &$errors = []) {
        $agreement = new \PayPal\Api\Agreement();

        try {
            // Execute agreement
            if ($agreement = $agreement->execute($token, $this->api_context)) {
                return $agreement;
            } else {
                $errors[] = 'Failed to set agreement';
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            $errors['paypal_exception_code'] = $ex->getCode();
            $errors['paypal_exception_data'] = $ex->getData();
        } catch (Exception $ex) {
            $errors['exception'] = $ex;
        }

        return false;
    }

    public function update_billing() {

    }

    public function cancel_billing() {

    }

    public function suspend_billing() {

    }

    public function create_product() {
        /*$product = new Product();


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/catalogs/products');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"name\": \"Video Streaming Service\",\n  \"description\": \"Video streaming service\",\n  \"type\": \"SERVICE\",\n  \"category\": \"SOFTWARE\",\n  \"image_url\": \"https://example.com/streaming.jpg\",\n  \"home_url\": \"https://example.com/home\"\n}");
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer Access-Token';
        $headers[] = 'Paypal-Request-Id: PRODUCT-18062019-001';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);*/

        return 'prod-t35t';
    }

    public function update_product() {

    }

    public function get_webhooks(&$errors = []) {
        $output = false;

        try {
            $output = PayPal\Api\Webhook::getAll($this->api_context)->webhooks;
        } catch (Exception $ex) {
            $errors[] = "Retrieve webhook list failed: " . json_encode($ex);
        }

        return $output;
    }

    public function get_webhook_from_url($url, &$errors = []) {
        if ($webhooks = $this->get_webhooks($errors)) {
            if (!empty($webhooks)) {
                foreach ($webhooks as $webhook) {
                    $url_parts = parse_url($webhook->url);
                    $url_check = (isset($url_parts['path']) ? $url_parts['path'] : '') . (isset($url_parts['query']) ? $url_parts['query'] : '');
                    if ($url == $url_check) {
                        return $webhook;
                    }
                }
            }
        }

        return false;
    }

    public function verify_response($webhook_id, &$errors = [], $body = false, $headers = false) {
        $output = false;

        /**
         * Receive the entire body received from PayPal webhook.
         */
        /** @var String $bodyReceived */
        if ($body) {
            $requestBody = $body;
        } else {
            $requestBody = file_get_contents('php://input');
        }

        if (!empty($requestBody)) {

            /**
             * Receive HTTP headers received from PayPal webhook.
             * In Documentions https://developer.paypal.com/docs/api/webhooks/#verify-webhook-signature_post
             * All header keys as UPPERCASE, but recieve the header key as the example array, First letter as UPPERCASE
             */
            /** @var Array $headers */
            if (!$headers) {
                $headers = array_change_key_case(getallheaders(), CASE_UPPER);
            }

            if ($headers && !empty($headers)) {
                foreach ($headers as $key => $header) {
                    if (is_array($header)) {
                        $headers[$key] = $header[0];
                    }
                }

                $headers_test = ['PAYPAL-AUTH-ALGO', 'PAYPAL-TRANSMISSION-ID', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-SIG', 'PAYPAL-TRANSMISSION-TIME'];

                foreach ($headers_test as $header_test) {
                    if (!isset($headers[$header_test])) {
                        $errors[] = 'Header not found: ' . $header_test;
                    }
                }

                if (empty($errors)) {
                    $signatureVerification = new VerifyWebhookSignature();
                    $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
                    $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
                    $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
                    $signatureVerification->setWebhookId($webhook_id); // Note that the Webhook ID must be a currently valid Webhook that you created with your client ID/secret.
                    $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
                    $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);

                    $signatureVerification->setRequestBody($requestBody);
                    $request = clone $signatureVerification;

                    try {
                        /** @var \PayPal\Api\VerifyWebhookSignatureResponse $output */
                        $output = $signatureVerification->post($this->api_context);
                    } catch (Exception $ex) {
                        $errors[] = 'Validate Received Webhook Event' . "\r\n\r\n" . 'Request JSON:' . "\r\n" . $request->toJSON() . "\r\n\r\n" . 'ex:' . "\r\n" . json_encode($ex);
                    }
                }
            } else {
                $errors[] = 'Didnt catch PayPal Headers: ' . "\r\n" . json_encode($headers);
                return false;
            }
        } else {
            $errors[] = 'Request was empty';
            return false;
        }

        if ($output !== false) {
        	return $output->getVerificationStatus();
		}

        //$errors[] = 'Error: Validate Received Webhook Event' . "\r\n\r\n" . 'Request JSON:' . "\r\n" . $request->toJSON() . "\r\n\r\n" . 'Status:' . "\r\n" . $output->getVerificationStatus() . "\r\n\r\n" . 'output:' . "\r\n" . json_encode($output);
        return $output;
    }

}
