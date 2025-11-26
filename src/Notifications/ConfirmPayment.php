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
            ->subject('Aboneliğiniz Başladı!')
            ->greeting('Merhaba '.$notifiable->name.' 👋')
            ->line('Aboneliğiniz başarıyla aktif edildi.')
            ->line('Hizmetlerimizi kullanmaya başlayabilirsiniz.')
            ->action('Panele Git', url('/dashboard'))
            ->line('Bizi tercih ettiğiniz için teşekkür ederiz!');
    }
}
