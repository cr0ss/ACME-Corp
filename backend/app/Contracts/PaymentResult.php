<?php

namespace App\Contracts;

class PaymentResult
{
    /**
     * @param  array<string, mixed>  $responseData
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly ?string $externalTransactionId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $responseData = []
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getExternalTransactionId(): ?string
    {
        return $this->externalTransactionId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
