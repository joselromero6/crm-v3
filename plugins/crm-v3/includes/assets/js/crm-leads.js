document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById(
        'crm-lead-note-modal'
    );

    const content = document.getElementById(
        'crm-lead-note-content'
    );

    const title = document.getElementById(
        'crm-lead-note-title'
    );

    const input = document.getElementById(
        'crm-lead-note-input'
    );

    const saveButton = document.getElementById(
        'crm-lead-note-save'
    );


    if (
        !modal ||
        !content ||
        !title ||
        !input ||
        !saveButton
    ) {
        return;
    }


    let currentLeadId = 0;
    let hasOriginalNote = false;


    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value || '';

        return div.innerHTML;
    }


    function renderNote(nota) {

        if (!nota) {

            content.innerHTML = `
                <div class="crm-lead-notes-empty">
                    Este lead no tiene comentario registrado.
                </div>
            `;

            return;
        }


        content.innerHTML = `
    <div class="crm-lead-note-item">

        <div class="crm-lead-note-text">
            ${escapeHtml(nota)}
        </div>

    </div>
`;
    }


    function prepareModal(button) {

        currentLeadId = parseInt(
            button.dataset.leadId,
            10
        ) || 0;

        hasOriginalNote =
            button.dataset.noteExists === '1';


        title.textContent =
            button.dataset.leadName || 'Lead';


        content.innerHTML = `
            <div class="crm-lead-notes-loading">
                Cargando comentario...
            </div>
        `;


        input.value = '';

        input.disabled = false;

        saveButton.disabled = true;

        saveButton.textContent =
            hasOriginalNote
                ? 'Guardar cambios'
                : 'Agregar comentario';


        modal.classList.add('is-open');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'crm-lead-modal-open'
        );


        fetch(
            crmLeadsData.ajaxUrl,
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded; charset=UTF-8'
                },

                body: new URLSearchParams({

                    action:
                        'crm_v3_obtener_notas_lead',

                    lead_id:
                        currentLeadId,

                    nonce:
                        crmLeadsData.nonce

                })
            }
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (response) {

            if (!response.success) {

                content.innerHTML = `
                    <div class="crm-lead-notes-empty">
                        No fue posible cargar el comentario.
                    </div>
                `;

                saveButton.disabled = true;

                return;
            }


            hasOriginalNote =
                !!response.data.has_note;


            input.value =
                response.data.nota || '';


            renderNote(
                response.data.nota || ''
            );


            saveButton.textContent =
                hasOriginalNote
                    ? 'Guardar cambios'
                    : 'Agregar comentario';


            saveButton.disabled = false;

            input.focus();

        })
        .catch(function () {

            content.innerHTML = `
                <div class="crm-lead-notes-empty">
                    No fue posible cargar el comentario.
                </div>
            `;

            saveButton.disabled = true;

        });
    }


    function closeModal() {

        modal.classList.remove('is-open');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'crm-lead-modal-open'
        );

        currentLeadId = 0;

        hasOriginalNote = false;

        input.value = '';

        input.disabled = false;

        saveButton.disabled = false;

        saveButton.textContent =
            'Guardar cambios';
    }


    document.querySelectorAll(
        '.crm-lead-note-button'
    ).forEach(function (button) {

        button.addEventListener(
            'click',
            function () {
                prepareModal(button);
            }
        );

    });


    modal.querySelectorAll(
        '[data-note-close]'
    ).forEach(function (element) {

        element.addEventListener(
            'click',
            closeModal
        );

    });


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal.classList.contains('is-open')
            ) {
                closeModal();
            }

        }
    );


    saveButton.addEventListener(
        'click',
        function () {

            /*
             * No usamos trim() para conservar exactamente
             * los saltos de lÃ­nea y lÃ­neas en blanco.
             */
            const nota = input.value;

            if (
                !currentLeadId ||
                !nota.trim()
            ) {
                input.focus();
                return;
            }


            saveButton.disabled = true;


            const action =
                hasOriginalNote
                    ? 'crm_v3_editar_nota_lead'
                    : 'crm_v3_agregar_nota_lead';


            fetch(
                crmLeadsData.ajaxUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded; charset=UTF-8'
                    },

                    body: new URLSearchParams({

                        action:
                            action,

                        lead_id:
                            currentLeadId,

                        nota:
                            nota,

                        nonce:
                            crmLeadsData.nonce

                    })
                }
            )
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {

                if (!response.success) {

                    alert(
                        response.data &&
                        response.data.message
                            ? response.data.message
                            : 'No fue posible guardar el comentario.'
                    );

                    return;
                }


                const notaGuardada =
                    response.data.nota || nota;


                hasOriginalNote = true;


                renderNote(
                    notaGuardada
                );


                input.value =
                    notaGuardada;


                saveButton.textContent =
                    'Guardar cambios';


                /*
                 * Actualizamos inmediatamente la celda
                 * para que pase de "Agregar" a "Ver".
                 */
                const oldButton =
                    document.querySelector(
                        '.crm-lead-note-button[data-lead-id="' +
                        currentLeadId +
                        '"]'
                    );


                if (oldButton) {

                    oldButton.textContent = 'Ver';

                    oldButton.dataset.noteExists = '1';

                    oldButton.classList.remove(
                        'crm-lead-note-add'
                    );
                }

            })
            .finally(function () {

                saveButton.disabled = false;

            });

        }
    );

});