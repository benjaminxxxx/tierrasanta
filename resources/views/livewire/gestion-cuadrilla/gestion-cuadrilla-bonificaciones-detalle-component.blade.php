<div x-data="bonificacionesDetalle">
   <div class="space-y-4 max-w-[34rem]">

      <x-flex class="mt-3 mb-2">
         <x-h3>Bonificación</x-h3>
         <x-secondary-button @click="agregarTramo">
            <i class="fa fa-plus"></i> Agregar Tramo
         </x-secondary-button>
      </x-flex>

      <div class="overflow-x-auto">
         <x-flex class="">
            <x-label>La bonificacion se aplicara a partir de:</x-label>
            <x-input-number x-model="estandarProduccion" @input="calcularBonos" step="0.1" class="!w-24"
               placeholder="Producción" />
            <x-label>{{ $unidades }}</x-label>
         </x-flex>
         <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="">
               <tr>
                  <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                     Hasta (unidades)
                  </th>
                  <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                     Se paga S/.
                  </th>
                  <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                     Acciones
                  </th>
               </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
               <template x-for="(tramo, index) in tramos" :key="index">
                  <tr class="">
                     <!-- Hasta -->
                     <td class="px-4 py-2">
                        <x-input-number class="w-full" x-model="tramo.hasta" @input="calcularBonos" />
                     </td>

                     <!-- Monto -->
                     <td class="px-4 py-2">
                        <x-input-number class="w-full" step="0.1" x-model="tramo.monto" @input="calcularBonos" />
                     </td>

                     <!-- Remove button -->
                     <td class="px-4 py-2 text-center">
                        <x-danger-button @click="removerTramo(index)">
                           <i class="fa fa-trash"></i>
                        </x-danger-button>
                     </td>
                  </tr>
               </template>
            </tbody>
         </table>
      </div>

   </div>

   <div wire:ignore class="mt-4">
      <x-flex class="justify-end mb-3">
         <x-button @click="agregarRecojo">
            <i class="fa fa-plus"></i> Agregar recojo
         </x-button>
         <x-danger-button @click="quitarRecojo">
            <i class="fa fa-minus"></i> Quitar recojo
         </x-danger-button>
      </x-flex>
      <div x-ref="tableBonificacionesCuadrilleros"></div>
   </div>

   <x-flex class="w-full justify-end mt-4">
      <x-button @click="guardarBonificaciones">
         <i class="fa fa-save"></i> Actualizar bonificaciones
      </x-button>
   </x-flex>

   <x-loading wire:loading/>

