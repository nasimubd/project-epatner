<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompleted extends Notification
{
    use Queueable;

    protected $importType;
    protected $totalRows;
    protected $processedRows;
    protected $failedRows;
    protected $errors;
    protected $processingTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $importType, int $totalRows, int $processedRows, int $failedRows, array $errors, int $processingTime)
    {
        $this->importType = $importType;
        $this->totalRows = $totalRows;
        $this->processedRows = $processedRows;
        $this->failedRows = $failedRows;
        $this->errors = $errors;
        $this->processingTime = $processingTime;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->importType} Completed")
            ->line("Your {$this->importType} has been processed.")
            ->line("Total rows: {$this->totalRows}")
            ->line("Successfully processed: {$this->processedRows}")
            ->line("Failed: {$this->failedRows}")
            ->line("Processing time: " . $this->formatTime($this->processingTime));

        if ($this->failedRows > 0 && count($this->errors) > 0) {
            $mail->line('Errors encountered:');

            // Limit the number of errors shown in the email
            $errorsToShow = array_slice($this->errors, 0, 10);
            foreach ($errorsToShow as $error) {
                $mail->line("- {$error}");
            }

            if (count($this->errors) > 10) {
                $mail->line("... and " . (count($this->errors) - 10) . " more errors.");
            }
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'import_type' => $this->importType,
            'total_rows' => $this->totalRows,
            'processed_rows' => $this->processedRows,
            'failed_rows' => $this->failedRows,
            'errors' => $this->errors,
            'processing_time' => $this->processingTime,
        ];
    }

    /**
     * Format processing time in a human-readable format
     */
    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes} minutes, {$remainingSeconds} seconds";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours} hours, {$remainingMinutes} minutes, {$remainingSeconds} seconds";
    }
}
