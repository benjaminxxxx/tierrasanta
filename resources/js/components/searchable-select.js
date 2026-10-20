/**
 * Lógica de <x-searchable-select>: buscar en una lista y elegir (flechas ↑ ↓, Enter, Escape).
 *
 * Tres modos (los decide el componente Blade):
 * - "fija":     la lista viene del servidor en data-opciones y se recarga sola cuando Livewire la cambia.
 * - "enlazada": la lista es una propiedad Livewire (@entangle) que el componente padre puede cambiar.
 * - "nativo":   <x-select searchable>: la lista son los <option> de un <select> oculto (x-ref="nativo"), que sigue
 *               siendo el que tiene el wire:model; al elegir se escribe en él y se dispara "change".
 * - "buscar":   cada vez que se escribe se llama a un método Livewire ($wire[metodo](texto)) que devuelve
 *               [{id, name}]; el valor se envía al servidor al elegir (como el antiguo x-select-dropdown).
 *               Ese método debe llevar #[\Livewire\Attributes\Renderless]: si re-renderiza el componente (sobre todo
 *               dentro de un modal con x-trap) el buscador pierde el foco a cada tecla.
 *
 * Opciones: [{id, name}]. El valor elegido va al wire:model (@entangle).
 */
const vacio = (v) => v === null || v === undefined || v === "";

