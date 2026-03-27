<?php
/**
 * Email Header - Same for all emails
 * 
 * Available variables:
 * $badge_text - Email type badge
 */
?>
<tr>
    <td style="padding:36px 36px 18px 36px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="left" style="vertical-align:middle;">
                    <span style="font-size:20px; font-weight:480; color:#142433; letter-spacing:-0.02em; border-left:3px solid #1763cf; padding-left:14px;">inscripcionsacmi</span>
                </td>
                <td align="right">
                    <span style="font-size:12px; font-weight:500; color:#ffffff; background-color:#1763cf; padding:6px 16px 6px 16px; border-radius:40px; letter-spacing:0.2px;"><?php echo $badge_text; ?></span>
                </td>
            </tr>
        </table>
    </td>
</tr>