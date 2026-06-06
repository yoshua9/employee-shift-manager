$('#login-form').on('submit', function (e) {
  e.preventDefault();
  $.ajax({
    url: '/api/login', method: 'POST', contentType: 'application/json', dataType: 'json',
    data: JSON.stringify({ correo: $('#correo').val(), password: $('#password').val() })
  }).done(function (res) {
    const rol = res.user.rol;
    window.location.href = (rol === 'empleado') ? '/turnos' : '/planificacion';
  }).fail(function (xhr) {
    const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error';
    API.toast(msg, 'danger');
  });
});
