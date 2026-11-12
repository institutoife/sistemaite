(function ($) {
    'use strict';
    function message($form, xhr) {
        var json = xhr.responseJSON || {}, messages = [];
        $.each(json.errors || {}, function (_, errors) { messages = messages.concat(errors); });
        $form.find('.attendance-errors').text(messages.join(' ') || json.message || 'No se pudo guardar. Revise los datos e intente nuevamente.').removeClass('d-none');
    }
    function minutesLater(time, minutes) {
        var parts = time.split(':'), total = Number(parts[0]) * 60 + Number(parts[1]) + Number(minutes);
        if (!/^\d{2}:\d{2}$/.test(time) || !Number.isFinite(total) || total < 0 || total >= 1440) return '';
        return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
    }
    function elapsedClock() { return window.performance ? window.performance.now() : Date.now(); }
    function currentStart($form) {
        var $panel = $form.closest('[data-attendance-panel]'), clock = $panel.data('attendance-clock');
        if (!clock) {
            clock = { server: Date.parse($panel.attr('data-server-now')), at: elapsedClock() };
            $panel.data('attendance-clock', clock);
        }
        if (!Number.isFinite(clock.server)) return;
        var parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/La_Paz', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
            .formatToParts(new Date(clock.server + elapsedClock() - clock.at));
        var local = {};
        parts.forEach(function (part) { local[part.type] = part.value; });
        var today = local.year + '-' + local.month + '-' + local.day;
        $panel.attr('data-today', today);
        if (!$form.data('date-edited')) $form.find('[name=fecha]').val(today);
        if (!$form.data('time-edited')) $form.find('[name=horainicio]').val(local.hour + ':' + local.minute);
        calculateEnd($form);
    }
    function calculateEnd($form) {
        var duration = Number($form.attr('data-duration')), $end = $form.find('[name=horafin]');
        if (!(duration > 0)) return;
        var end = minutesLater($form.find('[name=horainicio]').val(), duration);
        $end.val(end);
        $end[0].setCustomValidity(end ? '' : 'La clase debe finalizar el mismo día. Ajuste la hora de inicio.');
    }
    function originalDefaults($form) {
        var $option = $form.find('.attendance-original :selected');
        if (!$option.val()) return;
        $.each({ docente_id: 'docente', materia_id: 'materia', aula_id: 'aula' }, function (field, attr) { $form.find('[name=' + field + ']').val($option.attr('data-' + attr)); });
        $form.attr('data-duration', $option.attr('data-minutes'));
        $form.find('.attendance-duration').val($option.attr('data-minutes'));
        calculateEnd($form);
        topics($form);
        var $panel = $form.closest('[data-attendance-panel]');
        teacherLabels($panel, $panel.data('teacher-presence') || {});
    }
    function filterOriginal($form) {
        var type = $form.find('[name=tipo]').val(), search = ($form.find('.attendance-search').val() || '').toLocaleLowerCase();
        var effective = $form.find('[name=fecha]').val() + 'T' + $form.find('[name=horainicio]').val();
        var extra = type === 'extra', compensation = type === 'recuperacion' || type === 'adelanto' || extra, $select = $form.find('.attendance-original');
        $form.find('.attendance-original-group').toggleClass('d-none', !compensation);
        $form.find('.attendance-extra-note').toggleClass('d-none', !extra);
        $select.prop('required', compensation).prop('disabled', !compensation);
        $select.find('option[value!=""]').each(function () {
            var $option = $(this), matches = compensation && Number($option.attr(type === 'recuperacion' ? 'data-recovery' : 'data-advance')) === 1;
            if (effective.length === 16) matches = matches && (type === 'recuperacion' ? $option.attr('data-original-end') <= effective : $option.attr('data-original-start') > effective);
            var hide = !matches || $option.text().toLocaleLowerCase().indexOf(search) < 0;
            $option.prop('hidden', hide).prop('disabled', hide);
            if (hide && $option.is(':selected')) {
                $select.val('');
                $form.removeAttr('data-duration').find('.attendance-duration').val('');
                $form.find('[name=horafin]').val('')[0].setCustomValidity('');
            }
        });
        if (!compensation) $select.val('');
    }
    function topics($form) {
        var $select = $form.find('.attendance-topic'), subject = $form.find('[name=materia_id]').val();
        if (!$select.length) return;
        if ($select.data('topic-subject') !== undefined && $select.data('topic-subject') !== subject) {
            $select.empty().append($('<option>').val('').text('Sin tema específico')).val('').trigger('change');
        }
        $select.data('topic-subject', subject).prop('disabled', !subject);
        if (!$.fn.select2 || $select.hasClass('select2-hidden-accessible')) return;
        var $modal = $select.closest('.modal');
        $select.select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $modal.length ? $modal : $(document.body),
            placeholder: 'Buscar tema (opcional)', allowClear: true, minimumInputLength: 1,
            language: { inputTooShort: function () { return 'Escriba para buscar un tema'; }, searching: function () { return 'Buscando…'; }, noResults: function () { return 'Sin resultados'; }, errorLoading: function () { return 'No se pudieron cargar los temas'; } },
            ajax: { url: $select.attr('data-topics-url'), dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term || '', page: params.page || 1, materia_id: $form.find('[name=materia_id]').val() }; },
                transport: function (params, success, failure) {
                    var subjectAtRequest = params.data.materia_id, xhr = $.ajax(params);
                    xhr.done(function (json) { success($form.find('[name=materia_id]').val() === subjectAtRequest ? json : { results: [], pagination: { more: false } }); }).fail(failure);
                    return xhr;
                }
            }
        });
    }
    var presenceRequest;
    function teacherLabels($panel, presence) {
        $panel.find('.attendance-form').each(function () {
            var $form = $(this), branch = $form.find('[name=aula_id] :selected').attr('data-sede') || $panel.attr('data-active-branch') || '';
            $form.find('.attendance-teacher option[data-name]').each(function () {
                var $option = $(this), locations = (presence[$option.val()] || []).filter(function (location) {
                    return !branch || location.sede_id === null || Number(location.sede_id) === Number(branch);
                });
                var labels = locations.map(function (location) { return location.aula + (!branch && location.sucursal ? ' · ' + location.sucursal : '') + ', ' + location.presentes; });
                $option.text($option.attr('data-name') + ' (' + (labels.join(' / ') || 'S/E, 0') + ')');
            });
        });
    }
    function updateTeachers() {
        var $panels = $('[data-attendance-panel]'), $visible = $panels.filter(':visible');
        if (!$visible.length || presenceRequest || document.hidden || !$visible.find('.attendance-teacher').length) return;
        presenceRequest = $.getJSON($visible.first().attr('data-teachers-url')).done(function (json) {
            $panels.each(function () { $(this).data('teacher-presence', json.teachers); teacherLabels($(this), json.teachers); });
        }).always(function () { presenceRequest = null; });
    }
    function mount(root) {
        $(root).find('.attendance-entry-form').each(function () {
            var $form = $(this);
            if (!$form.data('clock-ready')) { currentStart($form); $form.data('clock-ready', true); }
        });
        $(root).find('.attendance-add-form').each(function () {
            var $form = $(this);
            if (!$form.data('clock-ready')) { currentStart($form); $form.data('clock-ready', true); }
            filterOriginal($form);
        });
        $(root).find('.attendance-form').each(function () { topics($(this)); });
        $(root).find('[data-attendance-panel]').each(function () { teacherLabels($(this), $(this).data('teacher-presence') || {}); });
    }
    function refresh($panel, successMessage, notice) {
        var $modal = $panel.closest('.modal'), url = $panel.attr('data-refresh-url');
        if (!$modal.length) {
            try { sessionStorage.setItem('ife-attendance-message', successMessage || 'Cambios guardados.'); } catch (_) {}
            if (window.IFEArrivalNotice) window.IFEArrivalNotice.save(notice);
            window.location.reload(); return;
        }
        if (window.IFEArrivalNotice) window.IFEArrivalNotice.show(notice);
        var active = $modal.find('.tab-pane.active').attr('id'), scroll = $modal.find('.modal-body').scrollTop();
        $.get(url).done(function (html) {
            $modal.find('.modal-content').html(html);
            if (active) {
                var $pane = $modal.find('#' + active);
                if ($pane.length) {
                    $modal.find('.nav-link,.tab-pane').removeClass('active show').attr('aria-selected', 'false');
                    $pane.addClass('active show');
                    $modal.find('.nav-link[href="#' + active + '"]').addClass('active').attr('aria-selected', 'true');
                }
            }
            mount($modal);
            $modal.find('.modal-body').first().prepend($('<div class="alert alert-success" role="status">').text(successMessage || 'Cambios guardados.'));
            $modal.find('.modal-body').scrollTop(scroll);
        }).fail(function () { $panel.prepend($('<div class="alert alert-warning">').text('El cambio se guardó. Cierre y vuelva a abrir el modal para actualizar el estado.')); });
        if ($.fn.dataTable) $('table.dataTable').each(function () {
            if ($.fn.dataTable.isDataTable(this)) {
                var table = $(this).DataTable();
                if (table.settings()[0].ajax) table.ajax.reload(null, false);
            }
        });
    }
    $(document).on('submit', '.attendance-form', function (event) {
        event.preventDefault();
        var $form = $(this), $panel = $form.closest('[data-attendance-panel]');
        if (!this.checkValidity()) { this.reportValidity(); return; }
        if ($form.data('saving')) return;
        $form.data('saving', true).find('[type=submit]').prop('disabled', true);
        $form.find('.attendance-errors').addClass('d-none').empty();
        $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), headers: { Accept: 'application/json' } })
            .done(function (json) { document.dispatchEvent(new Event('ife:attendance-changed')); refresh($panel, json.message, json.arrival_notice); })
            .fail(function (xhr) { message($form, xhr); })
            .always(function () { $form.data('saving', false).find('[type=submit]').prop('disabled', false); });
    });
    $(document).on('click', '.attendance-exit', function () {
        var $button = $(this), $panel = $button.closest('[data-attendance-panel]');
        if ($button.prop('disabled')) return;
        if (!window.confirm('¿Confirmas la salida de esta asistencia? Se registrará la fecha y hora actuales.')) return;
        $button.prop('disabled', true);
        $.ajax({ url: $button.data('url'), type: 'POST', data: { id: $button.data('id'), _token: $('meta[name=csrf-token]').attr('content') }, headers: { Accept: 'application/json' } })
            .done(function (json) { document.dispatchEvent(new Event('ife:attendance-changed')); refresh($panel, json.message); })
            .fail(function (xhr) {
                var $error = $('<div class="attendance-errors alert alert-danger">');
                $button.after($error); message($button.parent(), xhr);
            }).always(function () { $button.prop('disabled', false); });
    });
    $(document).on('change', '.attendance-add-form [name=tipo]', function () {
        var $form = $(this).closest('form');
        filterOriginal($form);
        if (!$form.find('.attendance-original').val()) {
            $form.removeAttr('data-duration').find('.attendance-duration').val('');
            $form.find('[name=horafin]').val('')[0].setCustomValidity('');
        } else originalDefaults($form);
    });
    $(document).on('input', '.attendance-search', function () { filterOriginal($(this).closest('form')); });
    $(document).on('change', '.attendance-original', function () {
        if (!this.value) {
            var $form = $(this).closest('form');
            $form.removeAttr('data-duration').find('.attendance-duration').val('');
            $form.find('[name=horafin]').val('')[0].setCustomValidity('');
            return;
        }
        originalDefaults($(this).closest('form'));
    });
    $(document).on('change', '.attendance-form [name=materia_id]', function () { topics($(this).closest('form')); });
    $(document).on('input change', '.attendance-add-form [name=horainicio], .attendance-entry-form [name=horainicio]', function () {
        var $form = $(this).closest('form');
        $form.data('time-edited', true);
        calculateEnd($form);
        if ($form.hasClass('attendance-add-form')) filterOriginal($form);
    });
    $(document).on('input change', '.attendance-entry-form [name=horafin]', function () {
        var $form = $(this).closest('form'), start = $form.find('[name=horainicio]').val(), end = this.value;
        $form.data('time-edited', true);
        var minutes = function (time) { return Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5)); };
        this.setCustomValidity(end <= start ? 'La finalización debe ser posterior al inicio.' : (minutes(end) - minutes(start) > Number($form.attr('data-duration')) ? 'La duración no puede superar la de la clase programada.' : ''));
    });
    $(document).on('input change', '.attendance-add-form [name=horafin]', function () {
        var $form = $(this).closest('form'), start = $form.find('[name=horainicio]').val().split(':'), end = this.value.split(':');
        var actual = Number(end[0]) * 60 + Number(end[1]) - Number(start[0]) * 60 - Number(start[1]);
        this.setCustomValidity(actual <= 0 ? 'La finalización debe ser posterior al inicio.' : ($form.attr('data-duration') && actual !== Number($form.attr('data-duration')) ? 'Conserve la duración de la clase original. Ajuste la hora de inicio para recalcular la finalización.' : ''));
    });
    $(document).on('change', '.attendance-add-form [name=fecha]', function () {
        var today = $(this).closest('[data-attendance-panel]').attr('data-today');
        $(this).closest('form').data('date-edited', true);
        $(this).closest('form').find('[name=marcar_entrada]').prop('checked', !today || $(this).val() <= today);
        filterOriginal($(this).closest('form'));
    });
    document.addEventListener('toggle', function (event) {
        if ($(event.target).is('.attendance-add[open]')) {
            var $form = $(event.target).find('.attendance-add-form');
            currentStart($form); filterOriginal($form);
        }
    }, true);
    $(document).on('click', '.attendance-scheduled', function () {
        var $form = $(this).closest('form');
        $form.data('time-edited', true);
        $form.find('[name=horainicio]').val($(this).attr('data-start'));
        $form.find('[name=horafin]').val($(this).attr('data-end'))[0].setCustomValidity('');
    });
    $(document).on('click', '.attendance-quick-entry', function () {
        var $form = $(this).closest('.card-body').find('.attendance-form').first();
        if (!$form.length || $form.data('saving')) return;
        $form.closest('details').prop('open', true);
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        $form.trigger('submit');
    });
    $(document).on('click', '[data-attendance-modal] .nav-link[data-toggle=tab]', function (event) {
        event.preventDefault();
        var $root = $(this).closest('[data-attendance-modal]'), target = $(this).attr('href');
        $root.find('.nav-link,.tab-pane').removeClass('active show').attr('aria-selected', 'false');
        $(this).addClass('active').attr('aria-selected', 'true');
        $root.find(target).addClass('active show');
    });
    $(document).on('click', '.attendance-close', function () {
        var $modal = $(this).closest('.modal');
        if ($.fn.modal) $modal.modal('hide');
        else if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
    });
    window.IFEAttendance = { mount: mount };
    $(document).on('change', '.attendance-form [name=aula_id]', function () {
        var $panel = $(this).closest('[data-attendance-panel]');
        teacherLabels($panel, $panel.data('teacher-presence') || {});
    });
    document.addEventListener('ife:attendance-changed', updateTeachers);
    window.setInterval(updateTeachers, 30000);
    window.setInterval(function () {
        $('.attendance-entry-form:visible').each(function () {
            var $form = $(this);
            if (!$form.data('time-edited') && !$form.data('saving')) currentStart($form);
        });
    }, 15000);
    $(function () {
        mount(document);
        var saved = sessionStorage.getItem('ife-attendance-message');
        if (saved && $('[data-attendance-panel]').length) {
            $('[data-attendance-panel]').first().prepend($('<div class="alert alert-success" role="status">').text(saved));
            sessionStorage.removeItem('ife-attendance-message');
        }
    });
})(jQuery);
