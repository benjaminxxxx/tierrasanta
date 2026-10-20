import flatpickr from "flatpickr";

/**
 * <x-fecha>: fecha que se escribe (dd/mm/aaaa con máscara) o se elige en el almanaque.
 *
 * - Se escriben solo números y la máscara pone las barras: "02625" → "02/06/25" y al salir "02/06/2025".
 *   Si un dígito no puede ser el segundo del día/mes, se completa con 0: "6" en el mes → "06" (no existe el mes 61).
 *   También se aceptan / - . o espacio para cerrar el día o el mes ("2-6-2025" → "02/06/2025").
 * - El valor (wire:model / @entangle) es "Y-m-d" o null, como el input date de siempre. Solo se envía cuando la fecha
 *   está completa y es válida: escribiendo "12/6/2" no se manda nada hasta terminar el año (o salir del campo con un
 *   año de 2 dígitos, que se completa a 20xx). Así un wire:model.live no interrumpe al escribir.
 * - Si el servidor cambia la fecha (botones anterior/siguiente, cargar un registro), el texto se actualiza.
 * - El almanaque (flatpickr) solo se abre con el ícono; tiene "Hoy" y "Borrar". Mes y año se cambian con su selector.
 */
const PAD = (n) => String(n).padStart(2, "0");

function formatear(crudo, borrando) {
    let d = "", m = "", y = "", seg = 0;
    const cola = [...String(crudo || "")];
    while (cola.length) {
        const c = cola.shift();
        if (/[\/\-. ]/.test(c)) {
            if (seg === 0 && d.length === 1) { d = "0" + d; seg = 1; }
            else if (seg === 1 && m.length === 1) { m = "0" + m; seg = 2; }
            continue;
        }
        if (!/\d/.test(c)) continue;
        const n = Number(c);
        if (seg === 0) {
            if (d === "" && n > 3) { d = "0" + c; seg = 1; }
            else if (d === "3" && n > 1) { d = "03"; seg = 1; cola.unshift(c); }
            else if (d === "0" && n === 0) continue; // no existe el día 00
            else { d += c; if (d.length === 2) seg = 1; }
        } else if (seg === 1) {
            if (m === "" && n > 1) { m = "0" + c; seg = 2; }
            else if (m === "1" && n > 2) { m = "01"; seg = 2; cola.unshift(c); }
            else if (m === "0" && n === 0) continue; // no existe el mes 00
            else { m += c; if (m.length === 2) seg = 2; }
        } else if (y.length < 4) {
            y += c;
        }
    }
    let texto = d;
    if (seg >= 1) texto += "/" + m;
    if (seg >= 2) texto += "/" + y;
    // Al borrar no se vuelve a poner la barra final (si no, no se podría borrar)
    if (borrando) texto = texto.replace(/\/+$/, "");
    return texto;
}

function aIso(texto, completarAnio) {
    const p = String(texto || "").split("/");
    if (p.length !== 3) return null;
    let [d, m, y] = p;
    if (completarAnio && y.length === 2) y = "20" + y;
    if (d.length !== 2 || m.length !== 2 || y.length !== 4) return null;
    if (Number(y) < 1900 || Number(y) > 2100) return null; // año mal escrito (ej. 1202)
    const fecha = new Date(Number(y), Number(m) - 1, Number(d));
    if (fecha.getFullYear() !== Number(y) || fecha.getMonth() !== Number(m) - 1 || fecha.getDate() !== Number(d)) return null;
    return `${y}-${m}-${d}`;
}

function aTexto(iso) {
    const s = String(iso || "").slice(0, 10);
    const p = s.split("-");
    return p.length === 3 && p[0].length === 4 ? `${p[2]}/${p[1]}/${p[0]}` : "";
}

