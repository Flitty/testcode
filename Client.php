<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 15:59
 */

namespace Subscription;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Subscription\Contracts\SubscriberContract;
use Subscription\Exceptions\CancelSubscriptionHasBeenFailed;
use Subscription\Exceptions\InvalidResponseException;
use Subscription\Exceptions\ReactivationSubscriptionHasBeenFailed;
use Subscription\Exceptions\SubscriptionCallbackHasBeenFailedException;
use Subscription\Exceptions\SubscriptionHasBeenFailedException;
use Subscription\Exceptions\SubscriptionHasNotBeenFoundException;
use Subscription\Exceptions\SubscriptionRedirectHasBeenFailedException;
use Subscription\Exceptions\SuspendingSubscriptionHasBeenFailed;
use Subscription\Exceptions\UserHasNoSubscriptionException;
use Subscription\Models\Subscription;

/**
 * Class Client
 * @package Subscription
 */
abstract class Client
{
    const MONTHLY_BILLING_PERIOD = 'Month';

    const RECURRING_PAYMENT = 'recurring_payment';
    const RECURRING_PAYMENT_SKIPPED = 'recurring_payment_skipped';
    const RECURRING_PAYMENT_FAILED = 'recurring_payment_failed';

    const SUCCESS_STATUS = 'Success';
    const PROCESSED_STATUS = 'Processed';
    const SKIPPED_STATUS = 'Skipped';
    const FAILED_STATUS = 'Failed';

    const SUBSCRIPTION_ID_FIELD = 'INVNUM';
    const IS_RECURRING = true;

    /**
     * @var null|Subscription - subscription model
     */

    protected $subscription = null;

    /**
     * @var mixed - payment system client manager
     */
    protected $provider;

    /**
     * @var null|string - string identifier of payment system
     * Available values:
     *      @const SubscriptionServiceProvider::PAY_PAL_DRIVER
     */
    protected $driver = null;

    /**
     * Client constructor.
     *
     * Initialize payment system client manager
     */
    public function __construct()
    {
        $this->provider = $this->initClient();
    }

    /**
     * Initialize client manager
     *
     * @return mixed
     */
    abstract protected function initClient();

    /**
     * Reactivate suspended subscription.
     *
     * @param SubscriberContract $subscriber
     * @param int                $subscriptionTypeId
     *
     * @return bool
     * @throws ReactivationSubscriptionHasBeenFailed
     * @throws UserHasNoSubscriptionException
     */
    public function reactivateSubscription(SubscriberContract $subscriber, int $subscriptionTypeId) : bool
    {
        $subscription = $subscriber->getTypeSubscription($subscriptionTypeId, Subscription::SUSPENDED_STATUS);
        if (!$subscription) {
            throw new UserHasNoSubscriptionException;
        }
        $response = $this->reactivateRecurringPaymentsProfile($subscription->recurring_payment_id);
        if ($this->hasErrorInDetails($response)) {
            throw new ReactivationSubscriptionHasBeenFailed;
        }
        return app('sub-service')->updateSubscription(
            $subscription->id,
            [
                'expire_at' => $subscription->suspendExpireDiff()->toDateTimeString(),
                'suspended_at' => null,
                'status' => Subscription::LIVE_STATUS
            ]
        );
    }

    /**
     * Suspend active subscription
     *
     * @param SubscriberContract $subscriber
     * @param int                $subscriptionTypeId
     *
     * @return bool
     * @throws SuspendingSubscriptionHasBeenFailed
     * @throws UserHasNoSubscriptionException
     */
    public function suspendSubscription(SubscriberContract $subscriber, int $subscriptionTypeId) : bool
    {
        $subscription = $subscriber->getTypeSubscription($subscriptionTypeId);
        if (!$subscription) {
            throw new UserHasNoSubscriptionException;
        }
        $response = $this->suspendRecurringPaymentsProfile($subscription->recurring_payment_id);
        if ($this->hasErrorInDetails($response)) {
            throw new SuspendingSubscriptionHasBeenFailed;
        }
        $now = Carbon::now()->toDateTimeString();
        app('sub-service')->updateSubscription($subscription->id, ['suspended_at' => $now, 'status' => Subscription::SUSPENDED_STATUS]);
        return true;
    }

