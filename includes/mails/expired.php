<?php
global $license_key, $body, $email, $first_name, $date_expiry, $expiration_reminder_text;
$body ='
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta content="width=device-width" name="viewport" />
    <!--[if !mso]><!-->
    <meta content="IE=edge" http-equiv="X-UA-Compatible" />
    <!--<![endif]-->
    <title></title>
    <!--[if !mso]><!-->
    <!--<![endif]-->
    <style type="text/css"> body { margin: 0; padding: 0; } table, td, tr { vertical-align: top; border-collapse: collapse; } * { line-height: inherit; } a[x-apple-data-detectors=true] { color: inherit !important; text-decoration: none !important; } .ie-browser table { table-layout: fixed; } [owa] .img-container div, [owa] .img-container button { display: block !important; } [owa] .fullwidth button { width: 100% !important; } [owa] .block-grid .col { display: table-cell; float: none !important; vertical-align: top; } .ie-browser .block-grid, .ie-browser .num12, [owa] .num12, [owa] .block-grid { width: 650px !important; } .ie-browser .mixed-two-up .num4, [owa] .mixed-two-up .num4 { width: 216px !important; } .ie-browser .mixed-two-up .num8, [owa] .mixed-two-up .num8 { width: 432px !important; } .ie-browser .block-grid.two-up .col, [owa] .block-grid.two-up .col { width: 324px !important; } .ie-browser .block-grid.three-up .col, [owa] .block-grid.three-up .col { width: 324px !important; } .ie-browser .block-grid.four-up .col [owa] .block-grid.four-up .col { width: 162px !important; } .ie-browser .block-grid.five-up .col [owa] .block-grid.five-up .col { width: 130px !important; } .ie-browser .block-grid.six-up .col, [owa] .block-grid.six-up .col { width: 108px !important; } .ie-browser .block-grid.seven-up .col, [owa] .block-grid.seven-up .col { width: 92px !important; } .ie-browser .block-grid.eight-up .col, [owa] .block-grid.eight-up .col { width: 81px !important; } .ie-browser .block-grid.nine-up .col, [owa] .block-grid.nine-up .col { width: 72px !important; } .ie-browser .block-grid.ten-up .col, [owa] .block-grid.ten-up .col { width: 60px !important; } .ie-browser .block-grid.eleven-up .col, [owa] .block-grid.eleven-up .col { width: 54px !important; } .ie-browser .block-grid.twelve-up .col, [owa] .block-grid.twelve-up .col { width: 50px !important; } </style>
    <style id="media-query" type="text/css"> @media only screen and (min-width: 670px) { .block-grid { width: 650px !important; } .block-grid .col { vertical-align: top; } .block-grid .col.num12 { width: 650px !important; } .block-grid.mixed-two-up .col.num3 { width: 162px !important; } .block-grid.mixed-two-up .col.num4 { width: 216px !important; } .block-grid.mixed-two-up .col.num8 { width: 432px !important; } .block-grid.mixed-two-up .col.num9 { width: 486px !important; } .block-grid.two-up .col { width: 325px !important; } .block-grid.three-up .col { width: 216px !important; } .block-grid.four-up .col { width: 162px !important; } .block-grid.five-up .col { width: 130px !important; } .block-grid.six-up .col { width: 108px !important; } .block-grid.seven-up .col { width: 92px !important; } .block-grid.eight-up .col { width: 81px !important; } .block-grid.nine-up .col { width: 72px !important; } .block-grid.ten-up .col { width: 65px !important; } .block-grid.eleven-up .col { width: 59px !important; } .block-grid.twelve-up .col { width: 54px !important; } } @media (max-width: 670px) { .block-grid, .col { min-width: 320px !important; max-width: 100% !important; display: block !important; } .block-grid { width: 100% !important; } .col { width: 100% !important; } .col>div { margin: 0 auto; } img.fullwidth, img.fullwidthOnMobile { max-width: 100% !important; } .no-stack .col { min-width: 0 !important; display: table-cell !important; } .no-stack.two-up .col { width: 50% !important; } .no-stack .col.num4 { width: 33% !important; } .no-stack .col.num8 { width: 66% !important; } .no-stack .col.num4 { width: 33% !important; } .no-stack .col.num3 { width: 25% !important; } .no-stack .col.num6 { width: 50% !important; } .no-stack .col.num9 { width: 75% !important; } .video-block { max-width: none !important; } .mobile_hide { min-height: 0px; max-height: 0px; max-width: 0px; display: none; overflow: hidden; font-size: 0px; } .desktop_hide { display: block !important; max-height: none !important; } } </style>
