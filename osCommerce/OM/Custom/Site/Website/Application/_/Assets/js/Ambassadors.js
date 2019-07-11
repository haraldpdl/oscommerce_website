/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

'use strict';

if (typeof OSCOM.a._ === 'undefined') OSCOM.a._ = {};

OSCOM.a._.Ambassadors = {
    execute: function() {
        let address;
        let paymentButtonText = $('#ambPaymentButton').html();
        let hasError = false;
        let gSecurityCheck;
        let panelCurrent = 'Start';
        let panelLast;

        let btDropinInstance;

        if ('MutationObserver' in window) {
            let paymentButtonObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.oldValue === null) {
                        $('#ambPaymentButton').siblings('svg.svg-inject').addClass('d-none');

                        if ($('#ambPaymentButton').html() != $('#ambPaymentButton').data('processing-text')) {
                            $('#ambPaymentButton').removeClass('btn-success').addClass('btn-secondary');
                        }
                    } else {
                        if ($('#ambPaymentButton').html() != $('#ambPaymentButton').data('processing-text')) {
                            $('#ambPaymentButton').siblings('svg.svg-inject').removeClass('d-none');
                            $('#ambPaymentButton').removeClass('btn-secondary').addClass('btn-success');
                        } else {
                            if ($('#ambPaymentButton').data('original-text')) {
                                $('#ambPaymentButton').html($('#ambPaymentButton').data('original-text')).removeData('original-text');
                            }

                            $('#ambPaymentButton').siblings('svg.svg-inject').addClass('d-none');
                            $('#ambPaymentButton').removeClass('btn-success').addClass('btn-secondary');
                        }
                    }
                });
            });

            paymentButtonObserver.observe(document.getElementById('ambPaymentButton'), {
                attributes: true,
                attributeOldValue: true,
                attributeFilter: [
                    'disabled'
                ]
            });
        }

        function showPanel(panel) {
            panelLast = panelCurrent;
            panelCurrent = panel;

            $('#amb' + panelLast).addClass('d-none');
            $('#amb' + panelCurrent).removeClass('d-none');

            $('#ambTitle')[0].scrollIntoView({behavior: "smooth"});
        }

        function showMessage(id, message, type) {
            if (typeof type === 'undefined') {
                type = 'danger';
            }

            $('#' + id + ' div.alert-' + type).append('<p>' + message + '</p>').removeClass('d-none');
        }

        function showInlineError(id, error) {
            hasError = true;

            if (typeof error === 'undefined') {
                error = $('#' + id).parent().find('.form-text.text-muted').html();
            }

            $('#' + id).parent().find('.invalid-feedback').html(error);

            $('#' + id).parent().find('.form-text.text-muted').addClass('d-none');
            $('#' + id).addClass('is-invalid');
        }

        function clearInlineError(id) {
            $('#' + id).parent().find('.invalid-feedback').html('');
            $('#' + id).removeClass('is-invalid');
            $('#' + id).parent().find('.form-text.text-muted').removeClass('d-none');
        }

        function prefillBillingAddress() {
            if (typeof OSCOM.a.vars.billing_address !== 'undefined') {
                $('#cFirstName').val(OSCOM.a.vars.billing_address.firstname);
                $('#cLastName').val(OSCOM.a.vars.billing_address.lastname);
                $('#cStreetAddress').val(OSCOM.a.vars.billing_address.street);
                $('#cStreetAddress2').val(OSCOM.a.vars.billing_address.street2);
                $('#cCity').val(OSCOM.a.vars.billing_address.city);
                $('#cZip').val(OSCOM.a.vars.billing_address.zip);
                $('#cCountry').val(OSCOM.a.vars.billing_address.country_iso_2).trigger('change');

                if (OSCOM.a.vars.billing_address.country_iso_2 in OSCOM.a.vars.zones) {
                    $('#cStateSelect').val(OSCOM.a.vars.billing_address.zone_code).trigger('change');
                } else {
                    $('#cState').val(OSCOM.a.vars.billing_address.state);
                }
            }
        }

        $('#currencySelector').on('change', function(e) {
            if (typeof OSCOM.a.vars.ambPrices[$(this).val()] !== 'undefined') {
                $('#ambPrice').html(OSCOM.a.vars.ambPrices[$(this).val()]);

                OSCOM.currency = $(this).val();

                Cookies.set('oscom_currency', $(this).val(), {expires: 365});
            }
        });

        $('#cCountry').on('change', function(e) {
            if ($(this).val() === '') {
                $('#cState, #cStateSelect').addClass('d-none');
                $('#cState').siblings('.form-control-plaintext').removeClass('d-none');
                $('#cState').siblings('label').removeAttr('for');
            } else if ($(this).val() in OSCOM.a.vars.zones) {
                $('#cStateSelect').empty();

                $('#cStateSelect').append('<option value="">' + OSCOM.lang.amb_select_please_select + '</option>');

                OSCOM.a.vars.zones[$(this).val()].forEach(function (value) {
                    $('#cStateSelect').append('<option value="' + value.code + '">' + value.title + '</option>');
                });

                $('#cState').siblings('.form-control-plaintext').addClass('d-none');
                $('#cState').addClass('d-none');
                $('#cStateSelect').removeClass('d-none');
                $('#cState').siblings('label').attr('for', 'cStateSelect');
            } else {
                $('#cState').siblings('.form-control-plaintext').addClass('d-none');
                $('#cStateSelect').addClass('d-none');
                $('#cState').removeClass('d-none');
                $('#cState').siblings('label').attr('for', 'cState');
            }
        });

        $('#ambStartButton').on('click', function(e) {
            e.preventDefault();

            if (OSCOM.a.vars.loggedIn === true) {
                prefillBillingAddress();

                showPanel('BillingAddress');
            } else {
                showPanel('Login');
            }
        });

        $('#ambCreateAccountLink').on('click', function(e) {
            e.preventDefault();

            showPanel('CreateAccount');

            if (typeof grecaptcha === 'undefined') {
                let script = document.createElement('script');
                script.src = 'https://www.google.com/recaptcha/api.js?hl=' + OSCOM.a.vars.language_code + '&render=explicit&onload=OSCOM_runRecaptcha';
                document.body.append(script);

                script.onerror = function() {
                    showMessage('ambCreateAccount', OSCOM.lang.amb_error_create_general);
                };

                return false;
            }
        });

        $('#ambLoginLink').on('click', function(e) {
            e.preventDefault();

            showPanel('Login');
        });

        $('#ambLoginForm').submit(function(e) {
            e.preventDefault();

            if ($('#ambLoginButton').prop('disabled') === true) {
                return false;
            }

            $('#ambLogin div.alert').empty().addClass('d-none');

            hasError = false;

            $('#ambLoginButton').prop('disabled', true).data('original-text', $('#ambLoginButton').html()).html($('#ambLoginButton').data('processing-text'));

            $('#cUsername').val($('#cUsername').val().trim());

            clearInlineError('cUsername');

            if (($('#cUsername').val().length < 3) || ($('#cUsername').val().length > 26)) {
                showInlineError('cUsername', OSCOM.lang.amb_error_login_username);
            }

            clearInlineError('cPassword');

            if (($('#cPassword').val().length < 3) || ($('#cPassword').val().length > 32)) {
                showInlineError('cPassword', OSCOM.lang.amb_error_login_password);
            }

            if (hasError === true) {
                $('#ambLoginButton').html($('#ambLoginButton').data('original-text')).removeData('original-text').prop('disabled', false);
            } else {
                let params = {
                    public_token: $('#ambLoginForm input[name="public_token"]').val(),
                    username: $('#cUsername').val(),
                    password: $('#cPassword').val(),
                    sendVerification: '1',
                    addressType: 'billing'
                };

                $.post(OSCOM.a.vars.rpc_login_url, params, function (result) {
                    if (typeof result.rpcStatus !== 'undefined') {
                        if (result.rpcStatus === 1) {
                            OSCOM.a.vars.loggedIn = true;

                            if (typeof result.address !== 'undefined') {
                                OSCOM.a.vars.billing_address = result.address;

                                prefillBillingAddress();
                            }

                            showPanel('BillingAddress');
                        } else {
                            if ((typeof result.errorCode !== 'undefined') && (result.errorCode === 'not_verified')) {
                                $('#ambVerifyEmailAddress').html(result.email);

                                showPanel('VerifyAccount');
                            } else {
                                let e = (typeof result.errors !== 'undefined') ? result.errors : [];

                                if (e.length < 1) {
                                    e.push(OSCOM.lang.amb_error_login_general);
                                }

                                e.forEach(function(v) {
                                    showMessage('ambLogin', v);
                                });
                            }
                        }
                    } else {
                        showMessage('ambLogin', OSCOM.lang.amb_error_general);
                    }
                }, 'json').fail(function() {
                    showMessage('ambLogin', OSCOM.lang.amb_error_general);
                }).always(function() {
                    $('#ambLoginButton').html($('#ambLoginButton').data('original-text')).removeData('original-text').prop('disabled', false);
                });
            }
        });

        $('#ambVerifyAccountForm').submit(function(e) {
            e.preventDefault();

            if ($('#ambVerifyAccountButton').prop('disabled') === true) {
                return false;
            }

            $('#ambVerifyAccount div.alert').empty().addClass('d-none');

            hasError = false;

            $('#ambVerifyAccountButton').prop('disabled', true).data('original-text', $('#ambVerifyAccountButton').html()).html($('#ambVerifyAccountButton').data('processing-text'));

            $('#cvUserId').val($('#cvUserId').val().trim());

            clearInlineError('cvUserId');

            if (($('#cvUserId').val().match(/^\d+$/g) === null) || ($('#cvUserId').val() < 1)) {
                showInlineError('cvUserId', OSCOM.lang.amb_error_verify_user_id);
            }

            $('#cvKey').val($('#cvKey').val().trim());

            clearInlineError('cvKey');

            if (($('#cvKey').val().length !== 32) || ($('#cvKey').val().match(/^[a-zA-Z0-9\-\_]+$/g) === null)) {
                showInlineError('cvKey', OSCOM.lang.amb_error_verify_user_key);
            }

            if (hasError === true) {
                $('#ambVerifyAccountButton').html($('#ambVerifyAccountButton').data('original-text')).removeData('original-text').prop('disabled', false);
            } else {
                let params = {
                    public_token: $('#ambVerifyAccountForm input[name="public_token"]').val(),
                    user_id: $('#cvUserId').val(),
                    key: $('#cvKey').val()
                };

                $.post(OSCOM.a.vars.rpc_verify_url, params, function (result) {
                    if (typeof result.rpcStatus !== 'undefined') {
                        if (result.rpcStatus === 1) {
                            let login_params;

                            if (panelLast === 'Login') {
                                login_params = {
                                    public_token: $('#ambLoginForm input[name="public_token"]').val(),
                                    username: $('#cUsername').val(),
                                    password: $('#cPassword').val()
                                };
                            } else if (panelLast === 'CreateAccount') {
                                login_params = {
                                    public_token: $('#ambCreateAccountForm input[name="public_token"]').val(),
                                    username: $('#cnUsername').val(),
                                    password: $('#cnPassword').val()
                                };
                            }

                            if (typeof login_params.username !== 'undefined') {
                                $.post(OSCOM.a.vars.rpc_login_url, login_params, function (login_result) {
                                    if ((typeof login_result.rpcStatus !== 'undefined') && (login_result.rpcStatus === 1)) {
                                        OSCOM.a.vars.loggedIn = true;

                                        if (typeof login_result.address !== 'undefined') {
                                            OSCOM.a.vars.billing_address = login_result.address;

                                            prefillBillingAddress();
                                        }

                                        showPanel('BillingAddress');
                                    } else {
                                        showPanel('Login');
                                    }
                                }, 'json').fail(function() {
                                    showPanel('Login');
                                }).always(function() {
                                    $('#ambVerifyAccountButton').html($('#ambVerifyAccountButton').data('original-text')).removeData('original-text').prop('disabled', false);
                                });
                            } else {
                                showPanel('Login');
                            }
                        } else {
                            if ((typeof result.errorCode !== 'undefined') && (result.errorCode === 'already_verified')) {
                                showMessage('ambLogin', 'already_verified');

                                $('#cvUserId, #cvKey, #cUsername, #cPassword').val('');

                                showPanel('Login');
                            } else {
                                let e = (typeof result.errors !== 'undefined') ? result.errors : [];

                                if (e.length < 1) {
                                    e.push(OSCOM.lang.amb_error_verify_general);
                                }

                                e.forEach(function(v) {
                                    showMessage('ambVerifyAccount', v);
                                });
                            }
                        }
                    } else {
                        showMessage('ambVerifyAccount', OSCOM.lang.amb_error_verify_general);
                    }
                }, 'json').fail(function() {
                    showMessage('ambVerifyAccount', OSCOM.lang.amb_error_verify_general);
                }).always(function() {
                    $('#ambVerifyAccountButton').html($('#ambVerifyAccountButton').data('original-text')).removeData('original-text').prop('disabled', false);
                });
            }
        });

        $('#ambCreateAccountForm').submit(function(e) {
            e.preventDefault();

            if ($('#ambCreateAccountButton').prop('disabled') === true) {
                return false;
            }

            $('#ambCreateAccount div.alert').empty().addClass('d-none');

            hasError = false;

            $('#ambCreateAccountButton').prop('disabled', true).data('original-text', $('#ambCreateAccountButton').html()).html($('#ambCreateAccountButton').data('processing-text'));

            $('#cnUsername').val($('#cnUsername').val().trim());

            clearInlineError('cnUsername');

            if (($('#cnUsername').val().length < 3) || ($('#cnUsername').val().length > 26)) {
                showInlineError('cnUsername');
            }

            $('#cnEmail').val($('#cnEmail').val().trim());

            clearInlineError('cnEmail');

            if (($('#cnEmail').val() === '') || ($('#cnEmail').val().match(/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/g) === null)) {
                showInlineError('cnEmail', OSCOM.lang.amb_error_create_email_address);
            }

            clearInlineError('cnPassword');

            if (($('#cnPassword').val().length < 3) || ($('#cnPassword').val().length > 32)) {
                showInlineError('cnPassword');
            }

            clearInlineError('cnSecurityCheck');

            if ((typeof OSCOM.a.vars.grecaptcharesponse === 'undefined') || (OSCOM.a.vars.grecaptcharesponse.length < 1)) {
                showInlineError('cnSecurityCheck', OSCOM.lang.amb_error_create_security_check);
            }

            if (hasError === true) {
                $('#ambCreateAccountButton').html($('#ambCreateAccountButton').data('original-text')).removeData('original-text').prop('disabled', false);
            } else {
                let params = {
                    public_token: $('#ambCreateAccountForm input[name="public_token"]').val(),
                    username: $('#cnUsername').val(),
                    email: $('#cnEmail').val(),
                    password: $('#cnPassword').val(),
                    gr_security_check: OSCOM.a.vars.grecaptcharesponse,
                    sendVerification: '1'
                };

                $.post(OSCOM.a.vars.rpc_create_url, params, function (result) {
                    if (typeof result.rpcStatus !== 'undefined') {
                        if (result.rpcStatus === 1) {
                            showMessage('ambVerifyAccount', OSCOM.lang.amb_create_verify_account, 'success');

                            $('#ambVerifyEmailAddress').html(result.email);

                            showPanel('VerifyAccount');
                        } else {
                            let e = (typeof result.errors !== 'undefined') ? result.errors : [];

                            if (e.length < 1) {
                                e.push(OSCOM.lang.amb_error_create_general);
                            }

                            e.forEach(function(v) {
                                showMessage('ambCreateAccount', v);
                            });

                            if ((typeof result.resetGSecurityCheck !== 'undefined') && (result.resetGSecurityCheck === true)) {
                                grecaptcha.reset(gSecurityCheck);
                            }
                        }
                    } else {
                        showMessage('ambCreateAccount', OSCOM.lang.amb_error_create_general);
                    }
                }, 'json').fail(function() {
                    showMessage('ambCreateAccount', OSCOM.lang.amb_error_create_general);
                }).always(function() {
                    $('#ambCreateAccountButton').html($('#ambCreateAccountButton').data('original-text')).removeData('original-text').prop('disabled', false);
                });
            }
        });

        $('#ambBillingAddressButton').on('click', function(e) {
            e.preventDefault();

            if ($('#ambBillingAddressButton').prop('disabled') === true) {
                return false;
            }

            $('#ambBillingAddress div.alert').empty().addClass('d-none');

            hasError = false;

            $('#ambBillingAddressButton').prop('disabled', true).data('original-text', $('#ambBillingAddressButton').html()).html($('#ambBillingAddressButton').data('processing-text'));

            $('#cFirstName').val($('#cFirstName').val().trim());

            clearInlineError('cFirstName');

            if ($('#cFirstName').val().length < 1) {
                showInlineError('cFirstName', OSCOM.lang.amb_error_billing_firstname);
            }

            $('#cLastName').val($('#cLastName').val().trim());

            clearInlineError('cLastName');

            if ($('#cLastName').val().length < 1) {
                showInlineError('cLastName', OSCOM.lang.amb_error_billing_lastname);
            }

            $('#cStreetAddress').val($('#cStreetAddress').val().trim());

            clearInlineError('cStreetAddress');

            if ($('#cStreetAddress').val().length < 1) {
                showInlineError('cStreetAddress', OSCOM.lang.amb_error_billing_street_address);
            }

            $('#cCity').val($('#cCity').val().trim());

            $('#cZip').val($('#cZip').val().trim());

            clearInlineError('cCountry');

            if ($('#cCountry').val() === '') {
                showInlineError('cCountry');
            }

            $('#cState').val($('#cState').val().trim());

            clearInlineError('cStateSelect');

            if (($('#cCountry').val() in OSCOM.a.vars.zones) && ($('#cStateSelect').val() === '')) {
                showInlineError('cStateSelect');
            }

            if (hasError === false) {
                address = {
                    firstname: $('#cFirstName').val(),
                    lastname: $('#cLastName').val(),
                    street: $('#cStreetAddress').val(),
                    street2: $('#cStreetAddress2').val(),
                    city: $('#cCity').val(),
                    zip: $('#cZip').val(),
                    country: $('#cCountry').val()
                };

                if ($('#cCountry').val() in OSCOM.a.vars.zones) {
                    address.zone = $('#cStateSelect').val();
                } else {
                    address.zone = $('#cState').val();
                }

                initializeBt();
            } else {
                $('#ambBillingAddressButton').html($('#ambBillingAddressButton').data('original-text')).removeData('original-text').prop('disabled', false);
            }
        });

        function initializeBt() {
            $.post(OSCOM.a.vars.rpc_braintree_client_token_url, address, function (result) {
                if ((typeof result.rpcStatus !== 'undefined') && (result.rpcStatus === 1) && (typeof result.token !== 'undefined')) {
                    $('#orderBillingAddress').html(result.addressFormatted);

                    $('#orderTable').empty();

                    $.each(result.items, function(i, value) {
                        $('#orderTable').append('<tr><td>' + value.title + '</td><td align="right">' + value.cost + '</td></tr>');
                    });

                    $.each(result.totals, function(i, value) {
                        $('#orderTable').append('<tr><td align="right">' + value.title + ':</td><td align="right">' + value.cost + '</td></tr>');
                    });

                    let blush = 'Uh, thanks for reading this <3 #vollgerne';

                    showPanel('Payment');

                    try {
                        createDropinInstance(result);
                    } catch (err) {
                        showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);

                        return false;
                    }
                }
            }, 'json').always(function() {
                $('#ambBillingAddressButton').html($('#ambBillingAddressButton').data('original-text')).removeData('original-text').prop('disabled', false);
            });
        }

        $('#ambPaymentChangeAddressButton').on('click', function(e) {
            e.preventDefault();

            btDropinInstance.teardown(function (err) {
                showPanel('BillingAddress');

                $('#ambPaymentChangeAddressButton').addClass('invisible');
            });
        });

        function createDropinInstance(result) {
            if (typeof braintree === 'undefined') {
                let script = document.createElement('script');
                script.src = result['braintree_web_dropin_url'];
                document.body.append(script);

                script.onload = function() {
                    createDropinInstance(result);
                };

                script.onerror = function() {
                    showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);

                    return false;
                };

                return false;
            }

            let total_raw = result.totals.total.cost_raw;

            let dropinParameters = {
                authorization: result.token,
                container: '#ambBtForm',
                paymentOptionPriority: ['paypal', 'card'],
/*
                card: {
                    cardholderName: true
                },
*/
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
                dropinParameters.paymentOptionPriority.push('googlePay');
                dropinParameters.googlePay = {
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

            braintree.dropin.create(dropinParameters, function (createErr, instance) {
                $('#ambPaymentChangeAddressButton').removeClass('invisible');

                if (createErr) {
                    showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);

                    return false;
                }

                btDropinInstance = instance;

                $('#ambPaymentButton').html(paymentButtonText + ' ' + result.totals.total.cost).removeClass('d-none');
                $('#ambPaymentTerms').removeClass('d-none');

                if (btDropinInstance.isPaymentMethodRequestable()) {
                    $('#ambPaymentButton').prop('disabled', false);
                }

                btDropinInstance.on('paymentMethodRequestable', function (event) {
                    if ($('#ambPaymentButton').html() != $('#ambPaymentButton').data('processing-text')) {
                        $('#ambPaymentButton').prop('disabled', false);
                    }
                });

                btDropinInstance.on('noPaymentMethodRequestable', function () {
                    $('#ambPaymentButton').prop('disabled', true);
                });
            });
        }

        $('#ambPaymentButton').on('click', function(e) {
            e.preventDefault();

            $('#ambPayment div.alert').empty().addClass('d-none');

            $('#ambPaymentButton').data('original-text', $('#ambPaymentButton').html()).html($('#ambPaymentButton').data('processing-text')).prop('disabled', true);

            btDropinInstance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {
                if (requestPaymentMethodErr) {
                    showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);

                    if ($('#ambPaymentButton').data('original-text')) {
                        $('#ambPaymentButton').html($('#ambPaymentButton').data('original-text')).removeData('original-text');
                    }

                    $('#ambPaymentButton').prop('disabled', false);

                    return false;
                }

                if ((payload.type !== 'CreditCard') || (payload.liabilityShifted === true) || (payload.liabilityShiftPossible === false)) {
                    processTransaction(payload.nonce);
                } else {
                    reinitializeBt();

                    showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);

                    return false;
                }
            });
        });

        function processTransaction(nonce) {
            let params = {
                nonce: nonce,
                address: address
            };

            $.post(OSCOM.a.vars.rpc_braintree_process_url, params, function (result) {
                if (typeof result.rpcStatus !== 'undefined') {
                    if (result.rpcStatus === 1) {
                        showPanel('Success');

                        if (typeof result.amb_profile !== 'undefined') {
                            let firmHandshakes = $('#newestAmbMembers div:first').clone();

                            $(firmHandshakes).find('a').attr('href', result.amb_profile['profile_url']);
                            $(firmHandshakes).find('img').attr({
                                src: result.amb_profile['photo_url'],
                                title: result.amb_profile['name']
                            });

                            $(firmHandshakes).addClass('animationNewAmbassador');

                            $('#newestAmbMembers div:last').remove();
                            $('#newestAmbMembers').prepend(firmHandshakes);
                        }
                    } else {
                        reinitializeBt();

                        showMessage('ambPayment', OSCOM.lang.amb_error_payment_transaction + ' ' + result.errorMessage);
                    }
                } else {
                    reinitializeBt();

                    showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);
                }
            }, 'json').fail(function() {
                reinitializeBt();

                showMessage('ambPayment', OSCOM.lang.amb_error_payment_general);
            });
        }

        function reinitializeBt() {
            btDropinInstance.clearSelectedPaymentMethod();
        }
    }
};

function OSCOM_runRecaptcha() {
    OSCOM.a._.Ambassadors.gSecurityCheck = grecaptcha.render('cnSecurityCheck', {
        sitekey: OSCOM.a.vars.recaptcha_key_public,
        callback: function(response) {
            OSCOM.a.vars.grecaptcharesponse = response;
        }
    });

    $('#ambCreateAccountLink').click();
}

if ((OSCOM.siteReq.app === '_') && (OSCOM.siteReq.actions.join('.') === 'Ambassadors')) {
    OSCOM.a._.Ambassadors.execute();
}
