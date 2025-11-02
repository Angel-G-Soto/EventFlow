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

  /**
   * Returns an associative array containing the storage operation that failed
   * and the path of the file that was attempted to access.
   *
   * @return array<string, string|null>
   */
  public function context(): array
  {
    return ['operation' => $this->operation, 'path' => $this->path];
  }

  /**
   * Returns whether the storage operation should be retried.
   *
   * @return bool Whether the operation should be retried.
   */
  public function isRetryable(): bool
  {
    return $this->retryable;
  }

  /**
   * Returns a user-friendly message describing the storage operation failure.
   *
   * @return string A human-readable message describing the failure.
   */
  public function userMessage(): string
  {
    return $this->operation === 'read'
      ? 'We could not access that file. It may have been removed.'
      : 'We could not save your file. Please try again.';
  }
}
