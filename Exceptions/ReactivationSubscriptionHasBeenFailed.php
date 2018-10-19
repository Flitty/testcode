<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


class ReactivationSubscriptionHasBeenFailed extends BaseException
{
    protected $message = 'Reactivating of the subscription has been failed';
}