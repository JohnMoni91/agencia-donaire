<?php
// Em app/Mail/ContatoRecebido.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request; // Importa o Request

class ContatoRecebido extends Mailable
{
    use Queueable, SerializesModels;

    // Propriedade pública para guardar os dados do formulário
    public $dados;

    /**
     * Create a new message instance.
     */
    public function __construct(array $dados)
    {
        // Recebe os dados do controller e os armazena
        $this->dados = $dados;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Define o assunto do e-mail e de quem ele veio
        return new Envelope(
            from: $this->dados['email'], // O e-mail do remetente é o da pessoa que preencheu
            replyTo: $this->dados['email'],
            subject: 'Novo Contato do Site: ' . $this->dados['assunto'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Diz ao Laravel para usar a view 'emails.contato' para o corpo do e-mail
        return new Content(
            markdown: 'emails.contato',
        );
    }
}