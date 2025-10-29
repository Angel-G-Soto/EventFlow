<?php
// app/Exceptions/FileInfectedException.php
namespace App\Exceptions;

use RuntimeException;

class FileInfectedException extends RuntimeException
{
  /**
   * Construct a new FileInfectedException instance.
   *
   * @param string      $message        The exception message.
   * @param string      $reason         The reason ('infected' or 'scanner_error').
   * @param string|null $signature      The detected virus signature, if any.
   * @param string|null $engineMessage  Message returned by the virus scanner, if any.
   */

  public function __construct(
    string $message = 'Upload blocked or scan unavailable.',
    protected string $reason = 'infected', // 'infected' | 'scanner_error'
    protected ?string $signature = null,
    protected ?string $engineMessage = null
  ) {
    parent::__construct($message);
  }

  /**
   * Return an associative array containing the exception context.
   *
   * @return array<string,string|null>
   */
  public function context(): array
  {
    return [
      'reason' => $this->reason,
      'signature' => $this->signature,
      'engine_message' => $this->engineMessage,
    ];
  }

  /**
   * Determine if the exception is retryable.
   *
   * Scanner errors are typically retryable (e.g. connection issues), whereas
   * infections are not (e.g. the file contains a virus).
   *
   * @return bool
   */
  public function isRetryable(): bool
  {
    // scanner errors are typically retryable; infections are not
    return $this->reason === 'scanner_error';
  }

  /**
   * Return a human-readable message describing the exception.
   *
   * @return string
   */
  public function userMessage(): string
  {
    return $this->reason === 'infected'
      ? 'Upload blocked. The file failed our security scan.'
      : 'We could not complete the security scan. Please try again shortly.';
  }
}
