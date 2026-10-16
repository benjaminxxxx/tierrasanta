/**
 * Fechas pegadas o escritas a mano (copiadas de Excel, de un correo…) → formato del campo.
 *
 * - Handsontable: en toda columna `type: 'date'`, el valor se convierte al `dateFormat` de la columna antes de
 *   guardarse en la tabla (antes "31/01/2023" quedaba tal cual en rojo y el servidor fallaba al guardar).
 * - <input type="date">: el navegador no deja pegar texto; al pegar se interpreta y se escribe en el campo.
 *
 * Acepta: 31/01/2023, 31-1-23, 31.01.2023, 2023-01-31, 2023/1/31, 31-ene-2023, 31 ene. 23 y el número de serie
 * de Excel (45688). Día antes que mes, como en Perú.
 */

const MESES = {
    ene: 1, jan: 1, feb: 2, mar: 3, abr: 4, apr: 4, may: 5, jun: 6, jul: 7, ago: 8, aug: 8,
    sep: 9, set: 9, oct: 10, nov: 11, dic: 12, dec: 12,
};

function anioCompleto(y) {
    const n = parseInt(y, 10);
    if (String(y).length <= 2) return n < 70 ? 2000 + n : 1900 + n;
    return n;
}

function valida(y, m, d) {
    if (!(y >= 1900 && y <= 2100 && m >= 1 && m <= 12 && d >= 1 && d <= 31)) return null;
    const f = new Date(Date.UTC(y, m - 1, d));
    return f.getUTCMonth() === m - 1 && f.getUTCDate() === d ? { y, m, d } : null;
}

/** @returns {{y:number,m:number,d:number}|null} */
export function parsearFecha(valor) {
    if (valor === null || valor === undefined) return null;
    const texto = String(valor).trim().toLowerCase().replace(/\s+/g, " ");
    if (!texto) return null;

    // Número de serie de Excel (días desde 1899-12-30)
    if (/^\d{5}(\.\d+)?$/.test(texto)) {
        const serie = Math.floor(parseFloat(texto));
        if (serie > 20000 && serie < 80000) {
            const f = new Date(Date.UTC(1899, 11, 30) + serie * 86400000);
            return valida(f.getUTCFullYear(), f.getUTCMonth() + 1, f.getUTCDate());
        }
        return null;
    }

    // Quita la hora si viene ("31/01/2023 00:00:00", "2023-01-31T05:00:00Z")
    const fecha = texto.split(/[ t](?=\d{1,2}:)/)[0];

    let m = fecha.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/); // año primero
    if (m) return valida(+m[1], +m[2], +m[3]);

    m = fecha.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{2}|\d{4})$/); // día primero
    if (m) return valida(anioCompleto(m[3]), +m[2], +m[1]);

    m = fecha.match(/^(\d{1,2})[-/. ]([a-z]{3})[a-z]*\.?[-/. ](\d{2}|\d{4})$/); // 31-ene-2023
    if (m && MESES[m[2]]) return valida(anioCompleto(m[3]), MESES[m[2]], +m[1]);

    return null;
}

/** Formato tipo moment de Handsontable: YYYY, MM, DD, M, D. */
export function formatearFecha({ y, m, d }, formato = "YYYY-MM-DD") {
    const dos = (n) => String(n).padStart(2, "0");
    return formato
        .replace("YYYY", String(y))
        .replace("MM", dos(m))
        .replace("DD", dos(d))
        .replace(/\bM\b/, String(m))
        .replace(/\bD\b/, String(d));
}

/** Convierte las fechas que entran a columnas de tipo fecha de cualquier tabla Handsontable. */
export function registrarFechasHandsontable(Handsontable) {
    Handsontable.hooks.add("beforeChange", function (cambios) {
        if (!cambios) return;
        for (const cambio of cambios) {
            if (!cambio) continue;
            const [fila, prop, , nuevo] = cambio;
            if (nuevo === null || nuevo === undefined || nuevo === "") continue;
            const columna = typeof prop === "number" ? prop : this.propToCol(prop);
            if (typeof columna !== "number" || columna < 0) continue;
            const meta = this.getCellMeta(fila, columna);
            if (meta.type !== "date") continue;
            const fecha = parsearFecha(nuevo);
            if (fecha) cambio[3] = formatearFecha(fecha, meta.dateFormat || "YYYY-MM-DD");
        }
    });
}

/** Permite pegar texto en cualquier <input type="date"> de la aplicación. */
export function registrarPegadoFechas() {
    document.addEventListener(
        "paste",
        (evento) => {
            const campo = evento.target;
            if (!(campo instanceof HTMLInputElement) || campo.type !== "date") return;
            const fecha = parsearFecha(evento.clipboardData?.getData("text"));
            if (!fecha) return;
            evento.preventDefault();
            campo.value = formatearFecha(fecha);
            campo.dispatchEvent(new Event("input", { bubbles: true }));
            campo.dispatchEvent(new Event("change", { bubbles: true }));
        },
        true
    );
}
