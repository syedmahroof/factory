<?php

namespace App\Exceptions;

use Exception;

class ActionException extends Exception
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(string $message, array $data = [])
    {
        parent::__construct($message);
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
