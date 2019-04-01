<?php

/**
 * Notification on order pending to processing status change
 *
 * @param int $order_id
 */

// woocommerce_order_status_pending
// woocommerce_order_status_failed
// woocommerce_order_status_on-hold
// woocommerce_order_status_processing
// woocommerce_order_status_completed
// woocommerce_order_status_refunded
// woocommerce_order_status_cancelled


function slm_processing_notification( $order_id ) {

    $order = wc_get_order( $order_id );

    // load the mailer class
    $mailer = WC()->mailer();

    $recipient =  $order->billing_email;
    $subject = __('Order Confirmation', 'slm');
    $content = slm_get_processing_notification_content( $order, $subject, $mailer );
    $headers = "Content-Type: text/html\r\n";

    $mailer->send( $recipient, $subject, $content, $headers );

}

add_action( 'woocommerce_order_status_completed', 'slm_processing_notification', 10, 1 );
add_action( 'woocommerce_order_status_processing', 'slm_processing_notification', 10, 1 );

/**
 * Get content html.
 *
 * @param WC_Order $order
 * @param str $heading
 * @param obj $mailer
 * @return string
 */
function slm_get_processing_notification_content( $order, $heading = false, $mailer ) {

    $template = 'emails/customer-completed-order.php';

    return wc_get_template_html( $template, array(
        'order'         => $order,
        'email_heading' => $heading,
        'sent_to_admin' => true,
        'plain_text'    => false,
        'email'         => $mailer
    ) );
}


