$(function () {
  let employees = [];
  const shiftModal = new bootstrap.Modal('#shift-modal');
  const subModal = new bootstrap.Modal('#sub-modal');

  function esc(t) { return $('<i>').text(t == null ? '' : t).html(); }

  function loadEmployees() {
    return API.get('/employees').done(res => {
      employees = res.data;
      const opts = employees.map(e => '<option value="' + e.id + '">' + e.apellidos + ', ' + e.nombre + ' (' + e.departamento + ')</option>').join('');
      $('#s-emp').html(opts);

      $('#f-emp').html('<option value="">(empleado)</option>' +
        employees.map(e => '<option value="' + e.id + '">' + e.apellidos + ', ' + e.nombre + '</option>').join(''));
      const deps = [...new Set(employees.map(e => e.departamento))].sort();
      $('#f-dep').html('<option value="">(departamento)</option>' +
        deps.map(d => '<option>' + d + '</option>').join(''));
    });
  }

  function filters() {
    const p = [];
    if ($('#f-date').val())   p.push('date=' + $('#f-date').val());
    if ($('#f-emp').val())    p.push('employee_id=' + $('#f-emp').val());
    if ($('#f-dep').val())    p.push('department=' + encodeURIComponent($('#f-dep').val()));
    if ($('#f-status').val()) p.push('status=' + $('#f-status').val());
    return p.length ? '?' + p.join('&') : '';
  }

  function load() {
    API.get('/shifts' + filters()).done(function (res) {
      const $b = $('#shifts-body').empty();
      if (!res.data.length) {
        $b.append('<tr><td colspan="7" class="text-center text-muted py-3">No hay turnos para los filtros seleccionados.</td></tr>');
        return;
      }
      res.data.forEach(function (s) {
        const actions = [];
        if (window.CAN_MANAGE) {
          if (s.estado === 'programado') {
            actions.push('<button class="btn btn-sm btn-outline-secondary act-edit-shift" data-id="' + s.id + '">Editar</button>');
            actions.push('<button class="btn btn-sm btn-outline-success act-confirm" data-id="' + s.id + '">Confirmar</button>');
            actions.push('<button class="btn btn-sm btn-outline-danger act-del" data-id="' + s.id + '">Borrar</button>');
          }
          if (s.estado === 'confirmado') actions.push('<button class="btn btn-sm btn-outline-warning act-absent" data-id="' + s.id + '">Ausente</button>');
          if (s.estado === 'ausente')    actions.push('<button class="btn btn-sm btn-outline-primary act-sub" data-id="' + s.id + '" data-emp="' + s.empleado_id + '">Sustituto</button>');
        }
        if (window.IS_ADMIN && (s.estado === 'confirmado' || s.estado === 'ausente' || s.estado === 'cubierto')) {
          actions.push('<button class="btn btn-sm btn-outline-dark act-reopen" data-id="' + s.id + '">Reabrir</button>');
        }

        let secondary = '';
        if (s.estado === 'cubierto' && s.sust_apellidos) {
          secondary += '<div class="small text-muted">Sustituye: ' + esc(s.sust_apellidos + ', ' + s.sust_nombre) + '</div>';
        }
        if ((s.estado === 'ausente' || s.estado === 'cubierto') && s.motivo_ausencia) {
          secondary += '<div class="small text-muted">Motivo: ' + esc(s.motivo_ausencia) + '</div>';
        }
        const estadoCell = '<span class="badge estado-' + s.estado + '">' + s.estado + '</span>' + secondary;
        const $tr = $('<tr class="estado-' + s.estado + '">').html(
          '<td>' + s.fecha + '</td><td>' + s.hora_inicio.slice(0,5) + '-' + s.hora_fin.slice(0,5) + '</td>' +
          '<td>' + esc(s.emp_apellidos + ', ' + s.emp_nombre) + '</td>' +
          '<td>' + esc(s.departamento) + '</td><td>' + s.tipo + '</td>' +
          '<td>' + estadoCell + '</td>' +
          '<td class="text-nowrap">' + actions.join(' ') + '</td>'
        );
        $tr.data('shift', s);
        $b.append($tr);
      });
    });
  }

  function checkOverlap() {
    const emp = $('#s-emp').val(), date = $('#s-date').val(), st = $('#s-start').val(), en = $('#s-end').val();
    if (!emp || !date || !st || !en) { $('#overlap-warn').text(''); return; }
    if (en <= st) { $('#overlap-warn').text('La hora de fin debe ser posterior a la de inicio.'); return; }
    API.get('/shifts?employee_id=' + emp + '&date=' + date).done(function (res) {
      const editingId = $('#s-id').val();
      const clash = res.data.some(s => String(s.id) !== editingId && st < s.hora_fin.slice(0,5) && en > s.hora_inicio.slice(0,5));
      $('#overlap-warn').text(clash ? 'Solapa con otro turno de ese empleado.' : '');
    });
  }
  $('#s-emp, #s-date, #s-start, #s-end').on('change', checkOverlap);

  $('#new-shift').on('click', function () {
    $('#s-id').val(''); $('#s-emp').prop('disabled', false);
    $('#s-date').val(''); $('#s-start').val(''); $('#s-end').val(''); $('#overlap-warn').text('');
    $('#shift-modal .modal-title').text('Nuevo turno');
    shiftModal.show();
  });

  $('#shifts-body').on('click', '.act-edit-shift', function () {
    const s = $(this).closest('tr').data('shift');
    $('#s-id').val(s.id);
    $('#s-emp').val(String(s.empleado_id)).prop('disabled', true);
    $('#s-date').val(s.fecha);
    $('#s-start').val(s.hora_inicio.slice(0, 5));
    $('#s-end').val(s.hora_fin.slice(0, 5));
    $('#s-type').val(s.tipo);
    $('#overlap-warn').text('');
    $('#shift-modal .modal-title').text('Editar turno');
    shiftModal.show();
  });

  $('#save-shift').on('click', function () {
    const id = $('#s-id').val();
    if (id) {
      const body = {
        fecha: $('#s-date').val(), hora_inicio: $('#s-start').val(),
        hora_fin: $('#s-end').val(), tipo: $('#s-type').val()
      };
      API.put('/shifts/' + id, body).done(function () {
        shiftModal.hide(); $('#s-emp').prop('disabled', false);
        API.toast('Turno actualizado', 'success'); load();
      });
    } else {
      const body = {
        empleado_id: parseInt($('#s-emp').val(), 10),
        fecha: $('#s-date').val(), hora_inicio: $('#s-start').val(),
        hora_fin: $('#s-end').val(), tipo: $('#s-type').val()
      };
      API.post('/shifts', body).done(function () { shiftModal.hide(); API.toast('Turno guardado', 'success'); load(); });
    }
  });

  $('#shifts-body').on('click', '.act-reopen', function () {
    API.put('/shifts/' + $(this).data('id'), { estado: 'programado' })
      .done(() => { API.toast('Turno reabierto', 'success'); load(); });
  });

  $('#filters').on('submit', function (e) { e.preventDefault(); load(); });

  $('#shifts-body').on('click', '.act-confirm', function () {
    API.put('/shifts/' + $(this).data('id'), { estado: 'confirmado' }).done(() => { API.toast('Confirmado', 'success'); load(); });
  });
  $('#shifts-body').on('click', '.act-absent', function () {
    API.put('/shifts/' + $(this).data('id'), { estado: 'ausente' }).done(() => { API.toast('Marcado ausente', 'success'); load(); });
  });
  $('#shifts-body').on('click', '.act-del', function () {
    if (!confirm('¿Borrar turno?')) return;
    API.del('/shifts/' + $(this).data('id')).done(() => { API.toast('Borrado', 'success'); load(); });
  });

  // Substitute flow: exclude the absent employee from the dropdown, never preselect them.
  $('#shifts-body').on('click', '.act-sub', function () {
    const absentEmp = String($(this).data('emp'));
    $('#sub-shift-id').val($(this).data('id'));
    $('#sub-warn').text(''); $('#sub-motivo').val('');
    const opts = employees
      .filter(e => String(e.id) !== absentEmp)
      .map(e => '<option value="' + e.id + '">' + e.apellidos + ', ' + e.nombre + ' (' + e.departamento + ')</option>')
      .join('');
    $('#sub-emp').html(opts);
    subModal.show();
    $('#sub-emp').trigger('change');
  });
  $('#sub-emp').on('change', function () {
    const shiftId = $('#sub-shift-id').val();
    const sub = $('#sub-emp').val();
    if (!sub) { $('#sub-warn').text(''); return; }
    API.get('/shifts/' + shiftId).done(function (res) {
      const s = res.data;
      API.get('/shifts?employee_id=' + sub + '&date=' + s.fecha).done(function (r2) {
        const clash = r2.data.some(x => s.hora_inicio.slice(0,5) < x.hora_fin.slice(0,5) && s.hora_fin.slice(0,5) > x.hora_inicio.slice(0,5));
        $('#sub-warn').html(clash ? '<span class="text-danger">El sustituto tiene un turno solapado.</span>' : '<span class="text-success">Disponible.</span>');
      });
    });
  });
  $('#save-sub').on('click', function () {
    const id = $('#sub-shift-id').val();
    API.put('/shifts/' + id, { sustituto_id: parseInt($('#sub-emp').val(), 10), motivo_ausencia: $('#sub-motivo').val() })
      .done(function () { subModal.hide(); API.toast('Sustituto asignado', 'success'); load(); });
  });

  loadEmployees().always(load);
});
