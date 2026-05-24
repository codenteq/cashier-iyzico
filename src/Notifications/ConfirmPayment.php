<?php

namespace Codenteq\Iyzico\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmPayment extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('cashier-iyzico::app.subscription_started_subject'))
            ->greeting(__('cashier-iyzico::app.subscription_started_greeting', ['name' => $notifiable->name]))
            ->line(__('cashier-iyzico::app.subscription_started_line_1'))
            ->line(__('cashier-iyzico::app.subscription_started_line_2'))
            ->action(__('cashier-iyzico::app.subscription_started_action'), url('/dashboard'))
            ->line(__('cashier-iyzico::app.subscription_started_thanks'));
    }
}