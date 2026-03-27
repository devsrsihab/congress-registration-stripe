<?php
/**
 * Registration User Email Template
 */
?>
<!-- Icon -->
<tr>
    <td align="center" style="padding:36px 36px 12px 36px;">
        <span style="font-size:54px; line-height:1; filter: drop-shadow(0 4px 6px rgba(23,99,207,0.12));">🫱🏼‍🫲🏽</span>
    </td>
</tr>

<!-- Title & Message -->
<tr>
    <td align="center" style="padding:0 36px 8px;">
        <h2 style="font-size:26px; font-weight:400; color:#142433; margin:0 0 8px 0; letter-spacing:-0.3px;"><?php echo $user_name; ?>, you're in.</h2>
        <p style="font-size:16px; color:#2b4055; margin:0; line-height:1.5; font-weight:350; max-width:380px;">
            We've saved your spot for <strong style="font-weight:550; color:#1763cf;"><?php echo $congress_name; ?></strong> — the community in Tenerife awaits.
        </p>
    </td>
</tr>

<!-- Button -->
<?php
$button_text = 'view my registrations';
$button_url = $registrations_url;
include CRS_PLUGIN_DIR . 'templates/email/parts/button.php';
?>

<!-- Details Card -->
<tr>
    <td style="padding:4px 36px 28px 36px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fafdff; border-radius:22px; border:1px solid #d5e5fa;">
            <tr>
                <td style="padding:24px 24px 20px;">
                    <table width="100%" cellpadding="8" cellspacing="0">
                        <tr>
                            <td style="font-size:14px; color:#4b677d; padding-bottom:12px; width:45%; border-bottom:1px dashed #cbdae8;">booking reference</td>
                            <td style="font-size:15px; color:#0f3147; font-weight:550; padding-bottom:12px; border-bottom:1px dashed #cbdae8;"><?php echo $booking_number; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d; padding-top:12px;">congress</td>
                            <td style="font-size:15px; color:#1763cf; font-weight:520;"><?php echo $congress_name; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">dates</td>
                            <td style="font-size:15px; color:#1b3f58;"><?php echo $congress_start; ?> – <?php echo $congress_end; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d;">location</td>
                            <td style="font-size:15px; color:#1b3f58;"><?php echo $congress_location; ?></td>
                        </tr>
                        <tr>
                            <td style="font-size:14px; color:#4b677d; padding-top:8px;">total</td>
                            <td style="font-size:22px; color:#0f3147; font-weight:550;"><?php echo $total_amount; ?></td>
                        </tr>
                    </table>
                    
                    <!-- Extra note -->
                    <div style="margin-top:20px; background:#e2edff; border-radius:60px; padding:8px 18px; display:inline-block; font-size:13px; color:#1763cf;">
                        ⚡ welcome kit included
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>