export default function campoFecha({ valor = null, min = null, max = null }) {
    return {
        valor,
        texto: "",
        error: "",
        fp: null,
        min,
        max,

        init() {
            this.texto = aTexto(this.valor);
            this.$watch("valor", (v) => {
                // Cambio desde el servidor: actualizar el texto si no se está escribiendo en el campo
                if (document.activeElement !== this.$refs.entrada) {
                    this.texto = aTexto(v);
                    this.error = "";
                }
                if (this.fp) this.fp.setDate(v || null, false);
            });
        },

        destroy() {
            this.fp && this.fp.destroy();
        },

        alEscribir(e) {
            const el = e.target;
            const borrando = (e.inputType || "").startsWith("delete");
            // Editando en medio del texto: no reformatear (movería el cursor); se arregla al salir
            if (el.selectionStart !== null && el.selectionStart < el.value.length) return;
            this.texto = formatear(el.value, borrando);
            this.error = "";
            if (this.texto === "") {
                this.fijar(null);
                return;
            }
            const iso = aIso(this.texto, false);
            if (iso) this.fijar(iso);
        },

        // --- Corregir una fecha ya escrita: clic en día, mes o año selecciona ese grupo y se reescribe ---
        // dd/mm/aaaa: día 0-2, mes 3-5, año 6-10
        seg: null,
        buffer: "",

        seleccionarSeg(i) {
            const rangos = [[0, 2], [3, 5], [6, 10]];
            this.seg = i;
            this.buffer = "";
            this.$nextTick(() => this.$refs.entrada.setSelectionRange(...rangos[i]));
        },

        alHacerClic(e) {
            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(this.texto)) {
                this.seg = null;
                return;
            }
            const pos = e.target.selectionStart ?? 0;
            // Si el usuario arrastró para seleccionar un tramo, respetarlo
            if (e.target.selectionEnd !== pos) return;
            this.seleccionarSeg(pos <= 2 ? 0 : pos <= 5 ? 1 : 2);
        },

        alTeclear(e) {
            if (this.seg === null) return;
            const k = e.key;
            if (k === "ArrowRight" || k === "/" || k === "-" || k === ".") {
                e.preventDefault();
                if (this.seg < 2) this.seleccionarSeg(this.seg + 1);
                return;
            }
            if (k === "ArrowLeft") {
                e.preventDefault();
                if (this.seg > 0) this.seleccionarSeg(this.seg - 1);
                return;
            }
            if (!/^\d$/.test(k)) {
                // Borrar, Tab, etc.: edición normal del texto
                if (k !== "Shift") this.seg = null;
                return;
            }
            e.preventDefault();
            const partes = this.texto.split("/");
            const n = Number(k);
            if (this.seg < 2) {
                // Día (máx. 31, primer dígito hasta 3) o mes (máx. 12, primer dígito hasta 1)
                const [tope, maxPrimero] = this.seg === 0 ? [31, 3] : [12, 1];
                const doble = this.buffer + k;
                if (this.buffer !== "" && Number(doble) >= 1 && Number(doble) <= tope) {
                    partes[this.seg] = doble;
                    this.texto = partes.join("/");
                    this.seleccionarSeg(this.seg + 1);
                } else if (n > maxPrimero) {
                    // "5" en el día → 05 y pasa al mes (no existe el día 5x)
                    partes[this.seg] = "0" + k;
                    this.texto = partes.join("/");
                    this.seleccionarSeg(this.seg + 1);
                } else {
                    // Primer dígito que puede tener segundo: se muestra 0k y se espera el siguiente
                    partes[this.seg] = "0" + k;
                    this.texto = partes.join("/");
                    const seg = this.seg;
                    this.seleccionarSeg(seg);
                    this.buffer = n === 0 ? "" : k;
                    this.error = "";
                    return; // se espera el segundo dígito: no se envía una fecha a medias
                }
            } else {
                this.buffer = (this.buffer + k).slice(0, 4);
                partes[2] = this.buffer;
                this.texto = partes.join("/");
                if (this.buffer.length === 4) {
                    this.seg = null;
                } else {
                    this.$nextTick(() => this.$refs.entrada.setSelectionRange(6, 6 + this.buffer.length));
                    this.error = "";
                    return; // año incompleto: se espera (al salir, 2 dígitos → 20xx)
                }
            }
            this.error = "";
            const iso = aIso(this.texto, false);
            if (iso) this.fijar(iso);
            else if (/^\d{2}\/\d{2}\/\d{4}$/.test(this.texto)) this.error = "Esa fecha no existe";
        },

        alSalir() {
            this.seg = null;
            if (String(this.texto).trim() === "") {
                this.texto = "";
                this.error = "";
                this.fijar(null);
                return;
            }
            this.texto = formatear(this.texto, false);
            const iso = aIso(this.texto, true);
            if (!iso) {
                this.error = "Fecha incompleta o inválida (dd/mm/aaaa)";
                return;
            }
            this.texto = aTexto(iso);
            this.fijar(iso);
        },

        fijar(iso) {
            if (iso && ((this.min && iso < this.min) || (this.max && iso > this.max))) {
                this.error = `Fuera del rango permitido${this.min ? " desde " + aTexto(this.min) : ""}${this.max ? " hasta " + aTexto(this.max) : ""}`;
                return;
            }
            this.error = "";
            if ((this.valor || null) !== iso) this.valor = iso;
        },

        limpiar() {
            this.texto = "";
            this.fijar(null);
            this.fp && this.fp.close();
        },

        almanaque() {
            if (!this.fp) {
                this.fp = flatpickr(this.$refs.ancla, {
                    dateFormat: "Y-m-d",
                    altInput: false,
                    allowInput: false,
                    clickOpens: false,
                    static: true,
                    monthSelectorType: "dropdown",
                    defaultDate: this.valor || null,
                    minDate: this.min || null,
                    maxDate: this.max || null,
                    onChange: (fechas, iso) => {
                        this.texto = aTexto(iso);
                        this.fijar(iso || null);
                    },
                    onReady: (_, __, fp) => {
                        const pie = document.createElement("div");
                        pie.className = "flex justify-between gap-2 p-2 border-t border-border text-sm";
                        const boton = (texto, accion, clase) => {
                            const b = document.createElement("button");
                            b.type = "button";
                            b.textContent = texto;
                            b.className = `px-2 py-1 rounded-md ${clase}`;
                            b.addEventListener("click", accion);
                            return b;
                        };
                        pie.append(
                            boton("Hoy", () => fp.setDate(new Date(), true), "hover:bg-muted"),
                            boton("Borrar", () => this.limpiar(), "text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"),
                            boton("Cerrar", () => fp.close(), "hover:bg-muted"),
                        );
                        fp.calendarContainer.appendChild(pie);
                    },
                });
            }
            this.fp.isOpen ? this.fp.close() : this.fp.open();
        },
    };
}
