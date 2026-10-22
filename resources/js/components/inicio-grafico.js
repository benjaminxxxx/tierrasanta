/**
 * Gráficos del inicio (Chart.js, cargado globalmente en layouts/app). Reglas en DESIGN.md § Gráficos:
 * - "enfasis": una serie; el mes elegido en el color de serie y el resto en gris (el título dice qué se mide).
 * - "apilado": hasta 3 series en el orden fijo de la paleta, con 2px de separación entre segmentos.
 * Barras de 24px máx. con 4px redondeados arriba, cuadrícula fina, ejes discretos, tooltip al pasar el mouse.
 * Los colores de la paleta están validados (contraste y daltonismo) contra el fondo de tarjeta claro y oscuro.
 * Se vuelve a dibujar al cambiar entre modo claro y oscuro (clase "dark" del body).
 */
const SERIES = {
    light: ["#2a78d6", "#eb6834", "#1baf7a"],
    dark: ["#3987e5", "#d95926", "#199e70"],
};
const FUENTE = "Inter, ui-sans-serif, system-ui, sans-serif";
const ATENUADO = { light: "#d5d7de", dark: "#2c353d" };

const token = (nombre) => `rgb(${getComputedStyle(document.body).getPropertyValue(nombre).trim()})`;
const compacto = new Intl.NumberFormat("es-PE", { notation: "compact", maximumFractionDigits: 1 });
const entero = new Intl.NumberFormat("es-PE", { maximumFractionDigits: 0 });

export default function inicioGrafico(cfg) {
    return {
        tabla: false,
        grafico: null,

        init() {
            this.$nextTick(() => requestAnimationFrame(() => this.dibujar()));
            // Solo se redibuja cuando cambia el modo (el body cambia otras clases al cargar)
            this.observador = new MutationObserver(() => {
                if (this.modo && this.modo !== this.modoActual()) {
                    this.dibujar();
                }
            });
            this.observador.observe(document.body, { attributes: true, attributeFilter: ["class"] });
        },

        destroy() {
            this.observador?.disconnect();
            this.grafico?.destroy();
        },

        modoActual() {
            return document.body.classList.contains("dark") ? "dark" : "light";
        },

        formato(v) {
            return cfg.unidad === "S/" ? `S/ ${entero.format(v)}` : `${entero.format(v)} ${cfg.unidad}`;
        },

        dibujar() {
            if (typeof window.Chart === "undefined" || !this.$refs.lienzo) {
                return;
            }
            const modo = (this.modo = this.modoActual());
            const superficie = token("--color-paper");
            const tenue = token("--color-pewter");
            const linea = token("--color-hairline");
            const apilado = cfg.tipo === "apilado";
            const ultimo = cfg.series.length - 1;

            const datasets = cfg.series.map((s, i) => {
                const color = apilado ? SERIES[modo][i] : s.datos.map((_, j) => (j === cfg.resaltar ? SERIES[modo][0] : ATENUADO[modo]));
                return {
                    label: s.nombre,
                    data: s.datos,
                    backgroundColor: color,
                    hoverBackgroundColor: color,
                    maxBarThickness: 24,
                    // Redondeo solo en el extremo de datos; separación de 2px (color de la tarjeta) entre segmentos apilados
                    borderRadius: !apilado || i === ultimo ? { topLeft: 4, topRight: 4 } : 0,
                    borderSkipped: apilado && i > 0 ? false : "start",
                    borderWidth: apilado && i > 0 ? { top: 0, left: 0, right: 0, bottom: 2 } : 0,
                    borderColor: superficie,
                };
            });

            this.grafico?.destroy();
            this.grafico = new window.Chart(this.$refs.lienzo, {
                type: "bar",
                data: { labels: cfg.etiquetas, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: "index", intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: token("--color-ink"),
                            titleColor: superficie,
                            bodyColor: superficie,
                            footerColor: superficie,
                            padding: 10,
                            cornerRadius: 8,
                            boxPadding: 4,
                            titleFont: { family: FUENTE, weight: "600" },
                            bodyFont: { family: FUENTE },
                            footerFont: { family: FUENTE, weight: "600" },
                            callbacks: {
                                title: (items) => cfg.etiquetasLargas[items[0].dataIndex],
                                label: (item) => ` ${item.dataset.label}: ${this.formato(item.raw)}`,
                                footer: (items) =>
                                    apilado ? `Total: ${this.formato(items.reduce((t, it) => t + it.raw, 0))}` : "",
                            },
                        },
                    },
                    scales: {
                        x: {
                            stacked: apilado,
                            grid: { display: false },
                            border: { color: linea },
                            ticks: { color: tenue, font: { size: 11, family: FUENTE } },
                        },
                        y: {
                            stacked: apilado,
                            beginAtZero: true,
                            grid: { color: linea, lineWidth: 1 },
                            border: { display: false },
                            ticks: { color: tenue, font: { size: 11, family: FUENTE }, maxTicksLimit: 5, callback: (v) => compacto.format(v) },
                        },
                    },
                },
            });
        },
    };
}
