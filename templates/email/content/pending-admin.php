<?php
/**
 * Pending Admin Email Template
 */
?>
<!-- Icon -->
<tr>
    <td align="center" style="padding:36px 36px 12px 36px;">
        <span style="font-size:54px; line-height:1; filter: drop-shadow(0 4px 6px rgba(23,99,207,0.12));">⏳</span>
    </td>
</tr>

<!-- Title & Message -->
<tr>
    <td align="center" style="padding:0 36px 8px;">
        <h2 style="font-size:26px; font-weight:400; color:#142433; margin:0 0 8px 0;">Pending Registration</h2>
        <p style="font-size:16px; color:#2b4055; margin:0; line-height:1.5;">
            <strong style="color:#1763cf;"><?php echo $user_name; ?></strong> (<?php echo $user_email; ?>) started a registration for <strong><?php echo $congress_name; ?></strong>
        </p>
    </td>
</tr>

<!-- Button -->
<?php
$button_text = 'view in admin';
$button_url = $admin_url;
include CRS_PLUGIN_DIR . 'templates/email/parts/button.php';
?>