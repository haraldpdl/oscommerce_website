/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

'use strict';

if (typeof OSCOM.a.Account === 'undefined') OSCOM.a.Account = {};
if (typeof OSCOM.a.Account.Partner === 'undefined') OSCOM.a.Account.Partner = {};

OSCOM.a.Account.Partner.Extend = {
    execute: function() {
        let btDropinInstance;
        let total_raw;

        let taxDescriptions = {
            'DE19MWST': OSCOM.lang.partner_extend_purchase_tax_DE19MWST_title
        };

        let pPackages;
        let pPlanKeys;
        let pPlanKeySelected;

        $('#currencySelector').on('change', function(e) {
            $('#currencySelector').prop('disabled', true);

            $('#pBtFormIndicator').removeClass('d-none');

            $('#pBillingContent, #pBtForm, #pPayButton').addClass('d-none');

            OSCOM.currency = this.value;

            if (typeof btDropinInstance !== 'undefined') {
                btDropinInstance.teardown(function() {
                    initializeBt();
                });
            }
        });

        $('#pPlanSelection').on('change', function() {
            pPlanKeySelected = this.value;

            updateOrderTable();
        });

        $('#pPlanDurationSelection').on('input', function() {
            pPlanKeySelected = pPlanKeys[this.value];

            updateOrderTable();
        });

        function showMessage(message) {
            $('#pPayAlert').append('<p>' + message + '</p>').removeClass('d-none');
        }

        function updateOrderTable() {
            let selected = pPackages.plans[pPackages.selected].levels[pPlanKeySelected];

            $('#pPlanSelectedText').html(selected.title);
            $('#pPlanSelectedPrice').html(selected.price);

            total_raw = selected.total_raw;

            if (typeof selected.tax !== 'undefined') {
                $('#orderTableTaxTitle').html(taxDescriptions[Object.keys(selected.tax)[0]] + ':');
                $('#orderTableTax').html(selected.tax[Object.keys(selected.tax)[0]]);
                $('#orderTableTaxRow').removeClass('d-none');
            } else {
                $('#orderTableTaxRow').addClass('d-none');
            }

            $('#orderTableTotal').html(selected.total);

            $('#pPayButton').html(OSCOM.lang.partner_extend_pay_button.replace('#amount#', selected.total));

            if (typeof btDropinInstance !== 'undefined') {
                btDropinInstance.updateConfiguration('threeDSecure', 'amount', selected.total_raw);
                btDropinInstance.updateConfiguration('paypal', 'amount', selected.total_raw);

                if (btDropinInstance._merchantConfiguration.paymentOptionPriority.includes('googlePay')) {
                    btDropinInstance.updateConfiguration('googlePay', 'transactionInfo', {
                        totalPriceStatus: 'FINAL',
                        totalPrice: selected.total_raw,
                        currencyCode: OSCOM.currency
                    });
                }
            }
        }

        function initializeBt() {
            $('#pPayButton').siblings().addClass('d-none');
            $('#pPayButton').addClass('d-none').prop('disabled', true);

            if ($('#pPayButton').data('original-text')) {
                $('#pPayButton').html($('#pPayButton').data('original-text')).removeData('original-text');
            }

            $.getJSON(OSCOM.a.vars.rpc_braintree_client_token_url.replace('CURRENCY_CODE', OSCOM.currency), function (result) {
                if ((typeof result.rpcStatus !== 'undefined') && (result.rpcStatus === 1) && (typeof result.token !== 'undefined')) {
                    try {
                        $('#currencySelector').val(result.currency);
                        OSCOM.currency = result.currency;
                        Cookies.set('oscom_currency', result.currency, {expires: 365});

                        pPackages = {
                            selected: result.plan,
                            plans: result.packages
                        };

                        pPlanKeys = Object.keys(pPackages.plans[pPackages.selected].levels);

                        if (typeof pPlanKeySelected === 'undefined') {
                            pPlanKeySelected = pPackages.plans[pPackages.selected].selected;
                        }

                        if (pPlanKeys.length < 13) {
                            $('#pPlanDurationSelection').parent().addClass('d-none');

                            $('#pPlanSelection').empty();

                            $.each(pPackages.plans[pPackages.selected].levels, function(lid, lvalue) {
                                $('#pPlanSelection').append($('<option/>', {
                                    value: lid,
                                    text: lvalue.title + ' ' + lvalue.price
                                }));
                            });

                            $('#pPlanSelection').val(pPlanKeySelected);
                            $('#pPlanSelection').parent().removeClass('d-none');
                        } else {
                            $('#pPlanSelection').parent().addClass('d-none');

                            $('#pPlanDurationSelection').attr('max', pPlanKeys.length - 1);
                            $('#pPlanDurationSelection').val(pPlanKeys.indexOf(pPlanKeySelected.toString()));
                            $('#pPlanDurationSelection').parent().removeClass('d-none');
                        }

                        updateOrderTable();

                        createDropinInstance(result);
                    } catch (err) {
                        $('#pBtFormIndicator').addClass('d-none')

                        showMessage(OSCOM.lang.partner_extend_error_payment_general);

                        return false;
                    }
                } else {
                    $('#pBtFormIndicator').addClass('d-none');

                    showMessage(OSCOM.lang.partner_extend_error_payment_general);
                }
            }).fail(function() {
                $('#pBtFormIndicator').addClass('d-none');

                showMessage(OSCOM.lang.partner_extend_error_payment_general);
            });
        }

        function createDropinInstance(result) {
            if (typeof braintree === 'undefined') {
                let script = document.createElement('script');
                script.src = result['braintree_web_dropin_url'];
                document.body.append(script);

                script.onload = function() {
                    createDropinInstance(result);
                };

                script.onerror = function() {
                    $('#pBtFormIndicator').addClass('d-none');

                    showMessage(OSCOM.lang.partner_extend_error_payment_general);
                };

                return false;
            }

            let btDropinParams = {
                authorization: result.token,
                container: '#pBtForm',
                paymentOptionPriority: ['paypal', 'card'],
                threeDSecure: {
                    amount: total_raw
                },
                paypal: {
                    flow: 'checkout',
                    amount: total_raw,
                    currency: OSCOM.currency,
                    displayName: 'osCommerce',
                    enableShippingAddress: false,
                    buttonStyle: {
                        size: 'small',
                        color: 'blue',
                        shape: 'rect'
                    }
                }
            };

            if (typeof result.googleMerchantId !== 'undefined') {
                btDropinParams.paymentOptionPriority.push('googlePay');

                btDropinParams.googlePay = {
                    googlePayVersion: 2,
                    merchantInfo: {
                        merchantId: result.googleMerchantId,
                        merchantName: 'osCommerce'
                    },
                    transactionInfo: {
                        totalPriceStatus: 'FINAL',
                        totalPrice: total_raw,
                        currencyCode: OSCOM.currency
                    },
                    allowedPaymentMethods: [
                        {
                            type: 'CARD',
                            parameters: {
                                billingAddressRequired: true,
                                billingAddressParameters: {
                                    format: 'FULL'
                                }
                            }
                        }
                    ]
                };
            }

            braintree.dropin.create(btDropinParams, function (createErr, instance) {
                $('#pBtFormIndicator').addClass('d-none');

                if (createErr) {
                    showMessage(OSCOM.lang.partner_extend_error_payment_general);

                    return false;
                }

                btDropinInstance = instance;

                $('#pCurrencyContent, #pBillingContent, #pBtForm, #pPayButton').removeClass('d-none');

                if ($('#currencySelector').prop('disabled') === true) {
                    $('#currencySelector').prop('disabled', false);
                }

                if (btDropinInstance.isPaymentMethodRequestable()) {
                    $('#pPayButton').siblings().removeClass('d-none');
                    $('#pPayButton').removeClass('btn-secondary').addClass('btn-success').prop('disabled', false);
                }

                btDropinInstance.on('paymentMethodRequestable', function () {
                    if ($('#pPayButton').html() != $('#pPayButton').data('processing-text')) {
                        $('#pPayButton').siblings().removeClass('d-none');
                        $('#pPayButton').removeClass('btn-secondary').addClass('btn-success').prop('disabled', false);
                    }
                });

                btDropinInstance.on('noPaymentMethodRequestable', function () {
                    $('#pPayButton').siblings().addClass('d-none');
                    $('#pPayButton').removeClass('btn-success').addClass('btn-secondary').prop('disabled', true);
                });
            });
        }

        $('#pPayButton').on('click', function(e) {
            e.preventDefault();

            $('#pPayAlert').empty().addClass('d-none');

            $('#pPayButton').siblings().addClass('d-none');
            $('#pPayButton').data('original-text', $('#pPayButton').html()).html($('#pPayButton').data('processing-text')).prop('disabled', true);

            btDropinInstance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
                if (requestPaymentMethodErr) {
                    showMessage(OSCOM.lang.partner_extend_error_payment_general);

                    if ($('#pPayButton').data('original-text')) {
                        $('#pPayButton').html($('#pPayButton').data('original-text')).removeData('original-text');
                    }

                    $('#pPayButton').siblings().removeClass('d-none');
                    $('#pPayButton').prop('disabled', false);

                    return false;
                }

                if ((payload.type !== 'CreditCard') || (payload.liabilityShifted === true) || (payload.liabilityShiftPossible === false)) {
                    processTransaction(payload.nonce);
                } else {
                    reinitializeBt();

                    showMessage(OSCOM.lang.partner_extend_error_payment_general);

                    return false;
                }
            });
        });

        function processTransaction(nonce) {
            let params = {
                nonce: nonce,
                plan: pPackages.selected,
                duration: pPlanKeySelected
            };

            $.post(OSCOM.a.vars.rpc_braintree_process_url.replace('CURRENCY_CODE', OSCOM.currency), params, function (result) {
                if (typeof result.rpcStatus !== 'undefined') {
                    if (result.rpcStatus === 1) {
                        window.location = OSCOM.a.vars.rpc_braintree_success_url;
                    } else {
                        reinitializeBt();

                        showMessage(OSCOM.lang.partner_extend_error_payment_transaction + ' ' + result.errorMessage);
                    }
                } else {
                    reinitializeBt();

                    showMessage(OSCOM.lang.partner_extend_error_payment_general);
                }
            }, 'json').fail(function() {
                reinitializeBt();

                showMessage(OSCOM.lang.partner_extend_error_payment_general);
            });
        }

        function reinitializeBt() {
            btDropinInstance.clearSelectedPaymentMethod();

            if ($('#pPayButton').data('original-text')) {
                $('#pPayButton').html($('#pPayButton').data('original-text')).removeData('original-text');
            }

            $('#pPayButton').siblings().addClass('d-none');
            $('#pPayButton').removeClass('btn-success').addClass('btn-secondary').prop('disabled', true);
        }

        initializeBt();

        let dateNow = new Date();
        let dateEnd = new Date(OSCOM.a.vars.partnerCampaign.dateEnd);

        if (dateEnd instanceof Date && !isNaN(dateEnd)) {
            $('.pcdate').html(dateEnd.toLocaleDateString(undefined, {year: 'numeric', month: 'long', day: 'numeric'}));
        }

        if (OSCOM.a.vars.partnerCampaign.status !== 1) {
            $('.pcdate').addClass('badge-danger');
        } else {
            let diffDays = Math.round(Math.abs((dateEnd.getTime() - dateNow.getTime()) / (60*60*24*1000)));

            if (diffDays > 14) {
                $('.pcdate').addClass('badge-success');
            } else {
                $('.pcdate').addClass('badge-warning');
            }
        }
    }
};

if ((OSCOM.siteReq.app === 'Account') && (OSCOM.siteReq.actions.join('.') === 'Partner.Extend')) {
    OSCOM.a.Account.Partner.Extend.execute();
}
