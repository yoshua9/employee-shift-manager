$(function () {
  let monday = startOfWeek(new Date());

  function startOfWeek(d) {
    const x = new Date(d); const day = (x.getDay() + 6) % 7; // Mon=0
    x.setDate(x.getDate() - day); x.setHours(0, 0, 0, 0); return x;
  }
  // Local date formatting — avoids the UTC day-shift that toISOString() causes in Europe/Madrid.
  function iso(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  function prettyRange(a, b) {
    if (a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear()) {
      return a.getDate() + ' – ' + b.getDate() + ' de ' + MESES[a.getMonth()] + ' de ' + a.getFullYear();
    }
    if (a.getFullYear() === b.getFullYear()) {
      return a.getDate() + ' de ' + MESES[a.getMonth()] + ' – ' + b.getDate() + ' de ' + MESES[b.getMonth()] + ' de ' + a.getFullYear();
    }
    return a.getDate() + ' de ' + MESES[a.getMonth()] + ' de ' + a.getFullYear() +
           ' – ' + b.getDate() + ' de ' + MESES[b.getMonth()] + ' de ' + b.getFullYear();
  }

  function render() {
    const end = new Date(monday); end.setDate(end.getDate() + 6);
    $('#week-label').text(prettyRange(monday, end));
    API.get('/shifts').done(function (res) {
      const byEmp = {};
      res.data.forEach(function (s) {
        if (s.fecha < iso(monday) || s.fecha > iso(end)) return;
        const name = s.emp_apellidos + ', ' + s.emp_nombre;
        (byEmp[name] = byEmp[name] || []).push(s);
      });
      const $b = $('#planning-body').empty();
      Object.keys(byEmp).sort().forEach(function (name) {
        const $tr = $('<tr>').append($('<td>').text(name));
        for (let i = 0; i < 7; i++) {
          const day = new Date(monday); day.setDate(day.getDate() + i);
          const cell = $('<td class="planning-cell">');
          byEmp[name].filter(s => s.fecha === iso(day)).forEach(function (s) {
            const item = $('<div class="mb-1">').text(s.hora_inicio.slice(0, 5) + '-' + s.hora_fin.slice(0, 5) + ' ');
            const badge = $('<span class="badge estado-' + s.estado + '">').text(s.estado);
            if (s.estado === 'cubierto' && s.sust_apellidos) {
              badge.attr('title', 'Cubre: ' + s.sust_apellidos + ', ' + s.sust_nombre);
            }
            item.append(badge);
            cell.append(item);
          });
          $tr.append(cell);
        }
        $b.append($tr);
      });
      if (!Object.keys(byEmp).length) $('#planning-body').append('<tr><td colspan="8" class="text-muted">Sin turnos esta semana.</td></tr>');
    });
  }

  $('#prev-week').on('click', () => { monday.setDate(monday.getDate() - 7); render(); });
  $('#next-week').on('click', () => { monday.setDate(monday.getDate() + 7); render(); });
  render();
});
