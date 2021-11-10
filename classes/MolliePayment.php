<?php
namespace Qteco\MallMolliePayments\Classes;

use OFFLINE\Mall\Classes\Payments\PaymentProvider;
use OFFLINE\Mall\Classes\Payments\PaymentResult;
use OFFLINE\Mall\Models\PaymentGatewaySettings;
use OFFLINE\Mall\Models\Order;
use Throwable;
use Session;
use Log;
use Event;

class MolliePayment extends PaymentProvider
{
    /**
     * {@inheritdoc}
     */
    public $order;

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return "Mollie";
    }

    /**
     * {@inheritdoc}
     */
    public function identifier(): string
    {
        return "mollie";
    }

    /**
     * {@inheritdoc}
     */
    public function settings(): array
    {
        return [
            "mollie_mode" => [
                "label" => "qteco.mallmolliepayments::lang.settings.mollie_mode",
                "default" => "test",
                "comment" => "qteco.mallmolliepayments::lang.settings.mollie_mode_label",
                "span" => "left",
                "type" => "dropdown",
                "options" => [
                    "test" => "Test",
                    "live" => "Live",
                ],
            ],
            "test_api_key" => [
                "label" => "qteco.mallmolliepayments::lang.settings.test_api_key",
                "comment" => "qteco.mallmolliepayments::lang.settings.test_api_key_label",
                "span" => "left",
                "type" => "text",
            ],
            "live_api_key" => [
                "label" => "qteco.mallmolliepayments::lang.settings.live_api_key",
                "comment" => "qteco.mallmolliepayments::lang.settings.live_api_key_label",
                "span" => "left",
                "type" => "text",
            ],
            "orders_page" => [
                "label" => "qteco.mallmolliepayments::lang.settings.orders_page",
                "comment" => "qteco.mallmolliepayments::lang.settings.orders_page_label",
                "span" => "left",
                "type" => "text",
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function encryptedSettings(): array
    {
        return ["test_api_key", "live_api_key"];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PaymentResult $result): PaymentResult
    {
        $payment = null;

        $webhookUrl = (!empty($_SERVER["HTTPS"]) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . "/";
        $webhookUrl .= "oc-mall-molliepayments-checkout";

        $activePaymentMethods = $this->getActivePaymentMethods();

        $paymentMethod = null;

        if (in_array($this->order->payment["method"]["code"], $activePaymentMethods)) {
            $paymentMethod = $this->order->payment["method"]["code"];
        }

        try {
            $payment = $this->getGateway()->payments->create([
                "amount" => [
                    "currency" => $this->order->currency["code"],
                    "value" => number_format($this->order->total_in_currency, 2, ".", ""),
                ],
                "description" => trans("offline.mall::lang.order.order_number") . ": " . $this->order->order_number,
                "redirectUrl" => $this->returnUrl(),
                "webhookUrl" => $webhookUrl,
                "method" => $paymentMethod,
                "metadata" => [
                    "order_number" => $this->order->order_number,
                ],
            ]);
        } catch (Throwable $e) {
            return $result->fail([], $e);
        }

        Session::put("mall.payment.callback", self::class);

        return $result->redirect($payment->getCheckoutUrl());
    }

    /**
     * Mollie has processed the payment and has redirected the user back to the website
     *
     * @param PaymentResult $result
     *
     * @return PaymentResult
     */
    public function complete(PaymentResult $result): PaymentResult
    {
        return $result->redirect(PaymentGatewaySettings::get("orders_page"));
    }

    /**
     * Mollie has called our webhook after a payment status change has occurred
     *
     * @param mixed $response
     *
     * @return PaymentResult
     */
    public function changePaymentStatus($response): PaymentResult
    {
        try {
            // Get payment data from Mollie using transaction ID from the webhook request
            $payment = $this->getGateway()->payments->get($response->id);

            // Find the right order using the order number from the Mollie payment data
            $order = Order::where("order_number", $payment->metadata->order_number)->first();

            // Set the order context
            $this->setOrder($order);

            $result = new PaymentResult($this, $order);

            // Update the order based on the payment status that Mollie has provided
            if ($payment->isPaid()) {
                
                // Send a notification to the admin, confirming that the checkout has succeeded
                Event::fire('mall.checkout.succeeded', [$result]);
                
                return $result->success((array) $payment, trans("offline.mall::lang.payment_status.paid"));
            } elseif ($payment->isFailed()) {
                return $result->fail((array) $payment, trans("offline.mall::lang.payment_status.failed"));
            } elseif ($payment->isExpired()) {
                return $result->fail((array) $payment, trans("offline.mall::lang.payment_status.expired"));
            } elseif ($payment->isCanceled()) {
                return $result->fail((array) $payment, trans("offline.mall::lang.payment_status.cancelled"));
            }
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            Log::error("API call failed: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Build the payment gateway for Mollie
     *
     * @return \Mollie\Api\MollieApiClient
     */
    protected function getGateway()
    {
        $apiKey = null;

        if (PaymentGatewaySettings::get("mollie_mode") == "live") {
            $apiKey = PaymentGatewaySettings::get("live_api_key");
        } else {
            $apiKey = PaymentGatewaySettings::get("test_api_key");
        }

        $gateway = new \Mollie\Api\MollieApiClient();
        $gateway->setApiKey(decrypt($apiKey));

        return $gateway;
    }

    /**
     * Produce an array of active Mollie payment methods
     *
     * @return array
     */
    protected function getActivePaymentMethods(): array
    {
        $paymentMethods = [];

        try {
            $methods = $this->getGateway()->methods->allActive();

            foreach ($methods as $method) {
                array_push($paymentMethods, $method->id);
            }
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            Log::error("API call failed: " . htmlspecialchars($e->getMessage()));
        }

        return $paymentMethods;
    }
}