export default function searchableSelect(config) {
    return {
        modo: config.modo,
        valor: config.valor,
        opciones: config.opciones ?? [],
        metodo: config.metodo,
        modelo: config.modelo,
        minimo: config.minimo ?? 2,
        etiqueta: config.etiqueta || "",

        texto: "",
        seleccion: null,
        escribiendo: false,
        abierto: false,
        resaltado: -1,
        resultados: [],
        cargando: false,

        init() {
            if (this.modo === "nativo") {
                this.select = this.$refs.nativo;
                this.opciones = this.leerNativo();
                this.valor = this.select.value || null;
                // Livewire cambia las <option> al volver a dibujar
                this._observador = new MutationObserver(() => {
                    this.opciones = this.leerNativo();
                });
                this._observador.observe(this.select, { childList: true, subtree: true, characterData: true, attributes: true });
                // y el valor del select cuando cambia la propiedad en el servidor
                if (this.modelo) {
                    this.$wire.$watch(this.modelo.split(".")[0], () => this.$nextTick(() => (this.valor = this.select.value || null)));
                }
            }
            if (this.modo === "fija") {
                this.opciones = this.leerOpciones();
                // Livewire actualiza el atributo al volver a dibujar: recargar la lista
                this._observador = new MutationObserver(() => {
                    this.opciones = this.leerOpciones();
                });
                this._observador.observe(this.$el, { attributes: true, attributeFilter: ["data-opciones"] });
            }

            this.sincronizar(false);
            this.$watch("valor", () => this.sincronizar(false));
            if (this.modo !== "buscar") {
                this.$watch("opciones", () => this.sincronizar(true));
            }

            if (this.modo === "buscar") {
                // El servidor puede cambiar el texto mostrado (p. ej. un modal que abre otro registro)
                const ponerEtiqueta = ({ model, etiqueta }) => {
                    if (model === this.modelo) {
                        this.etiqueta = etiqueta || "";
                        this.resultados = [];
                        this.sincronizar(false);
                        this.cerrar();
                    }
                };
                this._quitar = [
                    Livewire.on("selectDropdownEtiqueta", ponerEtiqueta),
                    Livewire.on("searchableEtiqueta", ponerEtiqueta),
                ];
            }
        },

        destroy() {
            this._observador?.disconnect();
            this._quitar?.forEach((quitar) => quitar?.());
        },

        leerNativo() {
            return Array.from(this.select.options)
                .filter((o) => o.value !== "" && !o.disabled)
                .map((o) => ({ id: o.value, name: o.textContent.trim() }));
        },

        leerOpciones() {
            try {
                return JSON.parse(this.$el.dataset.opciones || "[]");
            } catch (e) {
                return [];
            }
        },

        /** Muestra la opción del valor actual. Si la lista cambió y el valor ya no está, lo limpia. */
        sincronizar(cambioLista) {
            if (vacio(this.valor)) {
                this.seleccion = null;
                if (!this.escribiendo) this.texto = "";
                return;
            }
            if (this.modo === "buscar") {
                const nombre = this.seleccion && this.seleccion.id == this.valor ? this.seleccion.name : this.etiqueta;
                this.seleccion = { id: this.valor, name: nombre || String(this.valor) };
                if (!this.escribiendo) this.texto = nombre || "";
                return;
            }
            const encontrada = (this.opciones || []).find((o) => o.id == this.valor);
            if (encontrada) {
                this.seleccion = encontrada;
                if (!this.escribiendo) this.texto = encontrada.name;
            } else if (cambioLista && (this.opciones || []).length) {
                this.seleccion = null;
                this.texto = "";
                this.valor = null;
            }
        },

        get lista() {
            if (this.modo === "buscar") return this.resultados;
            const todas = this.opciones || [];
            if (!this.escribiendo || !this.texto.trim()) return todas;
            const buscado = this.normalizar(this.texto);
            return todas.filter((o) => this.normalizar(o.name).includes(buscado));
        },

        normalizar(t) {
            return String(t ?? "")
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        },

        alEscribir() {
            this.escribiendo = true;
            this.abierto = true;
            if (this.modo === "buscar") {
                this.consultar();
            } else {
                this.resaltado = this.lista.length ? 0 : -1;
            }
        },

        async consultar() {
            const texto = this.texto.trim();
            if (texto.length < this.minimo) {
                this.resultados = [];
                this.resaltado = -1;
                return;
            }
            this.cargando = true;
            try {
                const pedido = (this._pedido = (this._pedido || 0) + 1);
                const datos = await this.$wire[this.metodo](texto);
                if (pedido !== this._pedido) return; // llegó una respuesta más nueva
                this.resultados = Array.isArray(datos) ? datos : Object.values(datos || {});
                this.resaltado = this.resultados.length ? 0 : -1;
                this.abierto = true;
            } catch (e) {
                console.error(e);
            } finally {
                this.cargando = false;
            }
        },

        abrir() {
            this.abierto = true;
            this.resaltado = this.lista.findIndex((o) => this.seleccion && o.id == this.seleccion.id);
            if (this.modo === "buscar" && this.escribiendo && !this.resultados.length) this.consultar();
        },

        cerrar() {
            this.abierto = false;
            this.resaltado = -1;
            this.escribiendo = false;
            this.texto = this.seleccion ? this.seleccion.name : "";
        },

        alternar() {
            this.abierto ? this.cerrar() : (this.abrir(), this.$refs.entrada.focus());
        },

        bajar() {
            if (!this.abierto) return this.abrir();
            if (!this.lista.length) return;
            this.resaltado = this.resaltado < this.lista.length - 1 ? this.resaltado + 1 : 0;
            this.mostrarResaltado();
        },

        subir() {
            if (!this.abierto) return this.abrir();
            if (!this.lista.length) return;
            this.resaltado = this.resaltado > 0 ? this.resaltado - 1 : this.lista.length - 1;
            this.mostrarResaltado();
        },

        mostrarResaltado() {
            this.$nextTick(() => this.$refs.lista?.children[this.resaltado]?.scrollIntoView({ block: "nearest" }));
        },

        elegirResaltado() {
            const opcion = this.lista[this.resaltado];
            if (this.abierto && opcion) this.elegir(opcion);
        },

        elegir(opcion) {
            this.seleccion = opcion;
            this.etiqueta = opcion.name;
            this.escribiendo = false;
            this.texto = opcion.name;
            this.resultados = [];
            this.cerrar();
            this.guardar(opcion.id);
        },

        limpiar() {
            this.seleccion = null;
            this.etiqueta = "";
            this.texto = "";
            this.resultados = [];
            this.escribiendo = false;
            this.guardar(null);
            this.$nextTick(() => this.$refs.entrada.focus());
        },

        guardar(id) {
            this.valor = id;
            if (this.modo === "nativo") {
                this.select.value = id ?? "";
                this.select.dispatchEvent(new Event("input", { bubbles: true }));
                this.select.dispatchEvent(new Event("change", { bubbles: true }));
                return;
            }
            // En modo buscar el valor se envía en el momento (así funcionaba x-select-dropdown)
            if (this.modo === "buscar" && this.modelo) this.$wire.set(this.modelo, id);
        },
    };
}
