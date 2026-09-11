<?php

use Illuminate\Notifications\Messages\MailMessage;
use Kraftdo\Shared\SystemNotification;

/**
 * La clase base de las notificaciones del sistema: canal correo por omisión y
 * un MailMessage armado con asunto y vista Markdown. Las subclases solo aportan
 * esas dos cosas.
 */
function notificacionDePrueba(): SystemNotification
{
    return new class extends SystemNotification
    {
        public function toMail(object $notifiable): MailMessage
        {
            return $this->correo('Bienvenida', 'correos.bienvenida', ['nombre' => 'Ana']);
        }
    };
}

it('entrega por correo por omisión', function () {
    expect(notificacionDePrueba()->via(new stdClass))->toBe(['mail']);
});

it('arma el MailMessage con el asunto y la vista Markdown', function () {
    $correo = notificacionDePrueba()->toMail(new stdClass);

    expect($correo)->toBeInstanceOf(MailMessage::class)
        ->and($correo->subject)->toBe('Bienvenida')
        ->and($correo->markdown)->toBe('correos.bienvenida')
        ->and($correo->viewData)->toBe(['nombre' => 'Ana']);
});
