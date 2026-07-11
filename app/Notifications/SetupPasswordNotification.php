<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetupPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $password,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account has been created')
            ->greeting('Hello '.$notifiable->name)
            ->line('Your account has been created.')
            ->line('Email: '.$notifiable->email)
            ->line('Password: '.$this->password)
            ->line('Please sign in and change your password.');
    }
}
