<div>
    @php
        $menu = [
            [
                'label' => 'Campaña',
                'children' => [
                    [
                        'label' => 'Registrar Campaña',
                        'action' => 'dispatch',
                        'event' => 'registroCampania',
                    ],
                ],
            ],
            [
                'label' => 'Evaluación Campo',
                'children' => [
                    [
                        'label' => 'Proyección Rendimiento Poda',
                        'route' => 'reporte_campo.evaluacion_proyeccion_rendimiento_poda',
                    ],
                ],
            ],
            [
                'label' => 'Insumos y Proveedores',
                'children' => [
                    [
                        'label' => 'Proveedores',
                        'route' => 'proveedores.index',
                    ],
                    [
                        'label' => 'Registrar Proveedor',
                        'action' => 'dispatch',
                        'event' => 'crearProveedor',
                    ],
                ],
            ],
        ];
    @endphp

    <nav class="menubar z-[9999]">
        <ul class="menubar-root">
            @foreach ($menu as $item)
                <li class="menu-item">
                    <span class="menu-label">{{ $item['label'] }}</span>

                    @if (!empty($item['children']))
                        <x-menubar.menu :items="$item['children']" />
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <style>
        :root {
            --menubar-bg: #ffffff;
            --menubar-border: #e5e7eb;
            --menubar-text: #1f2937;
            --menubar-hover-bg: #f3f4f6;
            --menu-bg: #ffffff;
            --menu-border: #e5e7eb;
            --menu-text: #1f2937;
            --menu-hover-bg: #2563eb;
            --menu-hover-text: #ffffff;
            --menu-shadow: rgba(0, 0, 0, 0.1);
        }

        /* Dark mode variables */
        @media (prefers-color-scheme: dark) {
            :root {
                --menubar-bg: #252526;
                --menubar-border: #3e3e42;
                --menubar-text: #cccccc;
                --menubar-hover-bg: #2d2d30;
                --menu-bg: #2d2d30;
                --menu-border: #3e3e42;
                --menu-text: #cccccc;
                --menu-hover-bg: #0e639c;
                --menu-hover-text: #ffffff;
                --menu-shadow: rgba(0, 0, 0, 0.5);
            }
        }

        /* Reduced height to 32px for VS Code-like appearance */
        .menubar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 32px;
            background: var(--menubar-bg);
            border-bottom: 1px solid var(--menubar-border);
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            box-shadow: 0 1px 2px var(--menu-shadow);
            transition: background-color 0.2s ease;
        }

        .menubar-root {
            display: flex;
            height: 100%;
            margin: 0;
            padding: 0 8px;
            list-style: none;
            align-items: center;
            gap: 0;
        }

        /* Reduced padding and compact spacing */
        .menubar-root>.menu-item {
            position: relative;
            padding: 0 8px;
            display: flex;
            align-items: center;
            cursor: default;
            height: 100%;
            transition: background-color 0.15s ease;
        }

        .menubar-root>.menu-item:hover {
            background: var(--menubar-hover-bg);
        }

        /* Cleaner font styling without decorations */
        .menu-label {
            font-size: 13px;
            font-weight: 400;
            color: var(--menubar-text);
            letter-spacing: 0;
            user-select: none;
            transition: color 0.15s ease;
        }

        .menubar-root>.menu-item:hover .menu-label {
            color: var(--menubar-text);
        }

        .menu {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background: var(--menu-bg);
            border: 1px solid var(--menu-border);
            border-top: none;
            box-shadow: 0 10px 25px var(--menu-shadow);
            list-style: none;
            margin: 0;
            padding: 4px 0;
            display: none;
            z-index: 1001;
            transition: opacity 0.15s ease;
        }

        /* Improved submenu styling */
        .menu .menu {
            top: 0;
            left: 100%;
            margin-left: 2px;
            border-top: 1px solid var(--menu-border);
        }

        .menu-item:hover>.menu {
            display: block;
        }

        /* Reduced padding for compact look */
        .menu-button {
            width: 100%;
            padding: 4px 8px;
            text-align: left;
            background: none;
            border: none;
            font-size: 13px;
            color: var(--menu-text);
            cursor: default;
            white-space: nowrap;
            transition: all 0.12s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 400;
        }

        .menu-button:hover {
            background: var(--menu-hover-bg);
            color: var(--menu-hover-text);
        }

        /* Arrow only shows for items with submenus */
        .submenu-arrow {
            font-size: 12px;
            pointer-events: none;
            margin-left: 16px;
            opacity: 0.6;
            transition: opacity 0.15s ease;
        }

        .menu-item:hover>.submenu-arrow {
            opacity: 1;
        }

        .menu-item {
            position: relative;
        }
    </style>

</div>
