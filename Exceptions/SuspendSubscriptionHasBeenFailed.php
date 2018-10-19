<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


class SuspendSubscriptionHasBeenFailed extends BaseException
{
    protected $message = 'Suspending of the subscription has been failed';
}