<?php

namespace App\Services;

class EmailService
{
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $this->fromEmail = env('EMAIL_FROM',      'noreply@grabgreatdeals.com');
        $this->fromName  = env('EMAIL_FROM_NAME', 'GrabGreatDeals');
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    /**
     * Send an order confirmation email after a successful checkout.
     *
     * $order must include: order_number, status, subtotal, shipping_fee,
     *                     discount_amount, total, shipping_name, shipping_phone,
     *                     shipping_address, created_at, items[], payment
     */
    public function sendOrderConfirmation(array $order, string $toEmail, string $toName): bool
    {
        $subject = 'Order Confirmed – ' . $order['order_number'];
        $body    = view('emails/order_confirmation', [
            'order'         => $order,
            'customer_name' => $toName,
        ]);

        return $this->send($toEmail, $toName, $subject, $body, 'sendOrderConfirmation');
    }

    /**
     * Send an order status-change email when an admin updates an order.
     */
    public function sendOrderStatusUpdate(array $order, string $toEmail, string $toName): bool
    {
        $statusLabel = ucfirst($order['status']);
        $subject     = "Order Update – {$order['order_number']} is now {$statusLabel}";
        $body        = view('emails/order_status_update', [
            'order'         => $order,
            'customer_name' => $toName,
        ]);

        return $this->send($toEmail, $toName, $subject, $body, 'sendOrderStatusUpdate');
    }

    // ─── Internal ──────────────────────────────────────────────────────────────

    private function send(string $toEmail, string $toName, string $subject, string $body, string $context): bool
    {
        try {
            $email = \Config\Services::email();
            $this->configure($email);

            $email->setTo($toEmail, $toName);
            $email->setSubject($subject);
            $email->setMessage($body);

            $sent = $email->send(false);

            if (! $sent) {
                log_message('error', "EmailService::{$context} failed – " . $email->printDebugger(['headers']));
            }

            return $sent;
        } catch (\Throwable $e) {
            log_message('error', "EmailService::{$context} exception – " . $e->getMessage());
            return false;
        }
    }

    private function configure(\CodeIgniter\Email\Email $email): void
    {
        $protocol = env('EMAIL_PROTOCOL', 'mail');

        $config = [
            'protocol'  => $protocol,
            'fromEmail' => $this->fromEmail,
            'fromName'  => $this->fromName,
            'mailType'  => 'html',
            'charset'   => 'utf-8',
        ];

        if ($protocol === 'smtp') {
            $config['SMTPHost']    = env('EMAIL_HOST', '');
            $config['SMTPUser']    = env('EMAIL_USER', '');
            $config['SMTPPass']    = env('EMAIL_PASS', '');
            $config['SMTPPort']    = (int) env('EMAIL_PORT', 587);
            $config['SMTPCrypto']  = env('EMAIL_CRYPTO', 'tls');
            $config['SMTPTimeout'] = 10;
        }

        $email->initialize($config);
    }
}
