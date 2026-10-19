function initializeTrialForm(form) {
    const panels = Array.from(form.querySelectorAll('[data-trial-step]'));
    const progress = form.querySelector('[data-wizard-progress]');
    const backButton = form.querySelector('[data-trial-back]');
    const nextButton = form.querySelector('[data-trial-next]');
    const saveButton = form.querySelector('[data-trial-submit]');
    const positionLabel = form.querySelector('[data-trial-position]');
    const errorMessage = form.querySelector('[data-trial-error]');
    let currentStep = 0;

    // Comprobamos un paso a la vez, abriendo el apartado que tenga un dato inválido.
    form.noValidate = true;

    function hasTemplate(code) {
        for (const checkbox of form.querySelectorAll('[name="templates[]"]:checked')) {
            if (checkbox.value === code) {
                return true;
            }
        }
        return false;
    }

    function fieldValue(name) {
        const field = form.elements[name];
        if (!field) {
            return '';
        }
        return field.value;
    }

    function copyJourneyMeasurements() {
        const sharedFields = {
            segment_distance: 'distance',
            segment_time: 'time',
            u_segment_distance: 'u_distance',
            u_segment_time: 'u_time'
        };
        for (const targetName in sharedFields) {
            const sourceName = sharedFields[targetName];
            form.elements[targetName].value = fieldValue(sourceName);
        }
    }

    function refreshTrialFields() {
        const motion = hasTemplate('motion');
        const dynamics = hasTemplate('dynamics');
        const energy = hasTemplate('energy');
        const accelerationFromJourney = dynamics && fieldValue('acceleration_source') === 'model';
        const speedFromJourney = energy && fieldValue('speed_source') === 'model';
        const sharedModel = accelerationFromJourney || speedFromJourney;
        const useModel = (motion && form.elements.mrua.checked) || sharedModel;
        const reuseJourney = motion && useModel && form.elements.reuse_journey.checked;

        const workModelAllowed = speedFromJourney && (!dynamics || accelerationFromJourney);
        const workModelOption = form.elements.work_mode.querySelector('option[value="model"]');
        workModelOption.disabled = !workModelAllowed;
        if (!workModelAllowed && fieldValue('work_mode') === 'model') {
            form.elements.work_mode.value = 'none';
        }

        // Cada nombre coincide con un data-stat-group de la vista.
        const visibleGroups = {
            motion: motion,
            dynamics: dynamics,
            energy: energy,
            mass: dynamics || energy,
            model: useModel,
            reuse: motion && useModel,
            segment: useModel && !reuseJourney,
            'motion-optional': !sharedModel,
            'model-explanation': motion && form.elements.mrua.checked && !sharedModel,
            'measured-acceleration': dynamics && fieldValue('acceleration_source') === 'measured',
            'measured-speed': energy && fieldValue('speed_source') === 'measured',
            'measured-work': energy && fieldValue('work_mode') === 'measured'
        };
        for (const group of form.querySelectorAll('[data-stat-group]')) {
            group.hidden = !visibleGroups[group.dataset.statGroup];
        }
        for (const input of form.querySelectorAll('[data-required]')) {
            input.required = !input.closest('[data-stat-group][hidden]');
        }
        if (reuseJourney) {
            copyJourneyMeasurements();
        }

        const measuredFields = [];
        if (motion) {
            measuredFields.push('distance', 'time');
        }
        if (useModel && !reuseJourney) {
            measuredFields.push('segment_distance', 'segment_time');
        }
        if (visibleGroups.mass) {
            measuredFields.push('mass');
        }
        if (visibleGroups['measured-acceleration']) {
            measuredFields.push('acceleration');
        }
        if (visibleGroups['measured-speed']) {
            measuredFields.push('speed', 'initial_speed');
        }
        if (visibleGroups['measured-work']) {
            measuredFields.push('force', 'work_distance', 'angle');
        }
        for (const input of form.querySelectorAll('input[name^="u_"]')) {
            input.parentElement.hidden = !measuredFields.includes(input.name.slice(2));
        }
        refreshTrialSummary(motion, useModel);
    }

    function refreshTrialSummary(motion, useModel) {
        const participant = form.elements.participant_id.selectedOptions[0];
        const parts = [];
        if (participant && participant.value) {
            parts.push(participant.textContent);
        } else {
            parts.push('El participante que elijas');
        }
        for (const checkbox of form.querySelectorAll('[name="templates[]"]:checked')) {
            parts.push(checkbox.closest('label').querySelector('strong').textContent);
        }
        if (motion && fieldValue('distance') && fieldValue('time')) {
            parts.push(fieldValue('distance') + ' metros en ' + fieldValue('time') + ' segundos');
        } else if (useModel && fieldValue('segment_distance') && fieldValue('segment_time')) {
            parts.push(fieldValue('segment_distance') + ' metros en ' + fieldValue('segment_time') + ' segundos');
        }
        form.querySelector('[data-trial-recap]').textContent = parts.join(' · ') + '. Al guardar podrás ver los resultados explicados.';
    }

    function showStep(index, focusHeading = true) {
        currentStep = index;
        for (let position = 0; position < panels.length; position++) {
            panels[position].hidden = position !== index;
            const progressItem = progress.children[position];
            if (position === index) {
                progressItem.setAttribute('aria-current', 'step');
            } else {
                progressItem.removeAttribute('aria-current');
            }
        }
        progress.hidden = false;
        backButton.hidden = index === 0;
        nextButton.hidden = index === panels.length - 1;
        saveButton.hidden = index !== panels.length - 1;
        positionLabel.hidden = false;
        positionLabel.textContent = 'Paso ' + (index + 1) + ' de ' + panels.length;
        if (focusHeading) {
            const heading = panels[index].querySelector('h3');
            heading.tabIndex = -1;
            heading.focus();
        }
    }

    function validateStep(index) {
        errorMessage.hidden = true;
        if (index === 0 && !form.querySelector('[name="templates[]"]:checked')) {
            showStep(0, false);
            errorMessage.textContent = 'Elige al menos una opción para continuar.';
            errorMessage.hidden = false;
            form.querySelector('[name="templates[]"]').focus();
            return false;
        }
        for (const input of panels[index].querySelectorAll('input,select,textarea')) {
            if (input.closest('[data-stat-group][hidden]') || input.parentElement.hidden) {
                continue;
            }
            if (input.checkValidity()) {
                continue;
            }
            showStep(index, false);
            let parent = input.parentElement;
            while (parent && parent !== form) {
                if (parent.tagName === 'DETAILS') {
                    parent.open = true;
                }
                parent = parent.parentElement;
            }
            input.reportValidity();
            return false;
        }
        return true;
    }

    function nextStep() {
        refreshTrialFields();
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }
    }

    function previousStep() {
        errorMessage.hidden = true;
        showStep(currentStep - 1);
    }

    function submitTrial(event) {
        refreshTrialFields();
        if (currentStep < panels.length - 1) {
            event.preventDefault();
            nextStep();
            return;
        }
        for (let index = 0; index < panels.length; index++) {
            if (!validateStep(index)) {
                event.preventDefault();
                return;
            }
        }
    }

    form.addEventListener('input', refreshTrialFields);
    form.addEventListener('change', refreshTrialFields);
    nextButton.addEventListener('click', nextStep);
    backButton.addEventListener('click', previousStep);
    form.addEventListener('submit', submitTrial);
    refreshTrialFields();
    showStep(0, false);
}

