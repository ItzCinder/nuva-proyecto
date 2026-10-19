function readCalendarDate(text) {
  return new Date(text.replace(" ", "T") + "Z");
}

function calendarTimestamp(date) {
  return date
    .toISOString()
    .replace(/[-:]/g, "")
    .replace(/\.\d{3}Z$/, "Z");
}

function calendarText(text) {
  return text.replace(/[\\,;]/g, "\\$&").replace(/\r?\n/g, "\\n");
}

function initializeCalendar(calendar) {
  const start = readCalendarDate(calendar.dataset.start);
  const end = readCalendarDate(calendar.dataset.end);
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
    return;
  }

  const firstDay = calendar.dataset.startDay;
  const lastDay = calendar.dataset.endDay;
  let month = new Date(firstDay.slice(0, 7) + "-01T00:00:00Z");
  const eventsElement = document.querySelector("[data-calendar-events]");
  let events = [];
  if (eventsElement) {
    events = JSON.parse(eventsElement.textContent);
  }
  const days = calendar.querySelector("[data-calendar-days]");

  function drawCalendar() {
    const monthFormat = new Intl.DateTimeFormat("es-UY", {
      month: "long",
      year: "numeric",
      timeZone: "UTC",
    });
    calendar.querySelector("[data-calendar-month]").textContent =
      monthFormat.format(month);
    days.replaceChildren();

    for (const name of ["L", "Ma", "Mi", "J", "V", "S", "D"]) {
      const heading = document.createElement("span");
      heading.className = "weekday";
      heading.textContent = name;
      days.append(heading);
    }

    const yearNumber = month.getUTCFullYear();
    const monthNumber = month.getUTCMonth();
    const offset = (month.getUTCDay() + 6) % 7;
    const daysInMonth = new Date(
      Date.UTC(yearNumber, monthNumber + 1, 0),
    ).getUTCDate();
    const cells = Math.ceil((offset + daysInMonth) / 7) * 7;

    for (let position = 0; position < cells; position++) {
      const date = new Date(
        Date.UTC(yearNumber, monthNumber, position - offset + 1),
      );
      const day = date.toISOString().slice(0, 10);
      const cell = document.createElement("span");
      cell.textContent = String(date.getUTCDate());
      if (date.getUTCMonth() !== monthNumber) {
        cell.classList.add("outside");
      }
      if (day >= firstDay && day <= lastDay) {
        cell.classList.add("within");
      }
      if (day === firstDay || day === lastDay) {
        cell.classList.add("edge");
      }

      const matchNames = [];
      for (const event of events) {
        if (event.day === day) {
          matchNames.push(event.title);
        }
      }
      if (matchNames.length > 0) {
        cell.classList.add("has-matches");
        cell.title = matchNames.join(" · ");
        cell.setAttribute("aria-label", day + ": " + cell.title);
      }
      days.append(cell);
    }
  }

  function previousMonth() {
    month = new Date(
      Date.UTC(month.getUTCFullYear(), month.getUTCMonth() - 1, 1),
    );
    drawCalendar();
  }

  function nextMonth() {
    month = new Date(
      Date.UTC(month.getUTCFullYear(), month.getUTCMonth() + 1, 1),
    );
    drawCalendar();
  }

  function downloadCalendar() {
    const lines = [
      "BEGIN:VCALENDAR",
      "VERSION:2.0",
      "PRODID:-//Nuva//Torneos//ES",
      "BEGIN:VEVENT",
      "UID:nuva-torneo-" + calendar.dataset.id + "@local",
      "DTSTAMP:" + calendarTimestamp(new Date()),
      "DTSTART:" + calendarTimestamp(start),
      "DTEND:" + calendarTimestamp(end),
      "SUMMARY:" + calendarText(calendar.dataset.title),
      "END:VEVENT",
    ];
    for (const event of events) {
      let status = "CONFIRMED";
      if (event.status === "Suspendido") {
        status = "CANCELLED";
      }
      lines.push(
        "BEGIN:VEVENT",
        "UID:nuva-encuentro-" + event.id + "@local",
        "DTSTAMP:" + calendarTimestamp(new Date()),
        "DTSTART:" + calendarTimestamp(readCalendarDate(event.at)),
        "SUMMARY:" + calendarText(event.title),
        "STATUS:" + status,
        "END:VEVENT",
      );
    }
    lines.push("END:VCALENDAR");
    const file = new Blob([lines.join("\r\n")], {
      type: "text/calendar;charset=utf-8",
    });
    const url = URL.createObjectURL(file);
    const link = document.createElement("a");
    link.href = url;
    link.download = "torneo-nuva.ics";
    link.click();
    window.setTimeout(function () {
      URL.revokeObjectURL(url);
    }, 1000);
  }

  calendar
    .querySelector("[data-calendar-prev]")
    .addEventListener("click", previousMonth);
  calendar
    .querySelector("[data-calendar-next]")
    .addEventListener("click", nextMonth);
  const download = document.querySelector("[data-calendar-download]");
  if (download) {
    download.addEventListener("click", downloadCalendar);
  }
  drawCalendar();
}

document.addEventListener("DOMContentLoaded", function () {
  const calendar = document.querySelector("[data-calendar]");
  if (calendar) {
    initializeCalendar(calendar);
  }
});
