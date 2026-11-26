<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAlreadyRegistered extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You already have an account'))
            ->line(__('Someone tried to use this email address in :app.', ['app' => config('app.name')]))
            ->line(__('You already have an account with this address, so nothing has changed.'))
            ->action(__('Log In'), config()->string('app.frontend_url').'/login')
            ->line(__('If it was not you, you can ignore this message.'));
    }
}