function initializeManualStatisticsForm(form) {
    function refreshManualFields() {
        const option = form.elements.stat_type_id.selectedOptions[0];
        const isTeam = option && option.dataset.target === 'team';
        let description = '';
        if (option) {
            description = option.dataset.description || '';
        }
        form.querySelector('[data-manual-help]').textContent = description;
        form.querySelector('[data-manual-unit]').textContent = option && option.dataset.unit ? '(' + option.dataset.unit + ')' : ''; 
        form.elements.value.step = '0.00000001';
        form.elements.value.inputMode = 'decimal';
        if (option && option.dataset.numberType === 'integer') {
            form.elements.value.step = '1';
            form.elements.value.inputMode = 'numeric';
        }
        if (typeof validateNumericInput === 'function') {
            validateNumericInput(form.elements.value);
        }
        form.querySelector('[data-manual-person]').hidden = isTeam;
        form.querySelector('[data-manual-team]').hidden = !isTeam;
        form.elements.person_id.required = !isTeam;
        form.elements.team_id.required = isTeam;
    }
    form.addEventListener('change', refreshManualFields);
    refreshManualFields();
}

for (const form of document.querySelectorAll('[data-trial-form]')) {
    initializeTrialForm(form);
}
for (const form of document.querySelectorAll('[data-manual-form]')) {
    initializeManualStatisticsForm(form);
}
