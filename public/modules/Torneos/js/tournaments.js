function initializeTournamentForm(form) {
    const discipline = form.querySelector('[data-discipline]');
    const format = form.querySelector('[data-format]');
    const teams = form.querySelector('[data-has-team]');
    const alreadyStarted = form.dataset.started === '1';

    function refreshTournamentFields() {
        const customDiscipline = form.querySelector('[data-custom-discipline]');
        customDiscipline.hidden = discipline.value !== 'other';
        customDiscipline.querySelector('input').required = !customDiscipline.hidden;
        customDiscipline.querySelector('input').disabled = customDiscipline.hidden;

        const teamSize = form.querySelector('[data-team-size]');
        const isTeamTournament = teams.value === '1';
        teamSize.hidden = !isTeamTournament;
        teamSize.querySelector('input').required = isTeamTournament;
        teamSize.querySelector('input').disabled = alreadyStarted || !isTeamTournament;

        let capacityLabel = 'Cupo de participantes';
        if (isTeamTournament) {
            capacityLabel = 'Cupo de equipos';
        }
        const capacityInput = form.elements.max_participants;
        capacityInput.parentElement.firstChild.textContent = capacityLabel;
        capacityInput.min = '2';
        if (format.value === 'swiss') {
            capacityInput.min = '4';
        }
        for (const rule of form.querySelectorAll('[data-rule]')) {
            rule.hidden = rule.dataset.rule !== format.value;
            // Un campo de otro formato no debe bloquear ni enviarse con este formulario.
            for (const input of rule.querySelectorAll('input, select')) {
                input.disabled = alreadyStarted || rule.hidden;
            }
        }
        const descriptions = {
            league: 'Todos contra todos; elige una vuelta o ida y vuelta.',
            swiss: 'Rondas por rendimiento; los cruces se prepararán después.',
            elimination: 'Llave de eliminación; puede haber pases directos.'
        };
        form.querySelector('[data-format-help]').textContent = descriptions[format.value] || '';
    }

    function setDefaultScoring() {
        if (alreadyStarted) {
            return;
        }
        if (format.value === 'swiss') {
            form.elements.points_win.value = '1';
            form.elements.points_draw.value = '0.5';
        } else {
            form.elements.points_win.value = '3';
            form.elements.points_draw.value = '1';
        }
        form.elements.allow_draws.checked = format.value !== 'elimination';
    }

    discipline.addEventListener('change', refreshTournamentFields);
    format.addEventListener('change', refreshTournamentFields);
    teams.addEventListener('change', refreshTournamentFields);
    format.addEventListener('change', setDefaultScoring);
    refreshTournamentFields();

    if (alreadyStarted) {
        const lockedFields = [
            'format', 'has_team', 'max_participants', 'league_legs', 'swiss_rounds',
            'third_place', 'team_size', 'points_win', 'points_draw', 'points_loss',
            'allow_draws', 'score_order', 'score_label', 'status', 'start_date', 'end_date'
        ];
        for (const fieldName of lockedFields) {
            const input = form.elements[fieldName];
            if (input) {
                input.disabled = true;
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-tournament-form]');
    if (form) {
        initializeTournamentForm(form);
    }
});

for (const form of document.querySelectorAll('form[data-confirm]')) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
}
