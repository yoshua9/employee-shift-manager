$(function () {
  const modal = new bootstrap.Modal('#emp-modal');
  const FALLBACK_DEPARTMENTS = ['Soporte', 'Ventas', 'Operaciones'];

  function setDepartments(list) {
    const deps = list && list.length ? list : FALLBACK_DEPARTMENTS;
    $('#e-dep').html(deps.map(d => '<option>' + $('<i>').text(d).html() + '</option>').join(''));
  }

  function load() {
    API.get('/employees').done(function (res) {
      const deps = [...new Set(res.data.map(e => e.departamento))].sort();
      setDepartments(deps);
      const $b = $('#emp-body').empty();
      if (!res.data.length) {
        $b.append('<tr><td colspan="6" class="text-center text-muted py-3">No hay empleados.</td></tr>');
        return;
      }
      res.data.forEach(function (e) {
        $b.append($('<tr>').html(
          '<td>' + $('<i>').text(e.apellidos + ', ' + e.nombre).html() + '</td>' +
          '<td>' + $('<i>').text(e.correo).html() + '</td>' +
          '<td>' + $('<i>').text(e.departamento).html() + '</td><td>' + e.rol + '</td>' +
          '<td>' + (e.activo ? 'Sí' : 'No') + '</td>' +
          '<td><button class="btn btn-sm btn-outline-secondary act-edit" data-id="' + e.id + '">Editar</button> ' +
          '<button class="btn btn-sm btn-outline-danger act-del" data-id="' + e.id + '">Borrar</button></td>'
        ).data('emp', e));
      });
    });
  }

  $('#new-emp').on('click', function () {
    $('#e-id').val(''); $('#e-nombre,#e-apellidos,#e-correo,#e-pass').val('');
    $('#e-rol').val('empleado'); $('#e-activo').prop('checked', true);
    modal.show();
  });

  $('#emp-body').on('click', '.act-edit', function () {
    const e = $(this).closest('tr').data('emp');
    $('#e-id').val(e.id); $('#e-nombre').val(e.nombre); $('#e-apellidos').val(e.apellidos);
    $('#e-correo').val(e.correo); $('#e-pass').val(''); $('#e-dep').val(e.departamento);
    $('#e-rol').val(e.rol); $('#e-activo').prop('checked', !!e.activo);
    modal.show();
  });

  $('#save-emp').on('click', function () {
    const id = $('#e-id').val();
    const body = {
      nombre: $('#e-nombre').val(), apellidos: $('#e-apellidos').val(), correo: $('#e-correo').val(),
      departamento: $('#e-dep').val(), rol: $('#e-rol').val(), activo: $('#e-activo').is(':checked')
    };
    const pass = $('#e-pass').val();
    if (pass) body.contrasena = pass;
    if (!id && !body.contrasena) { API.toast('La contraseña es obligatoria', 'danger'); return; }
    const p = id ? API.put('/employees/' + id, body) : API.post('/employees', body);
    p.done(function () { modal.hide(); API.toast('Empleado guardado', 'success'); load(); });
  });

  $('#emp-body').on('click', '.act-del', function () {
    if (!confirm('¿Borrar empleado?')) return;
    API.del('/employees/' + $(this).data('id')).done(() => { API.toast('Borrado', 'success'); load(); });
  });

  load();
});
