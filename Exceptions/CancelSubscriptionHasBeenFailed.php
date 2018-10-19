<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


class CancelSubscriptionHasBeenFailed extends BaseException
{
    protected $message = 'Canceling of the subscription has been failed';
}