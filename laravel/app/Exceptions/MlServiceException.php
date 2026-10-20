<?php

namespace App\Exceptions;

use App\Enums\MlFailure;
use RuntimeException;
use Throwable;

/**
 * Kegagalan saat memanggil layanan ML. getMessage() selalu berisi pesan yang aman untuk pengguna;
 * rincian teknis hanya ada di $previous dan di log server.
 */
final class MlServiceException extends RuntimeException
{
    private function __construct(public readonly MlFailure $failure, ?Throwable $previous = null)
    {
        parent::__construct($failure->message(), 0, $previous);
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self(MlFailure::Unavailable, $previous);
    }

    public static function modelUnavailable(?Throwable $previous = null): self
    {
        return new self(MlFailure::ModelUnavailable, $previous);
    }

    public static function invalidUrl(?Throwable $previous = null): self
    {
        return new self(MlFailure::InvalidUrl, $previous);
    }

    public function userMessage(): string
    {
        return $this->failure->message();
    }
}