<x-app-layout>


    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <div id="app">
        <x-card>
            <x-spacing>
                <div class="block md:flex items-center gap-5">
                    <x-h2>
                        Planilla de Horas Trabajadas
                    </x-h2>
                </div>
                <div class="flex ga-5 my-10">
                    <div>
                        <x-label for="mes">Mes</x-label>
                        <x-select class="uppercase" id="mes" v-model="selectedMes" @change="fetchDias">
                            <option value="">Seleccionar Mes</option>
                            @foreach ($meses as $mes)
                            <option value="{{$mes['value']}}">{{$mes['label']}}</option>
                            @endforeach
                            
                        </x-select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse table-fixed border border-gray" cellpadding="3" cellspacing="1" style="font-size: 14px">
                        <thead>
                            <tr>
                                <th class="sticky left-0 bg-whiten z-10 border border-gray" style="max-width:40px">
                                    <div style="min-width:40px">
                                        N°
                                    </div>
                                </th>
                                <th class="sticky left-[40px] bg-whiten z-10 text-left border border-gray" style="max-width:140px">
                                    <div style="min-width:140px">
                                        N° de Orden
                                    </div>
                                </th>
                                <th class="sticky left-[180px] bg-whiten z-10 text-left border border-gray" style="max-width:160px">
                                    <div style="min-width:160px">
                                        Nombres
                                    </div>
                                </th>
                                <th v-for="dia in dias" class="border border-gray" :class="dia.es_dia_domingo ? 'bg-yellow-300 min-w-[25px]' : ''">
                                    @{{ dia.dia }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(empleado, index) in empleados" :key="empleado.id">
                                <td class="sticky left-0 bg-whiten z-10 text-center border border-gray">@{{ index + 1 }}</td>
                                <td class="sticky left-[40px] bg-whiten z-10 border border-gray">@{{ empleado.cargo_nombre }}</td>
                                <td class="sticky left-[180px] bg-whiten z-10 border border-gray">@{{ empleado.nombreCompleto }}</td>
                            
                                <th v-for="dia in dias" :key="dia.dia" class="border border-gray" :class="dia.es_dia_domingo ? 'bg-yellow-300 min-w-[25px]' : ''">
                                    <template v-if="!dia.es_dia_domingo">
                                        <input type="text" value="8" class="w-[35px] text-center border-none focus:outline-none focus:ring-0 focus:shadow-none"/>
                                    </template>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                    
                </div>
                
            </x-spacing>
        </x-card>
    </div>

    <script>
        const {
            createApp,
            ref
        } = Vue

        createApp({
            setup() {
                const currentMonth = new Date().getMonth() + 1;
                const selectedMes = ref('');
                const dias = ref([]);
                const empleados = ref([]);

                const fetchDias = async () => {
                    if (selectedMes.value) {
                        try {
                            const response = await axios.get('/planilla/asistencia/cargar-asistencias', {
                                params: {
                                    mes: selectedMes.value,
                                    anio: new Date().getFullYear() // Año actual
                                }
                            });
                            dias.value = response.data.dias;
                            empleados.value = response.data.empleados;
                        } catch (error) {
                            console.error('Error fetching days:', error);
                        }
                    } else {
                        // Limpiar los días si no se selecciona un mes
                        dias.value = [];
                        empleados.value = [];
                    }
                };

                return {
                    selectedMes,
                    dias,
                    fetchDias,
                    empleados
                }
            }
        }).mount('#app')
    </script>
</x-app-layout>