    /**
     * Cancel active subscription
     *
     * @param SubscriberContract $subscriber
     * @param int                $subscriptionTypeId
     *
     * @return bool
     * @throws CancelSubscriptionHasBeenFailed
     * @throws UserHasNoSubscriptionException
     */
    public function cancelSubscription(SubscriberContract $subscriber, int $subscriptionTypeId) : bool
    {
        $subscription = $subscriber->getTypeSubscription($subscriptionTypeId);
        if (!$subscription) {
            throw new UserHasNoSubscriptionException;
        }
        $response = $this->cancelRecurringPaymentsProfile($subscription->recurring_payment_id);
        if ($this->hasErrorInDetails($response)) {
            throw new CancelSubscriptionHasBeenFailed;
        }
        app('sub-service')->updateSubscription($subscription->id, ['expire_at' => null, 'status' => Subscription::CANCELED_STATUS]);
        return true;
    }

    /**
     * Send request to API to reactivate suspended subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    abstract protected function reactivateRecurringPaymentsProfile(string $profileId) : array;

    /**
     * Send request to API to cancel active subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    abstract protected function cancelRecurringPaymentsProfile(string $profileId) : array;

    /**
     * Send request to API to suspend active subscription
     *
     * @param string $profileId
     *
     * @return array - specific api response
     */
    abstract protected function suspendRecurringPaymentsProfile(string $profileId) : array;

    /**
     * Method handles payment webhook.
     * It updates Subscription row and create new Transaction in DB
     * if user has been payed money for next subscription cycle.
     *
     * @param array $request - specific payment request
     *
     * @return bool - Has transaction been created successfully?
     * @throws InvalidResponseException - Response validation returns failed result
     * @throws SubscriptionHasNotBeenFoundException - Subscription has not been found in database
     */
    public function cmdCallback(array $request) : bool
    {
        $subService = app('sub-service');
        $responseType = $this->validateResponse($request);
        if ($responseType) {
            $subscription = app('sub-service')->getSubscriptionByRecurringPaymentId($this->getRecurringPaymentId($request));
            if (!$subscription) {
                throw new SubscriptionHasNotBeenFoundException;
            }
            if ($responseType == static::RECURRING_PAYMENT) {
                $expireAt = Carbon::now()->addMonth()->toDateTimeString();
                $subService->updateSubscription($subscription->id, ['expire_at' => $expireAt, 'status' => Subscription::LIVE_STATUS]);
            }
            $transactionAttributes = [
                'subscription_id' => $subscription->id,
                'amount' => $this->getAmountFromRequest($request),
                'status' => $this->getStatus($responseType),
                'payer_id' => $this->getPayerIdFromRequest($request)
            ];
            return (bool) $subService->createTransaction($transactionAttributes);
        } else {
            throw new InvalidResponseException;
        }
    }

    /**
     * Returns transaction status slug according to response
     *
     * @param string $responseType
     *
     * @return null|string - one of that constants:
     *      @const Client::SUCCESS_STATUS,
     *      @const Client::SKIPPED_STATUS,
     *      @const Client::FAILED_STATUS
     */
    protected function getStatus(string $responseType) :? string
    {
        switch ($responseType) {
            case static::RECURRING_PAYMENT:
                $status = static::SUCCESS_STATUS;
                break;
            case static::RECURRING_PAYMENT_SKIPPED:
                $status = static::SKIPPED_STATUS;
                break;
            case static::RECURRING_PAYMENT_FAILED:
                $status = static::FAILED_STATUS;
                break;
            default:
                $status = null;
        }
        return $status;
    }

    /**
     * Get amount from webhook request.
     *
     * @param array $request - webhook request
     *
     * @return float - transaction amount
     */
    abstract protected function getAmountFromRequest(array $request) : float;

