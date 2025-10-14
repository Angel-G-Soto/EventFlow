<?php
// app/Exceptions/FileInfectedException.php
namespace App\Exceptions;

use RuntimeException;

class FileInfectedException extends RuntimeException
{
  public function __construct(
    string $message = 'Upload blocked or scan unavailable.',
    protected string $reason = 'infected', // 'infected' | 'scanner_error'
    protected ?string $signature = null,
    protected ?string $engineMessage = null
  ) {
    parent::__construct($message);
  }

  public function context(): array
  {
    return [
      'reason' => $this->reason,
      'signature' => $this->signature,
      'engine_message' => $this->engineMessage,
    ];
  }

  public function isRetryable(): bool
  {
    // scanner errors are typically retryable; infections are not
    return $this->reason === 'scanner_error';
  }

  public function userMessage(): string
  {
    return $this->reason === 'infected'
      ? 'Upload blocked. The file failed our security scan.'
      : 'We could not complete the security scan. Please try again shortly.';
  }
}
