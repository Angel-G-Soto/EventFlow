<?php
// app/Exceptions/StorageException.php
namespace App\Exceptions;

use RuntimeException;

class StorageException extends RuntimeException
{
  public function __construct(
    string $message = 'Storage operation failed.',
    protected string $operation = 'unknown', // 'read' | 'write' | 'delete'
    protected ?string $path = null,
    protected bool $retryable = false
  ) {
    parent::__construct($message);
  }

  public function context(): array
  {
    return ['operation' => $this->operation, 'path' => $this->path];
  }

  public function isRetryable(): bool
  {
    return $this->retryable;
  }

  public function userMessage(): string
  {
    return $this->operation === 'read'
      ? 'We could not access that file. It may have been removed.'
      : 'We could not save your file. Please try again.';
  }
}
