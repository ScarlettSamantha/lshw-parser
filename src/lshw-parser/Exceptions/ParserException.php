<?php
declare(strict_types=1);

namespace Scarlett\LshwParser\Exceptions;

use Exception;

class ParserException extends Exception
{
    /**
     * ParserException constructor.
     *
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param Exception|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a formatted exception message.
     *
     * @return string The formatted exception message.
     */
    public function getFormattedMessage(): string
    {
        return sprintf(
            "ParserException [%d]: %s\nFile: %s\nLine: %d",
            $this->getCode(),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine()
        );
    }
}
