<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeSetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('empleados.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Configura la contraseña de tu cuenta de empleado')
            ->greeting('Hola '.$notifiable->name)
            ->line('Se creó una cuenta de empleado con este correo electrónico.')
            ->line('Usa el siguiente enlace para establecer tu contraseña.')
            ->action('Establecer contraseña', $url)
            ->line('Si no esperabas este mensaje, puedes ignorarlo.');
    }
}
