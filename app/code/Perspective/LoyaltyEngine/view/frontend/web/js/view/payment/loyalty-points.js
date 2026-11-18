/**
 * Loyalty Points Checkout Component communicating with Magento via GraphQL.
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/get-payment-information',
    'mage/url',
    'mage/translate'
], function ($, ko, Component, quote, customer, getPaymentInformationAction, urlBuilder, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Perspective_LoyaltyEngine/payment/loyalty-points'
        },
        loyaltyBalance: ko.observable(0.0),
        maxRedeemablePoints: ko.observable(0.0),
        maskedCartId: ko.observable(''),
        pointsToApply: ko.observable(''),
        isApplied: ko.observable(false),
        appliedPoints: ko.observable(0.0),
        isLoading: ko.observable(false),
        message: ko.observable(''),
        messageType: ko.observable(''),

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            var self = this;

            this.checkCurrentAppliedTotals();

            quote.getTotals().subscribe(function () {
                self.checkCurrentAppliedTotals();
                self.recalculateMaxRedeemable();
            });

            if (customer.isLoggedIn()) {
                this.fetchCustomerLoyaltyBalance();
            }

            return this;
        },

        /**
         * Check if customer is authenticated
         *
         * @returns {Boolean}
         */
        isLoggedIn: function () {
            return customer.isLoggedIn();
        },

        /**
         * Check if loyalty points are already applied in totals
         */
        checkCurrentAppliedTotals: function () {
            var totals = quote.getTotals()();

            if (totals && totals.total_segments) {
                var segment = totals.total_segments.find(function (s) {
                    return s.code === 'loyalty_points';
                });

                if (segment && Math.abs(parseFloat(segment.value)) > 0) {
                    var discountAmount = Math.abs(parseFloat(segment.value));
                    this.isApplied(true);
                    this.appliedPoints(discountAmount);
                    if (!this.pointsToApply()) {
                        this.pointsToApply(discountAmount.toFixed(2));
                    }
                    return;
                }
            }

            if (!this.isLoading()) {
                this.isApplied(false);
            }
        },

        /**
         * Get max redemption percentage configured in admin
         *
         * @returns {Number}
         */
        getMaxRedemptionPercentage: function () {
            if (window.checkoutConfig &&
                window.checkoutConfig.loyaltyEngine &&
                typeof window.checkoutConfig.loyaltyEngine.maxRedemptionPercentage !== 'undefined'
            ) {
                return parseFloat(window.checkoutConfig.loyaltyEngine.maxRedemptionPercentage);
            }
            return 50.0;
        },

        /**
         * Dynamic label for max redeemable points with configured percentage
         *
         * @returns {String}
         */
        getMaxRedeemableLabel: function () {
            return $t('Max Redeemable (%1% Cart Limit):').replace('%1', this.getMaxRedemptionPercentage());
        },

        /**
         * Recalculate maximum redeemable points based on configured percentage
         */
        recalculateMaxRedeemable: function () {
            var totals = quote.getTotals()(),
                subtotal = 0.0;

            if (totals) {
                subtotal = parseFloat(totals.base_subtotal || totals.subtotal || 0.0);
            }

            var maxPercent = this.getMaxRedemptionPercentage(),
                maxCartLimit = subtotal * (maxPercent / 100.0),
                available = this.loyaltyBalance();

            var maxAllowed = Math.min(available, maxCartLimit);
            this.maxRedeemablePoints(Math.max(0.0, maxAllowed));
        },

        /**
         * Fetch current customer loyalty points balance via GraphQL
         */
        fetchCustomerLoyaltyBalance: function () {
            var self = this,
                graphQlQuery = {
                    query: 'query { customer { loyalty_points { balance } } customerCart { id } }'
                };

            $.ajax({
                url: urlBuilder.build('graphql'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(graphQlQuery),
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    if (response && response.data) {
                        if (response.data.customerCart && response.data.customerCart.id) {
                            self.maskedCartId(response.data.customerCart.id);
                        }
                        if (response.data.customer && response.data.customer.loyalty_points) {
                            var balance = parseFloat(response.data.customer.loyalty_points.balance || 0.0);
                            self.loyaltyBalance(balance);
                            self.recalculateMaxRedeemable();
                        }
                    }
                }
            });
        },

        /**
         * Apply loyalty points to current cart via GraphQL mutation
         */
        applyPoints: function () {
            var self = this,
                cartId = this.maskedCartId() || quote.getQuoteId(),
                points = parseFloat(this.pointsToApply());

            this.message('');

            if (isNaN(points) || points <= 0) {
                this.messageType('error');
                this.message($t('Please enter a valid amount of loyalty points greater than 0.'));
                return;
            }

            if (points > this.loyaltyBalance()) {
                this.messageType('error');
                this.message($t('Cannot apply more points than your active balance (%1).').replace('%1', this.formatPoints(this.loyaltyBalance())));
                return;
            }

            if (points > this.maxRedeemablePoints()) {
                this.messageType('error');
                this.message(
                    $t('Cannot apply more than %1 points (maximum %2% of cart value).')
                        .replace('%1', this.formatPoints(this.maxRedeemablePoints()))
                        .replace('%2', this.getMaxRedemptionPercentage())
                );
                return;
            }

            this.isLoading(true);

            var mutation = {
                query: 'mutation ApplyPoints($cartId: String!, $points: Float!) { ' +
                    'applyLoyaltyPointsToCart(input: { cart_id: $cartId, points: $points }) { ' +
                    'applied_points discount_amount cart { id } } }',
                variables: {
                    cartId: String(cartId),
                    points: points
                }
            };

            $.ajax({
                url: urlBuilder.build('graphql'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(mutation),
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    self.isLoading(false);
                    if (response.errors && response.errors.length) {
                        self.messageType('error');
                        self.message(response.errors[0].message);
                        return;
                    }

                    if (response.data && response.data.applyLoyaltyPointsToCart) {
                        var applied = response.data.applyLoyaltyPointsToCart.applied_points;
                        self.isApplied(true);
                        self.appliedPoints(applied);
                        self.messageType('success');
                        self.message($t('Successfully applied %1 loyalty points discount.').replace('%1', self.formatPoints(applied)));

                        var deferred = $.Deferred();
                        getPaymentInformationAction(deferred);
                    }
                },
                error: function (xhr) {
                    self.isLoading(false);
                    self.messageType('error');
                    var errorMsg = $t('An error occurred while applying loyalty points.');
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.errors && parsed.errors.length) {
                            errorMsg = parsed.errors[0].message;
                        }
                    } catch (e) {}
                    self.message(errorMsg);
                }
            });
        },

        /**
         * Cancel applied loyalty points discount via GraphQL mutation
         */
        cancelPoints: function () {
            var self = this,
                cartId = this.maskedCartId() || quote.getQuoteId();

            this.isLoading(true);
            this.message('');

            var mutation = {
                query: 'mutation RemovePoints($cartId: String!) { ' +
                    'removeLoyaltyPointsFromCart(input: { cart_id: $cartId }) { cart { id } } }',
                variables: {
                    cartId: String(cartId)
                }
            };

            $.ajax({
                url: urlBuilder.build('graphql'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(mutation),
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    self.isLoading(false);
                    if (response.errors && response.errors.length) {
                        self.messageType('error');
                        self.message(response.errors[0].message);
                        return;
                    }

                    self.isApplied(false);
                    self.appliedPoints(0.0);
                    self.pointsToApply('');
                    self.messageType('success');
                    self.message($t('Loyalty points discount removed.'));

                    var deferred = $.Deferred();
                    getPaymentInformationAction(deferred);
                },
                error: function () {
                    self.isLoading(false);
                    self.messageType('error');
                    self.message($t('An error occurred while removing loyalty points.'));
                }
            });
        },

        /**
         * Form submit handler
         */
        onSubmit: function () {
            if (!this.isApplied()) {
                this.applyPoints();
            }
            return false;
        },

        /**
         * Format numeric points
         *
         * @param {Number} points
         * @returns {String}
         */
        formatPoints: function (points) {
            return parseFloat(points || 0.0).toFixed(2);
        },

        /**
         * Retrieve formatted balance label
         *
         * @returns {String}
         */
        getBalanceLabel: function () {
            return this.formatPoints(this.loyaltyBalance()) + ' ' + $t('Points');
        },

        /**
         * Retrieve formatted max redeemable value label
         *
         * @returns {String}
         */
        getMaxRedeemableValueLabel: function () {
            return this.formatPoints(this.maxRedeemablePoints()) + ' ' + $t('Points');
        },

        /**
         * Retrieve points input placeholder
         *
         * @returns {String}
         */
        getPointsInputPlaceholder: function () {
            return $t('Enter points amount');
        }
    });
});
