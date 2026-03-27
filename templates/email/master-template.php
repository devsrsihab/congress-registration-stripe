<?php
/**
 * Master Email Template
 * All emails use this same layout
 * 
 * Available variables:
 * $badge_text - Email type badge (REGISTERED, INVOICE, etc.)
 * $icon - Emoji icon
 * $title - Main heading
 * $message - Email message
 * $button_text - Button label (optional)
 * $button_url - Button URL (optional)
 * $details - Additional details HTML (optional)
 * $preheader - Hidden preheader text (optional)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Congress registration · SIDI 2026</title>
    <style>
        body, table, td, p, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; }
        table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; height:auto; line-height:100%; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f2f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased;">

    <!-- Preheader (hidden) -->
    <?php if (!empty($preheader)): ?>
    <div style="display:none; font-size:1px; color:#1763cf; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;">
        <?php echo $preheader; ?>
    </div>
    <?php endif; ?>

    <!-- MAIN WRAPPER -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f2f5f9; width:100%;">
        <tr>
            <td align="center" >
                
                <!-- main card -->
                <table width="100%" max-width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px; width:100%; background-color:#ffffff; border-radius:24px; box-shadow:0 12px 30px -8px rgba(23,99,207,0.1); border:1px solid #f0f3f7;">

                    
                    <!-- gradient spacer -->
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="height:1.5px; background:linear-gradient(90deg, #d9e6f8, #1763cf, #d9e6f8); width:100%; border-radius:2px;"></div>
                        </td>
                    </tr>

                    <!-- ========== BODY CONTENT (CHANGES PER EMAIL) ========== -->
                    <?php echo $body_content; ?>
                    <!-- ========== END BODY CONTENT ========== -->

                    
                </table>
            </td>
        </tr>
    </table>

    <!-- responsive adjustments -->
    <style>
        @media screen and (max-width:500px) {
            td[style*="padding:36px 36px"] {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            td[style*="padding:24px 36px"] {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            h2 { font-size:24px !important; }
            span[style*="font-size:54px"] { font-size:48px !important; }
        }
    </style>
    
</body>
</html>