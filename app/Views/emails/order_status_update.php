<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Update – <?= esc($order['order_number']) ?></title>
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family: Arial, Helvetica, sans-serif; color:#333333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4; padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background:#2563eb; padding:24px 30px; text-align:center;">
            <h1 style="margin:0; color:#ffffff; font-size:24px; letter-spacing:1px;">GrabGreatDeals</h1>
        </td>
    </tr>

    <!-- Status badge mapping -->
    <?php
    $statusConfig = [
        'confirmed'  => ['icon' => '✅', 'color' => '#16a34a', 'bg' => '#dcfce7', 'text' => 'Your order has been confirmed and is being prepared.'],
        'processing' => ['icon' => '⚙️', 'color' => '#d97706', 'bg' => '#fef3c7', 'text' => 'Your order is currently being processed.'],
        'shipped'    => ['icon' => '🚚', 'color' => '#2563eb', 'bg' => '#dbeafe', 'text' => 'Great news! Your order is on its way.'],
        'delivered'  => ['icon' => '📦', 'color' => '#16a34a', 'bg' => '#dcfce7', 'text' => 'Your order has been delivered. Enjoy!'],
        'cancelled'  => ['icon' => '❌', 'color' => '#dc2626', 'bg' => '#fee2e2', 'text' => 'Unfortunately, your order has been cancelled.'],
        'refunded'   => ['icon' => '💰', 'color' => '#7c3aed', 'bg' => '#ede9fe', 'text' => 'Your refund has been processed.'],
        'pending'    => ['icon' => '⏳', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'text' => 'Your order is pending confirmation.'],
    ];
    $cfg = $statusConfig[$order['status']] ?? $statusConfig['pending'];
    ?>

    <!-- Hero -->
    <tr>
        <td style="padding:30px 30px 0 30px; text-align:center;">
            <p style="font-size:36px; margin:0;"><?= $cfg['icon'] ?></p>
            <h2 style="margin:10px 0 6px; color:<?= $cfg['color'] ?>;">Order <?= ucfirst(esc($order['status'])) ?></h2>
            <p style="margin:0; color:#555; font-size:15px;">Hi <?= esc($customer_name) ?>, here's an update on your order.</p>
        </td>
    </tr>

    <!-- Status message -->
    <tr>
        <td style="padding:20px 30px 0;">
            <div style="background:<?= $cfg['bg'] ?>; border-left:4px solid <?= $cfg['color'] ?>; padding:14px 18px; border-radius:4px; font-size:14px; color:#374151;">
                <?= esc($cfg['text']) ?>
            </div>
        </td>
    </tr>

    <!-- Order meta -->
    <tr>
        <td style="padding:20px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border-radius:6px; padding:16px 20px; font-size:14px;">
                <tr>
                    <td style="padding:5px 0; color:#555; width:50%;">Order Number</td>
                    <td style="padding:5px 0; font-weight:bold;"><?= esc($order['order_number']) ?></td>
                </tr>
                <tr>
                    <td style="padding:5px 0; color:#555;">Order Date</td>
                    <td style="padding:5px 0;"><?= esc(date('F j, Y', strtotime($order['created_at']))) ?></td>
                </tr>
                <tr>
                    <td style="padding:5px 0; color:#555;">Current Status</td>
                    <td style="padding:5px 0;">
                        <span style="background:<?= $cfg['bg'] ?>; color:<?= $cfg['color'] ?>; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize;">
                            <?= esc($order['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:5px 0; color:#555;">Order Total</td>
                    <td style="padding:5px 0; font-weight:bold; color:#1e40af;">RM <?= number_format((float)$order['total'], 2) ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Items summary -->
    <?php if (! empty($order['items'])): ?>
    <tr>
        <td style="padding:20px 30px 0;">
            <h3 style="margin:0 0 10px; font-size:15px; border-bottom:2px solid #e5e7eb; padding-bottom:8px;">Items in This Order</h3>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#555;">
                <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td style="padding:6px 4px; border-bottom:1px solid #f0f0f0;">
                        <?= esc($item['product_name']) ?>
                        <?php if (! empty($item['variant_label'])): ?>
                            <span style="color:#999;"> — <?= esc($item['variant_label']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:6px 4px; text-align:center; border-bottom:1px solid #f0f0f0; width:40px;">× <?= esc($item['quantity']) ?></td>
                    <td style="padding:6px 4px; text-align:right; border-bottom:1px solid #f0f0f0; width:90px;">RM <?= number_format((float)$item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <?php endif; ?>

    <!-- Body text -->
    <tr>
        <td style="padding:24px 30px; font-size:14px; color:#555; line-height:1.7;">
            <p style="margin:0 0 12px;">If you have any questions about your order, please don't hesitate to contact our support team.</p>
            <p style="margin:0;">Best regards,<br><strong>GrabGreatDeals Team</strong></p>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:#f1f5f9; padding:16px 30px; text-align:center; font-size:12px; color:#9ca3af;">
            &copy; <?= date('Y') ?> GrabGreatDeals. All rights reserved.
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
