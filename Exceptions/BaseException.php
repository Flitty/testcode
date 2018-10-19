<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 06.10.2018
 * Time: 20:15
 */

namespace Subscription\Exceptions;


use Illuminate\Support\MessageBag;

/**
 * Class BaseException
 * @package Subscription\Exceptions
 */
class BaseException extends \Exception
{
    protected $exceptionDetails;
    protected $errorKey = 'subscription';

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param \Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @since 5.1.0
     */
    public function __construct($message = null, $code = 0, \Throwable $previous = null)
    {
        $message = $message ?? $this->message;
        parent::__construct($message, $code, $previous);
        //FIXME: Add default logging
    }

}