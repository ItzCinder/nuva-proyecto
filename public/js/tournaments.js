document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-tournament-form]');
  if (form) {
    const discipline = form.querySelector('[data-discipline]');
    const format = form.querySelector('[data-format]');
    const teams = form.querySelector('[data-has-team]');
    const refresh = () => {
      const custom = form.querySelector('[data-custom-discipline]');
      custom.hidden = discipline.value !== 'other';
      custom.querySelector('input').required = !custom.hidden;
      form.querySelector('[data-team-size]').hidden = teams.value !== '1';
      form.querySelector('[data-team-size] input').required = teams.value === '1';
      form.querySelector('[name=max_participants]').parentElement.firstChild.textContent = teams.value === '1' ? 'Cupo de equipos' : 'Cupo de participantes';
      form.querySelectorAll('[data-rule]').forEach(el => { el.hidden = el.dataset.rule !== format.value; });
      const help = {league:'Todos contra todos; elige una vuelta o ida y vuelta.',swiss:'Rondas por rendimiento; los cruces se prepararán después.',elimination:'Llave de eliminación; puede haber pases directos.'};
      form.querySelector('[data-format-help]').textContent = help[format.value] || '';
    };
    [discipline, format, teams].forEach(el => el.addEventListener('change', refresh));
    refresh();
    if (form.dataset.started === '1') {
      ['format','has_team','max_participants','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label','status','start_date','end_date'].forEach(name => { const input=form.elements[name]; if(input) input.disabled=true; });
    }
    format.addEventListener('change', () => {
      if (form.dataset.started === '1') return;
      form.elements.points_win.value = format.value === 'swiss' ? '1' : '3';
      form.elements.points_draw.value = format.value === 'swiss' ? '0.5' : '1';
      form.elements.allow_draws.checked = format.value !== 'elimination';
    });
  }
  const calendar = document.querySelector('[data-calendar]');
  if (!calendar) return;
  const readUTC = text => new Date(text.replace(' ', 'T') + 'Z');
  const start = readUTC(calendar.dataset.start), end = readUTC(calendar.dataset.end);
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;
  const first = calendar.dataset.startDay, last = calendar.dataset.endDay;
  let month = new Date(first.slice(0,7) + '-01T00:00:00Z');
  const events = JSON.parse(document.querySelector('[data-calendar-events]')?.textContent || '[]');
  const days = calendar.querySelector('[data-calendar-days]');
  const render = () => {
    calendar.querySelector('[data-calendar-month]').textContent = new Intl.DateTimeFormat('es-UY',{month:'long',year:'numeric',timeZone:'UTC'}).format(month);
    days.replaceChildren();
    ['L','Ma','Mi','J','V','S','D'].forEach(name => { const el=document.createElement('span');el.className='weekday';el.textContent=name;days.append(el); });
    const offset=(month.getUTCDay()+6)%7;
    const total=Math.ceil((offset+new Date(Date.UTC(month.getUTCFullYear(),month.getUTCMonth()+1,0)).getUTCDate())/7)*7;
    for(let i=0;i<total;i++) {
      const date=new Date(Date.UTC(month.getUTCFullYear(),month.getUTCMonth(),i-offset+1));
      const el=document.createElement('span');el.textContent=String(date.getUTCDate());
      if(date.getUTCMonth()!==month.getUTCMonth()) el.classList.add('outside');
      const day=date.toISOString().slice(0,10);
      if(day>=first && day<=last) el.classList.add('within');
      if(day===first || day===last) el.classList.add('edge');
      const matches=events.filter(event=>event.day===day);
      if(matches.length) {el.classList.add('has-matches');el.title=matches.map(event=>event.title).join(' · ');el.setAttribute('aria-label',day+': '+el.title);}
      days.append(el);
    }
  };
  calendar.querySelector('[data-calendar-prev]').addEventListener('click',()=>{month=new Date(Date.UTC(month.getUTCFullYear(),month.getUTCMonth()-1,1));render();});
  calendar.querySelector('[data-calendar-next]').addEventListener('click',()=>{month=new Date(Date.UTC(month.getUTCFullYear(),month.getUTCMonth()+1,1));render();});
  render();
  document.querySelector('[data-calendar-download]').addEventListener('click',()=>{
    const stamp=d=>d.toISOString().replace(/[-:]/g,'').replace(/\.\d{3}Z$/,'Z');
    const escape=s=>s.replace(/[\\,;]/g,'\\$&').replace(/\r?\n/g,'\\n');
    const body=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Nuva//Torneos//ES','BEGIN:VEVENT',
      'UID:nuva-torneo-'+calendar.dataset.id+'@local','DTSTAMP:'+stamp(new Date()),'DTSTART:'+stamp(start),
      'DTEND:'+stamp(end),'SUMMARY:'+escape(calendar.dataset.title),'END:VEVENT',...events.flatMap(event=>['BEGIN:VEVENT','UID:nuva-encuentro-'+event.id+'@local','DTSTAMP:'+stamp(new Date()),'DTSTART:'+stamp(readUTC(event.at)),'SUMMARY:'+escape(event.title),'STATUS:'+(event.status==='Suspendido'?'CANCELLED':'CONFIRMED'),'END:VEVENT']),'END:VCALENDAR'].join('\r\n');
    const url=URL.createObjectURL(new Blob([body],{type:'text/calendar;charset=utf-8'}));
    const link=document.createElement('a');link.href=url;link.download='torneo-nuva.ics';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
  });
});

document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
