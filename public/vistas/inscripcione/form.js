(function () {
    'use strict';

    var modalidad = document.getElementById('modalidad_id');
    if (modalidad) {
        modalidad.addEventListener('change', function () {
            var option = modalidad.options[modalidad.selectedIndex];
            if (!option || !option.value) return;
            [['costo', 'data-costo'], ['totalhoras', 'data-horas']].forEach(function (field) {
                var input = document.getElementById(field[0]);
                var value = option.getAttribute(field[1]);
                if (input && value !== null && value !== '') input.value = value;
            });
        });
    }

    var objetivo = document.getElementById('objetivo');
    if (objetivo && window.ClassicEditor) {
        window.ClassicEditor.create(objetivo).catch(function (error) {
            console.error(error);
        });
    }
}());