    /**
     * Get payer identificator from webhook request.
     *
     * @param array $request - webhook request
     *
     * @return null|string - specific string
     */
    abstract protected function getPayerIdFromRequest(array $request) :? string;

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
    abstract protected function validateResponse(array $request) :? string;

    /**
     * When user has been submited the payment action payment system redirects by provided link with token.
     * The method gets payment details by provided token, update subscription,
     * generate transaction details and set it to database
     *
     * @param string $token - payment system token provided in callback
     *
     * @return bool - Is successful subscription?
     * @throws SubscriptionCallbackHasBeenFailedException - Payment system returns failed response
     * @throws SubscriptionHasBeenFailedException - Payment method details have failure status
     */
    public function subscriptionCallback(string $token) : bool
    {
        $subService = app('sub-service');
        $response = $this->getCheckoutDetails($token);
        if ($this->hasErrorInDetails($response)) {
            throw new SubscriptionCallbackHasBeenFailedException;
        }
        $subscriptionId = $this->getAppSubscriptionId($response);
        $subscriptionResponse = $this->submitSubscription($subscriptionId, $token, self::MONTHLY_BILLING_PERIOD);
        $isSuccess = $this->isSuccessSubscription($subscriptionResponse);
        $subService->createTransaction($this->getTransactionBody($subscriptionId, $subscriptionResponse));
        $expireAt = $isSuccess ? Carbon::now()->addMonth()->toDateTimeString() : null;
        $subService->updateSubscription(
            $subscriptionId,
            [
                'recurring_payment_id' => $this->getRecurringPaymentId($subscriptionResponse),
                'expire_at' => $expireAt,
                'status' => Subscription::LIVE_STATUS
            ]
        );
        if (!$isSuccess) {
            throw new SubscriptionHasBeenFailedException;
        }
        return true;
    }

    /**
     * Get recurring payment identificator from payment system response.
     *
     * @param array $subscriptionResponse - payment system response
     *
     * @return string - specific string
     */
    abstract protected function getRecurringPaymentId(array $subscriptionResponse) : string;

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
    abstract protected function getTransactionBody(int $subscriptionId, array $subscriptionResponse) : array;

    /**
     * Check is successful subscription
     *
     * @param array $subscriptionResponse - payment system response after subscription request
     *
     * @return bool
     */
    abstract protected function isSuccessSubscription(array $subscriptionResponse) : bool;

    /**
     * Send request to Payment system to approve the subscription
     *
     * @param int    $subscriptionId - application subscription identificator
     * @param string $token - payment system callback token
     * @param string $period - billing period can be one of that values: @const Client::MONTHLY_BILLING_PERIOD
     *
     * @return mixed
     */
    abstract protected function submitSubscription(int $subscriptionId, string $token, string $period);

    /**
     * Get application subscription identificator from payment system response
     *
     * @param array $details - payment system response
     *
     * @return int
     */
    abstract protected function getAppSubscriptionId(array $details) : int;

    /**
     * Return true if payment system response has been failed
     *
     * @param array $details - payment system response
     *
     * @return bool
     */
    abstract protected function hasErrorInDetails(array $details) : bool;

    /**
     * Send checkout request to the payment system
     *
     * @param string $token - payment system callback token
     *
     * @return array - specific data
     */
    abstract protected function getCheckoutDetails(string $token) : array;

