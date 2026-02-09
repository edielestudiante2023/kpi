/**
 * SafeFormulaParser - Evalúa expresiones aritméticas con +, -, *, /, paréntesis
 * NO usa eval(). Implementa un parser de descenso recursivo.
 */
var SafeFormulaParser = (function() {
    var input, pos;

    function parse(expression) {
        input = expression.replace(/\s+/g, '');
        pos = 0;
        if (input.length === 0) return NaN;
        var result = parseExpression();
        if (pos < input.length) return NaN;
        return result;
    }

    function parseExpression() {
        var result = parseTerm();
        while (pos < input.length) {
            if (input[pos] === '+') { pos++; result += parseTerm(); }
            else if (input[pos] === '-') { pos++; result -= parseTerm(); }
            else break;
        }
        return result;
    }

    function parseTerm() {
        var result = parseFactor();
        while (pos < input.length) {
            if (input[pos] === '*') { pos++; result *= parseFactor(); }
            else if (input[pos] === '/') {
                pos++;
                var divisor = parseFactor();
                if (divisor === 0) return Infinity;
                result /= divisor;
            }
            else break;
        }
        return result;
    }

    function parseFactor() {
        if (input[pos] === '-') {
            pos++;
            return -parseFactor();
        }
        if (input[pos] === '(') {
            pos++;
            var result = parseExpression();
            if (input[pos] === ')') pos++;
            return result;
        }
        var start = pos;
        while (pos < input.length && (isDigit(input[pos]) || input[pos] === '.')) {
            pos++;
        }
        if (start === pos) return NaN;
        return parseFloat(input.substring(start, pos));
    }

    function isDigit(ch) {
        return ch >= '0' && ch <= '9';
    }

    return { parse: parse };
})();

/**
 * Construye la fórmula desde las partes y valores del usuario, luego calcula
 */
function buildAndCalculate(formContainer) {
    var partsJson = formContainer.getAttribute('data-formula-parts');
    var parts = JSON.parse(partsJson);
    var formulaStr = '';
    var allFilled = true;
    var partesValues = {};

    parts.forEach(function(part) {
        if (part.tipo_parte === 'dato') {
            var input = formContainer.querySelector('input[data-variable="' + part.valor + '"]');
            var val = input ? input.value.trim() : '';
            if (val === '' || isNaN(parseFloat(val))) {
                allFilled = false;
                formulaStr += '0';
            } else {
                formulaStr += parseFloat(val);
                partesValues[part.valor] = parseFloat(val);
            }
        } else if (part.tipo_parte === 'constante') {
            formulaStr += part.valor;
        } else {
            formulaStr += part.valor;
        }
    });

    if (!allFilled) {
        return { result: null, partes: partesValues, complete: false, error: null };
    }

    var result = SafeFormulaParser.parse(formulaStr);

    if (result === Infinity || result === -Infinity) {
        return { result: null, partes: partesValues, complete: true, error: 'division_zero' };
    }
    if (isNaN(result)) {
        return { result: null, partes: partesValues, complete: true, error: 'invalid' };
    }

    result = Math.round(result * 10000) / 10000;

    return { result: result, partes: partesValues, complete: true, error: null };
}

/**
 * Recalcula la fórmula para un contenedor dado
 */
function recalculate(container) {
    var el = container[0] || container;
    var calc = buildAndCalculate(el);
    var $container = $(el);
    var resultDisplay = $container.find('.formula-result-display');
    var hiddenResult = $container.find('.formula-result-hidden');
    var hiddenPartesContainer = $container.find('.formula-partes-hidden');
    var submitBtn = $container.closest('form').find('button[type="submit"]');
    var ipId = el.getAttribute('data-ip-id');

    hiddenPartesContainer.empty();

    if (!calc.complete) {
        resultDisplay.html('<span class="text-muted"><i class="bi bi-three-dots me-1"></i>Completa todos los campos...</span>');
        hiddenResult.val('');
        submitBtn.prop('disabled', true);
        return;
    }

    if (calc.error === 'division_zero') {
        resultDisplay.html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Error: division por cero</span>');
        hiddenResult.val('');
        submitBtn.prop('disabled', true);
        return;
    }

    if (calc.error === 'invalid') {
        resultDisplay.html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Formula invalida</span>');
        hiddenResult.val('');
        submitBtn.prop('disabled', true);
        return;
    }

    resultDisplay.html('<span class="text-success fw-bold fs-5"><i class="bi bi-check-circle me-1"></i>' + calc.result + '</span>');
    hiddenResult.val(calc.result);
    submitBtn.prop('disabled', false);

    for (var key in calc.partes) {
        if (calc.partes.hasOwnProperty(key)) {
            hiddenPartesContainer.append(
                '<input type="hidden" name="formula_partes[' + ipId + '][' + key + ']" value="' + calc.partes[key] + '">'
            );
        }
    }
}

$(document).ready(function() {
    $(document).on('input', '.formula-var-input', function() {
        var formContainer = $(this).closest('.formula-inline-container');
        recalculate(formContainer);
    });
});
