<?php
declare(strict_types=1);
namespace App\Core;

final class ValidationException extends \InvalidArgumentException
{
    public function __construct(public array $fields)
    {
        parent::__construct((string) reset($fields));
    }
}