    /**
     * Create if not exist subscription row in current application.
     * Redirect to payment system form
     *
     * @param SubscriberContract $subscriber - application entity wants to create subscription
     * @param int                $subscriptionTypeId - Found o created subscription identificator
     * @param null               $subscriptionCouponId - Optional value.
     * Identificator of SubscriptionCoupon::class entity.
     *
     * @return RedirectResponse - Redirect to payment system form
     */
    public function createSubscription(SubscriberContract $subscriber, int $subscriptionTypeId, $subscriptionCouponId = null) : RedirectResponse
    {
        $subscription = $subscriber->availableSubscriptions()->whereSubscriptionTypeId($subscriptionTypeId)->first();
        if (!$subscription) {
            $subscription = app('sub-service')->createSubscription([
                'subscription_coupon_id' => $subscriptionCouponId,
                'subscription_type_id' => $subscriptionTypeId,
                'user_id' => $subscriber->getId(),
                'driver' => $this->driver,
            ]);
        }
        $subscription->status = static::PROCESSED_STATUS;
        $subscription->save();
        $paymentData = $this->getSubscriptionData($subscription->id);
        $response = $this->getSubscriptionResponse($paymentData);
        return $this->redirectSubscription($response);
    }

    /**
     * Send to the payment system subscription data.
     *
     * @param array $paymentData - specific array of subscription data that sends to the payment system
     *
     * @return mixed - payment system response, includes status of the request.
     */
    abstract protected function getSubscriptionResponse(array $paymentData);

    /**
     * Check response. Prepare redirect to the payment system form
     *
     * @param $response - specific array
     *
     * @return RedirectResponse
     * @throws SubscriptionRedirectHasBeenFailedException
     */
    abstract protected function redirectSubscription($response) : RedirectResponse;

    /**
     * Prepare specific data for subscription request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return array - specific data for subscription request
     */
    abstract protected function getSubscriptionData(int $subscriptionId) : array;

    /**
     * Callback url. Payment system will redirect when user has been submited payment form successfully
     *
     * @return string - url of the current site that handle payment system request
     */
    abstract protected function getSuccessUrl() : string;

    /**
     * Callback url. Payment system will redirect when user has been submited payment form unsuccessfully
     *
     * @return string - url of the current site
     */
    protected function getCancelUrl() : string
    {
        return url(config('subscription.cancel_url'));
    }

    /**
     * Prepare subscription identificator for request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return string - subscription identificator for request
     */
    protected function getSubscriptionId(int $subscriptionId) : string
    {
        return config('subscription.subscription_prefix') . ' #' . $subscriptionId;
    }

    /**
     * Prepare invoice description for request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return string - invoice description for request
     */
    protected function getInvoiceDescription(int $subscriptionId) : string
    {
        return "Order #". $subscriptionId ." Invoice";
    }

    /**
     * Prepare subscription name for request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return string - subscription name for request
     */
    protected function getSubscriptionName(int $subscriptionId) : string
    {
        return 'Monthly Subscription ' . $this->getSubscriptionId($subscriptionId);
    }

    /**
     * Prepare subscription description for request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return string - subscription description for request
     */
    protected function getSubscriptionDescription(int $subscriptionId) : string
    {
        return $this->getSubscriptionName($subscriptionId);
    }

    /**
     * Prepare subscription price for request
     *
     * @param int $subscriptionId - application subscription identificator
     *
     * @return array [
     *      'total' - required | total amount,
     *      'trial' - optional | @array should be provided for discount applying [
     *          'period' - ('Day', 'Week', 'SemiMonth', 'Month', 'Year')
     *          'frequency' - set 12 for monthly, 52 for yearly
     *          'cycles' - count of cycles the description should be applyed
     *          'amt' - recounted amount,
     */
    protected function getSubscriptionPrice(int $subscriptionId) : array
    {
        if (!$this->subscription) {
            $this->subscription = app('sub-service')->getSubscriptionById($subscriptionId);
        }
        $subscriptionCoupon = $this->subscription->subscriptionCoupon;
        $total = $this->subscription->subscriptionType->amount;
        if ($subscriptionCoupon) {
            $discount = $subscriptionCoupon ? $subscriptionCoupon->discount : 0;
            $data = [
                'trial' => [
                    'period' => $subscriptionCoupon->period,
                    'frequency' => $subscriptionCoupon->frequency,
                    'cycles' => $subscriptionCoupon->cycles,
                    'amt' => $total * (100 - $discount) / 100
                ]
            ];
        }
        $data['total'] = $total;
        return $data;
    }
}