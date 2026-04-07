<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed – <?= esc($order['order_number']) ?></title>
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

    <!-- Hero -->
    <tr>
        <td style="padding:30px 30px 0 30px; text-align:center;">
            <p style="font-size:28px; margin:0;">✅</p>
            <h2 style="margin:10px 0 6px; color:#1e40af;">Order Confirmed!</h2>
            <p style="margin:0; color:#555; font-size:15px;">Hi <?= esc($customer_name) ?>, thank you for your order.</p>
        </td>
    </tr>

    <!-- Order summary block -->
    <tr>
        <td style="padding:24px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border-radius:6px; padding:16px 20px;">
                <tr>
                    <td style="padding:5px 0; font-size:14px; color:#555; width:50%;">Order Number</td>
                    <td style="padding:5px 0; font-size:14px; font-weight:bold;"><?= esc($order['order_number']) ?></td>
                </tr>
                <tr>
                    <td style="padding:5px 0; font-size:14px; color:#555;">Date</td>
                    <td style="padding:5px 0; font-size:14px;"><?= esc(date('F j, Y', strtotime($order['created_at']))) ?></td>
                </tr>
                <tr>
                    <td style="padding:5px 0; font-size:14px; color:#555;">Status</td>
                    <td style="padding:5px 0; font-size:14px;">
                        <span style="background:#dbeafe; color:#1d4ed8; padding:2px 10px; border-radius:20px; font-size:12px; text-transform:capitalize;">
                            <?= esc($order['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:5px 0; font-size:14px; color:#555;">Payment</td>
                    <td style="padding:5px 0; font-size:14px; text-transform:uppercase;"><?= esc($order['payment']['method'] ?? 'N/A') ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Items -->
    <?php if (! empty($order['items'])): ?>
    <tr>
        <td style="padding:24px 30px 0;">
            <h3 style="margin:0 0 12px; font-size:16px; border-bottom:2px solid #e5e7eb; padding-bottom:8px;">Order Items</h3>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:8px 10px; text-align:left; font-weight:600;">Item</th>
                        <th style="padding:8px 10px; text-align:center; font-weight:600; width:50px;">Qty</th>
                        <th style="padding:8px 10px; text-align:right; font-weight:600; width:90px;">Price</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td style="padding:10px; border-bottom:1px solid #f0f0f0;">
                            <?= esc($item['product_name']) ?>
                            <?php if (! empty($item['variant_label'])): ?>
                                <br><span style="color:#888; font-size:12px;"><?= esc($item['variant_label']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px; text-align:center; border-bottom:1px solid #f0f0f0;"><?= esc($item['quantity']) ?></td>
                        <td style="padding:10px; text-align:right; border-bottom:1px solid #f0f0f0;">RM <?= number_format((float)$item['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </td>
    </tr>
    <?php endif; ?>

    <!-- Totals -->
    <tr>
        <td style="padding:10px 30px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                <tr>
                    <td style="padding:4px 10px; text-align:right; color:#555;">Subtotal</td>
                    <td style="padding:4px 10px; text-align:right; width:90px;">RM <?= number_format((float)$order['subtotal'], 2) ?></td>
                </tr>
                <?php if ((float)$order['shipping_fee'] > 0): ?>
                <tr>
                    <td style="padding:4px 10px; text-align:right; color:#555;">Shipping</td>
                    <td style="padding:4px 10px; text-align:right;">RM <?= number_format((float)$order['shipping_fee'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$order['discount_amount'] > 0): ?>
                <tr>
                    <td style="padding:4px 10px; text-align:right; color:#16a34a;">Discount</td>
                    <td style="padding:4px 10px; text-align:right; color:#16a34a;">−RM <?= number_format((float)$order['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:8px 10px; text-align:right; font-weight:bold; font-size:16px; border-top:2px solid #e5e7eb;">Total</td>
                    <td style="padding:8px 10px; text-align:right; font-weight:bold; font-size:16px; border-top:2px solid #e5e7eb; color:#1e40af;">RM <?= number_format((float)$order['total'], 2) ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Shipping address -->
    <tr>
        <td style="padding:20px 30px 0;">
            <h3 style="margin:0 0 10px; font-size:16px; border-bottom:2px solid #e5e7eb; padding-bottom:8px;">Shipping Address</h3>
            <div style="font-size:14px; line-height:1.7; background:#f8fafc; padding:12px 16px; border-radius:6px;">
                <strong><?= esc($order['shipping_name']) ?></strong><br>
                <?= esc($order['shipping_phone']) ?><br>
                <?= nl2br(esc((string) $order['shipping_address'])) ?>
            </div>
        </td>
    </tr>

    <!-- Body text -->
    <tr>
        <td style="padding:24px 30px; font-size:14px; color:#555; line-height:1.7;">
            <p style="margin:0 0 12px;">We'll keep you updated as your order progresses. If you have any questions, simply reply to this email or contact our support team.</p>
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
