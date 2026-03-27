<?php
/**
 * Invoice Email Template
 */
?>
<!-- Icon -->
<tr>
    <td align="center" style="padding:36px 36px 12px 36px;">
        <span style="font-size:54px; line-height:1; filter: drop-shadow(0 4px 6px rgba(23,99,207,0.12));">📄</span>
    </td>
</tr>

<!-- Title & Message -->
<tr>
    <td align="center" style="padding:0 36px 8px;">
        <h2 style="font-size:26px; font-weight:400; color:#142433; margin:0 0 8px 0;">Invoice #<?php echo $invoice_number; ?></h2>
        <p style="font-size:16px; color:#2b4055; margin:0; line-height:1.5;">
            Your invoice for <strong style="color:#1763cf;"><?php echo $congress_name; ?></strong> is ready.
        </p>
    </td>
</tr>

<!-- Button -->
<?php
$button_text = 'download invoice';
$button_url = $invoice_url;
include CRS_PLUGIN_DIR . 'templates/email/parts/button.php';
?>

<!-- Invoice Details -->
<tr>
    <td style="padding:4px 36px 28px 36px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fafdff; border-radius:22px; border:1px solid #d5e5fa;">
            <tr>
                <td style="padding:24px;">
                    <table width="100%" cellpadding="8">
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Invoice #</td>
                            <td style="font-size:15px; color:#0f3147;"><?php echo $invoice_number; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Amount</td>
                            <td style="font-size:18px; color:#1763cf; font-weight:550;"><?php echo $invoice_amount; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">Due date</td>
                            <td style="font-size:15px; color:#0f3147;"><?php echo $invoice_date; ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>