<div>
    <div class="flex justify-center overflow-auto my-4" x-data="processFlowChart" wire:ignore>
        <canvas x-ref="canvas" width="800" height="400"></canvas>
    </div>
</div>
@script
    <script>
        Alpine.data('processFlowChart', () => ({
            listeners: [],
            informacion: {
                totalKilos: {{ $resumen->total_kilos }},
                venteadoKilos: {{ $resumen->total_venteado_kilos_ingresados ?? 0 }},
                filtradoKilos: {{ $resumen->total_filtrado_kilos_ingresados ?? 0 }},
                mermas: {
                    ingresoAVenteado: {{ $resumen->merma_ingreso_venteado ?? 0 }},
                    venteadoAFiltrado: {{ $resumen->merma_venteado_filtrado ?? 0 }},
                    ingresoAFiltrado: {{ $resumen->merma_ingreso_filtrado ?? 0 }}
                },
                materialUtil: {
                    venteado: {{ $resumen->material_util_venteado ?? 0 }},
                    filtrado: {{ $resumen->material_util_filtrado ?? 0 }}
                }
            },
            hot: null,
            init() {
                this.iniciarGrafico();
                this.listeners.push(
                    Livewire.on('cargarDataMapaChart', (data) => {
                        console.log('Data recibida canvas', data);
                        const resumen = data[0] ?? {};

                        this.informacion = {
                            totalKilos: parseFloat(resumen.total_kilos) ?? 0,
                            venteadoKilos: parseFloat(resumen.total_venteado_kilos_ingresados) ?? 0,
                            filtradoKilos: parseFloat(resumen.total_filtrado_kilos_ingresados) ?? 0,
                            mermas: {
                                ingresoAVenteado: parseFloat(resumen.merma_ingreso_venteado) ?? 0,
                                venteadoAFiltrado: parseFloat(resumen.merma_venteado_filtrado) ?? 0,
                                ingresoAFiltrado: parseFloat(resumen.merma_ingreso_filtrado) ?? 0
                            },
                            materialUtil: {
                                venteado: parseFloat(resumen.material_util_venteado) ?? 0,
                                filtrado: parseFloat(resumen.material_util_filtrado) ?? 0
                            }
                        };

                        this.iniciarGrafico();
                    })
                );
            },
            iniciarGrafico() {
                const {
                    totalKilos,
                    venteadoKilos,
                    filtradoKilos,
                    mermas,
                    materialUtil
                } = this.informacion
                const canvas = this.$refs.canvas
                const ctx = canvas.getContext('2d')

                if (!ctx) return

                const boxWidth = 150
                const boxHeight = 80
                const arrowLength = 120
                const startX = 50
                const startY = 50

                ctx.clearRect(0, 0, canvas.width, canvas.height)

                const drawBox = (x, y, title, value, color, percentage) => {
                    ctx.shadowColor = "rgba(0, 0, 0, 0.2)"
                    ctx.shadowBlur = 10
                    ctx.shadowOffsetX = 2
                    ctx.shadowOffsetY = 2

                    ctx.fillStyle = color
                    ctx.beginPath()
                    ctx.roundRect?.(x, y, boxWidth, boxHeight, 8)
                    ctx.fill()

                    ctx.shadowColor = "transparent"
                    ctx.shadowBlur = 0
                    ctx.shadowOffsetX = 0
                    ctx.shadowOffsetY = 0

                    ctx.fillStyle = "white"
                    ctx.font = "bold 14px Arial"
                    ctx.textAlign = "center"
                    ctx.fillText(title, x + boxWidth / 2, y + 25)

                    ctx.font = "bold 16px Arial"
                    ctx.fillText(`${value.toFixed(2)} KI`, x + boxWidth / 2, y + 50)

                    if (percentage !== undefined) {
                        ctx.font = "12px Arial"
                        ctx.fillText(`(${percentage.toFixed(1)}%)`, x + boxWidth / 2, y + 70)
                    }
                }

                const drawArrow = (fromX, fromY, toX, toY, text, value, color, modo = 'nomerma') => {
                    ctx.strokeStyle = color
                    ctx.lineWidth = 3
                    ctx.beginPath()
                    ctx.moveTo(fromX, fromY)
                    ctx.lineTo(toX, toY)
                    ctx.stroke()

                    const angle = Math.atan2(toY - fromY, toX - fromX)
                    ctx.beginPath()
                    ctx.moveTo(toX, toY)
                    ctx.lineTo(toX - 15 * Math.cos(angle - Math.PI / 6), toY - 15 * Math.sin(angle - Math
                        .PI / 6))
                    ctx.lineTo(toX - 15 * Math.cos(angle + Math.PI / 6), toY - 15 * Math.sin(angle + Math
                        .PI / 6))
                    ctx.closePath()
                    ctx.fillStyle = color
                    ctx.fill()

                    const textX = (fromX + toX) / 2
                    const textY = (fromY + toY) / 2 - 15

                    const textWidth = ctx.measureText(text).width
                    ctx.fillStyle = "rgba(255, 255, 255, 0)"
                    ctx.fillRect(textX - textWidth / 2 - 5, textY - 12, textWidth + 10, 36)

                    ctx.fillStyle = color

                    if (modo == "merma") {
                        ctx.fillText(text, textX, textY - 15)
                        ctx.fillText(`${Math.abs(value).toFixed(2)} KI`, textX, textY)
                    } else {
                        ctx.fillText(text, textX + 40, textY + 8)
                        ctx.fillText(`${Math.abs(value).toFixed(2)} KI`, textX + 40, textY + 23)
                    }
                }

                // Cajas
                drawBox(startX, startY, "Ingreso Total", totalKilos, "#22c55e", 100)
                drawBox(startX + boxWidth + arrowLength, startY, "Venteado", venteadoKilos, "#3b82f6", (
                    venteadoKilos /
                    totalKilos) * 100)
                drawBox(startX + 2 * (boxWidth + arrowLength), startY, "Filtrado", filtradoKilos, "#8b5cf6", (
                    filtradoKilos / totalKilos) * 100)

                drawBox(startX + boxWidth + arrowLength, startY + boxHeight + 70, "Material Útil", materialUtil
                    .venteado, "#10b981", (materialUtil.venteado / totalKilos) * 100)
                drawBox(startX + 2 * (boxWidth + arrowLength), startY + boxHeight + 70, "Material Útil",
                    materialUtil
                    .filtrado, "#10b981", (materialUtil.filtrado / totalKilos) * 100)

                const mermaColor = "#ef4444"

                drawArrow(startX + boxWidth, startY + boxHeight / 2, startX + boxWidth + arrowLength, startY +
                    boxHeight / 2, "Merma", mermas.ingresoAVenteado, mermaColor, "merma")
                drawArrow(startX + 2 * boxWidth + arrowLength, startY + boxHeight / 2, startX + 2 * boxWidth +
                    2 *
                    arrowLength, startY + boxHeight / 2, "Merma", mermas.venteadoAFiltrado, mermaColor,
                    "merma")
                drawArrow(startX + boxWidth + arrowLength + boxWidth / 2, startY + boxHeight, startX +
                    boxWidth +
                    arrowLength + boxWidth / 2, startY + boxHeight + 60, "Material Útil", materialUtil
                    .venteado,
                    "#10b981")
                drawArrow(startX + 2 * (boxWidth + arrowLength) + boxWidth / 2, startY + boxHeight, startX + 2 *
                    (
                        boxWidth + arrowLength) + boxWidth / 2, startY + boxHeight + 60, "Material Útil",
                    materialUtil.filtrado, "#10b981")

                // Curva
                ctx.strokeStyle = mermaColor
                ctx.lineWidth = 2
                ctx.beginPath()
                ctx.moveTo(startX + boxWidth / 2, startY + boxHeight)

                const cp1x = startX + boxWidth / 2
                const cp1y = startY + boxHeight + 250
                const cp2x = startX + 2 * boxWidth + 2 * arrowLength
                const cp2y = startY + boxHeight + 250
                const endX = startX + 2 * boxWidth + 2 * arrowLength
                const endY = startY + boxHeight

                ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, endX, endY)
                ctx.stroke()

                const angle = Math.PI / 2
                ctx.beginPath()
                ctx.moveTo(endX, endY)
                ctx.lineTo(endX - 10 * Math.cos(angle - Math.PI / 6), endY - 10 * Math.sin(angle - Math.PI / 6))
                ctx.lineTo(endX - 10 * Math.cos(angle + Math.PI / 6), endY - 10 * Math.sin(angle + Math.PI / 6))
                ctx.closePath()
                ctx.fillStyle = mermaColor
                ctx.fill()

                const curveText = "Merma Total"
                const curveTextX = (startX + boxWidth / 2 + endX) / 2
                const curveTextY = startY + boxHeight + 210
                const curveTextWidth = ctx.measureText(curveText).width

                ctx.fillStyle = "rgba(255, 255, 255, 0.8)"
                ctx.fillRect(curveTextX - curveTextWidth / 2 - 5, curveTextY - 12, curveTextWidth + 10, 36)
                ctx.fillStyle = mermaColor
                ctx.font = "12px Arial"
                ctx.textAlign = "center"
                ctx.fillText(curveText, curveTextX, curveTextY)
                ctx.fillText(`${Math.abs(mermas.ingresoAFiltrado).toFixed(2)} KI`, curveTextX, curveTextY + 15)
            }
        }));
    </script>
@endscript
