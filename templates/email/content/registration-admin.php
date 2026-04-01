<?php
/**
 * Registration Admin Email Template
 */
?>
<!-- Icon -->
<tr>
    <td align="center" style="padding:36px 36px 12px 36px;">
        <span style="font-size:54px; line-height:1; filter: drop-shadow(0 4px 6px rgba(23,99,207,0.12));">📋</span>
    </td>
</tr>

<!-- Title & Message -->
<tr>
    <td align="center" style="padding:0 36px 8px;">
        <h2 style="font-size:26px; font-weight:400; color:#142433; margin:0 0 8px 0;">New Registration</h2>
        <p style="font-size:16px; color:#2b4055; margin:0; line-height:1.5;">
            <strong style="color:#1763cf;"><?php echo $user_name; ?></strong> (<?php echo $user_email; ?>) registered for <strong><?php echo $congress_name; ?></strong>
        </p>
    </td>
</tr>

<!-- Button -->
<?php
$button_text = 'view in admin';
$button_url = $admin_url;
include CRS_PLUGIN_DIR . 'templates/email/parts/button.php';
?>

<!-- Details Card -->
<tr>
    <td style="padding:4px 36px 28px 36px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fafdff; border-radius:22px; border:1px solid #d5e5fa;">
            <tr>
                <td style="padding:24px;">
                    <table width="100%" cellpadding="8">
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Booking #</td>
                            <td style="font-size:15px; color:#0f3147; font-weight:550;"><?php echo $booking_number; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Amount</td>
                            <td style="font-size:15px; color:#0f3147;"><?php echo $total_amount; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Status</td>
                            <td style="font-size:15px; color:#1763cf;">Confirmed</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>