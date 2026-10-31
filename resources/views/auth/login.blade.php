{{-- Login según DESIGN.md: panel de marca en Noche + formulario sobre Papel; en celular solo el formulario --}}
<x-guest-layout>
    <div class="w-full max-w-5xl">
        <div class="flex flex-col lg:flex-row overflow-hidden rounded-2xl border border-mist bg-paper lg:min-h-[34rem]">

            {{-- Panel de marca --}}
            <div class="relative hidden lg:flex lg:w-[45%] flex-col justify-between bg-noche p-10 text-white">
                <img src="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('horizontal_claro') }}" alt="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}" class="h-14 w-auto self-start" />

                <div>
                    <p class="text-[32px] font-semibold leading-tight tracking-[-0.02em]">
                        Sistema<br>administrativo
                    </p>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/70">
                        Campo, cochinilla, planilla, almacén y costos en un solo lugar.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs text-white/50">
                    <span class="h-1.5 w-6 rounded-full bg-carmin"></span>
                    {{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}
                </div>
            </div>

            {{-- Formulario --}}
            <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-10">
                <div class="w-full max-w-sm">
                    <img src="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('horizontal') }}" alt="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}" class="mb-8 h-12 w-auto lg:hidden dark:hidden" />
                    <img src="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('horizontal_claro') }}" alt="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}" class="mb-8 hidden h-12 w-auto dark:block lg:!hidden" />

                    <h1 class="text-2xl font-semibold tracking-[-0.01em] text-ink">Iniciar sesión</h1>
                    <p class="mt-1 text-sm text-graphite">Ingresa con tu correo y tu clave.</p>

                    @session('status')
                        <div class="mt-6 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-400/10 dark:text-green-400">
                            {{ $value }}
                        </div>
                    @endsession

                    @if ($errors->any())
                        <div class="mt-6 flex gap-2 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-400/10 dark:text-red-400" role="alert">
                            <i class="fa fa-circle-exclamation mt-0.5"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" x-data="{ ver: false, enviando: false }" @submit="enviando = true">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-ink">Correo</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                placeholder="nombre@empresa.com"
                                class="h-10 w-full rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink placeholder:text-steel
                                       focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25" />
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-ink">Clave</label>
                            <div class="relative">
                                <input id="password" :type="ver ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                                    class="h-10 w-full rounded-[10px] border border-mist bg-paper pl-3 pr-10 text-sm text-ink placeholder:text-steel
                                           focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25" />
                                <button type="button" @click="ver = !ver" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-steel hover:text-ink"
                                    :title="ver ? 'Ocultar clave' : 'Mostrar clave'" :aria-label="ver ? 'Ocultar clave' : 'Mostrar clave'">
                                    <i class="fa fa-eye" :class="ver ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <label for="remember_me" class="flex items-center gap-2 text-sm text-graphite">
                            <input id="remember_me" type="checkbox" name="remember"
                                class="h-4 w-4 rounded border-mist text-carmin focus:ring-carmin/25" />
                            Recordar sesión en este equipo
                        </label>

                        <button type="submit" :disabled="enviando"
                            class="flex h-10 w-full items-center justify-center gap-2 rounded-[10px] bg-carmin text-sm font-semibold text-white transition-colors
                                   hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-70">
                            <i class="fa fa-spinner fa-spin" x-show="enviando" x-cloak></i>
                            Ingresar
                        </button>
                    </form>

                    <p class="mt-6 text-xs text-pewter">¿Olvidaste tu clave? Pide al administrador del sistema que la restablezca.</p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
