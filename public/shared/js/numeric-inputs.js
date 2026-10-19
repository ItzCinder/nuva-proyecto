// Esta validación ayuda al usuario. PHP vuelve a comprobar los datos antes de guardarlos.
function numericInputError(value, input) {
    if (value === '') {
        return '';
    }
    const normalized = value.replace(',', '.');
    let pattern = /^-?\d+(\.\d+)?$/;
    if (input.step === '1' || input.step === '') {
        pattern = /^-?\d+$/;
    }
    if (!pattern.test(normalized)) {
        if (input.step === '1' || input.step === '') {
            return 'Escribe un número entero, sin letras ni decimales. Ejemplo: 12.';
        }
        return 'Escribe un número, sin letras. Ejemplo: 12 o 2,5.';
    }
    const number = Number(normalized);
    if (!Number.isFinite(number)) {
        return 'Escribe un número válido.';
    }
    if (input.min !== '' && number < Number(input.min)) {
        return 'El mínimo permitido es ' + input.min + '.';
    }
    if (input.max !== '' && number > Number(input.max)) {
        return 'El máximo permitido es ' + input.max + '.';
    }
    return '';
}

function validateNumericInput(input) {
    let message = '';
    if (input.validity.badInput) {
        message = 'Escribe un número válido, sin letras.';
    } else {
        message = numericInputError(input.value, input);
    }
    input.setCustomValidity(message);
    if (message !== '') {
        input.setAttribute('aria-invalid', 'true');
    } else {
        input.removeAttribute('aria-invalid');
    }
    return message === '';
}

function prepareNumericInput(input) {
    input.inputMode = 'decimal';
    if (input.step === '' || input.step === '1') {
        input.inputMode = 'numeric';
    }
    input.addEventListener('keydown', function (event) {
        // Conservar copiar, pegar, seleccionar y las teclas para moverse por el campo.
        if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) {
            return;
        }
        if (/[^0-9.,-]/.test(event.key)) {
            event.preventDefault();
            input.setCustomValidity('Este campo solo admite números, sin letras.');
            input.reportValidity();
        }
    });
    input.addEventListener('paste', function (event) {
        const pastedText = event.clipboardData.getData('text').trim();
        const message = numericInputError(pastedText, input);
        if (message !== '') {
            event.preventDefault();
            input.setCustomValidity(message);
            input.reportValidity();
        } else if (pastedText.includes(',')) {
            event.preventDefault();
            input.value = pastedText.replace(',', '.');
            input.dispatchEvent(new Event('input', {bubbles: true}));
        }
    });
    input.addEventListener('input', function () {
        validateNumericInput(input);
    });
    input.addEventListener('change', function () {
        validateNumericInput(input);
    });
}

for (const input of document.querySelectorAll('input[type="number"]')) {
    prepareNumericInput(input);
}
