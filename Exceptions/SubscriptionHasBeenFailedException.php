<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 11:14
 */

namespace Subscription\Exceptions;


class SubscriptionHasBeenFailedException extends BaseException
{
    protected $message = 'Subscription has been failed';

}