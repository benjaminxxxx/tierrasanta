<x-app-layout>
    <x-card class="w-full">
        <x-spacing>
            <livewire:seleccionar-labor-component />
            <div class="md:flex justify-between items-center w-full">
                <x-secondary-button id="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> Fecha Anterior
                </x-secondary-button>

                <div class="md:flex gap-4">
                    <x-input type="date" id="fecha" value="{{ date('Y-m-d') }}"
                        class="text-center mx-2 !mt-0 !w-auto" />
                    <x-button id="importarPlanilla">Importar Planilla</x-button>
                </div>

                <x-secondary-button id="fechaPosterior" class="ml-3">
                    Fecha Posterior <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>

    <x-card class="w-full mt-5">
        <x-spacing>
            <div class="w-full">
                <div class="w-full flex justify-end gap-3">
                    <livewire:ver-labores-component />
                    <x-button id="addGroupBtn"><i class="fa fa-plus"></i></x-button>
                    <x-danger-button id="removeGroupBtn"><i class="fa fa-minus"></i></x-danger-button>
                </div>

                <div id="employeesGrid" class="h-[45rem] mt-5 overflow-auto"></div>

                <div class="my-4">
                    <table>
                        <tbody>
                            <tr>
                                <x-th class="!text-left">
                                    TOTAL PLANILLAS ASISTIDO
                                </x-th>
                                <x-td class="w-[10rem]">
                                    <div id="total_planillas_asistido" class="p-2"></div>
                                </x-td>
                            </tr>
                            <tr>
                                <x-th class="!text-left">
                                    TOTAL FALTAS
                                </x-th>
                                <x-td>
                                    <div id="total_faltas" class="p-2"></div>
                                </x-td>
                            </tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL VACACIONES
                                </x-th>
                                <x-td>
                                    <div id="total_vacaciones" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL LICENCIA MATERNIDAD
                                </x-th>
                                <x-td>
                                    <div id="total_licencia_maternidad" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL LICENCIA SIN GOCE
                                </x-th>
                                <x-td>
                                    <div id="total_licencia_sin_goce" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL LICENCIA CON GOCE
                                </x-th>
                                <x-td>
                                    <div id="total_licencia_con_goce" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL DESCANSO MÉDICO
                                </x-th>
                                <x-td>
                                    <div id="total_descanso_medico" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL ATENCIÓN MÉDICA
                                </x-th>
                                <x-td>
                                    <div id="total_atencion_medica" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL CUADRILLAS
                                </x-th>
                                <x-td>
                                    <div id="total_cuadrillas" class="p-2"></div>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th class="!text-left">
                                    TOTAL PLANILLA
                                </x-th>
                                <x-td>
                                    <div id="total_planilla" class="p-2"></div>
                                </x-td>
                            </x-tr>
                        </tbody>

                    </table>
                </div>

                <div class="text-right mt-5">
                    <x-button id="guardarInformacion">Guardar Información</x-button>
                </div>
            </div>
        </x-spacing>
    </x-card>



    <link rel="stylesheet" href="{{ asset('css/handsontable.css') }}">
    <script src="{{ asset('js/handsontable.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            let employeeData = [];
            let groupCount = 0;
            let columns = [];
            let campos = [];
            let totales = {
                asistido: 0,
                faltas: 0,
                vacaciones: 0,
                licenciaMaternidad: 0,
                licenciaSinGoce: 0,
                licenciaConGoce: 0,
                descansoMedico: 0,
                atencionMedica: 0,
                cuadrillas: 0,
                totalPlanilla: 0
            };

            $.ajax({
                url: "{{ route('reporte.reporte_diario.obtener_campo') }}", // Ruta Laravel
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Crear la columna después de obtener los datos
                    campos = data;
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener los datos del campo:', error);
                }
            });

            // Función para calcular los totales
            function calcularTotales() {
                // Reiniciar los totales
                for (let key in totales) {
                    totales[key] = 0;
                }

                const data = hot.getData();

                data.forEach(row => {
                    const asistencia = row[2]; // Suponiendo que la columna de asistencia es la tercera (índice 2)
                    const nCuadrillas = row[3]; // Columna de número de cuadrillas

                    if (asistencia === 'A') totales.asistido++;
                    else if (asistencia === 'F') totales.faltas++;
                    else if (asistencia === 'V') totales.vacaciones++;
                    else if (asistencia === 'LM') totales.licenciaMaternidad++;
                    else if (asistencia === 'LSG') totales.licenciaSinGoce++;
                    else if (asistencia === 'LCG') totales.licenciaConGoce++;
                    else if (asistencia === 'DM') totales.descansoMedico++;
                    else if (asistencia === 'AM') totales.atencionMedica++;

                    // Sumar cuadrillas
                    if (row[1] === 'Cuadrilla') {
                        totales.cuadrillas += nCuadrillas || 0; // Asegurarse de que nCuadrillas sea un número
                    }
                });

                // Calcular el total de la planilla
                totales.totalPlanilla = totales.asistido + totales.faltas + totales.vacaciones +
                    totales.licenciaMaternidad + totales.licenciaSinGoce +
                    totales.licenciaConGoce + totales.descansoMedico +
                    totales.atencionMedica;

                // Actualizar la interfaz
                $('#total_planillas_asistido').text(totales.asistido);
                $('#total_faltas').text(totales.faltas);
                $('#total_vacaciones').text(totales.vacaciones);
                $('#total_licencia_maternidad').text(totales.licenciaMaternidad);
                $('#total_licencia_sin_goce').text(totales.licenciaSinGoce);
                $('#total_licencia_con_goce').text(totales.licenciaConGoce);
                $('#total_descanso_medico').text(totales.descansoMedico);
                $('#total_atencion_medica').text(totales.atencionMedica);
                $('#total_cuadrillas').text(totales.cuadrillas);
                $('#total_planilla').text(totales.totalPlanilla);
            }

            // Función para generar columnas de los grupos dinámicos
            function generateGroupColumns(groupIndex) {
                return [{
                        data: groupIndex * 4 + 4,
                        type: 'dropdown',
                        width: 100,
                        className: 'text-center',
                        source: campos,
                        title: `CAMPO ${groupIndex+1}`
                    },
                    {
                        data: groupIndex * 4 + 5,
                        type: 'text',
                        width: 100,
                        className: 'text-center',
                        title: `LABOR ${groupIndex+1}`
                    },
                    {
                        data: groupIndex * 4 + 6,
                        type: 'time',
                        width: 100,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `ENTRADA ${groupIndex+1}`
                    },
                    {
                        data: groupIndex * 4 + 7,
                        type: 'time',
                        width: 100,
                        timeFormat: 'HH:mm',
                        correctFormat: true,
                        className: 'text-center',
                        title: `SALIDA ${groupIndex+1}`
                    }
                ];
            }

            // Función para actualizar la tabla con el nuevo número de grupos
            function actualizarTablaConGrupos() {
                columns = [{
                        data: 0,
                        type: 'text',
                        width: 150,
                        title: 'DOCUMENTO',
                        className: 'text-center',
                        readOnly: true
                    },
                    {
                        data: 1,
                        type: 'text',
                        width: 380,
                        title: 'APELLIDOS Y NOMBRES'
                    },
                    {
                        data: 2,
                        type: 'dropdown',
                        source: ['A', 'DM', 'V', 'LSG', 'L', 'LCG', 'F', 'LM', 'AM'],
                        width: 60,
                        title: 'ASIST.',
                        className: 'text-center'
                    },
                    {
                        data: 3,
                        type: 'numeric',
                        width: 100,
                        title: 'N° CUADR.',
                        className: '!text-center'
                    }
                ];

                for (let i = 0; i < groupCount; i++) {
                    columns = columns.concat(generateGroupColumns(i));
                }

                hot.updateSettings({
                    columns: columns,
                    colHeaders: columns.map(col => col.title)
                });
            }

            // Función para obtener la cantidad de grupos según la fecha
            function obtenerCantidadGrupos(fecha) {
                return $.get(`/reporte/reporte-diario/obtener-campos`, {
                        fecha: fecha
                    })
                    .done(function(data) {
                        if (data.success) {
                            groupCount = data.campos;
                            actualizarTablaConGrupos(); // Una vez obtenidos los grupos, actualiza la tabla
                            obtenerEmpleados(); // Luego carga los empleados
                        }
                    })
                    .fail(function(error) {
                        console.error('Error al obtener la cantidad de grupos:', error);
                    });
            }

            // Función para cargar empleados
            function obtenerEmpleados() {
                const fecha = $('#fecha').val();
                const url = "{{ route('reporte.reporte_diario.importar_empleados') }}";

                $.post(url, {
                        fecha: fecha,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function(data) {
                        if (data.success) {
                            cargarEmpleados(data.data, data
                                .cuadrillas); // Llama a cargar los empleados si fue exitoso
                        } else {
                            console.error('Error al importar empleados:', data.message);
                        }
                    })
                    .fail(function(error) {
                        console.error('Error en la solicitud:', error);
                    });
            }

            // Función para cargar los empleados a la tabla
            function cargarEmpleados(empleados, cuadrillas) {
                const transformedData = [];

                // Cargar empleados
                empleados.forEach(empleado => {
                    const row = [
                        empleado.documento,
                        empleado.empleado_nombre,
                        empleado.asistencia,
                        null // Espacio para N° CUADR.
                    ];

                    if (empleado.detalles && empleado.detalles.length > 0) {
                        empleado.detalles.forEach(detalle => {
                            row.push(detalle.campo || '');
                            row.push(detalle.labor || '');
                            row.push(detalle.hora_inicio || '');
                            row.push(detalle.hora_salida || '');
                        });
                    } else {
                        // Si no hay detalles disponibles, añade celdas vacías
                        row.push('', '', '', '');
                    }

                    transformedData.push(row);
                });

                // Cargar cuadrillas
                cuadrillas.forEach(cuadrilla => {
                    const row = [
                        null, // Sin documento
                        'Cuadrilla', // Nombre de cuadrilla
                        null, // Asistencia no aplica
                        cuadrilla.numero_cuadrilleros // N° CUADR. de la cuadrilla
                    ];

                    if (cuadrilla.detalles && cuadrilla.detalles.length > 0) {
                        cuadrilla.detalles.forEach(detalle => {
                            row.push(detalle.campo || '');
                            row.push(detalle.labor || '');
                            row.push(detalle.hora_inicio || '');
                            row.push(detalle.hora_salida || '');
                        });
                    } else {
                        // Si no hay detalles disponibles, añade celdas vacías
                        row.push('', '', '', '');
                    }

                    transformedData.push(row);
                });

                // Cargar los datos en la tabla
                hot.loadData(transformedData);
                calcularTotales();
            }

            function cambiarFecha(dias) {
                let fechaActual = new Date($('#fecha').val());
                fechaActual.setDate(fechaActual.getDate() + dias);
                let nuevaFecha = fechaActual.toISOString().split('T')[0]; // Formato YYYY-MM-DD
                $('#fecha').val(nuevaFecha).trigger('change'); // Cambia la fecha y lanza el evento change
            }

            // Inicializar Handsontable
            const container = document.getElementById('employeesGrid');
            const hot = new Handsontable(container, {
                data: employeeData,
                colHeaders: true,
                rowHeaders: true,
                columns: columns,
                width: '100%',
                height: '90%',
                manualColumnResize: true,
                manualRowResize: true,
                fixedColumnsLeft: 2,
                minSpareRows: 1, // Permite agregar una fila vacía adicional para insertar más filas

                licenseKey: 'non-commercial-and-evaluation'
            });

            hot.addHook('afterChange', function(changes, source) {
                if (source !== 'loadData') {
                    calcularTotales();
                }
            });
            // Actualizar al cambiar la fecha
            $('#fecha').on('change', function() {
                const fechaSeleccionada = $(this).val();
                obtenerCantidadGrupos(fechaSeleccionada); // Llama a obtener la cantidad de grupos por fecha
            });
            $('#importarPlanilla').on('click', function() {
                const fechaSeleccionada = $(this).val();
                obtenerCantidadGrupos(fechaSeleccionada);
                Swal.fire({
                    toast: true,
                    position: 'top-end', // Puedes cambiar la posición
                    icon: 'success', // Tipo de alerta (success, error, warning, info)
                    title: 'Información actualizada',
                    showConfirmButton: false, // No mostrar botón de confirmación
                    timer: 2000,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            });


            // Evento para Fecha Anterior
            $('#fechaAnterior').on('click', function() {
                cambiarFecha(-1); // Resta 1 día a la fecha actual
            });

            // Evento para Fecha Posterior
            $('#fechaPosterior').on('click', function() {
                cambiarFecha(1); // Suma 1 día a la fecha actual
            });

            // Botones para añadir o eliminar grupos
            $('#addGroupBtn').on('click', function() {
                groupCount++;
                actualizarTablaConGrupos(); // Actualiza la tabla al agregar un grupo
                actualizarCampos(groupCount); // Envía la actualización al backend
            });

            $('#removeGroupBtn').on('click', function() {
                if (groupCount > 1) {
                    groupCount--;
                    actualizarTablaConGrupos(); // Actualiza la tabla al quitar un grupo
                    actualizarCampos(groupCount); // Envía la actualización al backend
                }
            });

            // Función para actualizar la cantidad de campos en el backend
            function actualizarCampos(groupCount) {
                const url = "{{ route('reporte.reporte_diario.actualizar_campos') }}";

                $.post(url, {
                        fecha: $('#fecha').val(),
                        campos: groupCount,
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function(data) {
                        if (data.success) {
                            console.log('Cantidad de campos actualizada correctamente.');
                        } else {
                            console.error('Error al actualizar los campos');
                        }
                    })
                    .fail(function(error) {
                        console.error('Error al actualizar los campos:', error);
                    });
            }

            // Guardar la información
            $('#guardarInformacion').on('click', function() {
                const data = hot.getData();
                const url = "{{ route('reporte.reporte_diario.guardar_informacion') }}";
                //console.log(data);
                $.post(url, {
                        empleados: data,
                        totales: totales,
                        fecha: $('#fecha').val(),
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function(result) {
                        if (result.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end', // Puedes cambiar la posición
                                icon: 'success', // Tipo de alerta (success, error, warning, info)
                                title: 'Información guardada correctamente',
                                showConfirmButton: false, // No mostrar botón de confirmación
                                timer: 2000,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                }
                            });
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top-end', // Puedes cambiar la posición
                                icon: 'error', // Tipo de alerta (success, error, warning, info)
                                title: 'Error al guardar la información: ' + result.message,
                                showConfirmButton: false, // No mostrar botón de confirmación
                                timer: 2000,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                }
                            });
                        }
                    })
                    .fail(function(error) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end', // Puedes cambiar la posición
                            icon: 'error', // Tipo de alerta (success, error, warning, info)
                            title: 'Error al guardar la información: ' + error,
                            showConfirmButton: false, // No mostrar botón de confirmación
                            timer: 2000,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer);
                                toast.addEventListener('mouseleave', Swal.resumeTimer);
                            }
                        });
                    });
            });

            // Inicialmente obtener los campos y empleados para la fecha actual
            obtenerCantidadGrupos($('#fecha').val());
        });
    </script>
</x-app-layout>
