<?php
namespace Meduzean\EanManager\Exceptions;

defined('ABSPATH') || exit;

class EanException extends \Exception
{
    public const EAN_NOT_FOUND = 'EAN_NOT_FOUND';
    public const EAN_ALREADY_EXISTS = 'EAN_ALREADY_EXISTS';
    public const EAN_INVALID = 'EAN_INVALID';
    public const PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    public const EAN_ALREADY_ASSIGNED = 'EAN_ALREADY_ASSIGNED';
    public const ASSIGNMENT_FAILED = 'ASSIGNMENT_FAILED';
    
    public function __construct(string $message = '', string $code = '', int $previous = 0)
    {
        parent::__construct($message, $previous);
        $this->code = $code;
    }
}
