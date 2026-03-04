define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/storage',
    'mage/translate',
    'mage/cookies'
], function (Component, ko, $, storage, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Perspective_ProductQA/product/questions',
            productId: null,
            fetchUrl: '',
            submitUrl: '',
            questions: ko.observableArray([]),
            isLoading: ko.observable(true),
            isSubmitting: ko.observable(false),
            submitSuccess: ko.observable(false),
            submitError: ko.observable(false),
            submitMessage: ko.observable(''),

            // Form observables
            newQuestionName: ko.observable(''),
            newQuestionEmail: ko.observable(''),
            newQuestionText: ko.observable('')
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.loadQuestions();
            return this;
        },

        /**
         * Load questions from the server
         */
        loadQuestions: function () {
            var self = this;
            this.isLoading(true);

            storage.get(
                this.fetchUrl + '?product_id=' + this.productId
            ).done(function (response) {
                if (response.success) {
                    self.questions(response.items);
                }
            }).fail(function () {
                console.error($t('Failed to load questions.'));
            }).always(function () {
                self.isLoading(false);
            });
        },

        /**
         * Submit a new question
         */
        submitQuestion: function (formElement) {
            var self = this;

            if (!$(formElement).valid()) {
                return false;
            }

            this.isSubmitting(true);
            this.submitSuccess(false);
            this.submitError(false);

            var formData = {
                product_id: this.productId,
                author_name: this.newQuestionName(),
                author_email: this.newQuestionEmail(),
                question_text: this.newQuestionText(),
                form_key: $.mage.cookies.get('form_key')
            };

            storage.post(
                this.submitUrl,
                JSON.stringify(formData),
                false
            ).done(function (response) {
                if (response.success) {
                    self.submitSuccess(true);
                    self.submitMessage(response.message);
                    self.resetForm();
                } else {
                    self.submitError(true);
                    self.submitMessage(response.message);
                }
            }).fail(function () {
                self.submitError(true);
                self.submitMessage($t('An error occurred during submission.'));
            }).always(function () {
                self.isSubmitting(false);
            });
        },

        /**
         * Reset the form fields
         */
        resetForm: function () {
            this.newQuestionName('');
            this.newQuestionEmail('');
            this.newQuestionText('');
        },

        /**
         * Format date string
         */
        formatDate: function (dateString) {
            var options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }
    });
});
