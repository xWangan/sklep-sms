<?php

use App\Payment;

$heart->register_page("payment", "PagePayment");

class PagePayment extends Page
{
    const PAGE_ID = "payment";

    function __construct()
    {
        global $lang;
        $this->title = $lang->translate('title_payment');

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $settings, $lang, $templates;

        // Sprawdzanie hashu danych przesłanych przez formularz
        if (!isset($post['sign']) || $post['sign'] != md5($post['data'] . $settings['random_key'])) {
            return $lang->translate('wrong_sign');
        }

        global $heart;

        /** @var Entity_Purchase $purchase_data */
        $purchase_data = unserialize(base64_decode($post['data']));

        // Fix: get user data again to avoid bugs linked with user wallet
        $purchase_data->user = $heart->get_user($purchase_data->user->getUid());

        if (!($purchase_data instanceof Entity_Purchase)) {
            return $lang->translate('error_occured');
        }

        if (($service_module = $heart->get_service_module($purchase_data->getService())) === null
            || !object_implements($service_module, "IService_PurchaseWeb")
        ) {
            return $lang->translate('bad_module');
        }

        // Pobieramy szczegóły zamówienia
        $order_details = $service_module->order_details($purchase_data);

        //
        // Pobieramy sposoby płatności

        $payment_methods = '';
        // Sprawdzamy, czy płatność za pomocą SMS jest możliwa
        if ($purchase_data->getPayment('sms_service') && $purchase_data->getTariff() !== null && !$purchase_data->getPayment('no_sms')) {
            $payment_sms = new Payment($purchase_data->getPayment('sms_service'));
            $payment_methods .= eval($templates->render('payment_method_sms'));
        }

        $cost_transfer = $purchase_data->getPayment('cost') !== null ? number_format($purchase_data->getPayment('cost') / 100.0,
            2) : "0.00";
        if (strlen($settings['transfer_service']) && $purchase_data->getPayment('cost') !== null
            && $purchase_data->getPayment('cost') > 1 && !$purchase_data->getPayment('no_transfer')
        ) {
            $payment_methods .= eval($templates->render("payment_method_transfer"));
        }

        if (is_logged() && $purchase_data->getPayment('cost') !== null && !$purchase_data->getPayment('no_wallet')) {
            $payment_methods .= eval($templates->render("payment_method_wallet"));
        }

        if (!$purchase_data->getPayment('no_code') && object_implements($service_module, "IService_ServiceCode")) {
            $payment_methods .= eval($templates->render("payment_method_code"));
        }

        $purchase_data = htmlspecialchars($post['data']);
        $purchase_sign = htmlspecialchars($post['sign']);

        $output = eval($templates->render("payment_form"));

        return $output;
    }
}