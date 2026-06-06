// Central AJAX layer: CSRF header + JSON + toast feedback.
const API = (function () {
  const csrf = $('meta[name="csrf-token"]').attr('content') || '';

  function toast(message, variant) {
    const el = $(
      '<div class="toast align-items-center text-bg-' + (variant || 'primary') + ' border-0" role="alert">' +
      '<div class="d-flex"><div class="toast-body">' + $('<div>').text(message).html() + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
    );
    $('#toast-container').append(el);
    const t = new bootstrap.Toast(el[0], { delay: 3500 });
    t.show();
    el.on('hidden.bs.toast', () => el.remove());
  }

  function request(method, path, body) {
    return $.ajax({
      url: '/api' + path,
      method: method,
      contentType: 'application/json',
      headers: { 'X-CSRF-Token': csrf },
      data: body ? JSON.stringify(body) : undefined,
      dataType: 'json'
    }).fail(function (xhr) {
      const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error inesperado';
      toast(msg, 'danger');
    });
  }

  return {
    get:  (p)    => request('GET', p),
    post: (p, b) => request('POST', p, b),
    put:  (p, b) => request('PUT', p, b),
    del:  (p)    => request('DELETE', p),
    toast: toast
  };
})();

$(function () {
  $('#logout-btn').on('click', function () {
    API.post('/logout').done(() => { window.location.href = '/login'; });
  });
});