</div>
@script
<script>
   Alpine.data('bonificacionesDetalle', () => ({
      hot: null,
      tramos: @entangle('tramos'),
      recojos: @entangle('recojos'),
      tableDataBonificados: @js($tableDataBonificados),
      estandarProduccion: @entangle('estandarProduccion'),
      init() {
         this.initTable();
      },
      initTable() {
         if (this.hot) {
            this.hot.destroy();
         }

         const container = this.$refs.tableBonificacionesCuadrilleros;
         this.hot = new Handsontable(container, {
            data: this.tableDataBonificados,
            //themeName: 'ht-theme-main-dark-auto',
            colHeaders: true,
            rowHeaders: true,
            columns: this.generarColumnasDinamicas(),
            width: '100%',
            height: 'auto',
            stretchH: 'all',
            autoColumnSize: true,
            fixedColumnsLeft: 2,

            afterChange: (changes, source) => {
               if (source === 'loadData' || !changes) return;

               this.calcularBonos();
            },
            licenseKey: 'non-commercial-and-evaluation',
         });
      },
      agregarRecojo() {
         this.recojos++;
         const columns = this.generarColumnasDinamicas();
         this.hot.updateSettings({
            columns: columns
         });
      },
      quitarRecojo() {
         if (this.recojos <= 1) {
            return;
         }
         this.recojos--;
         const columns = this.generarColumnasDinamicas();
         this.hot.updateSettings({
            columns: columns
         });
      },
      generarColumnasDinamicas() {
         const cols = [];

         // ➤ Trabajador
         cols.push({
            data: 'tipo',
            title: 'Tipo',
            readOnly: true,
            className: 'font-bold !text-left !bg-gray-100'
         }, {
            data: 'nombre_trabajador',
            title: 'Trabajador',
            readOnly: true,
            className: 'font-bold !text-left !bg-gray-100'
         }, {
            data: 'campo',
            title: 'Campo',
            readOnly: true,
            className: 'font-bold !text-center !bg-gray-100'
         }, {
            data: 'labor',
            title: 'Labor',
            readOnly: true,
            className: 'font-bold !text-center !bg-gray-100'
         }, {
            data: 'horarios',
            title: 'Horarios',
            readOnly: true,
            className: 'font-bold !text-center !bg-gray-100'
         }, {
            data: 'rango_total_horas',
            title: 'Rango<br/>Horas',
            readOnly: true,
            className: 'font-bold !text-center !bg-gray-100'
         });
         // ➤ Producciones según cantidad máxima
         for (let i = 1; i <= this.recojos; i++) {
            cols.push({
               data: `produccion_${i}`,
               title: `Recojo ${i}`,
               type: 'numeric',
               numericFormat: { pattern: '0,0.00' },
               allowInvalid: false,
               className: '!text-center !text-lg'
            });
         }

         // ➤ Total Bono
         cols.push({
            data: 'total_bono',
            title: 'Total Bono',
            readOnly: true,
            type: 'numeric',
            numericFormat: { pattern: '0,0.00' },
            className: '!bg-yellow-100 !text-center font-bold'
         }, {
            data: 'total_horas',
            title: 'Total Horas',
            readOnly: true,
            type: 'numeric',
            numericFormat: { pattern: '0,0.00' },
            className: '!bg-yellow-100 !text-center font-bold'
         });

         return cols;
      },
      agregarTramo() {
         this.tramos.push({ hasta: '', monto: '' });
         this.calcularBonos();
      },
      removerTramo(index) {
         this.tramos.splice(index, 1);
         this.calcularBonos();
      },
      calcularBonos() {
      
         if (!this.estandarProduccion || this.estandarProduccion <= 0) {
            // No hay bono si el estándar no está definido
            this.tableDataBonificados.forEach(trabajador => trabajador.total_bono = 0);
            return;
         }

         this.tableDataBonificados.forEach(trabajador => {
            // Sumatoria de todas las cantidades
            let sumaCantidad = 0;
            for (let i = 1; i <= this.recojos; i++) {
               const key = `produccion_${i}`;
               sumaCantidad += parseFloat(trabajador[key] || 0);
            }
            
            //calcular el estandar esperado
            const totalHorasTrabajadas = parseFloat(trabajador.total_horas || 0);
            const estandarEsperado = (this.estandarProduccion / 8) * totalHorasTrabajadas;
console.log(trabajador.total_horas);
            // Calcular excedente
            let excedente = sumaCantidad - estandarEsperado;
            if (excedente <= 0) {
               trabajador.total_bono = 0;
               return;
            }

            let bonoTotal = 0;
            let restante = excedente;

            // Ordenar tramos por hasta (opcional, seguridad)
            const tramosOrdenados = [...this.tramos].sort((a, b) => a.hasta - b.hasta);

            tramosOrdenados.forEach(tramo => {
               const tramoHasta = parseFloat(tramo.hasta);
               const tramoMonto = parseFloat(tramo.monto);

               if (restante <= 0) return;

               if (restante >= tramoHasta) {
                  bonoTotal += tramoHasta * tramoMonto;
                  restante -= tramoHasta;
               } else {
                  bonoTotal += restante * tramoMonto;
                  restante = 0;
               }
            });

            // Si todavía queda excedente, se paga al último tramo
            if (restante > 0 && this.tramos.length > 0) {
               const ultimoMonto = parseFloat(this.tramos[this.tramos.length - 1].monto);
               bonoTotal += restante * ultimoMonto;
            }

            trabajador.total_bono = bonoTotal.toFixed(2);
         });

         this.hot.render(); // actualiza la tabla, si no lo pongo, si actualiza pero con una funcion de retraso
      },
      guardarBonificaciones() {
         let allData = [];
         for (let row = 0; row < this.hot.countRows(); row++) {
            const rowData = this.hot.getSourceDataAtRow(row);
            allData.push(rowData);
         }

         const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !== null && cell !== ''));
         $wire.guardarBonificaciones(filteredData);
      }
   }));
</script>
@endscript