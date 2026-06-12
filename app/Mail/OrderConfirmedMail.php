<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Đơn hàng #{$this->order->order_code} đã được xác nhận");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-confirmed');
    }
}