</head>

<body class="clean-body" style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; background-color: #F1F3F3;">
    <!--[if IE]><div class="ie-browser"><![endif]-->
    <table bgcolor="#F1F3F3" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="table-layout: fixed; vertical-align: top; min-width: 320px; Margin: 0 auto; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #F1F3F3; width: 100%;" valign="top" width="100%">
        <tbody>
            <tr style="vertical-align: top;" valign="top">
                <td style="word-break: break-word; vertical-align: top; border-collapse: collapse;" valign="top">
                    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color:#F1F3F3"><![endif]-->
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:transparent;width:650px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
                                <div class="col num12" data-body-width-son="650" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                                            <!--<![endif]-->
                                            <table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
                                                <tbody>
                                                    <tr style="vertical-align: top;" valign="top">
                                                        <td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 10px; padding-right: 10px; padding-bottom: 10px; padding-left: 10px; border-collapse: collapse;" valign="top">
                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="15" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; border-top: 0px solid transparent; height: 15px;" valign="top" width="100%">
                                                                <tbody>
                                                                    <tr style="vertical-align: top;" valign="top">
                                                                        <td height="15" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; border-collapse: collapse;" valign="top"><span></span></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: #FFFFFF;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:#FFFFFF;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:#FFFFFF"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:#FFFFFF;width:650px; border-top: 0px solid transparent; border-left: 8px solid #F1F3F3; border-bottom: 0px solid transparent; border-right: 8px solid #F1F3F3;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 50px; padding-left: 50px; padding-top:50px; padding-bottom:5px;background-color:#FFFFFF;"><![endif]-->
                                <div class="col num12" data-body-width-son="634" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="background-color:#FFFFFF;width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:8px solid #F1F3F3; border-bottom:0px solid transparent; border-right:8px solid #F1F3F3; padding-top:50px; padding-bottom:5px; padding-right: 50px; padding-left: 50px;">
                                            <!--<![endif]-->
                                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
                                            <div style="color:#676767;font-family:Arial, Helvetica, sans-serif;line-height:120%;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
                                                <div style="font-size: 12px; line-height: 14px; font-family: Arial, Helvetica, sans-serif; color: #676767;">
                                                    <p style="font-size: 14px; line-height: 16px; text-align: left; margin: 0;">
                                                        <strong>Hello there, '. $first_name . '!</strong></p>
                                                </div>
                                            </div>
                                            <!--[if mso]></td></tr></table><![endif]-->
                                            <div class="mobile_hide">
                                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
                                                <div style="color:#1F1D1B;font-family:Helvetica, Arial, sans-serif;line-height:120%;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
                                                    <div style="line-height: 14px; font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1F1D1B;">
                                                        <p style="line-height: 60px; font-size: 12px; margin: 0;"><span style="font-size: 50px;">Your license key has
                                                                expired!</span></p>
                                                    </div>
                                                </div>
                                                <!--[if mso]></td></tr></table><![endif]-->
                                            </div>
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: #FFFFFF;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:#FFFFFF;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:#FFFFFF"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:#FFFFFF;width:650px; border-top: 0px solid transparent; border-left: 8px solid #F1F3F3; border-bottom: 0px solid transparent; border-right: 8px solid #F1F3F3;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:25px; padding-bottom:0px;background-color:#FFFFFF;"><![endif]-->
                                <div class="col num12" data-body-width-son="634" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="background-color:#FFFFFF;width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:8px solid #F1F3F3; border-bottom:0px solid transparent; border-right:8px solid #F1F3F3; padding-top:25px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
                                            <!--<![endif]-->
                                            <div></div>
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: #FFFFFF;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:#FFFFFF;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:#FFFFFF"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:#FFFFFF;width:650px; border-top: 0px solid transparent; border-left: 8px solid #F1F3F3; border-bottom: 0px solid transparent; border-right: 8px solid #F1F3F3;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 50px; padding-left: 50px; padding-top:35px; padding-bottom:5px;background-color:#FFFFFF;"><![endif]-->
                                <div class="col num12" data-body-width-son="634" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="background-color:#FFFFFF;width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:8px solid #F1F3F3; border-bottom:0px solid transparent; border-right:8px solid #F1F3F3; padding-top:35px; padding-bottom:5px; padding-right: 50px; padding-left: 50px;">
                                            <!--<![endif]-->
                                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 15px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
                                            <div style="color:#555555;font-family:Arial, Helvetica, sans-serif;line-height:150%;padding-top:15px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
                                                <div style="line-height: 18px; font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #555555;">
                                                    <p style="line-height: 25px; text-align: left; font-size: 12px; margin: 0;">
                                                        <span style="font-size: 17px; mso-ansi-font-size: 18px;">
                                                            '. $expiration_reminder_text .'
                                                            </span></p>
                                                     <p style="line-height: 25px; text-align: left; font-size: 12px; margin: 0;">
                                                     <span><br><br>
                                                     <strong>License info:</strong><br>
                                                     License key: ' . $license_key . '<br>
                                                     Expiration: ' . $date_expiry . '<br>
                                                      </span></p><br><br>
                                                      <p> Go to <a href="'. get_permalink(get_option('woocommerce_myaccount_page_id')).'">My account</a></p>
                                                </div>
                                            </div>
                                            <!--[if mso]></td></tr></table><![endif]-->
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: #FFFFFF;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:#FFFFFF;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:#FFFFFF"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:#FFFFFF;width:650px; border-top: 0px solid transparent; border-left: 8px solid #F1F3F3; border-bottom: 0px solid transparent; border-right: 8px solid #F1F3F3;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:45px;"><![endif]-->
                                <div class="col num12" data-body-width-son="634" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:8px solid #F1F3F3; border-bottom:0px solid transparent; border-right:8px solid #F1F3F3; padding-top:0px; padding-bottom:45px; padding-right: 0px; padding-left: 0px;">
                                            <!--<![endif]-->
                                            <div></div>
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: #FFFFFF;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:#FFFFFF;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:#FFFFFF"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:#FFFFFF;width:650px; border-top: 0px solid transparent; border-left: 8px solid #F1F3F3; border-bottom: 0px solid transparent; border-right: 8px solid #F1F3F3;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 50px; padding-left: 50px; padding-top:30px; padding-bottom:30px;background-color:#FFFFFF;"><![endif]-->
                                <div class="col num12" data-body-width-son="634" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="background-color:#FFFFFF;width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:8px solid #F1F3F3; border-bottom:0px solid transparent; border-right:8px solid #F1F3F3; padding-top:30px; padding-bottom:30px; padding-right: 50px; padding-left: 50px;">
                                            <!--<![endif]-->
                                            <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
                                            <div style="color:#555555;font-family:Arial, Helvetica, sans-serif;line-height:120%;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
                                                <div style="font-size: 12px; line-height: 14px; font-family: Arial, Helvetica, sans-serif; color: #555555;">
                                                    <p style="font-size: 14px; line-height: 16px; text-align: left; margin: 0;">
                                                        '. get_bloginfo('name') .' / <strong><a href="'. get_bloginfo('url').'" rel="noopener" style="text-decoration: underline; color: #66BECD;" target="_blank">'. get_bloginfo('admin_email').'</a></strong></p>
                                                </div>
                                            </div>
                                            <!--[if mso]></td></tr></table><![endif]-->
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <div style="background-color:transparent;">
                        <div class="block-grid" data-body-width-father="650px" rel="col-num-container-box-father" style="Margin: 0 auto; min-width: 320px; max-width: 650px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
                            <div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:650px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
                                <!--[if (mso)|(IE)]><td align="center" width="650" style="background-color:transparent;width:650px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
                                <div class="col num12" data-body-width-son="650" rel="col-num-container-box-son" style="min-width: 320px; max-width: 650px; display: table-cell; vertical-align: top;">
                                    <div style="width:100% !important;">
                                        <!--[if (!mso)&(!IE)]><!-->
                                        <div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                                            <!--<![endif]-->
                                            <table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
                                                <tbody>
                                                    <tr style="vertical-align: top;" valign="top">
                                                        <td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 10px; padding-right: 10px; padding-bottom: 10px; padding-left: 10px; border-collapse: collapse;" valign="top">
                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="15" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; border-top: 0px solid transparent; height: 15px;" valign="top" width="100%">
                                                                <tbody>
                                                                    <tr style="vertical-align: top;" valign="top">
                                                                        <td height="15" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; border-collapse: collapse;" valign="top"><span></span></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!--[if (!mso)&(!IE)]><!-->
                                        </div>
                                        <!--<![endif]-->
                                    </div>
                                </div>
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
                            </div>
                        </div>
                    </div>
                    <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                </td>
            </tr>
        </tbody>
    </table>
    <!--[if (IE)]></div><![endif]-->
</body>

</html>';