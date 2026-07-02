<?php

namespace App\Exceptions;

use Exception;

class InstagramImportException extends Exception
{
    public static function captionUnavailable(): self
    {
        return new self(
            'Instagram did not expose a public caption for this post. Instagram imports work only when the post caption is available without signing in.'
        );
    }
}
