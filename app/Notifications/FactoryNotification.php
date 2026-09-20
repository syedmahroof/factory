<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FactoryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $module = 'system',
        public string $type = 'info',
        public ?string $url = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[Factory ERP] {$this->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message);

        if ($this->url) {
            $mail->action('View Details', url($this->url));
        }

        return $mail->line('This is an automated notification from the Factory ERP system.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'module' => $this->module,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
