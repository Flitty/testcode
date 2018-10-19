<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 16:24
 */

namespace Subscription;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Srmklive\PayPal\Services\ExpressCheckout;
use Subscription\Exceptions\SubscriptionRedirectHasBeenFailedException;
use Subscription\Providers\SubscriptionServiceProvider;

/**
 * Class PayPalClient
 * @property ExpressCheckout $provider
 * @package Subscription
 */
class PayPalClient extends Client
{

    const SUCCESS_STATUSES = ['SUCCESS', 'SUCCESSWITHWARNING', 'ActiveProfile', 'PendingProfile'];
    const SUCCESS_VALIDATION_RESPONSE = 'VERIFIED';
    const INVALID_VALIDATION_RESPONSE = 'INVALID';

    protected $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER;

    /**
     * Initialize client manager
     *
     * @return ExpressCheckout
     */
    protected function initClient()
    {
        $config = config('subscription.drivers.PayPal');
        return new ExpressCheckout($config);
    }

    /**
     * Callback url. Payment system will redirect when user has been submited payment form successfully
     *
     * @return string - url of the current site that handle payment system request
     */
    protected function getSuccessUrl(): string
    {
        return url(config('subscription.drivers.PayPal.express-checkout-success'));
    }

    /**
     * Send to the payment system subscription data.
     *
     * @param array $paymentData - specific array of subscription data that sends to the payment system
     *
     * @return mixed - payment system response, includes status of the request.
     */
    protected function getSubscriptionResponse(array $paymentData)
    {
        return $this->provider->setExpressCheckout($paymentData,self::IS_RECURRING);
    }


    /**
     * Prepare specific data for subscription request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return array [
     *      'items' => [
     *          [
     *              'name'  => $this->getSubscriptionName($subscriptionId),
     *              'price' => $subscriptionPrice['total'],
     *              'qty'   => 1,
     *          ],
     *      ],
     *      'return_url'          => (string) the url where PayPal returns after user confirmed the payment,
     *      'subscription_desc'   => (string) subscription description,
     *      'invoice_id'          => (string) unique subscription identificator,
     *      'invoice_description' => (string) invoice description,
     *      'cancel_url'          => (string) the url where PayPal returns after some fail,
     *      'total'               => (string) Total price of the cart
     * ]
     */
    protected function getSubscriptionData(int $subscriptionId) : array
    {
        $subscriptionPrice = $this->getSubscriptionPrice($subscriptionId);
        return [
            'items' => [
                [
                    'name' => $this->getSubscriptionName($subscriptionId),
                    'price' => $subscriptionPrice['total'],
                    'qty' => 1,
                ],
            ],
            'return_url' => $this->getSuccessUrl(),
            'subscription_desc' => $this->getSubscriptionDescription($subscriptionId),
            'invoice_id' => (string) $subscriptionId,
            'invoice_description' => $this->getInvoiceDescription($subscriptionId),
            'cancel_url' => $this->getCancelUrl(),
            'total' =>  $subscriptionPrice['total']
        ];
    }

    /**
     * Check response. Prepare redirect to the payment system form
     *
     * @param $response - specific array
     *
     * @return RedirectResponse
     * @throws SubscriptionRedirectHasBeenFailedException
     */
    protected function redirectSubscription($response) : RedirectResponse
    {
        if (!$response['paypal_link']) {
            throw new SubscriptionRedirectHasBeenFailedException();
        } else {
            $redirectLink = $response['paypal_link'];
        }
        return redirect($redirectLink);
    }

    /**
     * Send checkout request to the payment system
     *
     * @param string $token - payment system callback token
     *
     * @return array - specific data
     */
    protected function getCheckoutDetails(string $token) : array
    {
        return $this->provider->getExpressCheckoutDetails($token);
    }

    /**
     * Return true if payment system response has been failed
     *
     * @param array $details - payment system response
     *
     * @return bool
     */
    protected function hasErrorInDetails(array $details) : bool
    {
        return (bool) !in_array(strtoupper($details['ACK']), self::SUCCESS_STATUSES);
    }

    /**
     * Get application subscription identificator from payment system response
     *
     * @param array $details - payment system response
     *
     * @return int
     */
    protected function getAppSubscriptionId(array $details): int
    {
        return $details[self::SUBSCRIPTION_ID_FIELD];
    }

