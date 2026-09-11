<?php

namespace Kraftdo\Shared;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Clase base de todas las notificaciones del sistema.
 *
 * Centraliza el canal por defecto (correo) y un constructor de MailMessage con
 * el asunto y la vista Markdown. Las subclases solo aportan esas dos cosas; el
 * logo, los colores y el pie salen del tema de correo que publique cada sistema
 * (config/mail.php → resources/views/vendor/mail).
 *
 * Para que una notificación se procese en segundo plano basta con que la
 * subclase implemente Illuminate\Contracts\Queue\ShouldQueue. Las sensibles a
 * la latencia (el código del segundo factor, por ejemplo) NO lo implementan y
 * se envían en el acto.
 */
abstract class SystemNotification extends Notification
{
    use Queueable;

    /**
     * Canales de entrega. Hoy solo correo; mañana se puede añadir 'database'
     * o un canal SMS sin tocar las subclases.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye un MailMessage con la vista Markdown indicada. La vista hereda
     * el layout de correo del sistema.
     *
     * @param  array<string, mixed>  $data
     */
    protected function correo(string $asunto, string $vista, array $data = []): MailMessage
    {
        return (new MailMessage)
            ->subject($asunto)
            ->markdown($vista, $data);
    }
}
