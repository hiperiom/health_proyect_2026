<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedTemporaryPassword extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $temporaryPassword,
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $name = trim((string) $notifiable->name);

        return (new MailMessage)
            ->subject(__('mail.temporary_password.subject'))
            ->greeting(__('mail.temporary_password.greeting', ['name' => $name]))
            ->line(__('mail.temporary_password.intro', ['app' => config('app.name')]))
            ->line(__('mail.temporary_password.password_label'))
            ->line('**'.$this->temporaryPassword.'**')
            ->action(__('mail.temporary_password.login_action'), route('login'))
            ->line(__('mail.temporary_password.change_requirement'))
            ->line(__('mail.temporary_password.change_advice'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
