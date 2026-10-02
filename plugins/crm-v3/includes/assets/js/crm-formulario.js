/**
 * CRM V3 - Nuevos Registros
 * Mensajes de validación flotantes.
 */

(function ($) {

    'use strict';

    const CRMFormulario = {

        init: function () {
            this.createNotice();
            this.observeValidation();
        },

        /**
         * Crear contenedor flotante.
         */
        createNotice: function () {

            if (document.getElementById('crm-v3-validation-notice')) {
                return;
            }

            const notice = document.createElement('div');

            notice.id = 'crm-v3-validation-notice';
            notice.className = 'crm-v3-validation-notice';

            notice.innerHTML = `
                <div class="crm-v3-validation-header">
                    <strong>Validación</strong>
                    <button type="button" class="crm-v3-validation-close" aria-label="Cerrar">
                        ×
                    </button>
                </div>

                <div class="crm-v3-validation-message"></div>
            `;

            document.body.appendChild(notice);

            notice
                .querySelector('.crm-v3-validation-close')
                .addEventListener('click', function () {
                    notice.classList.remove('is-visible');
                });
        },

        /**
         * Vigilar las validaciones generadas por ACF.
         */
        observeValidation: function () {

            const observer = new MutationObserver(() => {
                this.checkValidation();
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            $(document).on('submit', '.acf-form', () => {
                setTimeout(() => {
                    this.checkValidation();
                }, 150);
            });
        },

        /**
         * Buscar mensajes de error.
         */
        checkValidation: function () {

            const errors = [];

            $('.acf-form .acf-error-message:visible').each(function () {

                const text = $(this)
                    .text()
                    .trim();

                if (text && !errors.includes(text)) {
                    errors.push(text);
                }
            });

            if (!errors.length) {
                return;
            }

            this.showNotice(errors);
        },

        /**
         * Mostrar errores en ventana flotante.
         */
        showNotice: function (errors) {

            const notice = document.getElementById(
                'crm-v3-validation-notice'
            );

            if (!notice) {
                return;
            }

            const message = notice.querySelector(
                '.crm-v3-validation-message'
            );

            message.innerHTML = errors
                .map(error => `<div>${this.escapeHtml(error)}</div>`)
                .join('');

            notice.classList.add('is-visible');
        },

        /**
         * Seguridad básica para mensajes.
         */
        escapeHtml: function (text) {

            const div = document.createElement('div');

            div.textContent = text;

            return div.innerHTML;
        }
    };


    $(document).ready(function () {
        CRMFormulario.init();
    });

})(jQuery);