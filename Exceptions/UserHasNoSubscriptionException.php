<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


class UserHasNoSubscriptionException extends BaseException
{
    protected $message = 'User has no subscription';
}