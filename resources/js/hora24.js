// resources/js/hora24.js
// Tipo de celda "hora24" para Handsontable: horas en formato 24h con dos puntos (HH:mm).
// Se reemplaza al tipo "time" de Handsontable (desde v17 solo acepta HH:mm y muestra
// #bad-value# con los datos que se pegan desde Excel).
//
// Lo que se escribe, pega o autorellena se normaliza al salir de la celda:
//   5      → 05:00      15     → 15:00
//   5:30   → 05:30      7:05   → 07:05
//   5.30   → 05:30      5.5    → 05:50   (Excel con "sistema de punto": los dígitos son minutos, no decimales)
//   730    → 07:30      1530   → 15:30
//   07:30:00 → 07:30    3:00 pm → 15:00
// Lo que no se pueda interpretar se deja tal cual y la celda queda marcada como inválida.
//
// Uso en columnas: { data: 'hora_inicio', type: 'hora24' }

const FORMATO = /^([01]\d|2[0-3]):[0-5]\d$/;

/**
 * Convierte un valor escrito/pegado a "HH:mm". Devuelve null si está vacío
 * y el valor original si no se puede interpretar.
 */
export function normalizarHora24(valor) {
    if (valor === null || valor === undefined) return null;

    let texto = String(valor).trim().toLowerCase();
    if (texto === "") return null;

    // am / pm (por si viene así desde Excel)
    let sufijo = null;
    const ampm = texto.match(/^(.*?)\s*([ap])\.?\s*m\.?$/);
    if (ampm) {
        texto = ampm[1].trim();
        sufijo = ampm[2];
    }

    let horas;
    let minutos;
    let m;

    if ((m = texto.match(/^(\d{1,2})$/))) {
        // Solo la hora: 5 → 05:00
        horas = +m[1];
        minutos = 0;
    } else if ((m = texto.match(/^(\d{1,2})[.,](\d{1,2})$/))) {
        // Sistema de punto: 5.30 → 05:30, 5.5 → 05:50 (los dígitos son minutos)
        horas = +m[1];
        minutos = +m[2].padEnd(2, "0");
    } else if ((m = texto.match(/^(\d{1,2}):(\d{1,2})(?::\d{1,2})?$/))) {
        // Dos puntos (con segundos opcionales): 7:05, 07:30:00
        horas = +m[1];
        minutos = +m[2];
    } else if ((m = texto.match(/^(\d{1,2})(\d{2})$/))) {
        // Sin separador: 730 → 07:30, 1530 → 15:30
        horas = +m[1];
        minutos = +m[2];
    } else {
        return valor;
    }

    if (sufijo) {
        if (horas < 1 || horas > 12) return valor;
        if (sufijo === "p" && horas < 12) horas += 12;
        if (sufijo === "a" && horas === 12) horas = 0;
    }

    if (horas > 23 || minutos > 59) return valor;

    return `${String(horas).padStart(2, "0")}:${String(minutos).padStart(2, "0")}`;
}

export function registrarHora24(Handsontable) {
    Handsontable.cellTypes.registerCellType("hora24", {
        editor: Handsontable.editors.TextEditor,
        renderer: Handsontable.renderers.TextRenderer,
        validator(valor, callback) {
            callback(valor === null || valor === undefined || valor === "" || FORMATO.test(valor));
        },
        allowInvalid: true,
    });

    // Hook global: normaliza edición, pegado y autorelleno en las celdas "hora24" de cualquier tabla.
    Handsontable.hooks.add("beforeChange", function (changes) {
        if (!changes) return;
        for (const change of changes) {
            if (!change) continue;
            const [row, prop, , nuevo] = change;
            const col = typeof prop === "number" ? prop : this.propToCol(prop);
            if (typeof col !== "number" || col < 0) continue;
            if (this.getCellMeta(row, col).type !== "hora24") continue;
            change[3] = normalizarHora24(nuevo);
        }
    });
}
