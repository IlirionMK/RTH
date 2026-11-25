<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\SignedFrontendUrl;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyNewEmail extends Notification
{
    public function __construct(private readonly User $user) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = SignedFrontendUrl::make('email-changes.store', [
            'user' => $this->user->id,
            'hash' => sha1((string) $this->user->pending_email),
        ]);

        return (new MailMessage)
            ->subject(__('Confirm your new email address'))
            ->line(__('Please click the button below to confirm your new email address.'))
            ->action(__('Confirm email address'), $url)
            ->line(__('If you did not change your email address, you can ignore this message.'));
    }
}
