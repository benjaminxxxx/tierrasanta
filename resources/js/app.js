import "./bootstrap";
import Handsontable from "./handsontable";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "flowbite";

import { Spanish } from "flatpickr/dist/l10n/es.js"; // ✅ importa el idioma
import { Calendar } from "@fullcalendar/core";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import { SumadorSeleccionPlugin } from "./plugins/SumadorSeleccionPlugin";
import searchableSelect from "./components/searchable-select";

// <x-searchable-select>: se registra una sola vez (antes cada variante traía su propio <script>)
if (window.Alpine) {
    window.Alpine.data("searchableSelect", searchableSelect);
} else {
    document.addEventListener("alpine:init", () => window.Alpine.data("searchableSelect", searchableSelect));
}

// Establece el idioma globalmente
flatpickr.localize(Spanish);
flatpickr.setDefaults({
    dateFormat: "d/m/Y", // ✅ visible al usuario
    altInput: true,
    altFormat: "d/m/Y", // ✅ formato visible en input
    allowInput: true,
});

window.FullCalendar = {
    Calendar,
    resourceTimelinePlugin,
};
flatpickr(".datepicker", {
    //mode: "range",
});

Handsontable.plugins.registerPlugin("SumadorSeleccion", SumadorSeleccionPlugin);

window.HstConfig = {
    datePickerConfig: {
        i18n: {
            previousMonth: "Mes anterior",
            nextMonth: "Mes siguiente",
            months: [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ],
            weekdays: [
                "Domingo",
                "Lunes",
                "Martes",
                "Miércoles",
                "Jueves",
                "Viernes",
                "Sábado",
            ],
            weekdaysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
        },
        firstDay: 1,
    },
    language: "es-MX",
    colHeaders: true,
    rowHeaders: true,
    width: '100%',
    licenseKey: "non-commercial-and-evaluation",
    manualColumnResize: false,
    manualRowResize: true,
    stretchH: "all",
    //minSpareRows: 1,
    autoColumnSize: false,
};