    /**
     * Send request to Payment system to approve the subscription
     *
     * @param int    $subscriptionId - application subscription identificator
     * @param string $token - payment system callback token
     * @param string $period - billing period can be one of that values: @const Client::MONTHLY_BILLING_PERIOD
     *
     * @return mixed
     */
    protected function submitSubscription(int $subscriptionId, string $token, string $period)
    {
        $subscriptionPrice = $this->getSubscriptionPrice($subscriptionId);
        // if recurring then we need to create the subscription
        // you can create monthly or yearly subscriptions
        $data = [
            'PROFILESTARTDATE' => Carbon::now()->addDay()->toAtomString(),
            'DESC'             => $this->getSubscriptionDescription($subscriptionId),
            'BILLINGPERIOD'    => $period,
            'BILLINGFREQUENCY' => 1,
            'AMT'              => $subscriptionPrice['total'],
            'CURRENCYCODE'     => config('subscription.currency'),
        ];
        if (isset($subscriptionPrice['trial'])) {
            $data['TRIALBILLINGPERIOD'] = $subscriptionPrice['trial']['period'];
            $data['TRIALBILLINGFREQUENCY'] = $subscriptionPrice['trial']['frequency'];
            $data['TRIALTOTALBILLINGCYCLES'] = $subscriptionPrice['trial']['cycles'];
            $data['TRIALAMT'] = $subscriptionPrice['trial']['amt'];
        }
        return $this->provider->createRecurringPaymentsProfile($data, $token);
    }

    /**
     * Check is successful subscription
     *
     * @param array $subscriptionResponse - payment system response after subscription request
     *
     * @return bool
     */
    protected function isSuccessSubscription(array $subscriptionResponse) : bool
    {
        return !empty($subscriptionResponse['PROFILESTATUS']) && in_array($subscriptionResponse['PROFILESTATUS'], self::SUCCESS_STATUSES);
    }

    /**
     * Prepare transaction data before saving using subscription identificator
     * of the application and payment system response
     *
     * @param int   $subscriptionId - application subscription identificator
     * @param array $subscriptionResponse - payment system response after subscription request
     *
     * @return array [
     *      'subscription_id' - application subscription identificator
     *      'payer_id' - identificator of the payer,
     *      'amount' - amount of the transaction,
     *      'status' - status from the subscription response,
     *      'message' - message from the subscription response
     *  ]
     */
    protected function getTransactionBody(int $subscriptionId, array $subscriptionResponse) : array
    {
        $subscriptionPrice = $this->getSubscriptionPrice($subscriptionId);
        $price = isset($subscriptionPrice['trial']) ? $subscriptionPrice['trial']['amt'] : $subscriptionPrice['total'];
        return [
            'subscription_id' => $subscriptionId,
            'payer_id' => $subscriptionResponse['CORRELATIONID'],
            'amount' => $price,
            'status' => $subscriptionResponse['ACK'],
            'message' => $subscriptionResponse['L_LONGMESSAGE0']
        ];
    }

    /**
     * Get recurring payment identificator from payment system response.
     *
     * @param array $subscriptionResponse - payment system response
     *
     * @return string - specific string (I-HV9XJEPJKGT8)
     */
    protected function getRecurringPaymentId(array $subscriptionResponse) : string
    {
        return $subscriptionResponse['PROFILEID'] ?? $subscriptionResponse['recurring_payment_id'];
    }

    /**
     * Send request to payment api to check is got request valid and has actual information
     *
     * @param array $request - webhook request
     *
     * @return null|string - one of that constants:
     *      @const Client::RECURRING_PAYMENT,
     *      @const Client::RECURRING_PAYMENT_FAILED,
     *      @const Client::RECURRING_PAYMENT_SKIPPED
     */
    protected function validateResponse(array $request) :? string
    {
        // add _notify-validate cmd to request,
        // we need that to validate with PayPal that it was realy
        // PayPal who sent the request
        $request = array_merge($request, ['cmd' => '_notify-validate']);

        // send the data to PayPal for validation
        $strResponse = (string) $this->provider->verifyIPN($request);
        $validationResult = null;
        if ($strResponse === self::SUCCESS_VALIDATION_RESPONSE) {
            if (in_array($request['txn_type'], [
                static::RECURRING_PAYMENT,
                static::RECURRING_PAYMENT_FAILED,
                static::RECURRING_PAYMENT_SKIPPED
            ])) {
                $validationResult = $request['txn_type'];
            }
        }
        return $validationResult;
    }

    /**
     * Get payer identificator from webhook request.
     *
     * @param array $request - webhook request
     *
     * @return null|string - specific string (10cd949817fea)
     */
    protected function getPayerIdFromRequest(array $request) :? string
    {
        return array_get($request, 'payer_id');
    }

    /**
     * Get amount from webhook request.
     *
     * @param array $request - webhook request
     *
     * @return float - transaction amount
     */
    protected function getAmountFromRequest(array $request) : float
    {
        return (float) array_get($request, 'amount');
    }

    /**
     * Send request to API to cancel active subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    protected function cancelRecurringPaymentsProfile(string $profileId) : array
    {
        return $this->provider->cancelRecurringPaymentsProfile($profileId);
    }

    /**
     * Send request to API to reactivate suspended subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    protected function reactivateRecurringPaymentsProfile(string $profileId) : array
    {
        return $this->provider->reactivateRecurringPaymentsProfile($profileId);
    }

    /**
     * Send request to API to suspend active subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    protected function suspendRecurringPaymentsProfile(string $profileId) : array
    {
        return $this->provider->suspendRecurringPaymentsProfile($profileId);
    }


}