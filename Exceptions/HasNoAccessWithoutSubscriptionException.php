<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 08.10.2018
 * Time: 10:19
 */

namespace Subscription\Exceptions;


/**
 * Class HasNoAccessWithoutSubscriptionException
 * @package Subscription\Exceptions
 */
class HasNoAccessWithoutSubscriptionException extends BaseException
{
    protected $message = 'You have no access without subscription';
    protected $code = 403;

}