<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


class SubscriptionRedirectHasBeenFailedException extends BaseException
{
    protected $message = 'Something went wrong with Payment System. Contact to support to fix the issue';
}