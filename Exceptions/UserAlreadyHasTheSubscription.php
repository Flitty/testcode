<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 06.10.2018
 * Time: 20:14
 */

namespace Subscription\Exceptions;


use Throwable;

class UserAlreadyHasTheSubscription extends BaseException
{
    protected $errorKey = 'exist-subscription';

    protected $message = 'User already has this subscription';



}